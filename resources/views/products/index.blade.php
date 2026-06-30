@extends('layouts.app')

@php
	$pageTitle = isset($category)
		? (isset($selectedTag) && $selectedTag ? ($category->name . ' / ' . $selectedTag) : $category->name)
		: 'Tất cả sản phẩm';

	$sortOptions = [
		['value' => 'default', 'label' => 'Mặc định'],
		['value' => 'alpha-asc', 'label' => 'A → Z'],
		['value' => 'alpha-desc', 'label' => 'Z → A'],
		['value' => 'price-asc', 'label' => 'Giá tăng dần'],
		['value' => 'price-desc', 'label' => 'Giá giảm dần'],
	];

	$currentSort = (string) request('sort', 'default');
	$currentSortLabel = collect($sortOptions)->firstWhere('value', $currentSort)['label'] ?? 'Mặc định';

	$collectionBaseUrl = route('products.index');
	if (isset($category)) {
		$collectionBaseUrl = !empty($selectedTag)
			? route('categories.show.tag', ['slug' => $category->slug, 'tag' => $selectedTag])
			: route('categories.show', $category->slug);
	} elseif (!empty($searchKeyword)) {
		$collectionBaseUrl = route('search');
	}

	$buildSortUrl = function (string $value) use ($collectionBaseUrl) {
		$query = request()->except(['sort', 'page', 'category', 'tag']);

		if ($value !== 'default') {
			$query['sort'] = $value;
		}

		return empty($query) ? $collectionBaseUrl : $collectionBaseUrl . '?' . http_build_query($query);
	};
@endphp

@section('title', $pageTitle . ' - Thế Giới Trái Cây')

@section('content')
<!-- Breadcrumb -->
<section class="bread_crumb py-4">
	<div class="container">
		<div class="row">
			<div class="col-xs-12">
				<ul class="breadcrumb" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
					<li class="home">
						<a itemprop="url" href="{{ route('home') }}"><span itemprop="title">Trang chủ</span></a>
						<span> <i class="fa fa-angle-right"></i> </span>
					</li>
					@if(isset($category))
						<li>
							<a itemprop="url" href="{{ route('products.index') }}"><span itemprop="title">Sản phẩm</span></a>
							<span> <i class="fa fa-angle-right"></i> </span>
						</li>
						@if(!empty($selectedTag))
							<li>
								<a itemprop="url" href="{{ route('categories.show', $category->slug) }}"><span itemprop="title">{{ $category->name }}</span></a>
								<span> <i class="fa fa-angle-right"></i> </span>
							</li>
						@endif
						<li><strong><span itemprop="title">{{ $pageTitle }}</span></strong></li>
					@else
						<li><strong><span itemprop="title">Tất cả sản phẩm</span></strong></li>
					@endif
				</ul>
			</div>
		</div>
	</div>
</section>

