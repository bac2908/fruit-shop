<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\UserVoucher;
use App\Services\MomoPaymentService;
use App\Services\MomoCallbackService;
use App\Services\CustomerNotificationService;
use App\Services\OrderAutomationService;
use App\Services\OrderCancellationService;
use App\Services\ShippingFeeService;
use App\Services\VietnamAddressService;
use App\Services\VoucherSelectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = $this->getCartItems();
        $summary = $this->getCartSummary($cartItems, false, session('checkout_shipping', []));
        $totalQuantity = $cartItems->sum('quantity');

        return view('cart', [
            'cartItems' => $cartItems,
            'totalQuantity' => $totalQuantity,
            'summary' => $summary,
            'appliedCoupon' => $summary['coupon'],
        ]);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ]);

        $product = Product::query()
            ->where('id', $validated['product_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $productId = (string) $product->id;
        $quantity = (int) ($validated['quantity'] ?? 1);
        $cart = session('cart', []);
        $currentQuantity = (int) ($cart[$productId]['quantity'] ?? 0);
        $newQuantity = $currentQuantity + $quantity;

        if ($error = $this->getProductAvailabilityError($product, $newQuantity)) {
            return redirect()->back()->with('error', $error);
        }

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $newQuantity;
        } else {
            $cart[$productId] = [
                'product_id' => (int) $productId,
                'quantity' => $quantity,
            ];
        }

        session(['cart' => $cart]);

        if (session('cart_coupon_selection_mode') !== 'manual') {
            session([
                'cart_coupon_selection_mode' => 'auto',
                'cart_coupon_auto_disabled' => false,
            ]);
        }

        $cartQuantity = collect($cart)->sum(function ($item) {
            return (int) ($item['quantity'] ?? 0);
        });

        if ($request->boolean('checkout_redirect')) {
            return redirect()->route('checkout');
        }

        return redirect()->back()
            ->with('success', 'Đã thêm sản phẩm vào giỏ hàng.')
            ->with('cart_added', [
                'name' => $product->name,
                'image' => $product->thumb_url,
                'quantity' => $quantity,
                'unit_price' => $this->getOrderableUnitPrice($product),
                'cart_quantity' => $cartQuantity,
            ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $productId = (string) $validated['product_id'];
        $cart = session('cart', []);

        if (isset($cart[$productId])) {
            $product = Product::query()
                ->where('id', $validated['product_id'])
                ->where('is_active', true)
                ->first();

            if (!$product) {
                unset($cart[$productId]);
                session(['cart' => $cart]);

                return redirect()->route('cart')->with('error', 'San pham khong con kha dung.');
            }

            if ($error = $this->getProductAvailabilityError($product, (int) $validated['quantity'])) {
                return redirect()->route('cart')->with('error', $error);
            }

            $cart[$productId]['quantity'] = (int) $validated['quantity'];
            session(['cart' => $cart]);

            if (session('cart_coupon_selection_mode') !== 'manual') {
                session([
                    'cart_coupon_selection_mode' => 'auto',
                    'cart_coupon_auto_disabled' => false,
                ]);
            }
        }

        return redirect()->route('cart')->with('success', 'Da cap nhat gio hang.');
    }

    public function remove(Request $request)
    {
        $validated = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $productId = (string) $validated['product_id'];
        $cart = session('cart', []);

        unset($cart[$productId]);
        session(['cart' => $cart]);

        if (empty($cart)) {
            session()->forget([
                'cart_coupon_code',
                'cart_coupon_selection_mode',
                'cart_coupon_auto_disabled',
                'checkout_shipping',
                'checkout_payment_method',
            ]);
        }

        return redirect()->route('cart')->with('success', 'Da xoa san pham khoi gio hang.');
    }

    public function applyCoupon(Request $request)
    {
        if (!$request->user()) {
            session(['url.intended' => route('cart')]);

            return redirect()
                ->route('login')
                ->with('error', 'Vui lòng đăng nhập để sử dụng voucher thành viên.');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80'],
        ]);

        $redirectRoute = $this->resolveCartRedirectRoute($request);
        $cartItems = $this->getCartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Giỏ hàng đang trống, chưa thể áp dụng mã ưu đãi.');
        }

        $subtotal = (int) $cartItems->sum('line_total');
        $user = $request->user();
        $code = trim((string) $validated['code']);

        $coupon = Coupon::query()
            ->whereRaw('LOWER(code) = ?', [Str::lower($code)])
            ->first();

        if (!$coupon) {
            return redirect()->route($redirectRoute)->with('error', 'Mã ưu đãi không tồn tại hoặc đã hết hạn.');
        }

        if ($error = $coupon->getInvalidReason($subtotal, optional($user)->id, optional($user)->email)) {
            return redirect()->route($redirectRoute)->with('error', $error);
        }

        session([
            'cart_coupon_code' => $coupon->code,
            'cart_coupon_selection_mode' => 'manual',
            'cart_coupon_auto_disabled' => false,
        ]);

        return redirect()->route($redirectRoute)->with(
            'success',
            'Đã áp dụng mã ' . $coupon->code . ': ' . $coupon->benefit_label . '.'
        );
    }

    public function useCoupon(Request $request, Coupon $coupon)
    {
        $redirectRoute = $this->resolveCartRedirectRoute($request);
        $cartItems = $this->getCartItems();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Giỏ hàng đang trống, hãy thêm sản phẩm trước khi dùng voucher.');
        }

        $subtotal = (int) $cartItems->sum('line_total');
        $user = $request->user();

        if ($error = $coupon->getInvalidReason($subtotal, optional($user)->id, optional($user)->email)) {
            return redirect()->route($redirectRoute)->with('error', $error);
        }

        session([
            'cart_coupon_code' => $coupon->code,
            'cart_coupon_selection_mode' => 'manual',
            'cart_coupon_auto_disabled' => false,
        ]);

        return redirect()->route($redirectRoute)->with(
            'success',
            'Đã chọn mã ' . $coupon->code . ': ' . $coupon->benefit_label . '.'
        );
    }

    public function removeCoupon(Request $request)
    {
        session()->forget(['cart_coupon_code', 'cart_coupon_selection_mode']);
        session(['cart_coupon_auto_disabled' => true]);

        $redirectRoute = $this->resolveCartRedirectRoute($request);

        return redirect()->route($redirectRoute)->with('success', 'Đã bỏ mã ưu đãi khỏi giỏ hàng.');
    }

    public function autoCoupon(Request $request)
    {
        session()->forget('cart_coupon_code');
        session([
            'cart_coupon_selection_mode' => 'auto',
            'cart_coupon_auto_disabled' => false,
        ]);

        $redirectRoute = $this->resolveCartRedirectRoute($request);

        return redirect()->route($redirectRoute)->with('success', 'Hệ thống sẽ tự chọn voucher có giá trị tốt nhất cho đơn hàng.');
    }

    public function checkout(VietnamAddressService $addressService)
    {
        $cartItems = $this->getCartItems();
        $user = auth()->user()->loadMissing('addresses');

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Gio hang dang trong. Vui long them san pham de tiep tuc.');
        }

        if ($error = $this->getCartAvailabilityError($cartItems)) {
            return redirect()->route('cart')->with('error', $error);
        }

        $defaultAddress = $user->addresses->firstWhere('is_default', true) ?: $user->addresses->first();
        $summaryAddress = session('checkout_shipping', []);

        if (empty($summaryAddress['province_code']) && $defaultAddress) {
            $summaryAddress = [
                'province_code' => $defaultAddress->province_code,
                'ward_code' => $defaultAddress->ward_code,
                'ward' => $defaultAddress->ward,
            ];
        }

        $summary = $this->getCartSummary($cartItems, false, $summaryAddress);

        return view('checkout', [
            'cartItems' => $cartItems,
            'summary' => $summary,
            'appliedCoupon' => $summary['coupon'],
            'user' => $user,
            'defaultAddress' => $defaultAddress,
            'checkoutShipping' => session('checkout_shipping', []),
            'vietnamProvinces' => $addressService->provincesForSelect(),
            'vietnamAddressDataUrl' => asset('data/vietnam-addresses.json'),
            'shippingRules' => app(ShippingFeeService::class)->frontendRules(),
        ]);
    }

    public function storeCheckoutShipping(Request $request, VietnamAddressService $addressService)
    {
        $user = $request->user();

        $cartItems = $this->getCartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Gio hang dang trong.');
        }

        if ($error = $this->getCartAvailabilityError($cartItems)) {
            return redirect()->route('cart')->with('error', $error);
        }

        session(['checkout_shipping' => $this->validateCheckoutShipping($request, $user, $addressService)]);

        return redirect()->route('checkout.payment');
    }

    public function payment()
    {
        $cartItems = $this->getCartItems();
        $user = auth()->user();

        if ($cartItems->isEmpty()) {
            session()->forget(['checkout_shipping', 'checkout_payment_method']);
            return redirect()->route('cart')->with('error', 'Gio hang dang trong. Vui long them san pham de tiep tuc.');
        }

        if ($error = $this->getCartAvailabilityError($cartItems)) {
            return redirect()->route('cart')->with('error', $error);
        }

        $checkoutShipping = session('checkout_shipping');
        if (!is_array($checkoutShipping) || empty($checkoutShipping)) {
            return redirect()->route('checkout')->with('error', 'Vui long kiem tra thong tin giao hang truoc khi chon thanh toan.');
        }

        $summary = $this->getCartSummary($cartItems, false, $checkoutShipping);

        return view('checkout-payment', [
            'cartItems' => $cartItems,
            'summary' => $summary,
            'appliedCoupon' => $summary['coupon'],
            'user' => $user,
            'checkoutShipping' => $checkoutShipping,
            'selectedPaymentMethod' => old('payment_method', session('checkout_payment_method', Order::PAYMENT_METHOD_COD)),
        ]);
    }

    public function placeOrder(
        Request $request,
        MomoPaymentService $momoPayment,
        OrderAutomationService $orderAutomation,
        CustomerNotificationService $customerNotifications,
        OrderCancellationService $cancellationService
    )
    {
        $user = $request->user();
        $checkoutShipping = session('checkout_shipping');

        if (!is_array($checkoutShipping) || empty($checkoutShipping)) {
            return redirect()->route('checkout')->with('error', 'Vui long kiem tra thong tin giao hang truoc khi dat hang.');
        }

        $validatedPayment = $request->validate([
            'payment_method' => ['required', Rule::in([
                Order::PAYMENT_METHOD_COD,
                Order::PAYMENT_METHOD_BANK_TRANSFER,
                Order::PAYMENT_METHOD_MOMO,
            ])],
        ], [
            'payment_method.required' => 'Vui lòng chọn phương thức thanh toán.',
            'payment_method.in' => 'Phương thức thanh toán không hợp lệ.',
        ]);

        session(['checkout_payment_method' => $validatedPayment['payment_method']]);

        $cartItems = $this->getCartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Gio hang dang trong.');
        }

        if ($error = $this->getCartAvailabilityError($cartItems)) {
            return redirect()->route('cart')->with('error', $error);
        }

        $validated = array_merge($checkoutShipping, [
            'payment_method' => $validatedPayment['payment_method'],
        ]);

        $order = DB::transaction(function () use ($validated, $cartItems, $user, $orderAutomation, $customerNotifications) {
            $cartItems = $this->lockAndValidateCartItems($cartItems);
            $summary = $this->getCartSummary($cartItems, true, $validated);
            $summary = $this->lockAndValidateCoupon($summary);
            $shippingQuote = $summary['shipping_quote'];
            $orderCode = $this->generateOrderCode();
            $isMomo = $validated['payment_method'] === Order::PAYMENT_METHOD_MOMO;

            $order = Order::query()->create([
                'code' => $orderCode,
                'public_token' => Str::random(48),
                'user_id' => $user->id,
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_email' => $validated['customer_email'],
                'shipping_address' => $validated['shipping_address'],
                'shipping_province_code' => $validated['province_code'],
                'shipping_ward_code' => $validated['ward_code'],
                'customer_note' => $validated['notes'] ?? null,
                'admin_note' => !empty($shippingQuote['requires_confirmation'])
                    ? 'Đơn hàng tươi giao tỉnh/khu vực đặc biệt: phí ship đang là tạm tính, cần shop xác nhận đóng gói và tuyến giao trước khi xử lý.'
                    : null,
                'subtotal' => (int) $summary['subtotal'],
                'shipping_fee' => (int) $summary['shipping_fee'],
                'shipping_fee_status' => $shippingQuote['fee_status'] ?? Order::SHIPPING_FEE_STATUS_ESTIMATED,
                'discount_total' => (int) $summary['discount_total'],
                'total' => (int) $summary['total'],
                'coupon_code' => $summary['coupon'] ? $summary['coupon']->code : null,
                'status' => Order::STATUS_PENDING,
                'payment_method' => $validated['payment_method'],
                'payment_status' => Order::PAYMENT_STATUS_UNPAID,
                'momo_request_id' => $isMomo ? $orderCode : null,
                'payment_expires_at' => $isMomo
                    ? now()->addMinutes((int) config('shop.order_automation.momo_expire_minutes', 30))
                    : null,
                'shipping_status' => Order::SHIPPING_STATUS_PENDING,
                'shipping_delivery_method' => $shippingQuote['delivery_method'] ?? null,
                'shipping_delivery_eta' => $shippingQuote['eta'] ?? null,
                'shipping_delivery_note' => $shippingQuote['message'] ?? null,
            ]);

            foreach ($cartItems as $item) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $item['product']->id,
                    'product_name' => $item['product']->name,
                    'unit' => $item['product']->unit,
                    'unit_price' => (int) $item['unit_price'],
                    'qty' => (int) $item['quantity'],
                    'line_total' => (int) $item['line_total'],
                ]);

                $product = $item['product'];
                $stockBefore = (int) $product->stock;
                $stockAfter = $stockBefore - (int) $item['quantity'];

                $product->forceFill(['stock' => $stockAfter])->save();

                InventoryMovement::query()->create([
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'type' => 'order',
                    'quantity' => -1 * (int) $item['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $product->cost_price,
                    'note' => 'Xuat kho cho don ' . $order->code,
                ]);
            }

            if ($summary['coupon'] && $summary['coupon']->type === Coupon::TYPE_GIFT) {
                OrderItem::query()->create([
                    'order_id' => $order->id,
                    'product_id' => null,
                    'product_name' => $summary['coupon']->benefit_label,
                    'unit' => 'quà tặng voucher',
                    'unit_price' => 0,
                    'qty' => 1,
                    'line_total' => 0,
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'previous_status' => null,
                'status' => Order::STATUS_PENDING,
                'note' => 'Don hang moi duoc tao.',
                'created_at' => now(),
            ]);

            $customerNotifications->orderPlaced($order);

            if ($validated['payment_method'] !== Order::PAYMENT_METHOD_MOMO && empty($shippingQuote['requires_confirmation'])) {
                $orderAutomation->autoConfirmAfterStockReserved(
                    $order,
                    null,
                    'He thong tu dong xac nhan vi checkout da giu du ton kho.'
                );
            }

            if ($summary['coupon']) {
                CouponUsage::query()->create([
                    'coupon_id' => $summary['coupon']->id,
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'coupon_code' => $summary['coupon']->code,
                    'customer_email' => $validated['customer_email'],
                    'discount_total' => (int) $summary['discount_total'],
                    'used_at' => now(),
                ]);

                $summary['coupon']->increment('used_count');

                UserVoucher::query()
                    ->where('user_id', $user->id)
                    ->where('coupon_id', $summary['coupon']->id)
                    ->whereNull('used_at')
                    ->update(['used_at' => now()]);
            }

            if ($validated['save_address']) {
                $this->saveCheckoutAddress($user, $validated);
            }

            return $order;
        });

        $momoPayUrl = null;
        if ($validated['payment_method'] === Order::PAYMENT_METHOD_MOMO) {
            try {
                $momoResponse = $momoPayment->createPayment($order);
                $momoPayUrl = $momoResponse['payUrl'];
            } catch (\Throwable $exception) {
                DB::transaction(function () use ($order, $cancellationService) {
                    $lockedOrder = Order::query()
                        ->with('items')
                        ->whereKey($order->id)
                        ->lockForUpdate()
                        ->first();

                    if ($lockedOrder && $lockedOrder->payment_status === Order::PAYMENT_STATUS_UNPAID) {
                        $cancellationService->cancelImmediately(
                            $lockedOrder,
                            null,
                            null,
                            'Hệ thống hủy đơn vì không tạo được phiên thanh toán MoMo.',
                            false
                        );
                    }
                });

                report($exception);

                return redirect()->route('checkout.payment')->with(
                    'error',
                    $exception instanceof ValidationException
                        ? (collect($exception->errors())->flatten()->first() ?: 'Không thể tạo phiên thanh toán MoMo.')
                        : 'Không thể kết nối MoMo lúc này. Giỏ hàng của bạn vẫn được giữ, vui lòng thử lại.'
                );
            }
        }

        session([
            'checkout_order_code' => $order->code,
            'checkout_order_token' => $order->public_token,
        ]);
        session()->forget('cart');
        session()->forget(['cart_coupon_code', 'cart_coupon_selection_mode', 'cart_coupon_auto_disabled']);
        session()->forget('checkout_shipping');
        session()->forget('checkout_payment_method');

        if ($validated['payment_method'] === Order::PAYMENT_METHOD_MOMO && $momoPayUrl) {
            return redirect()->away($momoPayUrl);
        }

        return redirect()->route('checkout.thankyou', [
            'code' => $order->code,
            'token' => $order->public_token,
        ]);
    }

    public function momoReturn(
        Request $request,
        string $code,
        ?string $token = null,
        MomoCallbackService $momoCallback
    )
    {
        $order = Order::query()
            ->where('code', $code)
            ->firstOrFail();

        if (!$this->canViewOrder($order, $token)) {
            abort(404);
        }

        $result = $momoCallback->handle($request->query(), $code);
        $paid = $result['paid'];
        $flashType = $paid ? 'success' : 'error';
        $flashMessage = $paid
            ? 'Thanh toán MoMo thành công.'
            : 'Thanh toán MoMo chưa thành công hoặc đã bị hủy.';

        return redirect()
            ->route('checkout.thankyou', [
                'code' => $order->code,
                'token' => $order->public_token,
            ])
            ->with($flashType, $flashMessage);
    }

    public function momoIpn(Request $request, MomoCallbackService $momoCallback)
    {
        $payload = $request->all();
        $result = $momoCallback->handle($payload);

        return response()->json([
            'resultCode' => $result['accepted'] ? 0 : 1,
            'message' => $result['message'],
        ], $result['accepted'] ? 200 : 422);
    }

    public function thankYou(string $code, ?string $token = null)
    {
        $order = Order::query()
            ->with(['items', 'items.product', 'statusHistories', 'cancellationRequests'])
            ->where('code', $code)
            ->firstOrFail();

        if (!$this->canViewOrder($order, $token)) {
            abort(404);
        }

        return view('checkout-success', [
            'order' => $order,
            'appliedCoupon' => $order->coupon_code
                ? Coupon::query()->where('code', $order->coupon_code)->first()
                : null,
        ]);
    }

    private function getCartItems(): Collection
    {
        $cart = collect(session('cart', []));
        $productIds = $cart->pluck('product_id')->filter()->values()->all();

        if (empty($productIds)) {
            return collect();
        }

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return $cart->map(function (array $item) use ($products) {
            $product = $products->get($item['product_id'] ?? null);

            if (!$product) {
                return null;
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $unitPrice = $this->getOrderableUnitPrice($product);

            return [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $quantity,
            ];
        })->filter()->values();
    }

    private function getCartSummary(Collection $cartItems, bool $throwOnInvalidCoupon = false, ?array $shippingAddress = null): array
    {
        $subtotal = (int) $cartItems->sum('line_total');
        $user = auth()->user();
        $voucherOptions = $user
            ? app(VoucherSelectionService::class)->optionsFor($user, $subtotal)
            : collect();
        $shippingQuote = app(ShippingFeeService::class)->quote(
            $subtotal,
            $shippingAddress['province_code'] ?? null,
            $shippingAddress['ward'] ?? null
        );
        $shippingFee = (int) $shippingQuote['fee'];
        $coupon = $this->resolveCoupon($subtotal, $throwOnInvalidCoupon, $voucherOptions);
        $discountTotal = $coupon ? min($coupon->calculateDiscount($subtotal), $subtotal) : 0;
        $total = max(0, $subtotal + $shippingFee - $discountTotal);

        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'shipping_quote' => $shippingQuote,
            'discount_total' => $discountTotal,
            'total' => $total,
            'coupon' => $coupon,
            'voucher_options' => $voucherOptions,
            'coupon_selection_mode' => session('cart_coupon_selection_mode'),
        ];
    }

    private function resolveCoupon(int $subtotal, bool $throwOnInvalid = false, ?Collection $voucherOptions = null): ?Coupon
    {
        $couponCode = trim((string) session('cart_coupon_code', ''));
        $selectionMode = (string) session('cart_coupon_selection_mode', '');
        $user = auth()->user();

        if (
            $user
            && !session('cart_coupon_auto_disabled', false)
            && ($couponCode === '' || $selectionMode === 'auto')
        ) {
            $voucherOptions = $voucherOptions ?: app(VoucherSelectionService::class)->optionsFor($user, $subtotal);
            $bestCoupon = app(VoucherSelectionService::class)->bestEligible($voucherOptions);

            if ($bestCoupon) {
                $couponCode = $bestCoupon->code;
                session([
                    'cart_coupon_code' => $couponCode,
                    'cart_coupon_selection_mode' => 'auto',
                ]);
            } else {
                $couponCode = '';
                session()->forget(['cart_coupon_code', 'cart_coupon_selection_mode']);
            }
        }

        if ($couponCode === '') {
            return null;
        }

        $coupon = Coupon::query()
            ->whereRaw('LOWER(code) = ?', [Str::lower($couponCode)])
            ->first();

        if (!$coupon) {
            return $this->handleInvalidSessionCoupon($throwOnInvalid, 'Mã giảm giá không hợp lệ hoặc đã hết hạn.');
        }

        if ($error = $coupon->getInvalidReason($subtotal, optional($user)->id, optional($user)->email)) {
            return $this->handleInvalidSessionCoupon($throwOnInvalid, $error);
        }

        return $coupon;
    }

    private function handleInvalidSessionCoupon(bool $throwOnInvalid, string $message): ?Coupon
    {
        session()->forget(['cart_coupon_code', 'cart_coupon_selection_mode']);

        if ($throwOnInvalid) {
            throw ValidationException::withMessages([
                'coupon' => $message,
            ]);
        }

        return null;
    }

    private function getOrderableUnitPrice(Product $product): int
    {
        $basePrice = (int) ($product->price ?? 0);
        $salePrice = (int) ($product->sale_price ?? 0);

        if ($salePrice > 0 && ($basePrice <= 0 || $salePrice < $basePrice)) {
            return $salePrice;
        }

        return $basePrice;
    }

    private function getProductAvailabilityError(Product $product, int $quantity): ?string
    {
        if (!$product->is_active) {
            return 'San pham "' . $product->name . '" hien khong kha dung.';
        }

        if ($this->getOrderableUnitPrice($product) <= 0) {
            return 'San pham "' . $product->name . '" chua co gia ban. Vui long lien he de duoc bao gia.';
        }

        if ((int) $product->stock <= 0) {
            return 'San pham "' . $product->name . '" da het hang.';
        }

        if ($quantity > (int) $product->stock) {
            return 'San pham "' . $product->name . '" chi con ' . (int) $product->stock . ' san pham.';
        }

        return null;
    }

    private function getCartAvailabilityError(Collection $cartItems): ?string
    {
        foreach ($cartItems as $item) {
            $product = $item['product'];
            $quantity = (int) $item['quantity'];

            if ($error = $this->getProductAvailabilityError($product, $quantity)) {
                return $error;
            }
        }

        return null;
    }

    private function lockAndValidateCartItems(Collection $cartItems): Collection
    {
        $productIds = $cartItems->pluck('product.id')->filter()->values()->all();
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        return $cartItems->map(function (array $item) use ($products) {
            $product = $products->get($item['product']->id);

            if (!$product) {
                throw ValidationException::withMessages([
                    'cart' => 'Mot san pham trong gio hang khong con ton tai.',
                ]);
            }

            $quantity = (int) $item['quantity'];
            if ($error = $this->getProductAvailabilityError($product, $quantity)) {
                throw ValidationException::withMessages(['cart' => $error]);
            }

            $unitPrice = $this->getOrderableUnitPrice($product);

            return [
                'product' => $product,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $unitPrice * $quantity,
            ];
        })->values();
    }

    private function lockAndValidateCoupon(array $summary): array
    {
        if (!$summary['coupon']) {
            return $summary;
        }

        $coupon = Coupon::query()
            ->whereKey($summary['coupon']->id)
            ->lockForUpdate()
            ->first();

        $user = auth()->user();

        if (!$coupon) {
            throw ValidationException::withMessages([
                'coupon' => 'Ma giam gia khong hop le hoac da het luot su dung.',
            ]);
        }

        if ($error = $coupon->getInvalidReason((int) $summary['subtotal'], optional($user)->id, optional($user)->email)) {
            throw ValidationException::withMessages([
                'coupon' => $error,
            ]);
        }

        $discountTotal = min($coupon->calculateDiscount((int) $summary['subtotal']), (int) $summary['subtotal']);
        $summary['coupon'] = $coupon;
        $summary['discount_total'] = $discountTotal;
        $summary['total'] = max(0, (int) $summary['subtotal'] + (int) $summary['shipping_fee'] - $discountTotal);

        return $summary;
    }

    private function validateCheckoutShipping(Request $request, User $user, VietnamAddressService $addressService): array
    {
        $request->merge([
            'customer_name' => $this->cleanTextInput($request->input('customer_name')),
            'customer_phone' => $this->normalizeVietnamPhone($request->input('customer_phone')),
            'customer_email' => $this->normalizeEmail($request->input('customer_email')),
            'address_line' => $this->cleanTextInput($request->input('address_line')),
            'delivery_area' => $this->cleanTextInput($request->input('delivery_area'), true),
            'notes' => $this->cleanTextInput($request->input('notes'), true),
        ]);

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:120', 'not_regex:/[<>]/'],
            'customer_phone' => ['required', 'string', 'regex:/^\+84[0-9]{9}$/'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'address_line' => ['required', 'string', 'min:5', 'max:255', 'not_regex:/[<>]/'],
            'delivery_area' => ['nullable', 'string', 'max:120', 'not_regex:/[<>]/'],
            'province_code' => ['required', 'string', 'max:20'],
            'ward_code' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000', 'not_regex:/[<>]/'],
            'save_address' => ['nullable', 'boolean'],
            'set_default_address' => ['nullable', 'boolean'],
        ], [
            'customer_name.required' => 'Vui lòng nhập họ và tên người nhận.',
            'customer_name.min' => 'Họ tên người nhận phải có ít nhất 2 ký tự.',
            'customer_phone.required' => 'Vui lòng nhập số điện thoại nhận hàng.',
            'customer_phone.regex' => 'Số điện thoại cần theo định dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.',
            'customer_email.email' => 'Email nhận hàng không hợp lệ.',
            'address_line.required' => 'Vui lòng nhập số nhà, tên đường hoặc toà nhà.',
            'address_line.min' => 'Địa chỉ cụ thể cần có ít nhất 5 ký tự.',
            'address_line.not_regex' => 'Địa chỉ cụ thể không được chứa ký tự HTML.',
            'delivery_area.not_regex' => 'Khu vực giao hàng không được chứa ký tự HTML.',
            'province_code.required' => 'Vui lòng chọn Tỉnh/Thành.',
            'ward_code.required' => 'Vui lòng chọn Phường/Xã.',
            'notes.not_regex' => 'Ghi chú không được chứa ký tự HTML.',
        ]);

        $resolvedAddress = $addressService->resolve($validated['province_code'], $validated['ward_code']);
        $validated['customer_email'] = strtolower(trim((string) ($validated['customer_email'] ?? $user->email)));
        $validated['province'] = $resolvedAddress['province_name'];
        $validated['ward'] = $resolvedAddress['ward_name'];
        $validated['province_code'] = $resolvedAddress['province_code'];
        $validated['ward_code'] = $resolvedAddress['ward_code'];
        $validated['district'] = $validated['delivery_area'] ?? null;
        $validated['shipping_address'] = $this->formatShippingAddress(
            $validated['address_line'],
            $validated['district'],
            $validated['ward'],
            $validated['province']
        );
        $validated['save_address'] = $request->boolean('save_address') || $request->boolean('set_default_address');
        $validated['set_default_address'] = $request->boolean('set_default_address');

        return $validated;
    }

    private function saveCheckoutAddress(User $user, array $validated): void
    {
        $shouldDefault = (bool) $validated['set_default_address'] || !$user->addresses()->exists();

        if ($shouldDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        $address = UserAddress::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'recipient_name' => $validated['customer_name'],
                'phone' => $validated['customer_phone'],
                'address_line' => $validated['address_line'],
                'province_code' => $validated['province_code'],
                'ward_code' => $validated['ward_code'],
            ],
            [
                'ward' => $validated['ward'],
                'district' => $validated['district'],
                'province' => $validated['province'],
                'is_default' => $shouldDefault,
            ]
        );

        if ($shouldDefault && !$address->is_default) {
            $address->forceFill(['is_default' => true])->save();
        }
    }

    private function resolveCartRedirectRoute(Request $request): string
    {
        switch ($request->input('redirect_to')) {
            case 'payment':
                return 'checkout.payment';
            case 'checkout':
                return 'checkout';
            default:
                return 'cart';
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

    private function formatShippingAddress(string $addressLine, ?string $deliveryArea, string $ward, string $province): string
    {
        return collect([
            $addressLine,
            $ward,
            $deliveryArea,
            $province,
        ])->filter()->implode(', ');
    }

    private function normalizeEmail($value): ?string
    {
        $email = Str::lower(trim((string) $value));

        return $email === '' ? null : $email;
    }

    private function canViewOrder(Order $order, ?string $token): bool
    {
        if (auth()->check()) {
            $user = auth()->user();

            if ($user->isAdmin() || (int) $order->user_id === (int) $user->id) {
                return true;
            }
        }

        if ($order->public_token) {
            return is_string($token) && hash_equals($order->public_token, $token);
        }

        return session('checkout_order_code') === $order->code;
    }

    private function generateOrderCode(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $code = 'DH' . now()->format('ymdHis') . sprintf('%03d', random_int(0, 999));

            if (!Order::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        return 'DH' . strtoupper(Str::random(10));
    }
}
