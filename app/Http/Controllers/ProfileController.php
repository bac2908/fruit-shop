<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\Product;
use App\Models\UserAddress;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
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
            ->with(['items.product'])
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
            ->filter(function ($voucher) use ($user) {
                return $voucher->coupon
                    && $voucher->is_usable
                    && !$voucher->coupon->getInvalidReason(0, $user->id, $user->email);
            })
            ->sortBy(function ($voucher) {
                return optional($voucher->expires_at)->timestamp ?? PHP_INT_MAX;
            })
            ->values();

        $availableCoupons = Coupon::query()
            ->valid()
            ->where('is_public', true)
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

    public function storeAddress(Request $request)
    {
        $request->merge([
            'recipient_name' => $this->cleanTextInput($request->input('recipient_name')),
            'phone' => $this->normalizeVietnamPhone($request->input('phone')),
            'address_line' => $this->cleanTextInput($request->input('address_line')),
            'ward' => $this->cleanTextInput($request->input('ward'), true),
            'district' => $this->cleanTextInput($request->input('district'), true),
            'province' => $this->cleanTextInput($request->input('province'), true),
        ]);

        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'min:2', 'max:120', 'not_regex:/[<>]/'],
            'phone' => ['required', 'string', 'regex:/^\+84[0-9]{9}$/'],
            'address_line' => ['required', 'string', 'min:5', 'max:255', 'not_regex:/[<>]/'],
            'ward' => ['nullable', 'string', 'max:120', 'not_regex:/[<>]/'],
            'district' => ['nullable', 'string', 'max:120', 'not_regex:/[<>]/'],
            'province' => ['nullable', 'string', 'max:120', 'not_regex:/[<>]/'],
            'is_default' => ['nullable', 'boolean'],
        ], [
            'recipient_name.required' => 'Vui lòng nhập tên người nhận.',
            'recipient_name.min' => 'Tên người nhận phải có ít nhất 2 ký tự.',
            'phone.required' => 'Vui lòng nhập số điện thoại nhận hàng.',
            'phone.regex' => 'Số điện thoại cần theo định dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.',
            'address_line.required' => 'Vui lòng nhập địa chỉ giao hàng.',
            'address_line.min' => 'Địa chỉ giao hàng cần có ít nhất 5 ký tự.',
        ]);

        $user = $request->user();

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
