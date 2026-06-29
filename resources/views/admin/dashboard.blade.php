@extends('layouts.admin')

@section('title', 'Tong quan Admin | FruitShop')

@section('head')
    <style>
        .hero-note {
            border: 1px dashed #c5d6c8;
            border-radius: 12px;
            padding: 10px 12px;
            color: #42594b;
            font-size: 13px;
            background: #f8fbf7;
            margin-bottom: 14px;
        }

        .chart-shell {
            border-radius: 14px;
            border: 1px solid #e3ebdf;
            background: linear-gradient(180deg, #ffffff 0%, #f8fcf7 100%);
            padding: 12px;
        }

        .chart-bars {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
            align-items: end;
            height: 210px;
        }

        .chart-bar {
            text-align: center;
        }

        .chart-bar .bar {
            width: 100%;
            border-radius: 12px 12px 6px 6px;
            background: linear-gradient(180deg, #66c68a 0%, #1f7a4a 100%);
            min-height: 10px;
            transition: transform 0.24s ease;
        }

        .chart-bar:hover .bar {
            transform: translateY(-4px);
        }

        .chart-bar .label {
            display: block;
            margin-top: 8px;
            font-size: 11px;
            color: #5f7266;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .mini-card {
            border: 1px solid #dce7da;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }

        .mini-card small {
            color: #5e7366;
            font-size: 12px;
        }

        .mini-card strong {
            margin-top: 6px;
            font-size: 20px;
            font-family: 'Sora', sans-serif;
            display: block;
        }

        .list-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px dashed #e4ece0;
            font-size: 13px;
        }

        .list-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .list-item strong {
            font-size: 14px;
        }

        .list-item span {
            color: #65786d;
        }
    </style>
@endsection

@section('admin_content')
    @php
        $weeklyRevenue = $weeklyRevenue ?? [32, 46, 38, 57, 61, 52, 69];
        $funnel = $funnel ?? [
            ['label' => 'Gio hang tao moi', 'value' => 100],
            ['label' => 'Checkout bat dau', 'value' => 71],
            ['label' => 'Thanh toan thanh cong', 'value' => 53],
            ['label' => 'Don da giao', 'value' => 45],
        ];
        $recentOrdersData = collect($recentOrders ?? []);
        $lowStockProductsData = collect($lowStockProducts ?? []);
    @endphp

    <section class="page-head reveal" style="--delay: 0ms;">
        <div>
            <h1 class="page-title">Tong quan van hanh</h1>
            <p class="page-subtitle">Bang dieu khien trung tam cho van hanh thuong mai dien tu trong ngay.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button class="btn btn-ghost"><i class="ri-download-2-line"></i>Xuat bao cao</button>
            <button class="btn btn-primary"><i class="ri-add-circle-line"></i>Tao chien dich moi</button>
        </div>
    </section>

    <div class="hero-note reveal" style="--delay: 60ms;">
        Dashboard dang doc du lieu that tu MySQL. Don moi co ton kho se duoc he thong tu xac nhan, admin chi can theo doi va xu ly ngoai le.
    </div>

    <section class="stats-grid reveal" style="--delay: 120ms;">
        <article class="kpi-card">
            <div class="kpi-label">Don hang hom nay</div>
            <p class="kpi-value">{{ isset($metrics['orders_today']) ? number_format((int) $metrics['orders_today']) : '--' }}</p>
            <div class="kpi-foot"><strong>+12.5%</strong> so voi hom qua</div>
        </article>
        <article class="kpi-card">
            <div class="kpi-label">Doanh thu 30 ngay</div>
            <p class="kpi-value">{{ isset($metrics['monthly_revenue']) ? number_format((int) $metrics['monthly_revenue']) . ' VND' : '--' }}</p>
            <div class="kpi-foot"><strong>On track</strong> muc tieu thang nay</div>
        </article>
        <article class="kpi-card">
            <div class="kpi-label">Khach hang moi</div>
            <p class="kpi-value">{{ isset($metrics['new_customers']) ? number_format((int) $metrics['new_customers']) : '--' }}</p>
            <div class="kpi-foot"><strong>CRM</strong> dang tang truong tot</div>
        </article>
        <article class="kpi-card">
            <div class="kpi-label">Ty le chuyen doi</div>
            <p class="kpi-value">{{ isset($metrics['conversion_rate']) ? $metrics['conversion_rate'] . '%' : '--' }}</p>
            <div class="kpi-foot"><strong>A/B test</strong> dang hoat dong</div>
        </article>
    </section>

    <section class="grid-2">
        <article class="panel reveal" style="--delay: 180ms;">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Do thi doanh thu 7 ngay</h2>
                    <p class="panel-sub">Theo doi xu huong va phan bo doanh thu theo ngay.</p>
                </div>
                <span class="tag">MySQL realtime</span>
            </div>

            <div class="chart-shell">
                <div class="chart-bars">
                    @foreach($weeklyRevenue as $index => $height)
                        @php
                            $labels = ['T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'CN'];
                            $barHeight = max(12, intval($height));
                        @endphp
                        <div class="chart-bar">
                            <div class="bar" data-height="{{ $barHeight }}"></div>
                            <span class="label">{{ $labels[$index] ?? 'N' . ($index + 1) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </article>

        <article class="panel reveal" style="--delay: 240ms;">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Funnel don hang</h2>
                    <p class="panel-sub">Xac dinh diem roi trong hanh trinh mua hang.</p>
                </div>
            </div>

            <div class="stack">
                @foreach($funnel as $item)
                    <div class="metric-line">
                        <div class="metric-line-head">
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ $item['value'] }}%</strong>
                        </div>
                        <div class="progress">
                            <span data-width="{{ intval($item['value']) }}"></span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid-3" style="margin-top: 14px; margin-bottom: 0;">
                <div class="mini-card">
                    <small>AOV du kien</small>
                    <strong>{{ isset($metrics['aov']) ? number_format((int) $metrics['aov']) . ' VND' : '--' }}</strong>
                </div>
                <div class="mini-card">
                    <small>Hoan hang</small>
                    <strong>{{ isset($metrics['return_rate']) ? $metrics['return_rate'] . '%' : '--' }}</strong>
                </div>
                <div class="mini-card">
                    <small>Thanh toan COD</small>
                    <strong>{{ isset($metrics['cod_share']) ? $metrics['cod_share'] . '%' : '--' }}</strong>
                </div>
            </div>
        </article>
    </section>

    <section class="grid-2">
        <article class="panel reveal" style="--delay: 300ms;">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Don hang moi nhat</h2>
                    <p class="panel-sub">Danh sach don can xu ly ngay trong ca lam viec.</p>
                </div>
                <a href="{{ route('admin.orders') }}" class="tag">Mo trang don hang</a>
            </div>

            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Ma don</th>
                        <th>Khach hang</th>
                        <th>Tong tien</th>
                        <th>Trang thai</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($recentOrdersData->isNotEmpty())
                        @foreach($recentOrdersData as $order)
                            <tr>
                                <td><strong>{{ $order->code }}</strong></td>
                                <td>{{ $order->customer_name }}</td>
                                <td>{{ number_format((int) $order->total) }} VND</td>
                                <td><span class="status-pill {{ $order->status }}">{{ strtoupper($order->status) }}</span></td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4">
                                <div class="empty-box">
                                    <i class="ri-database-2-line"></i>
                                    <div>Chua co don hang nao trong database.</div>
                                </div>
                            </td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </article>

        <article class="panel reveal" style="--delay: 360ms;">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">Canh bao ton kho</h2>
                    <p class="panel-sub">San pham can nhap them de tranh out-of-stock.</p>
                </div>
                <a href="{{ route('admin.products') }}" class="tag">Quan ly ton kho</a>
            </div>

            <div class="stack">
                @if($lowStockProductsData->isNotEmpty())
                    @foreach($lowStockProductsData as $product)
                        <div class="list-item">
                            <div>
                                <strong>{{ $product->name }}</strong>
                                <span>{{ $product->category->name ?? 'Chua phan loai' }}</span>
                            </div>
                            <div class="status-pill pending">{{ (int) $product->stock }} {{ $product->unit ?? 'sp' }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="empty-box">
                        <i class="ri-box-3-line"></i>
                        <div>Chua co san pham nao cham nguong ton kho thap.</div>
                    </div>
                @endif
            </div>

            <div style="margin-top: 12px;">
                <div class="chip-group">
                    <span class="chip">Auto reorder threshold</span>
                    <span class="chip">Supplier SLA monitor</span>
                    <span class="chip">ABC inventory matrix</span>
                </div>
            </div>
        </article>
    </section>
@endsection

@section('scripts')
    @parent
    <script>
        (function () {
            document.querySelectorAll('.chart-bar .bar[data-height]').forEach(function (bar) {
                var value = parseInt(bar.getAttribute('data-height') || '0', 10);
                bar.style.height = Math.max(12, value) + '%';
            });

            document.querySelectorAll('.progress > span[data-width]').forEach(function (el) {
                var value = parseInt(el.getAttribute('data-width') || '0', 10);
                el.style.width = Math.max(0, Math.min(100, value)) + '%';
            });
        })();
    </script>
@endsection
