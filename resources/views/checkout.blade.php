@extends('layouts.app')

@section('title', 'Checkout - Thế Giới Trái Cây')

@section('content')
@php
    $addresses = $user->addresses ?? collect();
    $checkoutShipping = $checkoutShipping ?? [];
    $defaultShippingAddress = $defaultAddress
        ? collect([
            $defaultAddress->address_line,
            $defaultAddress->ward,
            $defaultAddress->district,
            $defaultAddress->province,
        ])->filter()->implode(', ')
        : '';
@endphp

<section class="checkout-page">
    <div class="checkout-shell">
        <div class="checkout-main">
            <div class="checkout-brand">
                <a href="{{ route('home') }}">Thế Giới Trái Cây</a>
            </div>

            <ol class="checkout-steps">
                <li><a href="{{ route('cart') }}">Giỏ hàng</a></li>
                <li class="is-active">Thông tin giao hàng</li>
                <li>Phương thức thanh toán</li>
            </ol>

            @if($errors->any())
                <div class="checkout-alert">
                    Vui lòng kiểm tra lại thông tin giao hàng bên dưới.
                </div>
            @endif

            <form method="post" action="{{ route('checkout.shipping') }}" id="checkoutForm">
                @csrf

                <section class="checkout-section">
                    <h1>Thông tin giao hàng</h1>

                    <div class="checkout-account">
                        <div class="checkout-avatar">{{ mb_substr($user->name ?: $user->email, 0, 1) }}</div>
                        <div>
                            <strong>{{ $user->name ?: 'Khách hàng' }}</strong>
                            <span>{{ $user->email }}</span>
                        </div>
                        <a href="{{ route('account.profile') }}">Hồ sơ</a>
                    </div>

                    @if($addresses->isNotEmpty())
                        <div class="checkout-field">
                            <label for="saved_address_id">Địa chỉ đã lưu</label>
                            <div class="checkout-select-wrap">
                                <select id="saved_address_id" name="saved_address_id">
                                    <option value="">Thêm địa chỉ mới...</option>
                                    @foreach($addresses as $address)
                                        @php
                                            $fullAddress = $address->full_address;
                                        @endphp
                                        <option
                                            value="{{ $address->id }}"
                                            data-name="{{ $address->recipient_name }}"
                                            data-phone="{{ $address->phone }}"
                                            data-address="{{ $fullAddress }}"
                                            {{ optional($defaultAddress)->id === $address->id ? 'selected' : '' }}
                                        >
                                            {{ $address->is_default ? 'Mặc định - ' : '' }}{{ $address->recipient_name }} · {{ $fullAddress }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="checkout-grid">
                        <div class="checkout-field">
                            <label for="customer_name">Họ và tên *</label>
                            <input
                                id="customer_name"
                                type="text"
                                name="customer_name"
                                value="{{ old('customer_name', $checkoutShipping['customer_name'] ?? (optional($defaultAddress)->recipient_name ?: $user->name)) }}"
                                class="@error('customer_name') is-invalid @enderror"
                                required
                            >
                            @error('customer_name')
                                <small>{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="checkout-field">
                            <label for="customer_phone">Số điện thoại *</label>
                            <input
                                id="customer_phone"
                                type="text"
                                name="customer_phone"
                                value="{{ old('customer_phone', $checkoutShipping['customer_phone'] ?? (optional($defaultAddress)->phone ?: $user->phone)) }}"
                                class="@error('customer_phone') is-invalid @enderror"
                                required
                            >
                            @error('customer_phone')
                                <small>{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="checkout-field">
                        <label for="customer_email">Email</label>
                        <input
                            id="customer_email"
                            type="email"
                            name="customer_email"
                            value="{{ old('customer_email', $checkoutShipping['customer_email'] ?? $user->email) }}"
                            class="@error('customer_email') is-invalid @enderror"
                        >
                        @error('customer_email')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="checkout-field">
                        <label for="shipping_address">Địa chỉ giao hàng *</label>
                        <input
                            id="shipping_address"
                            type="text"
                            name="shipping_address"
                            value="{{ old('shipping_address', $checkoutShipping['shipping_address'] ?? $defaultShippingAddress) }}"
                            class="@error('shipping_address') is-invalid @enderror"
                            required
                        >
                        @error('shipping_address')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="checkout-field">
                        <label for="notes">Ghi chú</label>
                        <textarea
                            id="notes"
                            name="notes"
                            rows="4"
                            class="@error('notes') is-invalid @enderror"
                            placeholder="Yêu cầu thêm cho đơn hàng">{{ old('notes', $checkoutShipping['notes'] ?? '') }}</textarea>
                        @error('notes')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="checkout-options">
                        <label>
                            <input type="checkbox" name="save_address" value="1" {{ old('save_address', $checkoutShipping['save_address'] ?? false) ? 'checked' : '' }}>
                            Lưu địa chỉ này vào hồ sơ của tôi
                        </label>
                        <label>
                            <input type="checkbox" name="set_default_address" value="1" {{ old('set_default_address', $checkoutShipping['set_default_address'] ?? false) ? 'checked' : '' }}>
                            Đặt làm địa chỉ mặc định
                        </label>
                    </div>

                    <div class="checkout-nav">
                        <a href="{{ route('cart') }}">Giỏ hàng</a>
                        <button type="submit">Tiếp tục đến phương thức thanh toán</button>
                    </div>
                </section>
            </form>
        </div>

        <aside class="checkout-sidebar">
            <div class="checkout-order-card">
                <h2>Đơn hàng của bạn</h2>

                <div class="checkout-items">
                    @foreach($cartItems as $item)
                        <div class="checkout-item">
                            <div class="checkout-thumb">
                                <img src="{{ $item['product']->thumb_url }}" alt="{{ $item['product']->name }}">
                                <span>{{ $item['quantity'] }}</span>
                            </div>
                            <div class="checkout-item-info">
                                <strong>{{ $item['product']->name }}</strong>
                                <small>{{ $item['product']->unit }}</small>
                            </div>
                            <div class="checkout-item-price">{{ number_format($item['line_total'], 0, ',', '.') }}₫</div>
                        </div>
                    @endforeach
                </div>

                <div class="checkout-coupon">
                    <form method="post" action="{{ route('cart.coupon.apply') }}">
                        @csrf
                        <input type="hidden" name="redirect_to" value="checkout">
                        <input type="text" name="code" placeholder="Mã giảm giá" value="{{ old('code') }}">
                        <button type="submit">Sử dụng</button>
                    </form>

                    @if(!empty($appliedCoupon))
                        <div class="checkout-coupon-applied">
                            <div>
                                <strong>{{ $appliedCoupon->code }}</strong>
                                <span>{{ $appliedCoupon->discount_label }}</span>
                            </div>
                            <form method="post" action="{{ route('cart.coupon.remove') }}">
                                @csrf
                                <input type="hidden" name="redirect_to" value="checkout">
                                <button type="submit">Bỏ mã</button>
                            </form>
                        </div>
                    @endif
                </div>

                <div class="checkout-total-list">
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

                <div class="checkout-grand-total">
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
    body:has(.checkout-page) .tgc-side-icons {
        display: none !important;
    }

    .checkout-page {
        background: #fff;
        padding: 18px 0 44px;
    }

    .checkout-shell {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 480px;
        min-height: 680px;
    }

    .checkout-main {
        display: flex;
        justify-content: flex-end;
        padding: 10px 56px 0 24px;
    }

    .checkout-main > form,
    .checkout-brand,
    .checkout-steps,
    .checkout-alert {
        width: min(720px, 100%);
    }

    .checkout-main {
        flex-direction: column;
        align-items: flex-end;
    }

    .checkout-brand a {
        color: #1f2e1d;
        font-size: 28px;
        font-weight: 600;
        text-decoration: none !important;
    }

    .checkout-steps {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        list-style: none;
        margin: 8px 0 14px;
        padding: 0;
    }

    .checkout-steps li,
    .checkout-steps a {
        color: #899086;
        font-size: 13px;
        text-decoration: none !important;
    }

    .checkout-steps li:not(:last-child)::after {
        color: #b8bdb5;
        content: ">";
        margin-left: 8px;
    }

    .checkout-steps a,
    .checkout-steps .is-active {
        color: #2d87b8;
    }

    .checkout-alert {
        background: #fff5f3;
        border: 1px solid #ffd1ca;
        border-radius: 6px;
        color: #bd3f30;
        font-weight: 700;
        margin-bottom: 14px;
        padding: 12px 14px;
    }

    .checkout-section h1 {
        color: #1f2e1d;
        font-size: 22px;
        font-weight: 600;
        margin: 0 0 16px;
    }

    .checkout-account {
        align-items: center;
        display: grid;
        grid-template-columns: 52px minmax(0, 1fr) auto;
        gap: 12px;
        margin-bottom: 16px;
    }

    .checkout-avatar {
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

    .checkout-account strong,
    .checkout-account span {
        display: block;
        line-height: 1.45;
    }

    .checkout-account span {
        color: #66705f;
    }

    .checkout-account a {
        color: #2d87b8;
        font-weight: 700;
        text-decoration: none !important;
    }

    .checkout-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .checkout-field {
        margin-bottom: 10px;
    }

    .checkout-field label {
        color: #6f786c;
        display: block;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .checkout-field input,
    .checkout-field select,
    .checkout-field textarea {
        background: #fff;
        border: 1px solid #d8ddd5;
        border-radius: 6px;
        color: #273322;
        font-size: 14px;
        min-height: 44px;
        outline: none;
        padding: 0 12px;
        width: 100%;
    }

    .checkout-field textarea {
        min-height: 78px;
        padding: 11px 12px;
        resize: vertical;
    }

    .checkout-field input:focus,
    .checkout-field select:focus,
    .checkout-field textarea:focus {
        border-color: #2d9bd0;
        box-shadow: 0 0 0 2px rgba(45, 155, 208, 0.12);
    }

    .checkout-field .is-invalid {
        border-color: #ff5a5f;
        box-shadow: 0 0 0 1px rgba(255, 90, 95, 0.08);
    }

    .checkout-field small {
        color: #ff4f55;
        display: block;
        font-size: 12px;
        margin-top: 5px;
    }

    .checkout-select-wrap {
        position: relative;
    }

    .checkout-select-wrap::after {
        color: #8b9188;
        content: "\f107";
        font-family: FontAwesome;
        pointer-events: none;
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
    }

    .checkout-select-wrap select {
        appearance: none;
        padding-right: 42px;
    }

    .checkout-options {
        display: grid;
        gap: 6px;
        margin: 4px 0 16px;
    }

    .checkout-options label {
        align-items: center;
        color: #495344;
        display: flex;
        font-size: 13px;
        gap: 8px;
        margin: 0;
    }

    .checkout-options input {
        margin: 0;
    }

    .checkout-payment-section {
        border-top: 1px solid #edf0eb;
        margin-top: 12px;
        padding-top: 14px;
    }

    .checkout-payment-section h2 {
        color: #1f2e1d;
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 10px;
    }

    .checkout-payment-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .checkout-payment-option {
        align-items: flex-start;
        background: #fff;
        border: 1px solid #dfe7da;
        border-radius: 8px;
        cursor: pointer;
        display: grid;
        gap: 10px;
        grid-template-columns: 34px minmax(0, 1fr);
        margin: 0;
        min-height: 82px;
        padding: 12px;
        transition: border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }

    .checkout-payment-option input {
        height: 1px;
        opacity: 0;
        position: absolute;
        width: 1px;
    }

    .checkout-payment-option:has(input:checked),
    .checkout-payment-option.is-selected {
        background: #f7fcf1;
        border-color: #75b72c;
        box-shadow: 0 0 0 2px rgba(117, 183, 44, 0.12);
    }

    .checkout-payment-icon {
        align-items: center;
        background: #eef8e5;
        border-radius: 50%;
        color: #5da014;
        display: flex;
        height: 34px;
        justify-content: center;
        width: 34px;
    }

    .checkout-payment-option strong,
    .checkout-payment-option small {
        display: block;
    }

    .checkout-payment-option strong {
        color: #1f2e1d;
        font-size: 14px;
        line-height: 1.3;
        margin-bottom: 4px;
    }

    .checkout-payment-option small {
        color: #687463;
        font-size: 12px;
        line-height: 1.45;
    }

    .checkout-payment-error {
        color: #ff4f55;
        display: block;
        font-size: 12px;
        margin-top: 7px;
    }

    .checkout-nav {
        align-items: center;
        display: flex;
        justify-content: space-between;
        gap: 14px;
        margin-top: 14px;
    }

    .checkout-nav a {
        color: #2d87b8;
        font-size: 15px;
        text-decoration: none !important;
    }

    .checkout-nav button {
        background: #2f93c4;
        border: 0;
        border-radius: 5px;
        color: #fff;
        font-size: 15px;
        font-weight: 700;
        min-height: 46px;
        min-width: 360px;
        padding: 0 28px;
    }

    .checkout-sidebar {
        background: #f7f7f7;
        border-left: 1px solid #e1e1e1;
        padding: 24px 36px;
    }

    .checkout-order-card {
        max-width: 540px;
        position: sticky;
        top: 72px;
    }

    .checkout-order-card h2 {
        color: #1f2e1d;
        font-size: 21px;
        font-weight: 700;
        margin: 0 0 14px;
    }

    .checkout-items {
        border-bottom: 1px solid #dedede;
        display: grid;
        gap: 12px;
        padding-bottom: 14px;
    }

    .checkout-item {
        align-items: center;
        display: grid;
        grid-template-columns: 68px minmax(0, 1fr) auto;
        gap: 12px;
    }

    .checkout-thumb {
        position: relative;
    }

    .checkout-thumb img {
        background: #fff;
        border: 1px solid #dedede;
        border-radius: 8px;
        height: 68px;
        object-fit: cover;
        width: 68px;
    }

    .checkout-thumb span {
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

    .checkout-item-info {
        display: grid;
        gap: 4px;
        min-width: 0;
    }

    .checkout-item-info strong {
        color: #263322;
        font-size: 14px;
        line-height: 1.4;
    }

    .checkout-item-info small {
        color: #777;
        font-size: 12px;
    }

    .checkout-item-price {
        color: #1f2e1d;
        font-size: 14px;
        font-weight: 700;
        white-space: nowrap;
    }

    .checkout-coupon {
        border-bottom: 1px solid #dedede;
        padding: 18px 0 14px;
    }

    .checkout-coupon form:first-child {
        display: grid;
        gap: 12px;
        grid-template-columns: minmax(0, 1fr) 128px;
    }

    .checkout-coupon input {
        border: 1px solid #d8ddd5;
        border-radius: 6px;
        font-size: 14px;
        height: 46px;
        padding: 0 14px;
        width: 100%;
    }

    .checkout-coupon button {
        background: #aeb0b3;
        border: 0;
        border-radius: 6px;
        color: #fff;
        font-size: 14px;
        font-weight: 700;
        height: 46px;
        padding: 0 16px;
    }

    .checkout-coupon-applied {
        align-items: center;
        background: #eef8e8;
        border: 1px solid #cfe6bf;
        border-radius: 8px;
        display: flex;
        justify-content: space-between;
        gap: 10px;
        margin-top: 12px;
        padding: 10px 12px;
    }

    .checkout-coupon-applied strong,
    .checkout-coupon-applied span {
        display: block;
    }

    .checkout-coupon-applied strong {
        color: #456d18;
    }

    .checkout-coupon-applied span {
        color: #66705f;
        font-size: 12px;
    }

    .checkout-coupon-applied form {
        margin: 0;
    }

    .checkout-coupon-applied button {
        background: #fff;
        border: 1px solid #cfe6bf;
        color: #456d18;
        font-size: 12px;
        height: 34px;
    }

    .checkout-total-list {
        border-bottom: 1px solid #dedede;
        display: grid;
        gap: 10px;
        padding: 20px 0;
    }

    .checkout-total-list div,
    .checkout-grand-total {
        align-items: center;
        display: flex;
        justify-content: space-between;
        gap: 16px;
    }

    .checkout-total-list span,
    .checkout-total-list strong {
        color: #555f50;
        font-size: 14px;
        font-weight: 500;
    }

    .checkout-grand-total {
        padding-top: 18px;
    }

    .checkout-grand-total span {
        color: #1f2e1d;
        font-size: 18px;
    }

    .checkout-grand-total strong {
        color: #1f2e1d;
        font-size: 26px;
        font-weight: 500;
        white-space: nowrap;
    }

    .checkout-grand-total small {
        color: #8c9288;
        font-size: 13px;
        font-weight: 500;
        margin-right: 8px;
    }

    @media (max-width: 1199px) {
        .checkout-shell {
            grid-template-columns: minmax(0, 1fr) 420px;
        }

        .checkout-main {
            padding-right: 36px;
        }

        .checkout-sidebar {
            padding-left: 28px;
            padding-right: 28px;
        }
    }

    @media (max-width: 991px) {
        .checkout-shell {
            display: flex;
            flex-direction: column;
        }

        .checkout-main {
            align-items: stretch;
            display: block;
            padding: 20px 16px 30px;
        }

        .checkout-main > form,
        .checkout-brand,
        .checkout-steps,
        .checkout-alert {
            width: 100%;
        }

        .checkout-sidebar {
            border-left: 0;
            border-top: 1px solid #e1e1e1;
            padding: 24px 16px 40px;
        }

        .checkout-order-card {
            max-width: none;
            position: static;
        }
    }

    @media (max-width: 575px) {
        .checkout-brand a {
            font-size: 28px;
        }

        .checkout-grid,
        .checkout-payment-grid,
        .checkout-coupon form:first-child {
            grid-template-columns: 1fr;
        }

        .checkout-account {
            grid-template-columns: 54px minmax(0, 1fr);
        }

        .checkout-account > a {
            grid-column: 2;
        }

        .checkout-nav {
            align-items: stretch;
            flex-direction: column-reverse;
        }

        .checkout-nav button {
            min-width: 0;
            width: 100%;
        }

        .checkout-item {
            grid-template-columns: 72px minmax(0, 1fr);
        }

        .checkout-item-price {
            grid-column: 2;
        }

        .checkout-thumb img {
            height: 72px;
            width: 72px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var select = document.getElementById('saved_address_id');
        if (!select) {
            return;
        }

        var nameInput = document.getElementById('customer_name');
        var phoneInput = document.getElementById('customer_phone');
        var addressInput = document.getElementById('shipping_address');

        function fillAddress() {
            var option = select.options[select.selectedIndex];
            if (!option || !option.value) {
                return;
            }

            if (nameInput) {
                nameInput.value = option.getAttribute('data-name') || '';
            }

            if (phoneInput) {
                phoneInput.value = option.getAttribute('data-phone') || '';
            }

            if (addressInput) {
                addressInput.value = option.getAttribute('data-address') || '';
            }
        }

        select.addEventListener('change', fillAddress);
    })();
</script>
@endpush
