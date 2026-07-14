@extends('layouts.admin')

@section('title', 'Chi tiết liên hệ | FruitShop Admin')

@section('head')
<style>
    .contact-detail-grid { display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr); gap: 16px; }
    .contact-detail-block { padding: 18px 0; border-bottom: 1px solid #e3e9e0; }
    .contact-detail-block:last-child { border-bottom: 0; }
    .contact-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
    .contact-meta span { display: block; color: var(--admin-subtle); font-size: 11px; margin-bottom: 4px; text-transform: uppercase; }
    .contact-message-body { white-space: pre-wrap; line-height: 1.75; color: #2a4031; }
    .contact-form { display: grid; gap: 10px; }
    .contact-form label { font-size: 12px; font-weight: 800; }
    .contact-form select, .contact-form textarea { width: 100%; padding: 10px 12px; border: 1px solid var(--admin-line); border-radius: 7px; background: #fff; font: inherit; }
    .contact-form textarea { min-height: 130px; resize: vertical; }
    .contact-form button { min-height: 40px; border: 0; border-radius: 7px; background: var(--admin-primary); color: #fff; font-weight: 800; cursor: pointer; }
    .contact-form .secondary { background: #eef4ea; color: #315226; }
    .admin-alert { margin-bottom: 14px; padding: 12px 14px; border-radius: 7px; }
    .admin-alert.success { color: #28642c; background: #e9f7e7; border: 1px solid #bbdcb5; }
    .admin-alert.error { color: #9d3025; background: #fff0ec; border: 1px solid #f0beb5; }
    .contact-back { color: var(--admin-primary); font-size: 13px; font-weight: 800; }
    @media (max-width: 960px) { .contact-detail-grid { grid-template-columns: 1fr; } }
    @media (max-width: 560px) { .contact-meta { grid-template-columns: 1fr; } }
</style>
@endsection

@section('admin_content')
<section class="page-head">
    <div>
        <a class="contact-back" href="{{ route('admin.contacts.index') }}"><i class="ri-arrow-left-line" aria-hidden="true"></i> Hộp thư</a>
        <h1 class="page-title" style="margin-top:8px;">{{ $contactMessage->subject ?: 'Yêu cầu liên hệ' }}</h1>
        <p class="page-subtitle">Nhận lúc {{ optional($contactMessage->created_at)->timezone(config('app.display_timezone'))->format('d/m/Y H:i') }}</p>
    </div>
</section>

@if(session('success'))<div class="admin-alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="admin-alert error" role="alert">{{ $errors->first() }}</div>@endif

<div class="contact-detail-grid">
    <section class="panel">
        <div class="contact-meta">
            <div><span>Khách hàng</span><strong>{{ $contactMessage->name }}</strong></div>
            <div><span>Trạng thái</span><strong>{{ strtoupper($contactMessage->status) }}</strong></div>
            <div><span>Email</span><a href="mailto:{{ $contactMessage->email }}">{{ $contactMessage->email }}</a></div>
            <div><span>Điện thoại</span><a href="tel:{{ $contactMessage->phone }}">{{ $contactMessage->phone ?: 'Không cung cấp' }}</a></div>
        </div>
        <div class="contact-detail-block">
            <h2 class="panel-title">Nội dung khách gửi</h2>
            <div class="contact-message-body">{{ $contactMessage->message }}</div>
        </div>
        @if($contactMessage->reply_message)
            <div class="contact-detail-block">
                <h2 class="panel-title">Phản hồi gần nhất</h2>
                <div class="contact-message-body">{{ $contactMessage->reply_message }}</div>
            </div>
        @endif
    </section>

    <aside style="display:grid;gap:16px;align-content:start;">
        <section class="panel">
            <h2 class="panel-title">Xử lý nội bộ</h2>
            <form class="contact-form" method="post" action="{{ route('admin.contacts.update', $contactMessage) }}">
                @csrf @method('PATCH')
                <label for="contact-status">Trạng thái</label>
                <select id="contact-status" name="status">
                    @foreach(['new' => 'Mới', 'read' => 'Đã đọc', 'replied' => 'Đã trả lời', 'spam' => 'Spam', 'archived' => 'Lưu trữ'] as $value => $label)
                        <option value="{{ $value }}" @selected($contactMessage->status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <label for="admin-note">Ghi chú admin</label>
                <textarea id="admin-note" name="admin_note" maxlength="3000">{{ old('admin_note', $contactMessage->admin_note) }}</textarea>
                <button class="secondary" type="submit">Lưu trạng thái</button>
            </form>
        </section>

        <section class="panel">
            <h2 class="panel-title">Trả lời qua email</h2>
            <form class="contact-form" method="post" action="{{ route('admin.contacts.reply', $contactMessage) }}">
                @csrf
                <label for="reply-message">Nội dung phản hồi</label>
                <textarea id="reply-message" name="reply_message" maxlength="3000" required>{{ old('reply_message') }}</textarea>
                <button type="submit"><i class="ri-send-plane-fill" aria-hidden="true"></i> Gửi phản hồi</button>
            </form>
        </section>
    </aside>
</div>
@endsection
