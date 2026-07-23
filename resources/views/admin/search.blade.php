@extends('layouts.admin')

@section('title', 'Tìm kiếm | FruitShop Admin')

@section('head')
<style>
    .search-summary { margin-bottom: 16px; color: var(--admin-subtle); }
    .search-group { margin-bottom: 14px; }
    .search-list { display: grid; gap: 8px; }
    .search-row { display: grid; grid-template-columns: 130px minmax(0, 1fr) auto; gap: 12px; align-items: center; padding: 12px; border: 1px solid var(--admin-line); border-radius: 8px; background: #fff; color: inherit; }
    .search-row:hover { border-color: #9bc4a8; background: #fbfefb; }
    .search-type { color: var(--admin-primary); font-size: 11px; font-weight: 800; text-transform: uppercase; }
    .search-row strong, .search-row span { display: block; }
    .search-row small { color: var(--admin-subtle); }
    @media (max-width: 700px) { .search-row { grid-template-columns: 1fr auto; } .search-type { grid-column: 1 / -1; } }
</style>
@endsection

@section('admin_content')
<section class="page-head">
    <div>
        <h1 class="page-title">Tìm kiếm toàn hệ thống</h1>
        <p class="page-subtitle">Tra cứu nhanh đơn hàng, sản phẩm, khách hàng và hộp thư.</p>
    </div>
</section>

@if($query === '')
    <section class="panel empty-box">
        <i class="ri-search-line"></i>
        <div>Nhập từ khóa vào thanh tìm kiếm phía trên để bắt đầu.</div>
    </section>
@else
    @php($total = $products->count() + $orders->count() + $customers->count() + $contacts->count())
    <p class="search-summary">Tìm thấy <strong>{{ $total }}</strong> kết quả cho “{{ $query }}”.</p>

    <section class="panel search-group">
        <div class="panel-head"><h2 class="panel-title">Đơn hàng ({{ $orders->count() }})</h2></div>
        <div class="search-list">
            @forelse($orders as $order)
                <a class="search-row" href="{{ route('admin.orders.show', $order) }}">
                    <span class="search-type">Đơn hàng</span>
                    <span><strong>{{ $order->code }}</strong><small>{{ $order->customer_name }} · {{ $order->customer_phone }}</small></span>
                    <strong>{{ number_format($order->total, 0, ',', '.') }}đ</strong>
                </a>
            @empty <div class="empty-box">Không có đơn hàng phù hợp.</div> @endforelse
        </div>
    </section>

    <section class="grid-2">
        <div class="panel search-group">
            <div class="panel-head"><h2 class="panel-title">Sản phẩm ({{ $products->count() }})</h2></div>
            <div class="search-list">
                @forelse($products as $product)
                    <a class="search-row" href="{{ route('admin.products.edit', $product) }}">
                        <span class="search-type">Sản phẩm</span>
                        <span><strong>{{ $product->name }}</strong><small>{{ $product->sku ?: 'Chưa có SKU' }}</small></span>
                        <strong>Tồn {{ $product->stock }}</strong>
                    </a>
                @empty <div class="empty-box">Không có sản phẩm phù hợp.</div> @endforelse
            </div>
        </div>

        <div class="panel search-group">
            <div class="panel-head"><h2 class="panel-title">Khách hàng ({{ $customers->count() }})</h2></div>
            <div class="search-list">
                @forelse($customers as $customer)
                    <a class="search-row" href="{{ route('admin.customers.show', $customer) }}">
                        <span class="search-type">Khách hàng</span>
                        <span><strong>{{ $customer->name }}</strong><small>{{ $customer->email }}</small></span>
                        <i class="ri-arrow-right-line"></i>
                    </a>
                @empty <div class="empty-box">Không có khách hàng phù hợp.</div> @endforelse
            </div>
        </div>
    </section>

    <section class="panel search-group">
        <div class="panel-head"><h2 class="panel-title">Hộp thư ({{ $contacts->count() }})</h2></div>
        <div class="search-list">
            @forelse($contacts as $contact)
                <a class="search-row" href="{{ route('admin.contacts.show', $contact) }}">
                    <span class="search-type">Liên hệ</span>
                    <span><strong>{{ $contact->subject ?: 'Yêu cầu hỗ trợ' }}</strong><small>{{ $contact->name }} · {{ $contact->email }}</small></span>
                    <i class="ri-arrow-right-line"></i>
                </a>
            @empty <div class="empty-box">Không có liên hệ phù hợp.</div> @endforelse
        </div>
    </section>
@endif
@endsection
