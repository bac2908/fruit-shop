@extends('layouts.admin')

@section('title', 'Sửa ' . $product->name . ' | FruitShop Admin')

@include('admin.products._styles')

@section('admin_content')
    <section class="page-head">
        <div>
            <p class="page-kicker">{{ $product->sku }}</p>
            <h1 class="page-title">Sửa sản phẩm</h1>
            <p class="page-subtitle">Cập nhật nội dung, giá, ảnh và tồn kho có lưu lịch sử.</p>
        </div>
        <div class="page-actions">
            @if($product->is_active)
                <a class="btn btn-ghost" href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener"><i class="ri-external-link-line"></i>Xem storefront</a>
            @endif
            <a class="btn btn-ghost" href="{{ route('admin.products') }}"><i class="ri-arrow-left-line"></i>Danh sách</a>
        </div>
    </section>

    @include('admin.products._errors')
    @include('admin.products._form')
@endsection
