@php
    $coupon = $coupon ?? null;
    $type = old('type', optional($coupon)->type ?: \App\Models\Coupon::TYPE_PERCENT);
    $isActive = old('is_active', $coupon ? (bool) $coupon->is_active : true);
    $isPublic = old('is_public', $coupon ? (bool) $coupon->is_public : true);
@endphp

<div class="coupon-form-grid">
    <div class="coupon-field">
        <label>Tiêu đề</label>
        <input type="text" name="title" value="{{ old('title', optional($coupon)->title) }}" required>
    </div>

    <div class="coupon-field">
        <label>Mã voucher</label>
        <input type="text" name="code" value="{{ old('code', optional($coupon)->code) }}" placeholder="GIOQUA10" required>
    </div>

    <div class="coupon-field">
        <label>Loại</label>
        <select name="type" required>
            <option value="{{ \App\Models\Coupon::TYPE_PERCENT }}" {{ $type === \App\Models\Coupon::TYPE_PERCENT ? 'selected' : '' }}>Giảm phần trăm</option>
            <option value="{{ \App\Models\Coupon::TYPE_FIXED }}" {{ $type === \App\Models\Coupon::TYPE_FIXED ? 'selected' : '' }}>Giảm tiền</option>
            <option value="{{ \App\Models\Coupon::TYPE_GIFT }}" {{ $type === \App\Models\Coupon::TYPE_GIFT ? 'selected' : '' }}>Quà tặng</option>
        </select>
    </div>

    <div class="coupon-field">
        <label>Giá trị</label>
        <input type="number" name="value" min="0" value="{{ old('value', optional($coupon)->value ?? 0) }}" placeholder="10">
    </div>

    <div class="coupon-field">
        <label>Đơn tối thiểu</label>
        <input type="number" name="min_order_total" min="0" value="{{ old('min_order_total', optional($coupon)->min_order_total ?? 0) }}">
    </div>

    <div class="coupon-field">
        <label>Giảm tối đa</label>
        <input type="number" name="max_discount" min="0" value="{{ old('max_discount', optional($coupon)->max_discount) }}">
    </div>

    <div class="coupon-field">
        <label>Tổng lượt dùng</label>
        <input type="number" name="usage_limit" min="1" value="{{ old('usage_limit', optional($coupon)->usage_limit) }}">
    </div>

    <div class="coupon-field">
        <label>Lượt mỗi khách</label>
        <input type="number" name="per_customer_limit" min="1" value="{{ old('per_customer_limit', optional($coupon)->per_customer_limit) }}">
    </div>

    <div class="coupon-field">
        <label>Bắt đầu</label>
        <input type="datetime-local" name="starts_at" value="{{ old('starts_at', optional(optional($coupon)->starts_at)->format('Y-m-d\TH:i')) }}">
    </div>

    <div class="coupon-field">
        <label>Kết thúc</label>
        <input type="datetime-local" name="ends_at" value="{{ old('ends_at', optional(optional($coupon)->ends_at)->format('Y-m-d\TH:i')) }}">
    </div>

    <div class="coupon-field">
        <label>Trạng thái</label>
        <label class="coupon-check">
            <input type="checkbox" name="is_active" value="1" {{ $isActive ? 'checked' : '' }}>
            Đang bật
        </label>
    </div>

    <div class="coupon-field">
        <label>Phạm vi</label>
        <label class="coupon-check">
            <input type="checkbox" name="is_public" value="1" {{ $isPublic ? 'checked' : '' }}>
            Mã công khai
        </label>
    </div>

    <div class="coupon-field full">
        <label>Mô tả</label>
        <textarea name="description" placeholder="Điều kiện, quà tặng hoặc ghi chú nội bộ">{{ old('description', optional($coupon)->description) }}</textarea>
    </div>
</div>
