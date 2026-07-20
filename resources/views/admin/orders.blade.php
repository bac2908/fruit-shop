@extends('layouts.admin')

@section('title', 'Quản lý đơn hàng | FruitShop Admin')

@include('admin.orders._styles')

@section('admin_content')
    @php
        $hasFilters = collect(['q', 'status', 'payment_status', 'payment_method', 'attention', 'date_from', 'date_to'])
            ->contains(fn ($field) => request()->filled($field));
    @endphp

    <section class="page-head reveal">
        <div>
            <h1 class="page-title">Quản lý đơn hàng</h1>
            <p class="page-subtitle">Theo dõi thanh toán, giao hàng, yêu cầu hủy và đổi trả trên cùng một luồng vận hành.</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost"><i class="ri-dashboard-line"></i>Tổng quan</a>
        </div>
    </section>

    @if(session('success'))<div class="admin-alert success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="admin-alert error">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="admin-alert error">{{ $errors->first() }}</div>@endif

    <section class="status-grid reveal" style="--delay: 40ms;">
        @foreach([
            'pending' => ['Chờ xác nhận', 'pending'],
            'confirmed' => ['Đã xác nhận', 'confirmed'],
            'shipping' => ['Đang giao', 'shipping'],
            'done' => ['Hoàn tất', 'done'],
            'cancelled' => ['Đã hủy', 'cancelled'],
        ] as $status => [$label, $class])
            <a class="status-card {{ $class }}" href="{{ route('admin.orders', ['status' => $status]) }}">
                <small>{{ $label }}</small>
                <strong>{{ number_format($orderSummary[$status] ?? 0) }}</strong>
            </a>
        @endforeach
    </section>

    <section class="attention-strip reveal" style="--delay: 70ms;">
        @foreach([
            'cancellation' => ['ri-close-circle-line', 'Yêu cầu hủy'],
            'return' => ['ri-refund-2-line', 'Yêu cầu đổi trả'],
            'awaiting_payment' => ['ri-bank-card-line', 'Chờ đối soát tiền'],
            'shipping_setup' => ['ri-truck-line', 'Chưa gán vận chuyển'],
        ] as $key => [$icon, $label])
            <a class="attention-link {{ ($attentionSummary[$key] ?? 0) > 0 ? 'has-work' : '' }}" href="{{ route('admin.orders', ['attention' => $key]) }}">
                <span><i class="{{ $icon }}"></i> {{ $label }}</span>
                <strong>{{ $attentionSummary[$key] ?? 0 }}</strong>
            </a>
        @endforeach
    </section>

    <section class="panel reveal" style="--delay: 90ms; margin-bottom:14px;">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Tìm và lọc đơn</h2>
                <p class="panel-sub">Có thể tìm bằng mã đơn, tên, email, số điện thoại hoặc mã vận đơn.</p>
            </div>
        </div>
        <form method="get" action="{{ route('admin.orders') }}">
            <div class="filter-grid">
                <div class="field">
                    <label for="q">Từ khóa</label>
                    <input class="input" id="q" name="q" value="{{ request('q') }}" placeholder="DH..., khách hàng, số điện thoại...">
                </div>
                <div class="field">
                    <label for="status">Trạng thái đơn</label>
                    <select class="select" id="status" name="status">
                        <option value="">Tất cả</option>
                        @foreach($statusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="payment_status">Thanh toán</label>
                    <select class="select" id="payment_status" name="payment_status">
                        <option value="">Tất cả</option>
                        @foreach($paymentStatusLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('payment_status') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="payment_method">Phương thức</label>
                    <select class="select" id="payment_method" name="payment_method">
                        <option value="">Tất cả</option>
                        @foreach($paymentMethodLabels as $value => $label)
                            <option value="{{ $value }}" @selected(request('payment_method') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="filter-grid secondary">
                <div class="field">
                    <label for="attention">Việc cần xử lý</label>
                    <select class="select" id="attention" name="attention">
                        <option value="">Tất cả</option>
                        <option value="cancellation" @selected(request('attention') === 'cancellation')>Yêu cầu hủy</option>
                        <option value="return" @selected(request('attention') === 'return')>Yêu cầu đổi trả</option>
                        <option value="awaiting_payment" @selected(request('attention') === 'awaiting_payment')>Chờ đối soát tiền</option>
                        <option value="shipping_setup" @selected(request('attention') === 'shipping_setup')>Chưa gán vận chuyển</option>
                    </select>
                </div>
                <div class="field">
                    <label for="date_from">Từ ngày</label>
                    <input class="input" id="date_from" name="date_from" type="date" value="{{ request('date_from') }}">
                </div>
                <div class="field">
                    <label for="date_to">Đến ngày</label>
                    <input class="input" id="date_to" name="date_to" type="date" value="{{ request('date_to') }}">
                </div>
                <div class="field">
                    <label for="per_page">Mỗi trang</label>
                    <select class="select" id="per_page" name="per_page">
                        @foreach([15, 25, 50] as $size)<option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }}</option>@endforeach
                    </select>
                </div>
                <div class="filter-actions">
                    <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i>Lọc</button>
                    @if($hasFilters)<a class="btn btn-ghost" href="{{ route('admin.orders') }}"><i class="ri-refresh-line"></i>Xóa lọc</a>@endif
                </div>
            </div>
        </form>
    </section>

    <section class="panel reveal" style="--delay: 120ms;">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Danh sách đơn hàng</h2>
                <p class="panel-sub">Mở chi tiết để cập nhật thanh toán, vận chuyển hoặc xử lý yêu cầu của khách.</p>
            </div>
            <span class="tag">{{ number_format($orders->total()) }} đơn</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Tổng tiền</th><th>Giao hàng</th><th>Trạng thái</th><th>Cần xử lý</th><th></th></tr></thead>
                <tbody>
                @forelse($orders as $order)
                    @php
                        $pendingCancellation = $order->cancellationRequests->contains('status', \App\Models\OrderCancellationRequest::STATUS_PENDING);
                        $pendingReturn = $order->returnRequests->contains('status', \App\Models\OrderReturnRequest::STATUS_PENDING);
                        $awaitingPayment = $order->payment_method === \App\Models\Order::PAYMENT_METHOD_BANK_TRANSFER && $order->payment_status === \App\Models\Order::PAYMENT_STATUS_UNPAID;
                    @endphp
                    <tr>
                        <td><div class="order-code"><a href="{{ route('admin.orders.show', $order) }}">{{ $order->code }}</a><small class="muted">{{ \App\Support\LocalDateTime::format($order->created_at) }}</small></div></td>
                        <td><div class="cell-stack"><strong>{{ $order->customer_name }}</strong><span class="muted">{{ $order->customer_phone ?: $order->customer_email ?: '-' }}</span></div></td>
                        <td><div class="cell-stack"><span class="money">{{ number_format((int) $order->total, 0, ',', '.') }}đ</span><span class="status-pill {{ $order->payment_status }}">{{ $order->payment_status_label }}</span><span class="muted">{{ $order->payment_method_label }}</span></div></td>
                        <td><div class="cell-stack"><strong>{{ $order->shipping_provider ?: 'Chưa gán' }}</strong><span class="muted">{{ $order->tracking_code ?: $order->delivery_method_label }}</span></div></td>
                        <td><span class="status-pill {{ $order->status }}">{{ $order->status_label }}</span></td>
                        <td><div class="attention-badges">
                            @if($pendingCancellation)<span class="attention-badge">Hủy đơn</span>@endif
                            @if($pendingReturn)<span class="attention-badge return">Đổi trả</span>@endif
                            @if($awaitingPayment)<span class="attention-badge payment">Đối soát tiền</span>@endif
                            @if(!$pendingCancellation && !$pendingReturn && !$awaitingPayment)<span class="muted">Không có</span>@endif
                        </div></td>
                        <td><a class="btn btn-ghost" href="{{ route('admin.orders.show', $order) }}"><i class="ri-eye-line"></i>Chi tiết</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-box"><i class="ri-inbox-2-line"></i><div>Không tìm thấy đơn hàng phù hợp.</div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <div class="admin-pager">
                <span>Trang {{ $orders->currentPage() }} / {{ $orders->lastPage() }}</span>
                <div class="admin-pager-actions">
                    @if($orders->onFirstPage())<span class="disabled">Trước</span>@else<a href="{{ $orders->previousPageUrl() }}">Trước</a>@endif
                    @if($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}">Sau</a>@else<span class="disabled">Sau</span>@endif
                </div>
            </div>
        @endif
    </section>
@endsection
