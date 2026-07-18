@extends('layouts.app')

@php
    $canonicalUrl = route('products.show', $product->slug);
    $plainDescription = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($product->description ?? $product->short_desc ?? ''))));
    $metaDescription = $plainDescription !== ''
        ? \Illuminate\Support\Str::limit($plainDescription, 320, '')
        : 'Thế Giới Trái Cây - Trái cây sạch, trái cây nhập khẩu chất lượng cao.';
    $schemaImage = (string) $product->primary_image_url;
    if (\Illuminate\Support\Str::startsWith($schemaImage, '//')) {
        $schemaImage = 'https:' . $schemaImage;
    } elseif (!\Illuminate\Support\Str::startsWith($schemaImage, ['http://', 'https://'])) {
        $schemaImage = asset(ltrim($schemaImage, '/'));
    }

    $organizationId = route('home') . '#organization';
    $productSchemaNode = [
        '@type' => 'Product',
        '@id' => $canonicalUrl . '#product',
        'name' => $product->name,
        'url' => $canonicalUrl,
        'image' => [$schemaImage],
        'description' => $metaDescription,
        'sku' => trim((string) ($product->sku ?: $product->slug)),
        'category' => optional($product->category)->name ?: 'Sản phẩm',
        'brand' => ['@id' => $organizationId],
    ];

    $schemaPrice = (int) $product->orderable_price;
    if ($schemaPrice > 0 && !(bool) $product->is_custom_order_product) {
        $productSchemaNode['offers'] = [
            '@type' => 'Offer',
            'url' => $canonicalUrl,
            'priceCurrency' => 'VND',
            'price' => (string) $schemaPrice,
            'availability' => $product->is_active && (int) $product->stock > 0
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => ['@id' => $organizationId],
        ];
    }

    $breadcrumbItems = [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Trang chủ',
            'item' => route('home'),
        ],
    ];

    if ($product->category) {
        $breadcrumbItems[] = [
            '@type' => 'ListItem',
            'position' => count($breadcrumbItems) + 1,
            'name' => $product->category->name,
            'item' => route('categories.show', $product->category->slug),
        ];
    }

    $breadcrumbItems[] = [
        '@type' => 'ListItem',
        'position' => count($breadcrumbItems) + 1,
        'name' => $product->name,
        'item' => $canonicalUrl,
    ];

    $productSchemaJson = json_encode(
        [
            '@context' => 'https://schema.org',
            '@graph' => [
                $productSchemaNode,
                [
                    '@type' => 'BreadcrumbList',
                    '@id' => $canonicalUrl . '#breadcrumb',
                    'itemListElement' => $breadcrumbItems,
                ],
            ],
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
@endphp

@section('title', $product->name . ' - Thế Giới Trái Cây')
@section('canonical', $canonicalUrl)
@section('meta_description', $metaDescription)

@push('head_meta')
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->name }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $product->primary_image_url }}">
    <meta property="product:price:amount" content="{{ (int) $product->orderable_price }}">
    <meta property="product:price:currency" content="VND">
    <script type="application/ld+json">{!! $productSchemaJson !!}</script>
@endpush

@section('content')
@php
    $resolvedImages = collect([$product->primary_image_url])
        ->merge($product->images->pluck('url')->map(function ($url) {
            if (!is_string($url) || $url === '') {
                return null;
            }

            if (\Illuminate\Support\Str::startsWith($url, ['http://', 'https://'])) {
                return $url;
            }

            return asset(ltrim($url, '/'));
        }))
        ->filter()
        ->unique()
        ->values();

    if ($resolvedImages->isEmpty()) {
        $resolvedImages = collect(['//theme.hstatic.net/200000157781/1001036201/14/no-image.jpg?v=1064']);
    }

    $mainImage = (string) $resolvedImages->first();
    $categoryName = optional($product->category)->name ?: 'Sản phẩm';
    $categoryUrl = $product->category ? route('categories.show', $product->category->slug) : route('products.index');

    $basePrice = (int) ($product->price ?? 0);
    $salePrice = (int) ($product->sale_price ?? 0);
    $displayPrice = (int) $product->orderable_price;
    $isSalePrice = $basePrice > 0 && $salePrice > 0 && $salePrice < $basePrice;
    $displayComparePrice = $isSalePrice ? $basePrice : null;
    $discountPercent = ($isSalePrice && $basePrice > 0)
        ? (int) round((($basePrice - $salePrice) / $basePrice) * 100)
        : 0;
    $isContactPrice = $displayPrice <= 0;
    $isCustomOrder = (bool) $product->is_custom_order_product;
    $canOrderProduct = (bool) $product->is_active && (int) $product->stock > 0 && !$isContactPrice && $displayPrice > 0 && !$isCustomOrder;
    $consultUrl = route('contact.page', ['product' => $product->name]);
    $sku = trim((string) $product->getAttribute('sku'));

    $summaryText = trim((string) ($product->short_desc ?? ''));
    if ($summaryText === '' && $plainDescription !== '') {
        $summaryText = \Illuminate\Support\Str::limit($plainDescription, 300, '...');
    }

    $descriptionHtml = trim((string) ($product->description ?? ''));
    if ($descriptionHtml === '') {
        $descriptionHtml = $summaryText !== ''
            ? '<p>' . e($summaryText) . '</p>'
            : '<p>Thông tin sản phẩm đang được cập nhật.</p>';
    }

    $descriptionHtml = preg_replace('#<(script|style|iframe|object|embed|link|meta)\b[^>]*>.*?</\1>#is', '', $descriptionHtml);
    $descriptionHtml = preg_replace('/\son[a-z]+\s*=\s*(["\']).*?\1/is', '', $descriptionHtml);
    $descriptionHtml = preg_replace('/\s(href|src)\s*=\s*(["\'])\s*javascript:.*?\2/is', ' $1="#"', $descriptionHtml);
    $descriptionHtml = strip_tags($descriptionHtml, '<p><br><strong><b><em><i><ul><ol><li><table><thead><tbody><tr><th><td><img><a><h2><h3><h4><blockquote>');

    $variantProducts = collect($optionProducts ?? [])->filter(function ($item) {
        return $item && $item->id;
    })->unique('id')->values();

    if ($variantProducts->isEmpty()) {
        $variantProducts = collect([$product]);
    }

    $hasVariantSelector = $variantProducts->count() > 1;

    $featuredItems = collect($featuredProducts ?? [])->filter(function ($item) {
        return $item && $item->id;
    })->take(5);

    $bundleRecommendation = $bundleRecommendation ?? [
        'items' => collect(),
        'source' => 'none',
        'title' => '',
        'subtitle' => '',
    ];
    $bundleItems = collect($bundleRecommendation['items'] ?? []);
    $aprioriStats = $aprioriStats ?? [
        'orders_count' => 0,
        'transaction_count' => 0,
        'rules_count' => 0,
        'min_pair_count' => 1,
    ];
    $showAprioriMetrics = auth()->check() && auth()->user()->isAdmin();
    $canBundleProduct = $canOrderProduct && !(bool) $product->has_gear_detail;

    $policyItems = [
        [
            'icon' => 'fa-shield',
            'title' => 'Cam kết chất lượng',
            'text' => 'Đổi trả nếu sản phẩm không đạt chất lượng hoặc không đúng mô tả.',
        ],
        [
            'icon' => 'fa-truck',
            'title' => 'Giao nhanh TP.HCM',
            'text' => 'TP.HCM giao nhanh 30 - 90 phút; đơn tỉnh được shop xác nhận đóng gói và tuyến vận chuyển trước khi giao.',
        ],
        [
            'icon' => 'fa-gift',
            'title' => 'Đóng gói chỉnh chu',
            'text' => 'Phù hợp biếu tặng với đóng gói sạch đẹp, hạn chế dập nát khi vận chuyển.',
        ],
        [
            'icon' => 'fa-percent',
            'title' => 'Ưu đãi định kỳ',
            'text' => 'Nhiều chương trình giá tốt theo mùa và mã giảm giá cho khách hàng thân thiết.',
        ],
    ];
