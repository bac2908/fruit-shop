@extends('layouts.admin')

@section('title', 'Quản lý voucher | FruitShop Admin')

@section('head')
    <style>
        .voucher-stats {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 14px;
        }

        .voucher-stat,
        .admin-alert,
        .coupon-form-box {
            border: 1px solid var(--admin-line);
            border-radius: var(--admin-radius-md);
            background: #fffdf7;
            padding: 14px;
        }

        .voucher-stat span {
            display: block;
            color: var(--admin-subtle);
            font-size: 12px;
            margin-bottom: 6px;
        }

        .voucher-stat strong {
            color: var(--admin-ink);
            font-family: 'Sora', sans-serif;
            font-size: 24px;
        }

        .admin-alert {
            margin-bottom: 14px;
            font-weight: 700;
        }

        .admin-alert.success {
            background: #edf8ef;
            border-color: #c8e6cf;
            color: #1d6b39;
        }

        .admin-alert.danger {
            background: #fff0ed;
            border-color: #f2c4ba;
            color: #b8402a;
        }

        .coupon-manage-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(280px, 0.7fr);
            gap: 14px;
            margin-bottom: 14px;
        }

        .coupon-form-grid,
        .coupon-edit-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }

        .coupon-form-box h3 {
            margin: 0 0 10px;
            font-family: 'Sora', sans-serif;
            font-size: 16px;
        }

        .coupon-field {
            display: grid;
            gap: 5px;
        }

        .coupon-field.full {
            grid-column: 1 / -1;
        }

        .coupon-field label {
            color: var(--admin-subtle);
            font-size: 12px;
            font-weight: 700;
        }

        .coupon-field input,
        .coupon-field select,
        .coupon-field textarea {
            width: 100%;
            min-height: 38px;
            border: 1px solid var(--admin-line);
            border-radius: 10px;
            background: #fff;
            color: var(--admin-ink);
            font-family: inherit;
            padding: 0 10px;
        }

        .coupon-field textarea {
            min-height: 72px;
            padding: 10px;
            resize: vertical;
        }

        .coupon-check {
            align-items: center;
            display: flex;
            gap: 8px;
            margin-top: 20px;
            color: var(--admin-ink);
            font-weight: 700;
        }

        .coupon-check input {
            width: auto;
            min-height: auto;
        }

        .coupon-actions,
        .row-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .coupon-actions {
            margin-top: 12px;
        }

        .row-actions form {
            margin: 0;
        }

        .btn-small {
            min-height: 32px;
            padding: 0 10px;
            font-size: 12px;
        }

        .coupon-edit-row td {
            background: #fbfaf3;
            border-top: 0;
        }

        .coupon-edit-box {
            padding: 12px 0;
        }

        .coupon-edit-box summary {
            cursor: pointer;
            color: var(--admin-primary);
            font-weight: 800;
            margin-bottom: 10px;
        }

        .coupon-code {
            color: var(--admin-primary);
            font-family: 'Sora', sans-serif;
        }

        .coupon-muted {
            color: var(--admin-subtle);
            font-size: 12px;
        }

        @media (max-width: 1100px) {
            .voucher-stats,
            .coupon-manage-grid,
            .coupon-form-grid,
            .coupon-edit-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 720px) {
            .voucher-stats,
            .coupon-manage-grid,
            .coupon-form-grid,
            .coupon-edit-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('admin_content')
    <section class="page-head reveal" style="--delay: 0ms;">
        <div>
            <h1 class="page-title">Quản lý voucher</h1>
            <p class="page-subtitle">Tạo mã công khai, giới hạn lượt dùng và gán voucher cá nhân cho khách hàng.</p>
        </div>
    </section>

    @if(session('success'))
        <div class="admin-alert success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="admin-alert danger">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <section class="voucher-stats reveal" style="--delay: 60ms;">
        <div class="voucher-stat">
            <span>Tổng voucher</span>
            <strong>{{ number_format($coupons->count()) }}</strong>
        </div>
        <div class="voucher-stat">
            <span>Đang bật</span>
            <strong>{{ number_format($activeCoupons ?? 0) }}</strong>
        </div>
        <div class="voucher-stat">
            <span>Lượt đã dùng</span>
            <strong>{{ number_format($usedCount ?? 0) }}</strong>
        </div>
        <div class="voucher-stat">
            <span>Voucher cá nhân</span>
            <strong>{{ number_format($personalVoucherCount ?? 0) }}</strong>
        </div>
    </section>

    <section class="coupon-manage-grid reveal" style="--delay: 100ms;">
        <div class="coupon-form-box">
            <h3>Tạo voucher mới</h3>
            <form method="post" action="{{ route('admin.coupons.store') }}">
                @csrf
                @include('admin.partials.coupon-fields', ['coupon' => null, 'prefix' => 'create'])
                <div class="coupon-actions">
                    <button type="submit" class="btn btn-primary"><i class="ri-ticket-2-line"></i>Tạo voucher</button>
                </div>
            </form>
        </div>

        <div class="coupon-form-box">
            <h3>Gán voucher cá nhân</h3>
            <form method="post" action="{{ route('admin.coupons.assign') }}">
                @csrf
                <div class="coupon-form-grid" style="grid-template-columns:1fr;">
                    <div class="coupon-field">
                        <label>Voucher</label>
                        <select name="coupon_id" required>
                            <option value="">Chọn mã</option>
                            @foreach($coupons as $coupon)
                                <option value="{{ $coupon->id }}" {{ (string) old('coupon_id') === (string) $coupon->id ? 'selected' : '' }}>
                                    {{ $coupon->code }} - {{ $coupon->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="coupon-field">
                        <label>Email khách hàng</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="khach@example.com" required>
                    </div>
                    <div class="coupon-field">
                        <label>Hạn riêng</label>
                        <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}">
                    </div>
                </div>
                <div class="coupon-actions">
                    <button type="submit" class="btn btn-primary"><i class="ri-user-heart-line"></i>Gán voucher</button>
                </div>
            </form>
        </div>
    </section>

    <section class="panel reveal" style="--delay: 140ms;">
        <div class="panel-head">
            <div>
                <h2 class="panel-title">Danh sách voucher</h2>
                <p class="panel-sub">Mã đang được dùng ở giỏ hàng, checkout và hồ sơ khách hàng.</p>
            </div>
            <span class="tag">MySQL</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Code</th>
                    <th>Tiêu đề</th>
                    <th>Ưu đãi</th>
                    <th>Điều kiện</th>
                    <th>Lượt dùng</th>
                    <th>Cá nhân</th>
                    <th>Phạm vi</th>
                    <th>Hiệu lực</th>
                    <th>Trạng thái</th>
                    <th>Thao tác</th>
                </tr>
                </thead>
                <tbody>
                @forelse($coupons as $coupon)
                    <tr>
                        <td><strong class="coupon-code">{{ $coupon->code }}</strong></td>
                        <td>
                            {{ $coupon->title }}
                            @if($coupon->description)
                                <div class="coupon-muted">{{ \Illuminate\Support\Str::limit($coupon->description, 72) }}</div>
                            @endif
                        </td>
                        <td>{{ $coupon->discount_label }}</td>
                        <td>{{ $coupon->condition_label }}</td>
                        <td>
                            {{ number_format((int) $coupon->used_count) }}
                            @if($coupon->usage_limit)
                                / {{ number_format((int) $coupon->usage_limit) }}
                            @endif
                            <div class="coupon-muted">Theo khách: {{ $coupon->per_customer_limit ?: 'Không giới hạn' }}</div>
                        </td>
                        <td>{{ number_format((int) $coupon->user_vouchers_count) }}</td>
                        <td>{{ $coupon->is_public ? 'Công khai' : 'Gán riêng' }}</td>
                        <td>
                            {{ optional($coupon->starts_at)->format('d/m/Y H:i') ?: 'Bắt đầu ngay' }}
                            <div class="coupon-muted">{{ $coupon->expiry_label }}</div>
                        </td>
                        <td>
                            <span class="status-pill {{ $coupon->isValid() ? 'done' : ($coupon->is_active ? 'pending' : 'cancelled') }}">
                                {{ $coupon->isValid() ? 'VALID' : ($coupon->is_active ? 'WAIT/EXPIRED' : 'OFF') }}
                            </span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <form method="post" action="{{ route('admin.coupons.toggle', $coupon) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-ghost btn-small">{{ $coupon->is_active ? 'Tắt' : 'Bật' }}</button>
                                </form>
                                <form method="post" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Xóa mềm voucher này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-ghost btn-small">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr class="coupon-edit-row">
                        <td colspan="10">
                            <details class="coupon-edit-box">
                                <summary>Chỉnh sửa {{ $coupon->code }}</summary>
                                <form method="post" action="{{ route('admin.coupons.update', $coupon) }}">
                                    @csrf
                                    @method('PUT')
                                    @include('admin.partials.coupon-fields', ['coupon' => $coupon, 'prefix' => 'edit_' . $coupon->id])
                                    <div class="coupon-actions">
                                        <button type="submit" class="btn btn-primary"><i class="ri-save-3-line"></i>Lưu thay đổi</button>
                                    </div>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            <div class="empty-box">
                                <i class="ri-coupon-3-line"></i>
                                <div>Chưa có voucher nào. Hãy tạo mã đầu tiên cho hệ thống.</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
