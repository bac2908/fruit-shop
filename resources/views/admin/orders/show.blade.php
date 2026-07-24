@extends('layouts.admin')

@section('title', 'Đơn ' . $order->code . ' | FruitShop Admin')

@include('admin.orders._styles')

@section('admin_content')
    @php
        $latestCancellation = $order->latest_cancellation_request;
        $pendingCancellation = $latestCancellation && $latestCancellation->status === \App\Models\OrderCancellationRequest::STATUS_PENDING;
        $isTerminal = in_array($order->status, [\App\Models\Order::STATUS_DONE, \App\Models\Order::STATUS_CANCELLED], true);
    @endphp

    <section class="page-head reveal">
        <div>
            <p class="page-subtitle"><a href="{{ route('admin.orders') }}">Đơn hàng</a> / {{ $order->code }}</p>
            <h1 class="page-title">{{ $order->code }}</h1>
            <div class="action-row" style="margin-top:8px;">
                <span class="status-pill {{ $order->status }}">{{ $order->status_label }}</span>
                <span class="status-pill {{ $order->payment_status }}">{{ $order->payment_status_label }}</span>
                <span class="muted">Tạo lúc {{ \App\Support\LocalDateTime::format($order->created_at) }}</span>
            </div>
        </div>
        <div class="page-actions">
            <a class="btn btn-ghost" href="{{ route('checkout.thankyou', ['code' => $order->code, 'token' => $order->public_token]) }}" target="_blank" rel="noopener"><i class="ri-external-link-line"></i>Trang khách</a>
            <a class="btn btn-ghost" href="{{ route('admin.orders') }}"><i class="ri-arrow-left-line"></i>Danh sách</a>
        </div>
    </section>

    @if(session('success'))<div class="admin-alert success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="admin-alert error">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="admin-alert error">{{ $errors->first() }}</div>@endif

    <section class="order-detail-grid">
        <div class="detail-main">
            <article class="panel reveal">
                <div class="panel-head">
                    <div><h2 class="panel-title">Sản phẩm</h2><p class="panel-sub">{{ $order->items->sum('qty') }} sản phẩm trong đơn.</p></div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Sản phẩm</th><th>Đơn giá</th><th>Số lượng</th><th>Thành tiền</th></tr></thead>
                        <tbody>
                        @foreach($order->items as $item)
                            <tr>
                                <td>
                                    <div class="item-cell">
                                        @if($item->product)
                                            <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product_name }}">
                                        @else
                                            <span class="product-thumb"><i class="ri-image-line"></i></span>
                                        @endif
                                        <div><strong>{{ $item->product_name }}</strong><div class="muted">{{ $item->unit ?: 'sản phẩm' }}</div></div>
                                    </div>
                                </td>
                                <td>{{ number_format((int) $item->unit_price, 0, ',', '.') }}đ</td>
                                <td>{{ number_format((int) $item->qty) }}</td>
                                <td class="money">{{ number_format((int) $item->line_total, 0, ',', '.') }}đ</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </article>

            <div class="detail-grid-2">
                <article class="panel reveal">
                    <div class="panel-head"><div><h2 class="panel-title">Khách hàng</h2><p class="panel-sub">Thông tin liên hệ của người nhận.</p></div></div>
                    <div class="detail-list">
                        <div class="detail-row"><span>Họ tên</span><strong>{{ $order->customer_name }}</strong></div>
                        <div class="detail-row"><span>Điện thoại</span><strong>{{ $order->customer_phone ?: '-' }}</strong></div>
                        <div class="detail-row"><span>Email</span><strong>{{ $order->customer_email ?: '-' }}</strong></div>
                        <div class="detail-row"><span>Tài khoản</span><strong>{{ optional($order->user)->name ?: 'Khách vãng lai' }}</strong></div>
                    </div>
                </article>
                <article class="panel reveal">
                    <div class="panel-head"><div><h2 class="panel-title">Giao nhận</h2><p class="panel-sub">Địa chỉ và thông tin vận chuyển hiện tại.</p></div></div>
                    <div class="detail-list">
                        <div class="detail-row"><span>Địa chỉ</span><strong>{{ $order->shipping_address ?: '-' }}</strong></div>
                        <div class="detail-row"><span>Hình thức</span><strong>{{ $order->delivery_method_label }}</strong></div>
                        <div class="detail-row"><span>Đơn vị giao</span><strong>{{ $order->shipping_provider ?: 'Chưa gán' }}</strong></div>
                        <div class="detail-row"><span>Mã vận đơn</span><strong>{{ $order->tracking_code ?: '-' }}</strong></div>
                        <div class="detail-row"><span>Dự kiến</span><strong>{{ $order->shipping_delivery_eta ?: '-' }}</strong></div>
                    </div>
                </article>
            </div>

            @if($order->customer_note || $order->admin_note)
                <article class="panel reveal">
                    <div class="panel-head"><div><h2 class="panel-title">Ghi chú</h2><p class="panel-sub">Yêu cầu của khách và nhật ký nội bộ.</p></div></div>
                    <div class="detail-grid-2">
                        <div><strong>Khách hàng</strong><div class="notice" style="margin-top:7px;">{{ $order->customer_note ?: 'Không có ghi chú.' }}</div></div>
                        <div><strong>Nội bộ</strong><div class="notice" style="margin-top:7px; white-space:pre-line;">{{ $order->admin_note ?: 'Chưa có ghi chú nội bộ.' }}</div></div>
                    </div>
                </article>
            @endif

            <article class="panel reveal">
                <div class="panel-head"><div><h2 class="panel-title">Lịch sử xử lý</h2><p class="panel-sub">Mọi thay đổi quan trọng đều được ghi lại cùng người thao tác.</p></div></div>
                <div class="timeline">
                    @forelse($order->statusHistories->sortByDesc('created_at') as $history)
                        <div class="timeline-item">
                            <span class="timeline-dot"></span>
                            <div class="timeline-body">
                                <strong>{{ $statusLabels[$history->status] ?? str_replace('_', ' ', ucfirst($history->status)) }}</strong>
                                @if($history->note)<span>{{ $history->note }}</span>@endif
                                <small>{{ \App\Support\LocalDateTime::format($history->created_at) }} · {{ optional($history->user)->name ?: 'Hệ thống' }}</small>
                            </div>
                        </div>
                    @empty
                        <div class="empty-box"><i class="ri-history-line"></i><div>Chưa có lịch sử cập nhật.</div></div>
                    @endforelse
                </div>
            </article>

            @if($order->returnRequests->isNotEmpty())
                <article class="panel reveal">
                    <div class="panel-head"><div><h2 class="panel-title">Yêu cầu đổi trả</h2><p class="panel-sub">Kiểm tra bằng chứng và phản hồi rõ ràng cho khách.</p></div></div>
                    <div class="detail-list">
                        @foreach($order->returnRequests->sortByDesc('requested_at') as $returnRequest)
                            <div class="request-box">
                                <div class="request-head">
                                    <div><strong>{{ $returnRequest->type_label }}</strong><div class="muted">{{ $returnRequest->reason_label }} · {{ \App\Support\LocalDateTime::format($returnRequest->requested_at) }}</div></div>
                                    <span class="status-pill {{ $returnRequest->status }}">{{ $returnRequest->status_label }}</span>
                                </div>
                                @if($returnRequest->note)<div class="notice">Khách ghi chú: {{ $returnRequest->note }}</div>@endif
                                @if($returnRequest->refund_method)<div class="muted">Nhận hoàn tiền: {{ $returnRequest->refund_method_label }} · {{ $returnRequest->refund_account ?: 'chờ xác nhận' }}</div>@endif
                                @if($returnRequest->evidence_url)<a class="btn btn-ghost" href="{{ $returnRequest->evidence_url }}" target="_blank" rel="noopener"><i class="ri-image-line"></i>Xem bằng chứng</a>@endif
                                @if($returnRequest->admin_note)<div class="notice">Shop phản hồi: {{ $returnRequest->admin_note }}</div>@endif

                                @if($returnRequest->status === \App\Models\OrderReturnRequest::STATUS_PENDING)
                                    <div class="request-actions">
                                        <form method="post" action="{{ route('admin.orders.returns.approve', $returnRequest) }}">
                                            @csrf @method('PATCH')
                                            @if($returnRequest->type === \App\Models\OrderReturnRequest::TYPE_REFUND)
                                                <input class="input" type="number" name="refund_amount" min="1000" max="{{ (int) $order->total }}" step="1000" value="{{ (int) $order->total }}" placeholder="Số tiền dự kiến hoàn">
                                            @endif
                                            <input class="input" name="admin_note" maxlength="500" placeholder="Hướng xử lý cho khách">
                                            <button class="btn btn-primary" type="submit"><i class="ri-check-line"></i>Duyệt</button>
                                        </form>
                                        <form method="post" action="{{ route('admin.orders.returns.reject', $returnRequest) }}">
                                            @csrf @method('PATCH')
                                            <input class="input" name="admin_note" minlength="5" maxlength="500" required placeholder="Lý do từ chối">
                                            <button class="btn btn-danger" type="submit"><i class="ri-close-line"></i>Từ chối</button>
                                        </form>
                                    </div>
                                @elseif($returnRequest->status === \App\Models\OrderReturnRequest::STATUS_APPROVED && $returnRequest->type === \App\Models\OrderReturnRequest::TYPE_REFUND)
                                    <form method="post" action="{{ route('admin.orders.returns.refund', $returnRequest) }}" class="action-panel">
                                        @csrf @method('PATCH')
                                        <div class="detail-grid-2">
                                            <div class="field"><label>Số tiền hoàn</label><input class="input" type="number" name="refund_amount" min="1000" max="{{ (int) $order->total }}" step="1000" value="{{ (int) ($returnRequest->refund_amount ?: $order->total) }}" required></div>
                                            <div class="field"><label>Mã giao dịch hoàn</label><input class="input" name="refund_reference" minlength="4" maxlength="120" required></div>
                                        </div>
                                        <input class="input" name="admin_note" maxlength="500" placeholder="Ghi chú hoàn tiền">
                                        <button class="btn btn-primary" type="submit"><i class="ri-refund-2-line"></i>Xác nhận đã hoàn tiền</button>
                                    </form>
                                @elseif($returnRequest->status === \App\Models\OrderReturnRequest::STATUS_APPROVED && $returnRequest->type === \App\Models\OrderReturnRequest::TYPE_EXCHANGE)
                                    <form method="post" action="{{ route('admin.orders.returns.complete', $returnRequest) }}" class="action-panel">
                                        @csrf @method('PATCH')
                                        <input class="input" name="admin_note" minlength="5" maxlength="500" required placeholder="Sản phẩm thay thế và cách bàn giao">
                                        <button class="btn btn-primary" type="submit"><i class="ri-checkbox-circle-line"></i>Xác nhận đã đổi xong</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </article>
            @endif
        </div>

        <aside class="detail-side">
            <article class="panel action-panel">
                <h3>Tổng thanh toán</h3>
                <div class="summary-list">
                    <div class="summary-row"><span>Tạm tính</span><strong>{{ number_format((int) $order->subtotal, 0, ',', '.') }}đ</strong></div>
                    <div class="summary-row"><span>Giảm giá</span><strong>-{{ number_format((int) $order->discount_total, 0, ',', '.') }}đ</strong></div>
                    <div class="summary-row"><span>Phí giao hàng</span><strong>{{ number_format((int) $order->shipping_fee, 0, ',', '.') }}đ</strong></div>
                    <div class="summary-row total"><span>Tổng cộng</span><strong>{{ number_format((int) $order->total, 0, ',', '.') }}đ</strong></div>
                </div>
                @if($order->coupon_code)<span class="status-pill confirmed">Voucher: {{ $order->coupon_code }}</span>@endif
            </article>

            <article class="panel action-panel">
                <h3>Trạng thái đơn</h3>
                <span class="status-pill {{ $order->status }}">{{ $order->status_label }}</span>
                @if($order->status === \App\Models\Order::STATUS_PENDING)
                    <div class="notice warning">
                        <strong>Đang chờ vì:</strong> {{ $order->confirmationPendingReason() }}
                        @if($order->requiresShippingConfirmation())
                            <br>Thao tác tiếp theo: hoàn thiện và lưu mục <strong>Vận chuyển</strong> bên dưới.
                        @elseif($order->payment_method === \App\Models\Order::PAYMENT_METHOD_BANK_TRANSFER && $order->payment_status !== \App\Models\Order::PAYMENT_STATUS_PAID)
                            <br>Thao tác tiếp theo: đối soát rồi bấm <strong>Xác nhận đã nhận tiền</strong>.
                        @elseif($order->payment_method === \App\Models\Order::PAYMENT_METHOD_MOMO && $order->payment_status !== \App\Models\Order::PAYMENT_STATUS_PAID)
                            <br>Hệ thống sẽ tự xử lý khi nhận callback MoMo hợp lệ; admin không xác nhận thủ công.
                        @elseif($order->isReadyForConfirmation())
                            <br>Thao tác tiếp theo: chọn <strong>Đã xác nhận</strong> để bắt đầu chuẩn bị hàng.
                        @endif
                    </div>
                @endif
                @if(count($availableStatuses) > 1)
                    <form method="post" action="{{ route('admin.orders.status', $order) }}">
                        @csrf @method('PATCH')
                        <div class="field">
                            <label for="status">Hành động tiếp theo</label>
                            <select class="select" id="status" name="status" required>
                                <option value="">Chọn trạng thái</option>
                                @foreach($availableStatuses as $value)
                                    @continue($value === $order->status)
                                    <option value="{{ $value }}">{{ $statusLabels[$value] ?? $value }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field"><label for="admin_note">Ghi chú nội bộ</label><input class="input" id="admin_note" name="admin_note" maxlength="500" placeholder="Lý do hoặc thông tin bàn giao"></div>
                        <button class="btn btn-primary" type="submit"><i class="ri-arrow-right-circle-line"></i>Cập nhật trạng thái</button>
                    </form>
                @else
                    @if($isTerminal)
                        <div class="notice">Đơn đã ở trạng thái kết thúc và không thể chuyển tiếp.</div>
                    @else
                        <div class="notice">Hoàn thành điều kiện được hướng dẫn ở trên để mở bước trạng thái tiếp theo.</div>
                    @endif
                @endif
            </article>

            <article class="panel action-panel">
                <h3>Thanh toán</h3>
                <div class="detail-list">
                    <div class="detail-row"><span>Phương thức</span><strong>{{ $order->payment_method_label }}</strong></div>
                    <div class="detail-row"><span>Trạng thái</span><strong>{{ $order->payment_status_label }}</strong></div>
                    @if($order->payment_reference)<div class="detail-row"><span>Tham chiếu thu</span><strong>{{ $order->payment_reference }}</strong></div>@endif
                    @if($order->refund_reference)<div class="detail-row"><span>Tham chiếu hoàn</span><strong>{{ $order->refund_reference }}</strong></div>@endif
                </div>
                @if($order->payment_method === \App\Models\Order::PAYMENT_METHOD_BANK_TRANSFER && $order->payment_status === \App\Models\Order::PAYMENT_STATUS_UNPAID && !$isTerminal)
                    <form method="post" action="{{ route('admin.orders.payment.verify', $order) }}">
                        @csrf @method('PATCH')
                        <div class="field"><label>Mã tham chiếu ngân hàng</label><input class="input" name="payment_reference" minlength="4" maxlength="120" required placeholder="FT..., giao dịch ngân hàng..."></div>
                        <input class="input" name="admin_note" maxlength="500" placeholder="Ghi chú đối soát">
                        <button class="btn btn-primary" type="submit"><i class="ri-shield-check-line"></i>Xác nhận đã nhận tiền</button>
                    </form>
                @elseif($order->status === \App\Models\Order::STATUS_CANCELLED && in_array($order->payment_status, [\App\Models\Order::PAYMENT_STATUS_PAID, \App\Models\Order::PAYMENT_STATUS_PARTIALLY_REFUNDED], true))
                    <div class="notice warning">Đơn đã hủy nhưng tiền chưa hoàn. Chỉ xác nhận sau khi giao dịch hoàn tiền thành công.</div>
                    <form method="post" action="{{ route('admin.orders.payment.refund', $order) }}">
                        @csrf @method('PATCH')
                        <div class="field"><label>Mã tham chiếu hoàn tiền</label><input class="input" name="refund_reference" minlength="4" maxlength="120" required></div>
                        <input class="input" name="admin_note" maxlength="500" placeholder="Ghi chú hoàn tiền">
                        <button class="btn btn-primary" type="submit"><i class="ri-refund-2-line"></i>Xác nhận đã hoàn tiền</button>
                    </form>
                @endif
            </article>

            <article class="panel action-panel">
                <h3>Vận chuyển</h3>
                @if(!$isTerminal)
                    <form method="post" action="{{ route('admin.orders.shipping', $order) }}">
                        @csrf @method('PATCH')
                        <div class="field"><label>Phí giao hàng</label><input class="input" type="number" name="shipping_fee" min="0" max="2000000" step="1000" value="{{ (int) $order->shipping_fee }}" required @readonly($order->payment_status !== \App\Models\Order::PAYMENT_STATUS_UNPAID)></div>
                        <div class="field"><label>Đơn vị giao hàng</label><input class="input" name="shipping_provider" maxlength="100" value="{{ $order->shipping_provider }}" placeholder="Nội bộ, Grab, GHN..."></div>
                        <div class="field"><label>Mã vận đơn</label><input class="input" name="tracking_code" maxlength="120" value="{{ $order->tracking_code }}" placeholder="Mã theo dõi giao hàng"></div>
                        <div class="field"><label>Thời gian dự kiến</label><input class="input" name="shipping_delivery_eta" maxlength="120" value="{{ $order->shipping_delivery_eta }}" placeholder="Ví dụ: 30 - 90 phút"></div>
                        <div class="field"><label>Ghi chú giao hàng</label><textarea class="textarea" name="shipping_delivery_note" maxlength="500">{{ $order->shipping_delivery_note }}</textarea></div>
                        <button class="btn btn-primary" type="submit"><i class="ri-truck-line"></i>Lưu vận chuyển</button>
                    </form>
                @else
                    <div class="notice">Thông tin vận chuyển đã khóa vì đơn đã kết thúc.</div>
                @endif
            </article>

            @if($latestCancellation)
                <article class="panel action-panel">
                    <h3>Yêu cầu hủy đơn</h3>
                    <span class="status-pill {{ $latestCancellation->status }}">{{ $latestCancellation->status_label }}</span>
                    <div class="notice warning"><strong>{{ $latestCancellation->reason_label }}</strong>@if($latestCancellation->note)<br>{{ $latestCancellation->note }}@endif</div>
                    @if($latestCancellation->admin_note)<div class="notice">Shop phản hồi: {{ $latestCancellation->admin_note }}</div>@endif
                    @if($pendingCancellation)
                        <form method="post" action="{{ route('admin.orders.cancellations.approve', $latestCancellation) }}">
                            @csrf @method('PATCH')
                            <input class="input" name="admin_note" maxlength="500" placeholder="Ghi chú khi duyệt">
                            <button class="btn btn-primary" type="submit"><i class="ri-check-line"></i>Duyệt hủy</button>
                        </form>
                        <form method="post" action="{{ route('admin.orders.cancellations.reject', $latestCancellation) }}">
                            @csrf @method('PATCH')
                            <input class="input" name="admin_note" minlength="5" maxlength="500" required placeholder="Lý do từ chối">
                            <button class="btn btn-danger" type="submit"><i class="ri-close-line"></i>Từ chối hủy</button>
                        </form>
                    @endif
                </article>
            @endif
        </aside>
    </section>
@endsection
