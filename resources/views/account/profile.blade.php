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
    $statusLabels = \App\Models\Order::statusLabels();
    $cancellationReasons = $cancellationReasons ?? \App\Models\OrderCancellationRequest::reasonLabels();
    $returnReasons = $returnReasons ?? \App\Models\OrderReturnRequest::reasonLabels();
    $returnTypes = $returnTypes ?? \App\Models\OrderReturnRequest::typeLabels();
    $refundMethods = $refundMethods ?? \App\Models\OrderReturnRequest::refundMethodLabels();
    $returnWindowHours = $returnWindowHours ?? (int) config('shop.returns.request_window_hours', 24);
    $provinceOptions = $vietnamProvinces ?? [];
    $addressDataUrl = $vietnamAddressDataUrl ?? asset('data/vietnam-addresses.json');
    $selectedAddressProvinceCode = old('province_code');
    $selectedAddressWardCode = old('ward_code');

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

            @if(session('success'))
                <div class="account-alert account-alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="account-alert account-alert-error">{{ session('error') }}</div>
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
                           <span style="width: {{ $profilePercent }}%;"></span>
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
                            <p>{{ $latestOrder->status_label }}</p>
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
                                    <strong>{{ $coupon->discount_label }}</strong>
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
                                <label for="profile_province_code">Tỉnh/Thành</label>
                                <select id="profile_province_code" name="province_code" required>
                                    <option value="">Chọn Tỉnh/Thành</option>
                                    @foreach($provinceOptions as $province)
                                        <option value="{{ $province['code'] }}" {{ (string) $selectedAddressProvinceCode === (string) $province['code'] ? 'selected' : '' }}>{{ $province['name'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="account-field">
                                <label for="profile_ward_code">Phường/Xã/Đặc khu</label>
                                <select id="profile_ward_code" name="ward_code" data-selected="{{ $selectedAddressWardCode }}" required>
                                    <option value="">Chọn Phường/Xã</option>
                                </select>
                            </div>
                            <input id="district" type="hidden" name="district" value="{{ old('district') }}">
                            <div class="account-field account-field-wide">
                                <small>Không cần nhập Quận/Huyện. Địa chỉ mới chỉ dùng Tỉnh/Thành và Phường/Xã/Đặc khu theo dữ liệu hành chính hiện tại.</small>
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
                            <p class="account-help-text">Nếu sản phẩm còn hàng, hệ thống sẽ tự xác nhận đơn và giữ tồn kho cho bạn. Đơn đã xác nhận vẫn có thể gửi yêu cầu hủy trước khi giao hàng. Đơn đã hoàn tất có thể gửi đổi trả/hoàn tiền trong {{ $returnWindowHours }} giờ sau khi nhận hàng.</p>
                        </div>
                    </div>

                    <div class="account-list">
                        @forelse($orders as $order)
                            @php
                                $latestCancellationRequest = $order->latest_cancellation_request;
                                $latestReturnRequest = $order->latest_return_request;
                                $returnDeadline = $order->returnRequestDeadline();
                            @endphp
                            <div class="account-order-row">
                                <a href="{{ route('checkout.thankyou', ['code' => $order->code, 'token' => $order->public_token]) }}" class="account-row account-row-large">
                                    <span>
                                        <strong>{{ $order->code }}</strong>
                                        <small>{{ $order->created_at ? $order->created_at->format('d/m/Y H:i') : '' }}</small>
                                    </span>
                                    <span>
                                        <strong>{{ number_format((int) $order->total, 0, ',', '.') }}₫</strong>
                                        <small>{{ $order->status_label }} · {{ $order->payment_status_label }}</small>
                                        @if($latestCancellationRequest)
                                            <small>Yêu cầu hủy: {{ $latestCancellationRequest->status_label }} · {{ $latestCancellationRequest->reason_label }}</small>
                                        @endif
                                        @if($latestReturnRequest)
                                            <small>Đổi trả: {{ $latestReturnRequest->status_label }} · {{ $latestReturnRequest->type_label }}</small>
                                        @elseif($order->isReturnRequestable() && $returnDeadline)
                                            <small>Đổi trả đến {{ $returnDeadline->format('d/m/Y H:i') }}</small>
                                        @endif
                                    </span>
                                </a>
                                <div class="account-order-actions">
                                    @if($order->isCustomerCancellable() || $order->isCustomerCancellationRequestable())
                                        <details class="account-cancel-details">
                                            <summary>{{ $order->isCustomerCancellable() ? 'Hủy đơn' : 'Yêu cầu hủy' }}</summary>
                                            <form method="post" action="{{ route('account.orders.cancel', $order) }}" class="account-cancel-form" onsubmit="return confirm('Bạn chắc chắn muốn gửi hủy đơn {{ $order->code }}?');">
                                                @csrf
                                                @method('PATCH')
                                                <select name="reason" required>
                                                    <option value="">Chọn lý do</option>
                                                    @foreach($cancellationReasons as $reasonValue => $reasonLabel)
                                                        <option value="{{ $reasonValue }}">{{ $reasonLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <textarea name="note" rows="2" maxlength="500" placeholder="Ghi chú thêm nếu cần"></textarea>
                                                <button type="submit">{{ $order->isCustomerCancellable() ? 'Xác nhận hủy' : 'Gửi yêu cầu' }}</button>
                                            </form>
                                        </details>
                                    @elseif($order->hasPendingCancellationRequest())
                                        <span class="account-cancel-state">Đang chờ duyệt hủy</span>
                                    @endif

                                    @if($order->isReturnRequestable())
                                        <details class="account-return-details">
                                            <summary>Đổi trả</summary>
                                            <form method="post" action="{{ route('account.orders.returns.store', $order) }}" enctype="multipart/form-data" class="account-return-form" onsubmit="return confirm('Gửi yêu cầu đổi trả/hoàn tiền cho đơn {{ $order->code }}?');">
                                                @csrf
                                                <select name="type" required>
                                                    <option value="">Hình thức xử lý</option>
                                                    @foreach($returnTypes as $typeValue => $typeLabel)
                                                        <option value="{{ $typeValue }}">{{ $typeLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <select name="reason" required>
                                                    <option value="">Lý do</option>
                                                    @foreach($returnReasons as $reasonValue => $reasonLabel)
                                                        <option value="{{ $reasonValue }}">{{ $reasonLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <textarea name="note" rows="3" maxlength="800" required placeholder="Mô tả tình trạng sản phẩm, thời điểm nhận hàng, mong muốn đổi/hoàn tiền"></textarea>
                                                <input type="file" name="evidence" accept="image/png,image/jpeg,image/webp">
                                                <select name="refund_method">
                                                    <option value="">Cách nhận hoàn tiền nếu chọn hoàn tiền</option>
                                                    @foreach($refundMethods as $methodValue => $methodLabel)
                                                        <option value="{{ $methodValue }}">{{ $methodLabel }}</option>
                                                    @endforeach
                                                </select>
                                                <textarea name="refund_account" rows="2" maxlength="255" placeholder="VD: Vietcombank - 0123456789 - Nguyen Van A, hoặc số MoMo"></textarea>
                                                <small>Ảnh bằng chứng giúp shop xử lý nhanh hơn. Trái cây tươi cần phản hồi sớm trong {{ $returnWindowHours }} giờ.</small>
                                                <a href="{{ route('page.return') }}" target="_blank" rel="noopener">Xem chính sách đổi trả</a>
                                                <button type="submit">Gửi yêu cầu</button>
                                            </form>
                                        </details>
                                    @elseif($order->hasPendingReturnRequest())
                                        <span class="account-return-state">Đang chờ xử lý đổi trả</span>
                                    @endif
                                </div>
                            </div>
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
                            <h2>Voucher của bạn</h2>
                            <p>{{ number_format($voucherCount) }} mã đã được cấp cho tài khoản.</p>
                        </div>
                    </div>

                    @if($personalVouchers->isNotEmpty())
                        <h3 class="account-section-title">Voucher thành viên</h3>
                        <div class="account-voucher-grid">
                            @foreach($personalVouchers as $voucher)
                                <div class="account-voucher account-voucher-featured">
                                    <div class="account-voucher-main">
                                        <span class="account-voucher-kicker">Dành cho tài khoản của bạn</span>
                                        <strong>{{ $voucher->coupon->code }}</strong>
                                        <span>{{ $voucher->coupon->title }}</span>
                                    </div>
                                    <div class="account-voucher-meta">
                                        <small>{{ $voucher->coupon->benefit_label }}</small>
                                        <small>{{ $voucher->coupon->condition_label }}</small>
                                        <small>{{ $voucher->expiry_label }}</small>
                                    </div>
                                    <div class="account-voucher-foot">
                                        <span class="account-voucher-status is-{{ $voucher->status_tone }}">{{ $voucher->status_label }}</span>
                                        @if($voucher->is_usable)
                                            <form method="post" action="{{ route('cart.coupon.use', $voucher->coupon) }}" class="account-voucher-action">
                                                @csrf
                                                <button type="submit">Dùng ngay</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="account-empty">Tài khoản chưa có voucher thành viên.</div>
                    @endif

                    @if($availableCoupons->isNotEmpty())
                        <h3 class="account-section-title">Mã đang hoạt động</h3>
                        <div class="account-voucher-grid">
                            @foreach($availableCoupons as $coupon)
                                <div class="account-voucher">
                                    <div class="account-voucher-main">
                                        <span class="account-voucher-kicker">Mã công khai</span>
                                        <strong>{{ $coupon->code }}</strong>
                                        <span>{{ $coupon->title }}</span>
                                    </div>
                                    <div class="account-voucher-meta">
                                        <small>{{ $coupon->benefit_label }}</small>
                                        <small>{{ $coupon->condition_label }}</small>
                                        <small>{{ $coupon->expiry_label }}</small>
                                        <small>{{ $coupon->usage_label }}</small>
                                    </div>
                                    <div class="account-voucher-foot">
                                        <span class="account-voucher-status is-success">Dùng được</span>
                                        <form method="post" action="{{ route('cart.coupon.use', $coupon) }}" class="account-voucher-action">
                                            @csrf
                                            <button type="submit">Dùng ngay</button>
                                        </form>
                                    </div>
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
                                    <small>{{ $usage->used_at ? $usage->used_at->format('d/m/Y H:i') : 'Đã dùng' }}</small>
                                    <strong>
                                        {{ (int) $usage->discount_total > 0
                                            ? '-' . number_format((int) $usage->discount_total, 0, ',', '.') . '₫'
                                            : optional($usage->coupon)->benefit_label }}
                                    </strong>
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

    .account-alert-success {
        background: #f1fae9;
        border: 1px solid #cfe7bd;
        color: #456d18;
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

    .account-help-text {
        color: #728068 !important;
        font-size: 13px !important;
        max-width: 720px;
    }

    .account-order-row {
        align-items: center;
        border: 1px solid #e3ebdc;
        border-radius: 8px;
        display: grid;
        gap: 10px;
        grid-template-columns: minmax(0, 1fr) auto;
        padding: 0;
    }

    .account-order-actions {
        align-items: center;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: flex-end;
        padding-right: 12px;
    }

    .account-order-row .account-row {
        border: 0;
        min-width: 0;
    }

    .account-cancel-form {
        margin: 0;
        padding-right: 12px;
    }

    .account-cancel-form button {
        background: #fff3f0;
        border: 1px solid #ffd0c7;
        border-radius: 999px;
        color: #b73f30;
        font-size: 12px;
        font-weight: 800;
        min-height: 32px;
        padding: 0 12px;
        white-space: nowrap;
    }

    .account-cancel-form button:hover {
        background: #ffe8e3;
    }

    .account-cancel-details {
        padding-right: 12px;
        position: relative;
    }

    .account-cancel-details summary {
        align-items: center;
        background: #fff3f0;
        border: 1px solid #ffd0c7;
        border-radius: 999px;
        color: #b73f30;
        cursor: pointer;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        min-height: 32px;
        padding: 0 12px;
        white-space: nowrap;
    }

    .account-cancel-details summary::-webkit-details-marker {
        display: none;
    }

    .account-cancel-details .account-cancel-form {
        background: #fffaf8;
        border: 1px solid #ffd0c7;
        border-radius: 10px;
        box-shadow: 0 14px 28px rgba(80, 33, 20, 0.12);
        display: grid;
        gap: 8px;
        margin-top: 8px;
        min-width: 260px;
        padding: 10px;
        position: absolute;
        right: 12px;
        top: 100%;
        z-index: 4;
    }

    .account-cancel-form select,
    .account-cancel-form textarea {
        border: 1px solid #e8c7bd;
        border-radius: 8px;
        color: #2a3523;
        font: inherit;
        font-size: 12px;
        outline: none;
        padding: 8px 10px;
        width: 100%;
    }

    .account-cancel-form textarea {
        min-height: 62px;
        resize: vertical;
    }

    .account-cancel-state {
        align-items: center;
        background: #fff8e3;
        border: 1px solid #f2d58a;
        border-radius: 999px;
        color: #8a6500;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        margin-right: 12px;
        min-height: 32px;
        padding: 0 12px;
        white-space: nowrap;
    }

    .account-return-details {
        position: relative;
    }

    .account-return-details summary {
        align-items: center;
        background: #eef8e7;
        border: 1px solid #cce4b7;
        border-radius: 999px;
        color: #48731e;
        cursor: pointer;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        min-height: 32px;
        padding: 0 12px;
        white-space: nowrap;
    }

    .account-return-details summary::-webkit-details-marker {
        display: none;
    }

    .account-return-form {
        background: #fbfff8;
        border: 1px solid #cce4b7;
        border-radius: 10px;
        box-shadow: 0 14px 28px rgba(47, 86, 24, 0.13);
        display: grid;
        gap: 8px;
        margin-top: 8px;
        min-width: 320px;
        padding: 10px;
        position: absolute;
        right: 0;
        top: 100%;
        z-index: 6;
    }

    .account-return-form select,
    .account-return-form textarea,
    .account-return-form input[type="file"] {
        background: #fff;
        border: 1px solid #d7e7cc;
        border-radius: 8px;
        color: #2a3523;
        font: inherit;
        font-size: 12px;
        outline: none;
        padding: 8px 10px;
        width: 100%;
    }

    .account-return-form textarea {
        min-height: 64px;
        resize: vertical;
    }

    .account-return-form small,
    .account-return-form a {
        color: #66745f;
        font-size: 12px;
        line-height: 1.4;
    }

    .account-return-form a {
        color: #5f8e1f;
        font-weight: 800;
    }

    .account-return-form button {
        background: #6fae25;
        border: 0;
        border-radius: 999px;
        color: #fff;
        font-size: 12px;
        font-weight: 800;
        min-height: 34px;
        padding: 0 12px;
    }

    .account-return-state {
        align-items: center;
        background: #eef8e7;
        border: 1px solid #cce4b7;
        border-radius: 999px;
        color: #48731e;
        display: inline-flex;
        font-size: 12px;
        font-weight: 800;
        min-height: 32px;
        padding: 0 12px;
        white-space: nowrap;
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

    .account-page {
        background:
            radial-gradient(circle at 8% 10%, rgba(255, 239, 177, 0.42) 0, rgba(255, 239, 177, 0) 32%),
            radial-gradient(circle at 92% 12%, rgba(178, 223, 128, 0.34) 0, rgba(178, 223, 128, 0) 34%),
            linear-gradient(180deg, #fbfdf6 0%, #eef8e9 100%);
        color: #203418;
        position: relative;
        z-index: 0;
    }

    .account-shell {
        max-width: 1120px;
        position: relative;
        z-index: 1;
    }

    .account-hero,
    .account-tabs,
    .account-card,
    .account-panel {
        border-color: #e2ebdb;
        box-shadow: 0 16px 36px rgba(37, 66, 25, 0.08);
    }

    .account-hero {
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 253, 244, 0.98));
        padding: 20px 22px;
    }

    .account-avatar {
        background: linear-gradient(135deg, #72af27, #f3a222);
        box-shadow: 0 10px 24px rgba(74, 128, 27, 0.18);
    }

    .account-kicker {
        color: #6f9225;
        letter-spacing: .02em;
    }

    .account-summary div {
        background: #fff;
        border-color: #dfead6;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.88);
        min-width: 96px;
    }

    .account-tabs {
        background: #fff;
        border-radius: 8px;
        gap: 7px;
        padding: 8px;
        position: relative;
        z-index: 1;
    }

    .account-tab {
        background: #f6faf2;
        border: 1px solid transparent;
        color: #425a38;
        transition: background-color .18s ease, border-color .18s ease, color .18s ease, box-shadow .18s ease;
    }

    .account-tab:hover {
        background: #eef7e8;
        border-color: #dbe9d1;
        color: #2f4f22;
    }

    .account-tab.is-active {
        background: #66a928;
        border-color: #66a928;
        box-shadow: 0 8px 18px rgba(89, 146, 32, 0.22);
        color: #fff;
    }

    .account-card,
    .account-panel {
        background: rgba(255, 255, 255, 0.98);
    }

    .account-card {
        min-height: 164px;
    }

    .account-card-highlight {
        background: #fff;
        border-left: 4px solid #66a928;
        color: #203418;
    }

    .account-card-highlight h2,
    .account-card-highlight p {
        color: #203418;
    }

    .account-card-highlight .account-card-head span {
        background: #66a928;
        border-radius: 999px;
        color: #fff;
        display: inline-flex;
        font-size: 13px;
        font-weight: 800;
        min-height: 26px;
        padding: 4px 10px;
    }

    .account-progress {
        background: #e6f0dd;
    }

    .account-progress span {
        background: linear-gradient(90deg, #66a928, #f2a21f);
    }

    .account-panel-head {
        border-bottom: 1px solid #edf3e8;
        margin-bottom: 16px;
        padding-bottom: 12px;
    }

    .account-card h2,
    .account-panel h2 {
        color: #1f3217;
        font-size: 19px;
    }

    .account-btn-primary {
        background: linear-gradient(135deg, #72b52c, #55951b);
        box-shadow: 0 9px 18px rgba(75, 134, 24, 0.18);
    }

    .account-btn-secondary,
    .account-btn-light {
        background: #eef7e8;
        color: #47711e;
    }

    .account-card-highlight .account-btn-light {
        background: #eef7e8;
        color: #47711e;
    }

    .account-row,
    .account-address,
    .account-voucher,
    .account-product {
        background: #fff;
        border-color: #e2ebdb;
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
    }

    .account-row:hover,
    .account-product:hover {
        border-color: #cfe3c0;
        box-shadow: 0 10px 22px rgba(42, 75, 23, 0.08);
        transform: translateY(-1px);
    }

    .account-product {
        display: grid;
        gap: 10px;
    }

    .account-product img {
        background: #f5faef;
        border: 1px solid #e5eedf;
    }

    .account-voucher strong {
        color: #54851f;
    }

    .account-empty {
        background: #fbfdf8;
        border-color: #d5e5ca;
        color: #65745e;
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

    /* Refined account dashboard */
    body .account-page {
        background: linear-gradient(180deg, #f8fbf3 0%, #eef7e9 100%) !important;
        padding: 34px 0 64px !important;
    }

    body .account-page .account-shell {
        max-width: 1140px !important;
    }

    body .account-page .account-hero {
        background: #fff !important;
        border: 1px solid #e5edde !important;
        box-shadow: 0 14px 34px rgba(38, 66, 24, 0.07) !important;
        margin-bottom: 18px !important;
        padding: 24px !important;
    }

    body .account-page .account-person {
        gap: 16px !important;
    }

    body .account-page .account-avatar {
        background: #75ab2c !important;
        box-shadow: none !important;
        flex-basis: 60px !important;
        height: 60px !important;
        width: 60px !important;
    }

    body .account-page .account-kicker {
        color: #6d8d24 !important;
        font-size: 11px !important;
        letter-spacing: .04em !important;
        margin-bottom: 4px !important;
    }

    body .account-page .account-hero h1 {
        font-size: 25px !important;
        line-height: 1.18 !important;
    }

    body .account-page .account-hero p {
        color: #71806a !important;
        font-size: 14px !important;
    }

    body .account-page .account-summary {
        gap: 10px !important;
    }

    body .account-page .account-summary div {
        align-items: center !important;
        background: #fbfdf8 !important;
        border: 1px solid #e3ecdc !important;
        box-shadow: none !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        min-height: 76px !important;
        min-width: 102px !important;
        padding: 12px 14px !important;
    }

    body .account-page .account-summary strong {
        color: #1f3217 !important;
        font-size: 18px !important;
        line-height: 1.2 !important;
    }

    body .account-page .account-summary span {
        color: #67745f !important;
        font-size: 12px !important;
        margin-top: 5px !important;
    }

    body .account-page .account-tabs {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        gap: 10px !important;
        margin: 0 0 18px !important;
        padding: 0 !important;
    }

    body .account-page .account-tab {
        background: #fff !important;
        border: 1px solid #e2ebdb !important;
        box-shadow: 0 8px 18px rgba(42, 72, 28, 0.05) !important;
        color: #405338 !important;
        min-height: 42px !important;
        padding: 0 15px !important;
    }

    body .account-page .account-tab:hover {
        background: #f8fbf5 !important;
        border-color: #cfe1c3 !important;
        color: #253b1d !important;
    }

    body .account-page .account-tab.is-active {
        background: #1f3217 !important;
        border-color: #1f3217 !important;
        box-shadow: 0 10px 22px rgba(31, 50, 23, 0.18) !important;
        color: #fff !important;
    }

    body .account-page .account-overview-grid,
    body .account-page .account-compact-grid {
        gap: 16px !important;
    }

    body .account-page .account-overview-grid {
        grid-template-columns: 1.06fr repeat(3, minmax(0, 1fr)) !important;
    }

    body .account-page .account-card,
    body .account-page .account-panel {
        background: #fff !important;
        border: 1px solid #e5edde !important;
        box-shadow: 0 12px 28px rgba(38, 66, 24, 0.06) !important;
        padding: 22px !important;
    }

    body .account-page .account-card {
        min-height: 180px !important;
    }

    body .account-page .account-card-highlight {
        border-left: 0 !important;
    }

    body .account-page .account-card h2,
    body .account-page .account-panel h2 {
        color: #1f3217 !important;
        font-size: 18px !important;
        letter-spacing: 0 !important;
    }

    body .account-page .account-card p,
    body .account-page .account-panel p {
        color: #66745f !important;
        font-size: 14px !important;
    }

    body .account-page .account-card-head,
    body .account-page .account-panel-head {
        border-bottom: 0 !important;
        margin-bottom: 14px !important;
        padding-bottom: 0 !important;
    }

    body .account-page .account-card-highlight .account-card-head span {
        background: #eff7e8 !important;
        color: #527b20 !important;
    }

    body .account-page .account-progress {
        background: #e8f0df !important;
        height: 6px !important;
        margin: 13px 0 14px !important;
    }

    body .account-page .account-progress span {
        background: #f3a422 !important;
    }

    body .account-page .account-btn,
    body .account-page .account-link-button {
        letter-spacing: 0 !important;
    }

    body .account-page .account-btn-light,
    body .account-page .account-btn-secondary {
        background: #f4f9ef !important;
        border: 1px solid #dce9d1 !important;
        color: #466f1e !important;
    }

    body .account-page .account-btn-primary {
        background: #66a928 !important;
        box-shadow: none !important;
    }

    body .account-page .account-link-button {
        color: #5d8d1f !important;
    }

    body .account-page .account-row,
    body .account-page .account-order-row,
    body .account-page .account-address,
    body .account-page .account-voucher,
    body .account-page .account-product,
    body .account-page .account-empty {
        background: #fcfefa !important;
        border-color: #e3ecdc !important;
        box-shadow: none !important;
    }

    body .account-page .account-row {
        min-height: 48px !important;
        padding: 0 14px !important;
    }

    body .account-page .account-order-row {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        min-height: 62px !important;
        padding: 0 !important;
    }

    body .account-page .account-order-row .account-row {
        background: transparent !important;
        border: 0 !important;
        min-height: 60px !important;
    }

    body .account-page .account-cancel-form {
        margin: 0 !important;
        padding-right: 14px !important;
    }

    body .account-page .account-cancel-form button {
        background: #fff3f0 !important;
        border: 1px solid #ffd0c7 !important;
        border-radius: 999px !important;
        color: #b73f30 !important;
        font-size: 12px !important;
        font-weight: 800 !important;
        min-height: 32px !important;
        padding: 0 12px !important;
    }

    body .account-page .account-cancel-details {
        padding-right: 14px !important;
        position: relative !important;
    }

    body .account-page .account-cancel-details summary {
        background: #fff3f0 !important;
        border: 1px solid #ffd0c7 !important;
        border-radius: 999px !important;
        color: #b73f30 !important;
        cursor: pointer !important;
        display: inline-flex !important;
        font-size: 12px !important;
        font-weight: 800 !important;
        min-height: 32px !important;
        padding: 0 12px !important;
        white-space: nowrap !important;
    }

    body .account-page .account-cancel-details .account-cancel-form {
        background: #fffaf8 !important;
        border: 1px solid #ffd0c7 !important;
        border-radius: 10px !important;
        box-shadow: 0 14px 28px rgba(80, 33, 20, 0.12) !important;
        display: grid !important;
        gap: 8px !important;
        margin-top: 8px !important;
        min-width: 270px !important;
        padding: 10px !important;
        position: absolute !important;
        right: 14px !important;
        top: 100% !important;
        z-index: 8 !important;
    }

    body .account-page .account-cancel-form select,
    body .account-page .account-cancel-form textarea {
        background: #fff !important;
        border: 1px solid #e8c7bd !important;
        border-radius: 8px !important;
        box-shadow: none !important;
        color: #26351f !important;
        font-size: 12px !important;
        min-height: 36px !important;
        outline: none !important;
        padding: 8px 10px !important;
        width: 100% !important;
    }

    body .account-page .account-cancel-form textarea {
        min-height: 62px !important;
        resize: vertical !important;
    }

    body .account-page .account-cancel-details .account-cancel-form button {
        width: 100% !important;
    }

    body .account-page .account-cancel-state {
        background: #fff8e3 !important;
        border: 1px solid #f2d58a !important;
        border-radius: 999px !important;
        color: #8a6500 !important;
        display: inline-flex !important;
        font-size: 12px !important;
        font-weight: 800 !important;
        margin-right: 14px !important;
        min-height: 32px !important;
        padding: 0 12px !important;
        white-space: nowrap !important;
    }

    body .account-page .account-voucher {
        min-height: 66px !important;
        padding: 13px 14px !important;
    }

    body .account-page .account-voucher strong {
        color: #45681d !important;
        font-size: 15px !important;
    }

    body .account-page .account-product {
        padding: 14px !important;
    }

    body .account-page .account-product img {
        border: 0 !important;
        border-radius: 8px !important;
    }

    body .account-page .account-empty {
        color: #6b7963 !important;
        font-weight: 600 !important;
    }

    body .account-page .account-voucher {
        align-items: stretch !important;
        background: #fffef9 !important;
        border-color: #dfe9d4 !important;
        border-radius: 10px !important;
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
        justify-content: space-between !important;
        min-height: 178px !important;
        padding: 16px !important;
    }

    body .account-page .account-voucher-featured {
        background: linear-gradient(135deg, #fffdf4 0%, #f1f8e8 100%) !important;
        border-color: #c8dfae !important;
    }

    body .account-page .account-voucher-main,
    body .account-page .account-voucher-meta {
        display: grid !important;
        gap: 5px !important;
    }

    body .account-page .account-voucher-kicker {
        color: #7a8d22 !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        letter-spacing: 0 !important;
        text-transform: uppercase !important;
    }

    body .account-page .account-voucher strong {
        color: #284417 !important;
        font-size: 20px !important;
        line-height: 1.15 !important;
    }

    body .account-page .account-voucher span:not(.account-voucher-kicker):not(.account-voucher-status) {
        color: #34492b !important;
        font-weight: 700 !important;
        line-height: 1.35 !important;
    }

    body .account-page .account-voucher small {
        color: #697760 !important;
        font-size: 12px !important;
        line-height: 1.35 !important;
    }

    body .account-page .account-voucher-foot {
        align-items: center !important;
        display: flex !important;
        gap: 10px !important;
        justify-content: space-between !important;
    }

    body .account-page .account-voucher-status {
        align-items: center !important;
        border-radius: 999px !important;
        display: inline-flex !important;
        font-size: 12px !important;
        font-weight: 800 !important;
        min-height: 28px !important;
        padding: 0 10px !important;
    }

    body .account-page .account-voucher-status.is-success {
        background: #edf8df !important;
        color: #4d8514 !important;
    }

    body .account-page .account-voucher-status.is-muted {
        background: #f2f3ef !important;
        color: #747b6f !important;
    }

    body .account-page .account-voucher-status.is-danger {
        background: #fff0ed !important;
        color: #b64b30 !important;
    }

    body .account-page .account-voucher-action {
        margin: 0 !important;
    }

    body .account-page .account-voucher-action button {
        background: #f7941e !important;
        border: 0 !important;
        border-radius: 999px !important;
        color: #fff !important;
        font-size: 12px !important;
        font-weight: 800 !important;
        min-height: 32px !important;
        padding: 0 14px !important;
        white-space: nowrap !important;
    }

    body .account-page .account-voucher-action button:hover {
        background: #e98410 !important;
    }

    @media (max-width: 991px) {
        body .account-page .account-overview-grid,
        body .account-page .account-compact-grid {
            grid-template-columns: 1fr !important;
        }
    }

    @media (max-width: 575px) {
        body .account-page .account-voucher-foot {
            align-items: stretch !important;
            flex-direction: column !important;
        }

        body .account-page .account-voucher-action button {
            width: 100% !important;
        }

        body .account-page .account-order-row {
            grid-template-columns: 1fr !important;
            padding-bottom: 12px !important;
        }

        body .account-page .account-cancel-form {
            padding: 0 14px !important;
        }

        body .account-page .account-cancel-form button {
            width: 100% !important;
        }

        body .account-page .account-cancel-details {
            padding: 0 14px !important;
        }

        body .account-page .account-cancel-details summary,
        body .account-page .account-cancel-state {
            justify-content: center !important;
            margin: 0 !important;
            width: 100% !important;
        }

        body .account-page .account-cancel-details .account-cancel-form {
            margin-top: 10px !important;
            min-width: 0 !important;
            position: static !important;
            width: 100% !important;
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

        var provinceSelect = document.getElementById('profile_province_code');
        var wardSelect = document.getElementById('profile_ward_code');
        var addressDataUrl = @json($addressDataUrl);
        var selectedWardCode = wardSelect ? (wardSelect.getAttribute('data-selected') || '') : '';
        var addressData = [];

        if (!provinceSelect || !wardSelect) {
            return;
        }

        function findProvince(provinceCode) {
            return addressData.find(function (province) {
                return String(province.Code) === String(provinceCode);
            });
        }

        function populateWards(provinceCode, wardCode) {
            wardSelect.innerHTML = '<option value="">Chọn Phường/Xã</option>';
            wardSelect.disabled = true;

            var province = findProvince(provinceCode);
            if (!province || !Array.isArray(province.Wards)) {
                return;
            }

            province.Wards.forEach(function (ward) {
                var option = document.createElement('option');
                option.value = ward.Code;
                option.textContent = ward.FullName;
                if (String(ward.Code) === String(wardCode || '')) {
                    option.selected = true;
                }
                wardSelect.appendChild(option);
            });

            wardSelect.disabled = false;
        }

        fetch(addressDataUrl)
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                addressData = Array.isArray(data) ? data : [];
                populateWards(provinceSelect.value, selectedWardCode);
            })
            .catch(function () {
                wardSelect.innerHTML = '<option value="">Không tải được dữ liệu Phường/Xã</option>';
                wardSelect.disabled = true;
            });

        provinceSelect.addEventListener('change', function () {
            selectedWardCode = '';
            var districtInput = document.getElementById('district');
            if (districtInput) {
                districtInput.value = '';
            }
            populateWards(provinceSelect.value, '');
        });

        wardSelect.addEventListener('change', function () {
            var districtInput = document.getElementById('district');
            if (districtInput) {
                districtInput.value = '';
            }
        });
    })();
</script>
@endpush
