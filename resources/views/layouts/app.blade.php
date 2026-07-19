<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	@php
		$shouldNoIndex = request()->routeIs(
			'login',
			'register',
			'password.*',
			'google.*',
			'verification.*',
			'account.*',
			'admin.*',
			'notifications.*',
			'cart',
			'checkout*',
			'search',
			'search.suggestions',
			'products.quick-view'
		);
		$robotsContent = $shouldNoIndex ? 'noindex,nofollow' : 'index,follow';
	@endphp
	<title>@yield('title', 'Thế Giới Trái Cây - Trái cây Việt Nam loại 1 & nhập khẩu cao cấp')</title>
	<meta name="description" content="@yield('meta_description', 'The Gioi Trai Cay - Trai cay Viet Nam loai 1 va nhap khau chat luong cao.')">

	<link rel="canonical" href="@yield('canonical', url()->current())" />
	<meta name="robots" content="@yield('robots', $robotsContent)" />
	<meta name="revisit-after" content="1 day" />
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="HandheldFriendly" content="true">
	<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml" />

	<!-- Fonts & Icons -->
	<link rel="stylesheet" href="https://cdn.linearicons.com/free/1.0.0/icon-font.min.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="preconnect" href="https://theme.hstatic.net" crossorigin>
	<link rel="preconnect" href="https://file.hstatic.net" crossorigin>
	<link rel="preconnect" href="https://product.hstatic.net" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<link href="https://fonts.googleapis.com/css?family=Roboto:300,300i,400,400i,500,500i,700,700i&amp;subset=vietnamese" rel="stylesheet">
	<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

	<!-- Toàn bộ CSS chính thức từ thegioitraicay.net -->
	<link href='//theme.hstatic.net/200000157781/1001036201/14/plugin.scss.css?v=1061' rel='stylesheet' type='text/css'  media='all'  />
	<link href='//theme.hstatic.net/200000157781/1001036201/14/base.scss.css?v=1061' rel='stylesheet' type='text/css'  media='all'  />
	<link href='//theme.hstatic.net/200000157781/1001036201/14/style.scss.css?v=1061' rel='stylesheet' type='text/css'  media='all'  />
	<link href='//theme.hstatic.net/200000157781/1001036201/14/module.scss.css?v=1061' rel='stylesheet' type='text/css'  media='all'  />
	<link href='//theme.hstatic.net/200000157781/1001036201/14/responsive.scss.css?v=1061' rel='stylesheet' type='text/css'  media='all'  />
	<link href='//theme.hstatic.net/200000157781/1001036201/14/bootstrap-theme.css?v=1061' rel='stylesheet' type='text/css'  media='all'  />
	<link href='//theme.hstatic.net/200000157781/1001036201/14/style-theme.scss.css?v=1061' rel='stylesheet' type='text/css'  media='all'  />
	<link href='//theme.hstatic.net/200000157781/1001036201/14/responsive-update.scss.css?v=1061' rel='stylesheet' type='text/css'  media='all'  />
	<link href='//theme.hstatic.net/200000157781/1001036201/14/hrv-style.css?v=1061' rel='stylesheet' type='text/css'  media='all'  />
	@vite('resources/css/app.css')
	@stack('head_meta')
	@include('partials.structured-data')
	<style>
		.site-skip-link {
			background: #fff;
			border: 2px solid #5d961f;
			color: #24420f;
			font-weight: 700;
			left: 12px;
			padding: 10px 14px;
			position: fixed;
			top: 12px;
			transform: translateY(-180%);
			transition: transform .15s ease;
			z-index: 100000;
		}

		.site-skip-link:focus {
			transform: translateY(0);
		}

		:where(a, button, input, select, textarea, summary, [tabindex]):focus-visible {
			outline: 3px solid #f59a23;
			outline-offset: 3px;
		}

		@media (prefers-reduced-motion: reduce) {
			*, *::before, *::after {
				animation-duration: .01ms !important;
				animation-iteration-count: 1 !important;
				scroll-behavior: auto !important;
				transition-duration: .01ms !important;
			}
		}
	</style>

	@yield('styles')
