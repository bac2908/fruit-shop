@extends('layouts.admin')
@section('title',($page->exists?'Sửa':'Tạo').' trang | FruitShop Admin')
@section('head')@include('admin.content._styles')@endsection
@section('admin_content')
<section class="page-head"><div><h1 class="page-title">{{ $page->exists?'Cập nhật trang nội dung':'Tạo trang nội dung' }}</h1><p class="page-subtitle">HTML được lọc thẻ và thuộc tính nguy hiểm trước khi lưu.</p></div><div class="content-actions">@if($page->exists)<a class="btn btn-ghost" target="_blank" rel="noopener" href="{{ url('/pages/'.$page->slug) }}"><i class="ri-external-link-line"></i>Xem trang</a>@endif<a class="btn btn-ghost" href="{{ route('admin.pages.index') }}">Danh sách</a></div></section>
@if($errors->any())<div class="alert alert-danger"><ul style="margin:0 0 0 18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="content-form-stack" method="post" action="{{ $page->exists?route('admin.pages.update',$page):route('admin.pages.store') }}">@csrf @if($page->exists)@method('PUT')@endif
<section class="panel"><div class="content-form-grid">
<div class="content-field"><label>Tiêu đề *</label><input class="content-input" name="title" value="{{ old('title',$page->title) }}" required maxlength="255"></div><div class="content-field"><label>Slug</label><input class="content-input" name="slug" value="{{ old('slug',$page->slug) }}" placeholder="Tự tạo từ tiêu đề"></div>
<div class="content-field full"><label>Mô tả mở đầu</label><textarea class="content-textarea" name="excerpt" maxlength="2000">{{ old('excerpt',$page->excerpt) }}</textarea></div>
<div class="content-field full"><label>Nội dung HTML</label><textarea class="content-textarea content-editor" name="content" placeholder="<h2>Tiêu đề mục</h2>&#10;<p>Nội dung...</p>">{{ old('content',$page->content) }}</textarea><span class="content-help">Cho phép tiêu đề, đoạn văn, danh sách, liên kết và bảng. Script, style và sự kiện JavaScript bị loại bỏ.</span></div>
<div class="content-field"><label>SEO title</label><input class="content-input" name="meta_title" value="{{ old('meta_title',$page->meta_title) }}" maxlength="255"></div><div class="content-field"><label>SEO description</label><input class="content-input" name="meta_description" value="{{ old('meta_description',$page->meta_description) }}" maxlength="1000"></div>
<label class="content-check full"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$page->is_active??true))>Cho phép truy cập công khai</label>
</div></section><div class="content-actions" style="justify-content:flex-end"><button class="btn btn-primary"><i class="ri-save-line"></i>{{ $page->exists?'Lưu trang':'Tạo trang' }}</button></div></form>
@if($page->exists && $page->is_active)<form method="post" action="{{ route('admin.pages.destroy',$page) }}">@csrf @method('DELETE')<button class="btn btn-danger"><i class="ri-eye-off-line"></i>Ẩn trang</button></form>@endif
@endsection
