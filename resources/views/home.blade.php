@extends('layouts.app')

@section('content')
<h1 class="hidden">Thế Giới Trái Cây -Trái cây Việt nam loại 1 & nhập khẩu cao cấp</h1>

{{-- Section 1: Slider & Sidebar --}}
<section class="awe-section-1" id="awe-section-1">
	<div class="section_category_slider">
		<div class="container">
			<h2 class="hidden">Slider and Category</h2>
			<div class="row">
				<div class="col-md-9 col-md-push-3 px-md-4 px-0 mt-md-5 mb-5">
					<div class="home-slider owl-carousel" style="background-image: url('{{ asset('images/sliders/banner_custom_1.jpg') }}');" data-lg-items='1' data-md-items='1' data-sm-items='1' data-autoplay='true' data-autoplaytimeout='4000' data-xs-items="1" data-margin='0' data-nav="true">
						<div class="item">
							<a href="{{ route('products.show', 'thanh-tra-thai-lan') }}" class="clearfix">
								<img src="{{ asset('images/sliders/banner_custom_1.jpg') }}" alt="Thanh Trà Thái lan - Thế giới trái cây">
							</a>
						</div>
						<div class="item">
							<a href="{{ route('products.show', 'may-indo') }}" class="clearfix">
								<img src="{{ asset('images/sliders/banner_custom_2.jpg') }}" alt="Mây Indo- Thế giới trái cây">
							</a>
						</div>
						<div class="item">
							<a href="{{ route('products.show', 'nho-xanh-uc-btm-sweetglobe') }}" class="clearfix">
								<img src="{{ asset('images/sliders/banner_custom_3.jpg') }}" alt="Nho xanh Úc - Thế giới trái cây">
							</a>
						</div>
						<div class="item">
							<a href="{{ route('products.show', 'mang-cut-da-cam-thai-lan') }}" class="clearfix">
								<img src="{{ asset('images/sliders/banner_custom_4.jpg') }}" alt="Măng cụt Thái Lan - Thế giới trái cây">
							</a>
						</div>
						<div class="item">
							<a href="{{ route('products.show', 'vu-sua-tim') }}" class="clearfix">
								<img src="{{ asset('images/sliders/banner_custom_5.jpg') }}" alt="Vú sữa Tím Mica - Thế giới trái cây">
							</a>
						</div>
					</div>
				</div>

				<div class="col-md-3 col-md-pull-9 mt-5 hidden-xs aside-vetical-menu">
					<aside class="blog-aside aside-item sidebar-category">
						<div class="aside-title text-center text-xl-left">
							<h2 class="title-head"><span>Danh mục</span></h2>
						</div>
						<div class="aside-content">
							<div class="nav-category navbar-toggleable-md">
								<ul class="nav navbar-pills">
									@foreach($sections as $section)
										<li class="nav-item">
											<img src="{{ $section['icon_url'] }}" alt="{{ $section['category']->name }}" />
											<a class="nav-link" href="{{ route('categories.show', $section['category']->slug) }}">{{ $section['category']->name }}</a>
										</li>
									@endforeach
								</ul>
							</div>
						</div>
					</aside>
				</div>
			</div>
		</div>
	</div>
</section>

{{-- Section 2: Banner --}}
<section class="awe-section-2" id="awe-section-2">
	<div class="section_banner">
		<div class="container">
			<h2 class="hidden">Banner</h2>
			<div class="row home-banner-row">
				<div class="col-xs-12 col-sm-4 home-banner-col">
					<a href="{{ route('products.show', 'mang-cut-lai-thieu') }}" class="home-banner-item clearfix">
						<img src="//theme.hstatic.net/200000157781/1001036201/14/banner1.jpg?v=1061" alt="Măng cụt Lái Thiêu">
					</a>
				</div>
				<div class="col-xs-12 col-sm-4 home-banner-col">
					<a href="{{ route('products.show', 'vai-thieu-luc-ngan') }}" class="home-banner-item clearfix">
						<img src="//theme.hstatic.net/200000157781/1001036201/14/banner2.jpg?v=1061" alt="Vải Thiều hàng máy bay">
					</a>
				</div>
				<div class="col-xs-12 col-sm-4 home-banner-col">
					<a href="{{ route('products.show', 'sau-rieng-ri-6') }}" class="home-banner-item clearfix">
						<img src="//theme.hstatic.net/200000157781/1001036201/14/banner3.jpg?v=1061" alt="Sầu riêng Ri6">
					</a>
				</div>
			</div>
		</div>
	</div>