</head>
<body>
	<a class="site-skip-link" href="#main-content">Bỏ qua để đến nội dung chính</a>
	@php
		$headerCartCount = collect(session('cart', []))->sum(function ($item) {
			return (int) ($item['quantity'] ?? 0);
		});
		$shippingPolicyUrl = route('page.shipping.payment');
		$contactUrl = route('contact.page');
		$cartUrl = route('cart');
	@endphp
	<header class="header">
	<div class="topbar-mobile hidden-lg hidden-md text-center text-md-left">
		<div class="container">
			<i class="fa fa-mobile" style=" font-size: 20px; display: inline-block; position: relative; transform: translateY(2px); "></i> Hotline:
			<span>
				<a href="tel:0333499426">0333499426</a>
			</span>
		</div>
	</div>
	<div class="topbar hidden-sm hidden-xs">
	 	<div class="container">
		<div class="d-flex justify-content-between align-items-center">
			<div class="topbar-left">
				<span class="margin-right-20">
					<i class="fa fa-mobile" style="font-size: 16px;"></i> Hotline: <b>0333499426</b>
				</span>
				<span>
					<i class="fa fa-map-marker"></i> Địa chỉ: 74 Trần Thái Tông
				</span>
				</div>
				<div class="topbar-right">
				<div class="topbar-account">
					@auth
						@if(auth()->user()->isAdmin())
							<a href="{{ route('admin.dashboard') }}" class="topbar-account-pill topbar-account-primary">
								<i class="fa fa-user" aria-hidden="true"></i>
								<span>Admin</span>
							</a>
						@else
							<a href="{{ route('account.profile') }}" class="topbar-account-pill topbar-account-primary" title="Hồ sơ của tôi">
								<i class="fa fa-user" aria-hidden="true"></i>
								<span>{{ auth()->user()->name }}</span>
							</a>
						@endif
						<form method="post" action="{{ route('logout') }}" class="topbar-logout-form">
							@csrf
							<button type="submit" class="topbar-account-pill topbar-account-logout">
								<i class="fa fa-sign-out" aria-hidden="true"></i>
								<span>Đăng xuất</span>
							</button>
						</form>
					@else
						<a href="{{ route('login') }}" class="topbar-account-pill topbar-account-primary">
							<i class="fa fa-user" aria-hidden="true"></i>
							<span>Đăng nhập</span>
						</a>
						<span class="topbar-account-divider">hoặc</span>
						<a href="{{ route('register') }}" class="topbar-account-pill topbar-account-register">
							<i class="fa fa-user-plus" aria-hidden="true"></i>
							<span>Đăng ký</span>
						</a>
					@endauth
				</div>
				</div>
			</div>
		</div>
	</div>
	<div class="container">
		<div class="header-content clearfix">
			<div class="row align-items-center">
				{{-- Logo --}}
				<div class="col-xs-12 col-md-3">
					<div class="logo">
						<a href="{{ route('home') }}" class="logo-wrapper">
							<img src="https://theme.hstatic.net/200000157781/1001036201/14/logo.png?v=1061" alt="Thế Giới Trái Cây" width="310" height="86" loading="eager" decoding="async" fetchpriority="high">
						</a>
					</div>
				</div>

				{{-- Policy and Cart --}}
				<div class="col-xs-12 col-md-9 hidden-xs">
					<div class="header-right d-flex align-items-center justify-content-between">
						<div class="policy d-flex justify-content-around flex-1">
							<div class="item-policy d-flex align-items-center">
								<a href="{{ $shippingPolicyUrl }}" class="policy-icon-link" aria-label="Xem chính sách giao hàng">
									<img src="https://theme.hstatic.net/200000157781/1001036201/14/policy1.png?v=1061" alt="" width="48" height="48" decoding="async">
								</a>
								<div class="info">
									<a href="{{ $shippingPolicyUrl }}" class="policy-title">Giao nhanh TP.HCM</a>
									<a href="{{ $shippingPolicyUrl }}" class="policy-desc">30 - 90 phút, tỉnh xác nhận riêng</a>
								</div>
							</div>
							<div class="item-policy d-flex align-items-center">
								<a href="{{ $contactUrl }}" class="policy-icon-link" aria-label="Liên hệ hỗ trợ khách hàng">
									<img src="https://theme.hstatic.net/200000157781/1001036201/14/policy2.png?v=1061" alt="" width="48" height="48" decoding="async">
								</a>
								<div class="info">
									<a href="{{ $contactUrl }}" class="policy-title">Hỗ trợ 24/7</a>
									<a href="tel:0333499426" class="policy-desc">Hotline: 0333499426</a>
								</div>
							</div>
							<div class="item-policy d-flex align-items-center">
								<a href="{{ $contactUrl }}" class="policy-icon-link" aria-label="Xem giờ làm việc của cửa hàng">
									<img src="https://theme.hstatic.net/200000157781/1001036201/14/policy3.png?v=1061" alt="" width="48" height="48" decoding="async">
								</a>
								<div class="info">
									<a href="{{ $contactUrl }}" class="policy-title">Giờ làm việc</a>
									<a href="{{ $contactUrl }}" class="policy-desc">T2 - CN (7:00-19:00)</a>
								</div>
							</div>
						</div>

						<div class="header-commerce-actions">
							@auth
								<div class="header-notification" data-header-notification>
									<button type="button" class="header-notification-toggle" data-notification-toggle aria-label="Mở thông báo" aria-expanded="false" title="Thông báo">
										<i class="fa fa-bell" aria-hidden="true"></i>
										@if($headerUnreadNotificationCount > 0)
											<span>{{ $headerUnreadNotificationCount > 99 ? '99+' : $headerUnreadNotificationCount }}</span>
										@endif
									</button>
									<div class="header-notification-menu" data-notification-menu hidden>
										<div class="header-notification-head">
											<strong>Thông báo</strong>
											@if($headerUnreadNotificationCount > 0)<span>{{ $headerUnreadNotificationCount }} chưa đọc</span>@endif
										</div>
										<div class="header-notification-list">
											@forelse($headerNotifications as $headerNotification)
												@php
													$notificationData = $headerNotification->data;
													$notificationIcon = in_array($notificationData['icon'] ?? '', ['ticket', 'shopping-bag', 'check-circle', 'truck', 'gift', 'times-circle', 'credit-card'], true)
														? $notificationData['icon']
														: 'bell';
												@endphp
												<form method="post" action="{{ route('notifications.open', $headerNotification->id) }}">
													@csrf
													<button type="submit" class="header-notification-item {{ $headerNotification->read_at ? '' : 'is-unread' }}">
														<i class="fa fa-{{ $notificationIcon }}" aria-hidden="true"></i>
														<span>
															<strong>{{ $notificationData['title'] ?? 'Thông báo mới' }}</strong>
															<small>{{ optional($headerNotification->created_at)->diffForHumans() }}</small>
														</span>
													</button>
												</form>
											@empty
												<div class="header-notification-empty">Chưa có thông báo mới.</div>
											@endforelse
										</div>
										<a href="{{ route('notifications.index') }}" class="header-notification-all">Xem tất cả thông báo</a>
									</div>
								</div>
							@endauth

							<div class="top-cart-contain">
								<div class="mini-cart">
									<div class="heading-cart">
										<a href="{{ $cartUrl }}" class="d-flex align-items-center" aria-label="Mở giỏ hàng">
											<div class="icon relative" style="background: #ff9800; color: #fff; padding: 8px 15px; border-radius: 20px; display: flex; align-items: center;">
												<i class="fa fa-shopping-bag" style="margin-right: 8px;"></i>
												<span class="label" style="font-weight: bold;">Giỏ hàng ({{ $headerCartCount }})</span>
											</div>
										</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

		<div class="menu-bar hidden-md hidden-lg">
			<img src='//theme.hstatic.net/200000157781/1001036201/14/menu-bar.png?v=1061' alt='' width="28" height="24" />
		</div>
		<div class="icon-cart-mobile hidden-md hidden-lg f-left absolute" data-href="{{ route('cart') }}" onclick="window.location.href=this.getAttribute('data-href');">
			<div class="icon relative">
				<i class="fa fa-shopping-bag"></i>
				<span class="cartCount count_item_pr">{{ $headerCartCount }}</span>
			</div>
		</div>
		@auth
			<a href="{{ route('notifications.index') }}" class="icon-notification-mobile hidden-md hidden-lg" aria-label="Mở thông báo">
				<i class="fa fa-bell" aria-hidden="true"></i>
				@if($headerUnreadNotificationCount > 0)<span>{{ $headerUnreadNotificationCount > 99 ? '99+' : $headerUnreadNotificationCount }}</span>@endif
			</a>
		@endauth
	</div>
	<nav aria-label="Điều hướng chính">
		<div class="container">
			<div class="hidden-sm hidden-xs d-flex align-items-center justify-content-between">
				<ul class="nav nav-left">
					<li class="nav-item {{ request()->is('/') ? 'active' : '' }}"><a class="nav-link" href="{{ route('home') }}">Trang chủ</a></li>
					<li class="nav-item {{ request()->is('collections/all') ? 'active' : '' }} has-mega">
						<a href="{{ route('products.index') }}" class="nav-link">Sản phẩm <i class="fa fa-angle-right" data-toggle="dropdown"></i></a>
						<div class="mega-content">
							<div class="level0-wrapper2">
								<div class="nav-block nav-block-center">
									<ul class="level0">
										@foreach(($megaCategories ?? collect()) as $category)
										<li class="level1 parent item">
											<h2 class="h4"><a href="{{ route('categories.show', $category->slug) }}"><span>{{ $category->name }}</span></a></h2>
											@php
												$megaItems = collect($category->mega_items ?? []);
											@endphp
											@if($megaItems->isNotEmpty())
											<ul class="level1">
												@foreach($megaItems as $menuItem)
												<li class="level2">
														<a href="{{ $menuItem['url'] }}"><span>{{ $menuItem['label'] }}</span></a>
												</li>
												@endforeach
											</ul>
											@endif
										</li>
										@endforeach
									</ul>
								</div>
							</div>
						</div>
					</li>
				<li class="nav-item {{ request()->is('collections/mam-dia-ngu-qua') ? 'active' : '' }}"><a class="nav-link" href="{{ url('/collections/mam-dia-ngu-qua') }}">Mâm dĩa ngũ quả</a></li>
				<li class="nav-item {{ request()->routeIs('about') ? 'active' : '' }}"><a class="nav-link" href="{{ route('about') }}">Giới thiệu</a></li>
				<li class="nav-item {{ request()->routeIs('contact.page') ? 'active' : '' }}"><a class="nav-link" href="{{ route('contact.page') }}">Liên hệ</a></li>
				</ul>

				<div class="menu-search">
					<div class="header_search search_form js-site-search" data-suggest-url="{{ route('search.suggestions') }}" data-authenticated="{{ auth()->check() ? '1' : '0' }}">
						<form class="input-group search-bar search_form" action="{{ route('search') }}" method="get" role="search">
							<input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm sản phẩm" class="input-group-field search-text auto-search" autocomplete="off" role="combobox" aria-label="Tìm sản phẩm" aria-autocomplete="list" aria-expanded="false" aria-controls="site-search-suggestions">
							<span class="input-group-btn">
								<button class="btn" type="submit" aria-label="Tìm kiếm">
									<i class="fa fa-search" aria-hidden="true"></i>
								</button>
							</span>
						</form>
						<div class="search-suggest-panel" id="site-search-suggestions" role="region" aria-label="Gợi ý tìm kiếm" data-search-suggest-panel hidden>
							<div class="search-suggest-content" data-search-suggest-content></div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</nav>
