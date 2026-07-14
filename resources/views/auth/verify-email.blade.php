@extends('layouts.app')

@section('title', 'Xác minh email - Thế Giới Trái Cây')

@section('content')
<section class="verify-email-page" aria-labelledby="verify-email-title">
    <div class="verify-email-card">
        <div class="verify-email-icon" aria-hidden="true"><i class="fa fa-envelope-o"></i></div>
        <p class="verify-email-kicker">BẢO VỆ TÀI KHOẢN</p>
        <h1 id="verify-email-title">Xác minh email của bạn</h1>
        <p>Chúng tôi đã gửi liên kết xác minh đến:</p>
        <p class="verify-email-address">{{ $email }}</p>
        <p>Nhấp vào liên kết trong email để mở khóa hồ sơ, voucher và checkout.</p>

        @if (session('status'))
            <div class="verify-email-alert success" role="status">{{ session('status') }}</div>
        @endif

        @error('email')
            <div class="verify-email-alert error" role="alert">{{ $message }}</div>
        @enderror

        <form method="post" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="verify-email-button">
                <i class="fa fa-paper-plane" aria-hidden="true"></i>
                Gửi lại email xác minh
            </button>
        </form>

        <form method="post" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="verify-email-link">Đăng xuất để dùng tài khoản khác</button>
        </form>
    </div>
</section>
@endsection

@push('styles')
<style>
    .verify-email-page { min-height: 58vh; display: grid; place-items: center; padding: 48px 16px; background: #f4f9ef; }
    .verify-email-card { width: min(100%, 520px); padding: 36px; border: 1px solid #dbe7d2; border-radius: 8px; background: #fff; text-align: center; box-shadow: 0 18px 45px rgba(33, 67, 24, .09); }
    .verify-email-icon { width: 56px; height: 56px; margin: 0 auto 16px; display: grid; place-items: center; border-radius: 50%; color: #fff; background: #71b62b; font-size: 23px; }
    .verify-email-kicker { margin: 0 0 8px; color: #5d8f20; font-size: 12px; font-weight: 800; }
    .verify-email-card h1 { margin: 0 0 14px; color: #1c3518; font-size: 30px; }
    .verify-email-card p { color: #5d6859; line-height: 1.65; }
    .verify-email-address { margin: 10px 0; color: #1c3518 !important; font-weight: 800; overflow-wrap: anywhere; }
    .verify-email-alert { margin: 18px 0; padding: 12px 14px; border-radius: 6px; text-align: left; }
    .verify-email-alert.success { color: #245d20; background: #edf8e8; border: 1px solid #bfddb3; }
    .verify-email-alert.error { color: #9b2d20; background: #fff0ed; border: 1px solid #f0c0b8; }
    .verify-email-button { width: 100%; min-height: 46px; border: 0; border-radius: 6px; color: #fff; background: #67a923; font-weight: 800; cursor: pointer; }
    .verify-email-button:hover, .verify-email-button:focus-visible { background: #4e8817; outline: 3px solid rgba(103, 169, 35, .25); outline-offset: 2px; }
    .verify-email-link { margin-top: 18px; border: 0; color: #527e1f; background: transparent; text-decoration: underline; cursor: pointer; }
    @media (max-width: 575px) { .verify-email-card { padding: 28px 20px; } .verify-email-card h1 { font-size: 25px; } }
</style>
@endpush
