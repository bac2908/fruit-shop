@extends('layouts.admin')
@section('title',($banner->exists?'Sửa':'Tạo').' banner | FruitShop Admin')
@section('head')@include('admin.content._styles')@endsection
@section('admin_content')
<section class="page-head"><div><h1 class="page-title">{{ $banner->exists?'Cập nhật banner':'Tạo banner' }}</h1><p class="page-subtitle">Ảnh hero nên dùng tỷ lệ 1376 × 768; banner khuyến mại dùng tỷ lệ ngang.</p></div><a class="btn btn-ghost" href="{{ route('admin.banners.index') }}"><i class="ri-arrow-left-line"></i>Danh sách</a></section>
@if($errors->any())<div class="alert alert-danger"><ul style="margin:0 0 0 18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form class="content-form-stack" method="post" enctype="multipart/form-data" action="{{ $banner->exists?route('admin.banners.update',$banner):route('admin.banners.store') }}">@csrf @if($banner->exists)@method('PUT')@endif
<section class="panel"><div class="content-form-grid">
<div class="content-field"><label>Vị trí *</label><select class="content-select" name="placement"><option value="hero" @selected(old('placement',$banner->placement)==='hero')>Slider chính</option><option value="promo" @selected(old('placement',$banner->placement)==='promo')>Banner khuyến mại</option></select></div>
<div class="content-field"><label>Thứ tự</label><input class="content-input" type="number" min="0" name="sort_order" value="{{ old('sort_order',$banner->sort_order??0) }}"></div>
<div class="content-field full"><label>Tiêu đề *</label><input class="content-input" name="title" value="{{ old('title',$banner->title) }}" required maxlength="255"></div>
<div class="content-field full"><label>Phụ đề</label><input class="content-input" name="subtitle" value="{{ old('subtitle',$banner->subtitle) }}" maxlength="500"></div>
<div class="content-field"><label>Ảnh tải lên {{ $banner->exists?'':'*' }}</label><input class="content-input" type="file" name="image" accept=".jpg,.jpeg,.png,.webp"></div>
<div class="content-field"><label>Hoặc URL ảnh</label><input class="content-input" name="image_url" value="{{ old('image_url',$banner->image_url) }}"></div>
<div class="content-field"><label>Alt text</label><input class="content-input" name="alt_text" value="{{ old('alt_text',$banner->alt_text) }}" maxlength="255"></div>
<div class="content-field"><label>Link khi nhấp</label><input class="content-input" name="link_url" value="{{ old('link_url',$banner->link_url) }}" placeholder="/products/slug hoặc https://..."></div>
<div class="content-field"><label>Bắt đầu hiển thị</label><input class="content-input" type="datetime-local" name="starts_at" value="{{ old('starts_at',optional($banner->starts_at)->format('Y-m-d\\TH:i')) }}"></div>
<div class="content-field"><label>Kết thúc hiển thị</label><input class="content-input" type="datetime-local" name="ends_at" value="{{ old('ends_at',optional($banner->ends_at)->format('Y-m-d\\TH:i')) }}"></div>
<label class="content-check full"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$banner->is_active??true))>Bật banner</label>
</div></section><div class="content-actions" style="justify-content:flex-end"><button class="btn btn-primary"><i class="ri-save-line"></i>{{ $banner->exists?'Lưu banner':'Tạo banner' }}</button></div></form>
@if($banner->exists)<form method="post" action="{{ route('admin.banners.destroy',$banner) }}" onsubmit="return confirm('Xóa banner này?')">@csrf @method('DELETE')<button class="btn btn-danger"><i class="ri-delete-bin-line"></i>Xóa banner</button></form>@endif
@endsection
