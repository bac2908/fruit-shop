@extends('layouts.app')

@section('title', 'Giỏ hàng - Thế Giới Trái Cây')

@section('content')
@php
    $shippingQuote = $summary['shipping_quote'] ?? [];
    $shippingPending = (bool) ($shippingQuote['is_pending'] ?? true);
    $shippingFee = (int) ($summary['shipping_fee'] ?? 0);
@endphp
<section class="bread_crumb py-4">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <ul class="breadcrumb" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
                    <li class="home">
                        <a itemprop="url" href="{{ route('home') }}"><span itemprop="title">Trang chủ</span></a>
                        <span> <i class="fa fa-angle-right"></i> </span>
                    </li>
                    <li><strong><span itemprop="title">Giỏ hàng</span></strong></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="cart-page-wrap">
    <div class="container">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <div class="cart-card">
                    <h1>Giỏ hàng của bạn</h1>
                    <p class="cart-sub">Tổng số lượng sản phẩm: <strong>{{ $totalQuantity ?? 0 }}</strong></p>

                    @if(($cartItems ?? collect())->isEmpty())
                        <div class="cart-empty">
                            <p>Giỏ hàng đang trống. Hãy chọn thêm sản phẩm bạn yêu thích.</p>
                            <a href="{{ route('products.index') }}" class="btn-back-products">Tiếp tục mua sắm</a>
                        </div>
                    @else
                        <div class="cart-table-wrap">
                            <table class="cart-table">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th class="text-center">Số lượng</th>
                                        <th class="text-right">Thành tiền</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($cartItems as $item)
                                        @php
                                            $product = $item['product'];
                                        @endphp
                                        <tr>
                                            <td data-label="Sản phẩm">
                                                <div class="cart-product-cell">
                                                    <a href="{{ route('products.show', $product->slug) }}" class="thumb">
                                                        <img src="{{ $product->thumb_url }}" alt="{{ $product->name }}">
                                                    </a>
                                                    <div class="meta">
                                                        <a href="{{ route('products.show', $product->slug) }}" class="name">{{ $product->name }}</a>
                                                        <p>Đơn giá: {{ number_format($item['unit_price']) }}₫</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center" data-label="Số lượng">
                                                <form method="post" action="{{ route('cart.update') }}" class="qty-form">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                    <input type="number" name="quantity" min="1" max="{{ min(99, (int) $product->stock) }}" value="{{ $item['quantity'] }}">
                                                    <button type="submit">Cập nhật</button>
                                                </form>
                                            </td>
                                            <td class="text-right line-total" data-label="Thành tiền">{{ number_format($item['line_total']) }}₫</td>
                                            <td class="text-right cart-remove-cell" data-label="Thao tác">
                                                <form method="post" action="{{ route('cart.remove') }}">
                                                    @csrf
                                                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                                                    <button type="submit" class="btn-remove" title="Xóa sản phẩm">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-md-4">
                <div class="summary-card">
                    <h2>Tóm tắt đơn hàng</h2>

                    <div class="coupon-box">
                        <form method="post" action="{{ route('cart.coupon.apply') }}" class="coupon-form">
                            @csrf
                            <input type="text" name="code" placeholder="Nhập mã voucher" value="{{ old('code') }}">
                            <button type="submit">Áp dụng</button>
                        </form>

                        @if(!empty($appliedCoupon))
                            <div class="coupon-applied">
                                <div>
                                    <p>Đã áp dụng: <strong>{{ $appliedCoupon->code }}</strong></p>
                                    <small>{{ $appliedCoupon->discount_label }} · {{ $appliedCoupon->condition_label }}</small>
                                </div>
                                <form method="post" action="{{ route('cart.coupon.remove') }}">
                                    @csrf
                                    <button type="submit">Bỏ mã</button>
                                </form>
                            </div>
                        @endif
                    </div>

                    <ul class="summary-list">
                        <li>
                            <span>Tạm tính</span>
                            <strong>{{ number_format($summary['subtotal'] ?? 0) }}₫</strong>
                        </li>
                        <li>
                            <span>Giảm giá</span>
                            <strong>-{{ number_format($summary['discount_total'] ?? 0) }}₫</strong>
                        </li>
                        <li>
                            <span>Phí giao hàng</span>
                            <strong>
                                @if($shippingPending)
                                    Tính ở bước giao hàng
                                @elseif($shippingFee > 0)
                                    {{ number_format($shippingFee) }}₫
                                @else
                                    Miễn phí
                                @endif
                            </strong>
                        </li>
                        @if(!empty($shippingQuote['message']))
                            <li class="summary-note">
                                <span>{{ $shippingQuote['message'] }}</span>
                            </li>
                        @endif
                        <li class="total">
                            <span>Tổng cộng</span>
                            <strong>{{ number_format($summary['total'] ?? 0) }}₫</strong>
                        </li>
                    </ul>

                    <a href="{{ route('checkout') }}" class="btn-checkout {{ ($cartItems ?? collect())->isEmpty() ? 'is-disabled' : '' }}">Tiến hành checkout</a>
                    <a href="{{ route('products.index') }}" class="btn-continue">Tiếp tục mua sắm</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    .cart-page-wrap {
        padding-bottom: 36px;
    }

    .cart-card,
    .summary-card {
        background: #fff;
        border: 1px solid #ececec;
        border-radius: 12px;
        padding: 20px;
    }

    .cart-card h1 {
        margin: 0 0 8px;
        font-size: 28px;
        color: #2f2f2f;
        font-weight: 700;
    }

    .cart-sub {
        margin: 0 0 16px;
        color: #666;
    }

    .cart-empty {
        border: 1px dashed #dedede;
        border-radius: 10px;
        padding: 18px;
        text-align: center;
    }

    .btn-back-products {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        height: 38px;
        padding: 0 16px;
        border-radius: 999px;
        background: #7fbe3b;
        color: #fff;
        text-decoration: none;
    }

    .cart-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .cart-table th:nth-child(1),
    .cart-table td:nth-child(1) {
        width: 52%;
    }

    .cart-table th:nth-child(2),
    .cart-table td:nth-child(2) {
        width: 28%;
    }

    .cart-table th:nth-child(3),
    .cart-table td:nth-child(3) {
        width: 14%;
    }

    .cart-table th:nth-child(4),
    .cart-table td:nth-child(4) {
        width: 6%;
    }

    .cart-table thead th {
        border-bottom: 1px solid #ececec;
        font-size: 13px;
        color: #666;
        font-weight: 600;
        padding: 10px 0;
    }

    .cart-table tbody td {
        border-bottom: 1px solid #f2f2f2;
        padding: 12px 0;
        vertical-align: top;
    }

    .cart-product-cell {
        display: flex;
        gap: 10px;
    }

    .cart-product-cell .thumb {
        width: 78px;
        height: 78px;
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        background: #fff;
        flex-shrink: 0;
    }

    .cart-product-cell .thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cart-product-cell .meta .name {
        color: #222;
        text-decoration: none;
        font-weight: 600;
        display: block;
        margin-bottom: 4px;
    }

    .cart-product-cell .meta p {
        margin: 0;
        color: #666;
        font-size: 13px;
    }

    .qty-form {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-width: 178px;
    }

    .qty-form input {
        width: 76px;
        text-align: center;
        height: 40px;
        border: 1px solid #ddd;
        border-radius: 8px;
        color: #24331f;
        font-size: 16px;
        font-weight: 700;
        line-height: 40px;
        padding: 0 8px;
        -moz-appearance: textfield;
    }

    .qty-form input::-webkit-outer-spin-button,
    .qty-form input::-webkit-inner-spin-button {
        margin: 0;
        -webkit-appearance: none;
    }

    .qty-form button {
        height: 40px;
        border: 0;
        border-radius: 8px;
        padding: 0 14px;
        background: #7fbe3b;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        white-space: nowrap;
    }

    .line-total {
        font-weight: 700;
        color: #333;
    }

    .btn-remove {
        width: 34px;
        height: 34px;
        border: 1px solid #f0d4d4;
        border-radius: 50%;
        background: #fff5f5;
        color: #dd4b39;
    }

    .summary-card h2 {
        margin: 0 0 14px;
        font-size: 22px;
        font-weight: 700;
    }

    .coupon-box {
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px dashed #e5e5e5;
    }

    .coupon-form {
        display: flex;
        gap: 8px;
    }

    .coupon-form input {
        flex: 1;
        height: 38px;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 0 10px;
    }

    .coupon-form button,
    .coupon-applied button {
        border: 0;
        border-radius: 8px;
        background: #f7941e;
        color: #fff;
        padding: 0 12px;
        height: 38px;
    }

    .coupon-applied {
        margin-top: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
        padding: 10px 12px;
        border: 1px solid #dfead3;
        border-radius: 8px;
        background: #f8fcf2;
    }

    .coupon-applied p {
        margin: 0;
        color: #333;
        font-size: 13px;
    }

    .coupon-applied small {
        display: block;
        margin-top: 2px;
        color: #66715f;
        line-height: 1.35;
    }

    .summary-list {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .summary-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        color: #555;
    }

    .summary-list li.total {
        border-top: 1px solid #ececec;
        padding-top: 10px;
        margin-top: 10px;
    }

    .summary-list li.total strong {
        color: #f7941e;
        font-size: 20px;
    }

    .summary-list li.summary-note {
        display: block;
        color: #7a8674;
        font-size: 12px;
        line-height: 1.4;
        margin-top: -4px;
    }

    .btn-checkout,
    .btn-continue {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        height: 42px;
        border-radius: 999px;
        text-decoration: none !important;
        font-size: 14px;
        font-weight: 600;
    }

    .btn-checkout {
        margin-top: 6px;
        background: #7fbe3b;
        color: #fff;
    }

    .btn-checkout.is-disabled {
        pointer-events: none;
        opacity: .5;
    }

    .btn-continue {
        margin-top: 8px;
        border: 1px solid #ddd;
        background: #fff;
        color: #555;
    }

    @media (max-width: 991px) {
        .summary-card {
            margin-top: 14px;
        }
    }

    @media (max-width: 767px) {
        .cart-card,
        .summary-card {
            padding: 14px;
            border-radius: 10px;
        }

        .cart-card h1 {
            font-size: 24px;
        }

        .cart-table,
        .cart-table tbody,
        .cart-table tr,
        .cart-table td {
            display: block;
            width: 100%;
        }

        .cart-table thead {
            display: none;
        }

        .cart-table tbody tr {
            border: 1px solid #ededed;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 12px;
            background: #fff;
            box-shadow: 0 6px 16px rgba(23, 44, 30, 0.06);
        }

        .cart-table tbody td {
            border-bottom: 0;
            padding: 8px 0;
            text-align: left !important;
        }

        .cart-table tbody td:not(:first-child) {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .cart-table tbody td:not(:first-child)::before {
            content: attr(data-label);
            color: #666;
            font-size: 13px;
            font-weight: 600;
        }

        .cart-product-cell {
            gap: 12px;
        }

        .cart-product-cell .thumb {
            width: 72px;
            height: 72px;
        }

        .cart-product-cell .meta .name {
            line-height: 1.4;
        }

        .qty-form {
            justify-content: flex-end;
            flex-wrap: wrap;
        }

        .line-total {
            font-size: 16px;
        }

        .cart-remove-cell form {
            margin-left: auto;
        }

        .coupon-form {
            flex-direction: column;
        }

        .coupon-form input,
        .coupon-form button {
            width: 100%;
        }

        .coupon-applied {
            align-items: flex-start;
            flex-direction: column;
        }
    }
</style>
@endpush
