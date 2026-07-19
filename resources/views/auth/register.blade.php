@extends('layouts.app')

@section('title', 'Đăng ký - Thế Giới Trái Cây')

@section('content')
<section class="auth-screen">
    <div class="container">
        <div class="auth-grid">
            <div class="auth-intro">
                <div>
                    <div class="auth-kicker">
                        <i class="fa fa-leaf" aria-hidden="true"></i>
                        Thành viên mới
                    </div>
                    <h1 class="auth-title">Tạo tài khoản để mua trái cây nhanh hơn mỗi lần quay lại.</h1>
                    <p class="auth-copy">
                        Tài khoản khách hàng giúp theo dõi đơn, giữ giỏ hàng và nhận ưu đãi phù hợp với lịch sử mua sắm.
                    </p>

                    <div class="auth-points">
                        <div class="auth-point">
                            <i class="fa fa-truck" aria-hidden="true"></i>
                            <span>Thanh toán nhanh hơn ở những lần mua sau</span>
                        </div>
                        <div class="auth-point">
                            <i class="fa fa-history" aria-hidden="true"></i>
                            <span>Xem lại đơn hàng và sản phẩm đã mua</span>
                        </div>
                        <div class="auth-point">
                            <i class="fa fa-percent" aria-hidden="true"></i>
                            <span>Nhận khuyến mãi dành riêng cho thành viên</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-card-head">
                    <h1>Đăng ký</h1>
                    <p>Chỉ cần thông tin cơ bản, phần giao hàng sẽ nhập ở bước thanh toán.</p>
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

                <form method="post" action="{{ route('register.post') }}">
                    @csrf

                    <div class="auth-field">
                        <label for="name">Họ và tên</label>
                        <div class="auth-control">
                            <i class="fa fa-user-o" aria-hidden="true"></i>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" maxlength="100" required>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="register-email">Email</label>
                        <div class="auth-control">
                            <i class="fa fa-envelope-o" aria-hidden="true"></i>
                            <input id="register-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="register-password">Mật khẩu</label>
                        <div class="auth-control auth-password-control">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                            <input id="register-password" type="password" name="password" autocomplete="new-password" minlength="8" maxlength="72" data-password-strength required>
                            <button type="button" class="auth-password-toggle" data-password-toggle="register-password" aria-label="Hiện mật khẩu">
                                <i class="fa fa-eye-slash" aria-hidden="true"></i>
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
                        <label for="password_confirmation">Nhập lại mật khẩu</label>
                        <div class="auth-control auth-password-control">
                            <i class="fa fa-check-circle-o" aria-hidden="true"></i>
                            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" maxlength="72" required>
                            <button type="button" class="auth-password-toggle" data-password-toggle="password_confirmation" aria-label="Hiện mật khẩu">
                                <i class="fa fa-eye-slash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <label class="auth-check" style="margin-block-end: 18px;">
                        <input type="checkbox" name="terms" value="1" required>
                        <span>
                            Tôi đồng ý với
                            <a href="{{ route('page.terms') }}" class="auth-link">Điều khoản dịch vụ</a>
                            và
                            <a href="{{ route('page.privacy') }}" class="auth-link">Chính sách bảo mật</a>
                        </span>
                    </label>

                    <button type="submit" class="auth-submit">
                        <i class="fa fa-user-plus" aria-hidden="true"></i>
                        Tạo tài khoản
                    </button>
                </form>

                <div class="auth-social-block auth-social-block-bottom">
                    <div class="auth-separator">
                        <span>hoặc đăng ký nhanh</span>
                    </div>

                    <a href="{{ route('google.redirect') }}" class="auth-google-btn">
                        <span class="auth-google-mark" aria-hidden="true">G</span>
                        <span>Tiếp tục với Google</span>
                    </a>
                </div>

                <div class="auth-switch">
                    Đã có tài khoản?
                    <a href="{{ route('login') }}" class="auth-link">Đăng nhập</a>
                </div>

                <div class="auth-small">
                    Mật khẩu được mã hóa trước khi lưu. Số điện thoại và địa chỉ sẽ được xác nhận khi đặt hàng.
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
