@extends('layouts.admin')

@section('title', 'Cài đặt hệ thống | FruitShop Admin')

@section('head')
<style>
    .settings-layout{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(320px,.85fr);gap:14px}.settings-stack{display:grid;gap:14px}.settings-form{display:grid;gap:14px}.settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.settings-field{display:grid;gap:6px}.settings-field.full{grid-column:1/-1}.settings-field label,.setting-label{color:#52675a;font-size:12px;font-weight:800}.settings-input,.settings-textarea{width:100%;border:1px solid #d6e1d5;border-radius:8px;background:#fff;color:#173425;padding:10px 11px;font:inherit}.settings-input{height:43px}.settings-textarea{min-height:88px;resize:vertical}.settings-toggle{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:14px;align-items:center;padding:13px 0;border-bottom:1px solid #e8eee5}.settings-toggle:last-child{border:0}.settings-toggle strong{font-size:13px;color:#223d2d}.settings-toggle span{display:block;color:#718078;font-size:12px;margin-top:3px}.settings-toggle input{width:20px;height:20px;accent-color:#397a50}.settings-note{padding:12px;border:1px solid #d9e6d5;border-radius:8px;background:#f7faf5;color:#526458;font-size:12px;line-height:1.6}.settings-security{display:flex;justify-content:space-between;align-items:center;gap:12px}.settings-security-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.settings-role{display:inline-flex;border-radius:999px;background:#edf5ea;color:#39734c;padding:5px 9px;font-size:11px;font-weight:800}.staff-create{display:grid;grid-template-columns:1fr 1.2fr 1fr 1.2fr auto;gap:9px;align-items:end;margin-bottom:14px}.staff-table select{height:36px;border:1px solid var(--admin-line);border-radius:7px;background:#fff;padding:0 8px;font:inherit;font-size:12px}@media(max-width:1100px){.staff-create{grid-template-columns:repeat(2,minmax(0,1fr))}.staff-create button{align-self:end}}@media(max-width:980px){.settings-layout{grid-template-columns:1fr}}@media(max-width:620px){.settings-grid,.staff-create{grid-template-columns:1fr}.settings-field.full{grid-column:auto}.settings-security{align-items:flex-start;flex-direction:column}.settings-security-actions{justify-content:flex-start}}
</style>
@endsection

@section('admin_content')
<section class="page-head">
    <div><h1 class="page-title">Cài đặt hệ thống</h1><p class="page-subtitle">Các thay đổi tại đây được lưu vào database và áp dụng ngay.</p></div>
</section>

@if($errors->any())
    <div class="alert alert-danger"><strong>Chưa thể lưu cài đặt.</strong><ul style="margin:8px 0 0 18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form class="settings-form" method="post" action="{{ route('admin.settings.update') }}">
    @csrf @method('PUT')
    <div class="settings-layout">
        <div class="settings-stack">
            <section class="panel">
                <div class="panel-head"><div><h2 class="panel-title">Thông tin cửa hàng</h2><p class="panel-sub">Dùng cho website, email và thông tin hỗ trợ khách hàng.</p></div></div>
                <div class="settings-grid">
                    <div class="settings-field"><label for="store_name">Tên thương hiệu</label><input class="settings-input" id="store_name" name="store_name" value="{{ old('store_name',$settings['store_name']) }}" required maxlength="120"></div>
                    <div class="settings-field"><label for="store_hotline">Hotline</label><input class="settings-input" id="store_hotline" name="store_hotline" value="{{ old('store_hotline',$settings['store_hotline']) }}" required maxlength="20"></div>
                    <div class="settings-field"><label for="store_email">Email CSKH</label><input class="settings-input" id="store_email" type="email" name="store_email" value="{{ old('store_email',$settings['store_email']) }}" required></div>
                    <div class="settings-field"><label for="display_timezone">Múi giờ hiển thị</label><select class="settings-input" id="display_timezone" name="display_timezone"><option value="Asia/Ho_Chi_Minh">Asia/Ho_Chi_Minh (UTC+7)</option></select></div>
                    <div class="settings-field full"><label for="store_address">Địa chỉ cửa hàng</label><textarea class="settings-textarea" id="store_address" name="store_address" required maxlength="500">{{ old('store_address',$settings['store_address']) }}</textarea></div>
                </div>
            </section>

            <section class="panel">
                <div class="panel-head"><div><h2 class="panel-title">Phí giao hàng và tồn kho</h2><p class="panel-sub">Các mức tiền này được ShippingFeeService sử dụng khi checkout.</p></div></div>
                <div class="settings-grid">
                    <div class="settings-field"><label for="shipping_free_threshold">Miễn phí giao TP.HCM từ</label><input class="settings-input" id="shipping_free_threshold" type="number" min="0" step="1000" name="shipping_free_threshold" value="{{ old('shipping_free_threshold',$settings['shipping_free_threshold']) }}"></div>
                    <div class="settings-field"><label for="shipping_default_fee">Phí mặc định toàn quốc</label><input class="settings-input" id="shipping_default_fee" type="number" min="0" step="1000" name="shipping_default_fee" value="{{ old('shipping_default_fee',$settings['shipping_default_fee']) }}"></div>
                    <div class="settings-field"><label for="shipping_remote_surcharge">Phụ phí khu vực đặc biệt</label><input class="settings-input" id="shipping_remote_surcharge" type="number" min="0" step="1000" name="shipping_remote_surcharge" value="{{ old('shipping_remote_surcharge',$settings['shipping_remote_surcharge']) }}"></div>
                    <div class="settings-field"><label for="low_stock_default_threshold">Ngưỡng tồn kho thấp mặc định</label><input class="settings-input" id="low_stock_default_threshold" type="number" min="0" name="low_stock_default_threshold" value="{{ old('low_stock_default_threshold',$settings['low_stock_default_threshold']) }}"></div>
                </div>
            </section>
        </div>

        <div class="settings-stack">
            <section class="panel">
                <div class="panel-head"><div><h2 class="panel-title">Phương thức thanh toán</h2><p class="panel-sub">Phải duy trì ít nhất một lựa chọn cho khách.</p></div></div>
                @foreach([
                    'payment_cod_enabled'=>['Thanh toán khi nhận hàng','Cho phép khách chọn COD.'],
                    'payment_bank_enabled'=>['Chuyển khoản ngân hàng','Hiển thị hướng dẫn chuyển khoản sau đặt hàng.'],
                    'payment_momo_enabled'=>['Ví MoMo sandbox','Bật luồng thanh toán thử nghiệm MoMo.'],
                ] as $key=>$copy)
                    <label class="settings-toggle"><span><strong>{{ $copy[0] }}</strong><span>{{ $copy[1] }}</span></span><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key,$settings[$key]))></label>
                @endforeach
            </section>

            <section class="panel">
                <div class="panel-head"><div><h2 class="panel-title">Thông báo tự động</h2><p class="panel-sub">Kiểm soát email nghiệp vụ và cảnh báo tồn kho.</p></div></div>
                @foreach([
                    'email_order_placed_enabled'=>['Email tiếp nhận đơn','Gửi ngay sau khi tạo đơn thành công.'],
                    'email_order_confirmed_enabled'=>['Email xác nhận đơn','Gửi khi hệ thống hoặc admin xác nhận đơn.'],
                    'email_order_cancelled_enabled'=>['Email hủy đơn','Thông báo kết quả hủy cho khách.'],
                    'low_stock_alert_enabled'=>['Cảnh báo tồn kho thấp','Cho phép tác vụ định kỳ gửi cảnh báo admin.'],
                ] as $key=>$copy)
                    <label class="settings-toggle"><span><strong>{{ $copy[0] }}</strong><span>{{ $copy[1] }}</span></span><input type="checkbox" name="{{ $key }}" value="1" @checked(old($key,$settings[$key]))></label>
                @endforeach
            </section>

            <section class="panel">
                <div class="settings-security">
                    <div><span class="setting-label">BẢO MẬT QUẢN TRỊ</span><h3 style="margin:6px 0 4px;font-size:16px">Xác thực hai lớp</h3><p class="panel-sub">Vai trò hiện tại: <span class="settings-role">{{ $adminRoles[$currentAdminRole] ?? $currentAdminRole }}</span></p></div>
                    <div class="settings-security-actions">
                        @if(auth()->user()->hasTwoFactorAuthentication())<span class="tag">Đã bật 2FA</span>@endif
                        <a class="btn btn-ghost" href="{{ route('admin.2fa.setup') }}"><i class="ri-shield-keyhole-line"></i>{{ auth()->user()->hasTwoFactorAuthentication() ? 'Quản lý 2FA' : 'Thiết lập 2FA' }}</a>
                        <a class="btn btn-ghost" href="{{ route('admin.security.password.edit') }}"><i class="ri-lock-password-line"></i>Đổi mật khẩu</a>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <div style="display:flex;justify-content:flex-end"><button class="btn btn-primary" type="submit"><i class="ri-save-3-line"></i>Lưu và áp dụng</button></div>
