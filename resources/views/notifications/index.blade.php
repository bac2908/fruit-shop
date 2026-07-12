@extends('layouts.app')

@section('title', 'Thông báo của tôi - Thế Giới Trái Cây')

@section('content')
<section class="notification-page">
    <div class="container notification-shell">
        <header class="notification-heading">
            <div>
                <span class="notification-kicker">Tài khoản khách hàng</span>
                <h1>Thông báo của tôi</h1>
                <p>Theo dõi voucher, thanh toán và tiến trình đơn hàng tại một nơi.</p>
            </div>
            @if($unreadCount > 0)
                <form method="post" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="notification-read-all">
                        <i class="fa fa-check" aria-hidden="true"></i>
                        Đánh dấu đã đọc
                    </button>
                </form>
            @endif
        </header>

        <nav class="notification-tabs" aria-label="Lọc thông báo">
            <a href="{{ route('notifications.index') }}" class="{{ $filter === 'all' ? 'is-active' : '' }}">Tất cả</a>
            <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="{{ $filter === 'unread' ? 'is-active' : '' }}">
                Chưa đọc
                @if($unreadCount > 0)<span>{{ $unreadCount }}</span>@endif
            </a>
        </nav>

        <div class="notification-list">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    $icon = in_array($data['icon'] ?? '', ['ticket', 'shopping-bag', 'check-circle', 'truck', 'gift', 'times-circle', 'credit-card'], true)
                        ? $data['icon']
                        : 'bell';
                @endphp
                <article class="notification-row {{ $notification->read_at ? '' : 'is-unread' }}">
                    <div class="notification-row-icon is-{{ $data['category'] ?? 'general' }}">
                        <i class="fa fa-{{ $icon }}" aria-hidden="true"></i>
                    </div>
                    <div class="notification-row-content">
                        <div class="notification-row-title">
                            <h2>{{ $data['title'] ?? 'Thông báo mới' }}</h2>
                            @if(!$notification->read_at)<span>Chưa đọc</span>@endif
                        </div>
                        <p>{{ $data['message'] ?? '' }}</p>
                        <time datetime="{{ optional($notification->created_at)->toIso8601String() }}">
                            {{ optional($notification->created_at)->diffForHumans() }}
                        </time>
                    </div>
                    <form method="post" action="{{ route('notifications.open', $notification->id) }}" class="notification-row-action">
                        @csrf
                        <button type="submit">
                            {{ $data['action_label'] ?? 'Xem chi tiết' }}
                            <i class="fa fa-angle-right" aria-hidden="true"></i>
                        </button>
                    </form>
                </article>
            @empty
                <div class="notification-empty">
                    <i class="fa fa-bell-o" aria-hidden="true"></i>
                    <h2>{{ $filter === 'unread' ? 'Bạn đã đọc hết thông báo' : 'Chưa có thông báo nào' }}</h2>
                    <p>Voucher được nhận và cập nhật đơn hàng sẽ xuất hiện tại đây.</p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="notification-pagination">{{ $notifications->links() }}</div>
        @endif
    </div>
</section>
@endsection

@push('styles')
<style>
    .notification-page {
        background: #f4f8ef;
        min-height: 560px;
        padding: 34px 0 58px;
    }

    .notification-shell {
        max-width: 1080px;
    }

    .notification-heading {
        align-items: flex-end;
        border-bottom: 1px solid #dce6d4;
        display: flex;
        gap: 24px;
        justify-content: space-between;
        padding-bottom: 22px;
    }

    .notification-kicker {
        color: #659d29;
        display: block;
        font-size: 12px;
        font-weight: 800;
        margin-bottom: 7px;
        text-transform: uppercase;
    }

    .notification-heading h1 {
        color: #20321b;
        font-size: 30px;
        margin: 0 0 7px;
    }

    .notification-heading p {
        color: #687363;
        margin: 0;
    }

    .notification-read-all {
        align-items: center;
        background: #fff;
        border: 1px solid #cddcc2;
        border-radius: 6px;
        color: #426c20;
        display: inline-flex;
        font-weight: 700;
        gap: 7px;
        min-height: 40px;
        padding: 0 14px;
    }

    .notification-tabs {
        display: flex;
        gap: 4px;
        margin: 20px 0 12px;
    }

    .notification-tabs a {
        align-items: center;
        border-bottom: 2px solid transparent;
        color: #687363;
        display: inline-flex;
        font-weight: 700;
        gap: 7px;
        padding: 9px 14px;
        text-decoration: none !important;
    }

    .notification-tabs a.is-active {
        border-color: #75b72c;
        color: #3d681c;
    }

    .notification-tabs span {
        background: #e9f4dd;
        border-radius: 999px;
        font-size: 11px;
        padding: 2px 7px;
    }

    .notification-list {
        background: #fff;
        border: 1px solid #dce6d4;
        border-radius: 8px;
        overflow: hidden;
    }

    .notification-row {
        align-items: center;
        border-bottom: 1px solid #edf1e9;
        display: grid;
        gap: 14px;
        grid-template-columns: 44px minmax(0, 1fr) auto;
        padding: 17px 18px;
    }

    .notification-row:last-child {
        border-bottom: 0;
    }

    .notification-row.is-unread {
        background: #f7fcef;
        box-shadow: inset 3px 0 #75b72c;
    }

    .notification-row-icon {
        align-items: center;
        background: #eef5e8;
        border-radius: 50%;
        color: #5e9628;
        display: flex;
        font-size: 17px;
        height: 42px;
        justify-content: center;
        width: 42px;
    }

    .notification-row-icon.is-payment {
        background: #e8f4fb;
        color: #2d87b8;
    }

    .notification-row-title {
        align-items: center;
        display: flex;
        gap: 9px;
    }

    .notification-row-title h2 {
        color: #25331f;
        font-size: 15px;
        margin: 0;
    }

    .notification-row-title span {
        background: #75b72c;
        border-radius: 999px;
        color: #fff;
        font-size: 10px;
        padding: 2px 7px;
    }

    .notification-row-content p {
        color: #5e6859;
        font-size: 13px;
        line-height: 1.5;
        margin: 5px 0;
    }

    .notification-row-content time {
        color: #939b8f;
        font-size: 11px;
    }

    .notification-row-action button {
        background: transparent;
        border: 0;
        color: #4d7c24;
        font-size: 12px;
        font-weight: 800;
        white-space: nowrap;
    }

    .notification-empty {
        color: #727c6e;
        padding: 70px 20px;
        text-align: center;
    }

    .notification-empty > i {
        color: #8ab85d;
        font-size: 38px;
    }

    .notification-empty h2 {
        color: #34432e;
        font-size: 20px;
        margin: 14px 0 7px;
    }

    .notification-empty p {
        margin: 0;
    }

    .notification-pagination {
        margin-top: 18px;
    }

    @media (max-width: 767px) {
        .notification-page {
            padding: 20px 0 42px;
        }

        .notification-heading {
            align-items: flex-start;
            flex-direction: column;
        }

        .notification-row {
            align-items: start;
            grid-template-columns: 40px minmax(0, 1fr);
            padding: 15px 13px;
        }

        .notification-row-action {
            grid-column: 2;
        }

        .notification-row-action button {
            padding: 0;
        }
    }
</style>
@endpush