</section>

{{-- Section 3: Coupon --}}
<section class="awe-section-3" id="awe-section-3">
	<div class="home-coupon coupon-initial section" >
		<div class="container">
			<div class="section-title a-center">
				<h2><span>Khuyến mãi dành cho bạn</span></h2>
			</div>
			<div class="listCoupon">
				@forelse($coupons as $coupon)
				<div class="col-12 col-md-6 col-xl-4 coupon-item">
					<div class="coupon-item__inner">
						<div class="coupon-item__left">
							<div class="cp-img boxlazy-img">
								<span class="boxlazy-img__insert">
								<img src="//theme.hstatic.net/200000157781/1001036201/14/rolling.svg?v=1061" data-lazyload="{{ $coupon->image_url ?? asset('images/coupon-default.png') }}" alt="{{ $coupon->title }}">
								</span>
							</div>
						</div>
						<div class="coupon-item__right">
							<button type="button" class="cp-icon" data-toggle="popover" data-container="body" data-placement="bottom" data-popover-content="#cp-tooltip-{{ $loop->index + 1 }}" data-class="coupon-popover" title="{{ $coupon->title }}">
								<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 20 20">
									<defs>
										<path id="path-{{ $loop->index + 1 }}" d="M8.333 0C3.738 0 0 3.738 0 8.333c0 4.595 3.738 8.334 8.333 8.334 4.595 0 8.334-3.739 8.334-8.334S12.928 0 8.333 0zm0 1.026c4.03 0 7.308 3.278 7.308 7.307 0 4.03-3.278 7.308-7.308 7.308-4.03 0-7.307-3.278-7.307-7.308 0-4.03 3.278-7.307 7.307-7.307zm.096 6.241c-.283 0-.512.23-.512.513v4.359c0 .283.23.513.512.513.284 0 .513-.23.513-.513V7.78c0-.283-.23-.513-.513-.513zm.037-3.114c-.474 0-.858.384-.858.858 0 .473.384.857.858.857s.858-.384.858-.857c0-.474-.384-.858-.858-.858z" />
									</defs>
									<g>
										<use xlink:href="#path-{{ $loop->index + 1 }}" />
									</g>
								</svg>
							</button>
							<div class="cp-top">
								<h3>{{ $coupon->title }}</h3>
								<p>{{ $coupon->description ?? 'Mã giảm giá đặc biệt' }}</p>
							</div>
							<div class="cp-bottom">
								<div class="cp-bottom-detail">
									<p>Mã: <strong>{{ $coupon->code }}</strong></p>
									<p>HSD: {{ $coupon->ends_at ? $coupon->ends_at->format('d/m/Y') : 'Unlimited' }}</p>
								</div>
								<div class="cp-bottom-btn">
									<button class="cp-btn button" data-coupon="{{ $coupon->code }}">Sao chép mã</button>
								</div>
							</div>
						</div>
					</div>
				</div>
				@empty
				<div class="col-12 text-center" style="padding: 30px 0;">
					<p>Không có khuyến mãi nào lúc này</p>
				</div>
				@endforelse
			</div>
		</div>
	</div>
</section>

{{-- Các Section Sản phẩm động --}}
@foreach($sections as $index => $section)
@continue($section['products']->isEmpty())
<section class="awe-section-{{ $index + 4 }}" id="awe-section-{{ $index + 4 }}">
	<div class="section section-deal products-view-grid">
		<div class="container">
			<div class="section-title a-center">
				<h2><a href="{{ route('categories.show', $section['category']->slug) }}">{{ $section['category']->name }}</a></h2>
				<p>{{ $section['category']->description ?? 'Sản phẩm chất lượng loại 1' }}</p>
			</div>
			<div class="section-content">
				<div class="products products-view-grid owl-carousel owl-theme" data-autoplay='true' data-md-items="4" data-sm-items="3" data-xs-items="2" data-margin="30" data-nav="true">
					@foreach($section['products'] as $product)
						<x-products.card :product="$product" />
					@endforeach
				</div>
			</div>
		</div>
	</div>
