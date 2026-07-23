<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Console | FruitShop')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Sora:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">

    <style>
        :root {
            --admin-bg: #f4f4ec;
            --admin-paper: #fffdf7;
            --admin-card: #ffffff;
            --admin-ink: #173425;
            --admin-subtle: #627368;
            --admin-line: #d8e0d4;
            --admin-primary: #1f7a4a;
            --admin-primary-soft: #e8f6ee;
            --admin-accent: #ff8a3d;
            --admin-accent-soft: #fff0e5;
            --admin-danger: #cf4c33;
            --admin-warning: #e4a723;
            --admin-radius-lg: 22px;
            --admin-radius-md: 14px;
            --admin-shadow: 0 16px 40px rgba(23, 52, 37, 0.08);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            font-family: 'Be Vietnam Pro', sans-serif;
            color: var(--admin-ink);
            background:
                radial-gradient(circle at 10% -10%, #fef9df 0%, transparent 36%),
                radial-gradient(circle at 90% 10%, #e8f6ee 0%, transparent 34%),
                repeating-linear-gradient(
                    135deg,
                    rgba(23, 52, 37, 0.02) 0,
                    rgba(23, 52, 37, 0.02) 8px,
                    transparent 8px,
                    transparent 16px
                ),
                var(--admin-bg);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .admin-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 0;
            position: relative;
        }

        .sidebar {
            background: linear-gradient(180deg, #173425 0%, #225538 100%);
            color: #edf7f0;
            padding: 20px;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding: 14px;
            border-radius: var(--admin-radius-md);
            background: rgba(255, 255, 255, 0.08);
        }

        .brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: grid;
            place-items: center;
            color: #1a3f2b;
            background: linear-gradient(135deg, #f8d14e, #ff8a3d);
            font-size: 20px;
        }

        .brand-title {
            font-family: 'Sora', sans-serif;
            font-weight: 700;
            font-size: 15px;
            line-height: 1.3;
        }

        .brand-sub {
            font-size: 12px;
            opacity: 0.75;
            margin-top: 2px;
        }

        .menu-label {
            font-size: 11px;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            opacity: 0.58;
            margin: 18px 12px 10px;
        }

        .menu {
            display: grid;
            gap: 8px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 13px;
            border-radius: 12px;
            font-weight: 500;
            color: rgba(237, 247, 240, 0.9);
            transition: all 0.25s ease;
        }

        .menu-link i {
            font-size: 18px;
        }

        .menu-link:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(2px);
        }

        .menu-link.active {
            color: #143522;
            background: linear-gradient(135deg, #f7d14d, #ff9a52);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.18);
        }

        .sidebar-footer {
            margin-top: 24px;
            border-radius: var(--admin-radius-md);
            padding: 14px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.05);
            font-size: 13px;
            line-height: 1.5;
        }

        .sidebar-footer h4 {
            margin: 0 0 6px;
            font-size: 14px;
            font-family: 'Sora', sans-serif;
            color: #fff5d4;
        }

        .content-wrap {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            padding: 14px 24px;
            background: rgba(255, 253, 247, 0.85);
            border-bottom: 1px solid var(--admin-line);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 30;
        }

        .menu-toggle {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: 1px solid var(--admin-line);
            background: #fff;
            color: var(--admin-ink);
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .topbar-search {
            flex: 1;
            max-width: 520px;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            border: 1px solid var(--admin-line);
            border-radius: 12px;
            padding: 0 12px;
            height: 42px;
        }

        .topbar-search-wrap {
            flex: 1;
            max-width: 520px;
            position: relative;
        }

        .topbar-search-wrap .topbar-search {
            max-width: none;
            width: 100%;
        }

        .search-suggestions {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            z-index: 60;
            padding: 7px;
            border: 1px solid var(--admin-line);
            border-radius: 9px;
            background: #fff;
            box-shadow: 0 16px 36px rgba(20, 50, 34, .14);
        }

        .search-suggestions.open {
            display: grid;
            gap: 3px;
        }

        .search-suggestion {
            display: grid;
            grid-template-columns: 78px minmax(0, 1fr);
            gap: 9px;
            padding: 9px;
            border-radius: 7px;
            color: var(--admin-ink);
        }

        .search-suggestion:hover {
            background: var(--admin-primary-soft);
        }

        .search-suggestion small {
            color: var(--admin-primary);
            font-weight: 700;
        }

        .search-suggestion strong,
        .search-suggestion span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .search-suggestion span {
            margin-top: 2px;
            color: var(--admin-subtle);
            font-size: 11px;
        }

        .topbar-search i {
            color: var(--admin-subtle);
            font-size: 18px;
        }

        .topbar-search input {
            width: 100%;
            border: none;
            outline: none;
            font-size: 14px;
            background: transparent;
            font-family: inherit;
            color: var(--admin-ink);
        }

        .topbar-actions {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            border: 1px solid var(--admin-line);
            background: #fff;
            display: grid;
            place-items: center;
            color: var(--admin-ink);
            cursor: pointer;
            position: relative;
        }

        .icon-badge {
            position: absolute;
            right: -5px;
            top: -6px;
            min-width: 19px;
            height: 19px;
            padding: 0 5px;
            border: 2px solid #fff;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: var(--admin-danger);
            color: #fff;
            font-size: 9px;
            font-weight: 800;
        }

        .profile-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--admin-line);
            background: #fff;
            border-radius: 999px;
            padding: 5px 6px 5px 10px;
        }

        .profile-chip strong {
            font-size: 13px;
            display: block;
        }

        .profile-chip span {
            font-size: 11px;
            color: var(--admin-subtle);
        }

        .profile-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            color: #fff;
            background: linear-gradient(135deg, #1f7a4a, #53a66f);
            font-size: 12px;
            font-weight: 700;
        }

        .admin-main {
            padding: 22px;
        }

        .page-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 18px;
        }

        .page-title {
            font-family: 'Sora', sans-serif;
            margin: 0;
            font-size: 28px;
            line-height: 1.2;
        }

        .page-subtitle {
            margin: 6px 0 0;
            color: var(--admin-subtle);
            font-size: 14px;
        }

        .btn {
            border: none;
            border-radius: 12px;
            padding: 10px 14px;
            font-family: inherit;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            color: #fff;
            background: linear-gradient(135deg, #1f7a4a, #2f995f);
            box-shadow: 0 8px 20px rgba(31, 122, 74, 0.24);
        }

        .btn-accent {
            color: #7b3d00;
            background: linear-gradient(135deg, #ffd36c, #ffb457);
        }

        .btn-ghost {
            color: var(--admin-ink);
            border: 1px solid var(--admin-line);
            background: #fff;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 16px;
        }

        .kpi-card {
            background: var(--admin-card);
            border-radius: var(--admin-radius-md);
            border: 1px solid var(--admin-line);
            padding: 14px;
            box-shadow: 0 8px 20px rgba(18, 44, 30, 0.04);
        }

        .kpi-label {
            color: var(--admin-subtle);
            font-size: 12px;
            margin-bottom: 8px;
        }

        .kpi-value {
            font-family: 'Sora', sans-serif;
            font-size: 28px;
            margin: 0;
            line-height: 1.1;
            color: var(--admin-ink);
        }

        .kpi-foot {
            margin-top: 8px;
            font-size: 12px;
            color: var(--admin-subtle);
        }

        .kpi-foot strong {
            color: var(--admin-primary);
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1.45fr 1fr;
            gap: 14px;
            margin-bottom: 14px;
        }

        .grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-bottom: 14px;
        }

        .panel {
            background: var(--admin-paper);
            border: 1px solid var(--admin-line);
            border-radius: var(--admin-radius-lg);
            box-shadow: var(--admin-shadow);
            padding: 16px;
        }

        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            margin-bottom: 12px;
        }

        .panel-title {
            margin: 0;
            font-family: 'Sora', sans-serif;
            font-size: 17px;
        }

        .panel-sub {
            margin: 4px 0 0;
            color: var(--admin-subtle);
            font-size: 13px;
        }

        .tag {
            font-size: 11px;
            padding: 5px 8px;
            border-radius: 999px;
            border: 1px solid transparent;
            font-weight: 600;
            background: var(--admin-primary-soft);
            color: var(--admin-primary);
        }

        .table-wrap {
            overflow: auto;
            border: 1px solid var(--admin-line);
            border-radius: 12px;
            background: #fff;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            min-width: 700px;
        }

        th,
        td {
            text-align: left;
            padding: 11px 12px;
            border-bottom: 1px solid #eef2eb;
            vertical-align: middle;
            font-size: 13px;
        }

        th {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--admin-subtle);
            font-weight: 600;
            background: #f7f9f5;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .status-pill.pending {
            color: #8b6200;
            background: #fff6db;
        }

        .status-pill.confirmed {
            color: #2d6a3d;
            background: #e9f8ef;
        }

        .status-pill.shipping {
            color: #1c5f82;
            background: #e5f4ff;
        }

        .status-pill.done {
            color: #214f2f;
            background: #dff5e7;
        }

        .status-pill.cancelled {
            color: #8f3122;
            background: #ffe6e1;
        }

        .stack {
            display: grid;
            gap: 10px;
        }

        .metric-line {
            display: grid;
            gap: 6px;
        }

        .metric-line-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 13px;
        }

        .progress {
            width: 100%;
            height: 9px;
            border-radius: 999px;
            background: #edf2eb;
            overflow: hidden;
        }

        .progress > span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #1f7a4a, #55b36f);
        }

        .empty-box {
            text-align: center;
            padding: 24px 14px;
            color: var(--admin-subtle);
            font-size: 13px;
        }

        .empty-box i {
            display: inline-grid;
            place-items: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-bottom: 8px;
            color: #567064;
            background: #edf2ea;
            font-size: 18px;
        }

        .chip-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .chip {
            padding: 6px 9px;
            border-radius: 999px;
            border: 1px solid var(--admin-line);
            background: #fff;
            font-size: 12px;
            color: #304639;
        }

        .reveal {
            opacity: 0;
            transform: translateY(16px) scale(0.99);
            animation: riseIn 0.55s ease forwards;
            animation-delay: var(--delay, 0ms);
        }

        @keyframes riseIn {
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        @media (max-width: 1180px) {
            .stats-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .grid-2,
            .grid-3 {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 980px) {
            .admin-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: fixed;
                left: -292px;
                top: 0;
                bottom: 0;
                width: 280px;
                z-index: 55;
                transition: left 0.28s ease;
            }

            .admin-shell.sidebar-open .sidebar {
                left: 0;
            }

            .admin-shell.sidebar-open::after {
                content: '';
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.3);
                z-index: 40;
            }

            .menu-toggle {
                display: inline-flex;
            }
        }

        @media (max-width: 768px) {
            .topbar {
                padding: 12px;
            }

            .topbar-search {
                min-width: 0;
            }

            .topbar-actions {
                gap: 7px;
            }

            .profile-chip {
                display: none;
            }

            .admin-main {
                padding: 12px;
            }

            .page-title {
                font-size: 23px;
            }

            .page-head {
                flex-direction: column;
                align-items: flex-start;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @yield('head')
</head>
<body>
<div class="admin-shell" id="adminShell">
    <aside class="sidebar">
        <a href="{{ route('admin.dashboard') }}" class="brand">
            <span class="brand-mark"><i class="ri-store-2-line"></i></span>
            <span>
                <span class="brand-title">FruitShop Admin</span>
                <span class="brand-sub">Ecommerce operation center</span>
            </span>
        </a>

        <div class="menu-label">Điều hướng</div>
        <nav class="menu">
            @if(auth()->user()->hasAdminPermission('dashboard.view'))
            <a href="{{ route('admin.dashboard') }}" class="menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="ri-dashboard-horizontal-line"></i>
                <span>Tổng quan</span>
            </a>
            @endif
            @if(auth()->user()->hasAdminPermission('orders.view'))
            <a href="{{ route('admin.orders') }}" class="menu-link {{ request()->routeIs('admin.orders*') ? 'active' : '' }}">
                <i class="ri-file-list-3-line"></i>
                <span>Đơn hàng</span>
            </a>
            @endif
            @if(auth()->user()->hasAdminPermission('catalog.manage'))
            <a href="{{ route('admin.products') }}" class="menu-link {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                <i class="ri-apple-line"></i>
                <span>Sản phẩm</span>
            </a>
            <a href="{{ route('admin.categories.index') }}" class="menu-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                <i class="ri-node-tree"></i>
                <span>Danh mục</span>
            </a>
            @endif
            @if(auth()->user()->hasAdminPermission('content.manage'))
            <a href="{{ route('admin.banners.index') }}" class="menu-link {{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">
                <i class="ri-image-2-line"></i>
                <span>Banner</span>
            </a>
            <a href="{{ route('admin.pages.index') }}" class="menu-link {{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">
                <i class="ri-pages-line"></i>
                <span>Trang nội dung</span>
            </a>
            @endif
            @if(auth()->user()->hasAdminPermission('customers.manage'))
            <a href="{{ route('admin.customers') }}" class="menu-link {{ request()->routeIs('admin.customers*') ? 'active' : '' }}">
                <i class="ri-team-line"></i>
                <span>Khách hàng</span>
            </a>
            @endif
            @if(auth()->user()->hasAdminPermission('support.manage'))
            <a href="{{ route('admin.contacts.index') }}" class="menu-link {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}">
                <i class="ri-mail-open-line"></i>
                <span>Hộp thư liên hệ</span>
                @if(($adminNewContactCount ?? 0) > 0)
                    <strong style="margin-left:auto;min-width:24px;padding:2px 7px;border-radius:999px;background:#fff;color:#1f7a4a;text-align:center;font-size:11px;">{{ $adminNewContactCount }}</strong>
                @endif
            </a>
            @endif
            @if(auth()->user()->hasAdminPermission('promotions.manage'))
            <a href="{{ route('admin.coupons') }}" class="menu-link {{ request()->routeIs('admin.coupons*') ? 'active' : '' }}">
                <i class="ri-coupon-2-line"></i>
                <span>Khuyến mãi</span>
            </a>
            @endif
            @if(auth()->user()->hasAdminPermission('reports.view'))
            <a href="{{ route('admin.reports') }}" class="menu-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                <i class="ri-bar-chart-box-line"></i>
                <span>Báo cáo</span>
            </a>
            @endif
            @if(auth()->user()->hasAdminPermission('settings.manage'))
            <a href="{{ route('admin.settings') }}" class="menu-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <i class="ri-settings-3-line"></i>
                <span>Cài đặt</span>
            </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <h4>Vận hành an toàn</h4>
            <div>Dùng tài khoản riêng, bật 2FA và chỉ cấp đúng quyền cần thiết cho từng nhân viên.</div>
        </div>
    </aside>

    <div class="content-wrap">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="adminMenuToggle" aria-label="Toggle menu">
                <i class="ri-menu-3-line"></i>
            </button>

            <div class="topbar-search-wrap">
                <form class="topbar-search" action="{{ route('admin.search') }}" method="get" role="search">
                    <i class="ri-search-line"></i>
                    <input id="adminGlobalSearch" type="search" name="q" value="{{ request()->routeIs('admin.search') ? request('q') : '' }}" placeholder="Tìm đơn hàng, sản phẩm, khách hàng..." autocomplete="off">
                </form>
                <div class="search-suggestions" id="adminSearchSuggestions" aria-live="polite"></div>
            </div>

            <div class="topbar-actions">
                <a href="{{ route('admin.notifications') }}" class="icon-btn" aria-label="Tác vụ cần xử lý" title="Tác vụ cần xử lý">
                    <i class="ri-notification-3-line"></i>
                    @if(($adminActionSummary['total'] ?? 0) > 0)<span class="icon-badge">{{ min(99, $adminActionSummary['total']) }}</span>@endif
                </a>
                @if(auth()->user()->hasAdminPermission('reports.view'))
                    <a href="{{ route('admin.reports', ['from'=>now()->startOfMonth()->toDateString(),'to'=>now()->toDateString()]) }}" class="icon-btn" aria-label="Báo cáo tháng này" title="Báo cáo tháng này"><i class="ri-calendar-2-line"></i></a>
                @endif
                <div class="profile-chip">
                    <span>
                        <strong>{{ auth()->user()->name }}</strong>
                        <span>{{ $adminRoleLabel }}</span>
                    </span>
                    <span class="profile-avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                </div>
                <form method="post" action="{{ route('logout') }}">@csrf<button type="submit" class="icon-btn" aria-label="Đăng xuất" title="Đăng xuất"><i class="ri-logout-box-r-line"></i></button></form>
            </div>
        </header>

        <main class="admin-main">
            @yield('admin_content')
        </main>
    </div>
</div>

<script>
    (function () {
        var shell = document.getElementById('adminShell');
        var toggle = document.getElementById('adminMenuToggle');

        if (toggle && shell) {
            toggle.addEventListener('click', function () {
                shell.classList.toggle('sidebar-open');
            });

            document.addEventListener('click', function (event) {
                if (!shell.classList.contains('sidebar-open')) {
                    return;
                }

                var isMenuToggle = toggle.contains(event.target);
                var isSidebar = event.target.closest('.sidebar');

                if (!isMenuToggle && !isSidebar) {
                    shell.classList.remove('sidebar-open');
                }
            });
        }

        var reveals = document.querySelectorAll('.reveal');
        reveals.forEach(function (el, index) {
            if (!el.style.getPropertyValue('--delay')) {
                el.style.setProperty('--delay', (index * 70) + 'ms');
            }
        });

        var searchInput = document.getElementById('adminGlobalSearch');
        var suggestionBox = document.getElementById('adminSearchSuggestions');
        var searchTimer = null;
        var searchController = null;

        function closeSuggestions() {
            if (suggestionBox) {
                suggestionBox.classList.remove('open');
                suggestionBox.replaceChildren();
            }
        }

        function renderSuggestions(items) {
            suggestionBox.replaceChildren();
            if (!items.length) {
                closeSuggestions();
                return;
            }

            items.forEach(function (item) {
                var link = document.createElement('a');
                var type = document.createElement('small');
                var copy = document.createElement('span');
                var title = document.createElement('strong');
                var detail = document.createElement('span');

                link.className = 'search-suggestion';
                link.href = item.url;
                type.textContent = item.type;
                title.textContent = item.title;
                detail.textContent = item.detail || '';
                copy.append(title, detail);
                link.append(type, copy);
                suggestionBox.append(link);
            });

            suggestionBox.classList.add('open');
        }

        if (searchInput && suggestionBox) {
            searchInput.addEventListener('input', function () {
                window.clearTimeout(searchTimer);
                var query = searchInput.value.trim();
                if (query.length < 2) {
                    closeSuggestions();
                    return;
                }

                searchTimer = window.setTimeout(function () {
                    if (searchController) {
                        searchController.abort();
                    }
                    searchController = new AbortController();

                    fetch(@json(route('admin.search.suggestions')) + '?q=' + encodeURIComponent(query), {
                        headers: { 'Accept': 'application/json' },
                        signal: searchController.signal
                    })
                        .then(function (response) { return response.ok ? response.json() : { items: [] }; })
                        .then(function (payload) {
                            if (query === searchInput.value.trim()) {
                                renderSuggestions(payload.items || []);
                            }
                        })
                        .catch(function (error) {
                            if (error.name !== 'AbortError') {
                                closeSuggestions();
                            }
                        });
                }, 220);
            });

            document.addEventListener('click', function (event) {
                if (!event.target.closest('.topbar-search-wrap')) {
                    closeSuggestions();
                }
            });
        }
    })();
</script>

@yield('scripts')
</body>
</html>
