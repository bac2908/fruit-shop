@extends('layouts.app')

@section('title', 'Phương thức thanh toán - Thế Giới Trái Cây')

@section('content')
@php
    $selectedPaymentMethod = $selectedPaymentMethod ?? \App\Models\Order::PAYMENT_METHOD_COD;
@endphp

<section class="checkout-payment-page">
    <div class="payment-shell">
        <main class="payment-main">
            <div class="payment-brand">
                <a href="{{ route('home') }}">Thế Giới Trái Cây</a>
            </div>

            <ol class="payment-steps">
                <li><a href="{{ route('cart') }}">Giỏ hàng</a></li>
                <li><a href="{{ route('checkout') }}">Thông tin giao hàng</a></li>
                <li class="is-active">Phương thức thanh toán</li>
            </ol>

            @if($errors->any())
                <div class="payment-alert">
                    Vui lòng kiểm tra lại phương thức thanh toán.
                </div>
            @endif

            <section class="payment-card">
                <h1>Phương thức thanh toán</h1>

                <div class="shipping-review">
                    <div class="review-avatar">{{ mb_substr($checkoutShipping['customer_name'] ?? $user->name ?? 'K', 0, 1) }}</div>
                    <div>
                        <strong>{{ $checkoutShipping['customer_name'] ?? 'Khách hàng' }}</strong>
                        <span>{{ $checkoutShipping['customer_phone'] ?? 'Chưa có số điện thoại' }}</span>
                        <span>{{ $checkoutShipping['shipping_address'] ?? 'Chưa có địa chỉ giao hàng' }}</span>
                    </div>
                    <a href="{{ route('checkout') }}">Sửa</a>
                </div>

                <form method="post" action="{{ route('checkout.place') }}">
                    @csrf

                    <div class="payment-method-grid">
                        <label class="payment-method-option {{ $selectedPaymentMethod === \App\Models\Order::PAYMENT_METHOD_COD ? 'is-selected' : '' }}">
                            <input
                                type="radio"
                                name="payment_method"
                                value="{{ \App\Models\Order::PAYMENT_METHOD_COD }}"
                                {{ $selectedPaymentMethod === \App\Models\Order::PAYMENT_METHOD_COD ? 'checked' : '' }}
                            >
                            <span class="method-icon"><i class="fa fa-truck"></i></span>
                            <span>
                                <strong>Thanh toán khi nhận hàng</strong>
                                <small>Trả tiền mặt cho nhân viên giao hàng sau khi kiểm tra đơn.</small>
                            </span>
                        </label>

                        <label class="payment-method-option {{ $selectedPaymentMethod === \App\Models\Order::PAYMENT_METHOD_BANK_TRANSFER ? 'is-selected' : '' }}">
                            <input
                                type="radio"
                                name="payment_method"
                                value="{{ \App\Models\Order::PAYMENT_METHOD_BANK_TRANSFER }}"
                                {{ $selectedPaymentMethod === \App\Models\Order::PAYMENT_METHOD_BANK_TRANSFER ? 'checked' : '' }}
                            >
                            <span class="method-icon"><i class="fa fa-university"></i></span>
                            <span>
                                <strong>Chuyển khoản ngân hàng</strong>
                                <small>Đặt hàng trước, chuyển khoản theo mã đơn ở trang cảm ơn.</small>
                            </span>
                        </label>

                        <label class="payment-method-option {{ $selectedPaymentMethod === \App\Models\Order::PAYMENT_METHOD_MOMO ? 'is-selected' : '' }}">
                            <input
                                type="radio"
                                name="payment_method"
                                value="{{ \App\Models\Order::PAYMENT_METHOD_MOMO }}"
                                {{ $selectedPaymentMethod === \App\Models\Order::PAYMENT_METHOD_MOMO ? 'checked' : '' }}
                            >
                            <span class="method-icon method-icon-momo"><i class="fa fa-mobile"></i></span>
                            <span>
                                <strong>Ví MoMo sandbox</strong>
                                <small>Chuyển sang môi trường thử nghiệm MoMo để thanh toán đơn hàng.</small>
                            </span>
                        </label>
                    </div>

                    @error('payment_method')
                        <small class="payment-error">{{ $message }}</small>
                    @enderror

                    <div class="payment-actions">
                        <a href="{{ route('checkout') }}">Thông tin giao hàng</a>
                        <button type="submit">Đặt hàng</button>
                    </div>
                </form>
            </section>
        </main>

        <aside class="payment-sidebar">
            <div class="payment-order-card">
                <h2>Đơn hàng của bạn</h2>

                <div class="payment-items">
                    @foreach($cartItems as $item)
                        <div class="payment-item">
                            <div class="payment-thumb">
                                <img src="{{ $item['product']->thumb_url }}" alt="{{ $item['product']->name }}">
                                <span>{{ $item['quantity'] }}</span>
                            </div>
                            <div class="payment-item-info">
                                <strong>{{ $item['product']->name }}</strong>
                                <small>{{ $item['product']->unit }}</small>
                            </div>
                            <div class="payment-item-price">{{ number_format($item['line_total'], 0, ',', '.') }}₫</div>
                        </div>
                    @endforeach
                </div>

                <div class="payment-coupon">
                    <form method="post" action="{{ route('cart.coupon.apply') }}">
                        @csrf
                        <input type="hidden" name="redirect_to" value="payment">
                        <input type="text" name="code" placeholder="Mã giảm giá" value="{{ old('code') }}">
                        <button type="submit">Sử dụng</button>
                    </form>

                    @if(!empty($appliedCoupon))
                        <div class="payment-coupon-applied">
                            <div>
                                <strong>{{ $appliedCoupon->code }}</strong>
                                <span>{{ $appliedCoupon->discount_label }}</span>
                            </div>
                            <form method="post" action="{{ route('cart.coupon.remove') }}">
                                @csrf
                                <input type="hidden" name="redirect_to" value="payment">
                                <button type="submit">Bỏ mã</button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="payment-total-list">
                    <div>
                        <span>Tạm tính</span>
                        <strong>{{ number_format($summary['subtotal'] ?? 0, 0, ',', '.') }}₫</strong>
                    </div>
                    <div>
                        <span>Giảm giá</span>
                        <strong>-{{ number_format($summary['discount_total'] ?? 0, 0, ',', '.') }}₫</strong>
                    </div>
                    <div>
                        <span>Phí vận chuyển</span>
                        <strong>{{ (int) ($summary['shipping_fee'] ?? 0) > 0 ? number_format($summary['shipping_fee'], 0, ',', '.') . '₫' : '—' }}</strong>
                    </div>
                </div>

                <div class="payment-grand-total">
                    <span>Tổng cộng</span>
                    <strong><small>VND</small> {{ number_format($summary['total'] ?? 0, 0, ',', '.') }}₫</strong>
                </div>
            </div>
        </aside>
    </div>