</section>
@endforeach

{{-- Section Brand: Khách hàng tiêu biểu --}}
<section class="awe-section-10">
	<div class="section_brand section">
		<div class="container">
			<div class="section-title a-center title_line">
				<h2><span>Khách hàng tiêu biểu</span></h2>
			</div>
			<div class="brand-item">
				<div class="row">
					<div class="col-md-4 text-center"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand1.png?v=1061" alt="Vietinbank"></div>
					<div class="col-md-4 text-center"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand2.png?v=1061" alt="PVTrans"></div>
					<div class="col-md-4 text-center"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand3.png?v=1061" alt="Xổ số kiến thiết"></div>
				</div>
			</div>
		</div>
	</div>
</section>

@endsection

@section('styles')
<style>
/* ========================
   DANH MỤC SIDEBAR (CATEGORY MENU)
======================== */

/* BOX background container */
.sidebar-category {
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e5e5e5;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
}

/* Header section */
.sidebar-category .aside-title {
    background: #8bc34a !important;
    padding: 12px;
    text-align: center;
}

.sidebar-category .title-head {
    color: #fff !important;
    font-size: 18px !important;
    font-weight: bold !important;
    margin: 0 !important;
    text-transform: uppercase;
}

/* List container */
.nav-category ul {
    margin: 0;
    padding: 0;
    list-style: none;
}

/* Category Item - Flexbox layout */
.nav-category .nav-item {
    display: flex !important;
    align-items: center !important;
    padding: 8px 15px !important;
    border-bottom: 1px solid #eee;
    transition: 0.3s ease;
    text-decoration: none !important;
    flex-wrap: nowrap !important;
}

/* Item hover state */
.nav-category .nav-item:hover {
    background: #f9f9f9;
}

/* Remove border from last item */
.nav-category .nav-item:last-child {
    border-bottom: none;
}

/* Special case for item 9 */
.nav-category ul > .nav-item:nth-child(9) {
    display: flex !important;
}

/* Icon image styling */
.nav-category .nav-item img {
    width: 30px !important;
    height: 30px !important;
    object-fit: contain;
    margin-right: 10px !important;
    flex-shrink: 0 !important;
}

/* Category name link */
.nav-category .nav-link {
    color: #333 !important;
    text-decoration: none !important;
    font-size: 14px !important;
    font-weight: 500 !important;
    background: none !important;
    padding: 0 !important;
    margin: 0 !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: inline-block !important;
}

/* Link hover state */
.nav-category .nav-item:hover .nav-link {
    color: #8bc34a !important;
}

/* Home top 3 promo banners (match reference block under slider) */
.home-banner-row {
	margin-left: -8px;
	margin-right: -8px;
}

.home-banner-col {
	padding-left: 8px;
	padding-right: 8px;
	margin-bottom: 10px;
}

.home-banner-item {
	display: block;
	border-radius: 14px;
	overflow: hidden;
}

.home-banner-item img {
	display: block;
	width: 100%;
	height: auto;
	border-radius: 14px;
}

/* 2026 Step-1 homepage refresh */
.section_category_slider {
	padding-top: 18px;
}

.home-slider {
	border-radius: 24px;
	overflow: hidden;
	box-shadow: 0 20px 44px rgba(49, 84, 24, 0.18);
	border: 1px solid rgba(127, 180, 66, 0.2);
}

.home-slider .item img {
	display: block;
	width: 100%;
	min-height: 320px;
	object-fit: cover;
}

.section-title h2 {
	font-size: 34px;
	line-height: 1.2;
	margin-bottom: 8px;
}

.section-title p {
	font-size: 15px;
	color: #5d6e59;
	margin-bottom: 20px;
}

