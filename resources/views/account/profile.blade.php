@extends('layouts.app')

@php
    $avatarUrl = null;

    if ($user->avatar_url) {
        $avatarUrl = \Illuminate\Support\Str::startsWith($user->avatar_url, ['http://', 'https://', '//'])
            ? $user->avatar_url
            : asset($user->avatar_url);
    }

    $initial = mb_substr(trim((string) $user->name), 0, 1, 'UTF-8') ?: 'U';
    $genderOptions = [
        'unspecified' => 'Chưa chọn',
        'male' => 'Nam',
        'female' => 'Nữ',
        'other' => 'Khác',
    ];
    $statusLabels = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'done' => 'Hoàn tất',
        'cancelled' => 'Đã hủy',
    ];

    $defaultAddress = $user->addresses->firstWhere('is_default', true) ?: $user->addresses->first();
    $latestOrder = $orders->first();
    $voucherCount = $personalVouchers->count() + $availableCoupons->count();
    $profileChecks = collect([
        filled($user->name),
        filled($user->email),
        filled($user->phone),
        filled($user->birthday),
        filled($user->gender),
        filled($user->avatar_url),
        (bool) $defaultAddress,
    ]);
    $profilePercent = (int) round(($profileChecks->filter()->count() / max(1, $profileChecks->count())) * 100);
    $activeTab = $errors->any()
        ? (old('_account_form') === 'address' ? 'addresses' : 'profile')
        : request('tab', 'overview');
@endphp

@section('title', 'Hồ sơ của tôi - Thế Giới Trái Cây')

