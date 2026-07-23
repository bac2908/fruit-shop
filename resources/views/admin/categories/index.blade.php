@extends('layouts.admin')
@section('title','Danh mục | FruitShop Admin')
@section('head')@include('admin.content._styles')@endsection
@section('admin_content')
<section class="page-head"><div><h1 class="page-title">Danh mục sản phẩm</h1><p class="page-subtitle">Quản lý cấu trúc menu, slogan và SEO của catalog.</p></div><a class="btn btn-primary" href="{{ route('admin.categories.create') }}"><i class="ri-add-line"></i>Thêm danh mục</a></section>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<section class="panel">
    <form class="content-filter" method="get"><div class="content-field"><label>Tìm kiếm</label><input class="content-input" name="q" value="{{ request('q') }}" placeholder="Tên hoặc slug"></div><div class="content-field"><label>Trạng thái</label><select class="content-select" name="status"><option value="">Tất cả</option><option value="active" @selected(request('status')==='active')>Đang hiện</option><option value="hidden" @selected(request('status')==='hidden')>Đang ẩn</option><option value="trashed" @selected(request('status')==='trashed')>Thùng rác</option></select></div><button class="btn btn-ghost"><i class="ri-search-line"></i>Lọc</button></form>
</section>
<section class="panel">
    <div class="table-wrap"><table><thead><tr><th>Danh mục</th><th>Danh mục cha</th><th>Sản phẩm</th><th>Thứ tự</th><th>Trạng thái</th><th></th></tr></thead><tbody>
    @forelse($categories as $category)
        <tr><td><strong>{{ $category->name }}</strong><small style="display:block;color:#748078">{{ $category->slug }}</small></td><td>{{ optional($category->parent)->name ?: 'Danh mục gốc' }}</td><td>{{ $category->products_count }}</td><td>{{ $category->sort_order }}</td><td><span class="content-badge {{ $category->is_active ? '' : 'off' }}">{{ $category->is_active ? 'Đang hiện' : 'Đang ẩn' }}</span></td><td><div class="content-actions">@if($category->trashed())<form method="post" action="{{ route('admin.categories.restore',$category->id) }}">@csrf @method('PATCH')<button class="btn btn-ghost">Khôi phục</button></form>@else<a class="btn btn-ghost" href="{{ route('admin.categories.edit',$category) }}"><i class="ri-edit-line"></i>Sửa</a>@endif</div></td></tr>
    @empty<tr><td colspan="6" style="text-align:center;padding:28px">Không có danh mục phù hợp.</td></tr>@endforelse
    </tbody></table></div>{{ $categories->links() }}
</section>
@endsection
