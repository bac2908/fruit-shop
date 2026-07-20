@extends('layouts.admin')

@section('title', 'Quản lý sản phẩm | FruitShop Admin')

@section('head')
    <style>
        .admin-alert {
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 14px;
            font-size: 13px;
            font-weight: 600;
        }

        .admin-alert.success {
            color: #245f35;
            border: 1px solid #bfe3c8;
            background: #edf9f0;
        }

        .admin-alert.error {
            color: #8f3122;
            border: 1px solid #f0bcb1;
            background: #fff1ee;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 1.35fr repeat(4, minmax(0, 1fr)) auto;
            gap: 10px;
            align-items: end;
            margin-bottom: 12px;
        }

        .field {
            display: grid;
            gap: 6px;
        }

        .field label {
            font-size: 12px;
            color: #5f7368;
            font-weight: 700;
        }

        .input,
        .select {
            width: 100%;
            border: 1px solid #d6dfd4;
            border-radius: 11px;
            padding: 10px 11px;
            font-size: 13px;
            font-family: inherit;
            background: #fff;
            color: #1a3729;
        }

        .filter-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            min-width: 260px;
        }

        .product-thumb {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            border: 1px solid #dce6da;
            background: #f4f8f2;
            display: grid;
            place-items: center;
            color: #597264;
            font-size: 18px;
            overflow: hidden;
            flex: 0 0 auto;
        }

        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .product-meta strong {
            display: block;
            font-size: 13px;
            line-height: 1.35;
        }

        .product-meta span {
            color: #5f7368;
            font-size: 12px;
        }

        .price-line {
            display: grid;
            gap: 2px;
            white-space: nowrap;
        }

        .price-line strong {
            color: #cc6500;
        }

        .price-line del {
            color: #9aa49d;
            font-size: 12px;
        }

        .stock-note {
            color: #5f7368;
            font-size: 12px;
        }

        .status-button {
            border: none;
            cursor: pointer;
            font-family: inherit;
        }

        .status-pill.low {
            color: #8b6200;
            background: #fff6db;
        }

        .inline-form {
            display: inline;
            margin: 0;
        }

        .action-links {
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .action-link {
            width: 34px;
            height: 34px;
            border-radius: 11px;
            display: inline-grid;
            place-items: center;
            border: 1px solid #d9e4d5;
            background: #fff;
            color: #244b35;
            cursor: pointer;
            font-family: inherit;
        }

        .action-link.danger {
            color: var(--admin-danger);
            border-color: #f0c8c0;
            background: #fff7f5;
        }

        .admin-pager {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 12px;
            color: #5f7368;
            font-size: 13px;
        }

        .admin-pager-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .admin-pager a,
        .admin-pager span.page-disabled {
            border: 1px solid #d9e4d5;
            background: #fff;
            color: #214b35;
            border-radius: 10px;
            padding: 7px 10px;
            font-weight: 700;
        }

        .admin-pager span.page-disabled {
            opacity: 0.45;
        }

        .quick-list {
            display: grid;
            gap: 8px;
        }

        .quick-item {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 10px;
            border: 1px solid #dce7d7;
            border-radius: 12px;
            background: #fff;
            font-size: 13px;
        }

        .quick-item strong {
            display: block;
            line-height: 1.35;
        }

        .quick-item span {
            color: #5f7368;
            font-size: 12px;
        }

        .hero-note {
            border: 1px dashed #c5d6c8;
            border-radius: 12px;
            padding: 10px 12px;
            color: #42594b;
            font-size: 13px;
            background: #f8fbf7;
        }

        @media (max-width: 1240px) {
            .filter-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('admin_content')
    @php
        $productsData = method_exists($products ?? null, 'getCollection')
            ? $products->getCollection()
            : collect($products ?? []);

        $hasFilters = request()->filled('q')
            || request()->filled('category')
            || request()->filled('status')
            || request()->filled('stock');
    @endphp

    @if(session('success'))
        <div class="admin-alert success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="admin-alert error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="admin-alert error">{{ $errors->first() }}</div>
    @endif

    <section class="page-head reveal" style="--delay: 0ms;">
        <div>
            <h1 class="page-title">Quản lý sản phẩm</h1>
            <p class="page-subtitle">Quản lý danh mục bán hàng, giá, hình ảnh, tồn kho và trạng thái hiển thị trên cửa hàng.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn btn-primary" href="{{ route('admin.products.create') }}">
                <i class="ri-add-line"></i>Thêm sản phẩm
            </a>
        </div>
    </section>

    <section class="stats-grid reveal" style="--delay: 40ms;">
        <article class="kpi-card">
            <div class="kpi-label">Tổng sản phẩm</div>
            <p class="kpi-value">{{ number_format($stats['total'] ?? 0) }}</p>
            <div class="kpi-foot"><strong>{{ number_format($stats['deleted'] ?? 0) }}</strong> sản phẩm trong thùng rác</div>
        </article>
        <article class="kpi-card">
            <div class="kpi-label">Đang hiển thị</div>
            <p class="kpi-value">{{ number_format($stats['active'] ?? 0) }}</p>
            <div class="kpi-foot"><strong>{{ number_format($stats['hidden'] ?? 0) }}</strong> sản phẩm đang ẩn</div>
        </article>
        <article class="kpi-card">
            <div class="kpi-label">Sắp hết hàng</div>
            <p class="kpi-value">{{ number_format($stats['low_stock'] ?? 0) }}</p>
            <div class="kpi-foot">Cần nhập hàng để tránh gián đoạn bán</div>
        </article>
        <article class="kpi-card">
            <div class="kpi-label">Hết hàng</div>
            <p class="kpi-value">{{ number_format($stats['out_of_stock'] ?? 0) }}</p>
            <div class="kpi-foot">Nên tạm ẩn hoặc cập nhật tồn kho</div>
        </article>
    </section>

    <section class="panel reveal" style="--delay: 80ms; margin-bottom: 14px;">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Bộ lọc và tìm kiếm</h2>
                <p class="panel-sub">Tìm theo tên, SKU hoặc slug; lọc theo danh mục, trạng thái và tồn kho.</p>
            </div>
        </div>

        <form class="filter-grid" method="get" action="{{ route('admin.products') }}">
            <div class="field">
                <label for="q">Tìm theo tên / SKU / slug</label>
                <input id="q" class="input" name="q" type="text" value="{{ request('q') }}" placeholder="Ví dụ: nho xanh, táo Envy...">
            </div>
            <div class="field">
                <label for="category">Danh mục</label>
                <select id="category" class="select" name="category">
                    <option value="">Tất cả danh mục</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="status">Trạng thái</label>
                <select id="status" class="select" name="status">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" @selected(request('status') === 'active')>Đang hiển thị</option>
                    <option value="hidden" @selected(request('status') === 'hidden')>Tạm ẩn</option>
                    <option value="trashed" @selected(request('status') === 'trashed')>Thùng rác</option>
                </select>
            </div>
            <div class="field">
                <label for="stock">Tồn kho</label>
                <select id="stock" class="select" name="stock">
                    <option value="">Tất cả tồn kho</option>
                    <option value="low" @selected(request('stock') === 'low')>Sắp hết hàng</option>
                    <option value="in_stock" @selected(request('stock') === 'in_stock')>Còn hàng</option>
                    <option value="out" @selected(request('stock') === 'out')>Hết hàng</option>
                </select>
            </div>
            <div class="field">
                <label for="per_page">Mỗi trang</label>
                <select id="per_page" class="select" name="per_page">
                    @foreach([15, 25, 50] as $size)
                        <option value="{{ $size }}" @selected((int) request('per_page', 15) === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-actions">
                <button class="btn btn-primary" type="submit"><i class="ri-search-line"></i>Lọc</button>
                @if($hasFilters)
                    <a class="btn btn-ghost" href="{{ route('admin.products') }}"><i class="ri-refresh-line"></i>Xóa lọc</a>
                @endif
            </div>
        </form>
    </section>

    <section class="grid-2" style="grid-template-columns: 1.7fr .8fr;">
        <article class="panel reveal" style="--delay: 140ms;">
            <div class="toolbar">
                <div class="toolbar-left">
                    <span class="panel-sub">Giá, ảnh và kho được cập nhật trong trang sửa của từng sản phẩm.</span>
                </div>
                @if(method_exists($products ?? null, 'total'))
                    <span class="tag">Đang hiện {{ $products->firstItem() ?? 0 }}-{{ $products->lastItem() ?? 0 }} / {{ number_format($products->total()) }}</span>
                @else
                    <span class="tag">Bảng sản phẩm</span>
                @endif
            </div>

            <div class="table-wrap">
                <table id="product-table">
                    <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Tồn kho</th>
                        <th>Trạng thái</th>
                        <th>Cập nhật</th>
                        <th>Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($productsData as $product)
                        @php
                            $stock = (int) $product->stock;
                            $threshold = max(5, (int) ($product->low_stock_threshold ?? 0));
                            $isOut = $stock <= 0;
                            $isLow = ! $isOut && $stock <= $threshold;
                            $displayPrice = (int) $product->orderable_price;
                            $basePrice = (int) ($product->price ?? 0);
                            $salePrice = (int) ($product->sale_price ?? 0);
                        @endphp
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <div class="product-thumb">
                                        @if($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}" alt="{{ $product->name }}">
                                        @else
                                            <i class="ri-image-line"></i>
                                        @endif
                                    </div>
                                    <div class="product-meta">
                                        <strong>{{ $product->name }}</strong>
                                        <span>{{ $product->sku ?: $product->slug }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $product->category->name ?? 'Chưa phân loại' }}</td>
                            <td>
                                <div class="price-line">
                                    <strong>{{ number_format($displayPrice) }} VND</strong>
                                    @if($salePrice > 0 && $basePrice > $salePrice)
                                        <del>{{ number_format($basePrice) }} VND</del>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <strong>{{ number_format($stock) }} {{ $product->unit ?? 'sp' }}</strong>
                                <div class="stock-note">
                                    @if($isOut)
                                        Hết hàng
                                    @elseif($isLow)
                                        Sắp hết hàng
                                    @else
                                        Còn hàng
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($product->trashed())
                                    <span class="status-pill cancelled">Đã xóa</span>
                                @else
                                    <form method="post" action="{{ route('admin.products.visibility', $product) }}" class="inline-form">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="status-pill status-button {{ $product->is_active ? 'done' : 'cancelled' }}" title="Bấm để đổi trạng thái hiển thị">
                                            {{ $product->is_active ? 'Đang hiện' : 'Tạm ẩn' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td>{{ \App\Support\LocalDateTime::format($product->updated_at) }}</td>
                            <td>
                                <div class="action-links">
                                    @if($product->trashed())
                                        <form method="post" action="{{ route('admin.products.restore', $product->id) }}" class="inline-form">
                                            @csrf
                                            @method('PATCH')
                                            <button class="action-link" type="submit" title="Khôi phục sản phẩm" aria-label="Khôi phục sản phẩm"><i class="ri-arrow-go-back-line"></i></button>
                                        </form>
                                    @else
                                        @if($product->is_active)
                                            <a class="action-link" href="{{ route('products.show', $product->slug) }}" target="_blank" rel="noopener" title="Xem ngoài storefront" aria-label="Xem ngoài storefront">
                                                <i class="ri-eye-line"></i>
                                            </a>
                                        @endif
                                        <a class="action-link" href="{{ route('admin.products.edit', $product) }}" title="Sửa sản phẩm" aria-label="Sửa sản phẩm">
                                            <i class="ri-edit-2-line"></i>
                                        </a>
                                        <form method="post" action="{{ route('admin.products.destroy', $product) }}" class="inline-form" onsubmit="return confirm('Đưa sản phẩm này vào thùng rác?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="action-link danger" type="submit" title="Xóa mềm sản phẩm" aria-label="Xóa mềm sản phẩm"><i class="ri-delete-bin-6-line"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="empty-box">
                                    <i class="ri-layout-grid-line"></i>
                                    <div>Không tìm thấy sản phẩm phù hợp với bộ lọc hiện tại.</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($products ?? null, 'hasPages') && $products->hasPages())
                <div class="admin-pager">
                    <div>Trang {{ $products->currentPage() }} / {{ $products->lastPage() }}</div>
                    <div class="admin-pager-actions">
                        @if($products->onFirstPage())
                            <span class="page-disabled">Trước</span>
                        @else
                            <a href="{{ $products->previousPageUrl() }}">Trước</a>
                        @endif

                        @if($products->hasMorePages())
                            <a href="{{ $products->nextPageUrl() }}">Sau</a>
                        @else
                            <span class="page-disabled">Sau</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>

        <article class="panel reveal" style="--delay: 210ms;">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Cần xử lý</h2>
                    <p class="panel-sub">Danh sách ưu tiên các sản phẩm sắp hết hàng để nhập kho kịp thời.</p>
                </div>
            </div>

            @if(($lowStockProducts ?? collect())->isNotEmpty())
                <div class="quick-list">
                    @foreach($lowStockProducts as $lowStockProduct)
                        <div class="quick-item">
                            <div>
                                <a href="{{ route('admin.products.edit', $lowStockProduct) }}"><strong>{{ $lowStockProduct->name }}</strong></a>
                                <span>{{ $lowStockProduct->category->name ?? 'Chưa phân loại' }}</span>
                            </div>
                            <strong>{{ number_format((int) $lowStockProduct->stock) }}</strong>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-box">
                    <i class="ri-checkbox-circle-line"></i>
                    <div>Chưa có sản phẩm sắp hết hàng.</div>
                </div>
            @endif
        </article>
    </section>
@endsection
