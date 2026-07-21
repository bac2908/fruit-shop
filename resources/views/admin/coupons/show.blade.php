@extends('layouts.admin')
@section('title', $coupon->code.' | FruitShop Admin')
@section('head') @include('admin.coupons._styles') @endsection

@section('admin_content')
    <section class="page-head reveal" style="--delay:0ms">
        <div>
            <div class="coupon-actions"><h1 class="page-title">{{ $coupon->code }}</h1><span class="coupon-status {{ $coupon->admin_status_key }}">{{ $coupon->admin_status_label }}</span></div>
            <p class="page-subtitle">{{ $coupon->title }}</p>
        </div>
        <div class="coupon-actions">
            <a class="btn btn-ghost" href="{{ route('admin.coupons') }}"><i class="ri-arrow-left-line"></i>Danh sách</a>
            <a class="btn btn-primary" href="{{ route('admin.coupons.edit',$coupon) }}"><i class="ri-edit-2-line"></i>Chỉnh sửa</a>
        </div>
    </section>

    @include('admin.coupons._errors')

    <section class="coupon-detail-grid reveal" style="--delay:50ms">
        <article class="panel">
            <div class="panel-head"><div><h2 class="panel-title">Cấu hình voucher</h2><p class="panel-sub">Thông tin đang được dùng tại giỏ hàng và checkout.</p></div></div>
            <div class="coupon-summary">
                <div><span>Quyền lợi</span><strong>{{ $coupon->discount_label }}</strong></div>
                <div><span>Loại</span><strong>{{ ['percent'=>'Giảm phần trăm','fixed'=>'Giảm cố định','gift'=>'Tặng sản phẩm'][$coupon->type] ?? $coupon->type }}</strong></div>
                <div><span>Điều kiện</span><strong>{{ $coupon->condition_label }}</strong></div>
                <div><span>Phạm vi</span><strong>{{ $coupon->is_public ? 'Mọi khách đủ điều kiện' : 'Chỉ tài khoản được gán' }}</strong></div>
                <div><span>Giới hạn toàn hệ thống</span><strong>{{ $coupon->usage_limit ? number_format((int)$coupon->used_count).' / '.number_format((int)$coupon->usage_limit) : 'Không giới hạn' }}</strong></div>
                <div><span>Giới hạn mỗi khách</span><strong>{{ $coupon->per_customer_limit ? number_format((int)$coupon->per_customer_limit).' lượt' : 'Không giới hạn' }}</strong></div>
                <div><span>Bắt đầu</span><strong>{{ \App\Support\LocalDateTime::format($coupon->starts_at,'d/m/Y H:i','Có hiệu lực ngay') }}</strong></div>
                <div><span>Kết thúc</span><strong>{{ \App\Support\LocalDateTime::format($coupon->ends_at,'d/m/Y H:i','Không giới hạn') }}</strong></div>
                @if($coupon->type === \App\Models\Coupon::TYPE_GIFT)
                    <div class="full"><span>Sản phẩm quà</span><strong>{{ $coupon->giftProduct ? $coupon->gift_quantity.' × '.$coupon->giftProduct->name.' · tồn '.number_format((int)$coupon->giftProduct->stock) : 'Voucher cũ chưa liên kết tồn kho' }}</strong></div>
                @endif
            </div>
            @if($coupon->description)<p class="coupon-muted" style="margin:14px 0 0">{{ $coupon->description }}</p>@endif
            <div class="coupon-actions" style="margin-top:16px">
                <form method="post" action="{{ route('admin.coupons.toggle',$coupon) }}">@csrf @method('PATCH')<button class="btn btn-ghost" type="submit"><i class="{{ $coupon->is_active ? 'ri-pause-circle-line' : 'ri-play-circle-line' }}"></i>{{ $coupon->is_active ? 'Tạm ngưng' : 'Bật voucher' }}</button></form>
                <form method="post" action="{{ route('admin.coupons.destroy',$coupon) }}" onsubmit="return confirm('Lưu trữ voucher? Lịch sử sử dụng vẫn được giữ lại.');">@csrf @method('DELETE')<button class="btn btn-ghost coupon-danger" type="submit"><i class="ri-archive-line"></i>Lưu trữ</button></form>
            </div>
        </article>

        <aside class="panel">
            <div class="panel-head"><div><h2 class="panel-title">Phát voucher</h2><p class="panel-sub">Chỉ tạo bản ghi mới; tài khoản đã dùng sẽ được bỏ qua.</p></div></div>
            <form class="assignment-grid" method="post" action="{{ route('admin.coupons.assign') }}">
                @csrf
                <input type="hidden" name="coupon_id" value="{{ $coupon->id }}">
                <div class="coupon-radio-group">
                    <label class="coupon-radio"><input type="radio" name="target" value="single" @checked(old('target','single') === 'single')><span><strong>Một khách hàng</strong><small>Phát theo email</small></span></label>
                    <label class="coupon-radio"><input type="radio" name="target" value="all_customers" @checked(old('target') === 'all_customers')><span><strong>Tất cả khách</strong><small>Không gồm admin</small></span></label>
                </div>
                <div class="coupon-field" id="couponEmailField"><label for="email">Email khách hàng</label><input class="coupon-input" id="email" type="email" name="email" value="{{ old('email') }}" placeholder="khach@example.com"></div>
                <div class="coupon-field"><label for="expires_at">Hạn riêng</label><input class="coupon-input" id="expires_at" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"><span class="coupon-help">Để trống để dùng hạn chung của voucher.</span></div>
                <button class="btn btn-primary" type="submit"><i class="ri-user-add-line"></i>Phát voucher</button>
            </form>
        </aside>
    </section>

    <section class="panel reveal" style="--delay:90ms;margin-bottom:14px">
        <div class="panel-head"><div><h2 class="panel-title">Lịch sử sử dụng</h2><p class="panel-sub">{{ number_format((int)$coupon->usages_count) }} lượt, tổng giảm {{ number_format((int)$coupon->usages()->sum('discount_total'),0,',','.') }}đ.</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Thời gian</th><th>Khách hàng</th><th>Đơn hàng</th><th>Giá trị giảm</th></tr></thead><tbody>
            @forelse($usages as $usage)
                <tr><td>{{ \App\Support\LocalDateTime::format($usage->used_at) }}</td><td><strong>{{ $usage->user->name ?? 'Khách hàng' }}</strong><div class="coupon-muted">{{ $usage->customer_email ?: optional($usage->user)->email ?: '-' }}</div></td><td>@if($usage->order)<a class="coupon-code" href="{{ route('admin.orders.show',$usage->order) }}">{{ $usage->order->code }}</a>@else<span class="coupon-muted">Không gắn đơn</span>@endif</td><td>{{ number_format((int)$usage->discount_total,0,',','.') }}đ</td></tr>
            @empty<tr><td colspan="4"><div class="coupon-empty">Voucher chưa được sử dụng.</div></td></tr>@endforelse
        </tbody></table></div>
        @if($usages->hasPages())<div class="coupon-pager"><span>Trang {{ $usages->currentPage() }} / {{ $usages->lastPage() }}</span><div>@if($usages->onFirstPage())<span class="disabled">Trước</span>@else<a href="{{ $usages->previousPageUrl() }}">Trước</a>@endif @if($usages->hasMorePages())<a href="{{ $usages->nextPageUrl() }}">Sau</a>@else<span class="disabled">Sau</span>@endif</div></div>@endif
    </section>

    <section class="panel reveal" style="--delay:120ms">
        <div class="panel-head"><div><h2 class="panel-title">Danh sách đã phát</h2><p class="panel-sub">Theo dõi khách đã nhận, đã dùng hoặc voucher cá nhân đã hết hạn.</p></div></div>
        <div class="table-wrap"><table><thead><tr><th>Khách hàng</th><th>Ngày phát</th><th>Hạn riêng</th><th>Trạng thái</th></tr></thead><tbody>
            @forelse($assignments as $assignment)
                <tr><td><strong>{{ $assignment->user->name ?? 'Tài khoản đã xóa' }}</strong><div class="coupon-muted">{{ optional($assignment->user)->email ?: '-' }}</div></td><td>{{ \App\Support\LocalDateTime::format($assignment->assigned_at) }}</td><td>{{ \App\Support\LocalDateTime::format($assignment->expires_at,'d/m/Y H:i','Theo hạn chung') }}</td><td><span class="coupon-status {{ $assignment->is_usable ? 'active' : ($assignment->used_at ? 'inactive' : 'expired') }}">{{ $assignment->status_label }}</span></td></tr>
            @empty<tr><td colspan="4"><div class="coupon-empty">Voucher chưa được phát riêng cho tài khoản nào.</div></td></tr>@endforelse
        </tbody></table></div>
        @if($assignments->hasPages())<div class="coupon-pager"><span>Trang {{ $assignments->currentPage() }} / {{ $assignments->lastPage() }}</span><div>@if($assignments->onFirstPage())<span class="disabled">Trước</span>@else<a href="{{ $assignments->previousPageUrl() }}">Trước</a>@endif @if($assignments->hasMorePages())<a href="{{ $assignments->nextPageUrl() }}">Sau</a>@else<span class="disabled">Sau</span>@endif</div></div>@endif
    </section>
@endsection

@section('scripts')
<script>
    (() => {
        const radios = document.querySelectorAll('input[name="target"]');
        const emailField = document.getElementById('couponEmailField');
        const email = document.getElementById('email');
        const sync = () => {
            const selected = document.querySelector('input[name="target"]:checked');
            const single = !selected || selected.value === 'single';
            emailField.hidden = !single;
            email.required = single;
        };
        radios.forEach((radio) => radio.addEventListener('change', sync));
        sync();
    })();
</script>
@endsection
