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
                        Hồ sơ khách hàng giúp lưu địa chỉ giao hàng, theo dõi đơn và nhận ưu đãi phù hợp với lịch sử mua sắm.
                    </p>

                    <div class="auth-points">
                        <div class="auth-point">
                            <i class="fa fa-truck" aria-hidden="true"></i>
                            <span>Lưu địa chỉ nhận hàng thường dùng</span>
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
                    <p>Tạo tài khoản khách hàng mới.</p>
                </div>

                @if($errors->any())
                    <div class="auth-alert">{{ $errors->first() }}</div>
                @endif

                <form method="post" action="#">
                    @csrf

                    <div class="auth-field">
                        <label for="name">Họ và tên</label>
                        <div class="auth-control">
                            <i class="fa fa-user-o" aria-hidden="true"></i>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" autocomplete="name" required>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="register-email">Email</label>
                        <div class="auth-control">
                            <i class="fa fa-envelope-o" aria-hidden="true"></i>
                            <input id="register-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="phone">Số điện thoại</label>
                        <div class="auth-control">
                            <i class="fa fa-phone" aria-hidden="true"></i>
                            <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel">
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="address">Địa chỉ giao hàng</label>
                        <div class="auth-control">
                            <i class="fa fa-map-marker" aria-hidden="true"></i>
                            <input id="address" type="text" name="address" value="{{ old('address') }}" autocomplete="street-address">
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="register-password">Mật khẩu</label>
                        <div class="auth-control">
                            <i class="fa fa-lock" aria-hidden="true"></i>
                            <input id="register-password" type="password" name="password" autocomplete="new-password" required>
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="password_confirmation">Nhập lại mật khẩu</label>
                        <div class="auth-control">
                            <i class="fa fa-check-circle-o" aria-hidden="true"></i>
                            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
                        </div>
                    </div>

                    <label class="auth-check" style="margin-bottom: 18px;">
                        <input type="checkbox" name="terms" value="1" required>
                        <span>Tôi đồng ý với điều khoản dịch vụ và chính sách bảo mật</span>
                    </label>

                    <button type="submit" class="auth-submit">
                        <i class="fa fa-user-plus" aria-hidden="true"></i>
                        Tạo tài khoản
                    </button>
                </form>

                <div class="auth-switch">
                    Đã có tài khoản?
                    <a href="{{ route('login') }}" class="auth-link">Đăng nhập</a>
                </div>

                <div class="auth-small">
                    Bước tiếp theo sẽ nối form này với backend tạo user, mã hóa mật khẩu và tự đăng nhập.
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('styles')
    @include('auth.partials.auth-styles')
@endsection
