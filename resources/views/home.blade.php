@extends('layouts.app')

@section('content')
<h1 class="hidden">Thế Giới Trái Cây -Trái cây Việt nam loại 1 & nhập khẩu cao cấp</h1>

@php
	$sliderItems = [
		['slug' => 'thanh-tra-thai-lan', 'file' => 'banner_custom_1', 'alt' => 'Thanh Trà Thái Lan - Thế Giới Trái Cây'],
		['slug' => 'may-indo', 'file' => 'banner_custom_2', 'alt' => 'Mây Indo - Thế Giới Trái Cây'],
		['slug' => 'nho-xanh-uc-btm-sweetglobe', 'file' => 'banner_custom_3', 'alt' => 'Nho xanh Úc - Thế Giới Trái Cây'],
		['slug' => 'mang-cut-da-cam-thai-lan', 'file' => 'banner_custom_4', 'alt' => 'Măng cụt Thái Lan - Thế Giới Trái Cây'],
		['slug' => 'vu-sua-tim', 'file' => 'banner_custom_5', 'alt' => 'Vú sữa tím Mica - Thế Giới Trái Cây'],
	];
@endphp

{{-- Section 1: Slider & Sidebar --}}
<section class="awe-section-1" id="awe-section-1">
	<div class="section_category_slider">
		<div class="container">
			<h2 class="hidden">Slider and Category</h2>
			<div class="row">
				<div class="col-md-9 col-md-push-3 px-md-4 px-0 mt-md-5 mb-5">
					<div class="home-slider owl-carousel" data-lg-items='1' data-md-items='1' data-sm-items='1' data-autoplay='true' data-autoplaytimeout='4000' data-xs-items="1" data-margin='0' data-nav="true">
						@foreach($sliderItems as $slider)
							<div class="item">
								<a href="{{ route('products.show', $slider['slug']) }}" class="clearfix">
									<picture>
										<source srcset="{{ asset('images/sliders/' . $slider['file'] . '.webp') }}" type="image/webp">
										<img
											src="{{ asset('images/sliders/' . $slider['file'] . '.jpg') }}"
											alt="{{ $slider['alt'] }}"
											width="1376"
											height="768"
											loading="{{ $loop->first ? 'eager' : 'lazy' }}"
											decoding="async"
											@if($loop->first) fetchpriority="high" @endif
										>
									</picture>
								</a>
							</div>
						@endforeach
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
											<img src="{{ $section['icon_url'] }}" alt="" width="32" height="32" loading="lazy" decoding="async" />
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
						<img src="//theme.hstatic.net/200000157781/1001036201/14/banner1.jpg?v=1061" alt="Măng cụt Lái Thiêu" width="720" height="335" loading="lazy" decoding="async">
					</a>
				</div>
				<div class="col-xs-12 col-sm-4 home-banner-col">
					<a href="{{ route('products.show', 'vai-thieu-luc-ngan') }}" class="home-banner-item clearfix">
						<img src="//theme.hstatic.net/200000157781/1001036201/14/banner2.jpg?v=1061" alt="Vải Thiều hàng máy bay" width="720" height="335" loading="lazy" decoding="async">
					</a>
				</div>
				<div class="col-xs-12 col-sm-4 home-banner-col">
					<a href="{{ route('products.show', 'sau-rieng-ri-6') }}" class="home-banner-item clearfix">
						<img src="//theme.hstatic.net/200000157781/1001036201/14/banner3.jpg?v=1061" alt="Sầu riêng Ri6" width="720" height="335" loading="lazy" decoding="async">
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
				@php
					$couponUsed = auth()->check()
						&& $coupon->hasBeenUsedBy(auth()->id(), auth()->user()->email);
					$couponInfoId = 'coupon-info-' . $coupon->id;
				@endphp
				<div class="col-12 col-md-6 col-xl-4 coupon-item {{ $couponUsed ? 'is-used' : '' }}">
					<div class="coupon-item__inner">
						<div class="coupon-item__left">
							<div class="cp-img boxlazy-img">
								<span class="boxlazy-img__insert">
								<img src="{{ $coupon->image_url ?? asset('images/coupon-default.png') }}" alt="{{ $coupon->title }}" width="104" height="104" loading="lazy" decoding="async">
								</span>
							</div>
						</div>
						<div class="coupon-item__right">
							<details class="coupon-info">
								<summary class="cp-icon" aria-label="Xem điều kiện mã {{ $coupon->code }}" aria-controls="{{ $couponInfoId }}">
									<i class="fa fa-info-circle" aria-hidden="true"></i>
								</summary>
								<div class="coupon-info-panel" id="{{ $couponInfoId }}">
									<strong>Quyền lợi</strong>
									<span>{{ $coupon->benefit_label }}</span>
									<strong>Điều kiện</strong>
									<span>{{ $coupon->condition_label }}</span>
									<strong>Giới hạn</strong>
									<span>{{ $coupon->customer_limit_label }}</span>
								</div>
							</details>
							<div class="cp-top">
								<h3>{{ $coupon->title }}</h3>
								<p>{{ $coupon->description ?? $coupon->benefit_label }}</p>
							</div>
							<div class="cp-benefit"><i class="fa fa-check-circle"></i> {{ $coupon->condition_label }}</div>
							<div class="cp-bottom">
								<div class="cp-bottom-detail">
									<p>Mã: <strong>{{ $coupon->code }}</strong></p>
									<p>HSD: {{ \App\Support\LocalDateTime::format($coupon->ends_at, 'd/m/Y', 'Không giới hạn') }}</p>
								</div>
								<div class="cp-bottom-btn">
									@if($couponUsed)
										<button type="button" class="cp-btn button is-used" disabled>Đã sử dụng</button>
									@else
										<button
											type="button"
											class="cp-btn button"
											data-coupon-copy="{{ $coupon->code }}"
											data-coupon-benefit="{{ $coupon->benefit_label }}"
										>Sao chép mã</button>
									@endif
								</div>
							</div>
							<div class="coupon-account-status {{ $couponUsed ? 'is-used' : 'is-ready' }}">
								@if($couponUsed)
									Mã này đã được tài khoản của bạn sử dụng
								@elseif(auth()->check())
									Dùng được 1 lần cho tài khoản của bạn
								@else
									Đăng nhập để sử dụng mã thành viên
								@endif
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
	<div class="coupon-copy-toast" id="couponCopyToast" role="status" aria-live="polite"></div>