</form>

@if(auth()->user()->hasAdminPermission('staff.manage'))
<section class="panel" style="margin-top:14px">
    <div class="panel-head">
        <div><h2 class="panel-title">Nhân viên và phân quyền</h2><p class="panel-sub">Mỗi người dùng một tài khoản riêng; mọi thay đổi được ghi audit log.</p></div>
        <span class="tag">{{ $staffMembers->count() }} tài khoản</span>
    </div>

    <form class="staff-create" method="post" action="{{ route('admin.staff.store') }}">
        @csrf
        <div class="settings-field"><label>Họ tên</label><input class="settings-input" name="name" required maxlength="100"></div>
        <div class="settings-field"><label>Email</label><input class="settings-input" type="email" name="email" required></div>
        <div class="settings-field"><label>Vai trò</label><select class="settings-input" name="admin_role">@foreach($adminRoles as $role=>$label)<option value="{{ $role }}">{{ $label }}</option>@endforeach</select></div>
        <div class="settings-field"><label>Mật khẩu tạm thời</label><input class="settings-input" type="password" name="password" required autocomplete="new-password" placeholder="Tối thiểu 12 ký tự"></div>
        <button class="btn btn-primary" type="submit"><i class="ri-user-add-line"></i>Tạo</button>
    </form>

    <div class="table-wrap">
        <table class="staff-table">
            <thead><tr><th>Nhân viên</th><th>2FA</th><th>Đăng nhập gần nhất</th><th>Quyền và trạng thái</th></tr></thead>
            <tbody>
            @foreach($staffMembers as $staff)
                <tr>
                    <td><strong>{{ $staff->name }}</strong><br><small>{{ $staff->email }}</small></td>
                    <td><span class="tag">{{ $staff->hasTwoFactorAuthentication() ? 'Đã bật' : 'Chưa bật' }}</span></td>
                    <td>{{ $staff->last_login_at?->format('d/m/Y H:i') ?: 'Chưa có' }}</td>
                    <td>
                        <form method="post" action="{{ route('admin.staff.update', $staff) }}" style="display:flex;gap:7px;align-items:center;flex-wrap:wrap">
                            @csrf @method('PUT')
                            <select name="admin_role" aria-label="Vai trò của {{ $staff->name }}">@foreach($adminRoles as $role=>$label)<option value="{{ $role }}" @selected(($staff->admin_role ?: 'super_admin') === $role)>{{ $label }}</option>@endforeach</select>
                            <select name="account_status" aria-label="Trạng thái của {{ $staff->name }}">
                                <option value="active" @selected($staff->account_status === 'active')>Hoạt động</option>
                                <option value="suspended" @selected($staff->account_status === 'suspended')>Tạm ngưng</option>
                            </select>
                            <button class="btn btn-ghost" type="submit" @disabled($staff->is(auth()->user()))><i class="ri-save-line"></i>Lưu</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif
@endsection
