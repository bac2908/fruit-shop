@extends('layouts.admin')
@section('title','Trang nội dung | FruitShop Admin')
@section('head')@include('admin.content._styles')@endsection
@section('admin_content')
<section class="page-head"><div><h1 class="page-title">Trang nội dung</h1><p class="page-subtitle">Chính sách, FAQ và nội dung SEO được quản lý tại đây.</p></div><a class="btn btn-primary" href="{{ route('admin.pages.create') }}"><i class="ri-add-line"></i>Thêm trang</a></section>
<section class="panel"><form class="content-filter"><div class="content-field"><label>Tìm kiếm</label><input class="content-input" name="q" value="{{ request('q') }}" placeholder="Tiêu đề hoặc slug"></div><div class="content-field"><label>Trạng thái</label><select class="content-select" name="status"><option value="">Tất cả</option><option value="active" @selected(request('status')==='active')>Đang công khai</option><option value="hidden" @selected(request('status')==='hidden')>Đang ẩn</option></select></div><button class="btn btn-ghost"><i class="ri-search-line"></i>Lọc</button></form></section>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Trang</th><th>SEO title</th><th>Cập nhật</th><th>Trạng thái</th><th></th></tr></thead><tbody>
@forelse($pages as $page)<tr><td><strong>{{ $page->title }}</strong><small style="display:block;color:#748078">/pages/{{ $page->slug }}</small></td><td>{{ $page->meta_title ?: 'Chưa thiết lập' }}</td><td>{{ \App\Support\LocalDateTime::format($page->updated_at) }}</td><td><span class="content-badge {{ $page->is_active?'':'off' }}">{{ $page->is_active?'Công khai':'Đang ẩn' }}</span></td><td><a class="btn btn-ghost" href="{{ route('admin.pages.edit',$page) }}"><i class="ri-edit-line"></i>Sửa</a></td></tr>
@empty<tr><td colspan="5" style="text-align:center;padding:28px">Chưa có trang trong database. Các trang cũ vẫn đang dùng nội dung dự phòng.</td></tr>@endforelse
</tbody></table></div>{{ $pages->links() }}</section>
@endsection
