@extends('layouts.admin')

@section('title', 'Báo cáo kinh doanh | FruitShop Admin')

@section('head')
<style>
    .report-filter,.report-kpis,.report-grid{display:grid;gap:12px}.report-filter{grid-template-columns:repeat(2,minmax(150px,210px)) auto auto;align-items:end}.report-kpis{grid-template-columns:repeat(5,minmax(0,1fr));margin:14px 0}.report-grid{grid-template-columns:1.1fr .9fr;margin-bottom:14px}.report-kpi{padding:18px}.report-kpi span{color:#687b70;font-size:12px;font-weight:700}.report-kpi strong{display:block;margin-top:8px;color:#173425;font-size:24px}.report-kpi small{display:block;margin-top:5px;color:#6f8177}.report-kpi small.up{color:#198754}.report-kpi small.down{color:#b54735}.report-field{display:grid;gap:6px}.report-field label{font-size:12px;font-weight:700;color:#53675b}.report-input{height:42px;border:1px solid #d7e1d5;border-radius:8px;padding:0 11px;background:#fff;color:#173425}.report-bars{display:grid;gap:12px}.report-bar-head{display:flex;justify-content:space-between;gap:12px;margin-bottom:6px;font-size:13px}.report-track{height:8px;border-radius:999px;background:#edf2ea;overflow:hidden}.report-track span{height:100%;display:block;background:#4f8b61}.report-chart{height:220px;display:flex;align-items:end;gap:5px;padding-top:18px;border-bottom:1px solid #dfe6dc}.report-column{flex:1;min-width:4px;background:#5b9870;border-radius:4px 4px 0 0;position:relative}.report-column:hover{background:#356c4a}.report-column:hover:after{content:attr(data-value);position:absolute;left:50%;bottom:calc(100% + 6px);transform:translateX(-50%);white-space:nowrap;background:#173425;color:#fff;border-radius:5px;padding:4px 6px;font-size:10px;z-index:2}.heatmap{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:8px}.heat-cell{min-height:58px;border:1px solid #dae5d9;border-radius:7px;display:grid;place-items:center;text-align:center;color:#385244;font-size:12px;background:#f7faf5}.heat-cell b{display:block}.heat-cell.lv1{background:#edf7ef}.heat-cell.lv2{background:#d8f0df}.heat-cell.lv3{background:#bce7c9}.heat-cell.lv4{background:#87cf9f}.empty-report{text-align:center;color:#708076;padding:28px}@media(max-width:1100px){.report-kpis{grid-template-columns:repeat(2,1fr)}.report-grid{grid-template-columns:1fr}}@media(max-width:700px){.report-filter{grid-template-columns:1fr 1fr}.report-filter .btn{width:100%;justify-content:center}.report-kpis{grid-template-columns:1fr}.report-chart{height:150px}}
</style>
@endsection

@section('admin_content')
<section class="page-head">
    <div>
        <h1 class="page-title">Báo cáo kinh doanh</h1>
        <p class="page-subtitle">Số liệu được tổng hợp trực tiếp từ đơn hàng trong hệ thống.</p>
    </div>
</section>

<section class="panel">
    <form class="report-filter" method="get" action="{{ route('admin.reports') }}">
        <div class="report-field"><label for="from">Từ ngày</label><input class="report-input" id="from" name="from" type="date" value="{{ $from }}"></div>
        <div class="report-field"><label for="to">Đến ngày</label><input class="report-input" id="to" name="to" type="date" value="{{ $to }}"></div>
        <button class="btn btn-primary" type="submit"><i class="ri-filter-3-line"></i>Áp dụng</button>
        <a class="btn btn-ghost" href="{{ route('admin.reports.export', ['from' => $from, 'to' => $to]) }}"><i class="ri-file-excel-2-line"></i>Xuất CSV</a>
    </form>
</section>

<section class="report-kpis">
    @php
        $kpis = [
            ['Doanh thu ghi nhận', number_format($metrics['revenue'], 0, ',', '.').'đ', $metrics['revenue_growth'], 'so với kỳ trước'],
            ['Tổng đơn', number_format($metrics['orders'], 0, ',', '.'), $metrics['orders_growth'], 'so với kỳ trước'],
            ['Giá trị đơn trung bình', number_format($metrics['aov'], 0, ',', '.').'đ', null, 'trên đơn ghi nhận doanh thu'],
            ['Tỷ lệ hoàn tất', $metrics['completion_rate'].'%', null, 'đơn đã giao / tổng đơn'],
            ['Tỷ lệ hủy', $metrics['cancellation_rate'].'%', null, 'đơn hủy / tổng đơn'],
        ];
    @endphp
    @foreach($kpis as [$label,$value,$growth,$hint])
        <article class="panel report-kpi">
            <span>{{ $label }}</span><strong>{{ $value }}</strong>
            @if($growth !== null)
                <small class="{{ $growth >= 0 ? 'up' : 'down' }}">{{ $growth >= 0 ? '+' : '' }}{{ $growth }}% {{ $hint }}</small>
            @else
                <small>{{ $hint }}</small>
            @endif
        </article>
    @endforeach
</section>

<section class="report-grid">
    <article class="panel">
        <div class="panel-head"><div><h2 class="panel-title">Doanh thu theo ngày</h2><p class="panel-sub">Chỉ tính đơn đã thanh toán hoặc hoàn tất, loại đơn hủy và hoàn tiền toàn bộ.</p></div></div>
        @php($maxRevenue = max(1, (int) $dailyRevenue->max('revenue')))
        <div class="report-chart" aria-label="Biểu đồ doanh thu theo ngày">
            @foreach($dailyRevenue as $day)
                <span class="report-column" style="height:{{ max(2, round($day['revenue'] * 100 / $maxRevenue)) }}%" data-value="{{ $day['label'] }}: {{ number_format($day['revenue'], 0, ',', '.') }}đ" title="{{ $day['label'] }}: {{ number_format($day['revenue'], 0, ',', '.') }}đ"></span>
            @endforeach
        </div>
    </article>
    <article class="panel">
        <div class="panel-head"><div><h2 class="panel-title">Khung giờ tạo đơn</h2><p class="panel-sub">Theo giờ Việt Nam, giúp bố trí nhân sự xử lý đơn.</p></div></div>
        <div class="heatmap">
            @foreach($hourlyOrders as $hour)
                <div class="heat-cell lv{{ $hour['level'] }}"><span><b>{{ $hour['hour'] }}</b>{{ $hour['count'] }} đơn</span></div>
            @endforeach
        </div>
    </article>
</section>

<section class="report-grid">
    <article class="panel">
        <div class="panel-head"><div><h2 class="panel-title">Trạng thái đơn hàng</h2><p class="panel-sub">Phân bổ toàn bộ đơn trong kỳ đã chọn.</p></div></div>
        <div class="report-bars">
            @foreach($statusRows as $row)
                <div><div class="report-bar-head"><span>{{ $row['label'] }}</span><strong>{{ $row['count'] }} đơn · {{ $row['percent'] }}%</strong></div><div class="report-track"><span style="width:{{ $row['percent'] }}%"></span></div></div>
            @endforeach
        </div>
    </article>
    <article class="panel">
        <div class="panel-head"><div><h2 class="panel-title">Phương thức thanh toán</h2><p class="panel-sub">Tỷ trọng theo số lượng đơn.</p></div></div>
        <div class="report-bars">
            @foreach($paymentRows as $row)
                <div><div class="report-bar-head"><span>{{ $row['label'] }}</span><strong>{{ $row['count'] }} đơn · {{ $row['percent'] }}%</strong></div><div class="report-track"><span style="width:{{ $row['percent'] }}%"></span></div></div>
            @endforeach
        </div>
    </article>
</section>

<section class="panel">
    <div class="panel-head"><div><h2 class="panel-title">Sản phẩm bán chạy</h2><p class="panel-sub">Xếp hạng theo doanh thu dòng sản phẩm, không tính quà tặng miễn phí.</p></div></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Sản phẩm</th><th>Số lượng</th><th>Doanh thu</th></tr></thead>
            <tbody>
            @forelse($topProducts as $product)
                <tr><td>{{ $loop->iteration }}</td><td><strong>{{ $product->product_name }}</strong></td><td>{{ number_format((int)$product->quantity, 0, ',', '.') }}</td><td>{{ number_format((int)$product->revenue, 0, ',', '.') }}đ</td></tr>
            @empty
                <tr><td colspan="4" class="empty-report">Chưa có đơn hàng phát sinh doanh thu trong kỳ này.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</section>
@endsection