.section-deal.products-view-grid {
	padding: 30px 0 18px;
}

.section-deal .section-content {
	background: linear-gradient(160deg, #ffffff 0%, #f7fbf2 100%);
	border: 1px solid #dfebd1;
	border-radius: 20px;
	padding: 16px 14px;
	box-shadow: 0 14px 28px rgba(63, 100, 34, 0.1);
}

.home-banner-item {
	position: relative;
	box-shadow: 0 16px 32px rgba(53, 79, 29, 0.16);
	border: 1px solid rgba(129, 178, 73, 0.25);
	transition: transform .3s ease, box-shadow .3s ease;
}

.home-banner-item:hover {
	transform: translateY(-4px);
	box-shadow: 0 20px 36px rgba(53, 79, 29, 0.24);
}

.home-coupon {
	padding: 32px 0 12px;
}

.coupon-item__inner {
	border: 1px solid #d8e8c4;
	border-style: solid;
	background: linear-gradient(130deg, #fefefe 0%, #f4fbec 100%);
	border-radius: 16px;
	padding: 14px;
	box-shadow: 0 12px 24px rgba(70, 106, 41, 0.09);
	transition: transform .25s ease, box-shadow .25s ease;
}

.coupon-item__inner:hover {
	transform: translateY(-3px);
	box-shadow: 0 18px 30px rgba(70, 106, 41, 0.14);
}

.coupon-item__right .cp-top h3 {
	font-size: 18px;
	font-weight: 700;
	line-height: 1.3;
}

.coupon-item__right .cp-top p {
	font-size: 14px;
	color: #5b6d57;
}

.cp-btn.button {
	border-radius: 10px;
	background: linear-gradient(130deg, #6ba931 0%, #7fbe3b 100%);
	color: #fff;
	font-weight: 700;
	border: 0;
	padding: 10px 14px;
}

.sidebar-category {
	border-radius: 18px;
	box-shadow: 0 18px 32px rgba(53, 79, 29, 0.14);
	border: 1px solid #dce7cf;
}

.sidebar-category .aside-title {
	background: linear-gradient(120deg, #6ca42f 0%, #7fbe3b 100%) !important;
	padding: 14px 12px;
}

.sidebar-category .title-head {
	font-size: 20px !important;
	letter-spacing: 0.4px;
}

.nav-category .nav-item {
	padding: 10px 14px !important;
}

.nav-category .nav-item:hover {
	background: #f4f9ee;
}

.nav-category .nav-item img {
	width: 32px !important;
	height: 32px !important;
}

@keyframes v26Rise {
	from {
		opacity: 0;
		transform: translateY(18px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.awe-section-1,
.awe-section-2,
.awe-section-3 {
	animation: v26Rise .5s ease both;
}

.awe-section-2 {
	animation-delay: .08s;
}

.awe-section-3 {
	animation-delay: .14s;
}

@media (max-width: 767px) {
	.home-banner-col {
		width: 100%;
	}

	.section-title h2 {
		font-size: 28px;
	}

	.home-slider .item img {
		min-height: 220px;
	}

	.sidebar-category .title-head {
		font-size: 18px !important;
	}
}
</style>
@endsection

@push('scripts')
<script>
	$(document).ready(function() {
		// Khởi tạo các carousel sản phẩm
		$('.products.owl-carousel').each(function() {
			var $carousel = $(this);
			$carousel.owlCarousel({
				margin: $carousel.data('margin') || 30,
				nav: true,
				dots: false,
				autoplay: true,
				autoplayTimeout: 5000,
				responsive: {
					0: { items: 2 },
					768: { items: 3 },
					992: { items: 4 }
				},
				navText: ["<i class='fa fa-angle-left'></i>","<i class='fa fa-angle-right'></i>"]
			});
		});

		// Khởi tạo Slider chính
		$('.home-slider').owlCarousel({
			items: 1,
			loop: true,
			nav: true,
			dots: true,
			autoplay: true,
			autoplayTimeout: 4000,
			navText: ["<i class='fa fa-angle-left'></i>","<i class='fa fa-angle-right'></i>"]
		});
	});
</script>
@endpush