@endphp

<section class="pdx-detail-page product product-template" itemscope itemtype="http://schema.org/Product">
    <meta itemprop="name" content="{{ $product->name }}">
    <meta itemprop="url" content="{{ $canonicalUrl }}">
    <meta itemprop="image" content="{{ $mainImage }}">
    <meta itemprop="description" content="{{ $metaDescription }}">

    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <ul class="breadcrumb pdx-breadcrumb">
                    <li class="home">
                        <a href="{{ route('home') }}"><span>Trang chủ</span></a>
                        <span class="mr_lr"> / </span>
                    </li>
                    <li>
                        <a href="{{ $categoryUrl }}"><span>{{ $categoryName }}</span></a>
                        <span class="mr_lr"> / </span>
                    </li>
                    <li><strong><span>{{ $product->name }}</span></strong></li>
                </ul>
            </div>
        </div>

        <div class="row pdx-layout">
            <div class="col-xs-12 col-md-8">
                <article class="pdx-main-card">
                    <div class="row">
                        <div class="col-xs-12 col-sm-6">
                            <div class="pdx-gallery-wrap">
                                <div class="pdx-zoom-stage" id="pdx-zoom-stage">
                                    <a href="{{ $mainImage }}" class="pdx-main-image-link" target="_blank" rel="noopener noreferrer">
                                        <img id="pdx-main-image" src="{{ $mainImage }}" alt="{{ $product->name }}" class="pdx-main-image" width="720" height="720" loading="eager" decoding="async" fetchpriority="high">
                                    </a>
                                    <span class="pdx-zoom-lens" aria-hidden="true"></span>
                                </div>
                                <div class="pdx-zoom-result" id="pdx-zoom-result" aria-hidden="true"></div>

                                @if($resolvedImages->count() > 1)
                                    <div class="pdx-thumb-grid" id="pdx-thumb-grid">
                                        @foreach($resolvedImages as $imageUrl)
                                            <button type="button" class="pdx-thumb {{ $loop->first ? 'is-active' : '' }}" data-image="{{ $imageUrl }}" aria-label="Xem ảnh {{ $loop->iteration }}">
                                                <img src="{{ $imageUrl }}" alt="{{ $product->name }} - ảnh {{ $loop->iteration }}" width="96" height="96" loading="lazy" decoding="async">
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="col-xs-12 col-sm-6">
                            <div class="pdx-info-wrap">
                                <a href="{{ $categoryUrl }}" class="pdx-category-chip">{{ $categoryName }}</a>
                                <h1 class="pdx-title">{{ $product->name }}</h1>

                                <div class="pdx-meta-line">
                                    <span class="pdx-meta-stock {{ $product->stock > 0 ? 'is-available' : 'is-soldout' }}">
                                        <i class="fa {{ $product->stock > 0 ? 'fa-check-circle' : 'fa-times-circle' }}" aria-hidden="true"></i>
                                        {{ $product->stock > 0 ? 'Còn hàng' : 'Tạm hết hàng' }}
                                    </span>
                                    @if($sku !== '')
                                        <span class="pdx-meta-sku">SKU: {{ $sku }}</span>
                                    @endif
                                </div>

                                <div class="pdx-price-line">
                                    @if($isContactPrice)
                                        <span class="pdx-price">Đang cập nhật giá</span>
                                    @else
                                        <span class="pdx-price">{{ $isCustomOrder ? 'Từ ' : '' }}{{ number_format($displayPrice, 0, ',', '.') }}₫</span>
                                        @if($displayComparePrice)
                                            <span class="pdx-price-old">{{ number_format($displayComparePrice, 0, ',', '.') }}₫</span>
                                        @endif
                                        @if($discountPercent > 0)
                                            <span class="pdx-price-badge">-{{ $discountPercent }}%</span>
                                        @endif
                                    @endif
                                </div>

                                @if($summaryText !== '')
                                    <p class="pdx-summary">{{ $summaryText }}</p>
                                @endif

                                @if($isCustomOrder && !$isContactPrice)
                                    <p class="pdx-custom-note">Giá trên là mức tham khảo cho mẫu cơ bản. Shop sẽ xác nhận lại theo loại trái cây, kích thước, ngân sách và thời điểm giao.</p>
                                @endif

                                @if($canOrderProduct)
                                <form action="{{ route('cart.add') }}" method="post" class="pdx-buy-form" data-cart-form>
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $product->id }}">

                                    @if($hasVariantSelector)
                                        <div class="pdx-field">
                                            <label for="pdx-product-option">Chọn quy cách</label>
                                            <select id="pdx-product-option" class="form-control">
                                                @foreach($variantProducts as $variantProduct)
                                                    @php
                                                        $variantBase = (int) ($variantProduct->price ?? 0);
                                                        $variantSale = (int) ($variantProduct->sale_price ?? 0);
                                                        $variantCurrent = (int) $variantProduct->orderable_price;
                                                        $variantLabel = trim((string) ($variantProduct->unit ?: $variantProduct->name));
                                                    @endphp
                                                    <option value="{{ route('products.show', $variantProduct->slug) }}" {{ $variantProduct->id === $product->id ? 'selected' : '' }}>
                                                        {{ $variantLabel }} - {{ $variantCurrent > 0 ? number_format($variantCurrent, 0, ',', '.') . '₫' : 'Đang cập nhật giá' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif

                                    <div class="pdx-qty-row">
                                        <label for="pdx-qty">Số lượng</label>
                                        <div class="pdx-qty-control">
                                            <button type="button" class="pdx-qty-btn" data-action="minus" aria-label="Giảm số lượng">-</button>
                                            <input id="pdx-qty" class="pdx-qty-input" type="text" name="quantity" value="1" maxlength="3" min="1" max="{{ min(99, (int) $product->stock) }}" data-max="{{ min(99, (int) $product->stock) }}">
                                            <button type="button" class="pdx-qty-btn" data-action="plus" aria-label="Tăng số lượng">+</button>
                                        </div>
                                    </div>

                                    <div class="pdx-actions">
                                        <button type="submit" class="pdx-btn pdx-btn-primary">
                                            <i class="fa fa-shopping-bag" aria-hidden="true"></i>
                                            Thêm vào giỏ
                                        </button>
                                        <button type="submit" name="checkout_redirect" value="1" class="pdx-btn pdx-btn-secondary">
                                            Mua nhanh
                                        </button>
                                    </div>
                                </form>
                                @else
                                    <div class="pdx-buy-form pdx-buy-disabled">
                                        <p class="pdx-unavailable-message">
                                            @if($isCustomOrder && !$isContactPrice)
                                                Sản phẩm này được làm theo yêu cầu, vui lòng gửi thông tin để shop tư vấn mẫu phù hợp.
                                            @else
                                                {{ $product->stock <= 0 ? 'Sản phẩm đang tạm hết hàng.' : 'Sản phẩm đang chờ cập nhật giá bán.' }}
                                            @endif
                                        </p>
                                        <a href="{{ $consultUrl }}" class="pdx-btn pdx-btn-secondary">Liên hệ tư vấn</a>
                                    </div>
                                @endif

                                @auth
                                    <form action="{{ route('account.wishlist.toggle', $product) }}" method="post" class="pdx-wishlist-form">
                                        @csrf
                                        <button type="submit" class="pdx-wishlist-btn {{ !empty($isWishlisted) ? 'is-active' : '' }}">
                                            <i class="fa {{ !empty($isWishlisted) ? 'fa-heart' : 'fa-heart-o' }}" aria-hidden="true"></i>
                                            {{ !empty($isWishlisted) ? 'Đã yêu thích' : 'Thêm vào yêu thích' }}
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('login') }}" class="pdx-wishlist-btn pdx-wishlist-link">
                                        <i class="fa fa-heart-o" aria-hidden="true"></i>
                                        Đăng nhập để lưu yêu thích
                                    </a>
                                @endauth

                                <div class="pdx-share-line">
                                    <span>Chia sẻ:</span>
                                    <a rel="nofollow" target="_blank" href="https://www.facebook.com/sharer.php?u={{ urlencode($canonicalUrl) }}" title="Facebook">Facebook</a>
                                    <a rel="nofollow" target="_blank" href="https://twitter.com/share?url={{ urlencode($canonicalUrl) }}" title="Twitter">Twitter</a>
                                    <a rel="nofollow" target="_blank" href="https://pinterest.com/pin/create/button/?url={{ urlencode($canonicalUrl) }}&media={{ urlencode($mainImage) }}" title="Pinterest">Pinterest</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="pdx-tabs-card">
                    <div class="pdx-tabs-nav">
                        <button type="button" class="pdx-tab-btn is-active" data-tab-target="description">Mô tả sản phẩm</button>
                        <button type="button" class="pdx-tab-btn" data-tab-target="delivery">Giao hàng & dịch vụ</button>
                    </div>

                    <div class="pdx-tab-panel is-active" data-tab-panel="description">
                        <div class="pdx-description-content">
                            {!! $descriptionHtml !!}
                        </div>
                    </div>

                    <div class="pdx-tab-panel" data-tab-panel="delivery">
                        <ul class="pdx-delivery-list">
                            @foreach($policyItems as $policy)
                                <li>
                                    <span class="pdx-delivery-icon"><i class="fa {{ $policy['icon'] }}" aria-hidden="true"></i></span>
                                    <div>
                                        <strong>{{ $policy['title'] }}</strong>
                                        <p>{{ $policy['text'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </article>
            </div>

            <div class="col-xs-12 col-md-4">
                <aside class="pdx-side-card pdx-policy-card">
                    <h2 class="pdx-side-title">Cam kết từ Thế Giới Trái Cây</h2>
                    @foreach($policyItems as $policy)
                        <div class="pdx-policy-item">
                            <span class="pdx-policy-icon"><i class="fa {{ $policy['icon'] }}" aria-hidden="true"></i></span>
                            <div class="pdx-policy-content">
                                <h3>{{ $policy['title'] }}</h3>
                                <p>{{ $policy['text'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </aside>

                @if($featuredItems->isNotEmpty())
                    <aside class="pdx-side-card pdx-featured-card">
                        <h2 class="pdx-side-title">Sản phẩm nổi bật</h2>
                        <div class="pdx-featured-list">
                            @foreach($featuredItems as $featured)
                                @php
                                    $featuredPrice = (int) ($featured->price ?? 0);
                                    $featuredSale = (int) ($featured->sale_price ?? 0);
                                    $featuredCurrent = (int) $featured->orderable_price;
                                    $featuredOld = ($featuredSale > 0 && $featuredSale < $featuredPrice) ? $featuredPrice : null;
                                @endphp
                                <a class="pdx-featured-item" href="{{ route('products.show', $featured->slug) }}" title="{{ $featured->name }}">
                                    <img src="{{ $featured->primary_image_url }}" alt="{{ $featured->name }}" width="96" height="96" loading="lazy" decoding="async">
                                    <div class="pdx-featured-info">
                                        <h3>{{ $featured->name }}</h3>
                                        <div class="pdx-featured-price">
                                            @if($featuredCurrent > 0)
                                                <span class="current">{{ number_format($featuredCurrent, 0, ',', '.') }}₫</span>
                                            @else
                                                <span class="current">Đang cập nhật giá</span>
                                            @endif

                                            @if($featuredOld)
                                                <span class="old">{{ number_format($featuredOld, 0, ',', '.') }}₫</span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </aside>
                @endif
            </div>
        </div>
    </div>
</section>

@if($bundleItems->isNotEmpty() && $canBundleProduct)
    <section class="pdx-bundle-section" aria-labelledby="pdx-bundle-title">
        <div class="container">
            <div class="pdx-bundle-heading">
                <div>
                    <span class="pdx-bundle-kicker">
                        <i class="fa fa-shopping-basket" aria-hidden="true"></i>
                        Gợi ý cho giỏ hàng
                    </span>
                    <h2 id="pdx-bundle-title">{{ $bundleRecommendation['title'] }}</h2>
                    <p>{{ $bundleRecommendation['subtitle'] }}</p>
                </div>
                @if($showAprioriMetrics)
                    <div class="pdx-bundle-stats" aria-label="Thống kê hệ thống gợi ý">
                        <span>{{ (int) ($aprioriStats['transaction_count'] ?? 0) }} giao dịch hợp lệ</span>
                        <span>{{ (int) ($aprioriStats['rules_count'] ?? 0) }} luật kết hợp</span>
                    </div>
                @endif
            </div>

            <form action="{{ route('cart.bundle.add') }}" method="post" class="pdx-bundle-shell" data-bundle-form>
                @csrf
                <input type="hidden" name="product_ids[]" value="{{ $product->id }}">

                <div class="pdx-bundle-products">
                    <article class="pdx-bundle-product is-current is-selected">
                        <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}" width="84" height="84" loading="lazy" decoding="async">
                        <div class="pdx-bundle-product-info">
                            <span class="pdx-bundle-product-label">Sản phẩm đang xem</span>
                            <h3><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h3>
                            <strong>{{ number_format($displayPrice, 0, ',', '.') }}₫</strong>
                        </div>
                        <span class="pdx-bundle-fixed-check" title="Sản phẩm chính luôn được thêm" aria-label="Sản phẩm chính đã được chọn">
                            <i class="fa fa-check" aria-hidden="true"></i>
                        </span>
                    </article>

                    @foreach($bundleItems as $recommendation)
                        @php
                            $recommendedProduct = $recommendation['product'];
                            $recommendedPrice = (int) $recommendedProduct->orderable_price;
                        @endphp
                        <label class="pdx-bundle-product is-selected" data-bundle-item>
                            <input
                                type="checkbox"
                                name="product_ids[]"
                                value="{{ $recommendedProduct->id }}"
                                data-bundle-checkbox
                                data-price="{{ $recommendedPrice }}"
                                checked
                            >
                            <span class="pdx-bundle-checkbox" aria-hidden="true">
                                <i class="fa fa-check"></i>
                            </span>
                            <img src="{{ $recommendedProduct->primary_image_url }}" alt="{{ $recommendedProduct->name }}" width="84" height="84" loading="lazy" decoding="async">
                            <span class="pdx-bundle-product-info">
                                <span class="pdx-bundle-product-category">{{ optional($recommendedProduct->category)->name ?: 'Sản phẩm' }}</span>
                                <span class="pdx-bundle-product-name">{{ $recommendedProduct->name }}</span>
                                <strong>{{ number_format($recommendedPrice, 0, ',', '.') }}₫</strong>
                                @if($showAprioriMetrics && ($recommendation['source'] ?? '') === 'behavioral')
                                    <small>
                                        Tin cậy {{ number_format(($recommendation['confidence'] ?? 0) * 100, 1, ',', '.') }}%
                                        · Lift {{ number_format($recommendation['lift'] ?? 0, 2, ',', '.') }}
                                    </small>
                                @endif
                            </span>
                        </label>
                    @endforeach
                </div>

                <aside class="pdx-bundle-summary" aria-label="Tóm tắt nhóm sản phẩm">
                    <span class="pdx-bundle-summary-label">Tạm tính nhóm đã chọn</span>
                    <strong data-bundle-total data-current-price="{{ $displayPrice }}">
                        {{ number_format($displayPrice + $bundleItems->sum(fn ($item) => (int) $item['product']->orderable_price), 0, ',', '.') }}₫
                    </strong>
                    <p><span data-bundle-count>{{ $bundleItems->count() + 1 }}</span> sản phẩm, mỗi loại 1</p>
                    <button type="submit" class="pdx-bundle-submit">
                        <i class="fa fa-shopping-bag" aria-hidden="true"></i>
                        Thêm <span data-bundle-submit-count>{{ $bundleItems->count() + 1 }}</span> sản phẩm vào giỏ
                    </button>
                    <small>Giá và tồn kho được kiểm tra lại khi thêm.</small>
                </aside>
            </form>
        </div>
    </section>
@endif

@if($relatedProducts->isNotEmpty())
    <section class="pdx-related-section">
        <div class="container">
            <div class="row">
                <div class="col-xs-12">
                    <h2 class="pdx-related-title">Sản phẩm liên quan</h2>
                </div>
            </div>
            <div class="row row-fix">
                @foreach($relatedProducts as $relatedProduct)
                    <div class="col-xs-6 col-sm-4 col-md-3 col-fix">
                        <x-products.card :product="$relatedProduct" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection

@push('styles')
<style>
.pdx-detail-page {
    padding: 8px 0 36px;
}

.pdx-breadcrumb {
    margin-bottom: 14px;
}

.pdx-layout {
    display: flex;
    flex-wrap: wrap;
}

.pdx-main-card,
.pdx-tabs-card,
.pdx-side-card {
    background: #fff;
    border: 1px solid #e9e9e9;
    border-radius: 14px;
    box-shadow: 0 8px 24px rgba(23, 44, 30, 0.05);
}

.pdx-main-card {
    padding: 16px;
    margin-bottom: 14px;
}

.pdx-gallery-wrap {
    margin-bottom: 12px;
    position: relative;
}

.pdx-zoom-stage {
    position: relative;
    overflow: hidden;
    border-radius: 12px;
    border: 1px solid #ececec;
    background: #fff;
    cursor: zoom-in;
}

.pdx-main-image-link {
    display: block;
    height: 100%;
}

.pdx-main-image {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    display: block;
    transition: transform 0.15s ease;
    transform-origin: center;
}

.pdx-zoom-lens {
    position: absolute;
    left: 0;
    top: 0;
    width: 120px;
    height: 120px;
    border: 1px solid #8bc34a;
    background: rgba(139, 195, 74, 0.2);
    box-shadow: 0 3px 12px rgba(0, 0, 0, 0.15);
    opacity: 0;
    transition: opacity 0.15s ease;
    pointer-events: none;
}

.pdx-zoom-stage.is-zooming {
    cursor: zoom-out;
}

.pdx-zoom-stage.is-zooming .pdx-main-image {
    transform: none;
}

.pdx-zoom-stage.is-zooming .pdx-zoom-lens {
    opacity: 1;
}

.pdx-zoom-result {
    position: absolute;
    top: 0;
    left: calc(100% + 16px);
    width: 100%;
    height: 100%;
    max-width: 360px;
    max-height: 360px;
    border: 1px solid #ececec;
    border-radius: 12px;
    background-color: #fff;
    background-repeat: no-repeat;
    background-position: 0 0;
    box-shadow: 0 10px 28px rgba(23, 44, 30, 0.18);
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.15s ease;
    pointer-events: none;
    z-index: 5;
}

.pdx-zoom-result.is-visible {
    opacity: 1;
    visibility: visible;
}

.pdx-thumb-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 8px;
    margin-top: 10px;
}

.pdx-thumb {
    border: 1px solid #ececec;
    border-radius: 8px;
    background: #fff;
    padding: 0;
    overflow: hidden;
    cursor: pointer;
    transition: border-color 0.2s ease, transform 0.2s ease;
}

.pdx-thumb img {
    width: 100%;
    aspect-ratio: 1;
    object-fit: cover;
    display: block;
}

.pdx-thumb.is-active,
.pdx-thumb:hover {
    border-color: #8bc34a;
    transform: translateY(-1px);
}

.pdx-info-wrap {
    padding-left: 4px;
}

.pdx-category-chip {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    border: 1px solid #dce9d2;
    background: #f5fbf0;
    color: #628f2e !important;
    padding: 4px 12px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 10px;
}

.pdx-title {
    margin: 0;
    font-size: 30px;
    line-height: 1.28;
    color: #1f1f1f;
}

.pdx-meta-line {
    margin: 10px 0 12px;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.pdx-meta-stock,
.pdx-meta-sku {
    font-size: 13px;
    color: #555;
}

.pdx-meta-stock i {
    margin-right: 5px;
}

.pdx-meta-stock.is-available {
    color: #2d8f4a;
    font-weight: 600;
}

.pdx-meta-stock.is-soldout {
    color: #c24938;
    font-weight: 600;
}

.pdx-price-line {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.pdx-price {
    font-size: 36px;
    font-weight: 700;
    line-height: 1;
    color: #f7941d;
}

.pdx-price-old {
    font-size: 18px;
    color: #888;
    text-decoration: line-through;
}

.pdx-price-badge {
    display: inline-flex;
    align-items: center;
    border-radius: 999px;
    background: #ffefdf;
    color: #d26c00;
    font-size: 12px;
    font-weight: 700;
    padding: 4px 8px;
}

.pdx-summary {
    margin: 0 0 14px;
    color: #555;
    line-height: 1.75;
}

.pdx-custom-note {
    background: #f4faeb;
    border: 1px solid #d8ebbf;
    border-radius: 8px;
    color: #496b22;
    font-size: 13px;
    font-weight: 600;
    line-height: 1.55;
    margin: 0 0 14px;
    padding: 10px 12px;
}

.pdx-buy-form {
    border-top: 1px dashed #e7e7e7;
    padding-top: 12px;
}

.pdx-buy-disabled {
    display: grid;
    gap: 10px;
}

.pdx-unavailable-message {
    background: #fff7ed;
    border: 1px solid #fed7aa;
    border-radius: 8px;
    color: #9a3412;
    font-weight: 700;
    margin: 0;
    padding: 10px 12px;
}

.pdx-field {
    margin-bottom: 12px;
}

.pdx-field label {
    display: inline-block;
    margin-bottom: 5px;
    color: #333;
    font-size: 13px;
    font-weight: 600;
}

.pdx-field .form-control {
    height: 40px;
    border-color: #e4e4e4;
    border-radius: 8px;
}

.pdx-qty-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.pdx-qty-row label {
    margin: 0;
    font-size: 13px;
    font-weight: 600;
    color: #333;
}

.pdx-qty-control {
    display: inline-flex;
    align-items: center;
    border: 1px solid #e4e4e4;
    border-radius: 8px;
    overflow: hidden;
    height: 40px;
}

.pdx-qty-btn {
    width: 40px;
    height: 40px;
    border: 0;
    background: #f7f7f7;
    color: #333;
    font-size: 18px;
    line-height: 1;
}

.pdx-qty-input {
    width: 58px;
    height: 40px;
    border: 0;
    border-left: 1px solid #e4e4e4;
    border-right: 1px solid #e4e4e4;
    text-align: center;
    font-size: 14px;
}

.pdx-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.pdx-btn {
    border: 0;
    border-radius: 8px;
    height: 42px;
    min-width: 160px;
    padding: 0 16px;
    font-size: 14px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    transition: all 0.2s ease;
}

.pdx-btn:hover {
    transform: translateY(-1px);
}

.pdx-btn-primary {
    background: #8bc34a;
    color: #fff;
}

.pdx-btn-primary:hover {
    background: #79ad3f;
    color: #fff;
}

.pdx-btn-secondary {
    background: #f3f3f3;
    color: #333;
}

.pdx-btn-secondary:hover {
    background: #e8e8e8;
    color: #222;
}

.pdx-wishlist-form {
    margin: 12px 0 0;
}

.pdx-wishlist-btn {
    align-items: center;
    background: #fff8f8;
    border: 1px solid #ffd7d7;
    border-radius: 8px;
    color: #b83535;
    display: inline-flex;
    font-size: 14px;
    font-weight: 700;
    gap: 8px;
    min-height: 40px;
    padding: 0 14px;
}

.pdx-wishlist-btn:hover,
.pdx-wishlist-btn:focus {
    background: #ffecec;
    color: #9e2525;
    text-decoration: none;
}

.pdx-wishlist-btn.is-active {
    background: #b83535;
    border-color: #b83535;
    color: #fff;
}

.pdx-share-line {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px dashed #e8e8e8;
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    color: #666;
    font-size: 13px;
}

.pdx-share-line a {
    color: #1e73be;
    font-weight: 500;
}

.pdx-tabs-card {
    padding: 16px;
}

.pdx-tabs-nav {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 12px;
}

.pdx-tab-btn {
    border: 1px solid #e5e5e5;
    border-radius: 999px;
    background: #fff;
    color: #555;
    font-size: 13px;
    font-weight: 600;
    padding: 8px 14px;
}

.pdx-tab-btn.is-active {
    border-color: #8bc34a;
    background: #8bc34a;
    color: #fff;
}

.pdx-tab-panel {
    display: none;
}

.pdx-tab-panel.is-active {
    display: block;
}

.pdx-description-content {
    color: #3f3f3f;
    line-height: 1.8;
}

.pdx-description-content img {
    max-width: 100%;
    height: auto;
}

.pdx-delivery-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 10px;
}

.pdx-delivery-list li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    border: 1px solid #ededed;
    border-radius: 10px;
    background: #fbfbfb;
    padding: 10px;
}

.pdx-delivery-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ecf7df;
    color: #65952f;
    flex: 0 0 30px;
}

.pdx-delivery-list strong {
    display: block;
    margin-bottom: 3px;
    color: #2f2f2f;
    font-size: 14px;
}

.pdx-delivery-list p {
    margin: 0;
    color: #555;
    font-size: 13px;
    line-height: 1.55;
}

.pdx-side-card {
    padding: 14px;
    margin-bottom: 14px;
}

.pdx-side-title {
    margin: 0 0 12px;
    color: #2a2a2a;
    font-size: 20px;
    line-height: 1.35;
}

.pdx-policy-item {
    display: flex;
    align-items: flex-start;
    gap: 9px;
    padding: 10px 0;
    border-bottom: 1px dashed #ececec;
}

.pdx-policy-item:last-child {
    border-bottom: 0;
}

.pdx-policy-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #eff8e6;
    color: #6f9f36;
    flex: 0 0 30px;
}

.pdx-policy-content h3 {
    margin: 0 0 3px;
    color: #2f2f2f;
    font-size: 14px;
    font-weight: 700;
}

.pdx-policy-content p {
    margin: 0;
    color: #5a5a5a;
    font-size: 13px;
    line-height: 1.55;
}

.pdx-featured-list {
    display: grid;
    gap: 10px;
}

.pdx-featured-item {
    display: flex;
    align-items: center;
    gap: 9px;
    border: 1px solid #ededed;
    border-radius: 10px;
    background: #fff;
    padding: 7px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.pdx-featured-item:hover {
    border-color: #d8eac8;
    box-shadow: 0 6px 16px rgba(23, 44, 30, 0.08);
}

.pdx-featured-item img {
    width: 62px;
    height: 62px;
    border-radius: 8px;
    object-fit: cover;
    flex: 0 0 62px;
}

.pdx-featured-info h3 {
    margin: 0 0 3px;
    color: #303030;
    font-size: 13px;
    line-height: 1.45;
    max-height: 37px;
    overflow: hidden;
}

.pdx-featured-price {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 6px;
}

.pdx-featured-price .current {
    color: #f7941d;
    font-size: 14px;
    font-weight: 700;
}

.pdx-featured-price .old {
    color: #929292;
    font-size: 12px;
    text-decoration: line-through;
}

.pdx-related-section {
    margin: 0 0 46px;
}

.pdx-bundle-section {
    margin: 0 0 34px;
}

.pdx-bundle-heading {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 18px;
    margin-bottom: 14px;
}

.pdx-bundle-kicker {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 26px;
    color: #5f922b;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}

.pdx-bundle-heading h2 {
    margin: 3px 0 4px;
    color: #202b1d;
    font-size: 27px;
    line-height: 1.2;
}

.pdx-bundle-heading p {
    margin: 0;
    color: #647060;
    font-size: 14px;
}

.pdx-bundle-stats {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 7px;
    color: #647060;
    font-size: 12px;
}

.pdx-bundle-stats span {
    min-height: 28px;
    border: 1px solid #dce8d2;
    border-radius: 999px;
    background: #fff;
    padding: 5px 9px;
}

.pdx-bundle-shell {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 310px;
    overflow: hidden;
    border: 1px solid #dce8d2;
    border-radius: 8px;
    background: #fff;
    box-shadow: 0 12px 30px rgba(40, 73, 28, 0.07);
}

.pdx-bundle-products {
    min-width: 0;
}

.pdx-bundle-product {
    position: relative;
    display: grid;
    grid-template-columns: 24px 74px minmax(0, 1fr);
    align-items: center;
    gap: 13px;
    min-height: 100px;
    margin: 0;
    border-bottom: 1px solid #e9eee5;
    padding: 12px 18px;
    background: #fff;
    color: #263322;
    cursor: pointer;
    transition: background-color 160ms ease, opacity 160ms ease;
}

.pdx-bundle-product:last-child {
    border-bottom: 0;
}

.pdx-bundle-product.is-current {
    grid-template-columns: 74px minmax(0, 1fr) 24px;
    background: #f5faef;
    cursor: default;
}

.pdx-bundle-product:not(.is-selected) {
    background: #fafafa;
    opacity: 0.62;
}

.pdx-bundle-product:hover {
    background: #f8fbf5;
}

.pdx-bundle-product input {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    opacity: 0;
    pointer-events: none;
}

.pdx-bundle-checkbox,
.pdx-bundle-fixed-check {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    border: 1px solid #b9cab0;
    border-radius: 5px;
    background: #fff;
    color: transparent;
    font-size: 11px;
}

.pdx-bundle-product.is-selected .pdx-bundle-checkbox,
.pdx-bundle-fixed-check {
    border-color: #70b52d;
    background: #70b52d;
    color: #fff;
}

.pdx-bundle-product input:focus-visible + .pdx-bundle-checkbox {
    outline: 3px solid rgba(104, 170, 36, 0.28);
    outline-offset: 2px;
}

.pdx-bundle-product > img {
    width: 74px;
    height: 74px;
    border: 1px solid #e5ebdf;
    border-radius: 6px;
    object-fit: contain;
    background: #fff;
}

.pdx-bundle-product-info {
    display: grid;
    min-width: 0;
    gap: 3px;
}

.pdx-bundle-product-label,
.pdx-bundle-product-category {
    color: #71806c;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.pdx-bundle-product h3,
.pdx-bundle-product-name {
    display: block;
    margin: 0;
    color: #253021;
    font-size: 15px;
    font-weight: 700;
    line-height: 1.35;
}

.pdx-bundle-product h3 a {
    color: inherit;
}

.pdx-bundle-product-info strong {
    color: #f08a12;
    font-size: 15px;
}

.pdx-bundle-product-info small {
    color: #6e7a69;
    font-size: 11px;
}

.pdx-bundle-summary {
    display: flex;
    flex-direction: column;
    justify-content: center;
    min-width: 0;
    border-left: 1px solid #dce8d2;
    padding: 24px;
    background: #f7fbf2;
}

.pdx-bundle-summary-label {
    margin-bottom: 7px;
    color: #61705c;
    font-size: 13px;
}

.pdx-bundle-summary > strong {
    color: #22311e;
    font-size: 26px;
    line-height: 1.2;
}

.pdx-bundle-summary p {
    margin: 7px 0 18px;
    color: #657160;
    font-size: 13px;
}

.pdx-bundle-submit {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 46px;
    width: 100%;
    border: 0;
    border-radius: 6px;
    background: #68aa24;
    color: #fff;
    padding: 10px 14px;
    font-size: 14px;
    font-weight: 700;
}

.pdx-bundle-submit:hover,
.pdx-bundle-submit:focus {
    background: #f39a18;
    color: #fff;
}

.pdx-bundle-summary small {
    margin-top: 10px;
    color: #768071;
    font-size: 11px;
    line-height: 1.45;
}

.pdx-related-title {
    margin: 0 0 12px;
    color: #2a2a2a;
    font-size: 30px;
}

@media (max-width: 1199px) {
    .pdx-title {
        font-size: 26px;
    }

    .pdx-price {
        font-size: 32px;
    }

    .pdx-side-title {
        font-size: 18px;
    }
}

@media (max-width: 991px) {
    .pdx-main-card,
    .pdx-tabs-card,
    .pdx-side-card {
        border-radius: 12px;
    }

    .pdx-main-card {
        padding: 12px;
    }

    .pdx-info-wrap {
        padding-left: 0;
        margin-top: 12px;
    }

    .pdx-zoom-lens {
        width: 100px;
        height: 100px;
    }

    .pdx-zoom-result,
    .pdx-zoom-lens {
        display: none;
    }

    .pdx-zoom-stage {
        cursor: default;
    }

    .pdx-price {
        font-size: 30px;
    }

    .pdx-related-title {
        font-size: 26px;
    }

    .pdx-bundle-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .pdx-bundle-stats {
        justify-content: flex-start;
    }

    .pdx-bundle-shell {
        grid-template-columns: minmax(0, 1fr) 270px;
    }
}

@media (max-width: 767px) {
    .pdx-detail-page {
        padding-top: 4px;
    }

    .pdx-title {
        font-size: 24px;
    }

    .pdx-thumb-grid {
        grid-template-columns: repeat(4, minmax(0, 1fr));
    }

    .pdx-qty-row {
        flex-wrap: wrap;
        align-items: flex-start;
        gap: 6px;
    }

    .pdx-actions {
        display: grid;
        grid-template-columns: 1fr;
    }

    .pdx-btn {
        width: 100%;
        min-width: 0;
    }

    .pdx-share-line {
        gap: 8px;
    }

    .pdx-side-title {
        font-size: 17px;
    }

    .pdx-related-title {
        font-size: 22px;
    }

    .pdx-bundle-heading h2 {
        font-size: 22px;
    }

    .pdx-bundle-shell {
        grid-template-columns: 1fr;
    }

    .pdx-bundle-summary {
        border-top: 1px solid #dce8d2;
        border-left: 0;
        padding: 18px;
    }

    .pdx-bundle-product,
    .pdx-bundle-product.is-current {
        grid-template-columns: 22px 62px minmax(0, 1fr);
        gap: 10px;
        min-height: 88px;
        padding: 10px 12px;
    }

    .pdx-bundle-product.is-current {
        grid-template-columns: 62px minmax(0, 1fr) 22px;
    }

    .pdx-bundle-product > img {
        width: 62px;
        height: 62px;
    }

    .pdx-bundle-product h3,
    .pdx-bundle-product-name {
        font-size: 14px;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var mainImage = document.getElementById('pdx-main-image');
    var mainImageLink = document.querySelector('.pdx-main-image-link');
    var thumbButtons = document.querySelectorAll('#pdx-thumb-grid .pdx-thumb');
    var zoomStage = document.getElementById('pdx-zoom-stage');
    var zoomLens = zoomStage ? zoomStage.querySelector('.pdx-zoom-lens') : null;
    var zoomResult = document.getElementById('pdx-zoom-result');
    var canHover = window.matchMedia ? window.matchMedia('(hover: hover)').matches : true;
    var zoomScaleX = 1;
    var zoomScaleY = 1;

    function syncZoomImage(imageUrl) {
        if (!zoomResult || !imageUrl) {
            return;
        }

        zoomResult.style.backgroundImage = 'url("' + imageUrl + '")';
    }

    function updateZoomMetrics() {
        if (!zoomStage || !zoomLens || !zoomResult) {
            return;
        }

        var rect = zoomStage.getBoundingClientRect();
        var lensWidth = zoomLens.offsetWidth || 1;
        var lensHeight = zoomLens.offsetHeight || 1;
        var resultWidth = zoomResult.offsetWidth || 1;
        var resultHeight = zoomResult.offsetHeight || 1;

        if (resultWidth < 20 || resultHeight < 20) {
            return;
        }

        zoomScaleX = resultWidth / lensWidth;
        zoomScaleY = resultHeight / lensHeight;
        zoomResult.style.backgroundSize = (rect.width * zoomScaleX) + 'px ' + (rect.height * zoomScaleY) + 'px';
    }

    function setZoomPosition(clientX, clientY) {
        if (!zoomStage || !zoomLens || !zoomResult) {
            return;
        }

        var rect = zoomStage.getBoundingClientRect();
        var lensWidth = zoomLens.offsetWidth;
        var lensHeight = zoomLens.offsetHeight;
        var x = clientX - rect.left;
        var y = clientY - rect.top;
        var lensX = Math.max(0, Math.min(rect.width - lensWidth, x - lensWidth / 2));
        var lensY = Math.max(0, Math.min(rect.height - lensHeight, y - lensHeight / 2));

        zoomLens.style.left = lensX + 'px';
        zoomLens.style.top = lensY + 'px';
        zoomResult.style.backgroundPosition = '-' + (lensX * zoomScaleX) + 'px -' + (lensY * zoomScaleY) + 'px';
    }

    if (zoomStage && zoomLens && zoomResult && canHover) {
        zoomStage.addEventListener('mouseenter', function () {
            updateZoomMetrics();
            zoomStage.classList.add('is-zooming');
            zoomResult.classList.add('is-visible');
        });

        zoomStage.addEventListener('mouseleave', function () {
            zoomStage.classList.remove('is-zooming');
            zoomResult.classList.remove('is-visible');
            zoomLens.style.left = '0px';
            zoomLens.style.top = '0px';
            zoomResult.style.backgroundPosition = '0 0';
        });

        zoomStage.addEventListener('mousemove', function (event) {
            setZoomPosition(event.clientX, event.clientY);
        });

        window.addEventListener('resize', function () {
            updateZoomMetrics();
        });
    }

    if (mainImage) {
        syncZoomImage(mainImage.getAttribute('src'));
        mainImage.addEventListener('load', function () {
            updateZoomMetrics();
            syncZoomImage(mainImage.getAttribute('src'));
        });
    }

    thumbButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var nextImage = button.getAttribute('data-image');
            if (!nextImage || !mainImage) {
                return;
            }

            mainImage.setAttribute('src', nextImage);
            if (mainImageLink) {
                mainImageLink.setAttribute('href', nextImage);
            }

            if (zoomStage) {
                zoomStage.classList.remove('is-zooming');
            }

            if (zoomResult) {
                zoomResult.classList.remove('is-visible');
                zoomResult.style.backgroundPosition = '0 0';
            }

            syncZoomImage(nextImage);
            updateZoomMetrics();

            thumbButtons.forEach(function (item) {
                item.classList.remove('is-active');
            });
            button.classList.add('is-active');
        });
    });

    var qtyInput = document.getElementById('pdx-qty');
    var qtyButtons = document.querySelectorAll('.pdx-qty-btn');

    if (qtyInput && qtyButtons.length) {
        var maxQty = parseInt(qtyInput.getAttribute('data-max'), 10) || 99;

        qtyButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var action = button.getAttribute('data-action');
                var current = parseInt(qtyInput.value, 10) || 1;

                if (action === 'plus') {
                    qtyInput.value = Math.min(maxQty, current + 1);
                    return;
                }

                qtyInput.value = Math.max(1, current - 1);
            });
        });

        qtyInput.addEventListener('input', function () {
            var value = parseInt(qtyInput.value, 10);

            if (Number.isNaN(value) || value < 1) {
                value = 1;
            }

            if (value > maxQty) {
                value = maxQty;
            }

            qtyInput.value = value;
        });
    }

    var variantSelect = document.getElementById('pdx-product-option');
    if (variantSelect) {
        variantSelect.addEventListener('change', function () {
            var targetUrl = variantSelect.value;
            if (targetUrl) {
                window.location.href = targetUrl;
            }
        });
    }

    var tabButtons = document.querySelectorAll('.pdx-tab-btn');
    var tabPanels = document.querySelectorAll('.pdx-tab-panel');

    tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var target = button.getAttribute('data-tab-target');
            if (!target) {
                return;
            }

            tabButtons.forEach(function (item) {
                item.classList.remove('is-active');
            });
            button.classList.add('is-active');

            tabPanels.forEach(function (panel) {
                var panelTarget = panel.getAttribute('data-tab-panel');
                panel.classList.toggle('is-active', panelTarget === target);
            });
        });
    });

    var bundleForm = document.querySelector('[data-bundle-form]');
    if (bundleForm) {
        var bundleCheckboxes = Array.from(bundleForm.querySelectorAll('[data-bundle-checkbox]'));
        var bundleTotal = bundleForm.querySelector('[data-bundle-total]');
        var bundleCount = bundleForm.querySelector('[data-bundle-count]');
        var bundleSubmitCount = bundleForm.querySelector('[data-bundle-submit-count]');
        var currentPrice = bundleTotal ? parseInt(bundleTotal.getAttribute('data-current-price'), 10) || 0 : 0;
        var currencyFormatter = new Intl.NumberFormat('vi-VN');

        function updateBundleSummary() {
            var selectedCount = 1;
            var selectedTotal = currentPrice;

            bundleCheckboxes.forEach(function (checkbox) {
                var item = checkbox.closest('[data-bundle-item]');
                var isSelected = checkbox.checked;

                if (item) {
                    item.classList.toggle('is-selected', isSelected);
                }

                if (isSelected) {
                    selectedCount += 1;
                    selectedTotal += parseInt(checkbox.getAttribute('data-price'), 10) || 0;
                }
            });

            if (bundleTotal) {
                bundleTotal.textContent = currencyFormatter.format(selectedTotal) + '₫';
            }
            if (bundleCount) {
                bundleCount.textContent = selectedCount;
            }
            if (bundleSubmitCount) {
                bundleSubmitCount.textContent = selectedCount;
            }
        }

        bundleCheckboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateBundleSummary);
        });
        updateBundleSummary();
    }
});
</script>
@endpush
