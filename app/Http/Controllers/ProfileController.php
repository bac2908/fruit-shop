<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\OrderReturnRequest;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\UserAddress;
use App\Models\WishlistItem;
use App\Services\OrderCancellationService;
use App\Services\OrderReturnService;
use App\Services\VietnamAddressService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProfileController extends Controller
{
    public function show(Request $request, VietnamAddressService $addressService)
    {
        $user = $request->user()->load([
            'addresses' => function ($query) {
                $query->orderByDesc('is_default')->latest();
            },
            'wishlistItems.product.images',
            'productViews.product.images',
            'vouchers.coupon',
        ]);

        $orders = Order::query()
            ->with(['items.product', 'statusHistories', 'cancellationRequests', 'returnRequests'])
            ->where('user_id', $user->id)
            ->latest()
            ->take(6)
            ->get();

        $orderSummary = $this->getOrderSummary($user->id);
        $membership = $this->getMembership($orderSummary['total_spent']);

        $wishlistItems = $user->wishlistItems
            ->filter(function ($item) {
                return $item->product;
            })
            ->sortByDesc('created_at')
            ->take(6)
            ->values();

        $recentViews = $user->productViews
            ->filter(function ($view) {
                return $view->product;
            })
            ->sortByDesc('last_viewed_at')
            ->take(6)
            ->values();

        $personalVouchers = $user->vouchers
            ->filter(function ($voucher) {
                return (bool) $voucher->coupon;
            })
            ->sortBy(function ($voucher) {
                return [
                    $voucher->is_usable ? 0 : 1,
                    optional($voucher->effective_expires_at)->timestamp ?? PHP_INT_MAX,
                ];
            })
            ->values();

        $personalCouponIds = $personalVouchers->pluck('coupon_id')->all();

        $availableCoupons = Coupon::query()
            ->valid()
            ->where('is_public', true)
            ->when(!empty($personalCouponIds), function ($query) use ($personalCouponIds) {
                $query->whereNotIn('id', $personalCouponIds);
            })
            ->orderByRaw('ends_at IS NULL')
            ->orderBy('ends_at')
            ->take(12)
            ->get()
            ->filter(function ($coupon) use ($user) {
                return !$coupon->getInvalidReason(0, $user->id, $user->email);
            })
            ->take(6)
            ->values();

        $usedCoupons = CouponUsage::query()
            ->with('coupon')
            ->where('user_id', $user->id)
            ->latest('used_at')
            ->take(5)
            ->get();

        return view('account.profile', [
            'user' => $user,
            'orders' => $orders,
            'orderSummary' => $orderSummary,
            'membership' => $membership,
            'wishlistItems' => $wishlistItems,
            'recentViews' => $recentViews,
            'personalVouchers' => $personalVouchers,
            'availableCoupons' => $availableCoupons,
            'usedCoupons' => $usedCoupons,
            'cancellationReasons' => OrderCancellationRequest::reasonLabels(),
            'returnReasons' => OrderReturnRequest::reasonLabels(),
            'returnTypes' => OrderReturnRequest::typeLabels(),
            'refundMethods' => OrderReturnRequest::refundMethodLabels(),
            'returnWindowHours' => (int) config('shop.returns.request_window_hours', 24),
            'vietnamProvinces' => $addressService->provincesForSelect(),
            'vietnamAddressDataUrl' => asset('data/vietnam-addresses.json'),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->merge([
            'name' => $this->cleanTextInput($request->input('name')),
            'phone' => $this->normalizeVietnamPhone($request->input('phone')),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100', 'not_regex:/[<>]/'],
            'phone' => [
                'nullable',
                'string',
                'regex:/^\+84[0-9]{9}$/',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'birthday' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other', 'unspecified'])],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Vui lòng nhập họ và tên.',
            'name.min' => 'Họ tên phải có ít nhất 2 ký tự.',
            'name.max' => 'Họ tên không được vượt quá 100 ký tự.',
            'phone.regex' => 'Số điện thoại cần theo định dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.',
            'phone.unique' => 'Số điện thoại này đã được sử dụng.',
            'birthday.date' => 'Ngày sinh không hợp lệ.',
            'birthday.before_or_equal' => 'Ngày sinh không được lớn hơn hôm nay.',
            'gender.in' => 'Giới tính không hợp lệ.',
            'avatar.image' => 'Avatar phải là file hình ảnh.',
            'avatar.mimes' => 'Avatar chỉ hỗ trợ JPG, PNG hoặc WebP.',
            'avatar.max' => 'Avatar không được vượt quá 2MB.',
        ]);

        $payload = [
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'birthday' => $validated['birthday'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'notify_order_status' => $request->boolean('notify_order_status'),
            'notify_promotions' => $request->boolean('notify_promotions'),
        ];

        if ($request->hasFile('avatar')) {
            $payload['avatar_url'] = 'storage/' . $request->file('avatar')->store('avatars', 'public');
            $this->deleteLocalAvatar($user->avatar_url);
        }

        $user->forceFill($payload)->save();

        return redirect()->route('account.profile')->with('success', 'Đã cập nhật hồ sơ cá nhân.');
    }

    public function storeAddress(Request $request, VietnamAddressService $addressService)
    {
        $request->merge([
            'recipient_name' => $this->cleanTextInput($request->input('recipient_name')),
            'phone' => $this->normalizeVietnamPhone($request->input('phone')),
            'address_line' => $this->cleanTextInput($request->input('address_line')),
            'district' => $this->cleanTextInput($request->input('district'), true),
        ]);

        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'min:2', 'max:120', 'not_regex:/[<>]/'],
            'phone' => ['required', 'string', 'regex:/^\+84[0-9]{9}$/'],
            'address_line' => ['required', 'string', 'min:5', 'max:255', 'not_regex:/[<>]/'],
            'province_code' => ['required', 'string', 'max:20'],
            'ward_code' => ['required', 'string', 'max:20'],
            'district' => ['nullable', 'string', 'max:120', 'not_regex:/[<>]/'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'recipient_name.required' => 'Vui lòng nhập tên người nhận.',
            'recipient_name.min' => 'Tên người nhận phải có ít nhất 2 ký tự.',
            'phone.required' => 'Vui lòng nhập số điện thoại nhận hàng.',
            'phone.regex' => 'Số điện thoại cần theo định dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.',
            'address_line.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'address_line.min' => 'Địa chỉ giao hàng cần có ít nhất 5 ký tự.',
            'province_code.required' => 'Vui lòng chọn Tỉnh/Thành.',
            'ward_code.required' => 'Vui lòng chọn Phường/Xã.',
            'district.not_regex' => 'Khu vực giao hàng không được chứa ký tự HTML.',
        ]);

        $user = $request->user();
        $resolvedAddress = $addressService->resolve($validated['province_code'], $validated['ward_code']);
        $validated['province'] = $resolvedAddress['province_name'];
        $validated['ward'] = $resolvedAddress['ward_name'];
        $validated['province_code'] = $resolvedAddress['province_code'];
        $validated['ward_code'] = $resolvedAddress['ward_code'];

        DB::transaction(function () use ($user, $request, $validated) {
            $shouldDefault = $request->boolean('is_default') || !$user->addresses()->exists();

            if ($shouldDefault) {
                $user->addresses()->update(['is_default' => false]);
            }

            $user->addresses()->create(array_merge($validated, [
                'is_default' => $shouldDefault,
            ]));
        });

        return redirect()->route('account.profile')->with('success', 'Đã thêm địa chỉ giao hàng.');
    }

    public function setDefaultAddress(Request $request, UserAddress $address)
    {
        $this->authorizeAddress($request, $address);

        DB::transaction(function () use ($request, $address) {
            $request->user()->addresses()->update(['is_default' => false]);
            $address->forceFill(['is_default' => true])->save();
        });

        return redirect()->route('account.profile')->with('success', 'Đã đặt địa chỉ mặc định.');
    }

    public function destroyAddress(Request $request, UserAddress $address)
    {
        $this->authorizeAddress($request, $address);

        DB::transaction(function () use ($request, $address) {
            $wasDefault = (bool) $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $nextAddress = $request->user()->addresses()->latest()->first();

                if ($nextAddress) {
                    $nextAddress->forceFill(['is_default' => true])->save();
                }
            }
        });

        return redirect()->route('account.profile')->with('success', 'Đã xóa địa chỉ giao hàng.');
    }

    public function toggleWishlist(Request $request, Product $product)
    {
        $user = $request->user();
        $item = WishlistItem::query()
            ->where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($item) {
            $item->delete();

            return redirect()->back()->with('success', 'Đã bỏ sản phẩm khỏi danh sách yêu thích.');
        }

        WishlistItem::query()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        return redirect()->back()->with('success', 'Đã thêm sản phẩm vào danh sách yêu thích.');
    }

    public function cancelOrder(Request $request, Order $order, OrderCancellationService $cancellationService)
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        $request->merge([
            'note' => $this->cleanTextInput($request->input('note'), true),
        ]);

        $validated = $request->validate([
            'reason' => ['required', Rule::in(array_keys(OrderCancellationRequest::reasonLabels()))],
            'note' => ['nullable', 'string', 'max:500', 'not_regex:/[<>]/'],
        ], [
            'reason.required' => 'Vui lòng chọn lý do hủy đơn.',
            'reason.in' => 'Lý do hủy đơn không hợp lệ.',
            'note.max' => 'Ghi chú hủy đơn không được vượt quá 500 ký tự.',
            'note.not_regex' => 'Ghi chú hủy đơn không được chứa ký tự HTML.',
        ]);

        $result = DB::transaction(function () use ($request, $order, $validated, $cancellationService) {
            $order = Order::query()
                ->with(['items', 'cancellationRequests'])
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->isCustomerCancellable()) {
                $cancelRequest = $this->createCancellationRequest($order, $request->user()->id, $validated, OrderCancellationRequest::STATUS_APPROVED);
                $cancellationService->cancelImmediately(
                    $order,
                    $request->user()->id,
                    $cancelRequest,
                    'Khach hang huy don tu trang tai khoan. Ly do: ' . $cancelRequest->reason_label
                );

                return 'cancelled';
            }

            if ($order->isCustomerCancellationRequestable()) {
                $this->createCancellationRequest($order, $request->user()->id, $validated, OrderCancellationRequest::STATUS_PENDING);

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'user_id' => $request->user()->id,
                    'previous_status' => $order->status,
                    'status' => 'cancel_requested',
                    'note' => 'Khách hàng gửi yêu cầu hủy đơn. Lý do: ' . OrderCancellationRequest::reasonLabels()[$validated['reason']],
                    'created_at' => now(),
                ]);

                return 'requested';
            }

            throw ValidationException::withMessages([
                'order' => 'Đơn này không thể tự hủy hoặc yêu cầu hủy ở trạng thái hiện tại. Vui lòng liên hệ shop để được hỗ trợ.',
            ]);
        });

        $message = $result === 'cancelled'
            ? 'Đã hủy đơn hàng và hoàn lại tồn kho.'
            : 'Đã gửi yêu cầu hủy đơn. Shop sẽ kiểm tra và phản hồi trong thời gian sớm nhất.';

        return redirect()
            ->route('account.profile', ['tab' => 'orders'])
            ->with('success', $message);
    }

    public function requestReturn(Request $request, Order $order, OrderReturnService $returnService)
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        $request->merge([
            'note' => $this->cleanTextInput($request->input('note'), true),
            'refund_account' => $this->cleanTextInput($request->input('refund_account'), true),
        ]);

        $maxEvidenceKb = max(1, (int) config('shop.returns.max_evidence_mb', 3)) * 1024;

        $validated = $request->validate([
            'type' => ['required', Rule::in(array_keys(OrderReturnRequest::typeLabels()))],
            'reason' => ['required', Rule::in(array_keys(OrderReturnRequest::reasonLabels()))],
            'note' => ['required', 'string', 'min:10', 'max:800', 'not_regex:/[<>]/'],
            'evidence' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . $maxEvidenceKb],
            'refund_method' => ['nullable', Rule::in(array_keys(OrderReturnRequest::refundMethodLabels()))],
            'refund_account' => ['nullable', 'string', 'max:255', 'not_regex:/[<>]/'],
        ], [
            'type.required' => 'Vui lòng chọn hình thức xử lý.',
            'type.in' => 'Hình thức xử lý không hợp lệ.',
            'reason.required' => 'Vui lòng chọn lý do đổi trả.',
            'reason.in' => 'Lý do đổi trả không hợp lệ.',
            'note.required' => 'Vui lòng mô tả tình trạng sản phẩm.',
            'note.min' => 'Mô tả cần có ít nhất 10 ký tự để shop kiểm tra.',
            'note.max' => 'Mô tả không được vượt quá 800 ký tự.',
            'note.not_regex' => 'Mô tả không được chứa ký tự HTML.',
            'evidence.image' => 'Ảnh bằng chứng phải là file hình ảnh.',
            'evidence.mimes' => 'Ảnh bằng chứng chỉ hỗ trợ JPG, PNG hoặc WebP.',
            'evidence.max' => 'Ảnh bằng chứng không được vượt quá ' . config('shop.returns.max_evidence_mb', 3) . 'MB.',
            'refund_method.in' => 'Phương thức nhận hoàn tiền không hợp lệ.',
            'refund_account.max' => 'Thông tin nhận hoàn tiền không được vượt quá 255 ký tự.',
            'refund_account.not_regex' => 'Thông tin nhận hoàn tiền không được chứa ký tự HTML.',
        ]);

        if ($validated['type'] === OrderReturnRequest::TYPE_REFUND && empty($validated['refund_method'])) {
            throw ValidationException::withMessages([
                'refund_method' => 'Vui lòng chọn cách nhận hoàn tiền.',
            ]);
        }

        if (
            $validated['type'] === OrderReturnRequest::TYPE_REFUND
            && ($validated['refund_method'] ?? null) !== OrderReturnRequest::REFUND_METHOD_CONTACT
            && empty($validated['refund_account'])
        ) {
            throw ValidationException::withMessages([
                'refund_account' => 'Vui lòng nhập thông tin nhận hoàn tiền.',
            ]);
        }

        $evidencePath = null;
        if ($request->hasFile('evidence')) {
            $evidencePath = $request->file('evidence')->store('return-evidence', 'public');
        }

        try {
            DB::transaction(function () use ($request, $order, $validated, $evidencePath, $returnService) {
                $order = Order::query()
                    ->with(['returnRequests', 'statusHistories'])
                    ->whereKey($order->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $order->user_id !== (int) $request->user()->id) {
                    abort(404);
                }

                $returnService->createCustomerRequest($order, $request->user()->id, array_merge($validated, [
                    'evidence_path' => $evidencePath,
                ]));
            });
        } catch (Throwable $exception) {
            if ($evidencePath) {
                Storage::disk('public')->delete($evidencePath);
            }

            throw $exception;
        }

        return redirect()
            ->route('account.profile', ['tab' => 'orders'])
            ->with('success', 'Đã gửi yêu cầu đổi trả/hoàn tiền. Shop sẽ kiểm tra và phản hồi sớm.');
    }

    private function createCancellationRequest(Order $order, int $userId, array $validated, string $status): OrderCancellationRequest
    {
        return OrderCancellationRequest::query()->create([
            'order_id' => $order->id,
            'user_id' => $userId,
            'status' => $status,
            'reason' => $validated['reason'],
            'note' => $validated['note'] ?? null,
            'requested_at' => now(),
            'resolved_by' => $status === OrderCancellationRequest::STATUS_APPROVED ? $userId : null,
            'resolved_at' => $status === OrderCancellationRequest::STATUS_APPROVED ? now() : null,
            'admin_note' => $status === OrderCancellationRequest::STATUS_APPROVED
                ? 'Tự động duyệt vì đơn còn ở trạng thái chờ xác nhận và chưa thanh toán online.'
                : null,
        ]);
    }

    public function removeWishlist(Request $request, WishlistItem $item)
    {
        if ((int) $item->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        $item->delete();

        return redirect()->route('account.profile')->with('success', 'Đã bỏ sản phẩm khỏi danh sách yêu thích.');
    }

    private function getOrderSummary(int $userId): array
    {
        $baseQuery = Order::query()->where('user_id', $userId);

        $totalOrders = (clone $baseQuery)->count();
        $activeOrders = (clone $baseQuery)
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_CONFIRMED, Order::STATUS_SHIPPING])
            ->count();
        $completedOrders = (clone $baseQuery)->where('status', Order::STATUS_DONE)->count();
        $totalSpent = (int) (clone $baseQuery)
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->sum('total');

        return [
            'total_orders' => $totalOrders,
            'active_orders' => $activeOrders,
            'completed_orders' => $completedOrders,
            'total_spent' => $totalSpent,
        ];
    }

    private function getMembership(int $totalSpent): array
    {
        $points = intdiv($totalSpent, 10000);
        $tier = 'Đồng';
        $nextTier = 'Bạc';
        $nextTarget = 2000000;

        if ($totalSpent >= 10000000) {
            $tier = 'Kim cương';
            $nextTier = null;
            $nextTarget = null;
        } elseif ($totalSpent >= 5000000) {
            $tier = 'Vàng';
            $nextTier = 'Kim cương';
            $nextTarget = 10000000;
        } elseif ($totalSpent >= 2000000) {
            $tier = 'Bạc';
            $nextTier = 'Vàng';
            $nextTarget = 5000000;
        }

        return [
            'points' => $points,
            'tier' => $tier,
            'next_tier' => $nextTier,
            'next_target' => $nextTarget,
            'remaining_to_next_tier' => $nextTarget ? max(0, $nextTarget - $totalSpent) : 0,
        ];
    }

    private function authorizeAddress(Request $request, UserAddress $address): void
    {
        if ((int) $address->user_id !== (int) $request->user()->id) {
            abort(404);
        }
    }

    private function normalizeVietnamPhone($value): ?string
    {
        $phone = preg_replace('/[\s().-]+/', '', trim((string) $value));

        if ($phone === '') {
            return null;
        }

        if (preg_match('/^0[0-9]{9}$/', $phone)) {
            return '+84' . substr($phone, 1);
        }

        if (preg_match('/^84[0-9]{9}$/', $phone)) {
            return '+' . $phone;
        }

        return $phone;
    }

    private function cleanTextInput($value, bool $nullable = false): ?string
    {
        $cleaned = preg_replace('/\s+/u', ' ', trim((string) $value));

        if ($nullable && $cleaned === '') {
            return null;
        }

        return $cleaned;
    }

    private function deleteLocalAvatar(?string $avatarUrl): void
    {
        if (!$avatarUrl || !Str::startsWith($avatarUrl, 'storage/avatars/')) {
            return;
        }

        $path = Str::after($avatarUrl, 'storage/');
        Storage::disk('public')->delete($path);
    }
}
