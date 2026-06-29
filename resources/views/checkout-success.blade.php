@extends('layouts.app')

@section('title', 'Đặt hàng thành công - Thế Giới Trái Cây')

@section('content')
@php
    $orderItems = collect($order->items ?? []);
    $totalQuantity = $orderItems->sum('qty');
    $statusText = $order->status_label;
    $bankTransfer = config('shop.bank_transfer', []);
    $isBankTransfer = $order->payment_method === \App\Models\Order::PAYMENT_METHOD_BANK_TRANSFER;
    $isMomo = $order->payment_method === \App\Models\Order::PAYMENT_METHOD_MOMO;
    $orderTimeline = $order->trackingTimeline();
    $latestCancellationRequest = $order->latest_cancellation_request;
    $cancellationReasons = \App\Models\OrderCancellationRequest::reasonLabels();
    $statusDescriptions = [
        \App\Models\Order::STATUS_PENDING => 'Shop đang kiểm tra tồn kho, địa chỉ giao hàng và thanh toán trước khi xử lý đơn.',
        \App\Models\Order::STATUS_CONFIRMED => 'Đơn đã được hệ thống xác nhận vì sản phẩm còn hàng và tồn kho đã được giữ cho bạn.',
        \App\Models\Order::STATUS_SHIPPING => 'Đơn đã rời shop và đang được giao tới địa chỉ bạn đã cung cấp.',
        \App\Models\Order::STATUS_DONE => 'Đơn đã hoàn tất. Cảm ơn bạn đã mua hàng tại Thế Giới Trái Cây.',
        \App\Models\Order::STATUS_CANCELLED => 'Đơn đã được hủy. Nếu cần hỗ trợ thêm, bạn có thể liên hệ shop bằng mã đơn này.',
    ];
    $statusDescription = $statusDescriptions[$order->status] ?? 'Shop đang xử lý đơn hàng của bạn.';
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
                    <p>Mã đơn hàng của bạn là <strong>{{ $order->code }}</strong>. {{ $statusDescription }}</p>
                </div>
            </div>

            <div class="order-meta">
                <p><span>Khách hàng:</span> {{ $order->customer_name }}</p>
                <p><span>Điện thoại:</span> {{ $order->customer_phone ?: 'Chưa cung cấp' }}</p>
                <p><span>Email:</span> {{ $order->customer_email ?: 'Chưa cung cấp' }}</p>
                <p><span>Địa chỉ:</span> {{ $order->shipping_address }}</p>
                <p><span>Giao hàng:</span> {{ $order->delivery_method_label }}{{ $order->shipping_delivery_eta ? ' · ' . $order->shipping_delivery_eta : '' }}</p>
                <p><span>Phí ship:</span> {{ $order->shipping_fee_status_label }}</p>
                <p><span>Số sản phẩm:</span> {{ $totalQuantity }} sản phẩm</p>
                <p><span>Thanh toán:</span> {{ $order->payment_method_label }}</p>
                <p><span>Trạng thái thanh toán:</span> {{ $order->payment_status_label }}</p>
            </div>

            <div class="order-tracking-card">
                <div class="section-heading">
                    <h2>Theo dõi đơn hàng</h2>
                    <span>{{ $order->status_label }}</span>
                </div>
                <ol class="order-timeline">
                    @foreach($orderTimeline as $step)
                        <li class="{{ $step['done'] ? 'is-done' : '' }} {{ $step['current'] ? 'is-current' : '' }}">
                            <span class="timeline-dot"></span>
                            <div>
                                <strong>{{ $step['label'] }}</strong>
                                <p>{{ $step['description'] }}</p>
                                @if($step['time'])
                                    <small>{{ $step['time']->format('d/m/Y H:i') }}</small>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            @if($latestCancellationRequest)
                <div class="cancellation-status-card">
                    <strong>Yêu cầu hủy: {{ $latestCancellationRequest->status_label }}</strong>
                    <p>Lý do: {{ $latestCancellationRequest->reason_label }}</p>
                    @if($latestCancellationRequest->note)
                        <p>Ghi chú của bạn: {{ $latestCancellationRequest->note }}</p>
                    @endif
                    @if($latestCancellationRequest->admin_note)
                        <p>Phản hồi từ shop: {{ $latestCancellationRequest->admin_note }}</p>
                    @endif
                </div>
            @endif

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
                    <strong>{{ $order->shipping_fee_status === \App\Models\Order::SHIPPING_FEE_STATUS_ESTIMATED ? 'Tạm tính ' : '' }}{{ (int) $order->shipping_fee > 0 ? number_format($order->shipping_fee) . '₫' : 'Miễn phí' }}</strong>
                </div>
                <div class="grand-total">
                    <span>Tổng thanh toán</span>
                    <strong>{{ number_format($order->total) }}₫</strong>
                </div>
            </div>

            <div class="payment-instruction-card {{ $isBankTransfer ? 'is-bank' : 'is-cod' }}">
                @if($order->shipping_delivery_note)
                    <div class="fresh-shipping-note {{ $order->requiresShippingConfirmation() ? 'is-estimated' : 'is-confirmed' }}">
                        <i class="fa fa-leaf"></i>
                        <div>
                            <strong>{{ $order->requiresShippingConfirmation() ? 'Đơn hàng tươi cần shop xác nhận' : 'Giao nhanh hàng tươi' }}</strong>
                            <p>{{ $order->shipping_delivery_note }}</p>
                        </div>
                    </div>
                @endif

                @if($isBankTransfer)
                    <div class="section-heading">
                        <h2>Thông tin chuyển khoản</h2>
                        <span>Chờ xác nhận thanh toán</span>
                    </div>
                    <div class="payment-instruction-body">
                        <p class="payment-note">Vui lòng chuyển khoản đúng số tiền và ghi đúng nội dung để cửa hàng đối soát nhanh hơn.</p>
                        <div class="bank-info-grid">
                            <div>
                                <span>Ngân hàng</span>
                                <strong>{{ $bankTransfer['bank_name'] ?? 'Vietcombank' }}</strong>
                            </div>
                            <div>
                                <span>Chủ tài khoản</span>
                                <strong>{{ $bankTransfer['account_name'] ?? 'THE GIOI TRAI CAY' }}</strong>
                            </div>
                            <div>
                                <span>Số tài khoản</span>
                                <strong>{{ $bankTransfer['account_number'] ?? '0123456789' }}</strong>
                            </div>
                            <div>
                                <span>Số tiền</span>
                                <strong>{{ number_format($order->total) }}₫</strong>
                            </div>
                            <div class="bank-info-wide">
                                <span>Nội dung chuyển khoản</span>
                                <strong>{{ $order->code }}</strong>
                            </div>
                            @if(!empty($bankTransfer['branch']))
                                <div class="bank-info-wide">
                                    <span>Chi nhánh</span>
                                    <strong>{{ $bankTransfer['branch'] }}</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                @elseif($isMomo)
                    <div class="payment-cod-box momo-status-box">
                        <i class="fa fa-mobile"></i>
                        <div>
                            <h2>Thanh toán MoMo sandbox</h2>
                            @if($order->payment_status === \App\Models\Order::PAYMENT_STATUS_PAID)
                                <p>MoMo đã xác nhận thanh toán thành công cho đơn hàng này. Cửa hàng sẽ xử lý và giao hàng theo thông tin bạn đã cung cấp.</p>
                            @else
                                <p>Đơn hàng đã được tạo nhưng MoMo chưa xác nhận thanh toán. Nếu bạn đã thanh toán, vui lòng chờ hệ thống đối soát hoặc liên hệ hotline kèm mã đơn <strong>{{ $order->code }}</strong>.</p>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="payment-cod-box">
                        <i class="fa fa-truck"></i>
                        <div>
                            <h2>Thanh toán khi nhận hàng</h2>
                            <p>Bạn sẽ thanh toán <strong>{{ number_format($order->total) }}₫</strong> cho nhân viên giao hàng sau khi đơn được xác nhận và giao tới địa chỉ đã đăng ký.</p>
                        </div>
                    </div>
                @endif
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
                @if(auth()->check() && (int) auth()->id() === (int) $order->user_id)
                    @if($order->isCustomerCancellable() || $order->isCustomerCancellationRequestable())
                        <details class="success-cancel-details">
                            <summary>{{ $order->isCustomerCancellable() ? 'Hủy đơn hàng' : 'Yêu cầu hủy đơn' }}</summary>
                            <form method="post" action="{{ route('account.orders.cancel', $order) }}" class="success-cancel-form" onsubmit="return confirm('Bạn chắc chắn muốn gửi hủy đơn {{ $order->code }}?');">
                                @csrf
                                @method('PATCH')
                                <select name="reason" required>
                                    <option value="">Chọn lý do</option>
                                    @foreach($cancellationReasons as $reasonValue => $reasonLabel)
                                        <option value="{{ $reasonValue }}">{{ $reasonLabel }}</option>
                                    @endforeach
                                </select>
                                <textarea name="note" rows="2" maxlength="500" placeholder="Ghi chú thêm nếu cần"></textarea>
                                <button type="submit">{{ $order->isCustomerCancellable() ? 'Xác nhận hủy' : 'Gửi yêu cầu' }}</button>
                            </form>
                        </details>
                    @elseif($order->hasPendingCancellationRequest())
                        <span class="success-cancel-pending">Đang chờ shop duyệt hủy</span>
                    @endif
                @endif
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
    .order-tracking-card,
    .cancellation-status-card,
    .order-total-card,
    .payment-instruction-card,
    .support-box {
        max-width: 680px;
        margin: 0 auto 16px;
        text-align: left;
    }

    .order-tracking-card {
        border: 1px solid #dcebd1;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }

    .order-timeline {
        list-style: none;
        margin: 0;
        padding: 14px 16px 16px;
    }

    .order-timeline li {
        display: grid;
        grid-template-columns: 18px minmax(0, 1fr);
        gap: 11px;
        padding: 0 0 16px;
        position: relative;
    }

    .order-timeline li:last-child {
        padding-bottom: 0;
    }

    .order-timeline li::before {
        background: #e3ebdc;
        content: '';
        height: 100%;
        left: 8px;
        position: absolute;
        top: 18px;
        width: 2px;
    }

    .order-timeline li:last-child::before {
        display: none;
    }

    .timeline-dot {
        background: #fff;
        border: 2px solid #d0dcc8;
        border-radius: 50%;
        height: 18px;
        position: relative;
        width: 18px;
        z-index: 1;
    }

    .order-timeline li.is-done .timeline-dot {
        background: #73b62b;
        border-color: #73b62b;
        box-shadow: inset 0 0 0 4px #fff;
    }

    .order-timeline li.is-current .timeline-dot {
        background: #f7941e;
        border-color: #f7941e;
    }

    .order-timeline strong {
        color: #24361d;
        display: block;
        font-size: 14px;
        margin-bottom: 3px;
    }

    .order-timeline p {
        color: #65715f;
        font-size: 13px;
        line-height: 1.45;
        margin: 0;
    }

    .order-timeline small {
        color: #8b9485;
        display: block;
        font-size: 12px;
        margin-top: 4px;
    }

    .cancellation-status-card {
        background: #fffaf0;
        border: 1px solid #f3d59b;
        border-radius: 12px;
        padding: 13px 14px;
    }

    .cancellation-status-card strong {
        color: #8a6200;
        display: block;
        margin-bottom: 5px;
    }

    .cancellation-status-card p {
        color: #685d45;
        font-size: 13px;
        margin: 4px 0 0;
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

    .payment-instruction-card {
        border: 1px solid #dcebd1;
        border-radius: 12px;
        background: #fff;
        overflow: hidden;
    }

    .fresh-shipping-note {
        align-items: flex-start;
        background: #f5fbef;
        border-bottom: 1px solid #dcebd1;
        display: grid;
        gap: 12px;
        grid-template-columns: 38px minmax(0, 1fr);
        padding: 13px 14px;
    }

    .fresh-shipping-note.is-estimated {
        background: #fffaf0;
        border-bottom-color: #f3d59b;
    }

    .fresh-shipping-note i {
        align-items: center;
        background: #e8f5db;
        border-radius: 50%;
        color: #5f922b;
        display: flex;
        height: 38px;
        justify-content: center;
        width: 38px;
    }

    .fresh-shipping-note.is-estimated i {
        background: #fff0d8;
        color: #a66a00;
    }

    .fresh-shipping-note strong {
        color: #26351f;
        display: block;
        font-size: 14px;
        margin-bottom: 4px;
    }

    .fresh-shipping-note p {
        color: #5d6659;
        font-size: 13px;
        line-height: 1.45;
        margin: 0;
    }

    .payment-instruction-card.is-bank .section-heading {
        background: #f5fbef;
    }

    .payment-instruction-body {
        padding: 14px;
    }

    .payment-note {
        color: #5d6659;
        font-size: 13px;
        line-height: 1.55;
        margin: 0 0 12px;
    }

    .bank-info-grid {
        display: grid;
        gap: 10px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .bank-info-grid > div {
        border: 1px solid #e5eadf;
        border-radius: 9px;
        background: #fbfdf9;
        padding: 10px 12px;
    }

    .bank-info-grid span,
    .bank-info-grid strong {
        display: block;
    }

    .bank-info-grid span {
        color: #75806f;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .bank-info-grid strong {
        color: #1f2e1d;
        font-size: 15px;
        line-height: 1.35;
        word-break: break-word;
    }

    .bank-info-grid .bank-info-wide {
        grid-column: 1 / -1;
    }

    .bank-info-wide strong {
        color: #5f922b;
        font-size: 18px;
        letter-spacing: .5px;
    }

    .payment-cod-box {
        align-items: center;
        display: grid;
        gap: 14px;
        grid-template-columns: 48px minmax(0, 1fr);
        padding: 16px;
    }

    .payment-cod-box i {
        align-items: center;
        background: #edf8e2;
        border-radius: 50%;
        color: #6bab22;
        display: flex;
        font-size: 22px;
        height: 48px;
        justify-content: center;
        width: 48px;
    }

    .momo-status-box i {
        background: #fff0f8;
        color: #b0006d;
    }

    .payment-cod-box h2 {
        color: #2f2f2f;
        font-size: 18px;
        margin: 0 0 5px;
    }

    .payment-cod-box p {
        color: #5d6659;
        font-size: 13px;
        line-height: 1.55;
        margin: 0;
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

    .success-cancel-form {
        margin: 0;
    }

    .success-cancel-form button {
        background: #fff3f0;
        border: 1px solid #ffd0c7;
        border-radius: 999px;
        color: #b73f30;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        height: 40px;
        padding: 0 18px;
    }

    .success-cancel-form button:hover {
        background: #ffe8e3;
    }

    .success-cancel-details {
        position: relative;
    }

    .success-cancel-details summary {
        align-items: center;
        background: #fff3f0;
        border: 1px solid #ffd0c7;
        border-radius: 999px;
        color: #b73f30;
        cursor: pointer;
        display: inline-flex;
        font-weight: 700;
        height: 40px;
        padding: 0 18px;
    }

    .success-cancel-details summary::-webkit-details-marker {
        display: none;
    }

    .success-cancel-details .success-cancel-form {
        background: #fffaf8;
        border: 1px solid #ffd0c7;
        border-radius: 12px;
        box-shadow: 0 16px 32px rgba(80, 33, 20, 0.14);
        display: grid;
        gap: 9px;
        margin-top: 9px;
        min-width: 280px;
        padding: 12px;
        position: absolute;
        right: 0;
        top: 100%;
        z-index: 6;
    }

    .success-cancel-form select,
    .success-cancel-form textarea {
        border: 1px solid #e8c7bd;
        border-radius: 8px;
        color: #2a3523;
        font: inherit;
        font-size: 13px;
        outline: none;
        padding: 9px 10px;
        width: 100%;
    }

    .success-cancel-form textarea {
        min-height: 66px;
        resize: vertical;
    }

    .success-cancel-details .success-cancel-form button {
        width: 100%;
    }

    .success-cancel-pending {
        align-items: center;
        background: #fff8e3;
        border: 1px solid #f2d58a;
        border-radius: 999px;
        color: #8a6500;
        display: inline-flex;
        font-weight: 700;
        height: 40px;
        padding: 0 18px;
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

        .bank-info-grid {
            grid-template-columns: 1fr;
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

        .payment-cod-box {
            align-items: flex-start;
        }

        .support-actions,
        .support-actions a,
        .btn-go-shop,
        .btn-go-home,
        .success-cancel-details,
        .success-cancel-details summary,
        .success-cancel-pending,
        .success-cancel-form,
        .success-cancel-form button {
            width: 100%;
        }

        .success-cancel-details summary,
        .success-cancel-pending {
            justify-content: center;
        }

        .success-cancel-details .success-cancel-form {
            min-width: 0;
            position: static;
            width: 100%;
        }
    }
</style>
@endpush