@section('content')
<section class="account-page">
    <div class="container">
        <div class="account-shell">
            <header class="account-hero">
                <div class="account-person">
                    <div class="account-avatar">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $user->name }}">
                        @else
                            <span>{{ $initial }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="account-kicker">Tài khoản khách hàng</span>
                        <h1>{{ $user->name }}</h1>
                        <p>{{ $user->email }}</p>
                    </div>
                </div>

                <div class="account-summary">
                    <div>
                        <strong>{{ number_format((int) $orderSummary['total_orders']) }}</strong>
                        <span>Đơn hàng</span>
                    </div>
                    <div>
                        <strong>{{ number_format((int) $membership['points']) }}</strong>
                        <span>Điểm thưởng</span>
                    </div>
                    <div>
                        <strong>{{ $membership['tier'] }}</strong>
                        <span>Thành viên</span>
                    </div>
                </div>
            </header>

            @if($errors->any())
                <div class="account-alert account-alert-error">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <nav class="account-tabs" aria-label="Khu vực tài khoản">
                <button type="button" class="account-tab {{ $activeTab === 'overview' ? 'is-active' : '' }}" data-account-tab="overview">
                    <i class="fa fa-dashboard" aria-hidden="true"></i>
                    Tổng quan
                </button>
                <button type="button" class="account-tab {{ $activeTab === 'profile' ? 'is-active' : '' }}" data-account-tab="profile">
                    <i class="fa fa-user-o" aria-hidden="true"></i>
                    Hồ sơ
                </button>
                <button type="button" class="account-tab {{ $activeTab === 'addresses' ? 'is-active' : '' }}" data-account-tab="addresses">
                    <i class="fa fa-map-marker" aria-hidden="true"></i>
                    Địa chỉ
                </button>
                <button type="button" class="account-tab {{ $activeTab === 'orders' ? 'is-active' : '' }}" data-account-tab="orders">
                    <i class="fa fa-shopping-bag" aria-hidden="true"></i>
                    Đơn hàng
                </button>
                <button type="button" class="account-tab {{ $activeTab === 'products' ? 'is-active' : '' }}" data-account-tab="products">
                    <i class="fa fa-heart-o" aria-hidden="true"></i>
                    Sản phẩm
                </button>
                <button type="button" class="account-tab {{ $activeTab === 'vouchers' ? 'is-active' : '' }}" data-account-tab="vouchers">
                    <i class="fa fa-ticket" aria-hidden="true"></i>
                    Voucher
                </button>
            </nav>

            <div class="account-tab-panel {{ $activeTab === 'overview' ? 'is-active' : '' }}" data-account-panel="overview">
                <div class="account-overview-grid">
                    <article class="account-card account-card-highlight">
                        <div class="account-card-head">
                            <h2>Hồ sơ của bạn</h2>
                            <span>{{ $profilePercent }}%</span>
                        </div>
                        <div class="account-progress" aria-hidden="true">
                            <span style="width: {{ $profilePercent }}%"></span>
                        </div>
                        <p>{{ $profilePercent >= 85 ? 'Thông tin đã khá đầy đủ.' : 'Bổ sung số điện thoại, avatar hoặc địa chỉ để đặt hàng nhanh hơn.' }}</p>
                        <button type="button" class="account-btn account-btn-light" data-account-tab="profile">Cập nhật hồ sơ</button>
                    </article>

                    <article class="account-card">
                        <h2>Địa chỉ mặc định</h2>
                        @if($defaultAddress)
                            <strong>{{ $defaultAddress->recipient_name }}</strong>
                            <p>{{ $defaultAddress->phone }}</p>
                            <p>{{ $defaultAddress->full_address }}</p>
                        @else
                            <p>Bạn chưa lưu địa chỉ giao hàng.</p>
                            <button type="button" class="account-link-button" data-account-tab="addresses">Thêm địa chỉ</button>
                        @endif
                    </article>

                    <article class="account-card">
                        <h2>Đơn gần nhất</h2>
                        @if($latestOrder)
                            <strong>{{ $latestOrder->code }}</strong>
                            <p>{{ number_format((int) $latestOrder->total, 0, ',', '.') }}₫</p>
                            <p>{{ $statusLabels[$latestOrder->status] ?? $latestOrder->status }}</p>
                        @else
                            <p>Chưa có đơn hàng nào.</p>
                            <a href="{{ route('products.index') }}" class="account-link-button">Mua sản phẩm</a>
                        @endif
                    </article>

                    <article class="account-card">
                        <h2>Thành viên {{ $membership['tier'] }}</h2>
                        <p>Đã chi tiêu {{ number_format((int) $orderSummary['total_spent'], 0, ',', '.') }}₫.</p>
                        @if($membership['next_tier'])
                            <p>Còn {{ number_format((int) $membership['remaining_to_next_tier'], 0, ',', '.') }}₫ để lên {{ $membership['next_tier'] }}.</p>
                        @else
                            <p>Bạn đang ở hạng cao nhất hiện tại.</p>
                        @endif
                    </article>
                </div>

                <div class="account-compact-grid">
                    <section class="account-panel">
                        <div class="account-panel-head">
                            <h2>Đơn hàng gần đây</h2>
                            <button type="button" class="account-link-button" data-account-tab="orders">Xem tất cả</button>
                        </div>
                        <div class="account-list">
                            @forelse($orders->take(3) as $order)
                                <a href="{{ route('checkout.thankyou', ['code' => $order->code, 'token' => $order->public_token]) }}" class="account-row">
                                    <span>{{ $order->code }}</span>
                                    <strong>{{ number_format((int) $order->total, 0, ',', '.') }}₫</strong>
                                </a>
                            @empty
                                <div class="account-empty">Chưa có đơn hàng.</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="account-panel">
                        <div class="account-panel-head">
                            <h2>Mã đang dùng được</h2>
                            <button type="button" class="account-link-button" data-account-tab="vouchers">Xem mã</button>
                        </div>
                        <div class="account-list">
                            @forelse($availableCoupons->take(3) as $coupon)
                                <div class="account-row">
                                    <span>{{ $coupon->code }}</span>
                                    <strong>{{ $coupon->title }}</strong>
                                </div>
                            @empty
                                <div class="account-empty">Chưa có mã phù hợp.</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>

            <div class="account-tab-panel {{ $activeTab === 'profile' ? 'is-active' : '' }}" data-account-panel="profile">
                <section class="account-panel">
                    <div class="account-panel-head">
                        <div>
                            <h2>Hồ sơ cá nhân</h2>
                            <p>Cập nhật thông tin cần cho đặt hàng và chăm sóc sau mua.</p>
                        </div>
                    </div>

                    <form method="post" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="account-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_account_form" value="profile">

                        <div class="account-form-grid">
                            <div class="account-field">
                                <label for="name">Họ và tên</label>
                                <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" maxlength="100" required>
                            </div>
                            <div class="account-field">
                                <label for="email">Email</label>
                                <input id="email" type="email" value="{{ $user->email }}" disabled>
                            </div>
                            <div class="account-field">
                                <label for="phone">Số điện thoại</label>
                                <input id="phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="0912345678 hoặc +84912345678">
                            </div>
                            <div class="account-field">
                                <label for="birthday">Ngày sinh</label>
                                <input id="birthday" type="date" name="birthday" value="{{ old('birthday', optional($user->birthday)->format('Y-m-d')) }}">
                            </div>
                            <div class="account-field">
                                <label for="gender">Giới tính</label>
                                <select id="gender" name="gender">
                                    @foreach($genderOptions as $value => $label)
                                        <option value="{{ $value }}" {{ old('gender', $user->gender ?: 'unspecified') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="account-field">
                                <label for="avatar">Avatar</label>
                                <input id="avatar" type="file" name="avatar" accept="image/png,image/jpeg,image/webp">
                                <small>JPG, PNG hoặc WebP, tối đa 2MB.</small>
                            </div>
                        </div>

                        <div class="account-checks">
                            <label>
                                <input type="checkbox" name="notify_order_status" value="1" {{ old('notify_order_status', $user->notify_order_status ?? true) ? 'checked' : '' }}>
                                Nhận thông báo trạng thái đơn hàng
                            </label>
                            <label>
                                <input type="checkbox" name="notify_promotions" value="1" {{ old('notify_promotions', $user->notify_promotions ?? false) ? 'checked' : '' }}>
                                Nhận khuyến mãi và voucher cá nhân
                            </label>
                        </div>

                        <button type="submit" class="account-btn account-btn-primary">Lưu hồ sơ</button>
                    </form>
                </section>
            </div>

            <div class="account-tab-panel {{ $activeTab === 'addresses' ? 'is-active' : '' }}" data-account-panel="addresses">
                <section class="account-panel">
                    <div class="account-panel-head">
                        <div>
                            <h2>Địa chỉ giao hàng</h2>
                            <p>Chỉ lưu thông tin cần thiết để checkout nhanh hơn.</p>
                        </div>
                    </div>

                    <div class="account-address-grid">
                        @forelse($user->addresses as $address)
                            <article class="account-address">
                                <div>
                                    <h3>{{ $address->recipient_name }}</h3>
                                    <p>{{ $address->phone }}</p>
                                    <p>{{ $address->full_address }}</p>
                                    @if($address->is_default)
                                        <span class="account-chip">Mặc định</span>
                                    @endif
                                </div>
                                <div class="account-actions">
                                    @unless($address->is_default)
                                        <form method="post" action="{{ route('account.addresses.default', $address) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="account-link-button">Đặt mặc định</button>
                                        </form>
                                    @endunless
                                    <form method="post" action="{{ route('account.addresses.destroy', $address) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="account-link-button is-danger">Xóa</button>
                                    </form>
                                </div>
                            </article>
                        @empty
                            <div class="account-empty">Bạn chưa có địa chỉ giao hàng nào.</div>
                        @endforelse
                    </div>

                    <form method="post" action="{{ route('account.addresses.store') }}" class="account-form account-subform">
                        @csrf
                        <input type="hidden" name="_account_form" value="address">
                        <h3>Thêm địa chỉ mới</h3>
                        <div class="account-form-grid">
                            <div class="account-field">
                                <label for="recipient_name">Người nhận</label>
                                <input id="recipient_name" type="text" name="recipient_name" value="{{ old('recipient_name', $user->name) }}" maxlength="120" required>
                            </div>
                            <div class="account-field">
                                <label for="address_phone">Số điện thoại nhận hàng</label>
                                <input id="address_phone" type="tel" name="phone" value="{{ old('phone', $user->phone) }}" required>
                            </div>
                            <div class="account-field account-field-wide">
                                <label for="address_line">Địa chỉ cụ thể</label>
                                <input id="address_line" type="text" name="address_line" value="{{ old('address_line') }}" maxlength="255" required>
                            </div>
                            <div class="account-field">
                                <label for="ward">Phường/Xã</label>
                                <input id="ward" type="text" name="ward" value="{{ old('ward') }}" maxlength="120">
                            </div>
                            <div class="account-field">
                                <label for="district">Quận/Huyện</label>
                                <input id="district" type="text" name="district" value="{{ old('district') }}" maxlength="120">
                            </div>
                            <div class="account-field">
                                <label for="province">Tỉnh/Thành</label>
                                <input id="province" type="text" name="province" value="{{ old('province') }}" maxlength="120">
                            </div>
                        </div>
                        <label class="account-checkbox">
                            <input type="checkbox" name="is_default" value="1">
                            Đặt làm địa chỉ mặc định
                        </label>
                        <button type="submit" class="account-btn account-btn-secondary">Thêm địa chỉ</button>
                    </form>
                </section>
            </div>

            <div class="account-tab-panel {{ $activeTab === 'orders' ? 'is-active' : '' }}" data-account-panel="orders">
                <section class="account-panel">
                    <div class="account-panel-head">
                        <div>
                            <h2>Lịch sử đơn hàng</h2>
                            <p>{{ number_format((int) $orderSummary['active_orders']) }} đơn đang xử lý, {{ number_format((int) $orderSummary['completed_orders']) }} đơn đã hoàn tất.</p>
                        </div>
                    </div>

                    <div class="account-list">
                        @forelse($orders as $order)
                            <a href="{{ route('checkout.thankyou', ['code' => $order->code, 'token' => $order->public_token]) }}" class="account-row account-row-large">
                                <span>
                                    <strong>{{ $order->code }}</strong>
                                    <small>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}</small>
                                </span>
                                <span>
                                    <strong>{{ number_format((int) $order->total, 0, ',', '.') }}₫</strong>
                                    <small>{{ $statusLabels[$order->status] ?? $order->status }}</small>
                                </span>
                            </a>
                        @empty
                            <div class="account-empty">Bạn chưa có đơn hàng nào.</div>
                        @endforelse
                    </div>
                </section>
            </div>

            <div class="account-tab-panel {{ $activeTab === 'products' ? 'is-active' : '' }}" data-account-panel="products">
                <div class="account-compact-grid">
                    <section class="account-panel">
                        <div class="account-panel-head">
                            <h2>Sản phẩm yêu thích</h2>
                            <span>{{ $wishlistItems->count() }}</span>
                        </div>
                        <div class="account-product-grid">
                            @forelse($wishlistItems as $item)
                                <article class="account-product">
                                    <a href="{{ route('products.show', $item->product->slug) }}">
                                        <img src="{{ $item->product->primary_image_url }}" alt="{{ $item->product->name }}">
                                        <strong>{{ $item->product->name }}</strong>
                                        <span>{{ number_format((int) $item->product->actual_price, 0, ',', '.') }}₫</span>
                                    </a>
                                    <form method="post" action="{{ route('account.wishlist.remove', $item) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="account-link-button is-danger">Bỏ lưu</button>
                                    </form>
                                </article>
                            @empty
                                <div class="account-empty">Chưa có sản phẩm yêu thích.</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="account-panel">
                        <div class="account-panel-head">
                            <h2>Đã xem gần đây</h2>
                            <span>{{ $recentViews->count() }}</span>
                        </div>
                        <div class="account-product-grid">
                            @forelse($recentViews as $view)
                                <article class="account-product">
                                    <a href="{{ route('products.show', $view->product->slug) }}">
                                        <img src="{{ $view->product->primary_image_url }}" alt="{{ $view->product->name }}">
                                        <strong>{{ $view->product->name }}</strong>
                                        <span>Xem {{ number_format((int) $view->view_count) }} lần</span>
                                    </a>
                                </article>
                            @empty
                                <div class="account-empty">Chưa có sản phẩm đã xem.</div>
                            @endforelse
                        </div>
                    </section>
                </div>
            </div>

            <div class="account-tab-panel {{ $activeTab === 'vouchers' ? 'is-active' : '' }}" data-account-panel="vouchers">
                <section class="account-panel">
                    <div class="account-panel-head">
                        <div>
                            <h2>Voucher</h2>
                            <p>{{ number_format($voucherCount) }} mã có thể xem trong tài khoản.</p>
                        </div>
                    </div>

                    @if($personalVouchers->isNotEmpty())
                        <h3 class="account-section-title">Voucher cá nhân</h3>
                        <div class="account-voucher-grid">
                            @foreach($personalVouchers as $voucher)
                                <div class="account-voucher">
                                    <strong>{{ $voucher->coupon->code }}</strong>
                                    <span>{{ $voucher->coupon->title }}</span>
                                    <small>Hết hạn: {{ $voucher->expires_at ? $voucher->expires_at->format('d/m/Y') : 'Theo mã gốc' }}</small>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="account-empty">Chưa có voucher cá nhân được gán riêng.</div>
                    @endif

                    @if($availableCoupons->isNotEmpty())
                        <h3 class="account-section-title">Mã đang hoạt động</h3>
                        <div class="account-voucher-grid">
                            @foreach($availableCoupons as $coupon)
                                <div class="account-voucher">
                                    <strong>{{ $coupon->code }}</strong>
                                    <span>{{ $coupon->title }}</span>
                                    <small>{{ $coupon->description ?: 'Áp dụng khi đủ điều kiện đơn hàng.' }}</small>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if($usedCoupons->isNotEmpty())
                        <h3 class="account-section-title">Mã đã dùng gần đây</h3>
                        <div class="account-list">
                            @foreach($usedCoupons as $usage)
                                <div class="account-row">
                                    <span>{{ $usage->coupon_code }}</span>
                                    <strong>-{{ number_format((int) $usage->discount_total, 0, ',', '.') }}₫</strong>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    .account-page {
        background: linear-gradient(180deg, #f7fbf2 0%, #edf7e8 100%);
        padding: 28px 0 52px;
    }

    .account-shell {
        margin: 0 auto;
        max-width: 1060px;
    }

    .account-hero,
    .account-tabs,
    .account-card,
    .account-panel {
        background: #fff;
        border: 1px solid #dfe9d8;
        border-radius: 8px;
        box-shadow: 0 12px 28px rgba(43, 72, 26, 0.08);
    }

    .account-hero {
        align-items: center;
        display: flex;
        gap: 20px;
        justify-content: space-between;
        margin-bottom: 14px;
        padding: 18px;
    }

    .account-person {
        align-items: center;
        display: flex;
        gap: 14px;
        min-width: 0;
    }

    .account-avatar {
        align-items: center;
        background: #69aa23;
        border-radius: 50%;
        color: #fff;
        display: flex;
        flex: 0 0 64px;
        font-size: 28px;
        font-weight: 800;
        height: 64px;
        justify-content: center;
        overflow: hidden;
        width: 64px;
    }

    .account-avatar img {
        height: 100%;
        object-fit: cover;
        width: 100%;
    }

    .account-kicker {
        color: #6b8d25;
        display: block;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: .04em;
        text-transform: uppercase;
    }

    .account-hero h1,
    .account-card h2,
    .account-panel h2,
    .account-panel h3 {
        color: #203418;
        font-family: Manrope, Arial, sans-serif;
        font-weight: 800;
        margin: 0;
    }

    .account-hero h1 {
        font-size: 26px;
        line-height: 1.2;
        margin-top: 3px;
    }

    .account-hero p,
    .account-card p,
    .account-panel p,
    .account-empty,
    .account-row small {
        color: #66745f;
    }

    .account-hero p,
    .account-card p,
    .account-panel p {
        line-height: 1.55;
        margin: 5px 0 0;
    }

    .account-summary {
        display: grid;
        gap: 8px;
        grid-template-columns: repeat(3, minmax(88px, 1fr));
    }

    .account-summary div {
        background: #f5faef;
        border: 1px solid #dcebd0;
        border-radius: 8px;
        padding: 11px;
        text-align: center;
    }

    .account-summary strong {
        color: #28401d;
        display: block;
        font-size: 18px;
        font-weight: 800;
    }

    .account-summary span {
        color: #65745e;
        font-size: 12px;
        font-weight: 700;
    }

    .account-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 14px;
        overflow-x: auto;
        padding: 8px;
    }

    .account-tab {
        align-items: center;
        background: transparent;
        border: 0;
        border-radius: 8px;
        color: #3d542f;
        display: inline-flex;
        flex: 0 0 auto;
        font-weight: 800;
        gap: 7px;
        min-height: 38px;
        padding: 0 12px;
    }

    .account-tab:hover,
    .account-tab.is-active {
        background: #6fae25;
        color: #fff;
    }

    .account-tab-panel {
        display: none;
    }

    .account-tab-panel.is-active {
        display: block;
    }

    .account-alert {
        border-radius: 8px;
        font-weight: 700;
        margin-bottom: 14px;
        padding: 12px 16px;
    }

    .account-alert ul {
        margin: 0;
        padding-left: 18px;
    }

    .account-alert-error {
        background: #fff3f0;
        border: 1px solid #ffd3ca;
        color: #9c341f;
    }

    .account-overview-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        margin-bottom: 14px;
    }

    .account-compact-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .account-card,
    .account-panel {
        padding: 18px;
    }

    .account-card {
        min-height: 150px;
    }

    .account-card-highlight {
        background: linear-gradient(135deg, #6fae25, #4f8616);
        color: #fff;
    }

    .account-card-highlight h2,
    .account-card-highlight p,
    .account-card-highlight .account-card-head span {
        color: #fff;
    }

    .account-card-head,
    .account-panel-head {
        align-items: flex-start;
        display: flex;
        gap: 12px;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .account-card h2,
    .account-panel h2 {
        font-size: 20px;
        line-height: 1.25;
    }

    .account-panel h3,
    .account-card h3 {
        font-size: 16px;
    }

    .account-progress {
        background: rgba(255, 255, 255, 0.25);
        border-radius: 99px;
        height: 7px;
        margin: 12px 0;
        overflow: hidden;
    }

    .account-progress span {
        background: #fff;
        border-radius: inherit;
        display: block;
        height: 100%;
    }

    .account-form,
    .account-subform {
        display: grid;
        gap: 16px;
    }

    .account-form-grid {
        display: grid;
        gap: 14px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .account-field {
        display: grid;
        gap: 6px;
    }

    .account-field-wide {
        grid-column: 1 / -1;
    }

    .account-field label,
    .account-checkbox,
    .account-checks label {
        color: #344a2d;
        font-size: 13px;
        font-weight: 800;
    }

    .account-field input,
    .account-field select {
        border: 1px solid #d8e3d2;
        border-radius: 8px;
        color: #1f3217;
        min-height: 42px;
        padding: 0 12px;
        width: 100%;
    }

    .account-field input[type="file"] {
        padding: 9px 12px;
    }

    .account-field small {
        color: #77836f;
        font-size: 12px;
    }

    .account-checks {
        display: grid;
        gap: 8px;
    }

    .account-checkbox,
    .account-checks label {
        align-items: center;
        display: flex;
        gap: 8px;
    }

    .account-btn,
    .account-link-button {
        align-items: center;
        border: 0;
        border-radius: 8px;
        display: inline-flex;
        font-weight: 800;
        justify-content: center;
        min-height: 38px;
        padding: 0 14px;
        text-decoration: none;
    }

    .account-btn-primary {
        background: #6fae25;
        color: #fff;
        width: fit-content;
    }

    .account-btn-secondary,
    .account-btn-light {
        background: #f3f8ed;
        color: #416421;
        width: fit-content;
    }

    .account-card-highlight .account-btn-light {
        background: #fff;
        color: #4f8616;
    }

    .account-link-button {
        background: transparent;
        color: #5f8e1f;
        min-height: auto;
        padding: 0;
    }

    .account-link-button.is-danger {
        color: #b83535;
    }

    .account-list,
    .account-voucher-grid,
    .account-address-grid {
        display: grid;
        gap: 10px;
    }

    .account-address-grid,
    .account-voucher-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .account-row,
    .account-address,
    .account-voucher,
    .account-product {
        border: 1px solid #e3ebdc;
        border-radius: 8px;
        padding: 12px;
    }

    .account-row {
        align-items: center;
        color: #273d20;
        display: flex;
        justify-content: space-between;
    }

    .account-row:hover {
        background: #f8fbf4;
        color: #273d20;
        text-decoration: none;
    }

    .account-row-large span {
        display: grid;
        gap: 3px;
    }

    .account-address {
        display: grid;
        gap: 12px;
    }

    .account-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }

    .account-chip {
        background: #eaf6df;
        border-radius: 99px;
        color: #4f7f1d;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        margin-top: 8px;
        padding: 4px 9px;
    }

    .account-subform {
        border-top: 1px solid #edf2e9;
        margin-top: 18px;
        padding-top: 18px;
    }

    .account-product-grid {
        display: grid;
        gap: 12px;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .account-product a {
        color: #273d20;
        display: grid;
        gap: 8px;
    }

    .account-product a:hover {
        color: #5f8e1f;
        text-decoration: none;
    }

    .account-product img {
        aspect-ratio: 1 / 1;
        border-radius: 6px;
        object-fit: cover;
        width: 100%;
    }

    .account-product strong,
    .account-product span {
        display: block;
        line-height: 1.35;
    }

    .account-voucher {
        display: grid;
        gap: 4px;
    }

    .account-voucher strong {
        color: #5f8e1f;
        font-size: 18px;
    }

    .account-section-title {
        margin: 18px 0 10px !important;
    }

    .account-empty {
        background: #f8fbf4;
        border: 1px dashed #cfdcc5;
        border-radius: 8px;
        font-weight: 700;
        padding: 14px;
    }

    @media (max-width: 991px) {
        .account-hero {
            align-items: stretch;
            flex-direction: column;
        }

        .account-summary,
        .account-overview-grid,
        .account-compact-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575px) {
        .account-form-grid,
        .account-address-grid,
        .account-product-grid,
        .account-voucher-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var buttons = Array.prototype.slice.call(document.querySelectorAll('[data-account-tab]'));
        var panels = Array.prototype.slice.call(document.querySelectorAll('[data-account-panel]'));

        function activate(tabName) {
            buttons.forEach(function (button) {
                button.classList.toggle('is-active', button.getAttribute('data-account-tab') === tabName);
            });

            panels.forEach(function (panel) {
                panel.classList.toggle('is-active', panel.getAttribute('data-account-panel') === tabName);
            });
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                activate(button.getAttribute('data-account-tab'));
            });
        });
    })();
</script>
@endpush