</section>

{{-- Các Section Sản phẩm động --}}
@foreach($sections as $index => $section)
@continue($section['products']->isEmpty())
<section class="awe-section-{{ $index + 4 }}" id="awe-section-{{ $index + 4 }}">
	<div class="section section-deal products-view-grid">
		<div class="container">
			<div class="section-title a-center">
				<h2><a href="{{ route('categories.show', $section['category']->slug) }}">{{ $section['category']->name }}</a></h2>
				<p>{{ $section['slogan'] }}</p>
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
					<div class="col-md-4 text-center"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand1.png?v=1061" alt="Vietinbank" width="240" height="100" loading="lazy" decoding="async"></div>
					<div class="col-md-4 text-center"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand2.png?v=1061" alt="PVTrans" width="240" height="100" loading="lazy" decoding="async"></div>
					<div class="col-md-4 text-center"><img src="https://theme.hstatic.net/200000157781/1001036201/14/brand3.png?v=1061" alt="Xổ số kiến thiết" width="240" height="100" loading="lazy" decoding="async"></div>
				</div>
			</div>
		</div>
	</div>
</section>

@endsection

@push('head_meta')
	@vite('resources/css/home.css')
@endpush

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

		function fallbackCopy(text) {
			var input = document.createElement('textarea');
			input.value = text;
			input.setAttribute('readonly', '');
			input.style.position = 'fixed';
			input.style.opacity = '0';
			document.body.appendChild(input);
			input.select();
			var copied = document.execCommand('copy');
			document.body.removeChild(input);
			return copied;
		}

		function showCouponToast(message) {
			var toast = document.getElementById('couponCopyToast');
			if (!toast) return;

			toast.textContent = message;
			toast.classList.add('is-visible');
			window.clearTimeout(toast.hideTimer);
			toast.hideTimer = window.setTimeout(function() {
				toast.classList.remove('is-visible');
			}, 3200);
		}

		$(document).on('click', '[data-coupon-copy]', async function() {
			var button = this;
			var code = button.getAttribute('data-coupon-copy');
			var benefit = button.getAttribute('data-coupon-benefit') || '';
			var originalLabel = button.textContent;
			var copied = false;

			try {
				if (navigator.clipboard && window.isSecureContext) {
					await navigator.clipboard.writeText(code);
					copied = true;
				} else {
					copied = fallbackCopy(code);
				}
			} catch (error) {
				copied = fallbackCopy(code);
			}

			if (!copied) {
				showCouponToast('Chưa sao chép được mã. Vui lòng thử lại.');
				return;
			}

			button.textContent = 'Đã sao chép';
			showCouponToast('Đã sao chép ' + code + (benefit ? ' - ' + benefit : '') + '. Dán mã tại giỏ hàng hoặc checkout.');
			window.setTimeout(function() {
				button.textContent = originalLabel;
			}, 1800);
		});

		$('.coupon-info').on('toggle', function() {
			if (!this.open) return;

			$('.coupon-info').not(this).removeAttr('open');
		});

		$(document).on('click', function(event) {
			if (!$(event.target).closest('.coupon-info').length) {
				$('.coupon-info').removeAttr('open');
			}
		});
	});
</script>
@endpush
