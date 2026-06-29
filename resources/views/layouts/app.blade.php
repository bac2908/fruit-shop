<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>@yield('title', 'Thế Giới Trái Cây - Trái cây Việt Nam loại 1 & nhập khẩu cao cấp')</title>
	<meta name="description" content="@yield('meta_description', 'The Gioi Trai Cay - Trai cay Viet Nam loai 1 va nhap khau chat luong cao.')">

	<link rel="canonical" href="@yield('canonical', url()->current())" />
	<meta name="robots" content="index,follow" />
	<meta name="revisit-after" content="1 day" />
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="HandheldFriendly" content="true">
	<link rel="icon" href="//theme.hstatic.net/200000157781/1001036201/14/favicon.png?v=1061" type="image/x-icon" />

	<!-- Fonts & Icons -->
	<link rel="stylesheet" href="https://cdn.linearicons.com/free/1.0.0/icon-font.min.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
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
	@stack('head_meta')

	@yield('styles')
</head>
<body>
	@php
		$headerCartCount = collect(session('cart', []))->sum(function ($item) {
			return (int) ($item['quantity'] ?? 0);
		});
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
							<img src="//theme.hstatic.net/200000157781/1001036201/14/logo.png?v=1061" alt="logo Thế Giới Trái Cây">
						</a>
					</div>
				</div>

				{{-- Policy and Cart --}}
				<div class="col-xs-12 col-md-9 hidden-xs">
					<div class="header-right d-flex align-items-center justify-content-between">
						<div class="policy d-flex justify-content-around flex-1">
							<div class="item-policy d-flex align-items-center">
								<a href="#"><img src="//theme.hstatic.net/200000157781/1001036201/14/policy1.png?v=1061" alt=""></a>
								<div class="info">
									<a href="{{ route('page.shipping.payment') }}">Giao nhanh TP.HCM</a>
									<p>30 - 90 phút, tỉnh xác nhận riêng</p>
								</div>
							</div>
							<div class="item-policy d-flex align-items-center">
								<a href="#"><img src="//theme.hstatic.net/200000157781/1001036201/14/policy2.png?v=1061" alt=""></a>
								<div class="info">
								<a href="#">Hỗ trợ 24/7</a>
									<p>Hotline: 0333499426</p>
								</div>
							</div>
							<div class="item-policy d-flex align-items-center">
								<a href="#"><img src="//theme.hstatic.net/200000157781/1001036201/14/policy3.png?v=1061" alt=""></a>
								<div class="info">
								<a href="#">Giờ làm việc</a>
									<p>T2 - CN (7:00-19:00)</p>
								</div>
							</div>
						</div>

						<div class="top-cart-contain">
							<div class="mini-cart">
								<div class="heading-cart">
									<a href="{{ route('cart') }}" class="d-flex align-items-center">
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

		<div class="menu-bar hidden-md hidden-lg">
			<img src='//theme.hstatic.net/200000157781/1001036201/14/menu-bar.png?v=1061' alt='menu bar'  />
		</div>
		<div class="icon-cart-mobile hidden-md hidden-lg f-left absolute" data-href="{{ route('cart') }}" onclick="window.location.href=this.getAttribute('data-href');">
			<div class="icon relative">
				<i class="fa fa-shopping-bag"></i>
				<span class="cartCount count_item_pr">{{ $headerCartCount }}</span>
			</div>
		</div>
	</div>
	<nav>
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
					<div class="header_search search_form">
						<form class="input-group search-bar search_form" action="{{ route('search') }}" method="get" role="search">
							<input type="search" name="q" value="" placeholder="Tìm sản phẩm" class="input-group-field search-text auto-search" autocomplete="off">
							<span class="input-group-btn">
								<button class="btn">
									<i class="fa fa-search"></i>
								</button>
							</span>
						</form>
					</div>
				</div>
			</div>
		</div>
	</nav>
