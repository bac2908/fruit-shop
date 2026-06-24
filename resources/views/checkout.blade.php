@extends('layouts.app')

@section('title', 'Checkout - Thế Giới Trái Cây')

@section('content')
@php
    $defaultShippingAddress = $defaultAddress
        ? collect([
            $defaultAddress->address_line,
            $defaultAddress->ward,
            $defaultAddress->district,
            $defaultAddress->province,
        ])->filter()->implode(', ')
        : '';
@endphp
<section class="bread_crumb py-4">
    <div class="container">
        <ul class="breadcrumb">
            <li class="home">
                <a href="{{ route('home') }}">Trang chủ</a>
                <span> <i class="fa fa-angle-right"></i> </span>
            </li>
            <li>
                <a href="{{ route('cart') }}">Giỏ hàng</a>
                <span> <i class="fa fa-angle-right"></i> </span>
            </li>
            <li><strong>Checkout</strong></li>
        </ul>
    </div>
</section>

<section class="checkout-wrap">
    <div class="container">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('checkout.place') }}">
            @csrf
            <div class="row">
                <div class="col-md-7">
                    <div class="checkout-card">
                        <h1>Thông tin nhận hàng</h1>
                        <div class="row">
                            <div class="col-sm-6">
                                <label>Họ và tên *</label>
                                <input type="text" name="customer_name" class="form-control" required value="{{ old('customer_name', optional($defaultAddress)->recipient_name ?: $user->name) }}">
                            </div>
                            <div class="col-sm-6">
                                <label>Số điện thoại *</label>
                                <input type="text" name="customer_phone" class="form-control" required value="{{ old('customer_phone', optional($defaultAddress)->phone ?: $user->phone) }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <label>Email</label>
                                <input type="email" name="customer_email" class="form-control" value="{{ old('customer_email', $user->email) }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <label>Địa chỉ giao hàng *</label>
                                <input type="text" name="shipping_address" class="form-control" required value="{{ old('shipping_address', $defaultShippingAddress) }}">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <label>Ghi chú</label>
                                <textarea name="notes" rows="4" class="form-control" placeholder="Yêu cầu thêm cho đơn hàng">{{ old('notes') }}</textarea>
                            </div>
                        </div>
                        <div class="checkout-options">
                            <label>
                                <input type="checkbox" name="save_address" value="1" {{ old('save_address') ? 'checked' : '' }}>
                                Lưu địa chỉ này vào hồ sơ của tôi
                            </label>
                            <label>
                                <input type="checkbox" name="set_default_address" value="1" {{ old('set_default_address') ? 'checked' : '' }}>
                                Đặt làm địa chỉ mặc định
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="checkout-card">
                        <h2>Đơn hàng của bạn</h2>

                        <div class="checkout-items">
                            @foreach($cartItems as $item)
                                <div class="checkout-item">
                                    <img src="{{ $item['product']->thumb_url }}" alt="{{ $item['product']->name }}">
                                    <div class="meta">
                                        <p class="name">{{ $item['product']->name }}</p>
                                        <p>{{ $item['quantity'] }} x {{ number_format($item['unit_price']) }}₫</p>
                                    </div>
                                    <strong>{{ number_format($item['line_total']) }}₫</strong>
                                </div>
                            @endforeach
                        </div>

                        <ul class="checkout-summary">
                            <li><span>Tạm tính</span><strong>{{ number_format($summary['subtotal'] ?? 0) }}₫</strong></li>
                            <li><span>Giảm giá</span><strong>-{{ number_format($summary['discount_total'] ?? 0) }}₫</strong></li>
                            <li><span>Phí giao hàng</span><strong>{{ number_format($summary['shipping_fee'] ?? 0) }}₫</strong></li>
                            <li class="total"><span>Tổng thanh toán</span><strong>{{ number_format($summary['total'] ?? 0) }}₫</strong></li>
                        </ul>

                        @if(!empty($appliedCoupon))
                            <div class="coupon-note">
                                <strong>{{ $appliedCoupon->code }}</strong>
                                <span>{{ $appliedCoupon->discount_label }} · {{ $appliedCoupon->condition_label }}</span>
                            </div>
                        @endif

                        <button type="submit" class="btn-place-order">Đặt hàng</button>
                        <a href="{{ route('cart') }}" class="btn-back-cart">Quay lại giỏ hàng</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@endsection

@push('styles')
<style>
    .checkout-wrap {
        padding-bottom: 36px;
    }

    .checkout-card {
        background: #fff;
        border: 1px solid #ececec;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 14px;
    }

    .checkout-card h1,
    .checkout-card h2 {
        margin: 0 0 14px;
        font-weight: 700;
        color: #2f2f2f;
    }

    .checkout-card h1 {
        font-size: 28px;
    }

    .checkout-card h2 {
        font-size: 24px;
    }

    .checkout-card label {
        display: block;
        margin-bottom: 6px;
        font-size: 13px;
        color: #666;
        font-weight: 600;
    }

    .checkout-card .form-control {
        margin-bottom: 12px;
        border-radius: 8px;
        min-height: 40px;
    }

    .checkout-items {
        border-top: 1px solid #f0f0f0;
        border-bottom: 1px solid #f0f0f0;
        margin: 0 0 12px;
    }

    .checkout-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid #f7f7f7;
    }

    .checkout-item:last-child {
        border-bottom: 0;
    }

    .checkout-item img {
        width: 56px;
        height: 56px;
        border-radius: 8px;
        object-fit: cover;
        border: 1px solid #ededed;
    }

    .checkout-item .meta {
        flex: 1;
    }

    .checkout-item .meta .name {
        margin: 0 0 2px;
        color: #333;
        font-weight: 600;
    }

    .checkout-item .meta p {
        margin: 0;
        color: #666;
        font-size: 12px;
    }

    .checkout-summary {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .checkout-summary li {
        display: flex;
        justify-content: space-between;
        margin-bottom: 8px;
        color: #555;
    }

    .checkout-summary .total {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px solid #ececec;
    }

    .checkout-summary .total strong {
        color: #f7941e;
        font-size: 22px;
    }

    .coupon-note {
        display: grid;
        gap: 2px;
        margin: 10px 0 12px;
        padding: 10px 12px;
        border: 1px solid #dfead3;
        border-radius: 8px;
        background: #f8fcf2;
        color: #444;
        font-size: 13px;
    }

    .coupon-note strong {
        color: #4f7f15;
        font-size: 14px;
    }

    .checkout-options {
        display: grid;
        gap: 8px;
        margin-top: 2px;
        padding: 10px 12px;
        border: 1px solid #e4ecd9;
        border-radius: 10px;
        background: #fbfef7;
    }

    .checkout-options label {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
        color: #34492b;
        font-size: 13px;
        font-weight: 600;
    }

    .checkout-options input {
        margin: 0;
    }

    .btn-place-order,
    .btn-back-cart {
        width: 100%;
        height: 42px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none !important;
        font-weight: 600;
    }

    .btn-place-order {
        border: 0;
        background: #7fbe3b;
        color: #fff;
    }

    .btn-back-cart {
        margin-top: 8px;
        border: 1px solid #ddd;
        background: #fff;
        color: #555;
    }
</style>
@endpush