<div class="container collection-page">
	<div class="row">
		<!-- Main Product Area -->
		<section class="main_container collection col-lg-9 col-lg-push-3">
			<div class="box-heading hidden relative">
				<h1 class="title-head margin-top-0">{{ $pageTitle }}</h1>
			</div>
			<div class="category-products products">
				@if(isset($category) && isset($childTags) && $childTags->isNotEmpty())
					<div class="collection-tag-filters">
						<a href="{{ route('categories.show', $category->slug) }}" class="collection-tag-btn {{ empty($selectedTag) ? 'is-active' : '' }}">Tất cả</a>
						@foreach($childTags as $tagItem)
							<a href="{{ route('categories.show.tag', ['slug' => $category->slug, 'tag' => $tagItem->name]) }}" class="collection-tag-btn {{ (!empty($selectedTag) && \Illuminate\Support\Str::slug($selectedTag) === \Illuminate\Support\Str::slug($tagItem->name)) ? 'is-active' : '' }}">{{ $tagItem->name }}</a>
						@endforeach
					</div>
				@endif

				<!-- Sorting Toolbar -->
				<div class="sortPagiBar">
					<div class="row">
						<div class="col-xs-12 col-sm-12 col-md-12 text-xs-left text-sm-right">
							<div class="bg-white clearfix">
								<!-- Sort By Dropdown -->
								<div id="sort-by">
									<label class="left hidden-xs">Sắp xếp: </label>
									<ul>
										<li>
											<span class="val">{{ $currentSortLabel }}</span>
											<ul class="ul_2">
												@foreach($sortOptions as $option)
													<li><a href="{{ $buildSortUrl($option['value']) }}">{{ $option['label'] }}</a></li>
												@endforeach
											</ul>
										</li>
									</ul>
								</div>

								<!-- View Mode Toggle -->
								<div class="view-mode f-left">
									<a href="javascript:;" data-view="grid" aria-label="Xem dạng lưới">
										<b class="btn button-view-mode view-mode-grid active ">
											<i class="fa fa-th" aria-hidden="true"></i>
										</b>
										<span>Lưới</span>
									</a>
									<a href="javascript:;" data-view="list" aria-label="Xem dạng danh sách">
										<b class="btn button-view-mode view-mode-list ">
											<i class="fa fa-th-list" aria-hidden="true"></i>
										</b>
										<span>Danh sách</span>
									</a>
								</div>
							</div>
						</div>
					</div>
				</div>

				<!-- Product Grid -->
				<section class="products-view products-view-grid" id="collectionProducts">
					<div class="row collection-products-row">
						@if($products->count() > 0)
							@foreach($products as $product)
								<div class="col-xs-6 col-xss-6 col-sm-4 col-md-4 col-lg-4 collection-product-col">
									<x-products.card :product="$product" />
								</div>
							@endforeach
						@else
							<div class="col-12 text-center py-5">
								<p class="text-muted lead">Chưa có sản phẩm nào trong danh mục này.</p>
							</div>
						@endif
					</div>

					<!-- Pagination -->
					@if($products->lastPage() > 1)
						@php
							$currentPage = $products->currentPage();
							$lastPage = $products->lastPage();
							$visiblePages = [];

							if ($lastPage <= 7) {
								$visiblePages = range(1, $lastPage);
							} elseif ($currentPage <= 3) {
								$visiblePages = [1, 2, 3, '...', $lastPage];
							} elseif ($currentPage >= $lastPage - 2) {
								$visiblePages = [1, '...', $lastPage - 2, $lastPage - 1, $lastPage];
							} else {
								$visiblePages = [1, '...', $currentPage - 1, $currentPage, $currentPage + 1, '...', $lastPage];
							}
						@endphp

						<div class="text-center mt-5 mb-4">
							<nav class="product-pagination-nav" aria-label="Phan trang san pham">
								<ul class="product-pagination-list">
									@if(!$products->onFirstPage())
										<li>
											<a class="product-page-btn product-page-nav" href="{{ $products->previousPageUrl() }}" aria-label="Trang truoc">
												<i class="fa fa-angle-left" aria-hidden="true"></i>
											</a>
										</li>
									@endif

									@foreach($visiblePages as $page)
										@if($page === '...')
											<li>
												<span class="product-page-ellipsis">...</span>
											</li>
										@elseif($page == $currentPage)
											<li>
												<span class="product-page-btn is-active">{{ $page }}</span>
											</li>
										@else
											<li>
												<a class="product-page-btn" href="{{ $products->url($page) }}">{{ $page }}</a>
											</li>
										@endif
									@endforeach

									@if($products->hasMorePages())
										<li>
											<a class="product-page-btn product-page-nav" href="{{ $products->nextPageUrl() }}" aria-label="Trang sau">
												<i class="fa fa-angle-right" aria-hidden="true"></i>
											</a>
										</li>
									@endif
								</ul>
							</nav>
						</div>
					@endif
				</section>
			</div>
		</section>

