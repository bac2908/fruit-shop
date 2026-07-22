@extends('layouts.admin')

@section('title', 'Quản lý khách hàng | FruitShop Admin')

@include('admin.customers._styles')

@section('admin_content')
    @php
        $hasFilters = collect(['q', 'status', 'segment', 'sort'])->contains(fn ($field) => request()->filled($field));
    @endphp

    <section class="page-head reveal">
        <div>
            <h1 class="page-title">Quản lý khách hàng</h1>
            <p class="page-subtitle">Dữ liệu tài khoản, hành vi mua, giá trị khách hàng và trạng thái bảo mật trên cùng một màn hình.</p>
        </div>
        <div class="customer-page-actions">
            <a class="btn btn-ghost" href="{{ route('admin.customers.export', request()->query()) }}">
                <i class="ri-file-excel-2-line"></i>Xuất CSV
            </a>
        </div>
    </section>

    @if(session('success'))<div class="customer-alert success" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="customer-alert error" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="customer-alert error" role="alert">{{ $errors->first() }}</div>@endif

    <section class="customer-stats reveal" style="--delay:40ms">
        @foreach([
            ['Tổng khách hàng', $customerSummary['total'], 'ri-team-line', null],
            ['Đang hoạt động', $customerSummary['active'], 'ri-user-follow-line', 'active'],
            ['Chưa xác minh', $customerSummary['unverified'], 'ri-mail-unread-line', 'unverified'],
            ['Tạm ngưng', $customerSummary['suspended'], 'ri-user-forbid-line', 'suspended'],
            ['Khóa đăng nhập', $customerSummary['locked'], 'ri-lock-line', 'locked'],
        ] as [$label, $value, $icon, $status])
            <a class="customer-stat" href="{{ $status ? route('admin.customers', ['status' => $status]) : route('admin.customers') }}">
                <span><i class="{{ $icon }}"></i>{{ $label }}</span>
                <strong>{{ number_format((int) $value) }}</strong>
            </a>
        @endforeach
    </section>

    <section class="segment-strip reveal" style="--delay:70ms">
        @foreach([
            'new' => ['Chưa mua hàng', $customerSummary['new']],
            'repeat' => ['Khách quay lại', $customerSummary['repeat']],
            'vip' => ['Khách VIP', $customerSummary['vip']],
        ] as $segment => [$label, $value])
            <a class="segment-link {{ request('segment') === $segment ? 'active' : '' }}" href="{{ route('admin.customers', ['segment' => $segment]) }}">
                <span>{{ $label }}</span><strong>{{ number_format((int) $value) }}</strong>
            </a>
        @endforeach
    </section>

    <section class="panel reveal" style="--delay:90ms; margin-bottom:14px">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Tìm và lọc khách hàng</h2>
                <p class="panel-sub">Tìm theo họ tên, email hoặc số điện thoại; kết quả xuất CSV sẽ giữ nguyên bộ lọc.</p>
            </div>
        </div>
        <form class="customer-filter-grid" method="get" action="{{ route('admin.customers') }}">
            <div class="customer-field wide">
                <label for="q">Từ khóa</label>
                <input class="customer-input" id="q" name="q" value="{{ request('q') }}" placeholder="Tên, email hoặc số điện thoại">
            </div>
            <div class="customer-field">
                <label for="status">Trạng thái tài khoản</label>
                <select class="customer-select" id="status" name="status">
                    <option value="">Tất cả</option>
                    <option value="active" @selected(request('status') === 'active')>Đang hoạt động</option>
                    <option value="suspended" @selected(request('status') === 'suspended')>Tạm ngưng</option>
                    <option value="locked" @selected(request('status') === 'locked')>Khóa đăng nhập</option>
                    <option value="unverified" @selected(request('status') === 'unverified')>Chưa xác minh email</option>
                </select>
            </div>
            <div class="customer-field">
                <label for="segment">Phân khúc</label>
                <select class="customer-select" id="segment" name="segment">
                    <option value="">Tất cả</option>
                    <option value="new" @selected(request('segment') === 'new')>Chưa mua hàng</option>
                    <option value="repeat" @selected(request('segment') === 'repeat')>Khách quay lại</option>
                    <option value="vip" @selected(request('segment') === 'vip')>Khách VIP</option>
                    <option value="churn_risk" @selected(request('segment') === 'churn_risk')>Nguy cơ rời bỏ</option>
                </select>
            </div>
            <div class="customer-field">
                <label for="sort">Sắp xếp</label>
                <select class="customer-select" id="sort" name="sort">
                    <option value="newest" @selected(request('sort', 'newest') === 'newest')>Đăng ký mới nhất</option>
                    <option value="oldest" @selected(request('sort') === 'oldest')>Đăng ký cũ nhất</option>
                    <option value="spend_desc" @selected(request('sort') === 'spend_desc')>Chi tiêu cao nhất</option>
                    <option value="orders_desc" @selected(request('sort') === 'orders_desc')>Nhiều đơn nhất</option>
                    <option value="last_order" @selected(request('sort') === 'last_order')>Mua gần đây</option>
                </select>
            </div>
            <div class="customer-field compact">
                <label for="per_page">Mỗi trang</label>
                <select class="customer-select" id="per_page" name="per_page">
                    @foreach([15, 25, 50] as $size)<option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }}</option>@endforeach
                </select>
            </div>
            <div class="customer-filter-actions">
                <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i>Lọc</button>
                @if($hasFilters)<a class="btn btn-ghost" href="{{ route('admin.customers') }}"><i class="ri-refresh-line"></i>Xóa lọc</a>@endif
            </div>
        </form>
    </section>

    <section class="panel reveal" style="--delay:120ms">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Danh sách khách hàng</h2>
                <p class="panel-sub">Chỉ hiển thị tài khoản khách mua hàng, không lẫn tài khoản quản trị.</p>
            </div>
            <span class="tag">{{ number_format($customers->total()) }} khách</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Khách hàng</th><th>Tài khoản</th><th>Mua hàng</th><th>Tổng chi tiêu</th><th>Đơn gần nhất</th><th>Phân khúc</th><th></th></tr></thead>
                <tbody>
                @forelse($customers as $customer)
                    <tr>
                        <td>
                            <div class="customer-cell">
                                <span class="customer-avatar">{{ mb_strtoupper(mb_substr($customer->name, 0, 1)) }}</span>
                                <span><strong>{{ $customer->name }}</strong><small>#{{ $customer->id }} · {{ $customer->phone ?: 'Chưa có SĐT' }}</small></span>
                            </div>
                        </td>
                        <td>
                            <div class="customer-cell-stack">
                                <span>{{ $customer->email }}</span>
                                <span class="account-badges">
                                    <span class="account-badge {{ $customer->account_status }}">{{ $accountStatusLabels[$customer->account_status] ?? $customer->account_status }}</span>
                                    <span class="account-badge {{ $customer->hasVerifiedEmail() ? 'verified' : 'unverified' }}">{{ $customer->hasVerifiedEmail() ? 'Đã xác minh' : 'Chưa xác minh' }}</span>
                                    @if($customer->locked_until && now()->lessThan($customer->locked_until))<span class="account-badge locked">Đang khóa</span>@endif
                                </span>
                            </div>
                        </td>
                        <td><div class="customer-cell-stack"><strong>{{ number_format((int) $customer->orders_count) }} đơn</strong><small>{{ number_format((int) $customer->completed_orders_count) }} hoàn tất · {{ number_format((int) $customer->addresses_count) }} địa chỉ</small></div></td>
                        <td><strong class="customer-money">{{ number_format((int) ($customer->lifetime_value ?? 0), 0, ',', '.') }}đ</strong></td>
                        <td><div class="customer-cell-stack"><span>{{ \App\Support\LocalDateTime::format($customer->last_order_at, 'd/m/Y') }}</span><small>Đăng ký {{ \App\Support\LocalDateTime::format($customer->created_at, 'd/m/Y') }}</small></div></td>
                        <td><span class="segment-badge">{{ $customer->segment_label }}</span></td>
                        <td><a class="btn btn-ghost" href="{{ route('admin.customers.show', $customer) }}"><i class="ri-eye-line"></i>Chi tiết</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="empty-box"><i class="ri-user-search-line"></i><div>Không tìm thấy khách hàng phù hợp với bộ lọc.</div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($customers->hasPages())
            <div class="customer-pager">
                <span>Trang {{ $customers->currentPage() }} / {{ $customers->lastPage() }}</span>
                <div>
                    @if($customers->onFirstPage())<span class="disabled">Trước</span>@else<a href="{{ $customers->previousPageUrl() }}">Trước</a>@endif
                    @if($customers->hasMorePages())<a href="{{ $customers->nextPageUrl() }}">Sau</a>@else<span class="disabled">Sau</span>@endif
                </div>
            </div>
        @endif
    </section>
@endsection
