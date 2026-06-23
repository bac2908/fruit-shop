@extends('layouts.app')

@section('title', 'Quên mật khẩu - Thế Giới Trái Cây')

@section('content')
<section class="auth-screen">
    <div class="container">
        <div class="auth-grid">
            <div class="auth-intro">
                <div>
                    <div class="auth-kicker">
                        <i class="fa fa-key" aria-hidden="true"></i>
                        Khôi phục tài khoản
                    </div>
                    <h1 class="auth-title">Lấy lại quyền truy cập tài khoản mua hàng của bạn.</h1>
                    <p class="auth-copy">
                        Nhập email đã đăng ký, hệ thống sẽ gửi một liên kết an toàn để bạn đặt lại mật khẩu mới.
                    </p>

                    <div class="auth-points">
                        <div class="auth-point">
                            <i class="fa fa-clock-o" aria-hidden="true"></i>
                            <span>Liên kết đặt lại mật khẩu chỉ có hiệu lực trong 60 phút</span>
                        </div>
                        <div class="auth-point">
                            <i class="fa fa-shield" aria-hidden="true"></i>
                            <span>Token được mã hóa và không lưu mật khẩu dạng thường</span>
                        </div>
                        <div class="auth-point">
                            <i class="fa fa-envelope-o" aria-hidden="true"></i>
                            <span>Vì bảo mật, hệ thống không tiết lộ email có tồn tại hay không</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="auth-card">
                <div class="auth-card-head">
                    <h1>Quên mật khẩu</h1>
                    <p>Chúng tôi sẽ gửi liên kết đặt lại mật khẩu đến email tài khoản của bạn.</p>
                </div>

                @if(session('status'))
                    <div class="auth-alert auth-alert-success">
                        {{ session('status') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="auth-alert">
                        <ul class="auth-error-list">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="post" action="{{ route('password.email') }}">
                    @csrf

                    <div class="auth-field">
                        <label for="forgot-email">Email</label>
                        <div class="auth-control">
                            <i class="fa fa-envelope-o" aria-hidden="true"></i>
                            <input id="forgot-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" required autofocus>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit">
                        <i class="fa fa-paper-plane" aria-hidden="true"></i>
                        Gửi liên kết đặt lại
                    </button>
                </form>

                <div class="auth-switch">
                    Nhớ mật khẩu rồi?
                    <a href="{{ route('login') }}" class="auth-link">Quay lại đăng nhập</a>
                </div>

                <div class="auth-small">
                    Nếu bạn đăng nhập bằng Google, bạn vẫn có thể dùng Google để vào tài khoản mà không cần đặt mật khẩu riêng.
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('styles')
    @include('auth.partials.auth-styles')
@endsection