<!-- Sidebar Navigation & Filters -->
		<aside class="dqdt-sidebar sidebar left left-content col-lg-3 col-lg-pull-9" id="collectionSidebar">
			<!-- Category Navigation -->
			<aside class="aside-item sidebar-category collection-category">
				<div class="aside-title">
					<h2 class="title-head margin-top-0"><span>Danh mục</span></h2>
				</div>
				<div class="aside-content">
					<div class="nav-category navbar-toggleable-md">
						<ul class="nav navbar-pills">
							<li class="nav-item">
								<i class="fa fa-caret-right"></i>
								<a class="nav-link" href="{{ route('home') }}">Trang chủ</a>
							</li>

							<li class="nav-item okactive">
								<i class="fa fa-caret-right"></i>
								<a href="{{ route('products.index') }}" class="nav-link">Sản phẩm</a>
								<i class="fa fa-angle-down menu-arrow"></i>
							</li>

							<li class="nav-item">
								<i class="fa fa-caret-right"></i>
								<a class="nav-link" href="{{ url('/collections/mam-dia-ngu-qua') }}">Mâm dĩa ngũ quả</a>
							</li>

							<li class="nav-item">
								<i class="fa fa-caret-right"></i>
								<a class="nav-link" href="{{ route('about') }}">Giới thiệu</a>
							</li>

							<li class="nav-item">
								<i class="fa fa-caret-right"></i>
								<a class="nav-link" href="{{ route('contact.page') }}">Liên hệ</a>
							</li>
						</ul>
					</div>
				</div>
			</aside>

			<!-- Price Filter -->
			<div class="aside-filter">
				<div class="filter-container">
					<aside class="aside-item filter-price">
						<div class="aside-title">
							<h2 class="title-head margin-top-0"><span>Giá sản phẩm</span></h2>
						</div>
						<div class="aside-content filter-group">
							<div class="filter-search">
								<input type="text" onkeyup="filterItemInList(this)">
								<i class="fa fa-search"></i>
							</div>
							<ul>
								<li class="filter-item filter-item--check-box filter-item--green">
									<span>
										<label for="filter-duoi-100-000d">
											<input type="checkbox" id="filter-duoi-100-000d"
												   onclick="applyFilter(this, 'price', '0-100000')"
												   {{ request('price') == '0-100000' ? 'checked' : '' }}>
											<i class="fa"></i>
											Giá dưới 100.000₫
										</label>
									</span>
								</li>

								<li class="filter-item filter-item--check-box filter-item--green">
									<span>
										<label for="filter-100-200">
											<input type="checkbox" id="filter-100-200"
												   onclick="applyFilter(this, 'price', '100000-200000')"
												   {{ request('price') == '100000-200000' ? 'checked' : '' }}>
											<i class="fa"></i>
											100.000₫ - 200.000₫
										</label>
									</span>
								</li>

								<li class="filter-item filter-item--check-box filter-item--green">
									<span>
										<label for="filter-200-300">
											<input type="checkbox" id="filter-200-300"
												   onclick="applyFilter(this, 'price', '200000-300000')"
												   {{ request('price') == '200000-300000' ? 'checked' : '' }}>
											<i class="fa"></i>
											200.000₫ - 300.000₫
										</label>
									</span>
								</li>

								<li class="filter-item filter-item--check-box filter-item--green">
									<span>
										<label for="filter-300-500">
											<input type="checkbox" id="filter-300-500"
												   onclick="applyFilter(this, 'price', '300000-500000')"
												   {{ request('price') == '300000-500000' ? 'checked' : '' }}>
											<i class="fa"></i>
											300.000₫ - 500.000₫
										</label>
									</span>
								</li>

								<li class="filter-item filter-item--check-box filter-item--green">
									<span>
										<label for="filter-500-1000">
											<input type="checkbox" id="filter-500-1000"
												   onclick="applyFilter(this, 'price', '500000-1000000')"
												   {{ request('price') == '500000-1000000' ? 'checked' : '' }}>
											<i class="fa"></i>
											500.000₫ - 1.000.000₫
										</label>
									</span>
								</li>
								<li class="filter-item filter-item--check-box filter-item--green">
									<span>
										<label for="filter-tren1000">
											<input type="checkbox" id="filter-tren1000"
												   onclick="applyFilter(this, 'price', 'over-1000000')"
												   {{ request('price') == 'over-1000000' ? 'checked' : '' }}>
											<i class="fa"></i>
											Giá trên 1.000.000₫
										</label>
									</span>
								</li>
							</ul>
						</div>
					</aside>
				</div>
			</div>

			<!-- Type Filter -->
			<div class="aside-filter">
				<div class="filter-container">
					<aside class="aside-item filter-type">
						<div class="aside-title">
							<h2 class="title-head margin-top-0"><span>Loại</span></h2>
						</div>
						<div class="aside-content filter-group filter-type-group">
							<div class="filter-search">
								<input type="text" onkeyup="filterItemInList(this)">
								<i class="fa fa-search"></i>
							</div>

							@php
								$availableRootSlugs = collect($allCategories ?? [])->filter(function ($item) {
									return (int) ($item->parent_id ?? 0) === 0 && (!isset($item->is_active) || (bool) $item->is_active);
								})->pluck('slug')->values()->all();

								$sidebarTypeItems = collect([
									['label' => 'Trái cây nhập khẩu', 'slug' => 'trai-cay-nhap-khau'],
									['label' => 'Trái Cây Việt Nam', 'slug' => 'trai-cay-viet-nam'],
									['label' => 'Giỏ quà và Set quà', 'slug' => 'gio-qua-va-set-qua'],
									['label' => 'Thực phẩm', 'slug' => 'thuc-pham'],
									['label' => 'Trái cây Thái lan', 'slug' => 'trai-cay-thai-lan'],
									['label' => 'Quả cưới và Mâm cúng', 'slug' => 'qua-cuoi-va-mam-cung'],
									['label' => 'Kem và Bánh nhập khẩu', 'slug' => null],
									['label' => 'Khác', 'slug' => null],
									['label' => 'Combo siêu tiết kiệm', 'slug' => 'combo-sieu-tiet-kiem'],
								])->map(function ($item) use ($availableRootSlugs) {
									$slug = $item['slug'];
									$item['target_url'] = ($slug && in_array($slug, $availableRootSlugs, true))
										? route('categories.show', $slug)
										: route('products.index');

									return $item;
								});
							@endphp

							<ul class="filter-type-list">
								@foreach($sidebarTypeItems as $typeItem)
									@php
										$typeInputId = 'filter-type-' . ($typeItem['slug'] ?: \Illuminate\Support\Str::slug($typeItem['label']));
										$isCurrentType = isset($category) && !empty($typeItem['slug']) && $category->slug === $typeItem['slug'];
									@endphp
									<li class="filter-item filter-item--check-box filter-item--green">
										<span>
											<label for="{{ $typeInputId }}">
												<input type="checkbox"
												       id="{{ $typeInputId }}"
												       data-target-url="{{ $typeItem['target_url'] }}"
												       data-fallback-url="{{ route('products.index') }}"
												       onchange="applyTypeNavigation(this)"
												       {{ $isCurrentType ? 'checked' : '' }}>
												<i class="fa"></i>
												{{ $typeItem['label'] }}
											</label>
										</span>
									</li>
								@endforeach
							</ul>
						</div>
					</aside>
				</div>
			</div>

			<!-- Featured Products -->
			<div class="aside-item aside-mini-list-product mb-5">
				<div>
					<div class="aside-title">
						<h2 class="title-head">
							<a href="{{ route('products.index') }}">Sản phẩm nổi bật</a>
						</h2>
					</div>
					<div class="aside-content related-product">
						<div class="product-mini-lists">
							<div class="products">
								@foreach($featuredProducts as $fp)
									@php
										$featuredThumbUrl = $fp->thumb_url;
										$featuredBase = (int) ($fp->price ?? 0);
										$featuredSalePrice = (int) ($fp->sale_price ?? 0);
										$featuredSale = $featuredBase > 0 && $featuredSalePrice > 0 && $featuredSalePrice < $featuredBase;
										$featuredCurrent = (int) $fp->orderable_price;
										$featuredMissingPrice = $featuredCurrent <= 0;
										$featuredCustomOrder = (bool) $fp->is_custom_order_product;
									@endphp
									<div class="row row-noGutter">
										<div class="col-sm-12">
											<div class="product-mini-item clearfix {{ $featuredSale ? 'on-sale' : '' }}">
												<div class="product-img relative">
													<a href="{{ route('products.show', $fp->slug) }}">
														<img src="{{ $featuredThumbUrl }}" alt="{{ $fp->name }}" style="width: 100%; height: 100%; object-fit: cover;">
													</a>
												</div>
												<div class="product-info">
													<h3>
														<a href="{{ route('products.show', $fp->slug) }}" title="{{ $fp->name }}" class="product-name">
															{{ \Illuminate\Support\Str::limit($fp->name, 40) }}
														</a>
													</h3>
													<div class="price-box">
														@if($featuredMissingPrice)
															<span class="special-price"><span class="price product-price">Đang cập nhật giá</span></span>
														@elseif($featuredSale)
															<span class="price"><span class="price product-price">{{ $featuredCustomOrder ? 'Từ ' : '' }}{{ number_format($featuredSalePrice) }}₫</span></span>
															<span class="old-price"><del class="sale-price">{{ number_format($featuredBase) }}₫</del></span>
														@else
															<span class="price"><span class="price product-price">{{ $featuredCustomOrder ? 'Từ ' : '' }}{{ number_format($featuredCurrent) }}₫</span></span>
														@endif
													</div>
												</div>
											</div>
										</div>
									</div>
								@endforeach
							</div>
						</div>
					</div>
				</div>
			</div>
		</aside>

		<button type="button" id="open-filters" class="open-filters hidden-lg" aria-controls="collectionSidebar" aria-expanded="false">
			<i class="fa fa-filter" aria-hidden="true"></i>
			<span>Lọc</span>
		</button>
	</div>
