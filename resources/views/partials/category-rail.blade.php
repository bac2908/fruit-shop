@php
	$currentCategorySlug = (string) request()->route('slug', '');
	$showsCategoryRail = request()->routeIs(
		'home',
		'products.index',
		'products.show',
		'categories.show',
		'categories.show.tag',
		'search'
	);
@endphp

@if($showsCategoryRail && !empty($quickCategories) && $quickCategories->isNotEmpty())
	<aside class="tgc-category-rail" data-category-rail aria-label="Danh mục sản phẩm nhanh">
		<a
			class="tgc-category-rail-link"
			href="{{ route('products.index') }}"
			aria-label="Danh mục"
			@if(request()->routeIs('products.index')) aria-current="page" @endif
		>
			<i class="fa fa-th-large" aria-hidden="true"></i>
			<span class="tgc-category-rail-label">Danh mục</span>
		</a>

		@foreach($quickCategories as $quickCategory)
			@php
				$isCurrentCategory = request()->routeIs('categories.show', 'categories.show.tag')
					&& $currentCategorySlug === (string) $quickCategory->slug;
			@endphp
			<a
				class="tgc-category-rail-link"
				href="{{ route('categories.show', $quickCategory->slug) }}"
				aria-label="{{ $quickCategory->name }}"
				@if($isCurrentCategory) aria-current="page" @endif
			>
				<img src="{{ $quickCategory->getIconUrl() }}" alt="" width="28" height="28" loading="lazy" decoding="async">
				<span class="tgc-category-rail-label">{{ $quickCategory->name }}</span>
			</a>
		@endforeach
	</aside>
@endif
