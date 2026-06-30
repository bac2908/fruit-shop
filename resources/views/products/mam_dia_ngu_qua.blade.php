@extends('layouts.app')

@php
    $pageTitle = 'Mâm dĩa ngũ quả';

    $sortOptions = [
        ['value' => 'default', 'label' => 'Mặc định'],
        ['value' => 'alpha-asc', 'label' => 'A → Z'],
        ['value' => 'alpha-desc', 'label' => 'Z → A'],
        ['value' => 'price-asc', 'label' => 'Giá tăng dần'],
        ['value' => 'price-desc', 'label' => 'Giá giảm dần'],
    ];

    $currentSort = (string) request('sort', 'default');
    $currentSortLabel = collect($sortOptions)->firstWhere('value', $currentSort)['label'] ?? 'Mặc định';
    $categorySlug = (string) ($category->slug ?? 'mam-dia-ngu-qua');

    $buildSortUrl = function (string $value) use ($categorySlug) {
        $query = request()->except(['sort', 'page']);

        if ($value !== 'default') {
            $query['sort'] = $value;
        }

        $baseUrl = route('categories.show', $categorySlug);

        return empty($query) ? $baseUrl : $baseUrl . '?' . http_build_query($query);
    };

@endphp

@section('title', $pageTitle . ' - Thế Giới Trái Cây')