</div>

<script>
function applyFilter(checkbox, filterType, filterValue) {
	const url = new URL(window.location);

	if (checkbox.checked) {
		url.searchParams.set(filterType, filterValue);
	} else {
		url.searchParams.delete(filterType);
	}

	url.searchParams.delete('page');
	window.location.href = url.toString();
}

function applyTypeNavigation(checkbox, targetUrl, fallbackUrl) {
	var nextUrl = targetUrl || checkbox.getAttribute('data-target-url');
	var resetUrl = fallbackUrl || checkbox.getAttribute('data-fallback-url');

	if (!nextUrl || !resetUrl) {
		return;
	}

	window.location.href = checkbox.checked ? nextUrl : resetUrl;
}

function filterItemInList(inputElement) {
	var query = (inputElement.value || '').toLowerCase().trim();
	var filterGroup = inputElement.closest('.filter-group');

	if (!filterGroup) {
		return;
	}

	var items = filterGroup.querySelectorAll('ul .filter-item');
	items.forEach(function (item) {
		var text = (item.textContent || '').toLowerCase();
		item.style.display = text.indexOf(query) >= 0 ? '' : 'none';
	});
}

(function () {
	var openFiltersBtn = document.getElementById('open-filters');
	var sidebar = document.getElementById('collectionSidebar');

	if (openFiltersBtn && sidebar) {
		openFiltersBtn.addEventListener('click', function () {
			var isOpen = sidebar.classList.toggle('is-open-mobile');
			openFiltersBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		});

		document.addEventListener('click', function (event) {
			if (window.innerWidth > 991 || !sidebar.classList.contains('is-open-mobile')) {
				return;
			}

			if (sidebar.contains(event.target) || openFiltersBtn.contains(event.target)) {
				return;
			}

			sidebar.classList.remove('is-open-mobile');
			openFiltersBtn.setAttribute('aria-expanded', 'false');
		});
	}

	var productsView = document.getElementById('collectionProducts');
	var viewButtons = document.querySelectorAll('.view-mode [data-view]');

	if (!productsView || !viewButtons.length) {
		return;
	}

	viewButtons.forEach(function (link) {
		link.addEventListener('click', function (event) {
			event.preventDefault();

			var viewMode = link.getAttribute('data-view') === 'list' ? 'list' : 'grid';
			productsView.classList.toggle('products-view-grid', viewMode === 'grid');
			productsView.classList.toggle('products-view-list', viewMode === 'list');

			viewButtons.forEach(function (button) {
				var isActive = button.getAttribute('data-view') === viewMode;
				var iconButton = button.querySelector('.button-view-mode');
				if (iconButton) {
					iconButton.classList.toggle('active', isActive);
				}
			});
		});
	});
})();
</script>

