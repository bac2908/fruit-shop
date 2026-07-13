@props([
    'options' => null,
    'selected' => null,
    'selectionMode' => null,
    'redirect' => 'cart',
])

@php
    $options = $options instanceof \Illuminate\Support\Collection ? $options : collect($options ?? []);
    $eligibleCount = $options->where('eligible', true)->count();
    $selectedCode = optional($selected)->code;
@endphp

<div class="voucher-picker">
    @if($selected)
        <div class="voucher-picker-current">
            <div class="voucher-picker-current-icon"><i class="fa fa-ticket" aria-hidden="true"></i></div>
            <div>
                <span>{{ $selectionMode === 'auto' ? 'Đã tự động chọn ưu đãi tốt nhất' : 'Voucher đang áp dụng' }}</span>
                <strong>{{ $selected->code }} · {{ $selected->benefit_label }}</strong>
            </div>
            <form method="post" action="{{ route('cart.coupon.remove') }}">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ $redirect }}">
                <button type="submit" title="Bỏ voucher" aria-label="Bỏ voucher"><i class="fa fa-times" aria-hidden="true"></i></button>
            </form>
        </div>
    @endif

    @if($options->isNotEmpty())
        <details class="voucher-picker-details" {{ !$selected ? 'open' : '' }}>
            <summary>
                <span><i class="fa fa-ticket" aria-hidden="true"></i> Chọn hoặc đổi voucher</span>
                <small>{{ $eligibleCount }} mã dùng được</small>
            </summary>

            <div class="voucher-picker-list">
                @foreach($options as $option)
                    @php
                        $coupon = $option['coupon'];
                        $isSelected = $selectedCode === $coupon->code;
                    @endphp
                    <div class="voucher-choice {{ $option['eligible'] ? 'is-eligible' : 'is-disabled' }} {{ $isSelected ? 'is-selected' : '' }}">
                        <div class="voucher-choice-main">
                            <div class="voucher-choice-title">
                                <strong>{{ $coupon->code }}</strong>
                                @if($option['recommended'])<span>Đề xuất tốt nhất</span>@endif
                            </div>
                            <p>{{ $coupon->benefit_label }}</p>
                            <small>{{ $coupon->condition_label }} · HSD {{ \App\Support\LocalDateTime::format($coupon->ends_at, 'd/m/Y', 'không giới hạn') }}</small>
                            @if(!$option['eligible'])
                                <em>{{ $option['reason'] }}</em>
                            @elseif($coupon->type === \App\Models\Coupon::TYPE_GIFT && $option['estimated_value'] > 0)
                                <em>Giá trị quà tặng dự kiến {{ number_format($option['estimated_value'], 0, ',', '.') }}đ</em>
                            @else
                                <em>Tiết kiệm {{ number_format($option['estimated_value'], 0, ',', '.') }}đ cho giỏ hàng này</em>
                            @endif
                        </div>

                        <div class="voucher-choice-action">
                            @if($isSelected)
                                <span class="voucher-selected-label"><i class="fa fa-check" aria-hidden="true"></i> Đang dùng</span>
                            @elseif($option['eligible'])
                                <form method="post" action="{{ route('cart.coupon.use', $coupon) }}">
                                    @csrf
                                    <input type="hidden" name="redirect_to" value="{{ $redirect }}">
                                    <button type="submit">Chọn</button>
                                </form>
                            @else
                                <span class="voucher-locked"><i class="fa fa-lock" aria-hidden="true"></i></span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="voucher-picker-tools">
                <form method="post" action="{{ route('cart.coupon.auto') }}">
                    @csrf
                    <input type="hidden" name="redirect_to" value="{{ $redirect }}">
                    <button type="submit" class="voucher-auto-button">
                        <i class="fa fa-magic" aria-hidden="true"></i>
                        Tự chọn mã tốt nhất
                    </button>
                </form>

                <details class="voucher-manual-entry">
                    <summary>Nhập mã khác</summary>
                    <form method="post" action="{{ route('cart.coupon.apply') }}">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ $redirect }}">
                        <input type="text" name="code" maxlength="80" placeholder="Nhập mã voucher" value="{{ old('code') }}" autocomplete="off">
                        <button type="submit">Áp dụng</button>
                    </form>
                </details>
            </div>
        </details>
    @else
        <div class="voucher-picker-empty">
            <i class="fa fa-ticket" aria-hidden="true"></i>
            Chưa có voucher thành viên phù hợp.
        </div>
    @endif

    <p class="voucher-picker-rule"><i class="fa fa-info-circle" aria-hidden="true"></i> Mỗi đơn hàng sử dụng tối đa một voucher.</p>
</div>

