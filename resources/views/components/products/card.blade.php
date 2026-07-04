@props(['product'])

@php
    $discount = 0;
	$basePrice = (int) ($product->price ?? 0);
	$salePrice = (int) ($product->sale_price ?? 0);
	$orderablePrice = (int) ($product->orderable_price ?? 0);
	$isSalePrice = $basePrice > 0 && $salePrice > 0 && $salePrice < $basePrice;
	$isMissingPrice = $orderablePrice <= 0;
	$isOutOfStock = (int) ($product->stock ?? 0) <= 0;
	$hasGearDetail = (bool) ($product->has_gear_detail ?? false);
	$isCustomOrder = (bool) ($product->is_custom_order_product ?? false);
	$canQuickAdd = !$isMissingPrice && !$isOutOfStock && !$hasGearDetail && !$isCustomOrder;
	$consultUrl = route('contact.page', ['product' => $product->name]);

	if ($isSalePrice) {
        $discount = round((($basePrice - $salePrice) / $basePrice) * 100);
    }
	$imgUrl = $product->primary_image_url;
	$productShowUrl = route('products.show', $product->slug);
	$productQuickViewUrl = route('products.quick-view', $product->slug);
@endphp

<div class="product-box">
	<div class="product-thumbnail flexbox-grid">
		<a href="{{ $productShowUrl }}" title="{{ $product->name }}">
			<img src="{{ $imgUrl }}" data-lazyload="{{ $imgUrl }}" alt="{{ $product->name }}">
		</a>

		@if($discount > 0)
		<div class="sale-flash"><div class="before"></div>- {{ $discount }}% </div>
		@endif

		<div class="product-action hidden-md hidden-sm hidden-xs clearfix">
			@if($canQuickAdd)
				<form action="{{ route('cart.add') }}" method="post" class="variants form-nut-grid margin-bottom-0" data-id="product-{{ $product->id }}">
					<div>
						@csrf
						<input type="hidden" name="product_id" value="{{ $product->id }}" />
						<input type="hidden" name="quantity" value="1" />
						<button type="submit" class="btn-buy btn-cart btn btn-primary left-to add_to_cart" data-toggle="tooltip" title="Đặt hàng">
							<i class="fa fa-shopping-bag"></i>
						</button>
						<a href="{{ $productShowUrl }}" title="Xem nhanh" aria-label="Xem nhanh {{ $product->name }}" class="btn-gray product-detail-link btn right-to" data-quick-view-url="{{ $productQuickViewUrl }}">
							<i class="fa fa-eye"></i>
						</a>
					</div>
				</form>
			@else
				<div class="product-action-links margin-bottom-0" data-id="product-{{ $product->id }}">
					<div>
						@if(!$isMissingPrice && !$isOutOfStock && $isCustomOrder)
							<a href="{{ $consultUrl }}" class="product-action-link product-action-primary left-to" title="Tư vấn đặt mẫu" aria-label="Tư vấn đặt mẫu {{ $product->name }}">
								<i class="fa fa-comments"></i>
							</a>
						@elseif(!$isMissingPrice && !$isOutOfStock && $hasGearDetail)
							<a href="{{ $productShowUrl }}" class="product-action-link product-action-primary left-to" title="Chọn sản phẩm" aria-label="Chọn sản phẩm {{ $product->name }}">
								<i class="fa fa-gear"></i>
							</a>
						@endif

						<a href="{{ $productShowUrl }}" title="Xem nhanh" aria-label="Xem nhanh {{ $product->name }}" class="product-action-link product-action-secondary product-detail-link right-to" data-quick-view-url="{{ $productQuickViewUrl }}">
							<i class="fa fa-eye"></i>
						</a>
					</div>
				</div>
			@endif
		</div>
	</div>
	<div class="product-info a-center">
		<h3 class="product-name"><a href="{{ $productShowUrl }}" title="{{ $product->name }}">{{ $product->name }}</a></h3>

		<div class="price-box clearfix">
			@if($isMissingPrice)
				<div class="special-price clearfix">
					<span class="price product-price">Đang cập nhật giá</span>
				</div>
			@elseif($isSalePrice)
				<div class="special-price">
					<span class="price product-price">{{ $isCustomOrder ? 'Từ ' : '' }}{{ number_format($salePrice, 0, ',', '.') }}₫</span>
				</div>
				<div class="old-price">
					<span class="price product-price-old">{{ number_format($basePrice, 0, ',', '.') }}₫</span>
				</div>
			@else
				<div class="special-price">
					<span class="price product-price">{{ $isCustomOrder ? 'Từ ' : '' }}{{ number_format($orderablePrice, 0, ',', '.') }}₫</span>
				</div>
			@endif
			@if($isCustomOrder && !$isMissingPrice)
				<div class="product-stock-note">Đặt theo yêu cầu</div>
			@endif
			@if($isOutOfStock)
				<div class="product-stock-note">Tạm hết hàng</div>
			@endif
		</div>
	</div>
</div>
