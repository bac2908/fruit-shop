@extends('layouts.admin')

@section('title', 'Thêm sản phẩm | FruitShop Admin')

@include('admin.products._styles')

@section('admin_content')
    <section class="page-head">
        <div>
            <p class="page-kicker">Catalog</p>
            <h1 class="page-title">Thêm sản phẩm</h1>
            <p class="page-subtitle">Tạo bản ghi sản phẩm, ảnh và tồn kho ban đầu trong một lần lưu.</p>
        </div>
        <a class="btn btn-ghost" href="{{ route('admin.products') }}"><i class="ri-arrow-left-line"></i>Danh sách</a>
    </section>

    @include('admin.products._errors')
    @include('admin.products._form')
@endsection
