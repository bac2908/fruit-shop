@extends('layouts.admin')

@section('title', 'Trung tâm tác vụ | FruitShop Admin')

@section('head')
<style>
    .action-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 16px; }
    .action-stat { padding: 13px; border: 1px solid var(--admin-line); border-radius: 8px; background: #fff; }
    .action-stat strong { display: block; font: 700 24px 'Sora', sans-serif; }
    .action-stat span { color: var(--admin-subtle); font-size: 12px; }
    .action-list { display: grid; gap: 8px; }
    .action-row { display: grid; grid-template-columns: 38px minmax(0, 1fr) auto; gap: 10px; align-items: center; padding: 11px; border: 1px solid var(--admin-line); border-radius: 8px; background: #fff; color: inherit; }
    .action-row:hover { border-color: #97c3a5; }
    .action-icon { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 8px; background: var(--admin-primary-soft); color: var(--admin-primary); font-size: 19px; }
    .action-row strong, .action-row small { display: block; }
    .action-row small { color: var(--admin-subtle); margin-top: 3px; }
    @media (max-width: 980px) { .action-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 620px) { .action-grid { grid-template-columns: 1fr; } }
</style>
@endsection

@section('admin_content')
<section class="page-head">
    <div>
        <h1 class="page-title">Trung tâm tác vụ</h1>
        <p class="page-subtitle">Danh sách ưu tiên được tổng hợp trực tiếp từ hoạt động cửa hàng.</p>
    </div>
    <span class="tag">{{ $total }} việc cần chú ý</span>
</section>

<div class="action-grid">
    @foreach([
        'pending_orders' => 'Đơn chờ xác nhận',
        'low_stock' => 'Sản phẩm sắp hết',
        'new_contacts' => 'Liên hệ mới',
        'cancellations' => 'Yêu cầu hủy',
        'returns' => 'Yêu cầu đổi trả',
    ] as $key => $label)
        <div class="action-stat"><strong>{{ $counts[$key] }}</strong><span>{{ $label }}</span></div>
    @endforeach
</div>

@php
    $sections = [
        ['title' => 'Đơn chờ xác nhận', 'items' => $pendingOrders, 'kind' => 'order'],
        ['title' => 'Tồn kho cần bổ sung', 'items' => $lowStockProducts, 'kind' => 'stock'],
        ['title' => 'Hộp thư chưa đọc', 'items' => $newContacts, 'kind' => 'contact'],
        ['title' => 'Yêu cầu hủy đang chờ', 'items' => $cancellationRequests, 'kind' => 'cancel'],
        ['title' => 'Yêu cầu đổi trả đang chờ', 'items' => $returnRequests, 'kind' => 'return'],
    ];
@endphp

@foreach($sections as $section)
    <section class="panel" style="margin-bottom:14px;">
        <div class="panel-head">
            <h2 class="panel-title">{{ $section['title'] }}</h2>
            <span class="tag">{{ $section['items']->count() }}</span>
        </div>
        <div class="action-list">
            @forelse($section['items'] as $item)
                @php
                    $isOrder = $section['kind'] === 'order';
                    $isStock = $section['kind'] === 'stock';
                    $isContact = $section['kind'] === 'contact';
                    $order = in_array($section['kind'], ['cancel', 'return'], true) ? $item->order : null;
                    $url = $isOrder ? route('admin.orders.show', $item)
                        : ($isStock ? route('admin.products.edit', $item)
                        : ($isContact ? route('admin.contacts.show', $item)
                        : ($order ? route('admin.orders.show', $order) : route('admin.orders'))));
                    $title = $isOrder ? 'Đơn '.$item->code.' · '.$item->customer_name
                        : ($isStock ? $item->name
                        : ($isContact ? ($item->subject ?: 'Liên hệ từ '.$item->name)
                        : (($section['kind'] === 'cancel' ? 'Hủy đơn ' : $item->type_label.' đơn ').($order?->code ?? '#'.$item->order_id))));
                    $detail = $isOrder ? number_format($item->total, 0, ',', '.').'đ'
                        : ($isStock ? 'Còn '.$item->stock.' · ngưỡng '.($item->low_stock_threshold ?: 'mặc định')
                        : ($isContact ? $item->email
                        : $item->reason_label));
                @endphp
                <a class="action-row" href="{{ $url }}">
                    <span class="action-icon"><i class="{{ $isOrder ? 'ri-shopping-bag-3-line' : ($isStock ? 'ri-alarm-warning-line' : ($isContact ? 'ri-mail-unread-line' : 'ri-arrow-go-back-line')) }}"></i></span>
                    <span><strong>{{ $title }}</strong><small>{{ $detail }}</small></span>
                    <i class="ri-arrow-right-line"></i>
                </a>
            @empty
                <div class="empty-box">Không có mục nào cần xử lý.</div>
            @endforelse
        </div>
    </section>
@endforeach
@endsection
