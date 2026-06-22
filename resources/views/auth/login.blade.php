@extends('layouts.app')

@section('title', 'Đăng nhập - Thế Giới Trái Cây')

@section('content')
<section class="auth-screen">
    <div class="container">
        <div class="auth-grid">
            <div class="auth-intro">
                <div>
                    <div class="auth-kicker">
                        <i class="fa fa-shield" aria-hidden="true"></i>
                        Tài khoản khách hàng
                    </div>
                    <h1 class="auth-title">Theo dõi đơn hàng và nhận ưu đãi trái cây theo mùa.</h1>
                    <p class="auth-copy">
                        Đăng nhập để lưu thông tin giao hàng, xem lịch sử mua hàng và dùng nhanh các mã khuyến mãi đang hoạt động.
                    </p>

                    <div class="auth-points">
                        <div class="auth-point">
                            <i class="fa fa-shopping-bag" aria-hidden="true"></i>
                            <span>Quản lý đơn hàng và trạng thái giao hàng</span>
                        </div>
                        <div class="auth-point">
                            <i class="fa fa-gift" aria-hidden="true"></i>
                            <span>Lưu mã ưu đãi và chương trình quà tặng</span>
                        </div>
                        <div class="auth-point">
                            <i class="fa fa-user" aria-hidden="true"></i>
                            <span>Dùng lại thông tin nhận hàng cho lần mua tiếp theo</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-card-head">
                    <h1>Đăng nhập</h1>
                    <p>Chào mừng bạn quay lại Thế Giới Trái Cây.</p>
                </div>

                @if($errors->any())
                    <div class="auth-alert">{{ $errors->first() }}</div>
                @endif

                <form method="post" action="{{ route('login.post') }}">
                    @csrf

                    <div class="auth-field">
                        <label for="email">Email</label>
                        <div class="auth-control">
                            <i class="fa fa-envelope-o" aria-hidden="true"></i>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="password">Mật khẩu</label>
                        <div class="auth-control">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                            <input id="password" type="password" name="password" autocomplete="current-password" required>
                        </div>
                    </div>

                    <div class="auth-row">
                        <label class="auth-check">
                            <input type="checkbox" name="remember" value="1">
                            <span>Ghi nhớ đăng nhập</span>
                        </label>
                        <a href="#" class="auth-link">Quên mật khẩu?</a>
                    </div>

                    <button type="submit" class="auth-submit">
                        <i class="fa fa-sign-in" aria-hidden="true"></i>
                        Đăng nhập
                    </button>
                </form>

                <div class="auth-switch">
                    Chưa có tài khoản?
                    <a href="{{ route('register') }}" class="auth-link">Tạo tài khoản mới</a>
                </div>

                <div class="auth-small">
                    Tài khoản admin sau khi đăng nhập sẽ được chuyển vào khu vực quản trị.
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('styles')
    @include('auth.partials.auth-styles')
@endsection
