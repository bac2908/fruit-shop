<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index()
    {
        $cartItems = $this->getCartItems();
        $summary = $this->getCartSummary($cartItems);
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

        if ($request->boolean('checkout_redirect')) {
            return redirect()->route('checkout');
        }

        return redirect()->back()->with('success', 'Da them san pham vao gio hang.');
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
            session()->forget('cart_coupon_code');
        }

        return redirect()->route('cart')->with('success', 'Da xoa san pham khoi gio hang.');
    }

    public function applyCoupon(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80'],
        ]);

        $cartItems = $this->getCartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Gio hang dang trong, khong the ap ma giam gia.');
        }

        $subtotal = (int) $cartItems->sum('line_total');
        $code = trim((string) $validated['code']);

        $coupon = Coupon::query()
            ->whereRaw('LOWER(code) = ?', [Str::lower($code)])
            ->first();

        if (!$coupon || !$coupon->isValid()) {
            return redirect()->route('cart')->with('error', 'Ma giam gia khong hop le hoac da het han.');
        }

        if ($coupon->min_order_total && $subtotal < (int) $coupon->min_order_total) {
            return redirect()->route('cart')->with('error', 'Don hang chua dat gia tri toi thieu de ap ma.');
        }

        session(['cart_coupon_code' => $coupon->code]);

        return redirect()->route('cart')->with('success', 'Ap ma giam gia thanh cong.');
    }

    public function removeCoupon()
    {
        session()->forget('cart_coupon_code');

        return redirect()->route('cart')->with('success', 'Da bo ma giam gia.');
    }

    public function checkout()
    {
        $cartItems = $this->getCartItems();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Gio hang dang trong. Vui long them san pham de tiep tuc.');
        }

        if ($error = $this->getCartAvailabilityError($cartItems)) {
            return redirect()->route('cart')->with('error', $error);
        }

        $summary = $this->getCartSummary($cartItems);

        return view('checkout', [
            'cartItems' => $cartItems,
            'summary' => $summary,
            'appliedCoupon' => $summary['coupon'],
        ]);
    }

    public function placeOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:120'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $cartItems = $this->getCartItems();
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart')->with('error', 'Gio hang dang trong.');
        }

        if ($error = $this->getCartAvailabilityError($cartItems)) {
            return redirect()->route('cart')->with('error', $error);
        }

        $order = DB::transaction(function () use ($validated, $cartItems) {
            $cartItems = $this->lockAndValidateCartItems($cartItems);
            $summary = $this->getCartSummary($cartItems);
            $summary = $this->lockAndValidateCoupon($summary);

            $order = Order::query()->create([
                'code' => $this->generateOrderCode(),
                'public_token' => Str::random(48),
                'user_id' => auth()->id(),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'] ?? null,
                'customer_email' => $validated['customer_email'] ?? null,
                'shipping_address' => $validated['shipping_address'],
                'customer_note' => $validated['notes'] ?? null,
                'subtotal' => (int) $summary['subtotal'],
                'shipping_fee' => (int) $summary['shipping_fee'],
                'discount_total' => (int) $summary['discount_total'],
                'total' => (int) $summary['total'],
                'coupon_code' => $summary['coupon'] ? $summary['coupon']->code : null,
                'status' => Order::STATUS_PENDING,
                'payment_method' => 'cod',
                'payment_status' => Order::PAYMENT_STATUS_UNPAID,
                'shipping_status' => Order::SHIPPING_STATUS_PENDING,
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
                    'user_id' => auth()->id(),
                    'type' => 'order',
                    'quantity' => -1 * (int) $item['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $product->cost_price,
                    'note' => 'Xuat kho cho don ' . $order->code,
                ]);
            }

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'previous_status' => null,
                'status' => Order::STATUS_PENDING,
                'note' => 'Don hang moi duoc tao.',
                'created_at' => now(),
            ]);

            if ($summary['coupon']) {
                CouponUsage::query()->create([
                    'coupon_id' => $summary['coupon']->id,
                    'order_id' => $order->id,
                    'user_id' => auth()->id(),
                    'coupon_code' => $summary['coupon']->code,
                    'customer_email' => $validated['customer_email'] ?? null,
                    'discount_total' => (int) $summary['discount_total'],
                    'used_at' => now(),
                ]);

                $summary['coupon']->increment('used_count');
            }

            return $order;
        });

        session([
            'checkout_order_code' => $order->code,
            'checkout_order_token' => $order->public_token,
        ]);
        session()->forget('cart');
        session()->forget('cart_coupon_code');

        return redirect()->route('checkout.thankyou', [
            'code' => $order->code,
            'token' => $order->public_token,
        ]);
    }

    public function thankYou(string $code, ?string $token = null)
    {
        $order = Order::query()
            ->with(['items', 'items.product'])
            ->where('code', $code)
            ->firstOrFail();

        if (!$this->canViewOrder($order, $token)) {
            abort(404);
        }

        return view('checkout-success', [
            'order' => $order,
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

    private function getCartSummary(Collection $cartItems): array
    {
        $subtotal = (int) $cartItems->sum('line_total');
        $shippingFee = 0;
        $coupon = $this->resolveCoupon($subtotal);
        $discountTotal = $coupon ? min($coupon->calculateDiscount($subtotal), $subtotal) : 0;
        $total = max(0, $subtotal + $shippingFee - $discountTotal);

        return [
            'subtotal' => $subtotal,
            'shipping_fee' => $shippingFee,
            'discount_total' => $discountTotal,
            'total' => $total,
            'coupon' => $coupon,
        ];
    }

    private function resolveCoupon(int $subtotal): ?Coupon
    {
        $couponCode = trim((string) session('cart_coupon_code', ''));

        if ($couponCode === '') {
            return null;
        }

        $coupon = Coupon::query()
            ->whereRaw('LOWER(code) = ?', [Str::lower($couponCode)])
            ->first();

        if (!$coupon || !$coupon->isValid()) {
            session()->forget('cart_coupon_code');
            return null;
        }

        if ($coupon->min_order_total && $subtotal < (int) $coupon->min_order_total) {
            return null;
        }

        return $coupon;
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

        if (!$coupon || !$coupon->isValid()) {
            throw ValidationException::withMessages([
                'coupon' => 'Ma giam gia khong hop le hoac da het luot su dung.',
            ]);
        }

        if ($coupon->min_order_total && (int) $summary['subtotal'] < (int) $coupon->min_order_total) {
            throw ValidationException::withMessages([
                'coupon' => 'Don hang chua dat gia tri toi thieu de ap ma.',
            ]);
        }

        $discountTotal = min($coupon->calculateDiscount((int) $summary['subtotal']), (int) $summary['subtotal']);
        $summary['coupon'] = $coupon;
        $summary['discount_total'] = $discountTotal;
        $summary['total'] = max(0, (int) $summary['subtotal'] + (int) $summary['shipping_fee'] - $discountTotal);

        return $summary;
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
