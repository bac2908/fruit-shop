@extends('layouts.admin')
@section('title',($category->exists?'Sửa':'Tạo').' danh mục | FruitShop Admin')
@section('head')@include('admin.content._styles')@endsection
@section('admin_content')
<section class="page-head"><div><h1 class="page-title">{{ $category->exists ? 'Cập nhật danh mục' : 'Tạo danh mục' }}</h1><p class="page-subtitle">Thông tin này xuất hiện tại menu và các khối sản phẩm storefront.</p></div><a class="btn btn-ghost" href="{{ route('admin.categories.index') }}"><i class="ri-arrow-left-line"></i>Danh sách</a></section>
@if($errors->any())<div class="alert alert-danger"><ul style="margin:0 0 0 18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="content-form-stack" method="post" action="{{ $category->exists ? route('admin.categories.update',$category) : route('admin.categories.store') }}">@csrf @if($category->exists)@method('PUT')@endif
<section class="panel"><div class="content-form-grid">
    <div class="content-field"><label for="name">Tên danh mục *</label><input class="content-input" id="name" name="name" value="{{ old('name',$category->name) }}" required maxlength="150"></div>
    <div class="content-field"><label for="slug">Slug</label><input class="content-input" id="slug" name="slug" value="{{ old('slug',$category->slug) }}" placeholder="Tự tạo từ tên nếu để trống"></div>
    <div class="content-field"><label for="parent_id">Danh mục cha</label><select class="content-select" id="parent_id" name="parent_id"><option value="">Danh mục gốc</option>@foreach($parents as $parent)<option value="{{ $parent->id }}" @selected((string)old('parent_id',$category->parent_id)===(string)$parent->id)>{{ $parent->name }}</option>@endforeach</select></div>
    <div class="content-field"><label for="sort_order">Thứ tự</label><input class="content-input" id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order',$category->sort_order ?? 0) }}"></div>
    <div class="content-field full"><label for="slogan">Slogan riêng</label><input class="content-input" id="slogan" name="slogan" maxlength="255" value="{{ old('slogan',$category->slogan) }}" placeholder="Ví dụ: Tươi ngon từ vườn Việt, chọn lọc mỗi ngày."></div>
    <div class="content-field full"><label for="description">Mô tả</label><textarea class="content-textarea" id="description" name="description" maxlength="2000">{{ old('description',$category->description) }}</textarea></div>
    <div class="content-field full"><label for="icon_url">URL icon</label><input class="content-input" id="icon_url" name="icon_url" maxlength="1000" value="{{ old('icon_url',$category->icon_url) }}"><span class="content-help">Có thể dùng URL HTTPS hoặc đường dẫn local trong public.</span></div>
    <div class="content-field"><label for="meta_title">SEO title</label><input class="content-input" id="meta_title" name="meta_title" maxlength="255" value="{{ old('meta_title',$category->meta_title) }}"></div>
    <div class="content-field"><label for="meta_description">SEO description</label><input class="content-input" id="meta_description" name="meta_description" maxlength="1000" value="{{ old('meta_description',$category->meta_description) }}"></div>
    <label class="content-check full"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$category->is_active ?? true))>Hiển thị trên storefront</label>
</div></section>
<div class="content-actions" style="justify-content:flex-end">@if($category->exists)<button class="btn btn-primary" type="submit"><i class="ri-save-line"></i>Lưu danh mục</button>@else<button class="btn btn-primary" type="submit"><i class="ri-add-line"></i>Tạo danh mục</button>@endif</div></form>
@if($category->exists)<form method="post" action="{{ route('admin.categories.destroy',$category) }}" onsubmit="return confirm('Đưa danh mục vào thùng rác?')">@csrf @method('DELETE')<button class="btn btn-danger" type="submit"><i class="ri-delete-bin-line"></i>Đưa vào thùng rác</button></form>@endif
@endsection