</header>

	<main id="main-content" tabindex="-1">
		@if((session('success') && !session('cart_added')) || session('error'))
			<div class="container site-flash-container">
				@if(session('success') && !session('cart_added'))
					<div class="site-flash site-flash-success">{{ session('success') }}</div>
				@endif

				@if(session('error'))
					<div class="site-flash site-flash-error">{{ session('error') }}</div>
				@endif
			</div>
		@endif

		@yield('content')
	</main>

	<div class="quick-view-overlay" id="quickViewModal" hidden aria-hidden="true">
		<div class="quick-view-dialog" role="dialog" aria-modal="true" aria-labelledby="quickViewTitle" aria-describedby="quickViewDescription" tabindex="-1">
			<button type="button" class="quick-view-close" data-quick-view-close aria-label="Đóng">&times;</button>

			<div class="quick-view-loading" data-qv-loading>
				<i class="fa fa-circle-o-notch fa-spin" aria-hidden="true"></i>
				<span>Đang tải thông tin sản phẩm...</span>
			</div>

			<div class="quick-view-error" data-qv-error hidden>
				Không thể tải nhanh sản phẩm lúc này. Vui lòng mở trang chi tiết sản phẩm.
			</div>

			<div class="quick-view-body" data-qv-body hidden>
				<div class="quick-view-gallery">
					<div class="quick-view-image-stage">
						<img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==" alt="" width="720" height="720" decoding="async" data-qv-main-image>
					</div>
					<div class="quick-view-thumbs" data-qv-thumbs></div>
				</div>

				<div class="quick-view-info">
					<a href="#" class="quick-view-category" data-qv-category>Sản phẩm</a>
					<h2 id="quickViewTitle" data-qv-title>Sản phẩm</h2>

					<div class="quick-view-stock-row">
						<span class="quick-view-label">Trạng thái:</span>
						<span class="quick-view-stock" data-qv-stock>Còn hàng</span>
					</div>

					<div class="quick-view-price-row">
						<span class="quick-view-price" data-qv-price></span>
						<span class="quick-view-old-price" data-qv-old-price hidden></span>
						<span class="quick-view-discount" data-qv-discount hidden></span>
					</div>

					<p class="quick-view-desc" id="quickViewDescription" data-qv-desc></p>

					<div class="quick-view-meta">
						<div data-qv-sku-row hidden><strong>SKU:</strong> <span data-qv-sku></span></div>
						<div data-qv-unit-row hidden><strong>Quy cách:</strong> <span data-qv-unit></span></div>
						<div><strong>Hãng sản xuất:</strong> <span data-qv-manufacturer>Khác</span></div>
					</div>

					<form action="{{ route('cart.add') }}" method="post" class="quick-view-cart-form" data-qv-form>
						@csrf
						<input type="hidden" name="product_id" value="" data-qv-product-id>
						<div class="quick-view-buy-row">
							<label for="quickViewQuantity">Số lượng</label>
							<input id="quickViewQuantity" type="number" name="quantity" min="1" max="99" value="1" data-qv-quantity>
							<button type="submit" class="quick-view-add-btn">
								<i class="fa fa-shopping-bag" aria-hidden="true"></i>
								Thêm vào giỏ hàng
							</button>
						</div>
					</form>

					<div class="quick-view-unavailable" data-qv-unavailable hidden>
						<p data-qv-unavailable-text></p>
						<a href="#" class="quick-view-primary-link" data-qv-primary-action> Xem chi tiết </a>
					</div>

					<a href="#" class="quick-view-detail-link" data-qv-detail-link>Xem chi tiết sản phẩm</a>
				</div>
			</div>
		</div>
	</div>

	@if(session('cart_added'))
		@php
			$cartAdded = session('cart_added');
		@endphp
		<div class="cart-added-overlay is-visible" id="cartAddedModal" role="dialog" aria-modal="true" aria-labelledby="cartAddedTitle" aria-hidden="false">
			<div class="cart-added-modal">
				<button type="button" class="cart-added-close" data-cart-modal-close aria-label="Đóng">&times;</button>
				<div class="cart-added-head">
					<i class="fa fa-check-square-o" aria-hidden="true"></i>
					<h2 id="cartAddedTitle">Sản phẩm đã được thêm vào giỏ hàng</h2>
				</div>
				<div class="cart-added-product">
					<img src="{{ $cartAdded['image'] ?? '//theme.hstatic.net/200000157781/1001036201/14/no-image.jpg?v=1064' }}" alt="{{ $cartAdded['name'] ?? 'Sản phẩm' }}" width="96" height="96" decoding="async">
					<div class="cart-added-info">
						<strong>{{ $cartAdded['name'] ?? 'Sản phẩm' }}</strong>
						<span>Số lượng: {{ number_format((int) ($cartAdded['quantity'] ?? 1)) }}</span>
						<span>Giá: {{ number_format((int) ($cartAdded['unit_price'] ?? 0), 0, ',', '.') }}₫</span>
					</div>
				</div>
				<div class="cart-added-summary">
					<i class="fa fa-caret-right" aria-hidden="true"></i>
					<span>Giỏ hàng của bạn hiện có <strong>{{ number_format((int) ($cartAdded['cart_quantity'] ?? 0)) }}</strong> sản phẩm</span>
				</div>
				<div class="cart-added-actions">
					<button type="button" class="cart-added-btn cart-added-btn-light" data-cart-modal-close>Tiếp tục mua sắm</button>
					<a href="{{ route('cart') }}" class="cart-added-btn cart-added-btn-outline">Xem giỏ hàng</a>
					<a href="{{ route('checkout') }}" class="cart-added-btn cart-added-btn-primary">Tiến hành thanh toán</a>
				</div>
			</div>
		</div>
	@endif


	@stack('styles')

	<section class="tgc-brand-strip">
		<div class="container">
			<div class="tgc-brand-grid">
				<div class="tgc-brand-item"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand1.png?v=1064" alt="Envy" width="180" height="90" loading="lazy" decoding="async"></div>
				<div class="tgc-brand-item"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand2.png?v=1064" alt="Koala Cherries" width="180" height="90" loading="lazy" decoding="async"></div>
				<div class="tgc-brand-item"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand3.png?v=1064" alt="Sun World" width="180" height="90" loading="lazy" decoding="async"></div>
				<div class="tgc-brand-item"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand4.png?v=1064" alt="Pretty Lady" width="180" height="90" loading="lazy" decoding="async"></div>
				<div class="tgc-brand-item"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand5.png?v=1064" alt="Zespri" width="180" height="90" loading="lazy" decoding="async"></div>
				<div class="tgc-brand-item"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand6.png?v=1064" alt="Sunkist" width="180" height="90" loading="lazy" decoding="async"></div>
			</div>
		</div>
	</section>

	<footer class="footer">
		<div class="site-footer">
			<div class="container">
				<div class="footer-inner">
					<div class="row">
						<div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
							<div class="footer-widget">
								<h3>Liên hệ</h3>
								<ul class="list-menu footer-contact-list">
									<li><i class="fa fa-map-marker"></i><span>74 Trần Thái Tông</span></li>
									<li><i class="fa fa-phone"></i><span>0333499426<span class="line-break">Thứ 2 - Chủ nhật: 7:00 - 21:00</span></span></li>
									<li><i class="fa fa-envelope"></i><span>bacnguyen2921@gmail.com</span></li>
								</ul>
								<ul class="list-menu">
									<li>Chủ sở hữu: Nguyễn Văn Bắc</li>
								</ul>
							</div>
						</div>

						<div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
							<div class="footer-widget">
								<h3>Danh mục</h3>
								<ul class="list-menu menu-dot">
									<li><a href="{{ route('home') }}">Trang chủ</a></li>
									<li><a href="{{ route('products.index') }}">Sản phẩm</a></li>
									<li><a href="{{ route('categories.show', 'mam-dia-ngu-qua') }}">Mâm dĩa ngũ quả</a></li>
									<li><a href="{{ route('about') }}">Giới thiệu</a></li>
									<li><a href="{{ route('contact.page') }}">Liên hệ</a></li>
								</ul>
							</div>
						</div>

						<div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
							<div class="footer-widget">
								<h3>Hỗ trợ khách hàng</h3>
								<ul class="list-menu menu-dot">
									<li><a href="{{ route('about') }}">Giới thiệu</a></li>
									<li><a href="{{ route('search') }}">Tìm kiếm</a></li>
									<li><a href="{{ route('page.faq') }}">Câu hỏi thường gặp</a></li>
									<li><a href="{{ route('page.shipping.payment') }}">Chính sách giao hàng và thanh toán</a></li>
									<li><a href="{{ route('page.corporate') }}">Khách hàng doanh nghiệp</a></li>
									<li><a href="{{ route('page.return') }}">Chính sách đổi trả</a></li>
									<li><a href="{{ route('page.privacy.info') }}">Chính sách bảo mật thông tin</a></li>
								</ul>
							</div>
						</div>

						<div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
							<div class="footer-widget">
								<h3>Kết nối với thế giới trái cây</h3>
								<div class="footer-connect">
									<div class="divider"></div>
									<a class="connect-label" href="https://www.facebook.com/thegioitraicay.net" target="_blank" rel="noopener noreferrer">Facebook</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="copyright">
			<div class="container" style="position: relative;">
				<div class="row">
					<div class="col-xs-12 col-md-6">
						<div class="copyright-text">© Bản quyền thuộc về Thế giới trái cây</div>
					</div>
					<div class="col-xs-12 col-md-6 hidden-xs">
						<ul class="list-menu-footer">
							<li><a href="{{ route('home') }}">Trang chủ</a></li>
							<li><a href="{{ route('products.index') }}">Sản phẩm</a></li>
							<li><a href="{{ route('categories.show', 'mam-dia-ngu-qua') }}">Mâm dĩa ngũ quả</a></li>
							<li><a href="{{ route('about') }}">Giới thiệu</a></li>
							<li><a href="{{ route('contact.page') }}">Liên hệ</a></li>
						</ul>
					</div>
				</div>
				<div class="back-to-top" id="backToTop" title="Lên đầu trang">
					<i class="fa fa-angle-up"></i>
				</div>
			</div>
		</div>
	</footer>

	@include('partials.category-rail')

	<div class="tgc-side-icons" data-floating-actions>
		<a class="icon-item phone" href="tel:0333499426" aria-label="Gọi hotline" title="Gọi hotline">
			<i class="fa fa-phone" aria-hidden="true"></i>
		</a>
		<a class="icon-item zalo" href="https://zalo.me/0333499426" target="_blank" rel="noopener noreferrer" aria-label="Nhắn Zalo" title="Nhắn Zalo">Zalo</a>
		<a class="icon-item location" href="{{ route('contact.page') }}" aria-label="Xem địa chỉ cửa hàng" title="Xem địa chỉ cửa hàng">
			<i class="fa fa-map-marker" aria-hidden="true"></i>
		</a>
		<button class="icon-item support" id="supportWidgetToggle" type="button" aria-label="Mở hỗ trợ mua hàng" title="Hỗ trợ mua hàng" aria-controls="supportWidget" aria-expanded="false">
			<i class="fa fa-comments" aria-hidden="true"></i>
		</button>
	</div>

	<section
		class="support-widget"
		id="supportWidget"
		role="dialog"
		aria-labelledby="supportWidgetTitle"
		data-shipping-url="{{ route('page.shipping.payment') }}"
		data-payment-url="{{ route('page.shipping.payment') }}"
		data-orders-url="{{ route('account.profile', ['tab' => 'orders']) }}"
		data-orders-authenticated="{{ auth()->check() ? 'true' : 'false' }}"
		data-returns-url="{{ route('page.return') }}"
		hidden
	>
		<header class="support-widget-header">
			<span class="support-widget-avatar" aria-hidden="true"><i class="fa fa-comments"></i></span>
			<span class="support-widget-heading">
				<strong id="supportWidgetTitle">Trợ lý mua hàng</strong>
				<small>Tự động 24/7 · Tư vấn 07:00-19:00</small>
			</span>
			<button class="support-widget-close" type="button" aria-label="Đóng hỗ trợ" title="Đóng">
				<i class="fa fa-times" aria-hidden="true"></i>
			</button>
		</header>

		<div class="support-widget-body">
			<p class="support-widget-intro">Xin chào, bạn cần hỗ trợ nội dung nào?</p>
			<div class="support-widget-topics" aria-label="Nội dung hỗ trợ">
				<button type="button" data-support-topic="shipping" aria-pressed="false"><i class="fa fa-truck" aria-hidden="true"></i> Phí giao hàng</button>
				<button type="button" data-support-topic="payment" aria-pressed="false"><i class="fa fa-credit-card" aria-hidden="true"></i> Thanh toán</button>
				<button type="button" data-support-topic="orders" aria-pressed="false"><i class="fa fa-cube" aria-hidden="true"></i> Theo dõi đơn</button>
				<button type="button" data-support-topic="returns" aria-pressed="false"><i class="fa fa-refresh" aria-hidden="true"></i> Đổi trả</button>
			</div>

			<div class="support-widget-answer" data-support-answer aria-live="polite" hidden>
				<strong data-support-answer-title></strong>
				<p data-support-answer-text></p>
				<a data-support-answer-link href="#"></a>
			</div>

			<div class="support-widget-contact">
				<a href="tel:0333499426"><i class="fa fa-phone" aria-hidden="true"></i> Gọi tư vấn</a>
				<a href="https://zalo.me/0333499426" target="_blank" rel="noopener noreferrer"><strong>Zalo</strong> Nhắn cửa hàng</a>
			</div>
		</div>
	</section>

	@if(!empty($salesPopupProducts))
	<div class="sales-pop-toast" id="salesPopToast" role="status" aria-live="polite">
		<a class="sales-pop-link" id="salesPopLink" href="#">
			<img class="sales-pop-image" id="salesPopImage" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==" alt="" width="96" height="72" loading="lazy" decoding="async">
			<span class="sales-pop-copy">
				<strong class="sales-pop-name" id="salesPopName"></strong>
				<span class="sales-pop-message">Một khách hàng vừa đặt mua cách đây <span id="salesPopTime"></span> phút</span>
			</span>
		</a>
		<button class="sales-pop-close" id="salesPopClose" type="button" aria-label="Đóng thông báo">&times;</button>
	</div>
	@endif

	<script src='//theme.hstatic.net/200000157781/1001036201/14/jquery-2.2.3.min.js?v=1061'></script>
	<script src='//theme.hstatic.net/200000157781/1001036201/14/plugin.js?v=1061'></script>

	<script>
		// Sticky Nav on Scroll
		(function() {
			const nav = document.querySelector('nav');
			if (!nav) return;

			const header = document.querySelector('.header');
			const headerHeight = header ? header.offsetHeight : 0;

			window.addEventListener('scroll', function() {
				const scrollTop = window.scrollY || window.pageYOffset;

				if (scrollTop >= headerHeight) {
					nav.classList.add('fixed-nav');
					// Add padding to prevent content jump
					document.body.style.paddingTop = nav.offsetHeight + 'px';
				} else {
					nav.classList.remove('fixed-nav');
					document.body.style.paddingTop = '0';
				}
			}, false);
		})();

		// Fallback lazyload: ensure images with data-lazyload are rendered.
		(function() {
			function hydrateLazyImages() {
				document.querySelectorAll('img[data-lazyload]').forEach(function (img) {
					const lazySrc = img.getAttribute('data-lazyload');
					if (!lazySrc) {
						return;
					}

					if (img.getAttribute('src') !== lazySrc) {
						img.setAttribute('src', lazySrc);
					}
				});
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', hydrateLazyImages);
			} else {
				hydrateLazyImages();
			}
		})();

		// Footer back-to-top
		(function() {
			var backToTop = document.getElementById('backToTop');
			if (!backToTop) {
				return;
			}

			backToTop.addEventListener('click', function() {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			});
		})();

		// Mini cart modal after adding a product.
		(function() {
			var modal = document.getElementById('cartAddedModal');
			if (!modal) {
				return;
			}
			var closeButton = modal.querySelector('[data-cart-modal-close]');

			function closeModal() {
				modal.classList.remove('is-visible');
				modal.setAttribute('aria-hidden', 'true');
				modal.hidden = true;
			}

			document.querySelectorAll('[data-cart-modal-close]').forEach(function(button) {
				button.addEventListener('click', closeModal);
			});

			modal.addEventListener('click', function(event) {
				if (event.target === modal) {
					closeModal();
				}
			});

			document.addEventListener('keydown', function(event) {
				if (event.key === 'Escape') {
					closeModal();
					return;
				}

				if (event.key === 'Tab' && !modal.hidden) {
					var focusable = modal.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])');
					var first = focusable[0];
					var last = focusable[focusable.length - 1];

					if (first && event.shiftKey && document.activeElement === first) {
						event.preventDefault();
						last.focus();
					} else if (last && !event.shiftKey && document.activeElement === last) {
						event.preventDefault();
						first.focus();
					}
				}
			});

			if (closeButton) {
				window.requestAnimationFrame(function() { closeButton.focus(); });
			}
		})();

		// Product quick-view modal.
		(function() {
			var modal = document.getElementById('quickViewModal');

			if (!modal) {
				return;
			}

			var loading = modal.querySelector('[data-qv-loading]');
			var dialog = modal.querySelector('.quick-view-dialog');
			var closeButton = modal.querySelector('[data-quick-view-close]');
			var errorBox = modal.querySelector('[data-qv-error]');
			var body = modal.querySelector('[data-qv-body]');
			var mainImage = modal.querySelector('[data-qv-main-image]');
			var thumbs = modal.querySelector('[data-qv-thumbs]');
			var category = modal.querySelector('[data-qv-category]');
			var title = modal.querySelector('[data-qv-title]');
			var stock = modal.querySelector('[data-qv-stock]');
			var price = modal.querySelector('[data-qv-price]');
			var oldPrice = modal.querySelector('[data-qv-old-price]');
			var discount = modal.querySelector('[data-qv-discount]');
			var desc = modal.querySelector('[data-qv-desc]');
			var skuRow = modal.querySelector('[data-qv-sku-row]');
			var sku = modal.querySelector('[data-qv-sku]');
			var unitRow = modal.querySelector('[data-qv-unit-row]');
			var unit = modal.querySelector('[data-qv-unit]');
			var manufacturer = modal.querySelector('[data-qv-manufacturer]');
			var form = modal.querySelector('[data-qv-form]');
			var productId = modal.querySelector('[data-qv-product-id]');
			var quantity = modal.querySelector('[data-qv-quantity]');
			var unavailable = modal.querySelector('[data-qv-unavailable]');
			var unavailableText = modal.querySelector('[data-qv-unavailable-text]');
			var primaryAction = modal.querySelector('[data-qv-primary-action]');
			var detailLink = modal.querySelector('[data-qv-detail-link]');
			var lastFocusedElement = null;

			function openModal() {
				modal.hidden = false;
				modal.setAttribute('aria-hidden', 'false');
				modal.classList.add('is-visible');
				document.body.classList.add('quick-view-open');
				window.requestAnimationFrame(function() {
					if (closeButton) closeButton.focus();
					else if (dialog) dialog.focus();
				});
			}

			function closeModal() {
				modal.classList.remove('is-visible');
				modal.setAttribute('aria-hidden', 'true');
				modal.hidden = true;
				document.body.classList.remove('quick-view-open');

				if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
					lastFocusedElement.focus();
				}
			}

			function showLoading() {
				openModal();
				loading.hidden = false;
				errorBox.hidden = true;
				body.hidden = true;
			}

			function showError() {
				openModal();
				loading.hidden = true;
				errorBox.hidden = false;
				body.hidden = true;
			}

			function selectImage(imageUrl, button) {
				if (!imageUrl || !mainImage) {
					return;
				}

				mainImage.src = imageUrl;
				mainImage.alt = title ? title.textContent : '';

				if (thumbs) {
					thumbs.querySelectorAll('.quick-view-thumb').forEach(function(item) {
						item.classList.toggle('is-active', item === button);
					});
				}
			}

			function renderThumbs(images, productName) {
				if (!thumbs) {
					return;
				}

				thumbs.innerHTML = '';

				(images || []).forEach(function(imageUrl, index) {
					var button = document.createElement('button');
					var image = document.createElement('img');

					button.type = 'button';
					button.className = 'quick-view-thumb' + (index === 0 ? ' is-active' : '');
					button.setAttribute('aria-label', 'Xem ảnh ' + (index + 1));
					image.src = imageUrl;
					image.alt = productName + ' - ảnh ' + (index + 1);
					image.width = 80;
					image.height = 80;
					image.loading = 'lazy';
					image.decoding = 'async';

					button.appendChild(image);
					button.addEventListener('click', function() {
						selectImage(imageUrl, button);
					});
					thumbs.appendChild(button);
				});
			}

			function unavailableMessage(product) {
				if (!product.stock || !product.stock.in_stock) {
					return 'Sản phẩm đang tạm hết hàng. Bạn có thể xem chi tiết hoặc liên hệ shop để được báo khi có hàng.';
				}

				if (product.price && product.price.is_contact_price) {
					return 'Sản phẩm đang chờ cập nhật giá bán. Vui lòng mở trang chi tiết hoặc liên hệ shop để được tư vấn.';
				}

				if (product.is_custom_order) {
					return 'Sản phẩm này làm theo yêu cầu, shop cần tư vấn mẫu, ngân sách và thời điểm giao trước khi đặt.';
				}

				if (product.has_gear_detail) {
					return 'Sản phẩm này cần chọn quy cách hoặc mẫu trước khi thêm vào giỏ hàng.';
				}

				return 'Sản phẩm chưa thể thêm nhanh vào giỏ. Vui lòng mở trang chi tiết để tiếp tục.';
			}

			function renderProduct(product) {
				var images = Array.isArray(product.images) && product.images.length ? product.images : [];
				var firstImage = images[0] || '';
				var maxQuantity = Math.max(1, Math.min(99, Number(product.stock && product.stock.quantity ? product.stock.quantity : 99)));

				loading.hidden = true;
				errorBox.hidden = true;
				body.hidden = false;

				if (category) {
					category.textContent = product.category && product.category.name ? product.category.name : 'Sản phẩm';
					category.href = product.category && product.category.url ? product.category.url : product.url;
				}

				if (title) {
					title.textContent = product.name || 'Sản phẩm';
				}

				if (mainImage) {
					mainImage.src = firstImage;
					mainImage.alt = product.name || 'Sản phẩm';
				}

				renderThumbs(images, product.name || 'Sản phẩm');

				if (stock) {
					stock.textContent = product.stock && product.stock.label ? product.stock.label : 'Còn hàng';
					stock.classList.toggle('is-soldout', !(product.stock && product.stock.in_stock));
				}

				if (price) {
					price.textContent = product.price && product.price.formatted ? product.price.formatted : '';
				}

				if (oldPrice) {
					oldPrice.hidden = !(product.price && product.price.compare_formatted);
					oldPrice.textContent = product.price && product.price.compare_formatted ? product.price.compare_formatted : '';
				}

				if (discount) {
					var discountPercent = Number(product.price && product.price.discount_percent ? product.price.discount_percent : 0);
					discount.hidden = discountPercent <= 0;
					discount.textContent = discountPercent > 0 ? '(-' + discountPercent + '%)' : '';
				}

				if (desc) {
					desc.textContent = product.description || 'Thông tin sản phẩm đang được cập nhật.';
				}

				if (skuRow && sku) {
					skuRow.hidden = !product.sku;
					sku.textContent = product.sku || '';
				}

				if (unitRow && unit) {
					unitRow.hidden = !product.unit;
					unit.textContent = product.unit || '';
				}

				if (manufacturer) {
					manufacturer.textContent = product.manufacturer || 'Khác';
				}

				if (detailLink) {
					detailLink.href = product.url || '#';
				}

				if (productId) {
					productId.value = product.id || '';
				}

				if (quantity) {
					quantity.value = 1;
					quantity.max = maxQuantity;
				}

				if (form) {
					form.hidden = !product.can_add_to_cart;
				}

				if (unavailable) {
					unavailable.hidden = !!product.can_add_to_cart;
				}

				if (unavailableText) {
					unavailableText.textContent = unavailableMessage(product);
				}

				if (primaryAction) {
					primaryAction.href = product.primary_action && product.primary_action.url ? product.primary_action.url : (product.url || '#');
					primaryAction.textContent = product.primary_action && product.primary_action.label ? product.primary_action.label : 'Xem chi tiết';
				}
			}

			function fetchProduct(url) {
				showLoading();

				fetch(url, {
					headers: {
						'Accept': 'application/json',
						'X-Requested-With': 'XMLHttpRequest'
					}
				})
					.then(function(response) {
						if (!response.ok) {
							throw new Error('Cannot load quick view');
						}

						return response.json();
					})
					.then(renderProduct)
					.catch(showError);
			}

			document.addEventListener('click', function(event) {
				var trigger = event.target.closest('[data-quick-view-url]');

				if (!trigger) {
					return;
				}

				event.preventDefault();
				event.stopPropagation();
				lastFocusedElement = trigger;
				fetchProduct(trigger.getAttribute('data-quick-view-url'));
			}, true);

			document.querySelectorAll('[data-quick-view-close]').forEach(function(button) {
				button.addEventListener('click', closeModal);
			});

			modal.addEventListener('click', function(event) {
				if (event.target === modal) {
					closeModal();
				}
			});

			document.addEventListener('keydown', function(event) {
				if (event.key === 'Escape' && !modal.hidden) {
					closeModal();
					return;
				}

				if (event.key === 'Tab' && !modal.hidden) {
					var focusable = modal.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])');
					var first = focusable[0];
					var last = focusable[focusable.length - 1];

					if (!first || !last) {
						event.preventDefault();
						if (dialog) dialog.focus();
					} else if (event.shiftKey && document.activeElement === first) {
						event.preventDefault();
						last.focus();
					} else if (!event.shiftKey && document.activeElement === last) {
						event.preventDefault();
						first.focus();
					}
				}
			});

			if (quantity) {
				quantity.addEventListener('input', function() {
					var max = Number(quantity.max || 99);
					var value = Number(quantity.value || 1);

					if (value < 1) {
						quantity.value = 1;
					} else if (value > max) {
						quantity.value = max;
					}
				});
			}
		})();

		// Show floating quick-actions after scrolling; support remains available on mobile.
		(function() {
			var sideIcons = document.querySelector('.tgc-side-icons');
			var categoryRail = document.querySelector('[data-category-rail]');
			var backToTop = document.getElementById('backToTop');

			if (!sideIcons && !categoryRail && !backToTop) {
				return;
			}

			var scrollThreshold = 140;

			function toggleFloatingActions() {
				var currentScroll = window.pageYOffset || document.documentElement.scrollTop || 0;
				var isMobile = window.matchMedia('(max-width: 767px)').matches;
				var isVisible = currentScroll > scrollThreshold || isMobile;

				if (sideIcons) {
					sideIcons.classList.toggle('is-visible', isVisible);
				}

				if (categoryRail) {
					categoryRail.classList.toggle('is-visible', currentScroll > scrollThreshold);
				}

				if (backToTop) {
					backToTop.classList.toggle('is-visible', isVisible);
				}
			}

			toggleFloatingActions();
			window.addEventListener('scroll', toggleFloatingActions, { passive: true });
			window.addEventListener('resize', toggleFloatingActions);
		})();

		(function() {
			var toggle = document.getElementById('supportWidgetToggle');
			var widget = document.getElementById('supportWidget');

			if (!toggle || !widget) {
				return;
			}

			var closeButton = widget.querySelector('.support-widget-close');
			var answer = widget.querySelector('[data-support-answer]');
			var answerTitle = widget.querySelector('[data-support-answer-title]');
			var answerText = widget.querySelector('[data-support-answer-text]');
			var answerLink = widget.querySelector('[data-support-answer-link]');
			var topicButtons = Array.prototype.slice.call(widget.querySelectorAll('[data-support-topic]'));
			var lastFocusedElement = null;
			var hasAuthenticatedCustomer = widget.getAttribute('data-orders-authenticated') === 'true';
			var topics = {
				shipping: {
					title: 'Phí giao hàng',
					text: 'Phí và thời gian giao dự kiến được tính theo Tỉnh/Thành, loại sản phẩm và phương thức giao. Tổng phí được hiển thị trước khi bạn đặt hàng.',
					url: widget.getAttribute('data-shipping-url'),
					label: 'Xem chính sách giao hàng'
				},
				payment: {
					title: 'Phương thức thanh toán',
					text: 'Cửa hàng hỗ trợ thanh toán khi nhận hàng, chuyển khoản ngân hàng và MoMo trong môi trường demo.',
					url: widget.getAttribute('data-payment-url'),
					label: 'Xem chính sách thanh toán'
				},
				orders: {
					title: 'Theo dõi đơn hàng',
					text: hasAuthenticatedCustomer
						? 'Mở mục Đơn hàng để xem trạng thái, lịch sử xử lý, yêu cầu hủy hoặc đổi trả của bạn.'
						: 'Đăng nhập để xem trạng thái và lịch sử xử lý. Sau khi đăng nhập, hệ thống sẽ đưa bạn trở lại đúng mục Đơn hàng.',
					url: widget.getAttribute('data-orders-url'),
					label: hasAuthenticatedCustomer ? 'Mở đơn hàng của tôi' : 'Đăng nhập và theo dõi đơn'
				},
				returns: {
					title: 'Đổi trả và hoàn tiền',
					text: 'Hãy giữ hình ảnh sản phẩm và gửi yêu cầu trong thời hạn áp dụng. Cửa hàng sẽ kiểm tra điều kiện trước khi xác nhận phương án xử lý.',
					url: widget.getAttribute('data-returns-url'),
					label: 'Xem chính sách đổi trả'
				}
			};

			function openWidget() {
				lastFocusedElement = document.activeElement;
				widget.hidden = false;
				toggle.setAttribute('aria-expanded', 'true');
				document.body.classList.add('support-widget-open');
				closeButton.focus();
			}

			function closeWidget() {
				widget.hidden = true;
				toggle.setAttribute('aria-expanded', 'false');
				document.body.classList.remove('support-widget-open');
				if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
					lastFocusedElement.focus();
				}
			}

			toggle.addEventListener('click', function() {
				if (widget.hidden) {
					openWidget();
				} else {
					closeWidget();
				}
			});

			closeButton.addEventListener('click', closeWidget);

			topicButtons.forEach(function(button) {
				button.addEventListener('click', function() {
					var topicName = button.getAttribute('data-support-topic');
					var topic = topics[topicName];

					if (!topic) {
						return;
					}

					topicButtons.forEach(function(item) {
						var isSelected = item === button;
						item.classList.toggle('is-selected', isSelected);
						item.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
					});

					answerTitle.textContent = topic.title;
					answerText.textContent = topic.text;
					answerLink.textContent = topic.label;
					answerLink.href = topic.url;
					answer.hidden = false;
				});
			});

			document.addEventListener('keydown', function(event) {
				if (event.key === 'Escape' && !widget.hidden) {
					closeWidget();
				}
			});

			document.addEventListener('click', function(event) {
				if (!widget.hidden && !widget.contains(event.target) && !toggle.contains(event.target)) {
					closeWidget();
				}
			});
		})();

		(function() {
			var searchRoot = document.querySelector('.js-site-search');

			if (!searchRoot) {
				return;
			}

			var input = searchRoot.querySelector('.auto-search');
			var form = searchRoot.querySelector('form');
			var panel = searchRoot.querySelector('[data-search-suggest-panel]');
			var content = searchRoot.querySelector('[data-search-suggest-content]');
			var suggestUrl = searchRoot.getAttribute('data-suggest-url');
			var isAuthenticated = searchRoot.getAttribute('data-authenticated') === '1';
			var storageKey = 'fruitshop_recent_searches';
			var debounceTimer = null;
			var abortController = null;
			var lastPayload = {
				recent: [],
				popular: ['măng cụt', 'giỏ quà', 'cherry', 'sầu riêng', 'nho xanh'],
				products: []
			};

			if (!input || !form || !panel || !content || !suggestUrl) {
				return;
			}

			function cleanKeyword(value) {
				return (value || '').replace(/\s+/g, ' ').trim();
			}

			function escapeHtml(value) {
				return String(value || '')
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;')
					.replace(/'/g, '&#039;');
			}

			function guestHistory() {
				try {
					var raw = JSON.parse(localStorage.getItem(storageKey) || '[]');
					return Array.isArray(raw) ? raw.slice(0, 6) : [];
				} catch (error) {
					return [];
				}
			}

			function saveGuestKeyword(keyword) {
				if (isAuthenticated || keyword.length < 2) {
					return;
				}

				var normalized = keyword.toLowerCase();
				var next = [keyword].concat(guestHistory().filter(function(item) {
					return String(item).toLowerCase() !== normalized;
				})).slice(0, 6);

				try {
					localStorage.setItem(storageKey, JSON.stringify(next));
				} catch (error) {}
			}

			function keywordButton(keyword) {
				return '<button type="button" class="search-suggest-keyword" data-search-keyword="' + escapeHtml(keyword) + '">' + escapeHtml(keyword) + '</button>';
			}

			function renderKeywordSection(title, keywords) {
				if (!keywords || !keywords.length) {
					return '';
				}

				return '<div class="search-suggest-section">' +
					'<div class="search-suggest-title">' + escapeHtml(title) + '</div>' +
					'<div class="search-suggest-keywords">' + keywords.map(keywordButton).join('') + '</div>' +
				'</div>';
			}

			function renderProduct(product) {
				return '<a class="search-suggest-product" href="' + escapeHtml(product.url) + '">' +
					'<img src="' + escapeHtml(product.image) + '" alt="' + escapeHtml(product.name) + '" width="52" height="52" loading="lazy" decoding="async">' +
					'<span>' +
						'<span class="search-suggest-name">' + escapeHtml(product.name) + '</span>' +
						'<span class="search-suggest-meta">' + escapeHtml(product.category || 'Sản phẩm') + '</span>' +
						'<span class="search-suggest-price">' + escapeHtml(product.price || '') + '</span>' +
					'</span>' +
				'</a>';
			}

			function renderProductSection(products, keyword) {
				if (keyword.length < 2) {
					return '';
				}

				if (!products || !products.length) {
					return '<div class="search-suggest-section"><div class="search-suggest-empty">Chưa tìm thấy sản phẩm phù hợp.</div></div>';
				}

				return '<div class="search-suggest-section">' +
					'<div class="search-suggest-title">Sản phẩm gợi ý</div>' +
					products.map(renderProduct).join('') +
				'</div>';
			}

			function showPanel() {
				panel.hidden = false;
				input.setAttribute('aria-expanded', 'true');
			}

			function hidePanel() {
				panel.hidden = true;
				input.setAttribute('aria-expanded', 'false');
			}

			function render(payload) {
				var keyword = cleanKeyword(input.value);
				var recent = isAuthenticated ? (payload.recent || []) : guestHistory();
				var popular = payload.popular || lastPayload.popular || [];
				var html = '';

				if (keyword.length >= 2) {
					html += renderProductSection(payload.products || [], keyword);
				} else {
					html += renderKeywordSection('Tìm kiếm gần đây', recent);
					html += renderKeywordSection('Từ khóa phổ biến', popular);
				}

				if (!html) {
					html = '<div class="search-suggest-empty">Gõ tên trái cây, giỏ quà hoặc danh mục cần tìm.</div>';
				}

				content.innerHTML = html;
				showPanel();
			}

			function fetchSuggestions(keyword) {
				if (abortController) {
					abortController.abort();
				}

				abortController = window.AbortController ? new AbortController() : null;

				fetch(suggestUrl + '?q=' + encodeURIComponent(keyword), {
					headers: {
						'Accept': 'application/json'
					},
					signal: abortController ? abortController.signal : undefined
				})
					.then(function(response) {
						return response.ok ? response.json() : lastPayload;
					})
					.then(function(payload) {
						lastPayload = payload || lastPayload;
						render(lastPayload);
					})
					.catch(function(error) {
						if (error && error.name === 'AbortError') {
							return;
						}

						render(lastPayload);
					});
			}

			function scheduleSuggestions() {
				window.clearTimeout(debounceTimer);
				debounceTimer = window.setTimeout(function() {
					fetchSuggestions(cleanKeyword(input.value));
				}, 220);
			}

			input.addEventListener('focus', function() {
				fetchSuggestions(cleanKeyword(input.value));
			});

			input.addEventListener('input', scheduleSuggestions);

			form.addEventListener('submit', function(event) {
				var keyword = cleanKeyword(input.value);

				if (!keyword) {
					event.preventDefault();
					input.focus();
					render(lastPayload);
					return;
				}

				input.value = keyword;
				saveGuestKeyword(keyword);
			});

			content.addEventListener('click', function(event) {
				var button = event.target.closest('[data-search-keyword]');

				if (!button) {
					return;
				}

				input.value = button.getAttribute('data-search-keyword') || '';
				saveGuestKeyword(input.value);
				form.submit();
			});

			document.addEventListener('click', function(event) {
				if (!searchRoot.contains(event.target)) {
					hidePanel();
				}
			});

			document.addEventListener('keydown', function(event) {
				if (event.key === 'Escape') {
					hidePanel();
				}
			});
		})();

		@if(!empty($salesPopupProducts))
		(function() {
			var products = @json($salesPopupProducts);
			var toast = document.getElementById('salesPopToast');
			var link = document.getElementById('salesPopLink');
			var image = document.getElementById('salesPopImage');
			var name = document.getElementById('salesPopName');
			var time = document.getElementById('salesPopTime');
			var close = document.getElementById('salesPopClose');
			var storageKey = 'sales_popup_closed';
			var showTimer = null;
			var hideTimer = null;
			var index = Math.floor(Math.random() * products.length);
			var minuteOptions = [4, 8, 12, 17, 23, 31, 42, 52];

			if (!toast || !link || !image || !name || !time || !Array.isArray(products) || products.length === 0) {
				return;
			}

			try {
				if (sessionStorage.getItem(storageKey) === '1') {
					return;
				}
			} catch (error) {}

			function getNextProduct() {
				var tries = 0;
				var product = null;

				while (tries < products.length) {
					product = products[index % products.length];
					index += 1;
					tries += 1;

					if (product && product.name && product.url && product.image) {
						return product;
					}
				}

				return null;
			}

			function renderProduct(product) {
				var minutes = minuteOptions[Math.floor(Math.random() * minuteOptions.length)];

				link.href = product.url;
				image.src = product.image;
				image.alt = product.name;
				name.textContent = product.name;
				time.textContent = minutes;
			}

			function hideToast(scheduleNext) {
				toast.classList.remove('is-visible');

				if (scheduleNext) {
					showTimer = window.setTimeout(showToast, 9000);
				}
			}

			function showToast() {
				var product = getNextProduct();

				if (!product) {
					return;
				}

				renderProduct(product);
				toast.classList.add('is-visible');
				hideTimer = window.setTimeout(function() {
					hideToast(true);
				}, 5800);
			}

			if (close) {
				close.addEventListener('click', function(event) {
					event.preventDefault();
					window.clearTimeout(showTimer);
					window.clearTimeout(hideTimer);
					hideToast(false);

					try {
						sessionStorage.setItem(storageKey, '1');
					} catch (error) {}
				});
			}

			showTimer = window.setTimeout(showToast, 1800);
		})();
		@endif
	</script>
	<script>
		(function () {
			var root = document.querySelector('[data-header-notification]');
			if (!root) return;

			var toggle = root.querySelector('[data-notification-toggle]');
			var menu = root.querySelector('[data-notification-menu]');
			if (!toggle || !menu) return;

			function setOpen(open) {
				toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
				menu.hidden = !open;
			}

			toggle.addEventListener('click', function () {
				setOpen(toggle.getAttribute('aria-expanded') !== 'true');
			});

			document.addEventListener('click', function (event) {
				if (!root.contains(event.target)) setOpen(false);
			});

			document.addEventListener('keydown', function (event) {
				if (event.key === 'Escape') {
					setOpen(false);
					toggle.focus();
				}
			});
		})();
	</script>
	@include('partials.analytics-consent')
	@stack('scripts')

</body>
</html>