</header>

	<main>
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

	@if(session('cart_added'))
		@php
			$cartAdded = session('cart_added');
		@endphp
		<div class="cart-added-overlay is-visible" id="cartAddedModal" role="dialog" aria-modal="true" aria-labelledby="cartAddedTitle">
			<div class="cart-added-modal">
				<button type="button" class="cart-added-close" data-cart-modal-close aria-label="Đóng">&times;</button>
				<div class="cart-added-head">
					<i class="fa fa-check-square-o" aria-hidden="true"></i>
					<h2 id="cartAddedTitle">Sản phẩm đã được thêm vào giỏ hàng</h2>
				</div>
				<div class="cart-added-product">
					<img src="{{ $cartAdded['image'] ?? '//theme.hstatic.net/200000157781/1001036201/14/no-image.jpg?v=1064' }}" alt="{{ $cartAdded['name'] ?? 'Sản phẩm' }}">
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

	<style>
		.site-flash-container {
			margin-top: 16px;
		}

		.site-flash {
			border-radius: 8px;
			font-weight: 700;
			line-height: 1.5;
			padding: 12px 16px;
		}

		.site-flash-success {
			background: #eef8e8;
			border: 1px solid #cfe6bf;
			color: #365f18;
		}

		.site-flash-error {
			background: #fff3f0;
			border: 1px solid #ffd3ca;
			color: #9c341f;
		}

		.cart-added-overlay {
			align-items: flex-start;
			background: rgba(17, 32, 22, 0.32);
			display: none;
			inset: 0;
			justify-content: center;
			padding: 96px 16px 24px;
			position: fixed;
			z-index: 10050;
		}

		.cart-added-overlay.is-visible {
			display: flex;
		}

		.cart-added-modal {
			background: #fff;
			border-radius: 8px;
			box-shadow: 0 24px 70px rgba(19, 35, 21, 0.24);
			max-width: 520px;
			padding: 28px 32px 30px;
			position: relative;
			width: min(100%, 520px);
		}

		.cart-added-close {
			align-items: center;
			background: transparent;
			border: 0;
			color: #b8b8b8;
			display: flex;
			font-size: 30px;
			height: 34px;
			justify-content: center;
			line-height: 1;
			position: absolute;
			right: 10px;
			top: 8px;
			width: 34px;
		}

		.cart-added-close:hover {
			color: #6f7d65;
		}

		.cart-added-head {
			align-items: center;
			border-bottom: 1px solid #edf0e9;
			display: flex;
			gap: 10px;
			padding-bottom: 16px;
		}

		.cart-added-head i {
			color: #5b8d1f;
			font-size: 24px;
		}

		.cart-added-head h2 {
			color: #1f2e1d;
			font-family: inherit;
			font-size: 20px;
			font-weight: 700;
			line-height: 1.35;
			margin: 0;
		}

		.cart-added-product {
			display: flex;
			gap: 16px;
			padding: 24px 0;
		}

		.cart-added-product img {
			border: 1px solid #edf0e9;
			border-radius: 6px;
			flex: 0 0 96px;
			height: 96px;
			object-fit: cover;
			width: 96px;
		}

		.cart-added-info {
			display: grid;
			gap: 8px;
			min-width: 0;
		}

		.cart-added-info strong {
			color: #1e2c1c;
			font-size: 16px;
			line-height: 1.45;
		}

		.cart-added-info span {
			color: #65705f;
			font-size: 14px;
		}

		.cart-added-info span:last-child {
			color: #5f922b;
			font-size: 18px;
			font-weight: 700;
		}

		.cart-added-summary {
			align-items: center;
			border-top: 1px solid #edf0e9;
			color: #253421;
			display: flex;
			gap: 8px;
			font-size: 16px;
			padding: 18px 0;
		}

		.cart-added-summary i {
			color: #1f2e1d;
		}

		.cart-added-actions {
			display: grid;
			gap: 10px;
		}

		.cart-added-btn {
			align-items: center;
			border-radius: 4px;
			display: inline-flex;
			font-size: 16px;
			font-weight: 700;
			height: 48px;
			justify-content: center;
			text-align: center;
			text-decoration: none !important;
			width: 100%;
		}

		.cart-added-btn-primary {
			background: #7fbe2d;
			border: 1px solid #7fbe2d;
			color: #fff !important;
		}

		.cart-added-btn-primary:hover {
			background: #6da822;
			border-color: #6da822;
		}

		.cart-added-btn-outline,
		.cart-added-btn-light {
			background: #fff;
			border: 1px solid #dfe8d4;
			color: #3f5f21 !important;
		}

		.cart-added-btn-light {
			background: #f8fbf3;
		}

		@media (max-width: 575px) {
			.cart-added-overlay {
				align-items: flex-end;
				padding: 16px;
			}

			.cart-added-modal {
				padding: 24px 18px 20px;
			}

			.cart-added-product {
				gap: 12px;
			}

			.cart-added-product img {
				flex-basis: 78px;
				height: 78px;
				width: 78px;
			}
		}

		/* Sticky Sidebar - Contact/Social Icons */
		.contact-float-wrapper {
			position: fixed !important;
			right: 0;
			top: 50%;
			transform: translateY(-50%);
			z-index: 9999;
			display: flex;
			flex-direction: column;
			gap: 0;
		}

		.contact-float-item {
			width: 48px;
			height: 48px;
			display: flex;
			align-items: center;
			justify-content: center;
			border-radius: 50%;
			cursor: pointer;
			transition: all 0.3s ease;
			box-shadow: 0 2px 8px rgba(0,0,0,0.15);
		}

		.contact-float-item a {
			width: 100%;
			height: 100%;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #fff !important;
			text-decoration: none !important;
			font-size: 20px;
			border-radius: 50%;
		}

		.contact-float-item a:hover {
			transform: scale(1.1);
		}

		.contact-float-item.phone { background: #ef4444; }
		.contact-float-item.phone:hover { background: #dc2626; }

		.contact-float-item.zalo { background: #0084ff; }
		.contact-float-item.zalo:hover { background: #0073e6; }

		.contact-float-item.email { background: #ec4899; }
		.contact-float-item.email:hover { background: #be185d; }

		.contact-float-item.shop { background: #ea580c; }
		.contact-float-item.shop:hover { background: #c2410c; }

		.contact-float-item.instagram { background: #e1306c; }
		.contact-float-item.instagram:hover { background: #c13584; }

		.contact-float-item.tiktok { background: #000; }
		.contact-float-item.tiktok:hover { background: #333; }

		.contact-float-item.location { background: #f59e0b; }
		.contact-float-item.location:hover { background: #d97706; }

		@media (max-width: 768px) {
			.contact-float-wrapper {
				right: 10px;
				gap: 8px;
			}
			.contact-float-item {
				width: 44px;
				height: 44px;
			}
		}

		.fixed-sidebar,
		.sidebar-contact,
		[class*="contact-sidebar"],
		.contact-float {
			position: fixed !important;
			right: 0;
			top: 50%;
			transform: translateY(-50%);
			z-index: 9999;
		}

		/* Flexbox Utils */
		.d-flex { display: flex !important; }
		.align-items-center { align-items: center !important; }
		.justify-content-between { justify-content: space-between !important; }

		/* Topbar */
		.topbar { background: #8bc34a !important; color: #fff !important; padding: 10px 0; font-size: 13px; }
		.topbar a { color: #fff !important; text-decoration: none; font-weight: bold; }
		.topbar i { margin-right: 5px; }

		/* Header */
		.header-content { padding: 20px 0; background: #fff; }
		.item-policy { padding: 0 15px; }
		.item-policy .info a { font-weight: bold; color: #333; font-size: 14px; }
		.item-policy .info p { font-size: 12px; color: #666; margin: 0; }
		.icon.relative { background: #ff9800 !important; color: #fff !important; padding: 10px 20px; border-radius: 25px; transition: 0.3s; }
		.icon.relative:hover { background: #e68a00 !important; }

		/* Nav - Sticky fallback using fixed when scrolled */
		nav {
			background: #8bc34a !important;
			border-top: 1px solid rgba(255,255,255,0.1) !important;
			position: relative !important;
			z-index: 1000 !important;
			width: 100% !important;
			margin: 0 !important;
			padding: 0 !important;
			box-sizing: border-box !important;
			box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
			transition: position 0.3s ease !important;
		}
		nav.fixed-nav {
			position: fixed !important;
			top: 0 !important;
			left: 0 !important;
			right: 0 !important;
		}
		nav > .container {
			max-width: 100%;
			margin: 0 auto;
		}
		nav > .container > .hidden-sm.hidden-xs {
			position: relative !important;
			min-height: 52px;
			align-items: stretch !important;
		}
		nav .nav-left {
			display: flex !important;
			align-items: stretch;
			flex-wrap: nowrap;
			margin: 0;
			padding: 0;
			list-style: none;
			min-width: 0;
		}
		nav .nav-left > .nav-item {
			display: block;
			float: none !important;
			position: relative;
		}
		nav .nav-left > .nav-item > .nav-link { color: #fff !important; font-weight: bold; text-transform: none; font-size: 14px; padding: 15px 20px !important; display: flex; align-items: center; height: 100%; }
		nav .nav-left > .nav-item.active > .nav-link { background: #ff9800 !important; }
		nav .nav-left > .nav-item:hover > .nav-link { background: rgba(0,0,0,0.05); }

		.bread_crumb {
			position: relative;
			z-index: 1;
			margin-top: 12px;
		}

		nav .nav-left > .nav-item.has-mega {
			position: static !important;
		}

		nav .nav-left > .nav-item.has-mega .mega-content {
			position: absolute !important;
			top: 100% !important;
			left: 0 !important;
			right: auto !important;
			display: none !important;
			width: min(1120px, calc(100vw - 32px)) !important;
			max-width: calc(100vw - 32px) !important;
			padding: 14px 18px 18px !important;
			border: 1px solid #ececec;
			background: #fff;
			box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
			z-index: 1002;
			opacity: 0;
			visibility: hidden;
			pointer-events: none;
			transform: translateY(8px);
			transition: opacity .18s ease, transform .18s ease, visibility .18s ease;
		}

		nav .nav-left > .nav-item.has-mega:hover .mega-content,
		nav .nav-left > .nav-item.has-mega:focus-within .mega-content {
			display: block !important;
			margin-top: 0 !important;
			opacity: 1;
			visibility: visible;
			pointer-events: auto;
			transform: translateY(0);
		}

		nav .nav-left > .nav-item.has-mega .level0-wrapper2,
		nav .nav-left > .nav-item.has-mega .nav-block,
		nav .nav-left > .nav-item.has-mega .nav-block.nav-block-center {
			width: 100% !important;
			max-width: 100% !important;
		}

		nav .nav-left > .nav-item.has-mega .level0 {
			display: grid;
			grid-template-columns: repeat(4, minmax(180px, 1fr));
			gap: 24px;
			margin: 0;
			padding: 0;
			list-style: none;
		}

		nav .nav-left > .nav-item.has-mega .level1.parent.item {
			margin: 0;
			padding: 0;
			list-style: none;
			float: none !important;
			width: auto !important;
			min-width: 0;
		}

		nav .nav-left > .nav-item.has-mega .level1.parent.item > h2 {
			margin: 0 0 10px;
			font-size: 16px;
			line-height: 1.2;
		}

		nav .nav-left > .nav-item.has-mega .level1.parent.item > h2 a {
			color: #1f1f1f !important;
			text-decoration: none !important;
			font-weight: 700;
		}

		nav .nav-left > .nav-item.has-mega .level1.parent.item > h2 a span {
			display: block;
			white-space: nowrap;
			overflow: visible;
			text-overflow: clip;
			max-width: none;
			word-break: keep-all;
			overflow-wrap: normal;
		}

		nav .nav-left > .nav-item.has-mega .level1.parent.item > ul.level1 {
			margin: 0;
			padding: 0;
			list-style: none;
		}

		nav .nav-left > .nav-item.has-mega .level1.parent.item > ul.level1 > li.level2 {
			margin: 0 0 10px;
			padding: 0;
			float: none !important;
			width: auto !important;
			min-width: 0;
		}

		nav .nav-left > .nav-item.has-mega .level1.parent.item > ul.level1 > li.level2:last-child {
			margin-bottom: 0;
		}

		nav .nav-left > .nav-item.has-mega .level1.parent.item > ul.level1 > li.level2 a {
			color: #333 !important;
			font-size: 14px;
			line-height: 1.25;
			font-weight: 500;
			text-decoration: none !important;
			white-space: nowrap;
			overflow: visible;
			text-overflow: clip;
			display: block;
			max-width: none;
			word-break: keep-all;
			overflow-wrap: normal;
		}

		nav .nav-left > .nav-item.has-mega .level1.parent.item > ul.level1 > li.level2 a:hover {
			color: #7fbe3b !important;
		}

		@media (max-width: 1399px) {
			nav .nav-left > .nav-item.has-mega .level1.parent.item > h2 {
				font-size: 15px;
			}

			nav .nav-left > .nav-item.has-mega .level1.parent.item > ul.level1 > li.level2 a {
				font-size: 13px;
			}
		}

		/* Search */
		.menu-search { flex: 0 0 auto; padding: 8px 0; }
		.header_search form { background: #fff; border-radius: 25px; height: 34px; padding: 0 15px; width: 250px; display: flex; align-items: center; }
		.header_search input { border: none !important; width: 100%; font-size: 13px; outline: none !important; height: 100%; }
		.header_search .btn { color: #333 !important; font-size: 16px; padding: 0; background: none; }

		/* Grid Failsafe */
		.row { margin-left: -15px; margin-right: -15px; display: block; }
		.row:before, .row:after { content: " "; display: table; }
		.row:after { clear: both; }
		[class*="col-"] { position: relative; min-height: 1px; padding-left: 15px; padding-right: 15px; float: left; box-sizing: border-box; }
		.col-md-3 { width: 25%; }
		.col-md-9 { width: 75%; }
		.col-md-4 { width: 33.33333333%; }
		.col-md-8 { width: 66.66666667%; }
		.col-md-12 { width: 100%; }

		@media (max-width: 991px) {
			[class*="col-md-"] { width: 100% !important; float: none !important; }
		}

		/* Product UI matching */
		.product-box { border: 1px solid #eee; transition: 0.3s; padding: 10px; background: #fff; border-radius: 10px; margin-bottom: 20px; position: relative; overflow: hidden; }
		.product-box:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); border-color: #8bc34a; }
		.product-thumbnail img { width: 100%; height: auto; object-fit: cover; }
		.product-name a { color: #333 !important; font-size: 14px; font-weight: 700; text-decoration: none; display: block; margin-top: 10px; height: 40px; overflow: hidden; }

		.product-action { display: none; position: absolute; bottom: 20px; left: 0; right: 0; text-align: center; }
		.product-box:hover .product-action { display: block; }
		.btn-cart, .btn_view, .product-detail-link { background: #8bc34a; color: #fff; width: 36px; height: 36px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin: 0 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }

		.sale-flash { position: absolute; top: 10px; left: 10px; background: #ff9800; color: #fff; padding: 2px 10px; border-radius: 15px; font-size: 12px; font-weight: bold; z-index: 5; }

		/* Coupons */
		.coupon-item__inner { border: 1px dashed #8bc34a; display: flex; padding: 15px; border-radius: 8px; background: #f9fff0; margin-bottom: 15px; }

		/* Footer Replica (thegioitraicay.net style) */
		.section_brand.section,
		.section.section-brand {
			display: none !important;
		}

		.tgc-brand-strip {
			background: #fff;
			padding: 22px 0 18px;
			border-top: 1px solid #efefef;
		}

		.tgc-brand-grid {
			display: grid;
			grid-template-columns: repeat(6, minmax(90px, 1fr));
			gap: 24px;
			align-items: center;
		}

		.tgc-brand-item {
			display: flex;
			align-items: center;
			justify-content: center;
			min-height: 72px;
		}

		.tgc-brand-item img {
			max-width: 100%;
			max-height: 68px;
			object-fit: contain;
			filter: saturate(0.95);
		}

		.footer {
			background: #679a24;
			color: #fff;
			margin-top: 0;
			padding-top: 0;
			border-top: 0;
		}

		.footer .site-footer {
			background: #679a24;
		}

		.footer .footer-inner {
			padding: 36px 0 28px;
		}

		.footer .footer-widget {
			margin-bottom: 18px;
		}

		.footer .footer-widget h3 {
			color: #fff;
			font-size: 16px;
			font-weight: 700;
			line-height: 1.45;
			margin: 0 0 10px;
			text-transform: uppercase;
		}

		.footer .list-menu {
			list-style: none;
			padding: 0;
			margin: 0;
		}

		.footer .list-menu li {
			margin-bottom: 0;
			padding: 3px 0;
			color: #fff;
			font-size: 15px;
			line-height: 1.7;
		}

		.footer .list-menu li a {
			color: #fff !important;
			text-decoration: none;
			transition: opacity .18s ease, transform .18s ease;
		}

		.footer .list-menu li a:hover {
			opacity: 1;
		}

		.footer .footer-contact-list li {
			display: flex;
			align-items: flex-start;
			gap: 8px;
		}

		.footer .footer-contact-list li i {
			color: #ff9f0e;
			font-size: 16px;
			margin-top: 6px;
			min-width: 20px;
		}

		.footer .footer-contact-list .line-break {
			display: block;
			margin-left: 0;
		}

		.footer .menu-dot li {
			padding-left: 16px;
			position: relative;
		}

		.footer .menu-dot li::before {
			content: '';
			position: absolute;
			left: 0;
			top: 12px;
			width: 8px;
			height: 8px;
			border-radius: 50%;
			background: #fff;
			transition: background .18s ease, box-shadow .18s ease, transform .18s ease;
		}

		.footer .menu-dot li:hover::before,
		.footer .menu-dot li:focus-within::before {
			background: #ff9f0e;
			box-shadow: 0 0 0 4px rgba(255, 159, 14, 0.16);
			transform: scale(1.12);
		}

		.footer .menu-dot li:hover a,
		.footer .menu-dot li:focus-within a {
			transform: translateX(2px);
		}

		.footer .footer-connect {
			min-height: 118px;
			display: flex;
			align-items: center;
			gap: 16px;
		}

		.footer .footer-connect .divider {
			width: 1px;
			height: 68px;
			background: rgba(255, 255, 255, 0.55);
		}

		.footer .footer-connect .connect-label {
			font-size: 16px;
			font-style: italic;
			font-weight: 500;
			color: #fff;
			text-decoration: none;
		}

		.footer .footer-connect .connect-label:hover {
			opacity: 0.85;
		}

		.footer .copyright {
			background: rgba(0, 0, 0, 0.2);
			color: #fff;
			padding: 10px 0;
			margin-top: 0;
			position: relative;
		}

		.footer .copyright .copyright-text {
			font-size: 15px;
			font-weight: 600;
		}

		.footer .copyright .list-menu-footer {
			list-style: none;
			margin: 0;
			padding: 0;
			display: flex;
			justify-content: flex-end;
			gap: 0;
		}

		.footer .copyright .list-menu-footer li {
			display: inline-block;
			padding: 0 10px;
		}

		.footer .copyright .list-menu-footer li a {
			color: #fff !important;
			font-size: 15px;
			font-weight: 400;
			text-decoration: none;
		}

		.footer .back-to-top {
			position: fixed;
			right: 14px;
			bottom: 16px;
			width: 54px;
			height: 46px;
			background: #7bb337;
			color: #fff;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			font-size: 20px;
			cursor: pointer;
			border-radius: 2px;
			z-index: 9998;
			box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
			opacity: 0;
			visibility: hidden;
			pointer-events: none;
			transform: translateY(10px);
			transition: opacity 0.25s ease, transform 0.25s ease, visibility 0.25s ease;
		}

		.footer .back-to-top.is-visible {
			opacity: 1;
			visibility: visible;
			pointer-events: auto;
			transform: translateY(0);
		}

		.tgc-side-icons {
			position: fixed;
			right: 14px;
			top: 50%;
			transform: translateY(-50%);
			z-index: 9999;
			display: flex;
			flex-direction: column;
			gap: 14px;
			opacity: 0;
			visibility: hidden;
			pointer-events: none;
			transform: translateY(calc(-50% + 12px));
			transition: opacity 0.25s ease, transform 0.25s ease, visibility 0.25s ease;
		}

		.tgc-side-icons.is-visible {
			opacity: 1;
			visibility: visible;
			pointer-events: auto;
			transform: translateY(-50%);
		}

		.tgc-side-icons .icon-item {
			width: 54px;
			height: 54px;
			border-radius: 50%;
			display: inline-flex;
			align-items: center;
			justify-content: center;
			color: #fff !important;
			text-decoration: none !important;
			box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
			font-size: 23px;
		}

		.tgc-side-icons .icon-item.phone { background: #ea2f2f; }
		.tgc-side-icons .icon-item.zalo { background: #2f83ff; font-size: 15px; font-weight: 700; letter-spacing: 0.2px; }
		.tgc-side-icons .icon-item.youtube { background: #ff1b1b; font-size: 20px; }
		.tgc-side-icons .icon-item.instagram {
			background: linear-gradient(145deg, #f9ce34, #ee2a7b, #6228d7);
		}
		.tgc-side-icons .icon-item.tiktok { background: #000; }
		.tgc-side-icons .icon-item.location { background: #f2ae16; }
		.tgc-side-icons .icon-item.messenger { background: #22a8ff; }

		.sales-pop-toast {
			position: fixed;
			left: 28px;
			bottom: 24px;
			z-index: 10001;
			width: min(478px, calc(100vw - 56px));
			min-height: 84px;
			background: #fff;
			border-radius: 3px;
			box-shadow: 0 12px 28px rgba(20, 30, 16, 0.2);
			opacity: 0;
			visibility: hidden;
			pointer-events: none;
			transform: translateY(16px);
			transition: opacity .24s ease, transform .24s ease, visibility .24s ease;
		}

		.sales-pop-toast.is-visible {
			opacity: 1;
			visibility: visible;
			pointer-events: auto;
			transform: translateY(0);
		}

		.sales-pop-link {
			display: grid;
			grid-template-columns: 86px minmax(0, 1fr);
			align-items: center;
			gap: 13px;
			width: 100%;
			min-height: 84px;
			padding: 13px 46px 13px 13px;
			color: #333 !important;
			text-decoration: none !important;
		}

		.sales-pop-image {
			width: 86px;
			height: 58px;
			object-fit: cover;
			background: #f4f7ef;
		}

		.sales-pop-copy {
			min-width: 0;
		}

		.sales-pop-name {
			display: block;
			color: #2d2d2d;
			font-size: 17px;
			font-weight: 800;
			line-height: 1.25;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.sales-pop-message {
			display: block;
			margin-top: 7px;
			color: #808080;
			font-size: 14px;
			line-height: 1.35;
		}

		.sales-pop-close {
			position: absolute;
			top: 7px;
			right: 8px;
			width: 28px;
			height: 28px;
			padding: 0;
			border: 0;
			background: transparent;
			color: #444;
			font-size: 28px;
			font-weight: 700;
			line-height: 24px;
			cursor: pointer;
		}

		.sales-pop-close:hover {
			color: #111;
		}

		@media (max-width: 1399px) {
			.footer .footer-inner { padding: 34px 0 26px; }
		}

		@media (max-width: 1199px) {
			.tgc-brand-grid {
				grid-template-columns: repeat(4, minmax(90px, 1fr));
				gap: 18px;
			}

			.footer .footer-widget h3 { font-size: 15px; margin-bottom: 8px; }
			.footer .list-menu li { font-size: 14px; }
			.footer .footer-connect .connect-label { font-size: 15px; }
		}

		@media (max-width: 991px) {
			.tgc-brand-strip {
				padding: 18px 0 16px;
			}

			.tgc-brand-grid {
				grid-template-columns: repeat(3, minmax(80px, 1fr));
				gap: 14px;
			}

			.footer .footer-inner {
				padding: 28px 0 22px;
			}

			.footer .footer-widget h3 { font-size: 15px; }
			.footer .list-menu li { font-size: 14px; }
			.footer .footer-connect { min-height: 84px; gap: 14px; }
			.footer .footer-connect .divider { height: 58px; }
			.footer .footer-connect .connect-label { font-size: 15px; }
			.footer .copyright .list-menu-footer {
				justify-content: flex-start;
				gap: 16px;
				flex-wrap: wrap;
				margin-top: 8px;
			}

			.footer .back-to-top {
				display: none;
			}
		}

		@media (max-width: 767px) {
			.tgc-side-icons { display: none; }
			.sales-pop-toast {
				left: 12px;
				right: 12px;
				bottom: 14px;
				width: auto;
				min-height: 76px;
			}
			.sales-pop-link {
				grid-template-columns: 74px minmax(0, 1fr);
				min-height: 76px;
				gap: 11px;
				padding: 11px 38px 11px 11px;
			}
			.sales-pop-image {
				width: 74px;
				height: 54px;
			}
			.sales-pop-name {
				font-size: 14px;
			}
			.sales-pop-message {
				margin-top: 5px;
				font-size: 12px;
			}
			.sales-pop-close {
				top: 5px;
				right: 5px;
			}
			.footer .footer-widget { margin-bottom: 18px; }
			.footer .list-menu li { font-size: 14px; }
			.footer .footer-contact-list .line-break { margin-left: 0; }
			.footer .footer-contact-list li {
				align-items: flex-start;
			}
		}

		/* 2026 step-1 visual layer */
		:root {
			--v26-bg: #eef3e8;
			--v26-ink: #1f2f1f;
			--v26-muted: #5e6f5b;
			--v26-primary: #669a29;
			--v26-primary-strong: #4f781f;
			--v26-accent: #f1a428;
			--v26-line: #d8e2ce;
			--v26-paper: #ffffff;
			--v26-shadow: 0 18px 38px rgba(45, 76, 24, 0.12);
			--v26-radius: 18px;
		}

		body {
			font-family: 'Manrope', 'Roboto', sans-serif;
			color: var(--v26-ink);
			background:
				radial-gradient(circle at 3% -5%, #f8f3cf 0%, transparent 30%),
				radial-gradient(circle at 95% 0%, #e1f1ce 0%, transparent 24%),
				var(--v26-bg);
		}

		h1, h2, h3, .title-head, .section-title h2 {
			font-family: 'Fraunces', serif;
		}

		a:focus-visible,
		button:focus-visible,
		input:focus-visible {
			outline: 2px solid var(--v26-accent);
			outline-offset: 2px;
		}

		.topbar {
			background: linear-gradient(95deg, #76ad33 0%, #8ac842 100%) !important;
			display: flex;
			align-items: center;
			font-weight: 600;
			min-height: 46px;
			padding: 0 !important;
		}

		.topbar > .container,
		.topbar > .container > .d-flex {
			min-height: 46px;
		}

		.topbar-left,
		.topbar-right {
			align-items: center;
			display: flex;
			min-height: 46px;
		}

		.topbar-left span {
			align-items: center;
			display: inline-flex;
		}

		.topbar-right {
			align-items: center;
			display: flex;
			justify-content: flex-end;
		}

		.topbar-account {
			align-items: center;
			background: rgba(31, 83, 10, 0.16);
			border: 1px solid rgba(255, 255, 255, 0.36);
			border-radius: 999px;
			box-shadow: 0 4px 10px rgba(43, 82, 18, 0.14), inset 0 1px 0 rgba(255, 255, 255, 0.18);
			display: inline-flex;
			gap: 5px;
			max-width: 100%;
			padding: 4px;
		}

		.topbar-account-pill {
			align-items: center;
			border: 0;
			border-radius: 999px;
			display: inline-flex;
			font-size: 13px;
			font-weight: 800 !important;
			gap: 7px;
			justify-content: center;
			line-height: 1;
			min-height: 30px;
			padding: 0 13px;
			text-decoration: none !important;
			transition: background-color .2s ease, color .2s ease, transform .2s ease, box-shadow .2s ease;
			white-space: nowrap;
		}

		.topbar-account-pill:hover,
		.topbar-account-pill:focus {
			transform: translateY(-1px);
		}

		.topbar-account-pill i {
			font-size: 14px;
			margin-right: 0 !important;
		}

		.topbar-account-pill span {
			display: block;
			max-width: 150px;
			overflow: hidden;
			text-overflow: ellipsis;
		}

		.topbar .topbar-account-primary {
			background: #ffffff;
			box-shadow: 0 4px 10px rgba(44, 91, 13, 0.18);
			color: #447316 !important;
		}

		.topbar .topbar-account-primary:hover,
		.topbar .topbar-account-primary:focus {
			background: #f7ffe9;
			color: #31590f !important;
		}

		.topbar .topbar-account-register {
			background: #ff9f1a;
			box-shadow: 0 4px 10px rgba(153, 88, 0, 0.2);
			color: #fff !important;
		}

		.topbar .topbar-account-register:hover,
		.topbar .topbar-account-register:focus {
			background: #f08e00;
			color: #fff !important;
		}

		.topbar-logout-form {
			display: inline-flex;
			margin: 0;
		}

		.topbar .topbar-account-logout {
			background: rgba(255, 255, 255, 0.18);
			border: 1px solid rgba(255, 255, 255, 0.32);
			color: #fff !important;
			font-family: inherit;
		}

		.topbar .topbar-account-logout:hover,
		.topbar .topbar-account-logout:focus {
			background: rgba(255, 255, 255, 0.24);
			color: #fff !important;
		}

		.topbar-account-divider {
			align-items: center;
			background: rgba(255, 255, 255, 0.16);
			border-radius: 999px;
			color: rgba(255, 255, 255, 0.94);
			display: inline-flex;
			font-size: 12px;
			font-weight: 800;
			min-height: 24px;
			padding: 0 8px;
		}

		.header-content {
			background: var(--v26-paper);
			border-radius: 24px;
			margin-top: 14px;
			padding: 18px 16px;
			box-shadow: var(--v26-shadow);
		}

		.logo-wrapper img {
			max-height: 72px;
		}

		nav {
			background: linear-gradient(90deg, #6ca42e 0%, #7fc037 100%) !important;
			border: 0 !important;
			margin-top: 14px !important;
			border-radius: var(--v26-radius);
			box-shadow: 0 12px 24px rgba(63, 99, 34, 0.16) !important;
			overflow: visible !important;
		}

		nav.fixed-nav {
			margin-top: 0 !important;
			border-radius: 0;
		}

		nav .nav-left > .nav-item > .nav-link {
			font-size: 13px;
			letter-spacing: 0.5px;
			text-transform: uppercase;
			font-weight: 700;
		}

		nav .nav-left > .nav-item.active > .nav-link {
			background: linear-gradient(120deg, #f7ae33 0%, #e9901f 100%) !important;
		}

		.header {
			position: relative;
			z-index: 5000;
		}

		.header nav {
			z-index: 5100 !important;
		}

		.header nav .nav-left > .nav-item.has-mega .mega-content {
			z-index: 5200 !important;
		}

		main {
			position: relative;
			z-index: 1;
		}

		.header_search form {
			border: 1px solid #e4edd6;
			box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
			height: 38px;
			width: 286px;
		}

		.product-box {
			border-radius: var(--v26-radius);
			border: 1px solid #e3ebd8;
			box-shadow: 0 10px 22px rgba(61, 94, 36, 0.08);
			transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease;
		}

		.product-box:hover {
			transform: translateY(-4px);
			border-color: #bfd59b;
			box-shadow: 0 18px 32px rgba(61, 94, 36, 0.16);
		}

		.tgc-brand-strip {
			background: linear-gradient(180deg, #ffffff 0%, #f8fbf4 100%);
		}

		.tgc-brand-item {
			background: #fff;
			border: 1px solid #e3ebd7;
			border-radius: 14px;
			padding: 8px;
			box-shadow: 0 8px 16px rgba(64, 94, 40, 0.08);
		}

		.footer {
			background: linear-gradient(170deg, #638f28 0%, #4f771e 100%);
		}

		.footer .site-footer {
			background: transparent;
		}

		.footer .copyright {
			background: rgba(18, 34, 8, 0.32);
		}

		@media (max-width: 991px) {
			.header-content {
				border-radius: 0;
				margin-top: 0;
				box-shadow: none;
			}

			nav {
				margin-top: 0 !important;
				border-radius: 0;
			}
		}
	</style>

	@stack('styles')

	<section class="tgc-brand-strip">
		<div class="container">
			<div class="tgc-brand-grid">
				<div class="tgc-brand-item"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand1.png?v=1064" alt="Envy"></div>
				<div class="tgc-brand-item"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand2.png?v=1064" alt="Koala Cherries"></div>
				<div class="tgc-brand-item"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand3.png?v=1064" alt="Sun World"></div>
				<div class="tgc-brand-item"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand4.png?v=1064" alt="Pretty Lady"></div>
				<div class="tgc-brand-item"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand5.png?v=1064" alt="Zespri"></div>
				<div class="tgc-brand-item"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand6.png?v=1064" alt="Sunkist"></div>
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

	<div class="tgc-side-icons hidden-xs hidden-sm">
		<a class="icon-item phone" href="tel:0333499426" aria-label="phone"><i class="fa fa-phone"></i></a>
		<a class="icon-item zalo" href="https://zalo.me/0333499426" target="_blank" rel="noopener noreferrer" aria-label="zalo">Zalo</a>
		<a class="icon-item youtube" href="https://www.youtube.com/channel/UCbP8WRHFrvv06knZzKyf1Ew" target="_blank" rel="noopener noreferrer" aria-label="youtube"><i class="fa fa-youtube"></i></a>
		<a class="icon-item instagram" href="https://www.instagram.com/thegioitraicay/" target="_blank" rel="noopener noreferrer" aria-label="instagram"><i class="fa fa-instagram"></i></a>
		<a class="icon-item tiktok" href="https://www.tiktok.com/@thegioitraicay.net" target="_blank" rel="noopener noreferrer" aria-label="tiktok"><i class="fa fa-music"></i></a>
		<a class="icon-item location" href="{{ route('contact.page') }}" aria-label="location"><i class="fa fa-map-marker"></i></a>
		<a class="icon-item messenger" href="https://www.facebook.com/messages/t/270232287830" target="_blank" rel="noopener noreferrer" aria-label="messenger"><i class="fa fa-comment"></i></a>
	</div>

	@if(!empty($salesPopupProducts))
	<div class="sales-pop-toast" id="salesPopToast" role="status" aria-live="polite">
		<a class="sales-pop-link" id="salesPopLink" href="#">
			<img class="sales-pop-image" id="salesPopImage" src="" alt="" loading="lazy">
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
	<script src='//theme.hstatic.net/200000157781/1001036201/14/main.js?v=1061'></script>

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

			function closeModal() {
				modal.classList.remove('is-visible');
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
				}
			});
		})();

		// Show floating quick-actions only after scrolling down.
		(function() {
			var sideIcons = document.querySelector('.tgc-side-icons');
			var backToTop = document.getElementById('backToTop');

			if (!sideIcons && !backToTop) {
				return;
			}

			var scrollThreshold = 140;

			function toggleFloatingActions() {
				var currentScroll = window.pageYOffset || document.documentElement.scrollTop || 0;
				var isVisible = currentScroll > scrollThreshold;

				if (sideIcons) {
					sideIcons.classList.toggle('is-visible', isVisible);
				}

				if (backToTop) {
					backToTop.classList.toggle('is-visible', isVisible);
				}
			}

			toggleFloatingActions();
			window.addEventListener('scroll', toggleFloatingActions, { passive: true });
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
	@stack('scripts')
</body>
</html>
