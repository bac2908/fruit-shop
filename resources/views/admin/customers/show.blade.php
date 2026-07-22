@extends('layouts.admin')

@section('title', $customer->name.' | Khách hàng | FruitShop Admin')

@include('admin.customers._styles')

@section('admin_content')
    @php
        $isLocked = $customer->locked_until && now()->lessThan($customer->locked_until);
        $eventLabels = [
            'login_success' => 'Đăng nhập bằng email',
            'google_login_success' => 'Đăng nhập bằng Google',
            'login_failed' => 'Đăng nhập thất bại',
            'password_reset_requested' => 'Yêu cầu đặt lại mật khẩu',
            'logout' => 'Đăng xuất',
        ];
    @endphp

    <section class="page-head reveal">
        <div class="customer-profile-head">
            <span class="customer-profile-avatar">
                @if($customer->avatar_url)<img src="{{ \Illuminate\Support\Str::startsWith($customer->avatar_url, ['http://', 'https://']) ? $customer->avatar_url : asset($customer->avatar_url) }}" alt="">@else{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}@endif
            </span>
            <div>
                <h1 class="page-title">{{ $customer->name }}</h1>
                <p class="page-subtitle">{{ $customer->email }} · Khách hàng #{{ $customer->id }}</p>
            </div>
        </div>
        <div class="customer-page-actions">
            <a class="btn btn-ghost" href="{{ route('admin.customers') }}"><i class="ri-arrow-left-line"></i>Danh sách</a>
            <a class="btn btn-ghost" href="{{ route('admin.orders', ['q' => $customer->email]) }}"><i class="ri-file-list-3-line"></i>Đơn hàng</a>
        </div>
    </section>

    @if(session('success'))<div class="customer-alert success" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="customer-alert error" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="customer-alert error" role="alert">{{ $errors->first() }}</div>@endif

    <section class="customer-metrics reveal" style="--delay:40ms">
        @foreach([
            ['Tổng chi tiêu', number_format($metrics['total_spent'], 0, ',', '.').'đ'],
            ['Tổng đơn', number_format($metrics['orders'])],
            ['Đơn hoàn tất', number_format($metrics['completed'])],
            ['Đơn đang xử lý', number_format($metrics['active'])],
            ['Hạng thành viên', $membership['tier']],
        ] as [$label, $value])
            <div class="customer-metric"><small>{{ $label }}</small><strong>{{ $value }}</strong></div>
        @endforeach
    </section>

    <section class="customer-detail-grid">
        <div class="customer-detail-main">
            <section class="panel reveal" style="--delay:70ms">
                <div class="panel-head">
                    <div><h2 class="panel-title">Hồ sơ khách hàng</h2><p class="panel-sub">Email và vai trò là dữ liệu định danh, không chỉnh sửa tại màn hình hỗ trợ.</p></div>
                    <span class="account-badge {{ $customer->account_status }}">{{ \App\Models\User::accountStatusLabels()[$customer->account_status] ?? $customer->account_status }}</span>
                </div>
                <form method="post" action="{{ route('admin.customers.update', $customer) }}">
                    @csrf @method('PUT')
                    <div class="detail-form-grid">
                        <div class="customer-field"><label for="name">Họ và tên</label><input class="customer-input" id="name" name="name" value="{{ old('name', $customer->name) }}" required></div>
                        <div class="customer-field"><label for="phone">Số điện thoại</label><input class="customer-input" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" placeholder="0912345678"></div>
                        <div class="customer-field"><label>Email đăng nhập</label><input class="customer-input" value="{{ $customer->email }}" disabled></div>
                        <div class="customer-field"><label>Nguồn đăng nhập</label><input class="customer-input" value="{{ $customer->auth_provider === 'google' ? 'Google' : 'Email và mật khẩu' }}" disabled></div>
                        <div class="customer-field"><label for="birthday">Ngày sinh</label><input class="customer-input" type="date" id="birthday" name="birthday" value="{{ old('birthday', optional($customer->birthday)->format('Y-m-d')) }}"></div>
                        <div class="customer-field">
                            <label for="gender">Giới tính</label>
                            <select class="customer-select" id="gender" name="gender">
                                <option value="">Chưa cung cấp</option>
                                <option value="male" @selected(old('gender', $customer->gender) === 'male')>Nam</option>
                                <option value="female" @selected(old('gender', $customer->gender) === 'female')>Nữ</option>
                                <option value="other" @selected(old('gender', $customer->gender) === 'other')>Khác</option>
                                <option value="unspecified" @selected(old('gender', $customer->gender) === 'unspecified')>Không muốn nêu</option>
                            </select>
                        </div>
                        <div class="customer-field span-2">
                            <label for="admin_note">Ghi chú nội bộ</label>
                            <textarea class="customer-textarea" id="admin_note" name="admin_note" placeholder="Ví dụ: yêu cầu giao buổi sáng, cần gọi trước khi giao...">{{ old('admin_note', $customer->admin_note) }}</textarea>
                        </div>
                    </div>
                    <div class="detail-actions" style="margin-top:10px"><button class="btn btn-primary" type="submit"><i class="ri-save-line"></i>Lưu hồ sơ</button></div>
                </form>
            </section>

            <section class="panel reveal" style="--delay:90ms">
                <div class="panel-head">
                    <div><h2 class="panel-title">Đơn hàng gần đây</h2><p class="panel-sub">Doanh thu chỉ tính các đơn đã hoàn tất.</p></div>
                    <span class="tag">AOV {{ number_format($metrics['average_order'], 0, ',', '.') }}đ</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Thanh toán</th><th>Trạng thái</th><th></th></tr></thead>
                        <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td><a class="order-link" href="{{ route('admin.orders.show', $order) }}">{{ $order->code }}</a></td>
                                <td>{{ \App\Support\LocalDateTime::format($order->created_at) }}</td>
                                <td><strong>{{ number_format((int) $order->total, 0, ',', '.') }}đ</strong></td>
                                <td><span class="status-pill {{ $order->payment_status }}">{{ $order->payment_status_label }}</span></td>
                                <td><span class="status-pill {{ $order->status }}">{{ $order->status_label }}</span></td>
                                <td><a class="btn btn-ghost" href="{{ route('admin.orders.show', $order) }}" title="Mở đơn hàng" aria-label="Mở đơn hàng"><i class="ri-arrow-right-line"></i></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><div class="empty-box"><i class="ri-shopping-bag-line"></i><div>Khách hàng chưa có đơn hàng.</div></div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @if($orders->hasPages())
                    <div class="customer-pager"><span>Trang {{ $orders->currentPage() }} / {{ $orders->lastPage() }}</span><div>@if($orders->onFirstPage())<span class="disabled">Trước</span>@else<a href="{{ $orders->previousPageUrl() }}">Trước</a>@endif @if($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}">Sau</a>@else<span class="disabled">Sau</span>@endif</div></div>
                @endif
            </section>

            <section class="panel reveal" style="--delay:110ms">
                <div class="panel-head"><div><h2 class="panel-title">Địa chỉ giao hàng</h2><p class="panel-sub">Địa chỉ do khách lưu; admin chỉ xem để tránh tự ý thay đổi thông tin nhận hàng.</p></div><span class="tag">{{ $customer->addresses->count() }} địa chỉ</span></div>
                <div class="address-list">
                    @forelse($customer->addresses as $address)
                        <div class="address-item">
                            <div class="address-item-head"><strong>{{ $address->recipient_name }} · {{ $address->phone }}</strong>@if($address->is_default)<span class="mini-status">Mặc định</span>@endif</div>
                            <p>{{ $address->full_address }}</p>
                            @if($address->province_code || $address->ward_code)<p>Mã hành chính: {{ $address->province_code ?: '-' }} / {{ $address->ward_code ?: '-' }}</p>@endif
                        </div>
                    @empty
                        <div class="empty-box"><i class="ri-map-pin-line"></i><div>Khách hàng chưa lưu địa chỉ giao hàng.</div></div>
                    @endforelse
                </div>
            </section>

            <section class="panel reveal" style="--delay:130ms">
                <div class="panel-head"><div><h2 class="panel-title">Voucher của khách</h2><p class="panel-sub">Theo dõi voucher đã phát, trạng thái sử dụng và hạn riêng.</p></div><span class="tag">{{ $vouchers->total() }} voucher</span></div>
                <div class="voucher-list">
                    @forelse($vouchers as $voucher)
                        <div class="voucher-item">
                            <div class="voucher-item-head">
                                <span><strong>{{ optional($voucher->coupon)->code ?: 'Voucher đã xóa' }}</strong> · {{ optional($voucher->coupon)->title ?: 'Không còn dữ liệu' }}</span>
                                <span class="mini-status">{{ $voucher->status_label }}</span>
                            </div>
                            <p>Nhận {{ \App\Support\LocalDateTime::format($voucher->assigned_at) }} · {{ $voucher->expiry_label }}</p>
                        </div>
                    @empty
                        <div class="empty-box"><i class="ri-coupon-2-line"></i><div>Khách hàng chưa có voucher cá nhân.</div></div>
                    @endforelse
                </div>
                @if($vouchers->hasPages())
                    <div class="customer-pager"><span>Trang {{ $vouchers->currentPage() }} / {{ $vouchers->lastPage() }}</span><div>@if($vouchers->onFirstPage())<span class="disabled">Trước</span>@else<a href="{{ $vouchers->previousPageUrl() }}">Trước</a>@endif @if($vouchers->hasMorePages())<a href="{{ $vouchers->nextPageUrl() }}">Sau</a>@else<span class="disabled">Sau</span>@endif</div></div>
                @endif
            </section>
        </div>

        <aside class="customer-detail-side">
            <section class="panel reveal" style="--delay:70ms">
                <div class="panel-head"><div><h2 class="panel-title">Tài khoản và bảo mật</h2><p class="panel-sub">Thao tác tại đây đều được ghi audit.</p></div></div>
                <div class="detail-list" style="margin-bottom:12px">
                    <div class="detail-row"><span>Trạng thái</span><strong>{{ \App\Models\User::accountStatusLabels()[$customer->account_status] ?? $customer->account_status }}</strong></div>
                    <div class="detail-row"><span>Xác minh email</span><strong>{{ $customer->hasVerifiedEmail() ? 'Đã xác minh' : 'Chưa xác minh' }}</strong></div>
                    <div class="detail-row"><span>Đăng nhập gần nhất</span><strong>{{ \App\Support\LocalDateTime::format($customer->last_login_at) }}</strong></div>
                    <div class="detail-row"><span>IP gần nhất</span><strong>{{ $customer->last_login_ip ?: '-' }}</strong></div>
                    <div class="detail-row"><span>Điểm thưởng</span><strong>{{ number_format($membership['points']) }}</strong></div>
                </div>

                <div class="account-action">
                    <h3>Phiên đăng nhập</h3><p>Buộc đăng nhập lại trên trình duyệt và thiết bị đang dùng tài khoản này.</p>
                    <form method="post" action="{{ route('admin.customers.sessions.revoke', $customer) }}">@csrf @method('PATCH')<button class="btn btn-ghost" type="submit"><i class="ri-logout-box-r-line"></i>Đăng xuất mọi thiết bị</button></form>
                </div>

                @if($isLocked)
                    <div class="account-action">
                        <h3>Mở khóa đăng nhập</h3><p>Tài khoản đang bị khóa đến {{ \App\Support\LocalDateTime::format($customer->locked_until) }} sau {{ $customer->failed_login_attempts }} lần sai.</p>
                        <form method="post" action="{{ route('admin.customers.unlock', $customer) }}">@csrf @method('PATCH')<button class="btn btn-ghost" type="submit"><i class="ri-lock-unlock-line"></i>Mở khóa</button></form>
                    </div>
                @endif

                @unless($customer->hasVerifiedEmail())
                    <div class="account-action">
                        <h3>Xác minh email</h3><p>Gửi lại liên kết ký số đến đúng email đăng ký.</p>
                        <form method="post" action="{{ route('admin.customers.verification', $customer) }}">@csrf<button class="btn btn-ghost" type="submit"><i class="ri-mail-send-line"></i>Gửi lại email</button></form>
                    </div>
                @endunless

                @if($customer->isSuspended())
                    <div class="account-action">
                        <div class="notice danger"><strong>Lý do tạm ngưng:</strong><br>{{ $customer->suspension_reason }}<br><small>{{ \App\Support\LocalDateTime::format($customer->suspended_at) }}@if($customer->suspendedBy) · {{ $customer->suspendedBy->name }}@endif</small></div>
                        <form method="post" action="{{ route('admin.customers.activate', $customer) }}">@csrf @method('PATCH')<button class="btn btn-primary" type="submit"><i class="ri-user-follow-line"></i>Kích hoạt lại</button></form>
                    </div>
                @else
                    <div class="account-action">
                        <h3>Tạm ngưng tài khoản</h3><p>Không xóa dữ liệu hoặc đơn hàng. Khách sẽ bị đăng xuất và không thể đăng nhập.</p>
                        <form method="post" action="{{ route('admin.customers.suspend', $customer) }}">@csrf @method('PATCH')<textarea class="customer-textarea" name="reason" required minlength="10" maxlength="1000" placeholder="Nêu rõ lý do để phục vụ đối soát...">{{ old('reason') }}</textarea><button class="btn btn-danger" type="submit"><i class="ri-user-forbid-line"></i>Tạm ngưng</button></form>
                    </div>
                @endif
            </section>

            <section class="panel reveal" style="--delay:90ms">
                <div class="panel-head"><div><h2 class="panel-title">Phát voucher</h2><p class="panel-sub">Voucher hợp lệ sẽ xuất hiện trong tài khoản và thông báo của khách.</p></div></div>
                @if($assignableCoupons->isNotEmpty())
                    <form method="post" action="{{ route('admin.customers.vouchers.store', $customer) }}">
                        @csrf
                        <div class="customer-field"><label for="coupon_id">Voucher</label><select class="customer-select" id="coupon_id" name="coupon_id" required><option value="">Chọn voucher</option>@foreach($assignableCoupons as $coupon)<option value="{{ $coupon->id }}" @selected((int) old('coupon_id') === $coupon->id)>{{ $coupon->code }} · {{ $coupon->title }}</option>@endforeach</select></div>
                        <div class="customer-field" style="margin-top:8px"><label for="expires_at">Hạn riêng (không bắt buộc)</label><input class="customer-input" id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at') }}"></div>
                        <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center;margin-top:9px"><i class="ri-gift-line"></i>Phát voucher</button>
                    </form>
                @else
                    <div class="notice warning">Hiện không có voucher đang hoạt động và còn hiệu lực để phát.</div>
                @endif
            </section>

            <section class="panel reveal" style="--delay:110ms">
                <div class="panel-head"><div><h2 class="panel-title">Hoạt động đăng nhập</h2><p class="panel-sub">Dùng để hỗ trợ phát hiện truy cập bất thường.</p></div></div>
                <div class="security-list">
                    @forelse($securityEvents as $event)
                        <div class="security-item"><div class="security-item-head"><strong>{{ $eventLabels[$event->action] ?? $event->action }}</strong><span class="mini-status">{{ $event->ip_address ?: '-' }}</span></div><p>{{ \App\Support\LocalDateTime::format($event->created_at) }}</p></div>
                    @empty
                        <div class="notice">Chưa có hoạt động bảo mật được ghi nhận.</div>
                    @endforelse
                </div>
                @if($failedLoginAttempts->isNotEmpty())<div class="notice warning" style="margin-top:9px">Có {{ $failedLoginAttempts->count() }} lần đăng nhập sai gần nhất được lưu để đối soát.</div>@endif
            </section>
        </aside>
    </section>
@endsection