@section('content')
<section class="bread_crumb py-4">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <ul class="breadcrumb" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
                    <li class="home">
                        <a itemprop="url" href="{{ route('home') }}"><span itemprop="title">Trang chủ</span></a>
                        <span> <i class="fa fa-angle-right"></i> </span>
                    </li>
                    <li><strong><span itemprop="title">{{ $pageTitle }}</span></strong></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<div class="container collection-mamdia">
    <div class="row">
        <section class="main_container collection col-lg-9 col-lg-push-3">
            <div class="box-heading hidden relative">
                <h1 class="title-head margin-top-0">{{ $pageTitle }}</h1>
            </div>

            <div class="category-products products">
                <div class="sortPagiBar">
                    <div class="row">
                        <div class="col-xs-12 col-sm-12 col-md-12 text-xs-left text-sm-right">
                            <div class="bg-white clearfix">
                                <div id="sort-by">
                                    <label class="left hidden-xs">Sắp xếp:</label>
                                    <ul>
                                        <li>
                                            <span class="val">{{ $currentSortLabel }}</span>
                                            <ul class="ul_2">
                                                @foreach($sortOptions as $option)
                                                    <li>
                                                        <a href="{{ $buildSortUrl($option['value']) }}">{{ $option['label'] }}</a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </li>
                                    </ul>
                                </div>

                                <div class="view-mode f-left">
                                    <a href="javascript:;" data-view="grid">
                                        <b class="btn button-view-mode view-mode-grid active ">
                                            <i class="fa fa-th" aria-hidden="true"></i>
                                        </b>
                                        <span>Lưới</span>
                                    </a>
                                    <a href="javascript:;" data-view="list">
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

                <section class="products-view products-view-grid">
                    <div class="row">
                        @forelse($products as $product)
                            <div class="col-xs-6 col-xss-6 col-sm-4 col-md-4 col-lg-4">
                                <x-products.card :product="$product" />
                            </div>
                        @empty
                            <div class="col-xs-12">
                                <div class="text-center py-4">
                                    <p>Hiện chưa có sản phẩm trong danh mục này.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>

                    @if($products->lastPage() > 1)
                        <div class="text-center mt-4 mb-3">
                            {{ $products->links() }}
                        </div>
                    @endif
                </section>
            </div>
        </section>

        <aside class="dqdt-sidebar sidebar left left-content col-lg-3 col-lg-pull-9" id="collectionSidebar">
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

                            <li class="nav-item okactive">
                                <i class="fa fa-caret-right"></i>
                                <a class="nav-link" href="{{ route('categories.show', 'mam-dia-ngu-qua') }}">Mâm dĩa ngũ quả</a>
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
                                        : route('categories.show', 'mam-dia-ngu-qua');

                                    return $item;
                                });
                            @endphp

                            <ul class="filter-type-list">
                                @foreach($sidebarTypeItems as $typeItem)
                                    @php
                                        $typeInputId = 'mam-filter-type-' . ($typeItem['slug'] ?: \Illuminate\Support\Str::slug($typeItem['label']));
                                        $isCurrentType = isset($category) && !empty($typeItem['slug']) && $category->slug === $typeItem['slug'];
                                    @endphp
                                    <li class="filter-item filter-item--check-box filter-item--green">
                                        <span>
                                            <label for="{{ $typeInputId }}">
                                                <input type="checkbox"
                                                       id="{{ $typeInputId }}"
                                                      data-target-url="{{ $typeItem['target_url'] }}"
                                                      data-fallback-url="{{ route('categories.show', 'mam-dia-ngu-qua') }}"
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

            <div class="aside-item aside-mini-list-product mb-5">
                <div>
                    <div class="aside-title">
                        <h2 class="title-head">
                            <a href="{{ route('categories.show', 'mam-dia-ngu-qua') }}">Sản phẩm nổi bật</a>
                        </h2>
                    </div>
                    <div class="aside-content related-product">
                        <div class="product-mini-lists">
                            <div class="products">
                                @foreach(collect($featuredProducts ?? [])->take(6) as $fp)
                                    @php
                                        $miniThumb = $fp->primary_image_url;
                                        $miniBase = (int) ($fp->price ?? 0);
                                        $miniSalePrice = (int) ($fp->sale_price ?? 0);
                                        $miniSale = $miniBase > 0 && $miniSalePrice > 0 && $miniSalePrice < $miniBase;
                                        $miniCurrent = (int) $fp->orderable_price;
                                        $miniMissingPrice = $miniCurrent <= 0;
                                        $miniCustomOrder = (bool) $fp->is_custom_order_product;
                                    @endphp
                                    <div class="row row-noGutter">
                                        <div class="col-sm-12">
                                            <div class="product-mini-item clearfix {{ $miniSale ? 'on-sale' : '' }}">
                                                <div class="product-img relative">
                                                    <a href="{{ route('products.show', $fp->slug) }}">
                                                        <img src="{{ $miniThumb }}" alt="{{ $fp->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                                    </a>
                                                </div>

                                                <div class="product-info">
                                                    <h3>
                                                        <a href="{{ route('products.show', $fp->slug) }}" title="{{ $fp->name }}" class="product-name">
                                                            {{ \Illuminate\Support\Str::limit($fp->name, 40) }}
                                                        </a>
                                                    </h3>

                                                    <div class="price-box">
                                                        @if($miniMissingPrice)
                                                            <span class="special-price"><span class="price product-price">Đang cập nhật giá</span></span>
                                                        @elseif($miniSale)
                                                            <span class="price"><span class="price product-price">{{ $miniCustomOrder ? 'Từ ' : '' }}{{ number_format($miniSalePrice) }}₫</span></span>
                                                            <span class="old-price"><del class="sale-price">{{ number_format($miniBase) }}₫</del></span>
                                                        @else
                                                            <span class="price"><span class="price product-price">{{ $miniCustomOrder ? 'Từ ' : '' }}{{ number_format($miniCurrent) }}₫</span></span>
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

        <div id="open-filters" class="open-filters hidden-lg">
            <i class="fa fa-align-right"></i>
            <span>Lọc</span>
        </div>
    </div>
</div>

<script>
    function applyFilter(checkbox, filterType, filterValue) {
        var url = new URL(window.location.href);

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

        if (!openFiltersBtn || !sidebar) {
            return;
        }

        openFiltersBtn.addEventListener('click', function () {
            sidebar.classList.toggle('is-open-mobile');
        });

        document.addEventListener('click', function (event) {
            if (window.innerWidth > 991) {
                return;
            }

            if (!sidebar.classList.contains('is-open-mobile')) {
                return;
            }

            if (sidebar.contains(event.target) || openFiltersBtn.contains(event.target)) {
                return;
            }

            sidebar.classList.remove('is-open-mobile');
        });
    })();
