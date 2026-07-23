@extends('layouts.admin')
@section('title','Banner | FruitShop Admin')
@section('head')@include('admin.content._styles')@endsection
@section('admin_content')
<section class="page-head"><div><h1 class="page-title">Banner storefront</h1><p class="page-subtitle">Quản lý slider chính và banner khuyến mại theo lịch.</p></div><a class="btn btn-primary" href="{{ route('admin.banners.create') }}"><i class="ri-add-line"></i>Thêm banner</a></section>
<section class="panel"><form class="content-filter"><div class="content-field"><label>Vị trí</label><select class="content-select" name="placement"><option value="">Tất cả</option><option value="hero" @selected(request('placement')==='hero')>Slider chính</option><option value="promo" @selected(request('placement')==='promo')>Banner khuyến mại</option></select></div><div class="content-field"><label>Trạng thái</label><select class="content-select" name="status"><option value="">Tất cả</option><option value="active" @selected(request('status')==='active')>Đang bật</option><option value="hidden" @selected(request('status')==='hidden')>Đang tắt</option></select></div><button class="btn btn-ghost"><i class="ri-filter-line"></i>Lọc</button></form></section>
<section class="panel"><div class="content-card-list">
@forelse($banners as $banner)<article class="content-card"><img class="content-thumb" src="{{ str_starts_with($banner->image_url,'http') || str_starts_with($banner->image_url,'//') ? $banner->image_url : asset($banner->image_url) }}" alt=""><div><h3>{{ $banner->title }}</h3><p>{{ $banner->placement==='hero'?'Slider chính':'Banner khuyến mại' }} · thứ tự {{ $banner->sort_order }}</p><span class="content-badge {{ $banner->is_active?'':'off' }}">{{ $banner->is_active?'Đang bật':'Đang tắt' }}</span><div class="content-actions" style="margin-top:9px"><a class="btn btn-ghost" href="{{ route('admin.banners.edit',$banner) }}"><i class="ri-edit-line"></i>Sửa</a></div></div></article>
@empty<div style="padding:28px;color:#718078">Chưa có banner trong database. Storefront đang dùng ảnh dự phòng.</div>@endforelse
</div>{{ $banners->links() }}</section>
@endsection