@once
    @push('styles')
    <style>
        .voucher-picker { display: grid; gap: 10px; }
        .voucher-picker-current { align-items: center; background: #f4faed; border: 1px solid #cfe3bd; border-radius: 7px; display: grid; gap: 10px; grid-template-columns: 34px minmax(0, 1fr) 30px; padding: 10px; }
        .voucher-picker-current-icon { align-items: center; background: #deefce; border-radius: 50%; color: #599524; display: flex; height: 34px; justify-content: center; width: 34px; }
        .voucher-picker-current span { color: #6c7865; display: block; font-size: 11px; }
        .voucher-picker-current strong { color: #315c16; display: block; font-size: 13px; line-height: 1.4; margin-top: 2px; overflow-wrap: anywhere; }
        .voucher-picker-current form { margin: 0; }
        .voucher-picker-current form button { align-items: center; background: transparent; border: 0; color: #7f8a78; display: flex; height: 30px; justify-content: center; padding: 0; width: 30px; }
        .voucher-picker-details { border-top: 1px solid #e3e9df; padding-top: 8px; }
        .voucher-picker-details > summary { align-items: center; color: #3e671e; cursor: pointer; display: flex; font-size: 13px; font-weight: 800; justify-content: space-between; list-style: none; padding: 7px 2px; }
        .voucher-picker-details > summary::-webkit-details-marker { display: none; }
        .voucher-picker-details > summary small { color: #7a8574; font-size: 11px; font-weight: 600; }
        .voucher-picker-list { border-top: 1px solid #edf1e9; }
        .voucher-choice { align-items: center; border-bottom: 1px solid #edf1e9; display: grid; gap: 10px; grid-template-columns: minmax(0, 1fr) auto; padding: 12px 2px; }
        .voucher-choice.is-selected { background: #f8fcf4; box-shadow: inset 3px 0 #75b72c; padding-left: 10px; }
        .voucher-choice.is-disabled { opacity: .64; }
        .voucher-choice-title { align-items: center; display: flex; flex-wrap: wrap; gap: 7px; }
        .voucher-choice-title strong { color: #2e4f18; font-size: 13px; }
        .voucher-choice-title span { background: #ecf6e2; border-radius: 999px; color: #4f8420; font-size: 9px; font-weight: 800; padding: 2px 7px; }
        .voucher-choice-main p { color: #34422f; font-size: 12px; font-weight: 700; line-height: 1.4; margin: 3px 0; }
        .voucher-choice-main small { color: #7a8476; display: block; font-size: 10px; line-height: 1.4; }
        .voucher-choice-main em { color: #c66a14; display: block; font-size: 10px; font-style: normal; line-height: 1.4; margin-top: 4px; }
        .voucher-choice-action form { margin: 0; }
        .voucher-choice-action button { background: #6ba92c; border: 0; border-radius: 5px; color: #fff; font-size: 11px; font-weight: 800; min-height: 32px; padding: 0 13px; }
        .voucher-selected-label { color: #548b23; font-size: 11px; font-weight: 800; white-space: nowrap; }
        .voucher-locked { color: #8e9789; display: block; padding: 8px; }
        .voucher-picker-tools { align-items: flex-start; display: flex; gap: 10px; justify-content: space-between; padding-top: 10px; }
        .voucher-picker-tools form { display: block; margin: 0; }
        .voucher-auto-button { background: transparent; border: 0; color: #4f8124; font-size: 11px; font-weight: 800; padding: 7px 0; }
        .voucher-manual-entry { min-width: 112px; }
        .voucher-manual-entry > summary { color: #687363; cursor: pointer; font-size: 11px; font-weight: 700; list-style: none; padding: 7px 0; text-align: right; }
        .voucher-manual-entry > summary::-webkit-details-marker { display: none; }
        .voucher-manual-entry form { display: grid; gap: 6px; grid-template-columns: minmax(120px, 1fr) auto; margin-top: 6px; }
        .voucher-manual-entry input { border: 1px solid #d7dfd1; border-radius: 5px; font-size: 12px; height: 34px; min-width: 0; padding: 0 9px; }
        .voucher-manual-entry button { background: #66745e; border: 0; border-radius: 5px; color: #fff; font-size: 11px; font-weight: 800; padding: 0 11px; }
        .voucher-picker-empty { color: #788174; font-size: 12px; padding: 10px 0; }
        .voucher-picker-rule { color: #8a9285; font-size: 10px; line-height: 1.4; margin: 0; }
        @media (max-width: 575px) {
            .voucher-picker-tools { flex-direction: column; }
            .voucher-manual-entry { width: 100%; }
            .voucher-manual-entry > summary { text-align: left; }
            .voucher-manual-entry form { grid-template-columns: minmax(0, 1fr) auto; }
        }
    </style>
    @endpush
@endonce