</script>
@endsection

@push('styles')
<style>
    .collection-mamdia .collection-category .nav-category .nav-item {
        display: flex !important;
        align-items: center !important;
        float: none !important;
        width: 100%;
        position: relative;
        padding: 12px 0 !important;
        border-bottom: 1px solid #efefef;
    }

    .collection-mamdia .collection-category .nav-category .nav-item:last-child {
        border-bottom: 0;
    }

    .collection-mamdia .collection-category .nav-category .nav-item > .fa {
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

    .collection-mamdia .collection-category .nav-category .nav-link {
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

    .collection-mamdia .collection-category .nav-category .nav-item:hover > .nav-link {
        color: #7fbe3b !important;
    }

    .collection-mamdia .collection-category .nav-category .dropdown-menu {
        display: none;
        position: static;
        float: none;
        border: 0;
        box-shadow: none;
        padding: 8px 0 0 18px;
        margin: 0;
        width: 100%;
    }

    .collection-mamdia .collection-category .nav-category .menu-arrow {
        position: static !important;
        top: auto !important;
        right: auto !important;
        transform: none !important;
        margin-left: auto;
        color: #666;
        font-size: 12px;
    }

    .collection-mamdia .aside-filter .filter-search {
        position: relative;
        margin-bottom: 10px;
    }

    .collection-mamdia .aside-filter .filter-search input {
        width: 100%;
        height: 34px;
        border: 1px solid #ececec;
        border-radius: 2px;
        padding: 0 34px 0 10px;
        font-size: 13px;
        outline: none;
    }

    .collection-mamdia .aside-filter .filter-search .fa {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #333;
        font-size: 16px;
    }

    .collection-mamdia .aside-filter .filter-group {
        padding: 10px;
    }

    .collection-mamdia .aside-filter .filter-group > ul {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .collection-mamdia .aside-filter .filter-group .filter-item {
        margin-bottom: 6px;
    }

    .collection-mamdia .aside-filter .filter-group .filter-item:last-child {
        margin-bottom: 0;
    }

    .collection-mamdia .aside-filter .filter-group label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 500;
        color: #444;
        line-height: 1.6;
        cursor: pointer;
    }

    .collection-mamdia .aside-filter .filter-group label input[type="checkbox"] {
        margin: 0;
    }

    .collection-mamdia .filter-type .filter-type-group {
        padding: 10px;
    }

    .collection-mamdia .filter-type .filter-type-list {
        max-height: 230px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .collection-mamdia .filter-type .filter-type-list .filter-item {
        margin-bottom: 6px;
    }

    .collection-mamdia .open-filters {
        display: none;
    }

    @media (max-width: 991px) {
        .collection-mamdia .main_container.collection {
            width: 100%;
            left: 0;
        }

        .collection-mamdia .sidebar.left-content {
            position: fixed;
            left: -320px;
            top: 0;
            bottom: 0;
            width: 300px;
            z-index: 1050;
            background: #fff;
            overflow-y: auto;
            transition: left 0.25s ease;
            padding-top: 20px;
        }

        .collection-mamdia .sidebar.left-content.is-open-mobile {
            left: 0;
            box-shadow: 0 0 18px rgba(0, 0, 0, 0.2);
        }

        .collection-mamdia .open-filters {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            position: fixed;
            right: 14px;
            bottom: 80px;
            z-index: 1055;
            border-radius: 999px;
            background: #8bc34a;
            color: #fff;
            padding: 10px 14px;
            font-size: 13px;
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.18);
        }
    }

    @media (max-width: 767px) {
        .collection-mamdia .view-mode {
            margin-bottom: 0;
        }
    }
</style>
@endpush
