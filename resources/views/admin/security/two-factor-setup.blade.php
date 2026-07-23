@extends('layouts.admin')

@section('title', 'Xác thực hai lớp | FruitShop Admin')

@section('head')
<style>
    .security-shell { max-width: 820px; margin: 0 auto; }
    .security-step { display: grid; grid-template-columns: 38px minmax(0, 1fr); gap: 12px; padding: 14px 0; border-bottom: 1px solid var(--admin-line); }
    .security-step:last-child { border: 0; }
    .step-no { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 50%; color: #fff; background: var(--admin-primary); font-weight: 800; }
    .secret-code, .recovery-code { font-family: Consolas, monospace; letter-spacing: .08em; overflow-wrap: anywhere; }
    .secret-code { display: block; padding: 12px; border: 1px dashed #95bfa2; border-radius: 8px; background: #f5fbf6; margin: 8px 0; }
    .settings-input { width: 100%; height: 43px; margin-top: 6px; border: 1px solid var(--admin-line); border-radius: 8px; padding: 0 11px; background: #fff; color: var(--admin-ink); font: inherit; }
    .settings-note { padding: 12px; border: 1px solid #d9e6d5; border-radius: 8px; background: #f7faf5; color: #526458; font-size: 12px; line-height: 1.6; }
    .code-input { width: 180px; height: 44px; border: 1px solid var(--admin-line); border-radius: 8px; padding: 0 12px; font: 700 18px Consolas, monospace; letter-spacing: .15em; }
    .recovery-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-top: 12px; }
    .recovery-code { padding: 9px; border: 1px solid var(--admin-line); border-radius: 7px; background: #fff; text-align: center; }
    @media(max-width:600px){.recovery-grid{grid-template-columns:1fr}}
</style>
@endsection

@section('admin_content')
<div class="security-shell">
    <section class="page-head">
        <div><h1 class="page-title">Xác thực hai lớp</h1><p class="page-subtitle">Bảo vệ tài khoản quản trị bằng mã TOTP thay đổi mỗi 30 giây.</p></div>
        <a class="btn btn-ghost" href="{{ route('admin.settings') }}"><i class="ri-arrow-left-line"></i>Cài đặt</a>
    </section>

    @if(session('two_factor_recovery_codes'))
        <section class="panel" style="margin-bottom:14px;border-color:#e0b03f">
            <h2 class="panel-title">Lưu mã khôi phục ngay</h2>
            <p class="panel-sub">Mỗi mã chỉ dùng một lần. Đây là lần duy nhất hệ thống hiển thị các mã này.</p>
            <div class="recovery-grid">
                @foreach(session('two_factor_recovery_codes') as $code)<span class="recovery-code">{{ $code }}</span>@endforeach
            </div>
        </section>
    @endif

    @if(auth()->user()->hasTwoFactorAuthentication())
        <section class="panel">
            <div class="panel-head">
                <div><h2 class="panel-title">2FA đang hoạt động</h2><p class="panel-sub">Đã bật lúc {{ auth()->user()->two_factor_confirmed_at?->format('d/m/Y H:i') }}.</p></div>
                <span class="tag">Đang bảo vệ</span>
            </div>
            <div class="settings-note" style="margin-bottom:14px">Chỉ tắt khi bạn vẫn kiểm soát tài khoản. Hệ thống yêu cầu đồng thời mật khẩu và mã TOTP.</div>
            <form method="post" action="{{ route('admin.2fa.disable') }}" style="display:grid;gap:10px;max-width:420px">
                @csrf @method('DELETE')
                <label>Mật khẩu hiện tại<input class="settings-input" type="password" name="current_password" required autocomplete="current-password"></label>
                <label>Mã TOTP<input class="settings-input" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" required autocomplete="one-time-code"></label>
                <button class="btn btn-ghost" type="submit"><i class="ri-shield-cross-line"></i>Tắt xác thực hai lớp</button>
            </form>
        </section>
    @else
        <section class="panel">
            <div class="security-step">
                <span class="step-no">1</span>
                <div><strong>Mở ứng dụng Authenticator</strong><p class="panel-sub">Dùng Google Authenticator, Microsoft Authenticator, 1Password hoặc ứng dụng TOTP tương thích.</p></div>
            </div>
            <div class="security-step">
                <span class="step-no">2</span>
                <div>
                    <strong>Thêm khóa thiết lập</strong>
                    <span class="secret-code">{{ $secret }}</span>
                    <a href="{{ $provisioningUri }}" class="btn btn-ghost"><i class="ri-smartphone-line"></i>Mở trong ứng dụng xác thực</a>
                    <p class="panel-sub">Loại khóa: dựa trên thời gian. Không gửi khóa này cho bất kỳ ai.</p>
                </div>
            </div>
            <div class="security-step">
                <span class="step-no">3</span>
                <form method="post" action="{{ route('admin.2fa.confirm') }}">
                    @csrf
                    <strong>Xác nhận mã 6 số</strong>
                    <p class="panel-sub">Nhập mã đang hiển thị trong ứng dụng để hoàn tất.</p>
                    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                        <input class="code-input" name="code" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus autocomplete="one-time-code">
                        <button class="btn btn-primary" type="submit"><i class="ri-shield-check-line"></i>Bật 2FA</button>
                    </div>
                    @error('code')<small style="color:var(--admin-danger)">{{ $message }}</small>@enderror
                </form>
            </div>
        </section>
    @endif
</div>
@endsection