</section>
@endsection

@push('styles')
<style>
    body:has(.checkout-payment-page) .tgc-side-icons {
        display: none !important;
    }

    .checkout-payment-page {
        background: #fff;
        padding: 18px 0 44px;
    }

    .payment-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 480px;
        min-height: 620px;
    }

    .payment-main {
        align-items: flex-end;
        display: flex;
        flex-direction: column;
        padding: 10px 56px 0 24px;
    }

    .payment-brand,
    .payment-steps,
    .payment-alert,
    .payment-card {
        width: min(720px, 100%);
    }

    .payment-brand a {
        color: #1f2e1d;
        font-size: 28px;
        font-weight: 600;
        text-decoration: none !important;
    }

    .payment-steps {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        list-style: none;
        margin: 8px 0 16px;
        padding: 0;
    }

    .payment-steps li,
    .payment-steps a {
        color: #899086;
        font-size: 13px;
        text-decoration: none !important;
    }

    .payment-steps li:not(:last-child)::after {
        color: #b8bdb5;
        content: ">";
        margin-left: 8px;
    }

    .payment-steps a,
    .payment-steps .is-active {
        color: #2d87b8;
    }

    .payment-alert {
        background: #fff5f3;
        border: 1px solid #ffd1ca;
        border-radius: 6px;
        color: #bd3f30;
        font-weight: 700;
        margin-bottom: 14px;
        padding: 12px 14px;
    }

    .payment-card {
        border-top: 1px solid #ecefec;
        padding-top: 16px;
    }

    .payment-card h1 {
        color: #1f2e1d;
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 14px;
    }

    .shipping-review {
        align-items: center;
        background: #f8fbf4;
        border: 1px solid #dfead8;
        border-radius: 9px;
        display: grid;
        gap: 12px;
        grid-template-columns: 52px minmax(0, 1fr) auto;
        margin-bottom: 16px;
        padding: 12px;
    }

    .review-avatar {
        align-items: center;
        background: #d8d8d8;
        border-radius: 8px;
        color: #fff;
        display: flex;
        font-size: 24px;
        font-weight: 700;
        height: 52px;
        justify-content: center;
        text-transform: uppercase;
        width: 52px;
    }

    .shipping-review strong,
    .shipping-review span {
        display: block;
        line-height: 1.45;
    }

    .shipping-review span {
        color: #66705f;
        font-size: 13px;
    }

    .shipping-review a {
        color: #2d87b8;
        font-weight: 700;
        text-decoration: none !important;
    }

    .payment-method-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .payment-method-option {
        align-items: flex-start;
        background: #fff;
        border: 1px solid #dfe7da;
        border-radius: 8px;
        cursor: pointer;
        display: grid;
        gap: 10px;
        grid-template-columns: 34px minmax(0, 1fr);
        margin: 0;
        min-height: 112px;
        padding: 12px;
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .payment-method-option input {
        height: 1px;
        opacity: 0;
        position: absolute;
        width: 1px;
    }

    .payment-method-option:has(input:checked),
    .payment-method-option.is-selected {
        background: #f7fcf1;
        border-color: #75b72c;
        box-shadow: 0 0 0 2px rgba(117, 183, 44, 0.12);
    }

    .method-icon {
        align-items: center;
        background: #eef8e5;
        border-radius: 50%;
        color: #5da014;
        display: flex;
        height: 34px;
        justify-content: center;
        width: 34px;
    }

    .method-icon-momo {
        background: #fff0f8;
        color: #b0006d;
    }

    .payment-method-option strong,
    .payment-method-option small {
        display: block;
    }

    .payment-method-option strong {
        color: #1f2e1d;
        font-size: 14px;
        line-height: 1.3;
        margin-bottom: 5px;
    }

    .payment-method-option small {
        color: #687463;
        font-size: 12px;
        line-height: 1.45;
    }

    .payment-error {
        color: #ff4f55;
        display: block;
        font-size: 12px;
        margin-top: 7px;
    }

    .payment-actions {
        align-items: center;
        display: flex;
        justify-content: space-between;
        gap: 14px;
        margin-top: 18px;
    }

    .payment-actions a {
        color: #2d87b8;
        font-size: 15px;
        text-decoration: none !important;
    }

    .payment-actions button {
        background: #2f93c4;
        border: 0;
        border-radius: 5px;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        min-height: 46px;
        min-width: 280px;
        padding: 0 28px;
    }

    .payment-sidebar {
        background: #f7f7f7;
        border-left: 1px solid #e1e1e1;
        padding: 24px 36px;
    }

    .payment-order-card {
        max-width: 540px;
        position: sticky;
        top: 72px;
    }

    .payment-order-card h2 {
        color: #1f2e1d;
        font-size: 21px;
        font-weight: 700;
        margin: 0 0 14px;
    }

    .payment-items {
        border-bottom: 1px solid #dedede;
        display: grid;
        gap: 12px;
        padding-bottom: 14px;
    }

    .payment-item {
        align-items: center;
        display: grid;
        gap: 12px;
        grid-template-columns: 68px minmax(0, 1fr) auto;
    }

    .payment-thumb {
        position: relative;
    }

    .payment-thumb img {
        background: #fff;
        border: 1px solid #dedede;
        border-radius: 8px;
        height: 68px;
        object-fit: cover;
        width: 68px;
    }

    .payment-thumb span {
        align-items: center;
        background: #96999d;
        border-radius: 50%;
        color: #fff;
        display: flex;
        font-size: 12px;
        font-weight: 700;
        height: 24px;
        justify-content: center;
        position: absolute;
        right: -9px;
        top: -10px;
        width: 24px;
    }

    .payment-item-info {
        display: grid;
        gap: 4px;
        min-width: 0;
    }

    .payment-item-info strong {
        color: #263322;
        font-size: 14px;
        line-height: 1.4;
    }

    .payment-item-info small {
        color: #777;
        font-size: 12px;
    }

    .payment-item-price {
        color: #1f2e1d;
        font-size: 14px;
        font-weight: 700;
        white-space: nowrap;
    }

    .payment-coupon {
        border-bottom: 1px solid #dedede;
        padding: 18px 0 14px;
    }

    .payment-coupon form:first-child {
        display: grid;
        gap: 12px;
        grid-template-columns: minmax(0, 1fr) 128px;
    }

    .payment-coupon input {
        border: 1px solid #d8ddd5;
        border-radius: 6px;
        font-size: 14px;
        height: 46px;
        padding: 0 14px;
        width: 100%;
    }

    .payment-coupon button {
        background: #aeb0b3;
        border: 0;
        border-radius: 6px;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        height: 46px;
        padding: 0 16px;
    }

    .payment-coupon-applied {
        align-items: center;
        background: #eef8e8;
        border: 1px solid #cfe6bf;
        border-radius: 8px;
        display: flex;
        gap: 10px;
        justify-content: space-between;
        margin-top: 12px;
        padding: 10px 12px;
    }

    .payment-coupon-applied strong,
    .payment-coupon-applied span {
        display: block;
    }

    .payment-coupon-applied strong {
        color: #456d18;
    }

    .payment-coupon-applied span {
        color: #66705f;
        font-size: 12px;
    }

    .payment-coupon-applied form {
        margin: 0;
    }

    .payment-coupon-applied button {
        background: #fff;
        border: 1px solid #cfe6bf;
        color: #456d18;
        font-size: 12px;
        height: 34px;
    }

    .payment-total-list {
        border-bottom: 1px solid #dedede;
        display: grid;
        gap: 10px;
        padding: 20px 0;
    }

    .payment-total-list div,
    .payment-grand-total {
        align-items: center;
        display: flex;
        gap: 16px;
        justify-content: space-between;
    }

    .payment-total-list span,
    .payment-total-list strong {
        color: #555f50;
        font-size: 14px;
        font-weight: 500;
    }

    .payment-grand-total {
        padding-top: 18px;
    }

    .payment-grand-total span {
        color: #1f2e1d;
        font-size: 18px;
    }

    .payment-grand-total strong {
        color: #1f2e1d;
        font-size: 26px;
        font-weight: 500;
        white-space: nowrap;
    }

    .payment-grand-total small {
        color: #8c9288;
        font-size: 13px;
        font-weight: 500;
        margin-right: 8px;
    }

    @media (max-width: 1199px) {
        .payment-shell {
            grid-template-columns: minmax(0, 1fr) 420px;
        }

        .payment-main {
            padding-right: 36px;
        }

        .payment-sidebar {
            padding-left: 28px;
            padding-right: 28px;
        }
    }

    @media (max-width: 991px) {
        .payment-shell {
            display: flex;
            flex-direction: column;
        }

        .payment-main {
            align-items: stretch;
            display: block;
            padding: 20px 16px 30px;
        }

        .payment-brand,
        .payment-steps,
        .payment-alert,
        .payment-card {
            width: 100%;
        }

        .payment-sidebar {
            border-left: 0;
            border-top: 1px solid #e1e1e1;
            padding: 24px 16px 40px;
        }

        .payment-order-card {
            max-width: none;
            position: static;
        }
    }

    @media (max-width: 575px) {
        .payment-brand a {
            font-size: 28px;
        }

        .payment-method-grid,
        .payment-coupon form:first-child {
            grid-template-columns: 1fr;
        }

        .shipping-review {
            grid-template-columns: 52px minmax(0, 1fr);
        }

        .shipping-review > a {
            grid-column: 2;
        }

        .payment-actions {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .payment-actions button {
            min-width: 0;
            width: 100%;
        }

        .payment-item {
            grid-template-columns: 72px minmax(0, 1fr);
        }

        .payment-item-price {
            grid-column: 2;
        }

        .payment-thumb img {
            height: 72px;
            width: 72px;
        }
    }
</style>
@endpush
