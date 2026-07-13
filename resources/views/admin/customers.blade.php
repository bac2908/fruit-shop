@extends('layouts.admin')

@section('title', 'Quan ly khach hang | FruitShop Admin')

@section('head')
    <style>
        .segment-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 12px;
        }

        .segment {
            border: 1px solid #dce6db;
            border-radius: 12px;
            padding: 12px;
            background: #fff;
        }

        .segment small {
            color: #63776b;
            display: block;
            margin-bottom: 6px;
        }

        .segment strong {
            display: block;
            font-size: 23px;
            font-family: 'Sora', sans-serif;
        }

        .segment p {
            margin: 7px 0 0;
            font-size: 12px;
            color: #688073;
        }

        @media (max-width: 1100px) {
            .segment-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 768px) {
            .segment-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('admin_content')
    <section class="page-head reveal" style="--delay: 0ms;">
        <div>
            <h1 class="page-title">Quan ly khach hang</h1>
            <p class="page-subtitle">Nhom khach hang theo hanh vi mua va gia tri vong doi.</p>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap;">
            <button class="btn btn-ghost"><i class="ri-file-chart-line"></i>Xuat CRM list</button>
            <button class="btn btn-primary"><i class="ri-mail-send-line"></i>Tao segment campaign</button>
        </div>
    </section>

    <section class="panel reveal" style="--delay: 90ms; margin-bottom: 14px;">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Phan khuc khach hang</h2>
                <p class="panel-sub">Theo doi su thay doi cua nhom VIP, nguoi mua moi va nhom nguy co roi bo.</p>
            </div>
        </div>

        <div class="segment-grid">
            <div class="segment">
                <small>Khach hang moi</small>
                <strong>{{ $customerSummary['new'] ?? '--' }}</strong>
                <p>Dang can kich hoat don dau tien</p>
            </div>
            <div class="segment">
                <small>Khach quay lai</small>
                <strong>{{ $customerSummary['repeat'] ?? '--' }}</strong>
                <p>Dat tu 2 don tro len</p>
            </div>
            <div class="segment">
                <small>VIP</small>
                <strong>{{ $customerSummary['vip'] ?? '--' }}</strong>
                <p>Gia tri mua cao va tan suat tot</p>
            </div>
            <div class="segment">
                <small>Nguy co roi bo</small>
                <strong>{{ $customerSummary['churn_risk'] ?? '--' }}</strong>
                <p>Khong mua lai trong 45 ngay</p>
            </div>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Khach hang</th>
                    <th>Email/Phone</th>
                    <th>Don gan nhat</th>
                    <th>Tong chi tieu</th>
                    <th>So don</th>
                    <th>Tag</th>
                </tr>
                </thead>
                <tbody>
                @forelse(($customers ?? []) as $customer)
                    <tr>
                        <td><strong>{{ $customer->name }}</strong></td>
                        <td>{{ $customer->email ?? $customer->phone ?? '-' }}</td>
                        <td>{{ \App\Support\LocalDateTime::format($customer->last_order_at ?? null, 'd/m/Y') }}</td>
                        <td>{{ isset($customer->lifetime_value) ? number_format((int) $customer->lifetime_value) . ' VND' : '--' }}</td>
                        <td>{{ isset($customer->orders_count) ? (int) $customer->orders_count : '--' }}</td>
                        <td><span class="tag">{{ $customer->segment ?? 'segment' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty-box">
                                <i class="ri-user-star-line"></i>
                                <div>Bang khach hang dang cho map du lieu that tu bang users + orders.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
