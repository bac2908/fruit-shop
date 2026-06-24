@extends('layouts.app')

@section('title', 'Đặt hàng thành công - Thế Giới Trái Cây')

@section('content')
@php
    $orderItems = collect($order->items ?? []);
    $totalQuantity = $orderItems->sum('qty');
    $statusLabels = [
        'pending' => 'Đang chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao hàng',
        'done' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
    ];
    $statusText = $statusLabels[$order->status] ?? 'Đang chờ xác nhận';
@endphp

<section class="bread_crumb py-4">
    <div class="container">
        <ul class="breadcrumb">
            <li class="home">
                <a href="{{ route('home') }}">Trang chủ</a>
                <span> <i class="fa fa-angle-right"></i> </span>
            </li>
            <li><strong>Đặt hàng thành công</strong></li>
        </ul>
    </div>
</section>

<section class="checkout-success-wrap">
    <div class="container">
        <div class="success-card">
            <div class="success-hero">
                <div class="icon"><i class="fa fa-check-circle"></i></div>
                <div>
                    <span class="status-pill">{{ $statusText }}</span>
                    <h1>Cảm ơn bạn đã đặt hàng</h1>
                    <p>Mã đơn hàng của bạn là <strong>{{ $order->code }}</strong>. Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.</p>
                </div>
            </div>

            <div class="order-meta">
                <p><span>Khách hàng:</span> {{ $order->customer_name }}</p>
                <p><span>Điện thoại:</span> {{ $order->customer_phone ?: 'Chưa cung cấp' }}</p>
                <p><span>Email:</span> {{ $order->customer_email ?: 'Chưa cung cấp' }}</p>
                <p><span>Địa chỉ:</span> {{ $order->shipping_address }}</p>
                <p><span>Số sản phẩm:</span> {{ $totalQuantity }} sản phẩm</p>
            </div>

            @if($orderItems->isNotEmpty())
                <div class="order-items-card">
                    <div class="section-heading">
                        <h2>Sản phẩm đã đặt</h2>
                        <span>{{ $orderItems->count() }} dòng hàng</span>
                    </div>

                    <div class="order-items-list">
                        @foreach($orderItems as $item)
                            @php
                                $itemImage = optional($item->product)->thumb_url ?: '//theme.hstatic.net/200000157781/1001036201/14/no-image.jpg?v=1064';
                            @endphp
                            <div class="order-item">
                                <img src="{{ $itemImage }}" alt="{{ $item->product_name }}">
                                <div class="order-item-info">
                                    <h3>{{ $item->product_name }}</h3>
                                    <p>{{ $item->qty }} x {{ number_format($item->unit_price) }}₫{{ $item->unit ? ' / ' . $item->unit : '' }}</p>
                                </div>
                                <strong>{{ number_format($item->line_total) }}₫</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="order-total-card">
                <div>
                    <span>Tạm tính</span>
                    <strong>{{ number_format($order->subtotal) }}₫</strong>
                </div>
                @if((int) $order->discount_total > 0 || $order->coupon_code)
                    <div>
                        <span>Voucher{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}</span>
                        <strong>{{ (int) $order->discount_total > 0 ? '-' . number_format($order->discount_total) . '₫' : 'Đã áp dụng' }}</strong>
                    </div>
                @endif
                <div>
                    <span>Phí giao hàng</span>
                    <strong>{{ number_format($order->shipping_fee) }}₫</strong>
                </div>
                <div class="grand-total">
                    <span>Tổng thanh toán</span>
                    <strong>{{ number_format($order->total) }}₫</strong>
                </div>
            </div>

            <div class="support-box">
                <div>
                    <strong>Cần hỗ trợ đơn hàng?</strong>
                    <p>Gọi hotline hoặc nhắn Zalo và cung cấp mã đơn <b>{{ $order->code }}</b>.</p>
                </div>
                <div class="support-actions">
                    <a href="tel:0333499426"><i class="fa fa-phone"></i> Gọi hotline</a>
                    <a href="https://zalo.me/0333499426" target="_blank" rel="noopener noreferrer">Zalo</a>
                </div>
            </div>

            <div class="action-row">
                <a href="{{ route('products.index') }}" class="btn-go-shop">Tiếp tục mua sắm</a>
                <a href="{{ route('home') }}" class="btn-go-home">Về trang chủ</a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    .checkout-success-wrap {
        padding-bottom: 36px;
    }

    .success-card {
        max-width: 860px;
        margin: 0 auto;
        background: #fff;
        border: 1px solid #ececec;
        border-radius: 14px;
        padding: 28px 22px;
        box-shadow: 0 14px 30px rgba(23, 44, 30, 0.08);
    }

    .success-hero {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        text-align: left;
        margin-bottom: 18px;
    }

    .success-card .icon {
        font-size: 56px;
        color: #7fbe3b;
        line-height: 1;
    }

    .success-card h1 {
        margin: 0 0 10px;
        color: #2f2f2f;
        font-size: 32px;
        font-weight: 700;
    }

    .success-hero p {
        color: #555;
        font-size: 15px;
        margin: 0;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        height: 28px;
        border-radius: 999px;
        background: #ecf7df;
        color: #5f922b;
        padding: 0 12px;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .order-meta {
        text-align: left;
        margin: 0 auto 16px;
        max-width: 680px;
        border: 1px dashed #e4e4e4;
        border-radius: 10px;
        padding: 14px;
        background: #fafafa;
    }

    .order-meta p {
        margin: 0 0 6px;
        color: #444;
    }

    .order-meta p:last-child {
        margin-bottom: 0;
    }

    .order-meta span {
        display: inline-block;
        min-width: 110px;
        color: #777;
    }

    .order-items-card,
    .order-total-card,
    .support-box {
        max-width: 680px;
        margin: 0 auto 16px;
        text-align: left;
    }

    .order-items-card {
        border: 1px solid #ececec;
        border-radius: 12px;
        overflow: hidden;
    }

    .section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 12px 14px;
        background: #f7fbf2;
        border-bottom: 1px solid #ececec;
    }

    .section-heading h2 {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #2f2f2f;
    }

    .section-heading span {
        color: #777;
        font-size: 13px;
    }

    .order-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-bottom: 1px solid #f1f1f1;
    }

    .order-item:last-child {
        border-bottom: 0;
    }

    .order-item img {
        width: 64px;
        height: 64px;
        border-radius: 10px;
        object-fit: cover;
        border: 1px solid #ededed;
        flex: 0 0 64px;
    }

    .order-item-info {
        flex: 1;
        min-width: 0;
    }

    .order-item-info h3 {
        margin: 0 0 4px;
        color: #2f2f2f;
        font-size: 15px;
        font-weight: 700;
        line-height: 1.4;
    }

    .order-item-info p {
        margin: 0;
        color: #666;
        font-size: 13px;
    }

    .order-item > strong {
        color: #333;
        white-space: nowrap;
    }

    .order-total-card {
        border: 1px solid #ececec;
        border-radius: 12px;
        padding: 14px;
        background: #fff;
    }

    .order-total-card > div {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 9px;
        color: #555;
    }

    .order-total-card > div:last-child {
        margin-bottom: 0;
    }

    .order-total-card .grand-total {
        padding-top: 10px;
        margin-top: 10px;
        border-top: 1px solid #ececec;
        color: #222;
    }

    .order-total-card .grand-total strong {
        color: #f7941e;
        font-size: 22px;
    }

    .support-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        border: 1px solid #dcebd1;
        border-radius: 12px;
        background: #f8fcf4;
        padding: 14px;
    }

    .support-box strong {
        color: #2f2f2f;
        font-size: 15px;
    }

    .support-box p {
        margin: 4px 0 0;
        color: #666;
        font-size: 13px;
    }

    .support-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-shrink: 0;
    }

    .support-actions a {
        height: 38px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 0 14px;
        background: #7fbe3b;
        color: #fff !important;
        text-decoration: none !important;
        font-weight: 700;
        font-size: 13px;
    }

    .support-actions a:last-child {
        background: #1f8fff;
    }

    .action-row {
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .btn-go-shop,
    .btn-go-home {
        height: 40px;
        padding: 0 18px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none !important;
        font-weight: 600;
    }

    .btn-go-shop {
        background: #7fbe3b;
        color: #fff;
    }

    .btn-go-home {
        border: 1px solid #ddd;
        color: #555;
        background: #fff;
    }

    @media (max-width: 767px) {
        .success-card {
            padding: 18px 14px;
        }

        .success-hero {
            display: block;
            text-align: center;
        }

        .success-card .icon {
            margin-bottom: 8px;
        }

        .success-card h1 {
            font-size: 26px;
        }

        .order-meta span {
            display: block;
            min-width: 0;
            margin-bottom: 2px;
        }

        .section-heading,
        .order-item,
        .support-box {
            align-items: flex-start;
        }

        .order-item {
            gap: 10px;
        }

        .order-item > strong {
            align-self: flex-start;
            font-size: 13px;
        }

        .support-box,
        .support-actions {
            flex-direction: column;
        }

        .support-actions,
        .support-actions a,
        .btn-go-shop,
        .btn-go-home {
            width: 100%;
        }
    }
</style>
@endpush
