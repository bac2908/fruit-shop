@extends('layouts.app')

@section('title', 'Đặt lại mật khẩu - Thế Giới Trái Cây')

@section('content')
<section class="auth-screen">
    <div class="container">
        <div class="auth-grid">
            <div class="auth-intro">
                <div>
                    <div class="auth-kicker">
                        <i class="fa fa-lock" aria-hidden="true"></i>
                        Mật khẩu mới
                    </div>
                    <h1 class="auth-title">Tạo mật khẩu mới đủ mạnh để bảo vệ tài khoản.</h1>
                    <p class="auth-copy">
                        Sau khi đặt lại, bạn có thể đăng nhập bằng mật khẩu mới và tiếp tục quản lý hồ sơ, địa chỉ giao hàng, đơn hàng và ưu đãi cá nhân.
                    </p>

                    <div class="auth-points">
                        <div class="auth-point">
                            <i class="fa fa-check-circle" aria-hidden="true"></i>
                            <span>Mật khẩu cần có ít nhất 8 ký tự</span>
                        </div>
                        <div class="auth-point">
                            <i class="fa fa-font" aria-hidden="true"></i>
                            <span>Bao gồm chữ hoa, chữ thường và số</span>
                        </div>
                        <div class="auth-point">
                            <i class="fa fa-history" aria-hidden="true"></i>
                            <span>Lịch sử đổi mật khẩu được ghi lại để audit bảo mật</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-card-head">
                    <h1>Đặt lại mật khẩu</h1>
                    <p>Nhập email tài khoản và mật khẩu mới của bạn.</p>
                </div>

                @if($errors->any())
                    <div class="auth-alert">
                        <ul class="auth-error-list">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="auth-field">
                        <label for="reset-email">Email</label>
                        <div class="auth-control">
                            <i class="fa fa-envelope-o" aria-hidden="true"></i>
                            <input id="reset-email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" maxlength="255" required autofocus>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="reset-password">Mật khẩu mới</label>
                        <div class="auth-control auth-password-control">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                            <input id="reset-password" type="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" data-password-strength required>
                            <button type="button" class="auth-password-toggle" data-password-toggle="reset-password" aria-label="Hiện mật khẩu">
                                <i class="fa fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div class="auth-strength" data-strength-meter aria-live="polite">
                            <div class="auth-strength-track" aria-hidden="true">
                                <span class="auth-strength-bar"></span>
                                <span class="auth-strength-bar"></span>
                                <span class="auth-strength-bar"></span>
                            </div>
                            <div class="auth-strength-text">Độ mạnh mật khẩu: <strong class="auth-strength-label">Chưa nhập</strong></div>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="reset-password-confirmation">Nhập lại mật khẩu mới</label>
                        <div class="auth-control auth-password-control">
                            <i class="fa fa-check-circle-o" aria-hidden="true"></i>
                            <input id="reset-password-confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="72" required>
                            <button type="button" class="auth-password-toggle" data-password-toggle="reset-password-confirmation" aria-label="Hiện mật khẩu">
                                <i class="fa fa-eye" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit">
                        <i class="fa fa-refresh" aria-hidden="true"></i>
                        Đặt lại mật khẩu
                    </button>
                </form>

                <div class="auth-switch">
                    Đã nhớ mật khẩu?
                    <a href="{{ route('login') }}" class="auth-link">Quay lại đăng nhập</a>
                </div>

                <div class="auth-small">
                    Sau khi đổi mật khẩu, các lần đăng nhập sau sẽ dùng mật khẩu mới. Tài khoản Google đã liên kết vẫn đăng nhập được bình thường.
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('styles')
    @include('auth.partials.auth-styles')
@endsection

@push('scripts')
    @include('auth.partials.auth-scripts')
@endpush
