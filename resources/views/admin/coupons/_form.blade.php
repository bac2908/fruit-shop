@php
    $type = old('type', $coupon->type ?: \App\Models\Coupon::TYPE_PERCENT);
    $hasUsage = (int) ($coupon->usages_count ?? $coupon->used_count ?? 0) > 0;
@endphp

@if($hasUsage)
    <div class="coupon-lock-note"><strong>Voucher đã phát sinh lượt dùng.</strong> Mã, loại, giá trị, điều kiện, phạm vi và sản phẩm quà tặng đã được khóa để bảo toàn lịch sử đơn hàng.</div>
@endif

<section class="coupon-form-section">
    <h2>Thông tin chương trình</h2>
    <p>Tên hiển thị rõ lợi ích; mã ngắn, dễ nhập và không gây nhầm lẫn.</p>
    <div class="coupon-form-grid">
        <div class="coupon-field">
            <label for="title">Tên voucher *</label>
            <input class="coupon-input" id="title" name="title" value="{{ old('title', $coupon->title) }}" maxlength="160" required>
        </div>
        <div class="coupon-field">
            <label for="code">Mã voucher *</label>
            <input class="coupon-input" id="code" name="code" value="{{ old('code', $coupon->code) }}" maxlength="80" placeholder="TRAICAY10" {{ $hasUsage ? 'readonly' : '' }} required>
            <span class="coupon-help">Chỉ dùng chữ in hoa, số, dấu gạch ngang hoặc gạch dưới.</span>
        </div>
        <div class="coupon-field full">
            <label for="description">Mô tả điều kiện</label>
            <textarea class="coupon-textarea" id="description" name="description" maxlength="1000" placeholder="Nêu ngắn gọn quyền lợi và điều kiện sử dụng">{{ old('description', $coupon->description) }}</textarea>
        </div>
    </div>
</section>

<section class="coupon-form-section">
    <h2>Quyền lợi và điều kiện</h2>
    <p>Hệ thống chỉ cho áp dụng một voucher trên mỗi đơn và tự chọn ưu đãi phù hợp nhất.</p>
    <div class="coupon-form-grid">
        <div class="coupon-field">
            <label for="type">Loại ưu đãi *</label>
            <select class="coupon-select" id="type" name="type" {{ $hasUsage ? 'disabled' : '' }} required>
                <option value="percent" @selected($type === 'percent')>Giảm theo phần trăm</option>
                <option value="fixed" @selected($type === 'fixed')>Giảm số tiền cố định</option>
                <option value="gift" @selected($type === 'gift')>Tặng sản phẩm</option>
            </select>
            @if($hasUsage)<input type="hidden" name="type" value="{{ $coupon->type }}">@endif
        </div>
        <div class="coupon-field coupon-discount-field">
            <label for="value">{{ $type === 'percent' ? 'Phần trăm giảm (%)' : 'Số tiền giảm (VND)' }} *</label>
            <input class="coupon-input" id="value" type="number" name="value" min="0" max="1000000000" value="{{ old('value', $coupon->value) }}" {{ $hasUsage ? 'readonly' : '' }}>
        </div>
        <div class="coupon-field coupon-gift-field">
            <label for="gift_product_id">Sản phẩm tặng *</label>
            <select class="coupon-select" id="gift_product_id" name="gift_product_id" {{ $hasUsage ? 'disabled' : '' }}>
                <option value="">Chọn sản phẩm trong kho</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected((string) old('gift_product_id', $coupon->gift_product_id) === (string) $product->id)>
                        {{ $product->name }} · {{ $product->sku }} · tồn {{ number_format((int) $product->stock) }}
                    </option>
                @endforeach
            </select>
            @if($hasUsage)<input type="hidden" name="gift_product_id" value="{{ $coupon->gift_product_id }}">@endif
        </div>
        <div class="coupon-field coupon-gift-field">
            <label for="gift_quantity">Số lượng tặng *</label>
            <input class="coupon-input" id="gift_quantity" type="number" name="gift_quantity" min="1" max="100" value="{{ old('gift_quantity', $coupon->gift_quantity ?: 1) }}" {{ $hasUsage ? 'readonly' : '' }}>
            <span class="coupon-help">Giá trị ước tính được lấy tự động từ giá bán hiện tại.</span>
        </div>
        <div class="coupon-field">
            <label for="min_order_total">Giá trị đơn tối thiểu (VND)</label>
            <input class="coupon-input" id="min_order_total" type="number" name="min_order_total" min="0" max="1000000000" value="{{ old('min_order_total', $coupon->min_order_total ?: 0) }}" {{ $hasUsage ? 'readonly' : '' }}>
        </div>
        <div class="coupon-field coupon-percent-field">
            <label for="max_discount">Mức giảm tối đa (VND)</label>
            <input class="coupon-input" id="max_discount" type="number" name="max_discount" min="0" max="1000000000" value="{{ old('max_discount', $coupon->max_discount) }}" {{ $hasUsage ? 'readonly' : '' }}>
        </div>
        <div class="coupon-field">
            <label for="usage_limit">Tổng lượt sử dụng</label>
            <input class="coupon-input" id="usage_limit" type="number" name="usage_limit" min="1" max="1000000" value="{{ old('usage_limit', $coupon->usage_limit) }}">
            <span class="coupon-help">Để trống nếu không giới hạn. Không được thấp hơn {{ number_format((int) $coupon->used_count) }} lượt đã dùng.</span>
        </div>
        <div class="coupon-field">
            <label for="per_customer_limit">Lượt tối đa mỗi khách</label>
            <input class="coupon-input" id="per_customer_limit" type="number" name="per_customer_limit" min="1" max="1000" value="{{ old('per_customer_limit', $coupon->per_customer_limit) }}" {{ $hasUsage ? 'readonly' : '' }}>
        </div>
    </div>
</section>

<section class="coupon-form-section">
    <h2>Thời gian và phạm vi</h2>
    <p>Voucher công khai có thể được nhập trực tiếp; voucher gán riêng chỉ dùng được trong kho voucher của khách.</p>
    <div class="coupon-form-grid">
        <div class="coupon-field">
            <label for="starts_at">Bắt đầu</label>
            <input class="coupon-input" id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', \App\Support\LocalDateTime::format($coupon->starts_at, 'Y-m-d\TH:i', '')) }}" {{ $hasUsage ? 'readonly' : '' }}>
        </div>
        <div class="coupon-field">
            <label for="ends_at">Kết thúc</label>
            <input class="coupon-input" id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', \App\Support\LocalDateTime::format($coupon->ends_at, 'Y-m-d\TH:i', '')) }}">
        </div>
        <div class="coupon-field full">
            <div class="coupon-checks">
                <label class="coupon-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', (bool) $coupon->is_active))> Bật voucher sau khi lưu</label>
                <label class="coupon-check"><input type="checkbox" name="is_public" value="1" @checked(old('is_public', (bool) $coupon->is_public)) {{ $hasUsage ? 'disabled' : '' }}> Cho phép mọi khách đủ điều kiện sử dụng</label>
                @if($hasUsage)<input type="hidden" name="is_public" value="{{ (int) $coupon->is_public }}">@endif
            </div>
        </div>
    </div>
</section>

<div class="coupon-actions">
    <button class="btn btn-primary" type="submit"><i class="ri-save-3-line"></i>{{ $submitLabel }}</button>
    <a class="btn btn-ghost" href="{{ $coupon->exists ? route('admin.coupons.show', $coupon) : route('admin.coupons') }}">Hủy</a>
</div>
