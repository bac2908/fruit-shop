@extends('layouts.admin')

@section('title', 'Quan ly don hang | FruitShop Admin')

@section('head')
    <style>
        .admin-alert {
            border-radius: 14px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 14px;
            padding: 12px 14px;
        }

        .admin-alert.success {
            background: #edf8e8;
            border: 1px solid #b9dcac;
            color: #2d6d24;
        }

        .admin-alert.error {
            background: #fff0ec;
            border: 1px solid #f1b9aa;
            color: #a13e2c;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .status-card {
            border: 1px solid #dae5d9;
            border-radius: 12px;
            background: #fff;
            padding: 12px;
        }

        .status-card small {
            color: #5f7368;
            display: block;
            margin-bottom: 6px;
        }

        .status-card strong {
            font-family: 'Sora', sans-serif;
            font-size: 24px;
            line-height: 1.1;
        }

        .status-card.pending strong,
        .status-card.cancel-request strong {
            color: #936c00;
        }

        .status-card.confirmed strong {
            color: #2d7040;
        }

        .status-card.shipping strong {
            color: #1d6b90;
        }

        .status-card.done strong {
            color: #255332;
        }

        .status-card.cancelled strong {
            color: #9a3e2c;
        }

        .order-code {
            display: grid;
            gap: 4px;
        }

        .order-code small,
        .muted {
            color: #6c7b70;
            font-size: 12px;
        }

        .payment-stack,
        .order-update-form,
        .shipping-update-form,
        .cancel-request-box {
            display: grid;
            gap: 7px;
        }

        .order-update-form select,
        .order-update-form input,
        .shipping-update-form input,
        .cancel-request-box input {
            border: 1px solid #d7e2d2;
            border-radius: 9px;
            color: #1d3325;
            font-family: inherit;
            font-size: 12px;
            height: 34px;
            outline: none;
            padding: 0 10px;
            width: 100%;
        }

        .order-update-form button,
        .shipping-update-form button,
        .cancel-request-actions button {
            border: 0;
            border-radius: 9px;
            cursor: pointer;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            min-height: 34px;
            padding: 0 11px;
        }

        .order-update-form button {
            background: #1f7a4a;
            color: #fff;
        }

        .shipping-update-form {
            background: #f6fbf3;
            border: 1px solid #d7e8ce;
            border-radius: 12px;
            margin-top: 8px;
            min-width: 230px;
            padding: 8px;
        }

        .shipping-update-form button {
            background: #f59b18;
            color: #fff;
        }

        .cancel-request-box {
            background: #fffaf0;
            border: 1px solid #f0d8a2;
            border-radius: 12px;
            min-width: 260px;
            padding: 10px;
        }

        .cancel-request-box strong {
            color: #7a5500;
        }

        .cancel-request-box p {
            color: #5f5540;
            font-size: 12px;
            line-height: 1.45;
            margin: 0;
        }

        .cancel-request-actions {
            display: grid;
            gap: 7px;
            grid-template-columns: 1fr 1fr;
        }

        .cancel-request-actions form {
            display: grid;
            gap: 7px;
        }

        .cancel-request-actions form:first-child button {
            background: #1f7a4a;
            color: #fff;
        }

        .cancel-request-actions form:last-child button {
            background: #fff0ec;
            border: 1px solid #f1b9aa;
            color: #a13e2c;
        }

        .status-pill.refunded,
        .status-pill.paid {
            color: #2d6a3d;
            background: #e9f8ef;
        }

        .status-pill.unpaid {
            color: #8b6200;
            background: #fff6db;
        }

        .status-pill.estimated {
            color: #8b6200;
            background: #fff6db;
        }

        .status-pill.confirmed {
            color: #2d6a3d;
            background: #e9f8ef;
        }

        .admin-order-link {
            color: #1f7a4a;
            font-size: 12px;
            font-weight: 700;
        }

        tr.has-cancel-request td {
            background: #fffdf6;
        }

        @media (max-width: 1250px) {
            .status-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .status-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .cancel-request-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('admin_content')
    <section class="page-head reveal" style="--delay: 0ms;">
        <div>
            <h1 class="page-title">Quan ly don hang</h1>
            <p class="page-subtitle">Theo doi don that tu MySQL, duyet huy don, cap nhat trang thai va fulfillment.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a href="{{ route('admin.coupons') }}" class="btn btn-ghost"><i class="ri-coupon-2-line"></i>Voucher</a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-primary"><i class="ri-dashboard-line"></i>Tong quan</a>
        </div>
    </section>

    @if(session('success'))
        <div class="admin-alert success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="admin-alert error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="admin-alert error">{{ $errors->first() }}</div>
    @endif

    <section class="panel reveal" style="--delay: 90ms; margin-bottom: 14px;">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Trang thai don hang</h2>
                <p class="panel-sub">Snapshot pipeline xu ly don va so yeu cau huy dang cho shop duyet.</p>
            </div>
            <span class="tag">Operation pulse</span>
        </div>

        <div class="status-grid">
            <div class="status-card pending">
                <small>Cho xac nhan</small>
                <strong>{{ $orderSummary['pending'] ?? 0 }}</strong>
            </div>
            <div class="status-card confirmed">
                <small>Da xac nhan</small>
                <strong>{{ $orderSummary['confirmed'] ?? 0 }}</strong>
            </div>
            <div class="status-card shipping">
                <small>Dang giao</small>
                <strong>{{ $orderSummary['shipping'] ?? 0 }}</strong>
            </div>
            <div class="status-card done">
                <small>Hoan tat</small>
                <strong>{{ $orderSummary['done'] ?? 0 }}</strong>
            </div>
            <div class="status-card cancelled">
                <small>Da huy</small>
                <strong>{{ $orderSummary['cancelled'] ?? 0 }}</strong>
            </div>
            <div class="status-card cancel-request">
                <small>Yeu cau huy</small>
                <strong>{{ $pendingCancellationCount ?? 0 }}</strong>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Ma don</th>
                    <th>Khach hang</th>
                    <th>Giao hang</th>
                    <th>Thanh toan</th>
                    <th>Trang thai</th>
                    <th>Yeu cau huy</th>
                    <th>Cap nhat</th>
                </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    @php
                        $latestCancellationRequest = $order->latest_cancellation_request;
                        $hasPendingCancellation = $latestCancellationRequest && $latestCancellationRequest->status === \App\Models\OrderCancellationRequest::STATUS_PENDING;
                    @endphp
                    <tr class="{{ $hasPendingCancellation ? 'has-cancel-request' : '' }}">
                        <td>
                            <span class="order-code">
                                <strong>{{ $order->code }}</strong>
                                <small>{{ optional($order->created_at)->format('d/m/Y H:i') }}</small>
                                <a class="admin-order-link" href="{{ route('checkout.thankyou', ['code' => $order->code, 'token' => $order->public_token]) }}" target="_blank" rel="noopener">Xem trang khach</a>
                            </span>
                        </td>
                        <td>
                            <strong>{{ $order->customer_name }}</strong><br>
                            <span class="muted">{{ $order->customer_phone ?? $order->customer_email ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="payment-stack">
                                <strong>{{ $order->shipping_fee_status === \App\Models\Order::SHIPPING_FEE_STATUS_ESTIMATED ? 'Tam tinh ' : '' }}{{ (int) $order->shipping_fee > 0 ? number_format((int) $order->shipping_fee, 0, ',', '.') . ' VND' : 'Mien phi' }}</strong>
                                <span class="status-pill {{ $order->shipping_fee_status }}">{{ $order->shipping_fee_status_label }}</span>
                                <span class="muted">{{ $order->delivery_method_label }}{{ $order->shipping_delivery_eta ? ' · ' . $order->shipping_delivery_eta : '' }}</span>
                                <span class="muted">{{ $order->shipping_address ?: 'Chua co dia chi' }}</span>
                                @if($order->shipping_delivery_note)
                                    <span class="muted">{{ $order->shipping_delivery_note }}</span>
                                @endif
                                @if($order->status !== \App\Models\Order::STATUS_CANCELLED && $order->payment_status !== \App\Models\Order::PAYMENT_STATUS_PAID)
                                    <form method="post" action="{{ route('admin.orders.shipping', $order) }}" class="shipping-update-form">
                                        @csrf
                                        @method('PATCH')
                                        <span class="muted">{{ $order->shipping_fee_status === \App\Models\Order::SHIPPING_FEE_STATUS_CONFIRMED ? 'Sua phi ship neu can' : 'Chot phi ship thuc te' }}</span>
                                        <input type="number" name="shipping_fee" min="0" max="2000000" step="1000" value="{{ (int) $order->shipping_fee }}" required>
                                        <input type="text" name="shipping_delivery_note" maxlength="500" value="{{ $order->shipping_delivery_note }}" placeholder="Ghi chu giao hang">
                                        <button type="submit">{{ $order->shipping_fee_status === \App\Models\Order::SHIPPING_FEE_STATUS_CONFIRMED ? 'Cap nhat phi' : 'Chot phi' }}</button>
                                    </form>
                                @elseif($order->payment_status === \App\Models\Order::PAYMENT_STATUS_PAID)
                                    <span class="muted">Tổng tiền đã khóa sau thanh toán</span>
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="payment-stack">
                                <strong>{{ number_format((int) $order->total, 0, ',', '.') }} VND</strong>
                                <span class="status-pill {{ $order->payment_status }}">{{ $order->payment_status_label }}</span>
                                <span class="muted">{{ $order->payment_method_label }}</span>
                                @if($order->coupon_code)
                                    <span class="status-pill confirmed">Voucher: {{ $order->coupon_code }}</span>
                                @endif
                            </span>
                        </td>
                        <td>
                            <span class="status-pill {{ $order->status }}">{{ $order->status_label }}</span>
                            @if($order->admin_note)
                                <div class="muted" style="margin-top:6px; line-height:1.45;">{!! nl2br(e($order->admin_note)) !!}</div>
                            @endif
                        </td>
                        <td>
                            @if($latestCancellationRequest)
                                <div class="cancel-request-box">
                                    <strong>{{ $latestCancellationRequest->status_label }}</strong>
                                    <p>Ly do: {{ $latestCancellationRequest->reason_label }}</p>
                                    @if($latestCancellationRequest->note)
                                        <p>Khach ghi chu: {{ $latestCancellationRequest->note }}</p>
                                    @endif
                                    @if($latestCancellationRequest->admin_note)
                                        <p>Shop phan hoi: {{ $latestCancellationRequest->admin_note }}</p>
                                    @endif

                                    @if($hasPendingCancellation)
                                        <div class="cancel-request-actions">
                                            <form method="post" action="{{ route('admin.orders.cancellations.approve', $latestCancellationRequest) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="text" name="admin_note" maxlength="500" placeholder="Ghi chu duyet">
                                                <button type="submit">Duyet huy</button>
                                            </form>
                                            <form method="post" action="{{ route('admin.orders.cancellations.reject', $latestCancellationRequest) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="text" name="admin_note" maxlength="500" placeholder="Ly do tu choi" required>
                                                <button type="submit">Tu choi</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            @else
                                <span class="muted">Chua co yeu cau</span>
                            @endif
                        </td>
                        <td>
                            <form method="post" action="{{ route('admin.orders.status', $order) }}" class="order-update-form">
                                @csrf
                                @method('PATCH')
                                <select name="status" required>
                                    @foreach(($allowedStatusTransitions[$order->id] ?? [$order->status]) as $statusValue)
                                        <option value="{{ $statusValue }}" {{ $order->status === $statusValue ? 'selected' : '' }}>{{ $statusLabels[$statusValue] ?? $statusValue }}</option>
                                    @endforeach
                                </select>
                                <input type="text" name="admin_note" maxlength="500" placeholder="Ghi chu noi bo">
                                <button type="submit">Luu trang thai</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-box">
                                <i class="ri-truck-line"></i>
                                <div>Chua co don hang nao trong database.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
