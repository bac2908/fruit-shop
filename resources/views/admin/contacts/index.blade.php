@extends('layouts.admin')

@section('title', 'Hộp thư liên hệ | FruitShop Admin')

@section('head')
<style>
    .contact-toolbar { display: flex; gap: 10px; align-items: center; justify-content: space-between; margin-bottom: 16px; flex-wrap: wrap; }
    .contact-filters { display: flex; gap: 8px; flex-wrap: wrap; }
    .contact-filter { padding: 8px 12px; border: 1px solid var(--admin-line); border-radius: 7px; background: #fff; color: var(--admin-subtle); font-size: 12px; font-weight: 700; }
    .contact-filter.active { border-color: var(--admin-primary); background: var(--admin-primary-soft); color: var(--admin-primary); }
    .contact-search { display: flex; gap: 8px; }
    .contact-search input { min-width: 260px; height: 38px; padding: 0 12px; border: 1px solid var(--admin-line); border-radius: 7px; font: inherit; }
    .contact-search button { height: 38px; padding: 0 15px; border: 0; border-radius: 7px; background: var(--admin-primary); color: #fff; font-weight: 700; cursor: pointer; }
    .contact-table-wrap { overflow-x: auto; }
    .contact-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .contact-table th, .contact-table td { padding: 13px 12px; border-bottom: 1px solid #e2e9df; text-align: left; vertical-align: top; }
    .contact-table th { color: #607064; background: #f6faf4; font-size: 11px; text-transform: uppercase; }
    .contact-person strong, .contact-person span { display: block; }
    .contact-person span, .contact-preview { color: var(--admin-subtle); }
    .contact-preview { max-width: 440px; line-height: 1.55; }
    .contact-status { display: inline-flex; padding: 4px 8px; border-radius: 999px; background: #edf1ea; font-size: 11px; font-weight: 800; }
    .contact-status.new { color: #9a6200; background: #fff5d7; }
    .contact-status.read { color: #216789; background: #e8f6fc; }
    .contact-status.replied { color: #26703d; background: #e7f7ec; }
    .contact-status.spam { color: #a63c2b; background: #fff0ec; }
    .contact-open { color: var(--admin-primary); font-weight: 800; }
    .empty-inbox { padding: 44px 20px; text-align: center; color: var(--admin-subtle); }
    @media (max-width: 700px) { .contact-search, .contact-search input { width: 100%; min-width: 0; } }
</style>
@endsection

@section('admin_content')
<section class="page-head">
    <div>
        <h1 class="page-title">Hộp thư liên hệ</h1>
        <p class="page-subtitle">Theo dõi, phân loại và phản hồi yêu cầu của khách hàng.</p>
    </div>
</section>

<section class="panel">
    <div class="contact-toolbar">
        <div class="contact-filters" aria-label="Lọc trạng thái">
            <a class="contact-filter {{ !$selectedStatus ? 'active' : '' }}" href="{{ route('admin.contacts.index') }}">Tất cả {{ $counts->sum() }}</a>
            @foreach(['new' => 'Mới', 'read' => 'Đã đọc', 'replied' => 'Đã trả lời', 'spam' => 'Spam', 'archived' => 'Lưu trữ'] as $status => $label)
                <a class="contact-filter {{ $selectedStatus === $status ? 'active' : '' }}" href="{{ route('admin.contacts.index', ['status' => $status]) }}">
                    {{ $label }} {{ (int) ($counts[$status] ?? 0) }}
                </a>
            @endforeach
        </div>
        <form class="contact-search" method="get" action="{{ route('admin.contacts.index') }}">
            @if($selectedStatus)<input type="hidden" name="status" value="{{ $selectedStatus }}">@endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Tên, email, số điện thoại..." aria-label="Tìm trong hộp thư">
            <button type="submit"><i class="ri-search-line" aria-hidden="true"></i> Tìm</button>
        </form>
    </div>

    <div class="contact-table-wrap">
        <table class="contact-table">
            <thead><tr><th>Khách hàng</th><th>Nội dung</th><th>Trạng thái</th><th>Thời gian</th><th></th></tr></thead>
            <tbody>
            @forelse($messages as $message)
                <tr>
                    <td class="contact-person"><strong>{{ $message->name }}</strong><span>{{ $message->email }}</span><span>{{ $message->phone ?: 'Không có SĐT' }}</span></td>
                    <td><div class="contact-preview">{{ \Illuminate\Support\Str::limit($message->message, 150) }}</div></td>
                    <td><span class="contact-status {{ $message->status }}">{{ strtoupper($message->status) }}</span></td>
                    <td>{{ optional($message->created_at)->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</td>
                    <td><a class="contact-open" href="{{ route('admin.contacts.show', $message) }}">Mở <i class="ri-arrow-right-line" aria-hidden="true"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-inbox">Không có yêu cầu phù hợp.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px;">{{ $messages->links() }}</div>
</section>
@endsection