@endsection

@push('styles')
<style>
	.collection-category .nav-category .nav-item {
		display: flex !important;
		align-items: center !important;
		float: none !important;
		width: 100%;
		position: relative;
		padding: 12px 0 !important;
		border-bottom: 1px solid #efefef;
	}

	.collection-category .nav-category .nav-item:last-child {
		border-bottom: 0;
	}

	.collection-category .nav-category .nav-item > .fa {
		position: static !important;
		top: auto !important;
		left: auto !important;
		transform: none !important;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		width: 18px;
		height: 18px;
		line-height: 1;
		margin: 0 10px 0 0 !important;
		color: #8b8b8b;
		min-width: 18px;
	}

	.collection-category .nav-category .nav-link {
		color: #333 !important;
		background: transparent !important;
		display: block !important;
		flex: 1;
		padding: 0 !important;
		margin: 0 !important;
		font-size: 14px !important;
		font-weight: 500 !important;
		line-height: 1.4 !important;
		white-space: normal;
	}

	.collection-category .nav-category .nav-item:hover > .nav-link {
		color: #7fbe3b !important;
	}

	.collection-category .nav-category .dropdown-menu {
		display: none;
		position: static;
		float: none;
		border: 0;
		box-shadow: none;
		padding: 8px 0 0 18px;
		margin: 0;
		width: 100%;
	}

	.collection-category .nav-category .menu-arrow {
		position: static !important;
		top: auto !important;
		right: auto !important;
		transform: none !important;
		margin-left: auto;
		color: #666;
		font-size: 12px;
	}

	.product-pagination-nav {
		display: flex;
		justify-content: center;
	}

	.collection-tag-filters {
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
		margin-bottom: 14px;
	}

	.collection-tag-btn {
		display: inline-flex;
		align-items: center;
		justify-content: center;
		height: 32px;
		padding: 0 12px;
		border-radius: 999px;
		border: 1px solid #dcdcdc;
		background: #fff;
		color: #444;
		text-decoration: none !important;
		font-size: 13px;
		transition: all .2s ease;
	}

	.collection-tag-btn:hover {
		border-color: #7fbe3b;
		color: #7fbe3b;
	}

	.collection-tag-btn.is-active {
		background: #7fbe3b;
		border-color: #7fbe3b;
		color: #fff;
	}

	.aside-filter .filter-search {
		position: relative;
		margin-bottom: 10px;
	}

	.aside-filter .filter-search input {
		width: 100%;
		height: 34px;
		border: 1px solid #ececec;
		border-radius: 2px;
		padding: 0 34px 0 10px;
		font-size: 13px;
		outline: none;
	}

	.aside-filter .filter-search .fa {
		position: absolute;
		right: 10px;
		top: 50%;
		transform: translateY(-50%);
		color: #333;
		font-size: 16px;
	}

	.aside-filter .filter-group {
		padding: 10px;
	}

	.aside-filter .filter-group > ul {
		margin: 0;
		padding: 0;
		list-style: none;
	}

	.aside-filter .filter-group .filter-item {
		margin-bottom: 6px;
	}

	.aside-filter .filter-group .filter-item:last-child {
		margin-bottom: 0;
	}

	.aside-filter .filter-group label {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		font-size: 14px;
		font-weight: 500;
		color: #444;
		line-height: 1.6;
		cursor: pointer;
	}

	.aside-filter .filter-group label input[type="checkbox"] {
		margin: 0;
	}

	.filter-type .filter-type-group {
		padding: 10px;
	}

	.filter-type .filter-type-list {
		max-height: 230px;
		overflow-y: auto;
		padding-right: 4px;
	}

	.filter-type .filter-type-list .filter-item {
		margin-bottom: 6px;
	}

	.product-pagination-list {
		list-style: none;
		padding: 0;
		margin: 0;
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.product-page-btn,
	.product-page-ellipsis {
		min-width: 42px;
		height: 42px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		padding: 0 12px;
		border: 1px solid #d9d9d9;
		border-radius: 12px;
		background: #fff;
		color: #333;
		font-size: 22px;
		line-height: 1;
		text-decoration: none !important;
		transition: all 0.2s ease;
	}

	.product-page-btn {
		font-size: 22px;
		font-weight: 400;
	}

	.product-page-btn:hover {
		border-color: #7fbe3b;
		color: #7fbe3b;
	}

	.product-page-btn.is-active {
		background: #7fbe3b;
		border-color: #7fbe3b;
		color: #fff;
	}

	.product-page-ellipsis {
		font-size: 24px;
		color: #444;
	}

	.product-page-nav {
		font-size: 24px;
	}

	.products-view-list .collection-product-col {
		width: 100% !important;
		float: none !important;
	}

	.products-view-list .product-box {
		display: flex;
		align-items: stretch;
		gap: 16px;
		padding: 12px;
	}

	.products-view-list .product-thumbnail {
		flex: 0 0 180px;
		max-width: 180px;
	}

	.products-view-list .product-info {
		flex: 1;
		text-align: left !important;
		min-width: 0;
		padding-top: 6px;
	}

	.products-view-list .product-name a {
		height: auto;
		max-height: none;
		margin-top: 0;
		font-size: 16px;
		line-height: 1.45;
	}

	.products-view-list .product-action {
		position: static;
		display: block;
		text-align: left;
		margin-top: 10px;
	}

	.open-filters {
		display: none;
	}

	@media (max-width: 991px) {
		.collection-page .main_container.collection {
			width: 100%;
			left: 0;
		}

		.collection-page .sidebar.left-content {
			position: fixed;
			left: -320px;
			top: 0;
			bottom: 0;
			width: 300px;
			z-index: 1050;
			background: #fff;
			overflow-y: auto;
			transition: left 0.25s ease;
			padding: 20px 15px;
		}

		.collection-page .sidebar.left-content.is-open-mobile {
			left: 0;
			box-shadow: 0 0 18px rgba(0, 0, 0, 0.2);
		}

		.collection-page .open-filters {
			display: inline-flex;
			align-items: center;
			justify-content: center;
			gap: 6px;
			position: fixed;
			right: 14px;
			bottom: 80px;
			z-index: 1055;
			border: 0;
			border-radius: 999px;
			background: #8bc34a;
			color: #fff;
			padding: 10px 14px;
			font-size: 13px;
			font-weight: 700;
			box-shadow: 0 8px 18px rgba(0, 0, 0, 0.18);
		}

		.products-view-list .product-box {
			display: block;
		}

		.products-view-list .product-thumbnail {
			max-width: none;
		}

		.products-view-list .product-info {
			padding-top: 0;
		}
	}

	@media (max-width: 576px) {
		.product-pagination-list {
			gap: 6px;
		}

		.product-page-btn,
		.product-page-ellipsis {
			min-width: 38px;
			height: 38px;
			padding: 0 10px;
			border-radius: 10px;
			font-size: 20px;
		}
	}
</style>
@endpush
