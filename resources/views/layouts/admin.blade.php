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

        <div class="menu-label">Dieu huong</div>
        <nav class="menu">
            <a href="{{ route('admin.dashboard') }}" class="menu-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="ri-dashboard-horizontal-line"></i>
                <span>Tong quan</span>
            </a>
            <a href="{{ route('admin.orders') }}" class="menu-link {{ request()->routeIs('admin.orders') ? 'active' : '' }}">
                <i class="ri-file-list-3-line"></i>
                <span>Don hang</span>
            </a>
            <a href="{{ route('admin.products') }}" class="menu-link {{ request()->routeIs('admin.products*') ? 'active' : '' }}">
                <i class="ri-apple-line"></i>
                <span>San pham</span>
            </a>
            <a href="{{ route('admin.customers') }}" class="menu-link {{ request()->routeIs('admin.customers') ? 'active' : '' }}">
                <i class="ri-team-line"></i>
                <span>Khach hang</span>
            </a>
            <a href="{{ route('admin.contacts.index') }}" class="menu-link {{ request()->routeIs('admin.contacts.*') ? 'active' : '' }}">
                <i class="ri-mail-open-line"></i>
                <span>Hop thu lien he</span>
                @if(($adminNewContactCount ?? 0) > 0)
                    <strong style="margin-left:auto;min-width:24px;padding:2px 7px;border-radius:999px;background:#fff;color:#1f7a4a;text-align:center;font-size:11px;">{{ $adminNewContactCount }}</strong>
                @endif
            </a>
            <a href="{{ route('admin.coupons') }}" class="menu-link {{ request()->routeIs('admin.coupons') ? 'active' : '' }}">
                <i class="ri-coupon-2-line"></i>
                <span>Coupon</span>
            </a>
            <a href="{{ route('admin.reports') }}" class="menu-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                <i class="ri-bar-chart-box-line"></i>
                <span>Bao cao</span>
            </a>
            <a href="{{ route('admin.settings') }}" class="menu-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <i class="ri-settings-3-line"></i>
                <span>Cai dat</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <h4>Sprint focus</h4>
            <div>Uu tien FE truoc, san sang map API va truy van DB khi chuyen sang BE.</div>
        </div>
    </aside>

    <div class="content-wrap">
        <header class="topbar">
            <button type="button" class="menu-toggle" id="adminMenuToggle" aria-label="Toggle menu">
                <i class="ri-menu-3-line"></i>
            </button>

            <div class="topbar-search">
                <i class="ri-search-line"></i>
                <input type="text" placeholder="Tim nhanh don hang, san pham, khach hang...">
            </div>

            <div class="topbar-actions">
                <button type="button" class="icon-btn" aria-label="Notifications"><i class="ri-notification-3-line"></i></button>
                <button type="button" class="icon-btn" aria-label="Calendar"><i class="ri-calendar-2-line"></i></button>
                <div class="profile-chip">
                    <span>
                        <strong>Admin Team</strong>
                        <span>Operations</span>
                    </span>
                    <span class="profile-avatar">AD</span>
                </div>
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
    })();
</script>

@yield('scripts')
</body>
</html>
