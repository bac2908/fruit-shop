@extends('layouts.admin')

@section('title', 'Quản lý voucher | FruitShop Admin')
@section('head') @include('admin.coupons._styles') @endsection

@section('admin_content')
    <section class="page-head reveal" style="--delay:0ms">
        <div>
            <h1 class="page-title">Voucher và khuyến mãi</h1>
            <p class="page-subtitle">Quản lý điều kiện, lượt sử dụng, quà tặng tồn kho và lịch sử phát voucher.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('admin.coupons.create') }}"><i class="ri-add-line"></i>Tạo voucher</a>
    </section>

    @include('admin.coupons._errors')

    <section class="coupon-stats reveal" style="--delay:40ms">
        <a class="coupon-stat" href="{{ route('admin.coupons') }}"><span>Tổng voucher</span><strong>{{ number_format($stats['total']) }}</strong></a>
        <a class="coupon-stat" href="{{ route('admin.coupons', ['status' => 'active']) }}"><span>Đang áp dụng</span><strong>{{ number_format($stats['active']) }}</strong></a>
        <div class="coupon-stat"><span>Lượt đã sử dụng</span><strong>{{ number_format($stats['used']) }}</strong></div>
        <div class="coupon-stat"><span>Đã phát cho khách</span><strong>{{ number_format($stats['assigned']) }}</strong></div>
        <a class="coupon-stat" href="{{ route('admin.coupons', ['status' => 'archived']) }}"><span>Đã lưu trữ</span><strong>{{ number_format($stats['archived']) }}</strong></a>
    </section>

    @php $hasFilters = collect(['q','status','type','scope'])->contains(fn($key) => request()->filled($key)); @endphp
    <form class="coupon-filter reveal" method="get" action="{{ route('admin.coupons') }}" style="--delay:70ms">
        <div class="coupon-filter-grid">
            <div class="coupon-field">
                <label for="q">Tìm voucher</label>
                <input class="coupon-input" id="q" name="q" value="{{ request('q') }}" placeholder="Mã, tên hoặc mô tả">
            </div>
            <div class="coupon-field">
                <label for="status">Trạng thái</label>
                <select class="coupon-select" id="status" name="status">
                    <option value="">Tất cả</option>
                    @foreach(['active'=>'Đang áp dụng','scheduled'=>'Sắp diễn ra','expired'=>'Đã hết hạn','exhausted'=>'Hết lượt','unavailable'=>'Quà không khả dụng','inactive'=>'Tạm ngưng','archived'=>'Đã lưu trữ'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="coupon-field">
                <label for="type">Loại ưu đãi</label>
                <select class="coupon-select" id="type" name="type">
                    <option value="">Tất cả</option>
                    <option value="percent" @selected(request('type') === 'percent')>Phần trăm</option>
                    <option value="fixed" @selected(request('type') === 'fixed')>Giảm cố định</option>
                    <option value="gift" @selected(request('type') === 'gift')>Tặng sản phẩm</option>
                </select>
            </div>
            <div class="coupon-field">
                <label for="scope">Phạm vi</label>
                <select class="coupon-select" id="scope" name="scope">
                    <option value="">Tất cả</option>
                    <option value="public" @selected(request('scope') === 'public')>Công khai</option>
                    <option value="private" @selected(request('scope') === 'private')>Gán riêng</option>
                </select>
            </div>
            <div class="coupon-field">
                <label for="per_page">Mỗi trang</label>
                <select class="coupon-select" id="per_page" name="per_page">
                    @foreach([15,25,50] as $size)<option value="{{ $size }}" @selected((int) request('per_page',15) === $size)>{{ $size }}</option>@endforeach
                </select>
            </div>
            <div class="coupon-actions">
                <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i>Lọc</button>
                @if($hasFilters)<a class="btn btn-ghost" href="{{ route('admin.coupons') }}" title="Xóa bộ lọc"><i class="ri-refresh-line"></i></a>@endif
            </div>
        </div>
    </form>

    <section class="panel reveal" style="--delay:100ms">
        <div class="panel-head">
            <div><h2 class="panel-title">Danh sách voucher</h2><p class="panel-sub">{{ number_format($coupons->total()) }} kết quả phù hợp.</p></div>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Voucher</th><th>Quyền lợi</th><th>Điều kiện</th><th>Sử dụng</th><th>Đã phát</th><th>Hiệu lực</th><th>Trạng thái</th><th></th></tr></thead>
                <tbody>
                @forelse($coupons as $coupon)
                    <tr>
                        <td><strong class="coupon-code">{{ $coupon->code }}</strong><div>{{ $coupon->title }}</div><span class="coupon-muted">{{ $coupon->is_public ? 'Công khai' : 'Gán riêng' }}</span></td>
                        <td><span class="coupon-type">{{ ['percent'=>'Phần trăm','fixed'=>'Giảm tiền','gift'=>'Tặng quà'][$coupon->type] ?? $coupon->type }}</span><div class="coupon-muted">{{ $coupon->discount_label }}</div>@if($coupon->giftProduct)<div class="coupon-muted">{{ $coupon->gift_quantity }} × {{ $coupon->giftProduct->name }}</div>@endif</td>
                        <td>{{ $coupon->condition_label }}<div class="coupon-muted">{{ $coupon->customer_limit_label }}</div></td>
                        <td><strong>{{ number_format((int) $coupon->used_count) }}</strong>@if($coupon->usage_limit) / {{ number_format((int) $coupon->usage_limit) }}@endif<div class="coupon-muted">{{ number_format((int) $coupon->usages_count) }} bản ghi</div></td>
                        <td>{{ number_format((int) $coupon->user_vouchers_count) }}</td>
                        <td><span class="coupon-muted">{{ \App\Support\LocalDateTime::format($coupon->starts_at, 'd/m/Y H:i', 'Có hiệu lực ngay') }}</span><div class="coupon-muted">{{ $coupon->expiry_label }}</div></td>
                        <td><span class="coupon-status {{ $coupon->admin_status_key }}">{{ $coupon->admin_status_label }}</span></td>
                        <td>
                            <div class="coupon-row-actions">
                                @if($coupon->trashed())
                                    <form method="post" action="{{ route('admin.coupons.restore', $coupon->id) }}">@csrf @method('PATCH')<button class="action-link" type="submit" title="Khôi phục" aria-label="Khôi phục"><i class="ri-arrow-go-back-line"></i></button></form>
                                @else
                                    <a class="action-link" href="{{ route('admin.coupons.show', $coupon) }}" title="Chi tiết" aria-label="Chi tiết"><i class="ri-eye-line"></i></a>
                                    <a class="action-link" href="{{ route('admin.coupons.edit', $coupon) }}" title="Chỉnh sửa" aria-label="Chỉnh sửa"><i class="ri-edit-2-line"></i></a>
                                    <form method="post" action="{{ route('admin.coupons.toggle', $coupon) }}">@csrf @method('PATCH')<button class="action-link" type="submit" title="{{ $coupon->is_active ? 'Tạm ngưng' : 'Bật voucher' }}" aria-label="{{ $coupon->is_active ? 'Tạm ngưng' : 'Bật voucher' }}"><i class="{{ $coupon->is_active ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i></button></form>
                                    <form method="post" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Lưu trữ voucher này? Voucher sẽ bị tắt nhưng lịch sử vẫn được giữ.');">@csrf @method('DELETE')<button class="action-link danger" type="submit" title="Lưu trữ" aria-label="Lưu trữ"><i class="ri-archive-line"></i></button></form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="coupon-empty"><i class="ri-coupon-3-line"></i><div>Không tìm thấy voucher phù hợp.</div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($coupons->hasPages())
            <div class="coupon-pager"><span>Trang {{ $coupons->currentPage() }} / {{ $coupons->lastPage() }}</span><div>@if($coupons->onFirstPage())<span class="disabled">Trước</span>@else<a href="{{ $coupons->previousPageUrl() }}">Trước</a>@endif @if($coupons->hasMorePages())<a href="{{ $coupons->nextPageUrl() }}">Sau</a>@else<span class="disabled">Sau</span>@endif</div></div>
        @endif
    </section>
@endsection
