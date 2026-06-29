@extends('layouts.app')

@section('title', 'Checkout - Thế Giới Trái Cây')

@section('content')
@php
    $addresses = $user->addresses ?? collect();
    $checkoutShipping = $checkoutShipping ?? [];
    $provinceOptions = $vietnamProvinces ?? [];
    $addressDataUrl = $vietnamAddressDataUrl ?? asset('data/vietnam-addresses.json');
    $selectedProvinceCode = old('province_code', $checkoutShipping['province_code'] ?? optional($defaultAddress)->province_code);
    $selectedWardCode = old('ward_code', $checkoutShipping['ward_code'] ?? optional($defaultAddress)->ward_code);
    $addressLineValue = old('address_line', $checkoutShipping['address_line'] ?? optional($defaultAddress)->address_line);
    $deliveryAreaValue = old('delivery_area', $checkoutShipping['district'] ?? optional($defaultAddress)->district);
    $shippingRules = $shippingRules ?? [];
    $shippingQuote = $summary['shipping_quote'] ?? [];
    $shippingPending = (bool) ($shippingQuote['is_pending'] ?? true);
    $shippingFee = (int) ($summary['shipping_fee'] ?? 0);
    $shippingEstimated = ($shippingQuote['fee_status'] ?? '') === 'estimated';
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
                                            data-address-line="{{ $address->address_line }}"
                                            data-delivery-area="{{ $address->district }}"
                                            data-province-code="{{ $address->province_code }}"
                                            data-ward-code="{{ $address->ward_code }}"
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
                        <label for="address_line">Số nhà, tên đường, toà nhà *</label>
                        <input
                            id="address_line"
                            type="text"
                            name="address_line"
                            value="{{ $addressLineValue }}"
                            class="@error('address_line') is-invalid @enderror"
                            placeholder="VD: 74 Trần Thái Tông, hẻm/căn hộ/tầng nếu có"
                            required
                        >
                        @error('address_line')
                            <small>{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="checkout-grid checkout-address-grid">
                        <div class="checkout-field">
                            <label for="province_code">Tỉnh/Thành *</label>
                            <div class="checkout-select-wrap">
                                <select id="province_code" name="province_code" class="@error('province_code') is-invalid @enderror" required>
                                    <option value="">Chọn Tỉnh/Thành</option>
                                    @foreach($provinceOptions as $province)
                                        <option value="{{ $province['code'] }}" {{ (string) $selectedProvinceCode === (string) $province['code'] ? 'selected' : '' }}>{{ $province['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @error('province_code')
                                <small>{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="checkout-field">
                            <label for="ward_code">Phường/Xã/Đặc khu *</label>
                            <div class="checkout-select-wrap">
                                <select id="ward_code" name="ward_code" data-selected="{{ $selectedWardCode }}" class="@error('ward_code') is-invalid @enderror" required>
                                    <option value="">Chọn Phường/Xã</option>
                                </select>
                            </div>
                            @error('ward_code')
                                <small>{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <input id="delivery_area" type="hidden" name="delivery_area" value="{{ $deliveryAreaValue }}">

                    <div class="checkout-field">
                        <small>Không cần nhập Quận/Huyện. Địa chỉ đang dùng dữ liệu hành chính mới: Tỉnh/Thành và Phường/Xã/Đặc khu.</small>
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
                        <strong id="checkoutShippingFee">
                            @if($shippingPending)
                                —
                            @elseif($shippingFee > 0)
                                {{ $shippingEstimated ? 'Tạm tính ' : '' }}{{ number_format($shippingFee, 0, ',', '.') }}₫
                            @else
                                Miễn phí
                            @endif
                        </strong>
                    </div>
                    <small id="checkoutShippingNote" class="checkout-shipping-note">{{ $shippingQuote['message'] ?? 'Chọn Tỉnh/Thành để tính phí giao hàng.' }}</small>
                </div>

                <div class="checkout-grand-total">
                    <span>Tổng cộng</span>
                    <strong><small>VND</small> <span id="checkoutGrandTotal">{{ number_format($summary['total'] ?? 0, 0, ',', '.') }}₫</span></strong>
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

    .checkout-shipping-note {
        color: #778270;
        display: block;
        font-size: 12px;
        line-height: 1.4;
        margin-top: -2px;
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
        var provinceSelect = document.getElementById('province_code');
        var wardSelect = document.getElementById('ward_code');
        var addressDataUrl = @json($addressDataUrl);
        var shippingRules = @json($shippingRules);
        var subtotal = Number(@json((int) ($summary['subtotal'] ?? 0)));
        var discountTotal = Number(@json((int) ($summary['discount_total'] ?? 0)));
        var addressData = [];
        var selectedWardCode = wardSelect ? (wardSelect.getAttribute('data-selected') || '') : '';

        if (!provinceSelect || !wardSelect) {
            return;
        }

        var nameInput = document.getElementById('customer_name');
        var phoneInput = document.getElementById('customer_phone');
        var addressLineInput = document.getElementById('address_line');
        var deliveryAreaInput = document.getElementById('delivery_area');
        var shippingFeeOutput = document.getElementById('checkoutShippingFee');
        var shippingNoteOutput = document.getElementById('checkoutShippingNote');
        var grandTotalOutput = document.getElementById('checkoutGrandTotal');

        function formatVnd(value) {
            return new Intl.NumberFormat('vi-VN').format(Math.max(0, Number(value) || 0)) + '₫';
        }

        function selectedWardName() {
            if (!wardSelect || wardSelect.selectedIndex < 0) {
                return '';
            }

            return wardSelect.options[wardSelect.selectedIndex].textContent || '';
        }

        function hasRemoteSurcharge(wardName) {
            var keywords = shippingRules.remote_keywords || [];
            var normalizedWard = (wardName || '').toLocaleLowerCase('vi-VN');

            return keywords.some(function (keyword) {
                return keyword && normalizedWard.indexOf(String(keyword).toLocaleLowerCase('vi-VN')) !== -1;
            });
        }

        function updateShippingQuote() {
            if (!shippingFeeOutput || !grandTotalOutput) {
                return;
            }

            var provinceCode = provinceSelect.value || '';
            var rate = (shippingRules.province_rates || {})[provinceCode];

            if (!provinceCode) {
                shippingFeeOutput.textContent = '—';
                if (shippingNoteOutput) {
                    shippingNoteOutput.textContent = 'Chọn Tỉnh/Thành để tính phí giao hàng.';
                }
                grandTotalOutput.textContent = formatVnd(subtotal - discountTotal);
                return;
            }

            var baseFee = rate ? Number(rate.fee || 0) : Number(shippingRules.default_fee || 0);
            var zoneName = rate ? rate.zone_name : (shippingRules.default_zone_name || 'Khu vực toàn quốc');
            var surcharge = hasRemoteSurcharge(selectedWardName()) ? Number(shippingRules.remote_ward_surcharge || 0) : 0;
            var rawFee = baseFee + surcharge;
            var freeThreshold = Number(shippingRules.free_threshold || 0);
            var isLocalExpress = provinceCode === String(shippingRules.local_express_province_code || '79') && surcharge === 0;
            var isFree = isLocalExpress && freeThreshold > 0 && subtotal >= freeThreshold;
            var finalFee = isFree ? 0 : rawFee;
            var isEstimated = !isLocalExpress;

            shippingFeeOutput.textContent = finalFee > 0 ? (isEstimated ? 'Tạm tính ' : '') + formatVnd(finalFee) : 'Miễn phí';
            grandTotalOutput.textContent = formatVnd(subtotal + finalFee - discountTotal);

            if (shippingNoteOutput) {
                if (isLocalExpress) {
                    shippingNoteOutput.textContent = isFree
                        ? 'Giao nhanh ' + (shippingRules.local_express_eta || '30 - 90 phút') + ' tại TP.HCM. Miễn phí vận chuyển cho đơn từ ' + formatVnd(freeThreshold) + '.'
                        : 'Giao nhanh ' + (shippingRules.local_express_eta || '30 - 90 phút') + ' tại TP.HCM. Phí đã chốt theo khu vực.';
                } else if (surcharge > 0) {
                    shippingNoteOutput.textContent = zoneName + ' + phụ phí khu vực đặc biệt. Shop sẽ xác nhận lại khả năng giao hàng và phí cuối cùng trước khi xử lý.';
                } else {
                    shippingNoteOutput.textContent = zoneName + '. Phí đang là tạm tính cho đơn hàng tỉnh; shop sẽ xác nhận đóng gói, thời gian và phí cuối cùng trước khi giao.';
                }
            }
        }

        function findProvince(provinceCode) {
            return addressData.find(function (province) {
                return String(province.Code) === String(provinceCode);
            });
        }

        function populateWards(provinceCode, wardCode) {
            wardSelect.innerHTML = '<option value="">Chọn Phường/Xã</option>';
            wardSelect.disabled = true;

            var province = findProvince(provinceCode);
            if (!province || !Array.isArray(province.Wards)) {
                return;
            }

            province.Wards.forEach(function (ward) {
                var option = document.createElement('option');
                option.value = ward.Code;
                option.textContent = ward.FullName;
                if (String(ward.Code) === String(wardCode || '')) {
                    option.selected = true;
                }
                wardSelect.appendChild(option);
            });

            wardSelect.disabled = false;
            updateShippingQuote();
        }

        function loadAddressData(callback) {
            fetch(addressDataUrl)
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    addressData = Array.isArray(data) ? data : [];
                    populateWards(provinceSelect.value, selectedWardCode);
                    if (typeof callback === 'function') {
                        callback();
                    }
                })
                .catch(function () {
                    wardSelect.innerHTML = '<option value="">Không tải được dữ liệu Phường/Xã</option>';
                    wardSelect.disabled = true;
                });
        }

        function fillAddress() {
            if (!select) {
                return;
            }

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

            if (addressLineInput) {
                addressLineInput.value = option.getAttribute('data-address-line') || '';
            }

            if (deliveryAreaInput) {
                deliveryAreaInput.value = option.getAttribute('data-delivery-area') || '';
            }

            if (provinceSelect) {
                provinceSelect.value = option.getAttribute('data-province-code') || '';
                selectedWardCode = option.getAttribute('data-ward-code') || '';
                populateWards(provinceSelect.value, selectedWardCode);
                updateShippingQuote();
            }
        }

        provinceSelect.addEventListener('change', function () {
            selectedWardCode = '';
            if (deliveryAreaInput) {
                deliveryAreaInput.value = '';
            }
            populateWards(provinceSelect.value, '');
            updateShippingQuote();
        });

        wardSelect.addEventListener('change', function () {
            if (deliveryAreaInput) {
                deliveryAreaInput.value = '';
            }
            updateShippingQuote();
        });

        if (select) {
            select.addEventListener('change', fillAddress);
        }

        loadAddressData();
        updateShippingQuote();
    })();
</script>
@endpush
