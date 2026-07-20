@section('head')
    <style>
        .admin-alert { display: grid; gap: 4px; margin-bottom: 14px; padding: 12px 14px; border: 1px solid; border-radius: 8px; font-size: 13px; font-weight: 650; }
        .admin-alert.success { color: #245f35; border-color: #bfe3c8; background: #edf9f0; }
        .admin-alert.error { color: #8f3122; border-color: #f0bcb1; background: #fff1ee; }
        .status-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
        .status-card { display: grid; gap: 5px; min-height: 84px; padding: 13px; border: 1px solid var(--admin-line); border-radius: 8px; background: #fff; color: var(--admin-ink); }
        .status-card:hover { border-color: #9fc4a5; box-shadow: 0 7px 18px rgba(28, 70, 44, .07); }
        .status-card small { color: var(--admin-subtle); font-size: 11px; font-weight: 700; }
        .status-card strong { align-self: end; font-family: 'Sora', sans-serif; font-size: 24px; }
        .status-card.pending strong { color: #966900; }
        .status-card.confirmed strong { color: #26703d; }
        .status-card.shipping strong { color: #17658a; }
        .status-card.done strong { color: #244f32; }
        .status-card.cancelled strong { color: #9a3e2c; }
        .attention-strip { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-bottom: 14px; }
        .attention-link { display: flex; align-items: center; justify-content: space-between; gap: 10px; min-height: 46px; padding: 9px 11px; border: 1px solid #d8e4d6; border-radius: 8px; background: #f9fbf8; color: #294735; font-size: 12px; font-weight: 750; }
        .attention-link strong { display: grid; place-items: center; min-width: 26px; height: 26px; border-radius: 50%; background: #edf4e8; color: var(--admin-primary); }
        .attention-link.has-work { border-color: #e7c97f; background: #fffaf0; }
        .attention-link.has-work strong { background: #f5a623; color: #fff; }
        .filter-grid { display: grid; grid-template-columns: 1.5fr repeat(3, minmax(130px, .7fr)); gap: 10px; align-items: end; }
        .filter-grid.secondary { grid-template-columns: repeat(4, minmax(130px, 1fr)) auto; margin-top: 10px; }
        .field { display: grid; gap: 5px; min-width: 0; }
        .field label { color: #42594b; font-size: 11px; font-weight: 750; }
        .input, .select, .textarea { width: 100%; border: 1px solid #cad8ca; border-radius: 7px; padding: 9px 10px; background: #fff; color: var(--admin-ink); font: inherit; font-size: 12px; }
        .input, .select { min-height: 38px; }
        .textarea { min-height: 84px; resize: vertical; line-height: 1.5; }
        .input:focus, .select:focus, .textarea:focus { outline: 2px solid rgba(31, 122, 74, .16); border-color: var(--admin-primary); }
        .filter-actions, .page-actions, .action-row { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        .order-code { display: grid; gap: 3px; }
        .order-code a { color: var(--admin-primary); font-weight: 800; }
        .muted { color: var(--admin-subtle); font-size: 11px; line-height: 1.45; }
        .cell-stack { display: grid; gap: 5px; min-width: 125px; }
        .money { white-space: nowrap; font-weight: 800; }
        .attention-badges { display: flex; gap: 5px; flex-wrap: wrap; }
        .attention-badge { padding: 4px 7px; border-radius: 5px; background: #fff5d7; color: #815b00; font-size: 10px; font-weight: 800; }
        .attention-badge.return { background: #f0ecff; color: #5d3ca4; }
        .attention-badge.payment { background: #e9f5ff; color: #17658a; }
        .status-pill.partially_refunded { color: #755600; background: #fff3c8; }
        .status-pill.approved, .status-pill.completed, .status-pill.refunded { color: #28623a; background: #e9f7ed; }
        .status-pill.rejected { color: #963d2d; background: #fff0ec; }
        .admin-pager { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-top: 12px; color: var(--admin-subtle); font-size: 12px; }
        .admin-pager-actions { display: flex; gap: 7px; }
        .admin-pager a, .admin-pager .disabled { padding: 7px 10px; border: 1px solid var(--admin-line); border-radius: 7px; background: #fff; font-weight: 750; }
        .admin-pager .disabled { opacity: .45; }
        .order-detail-grid { display: grid; grid-template-columns: minmax(0, 1fr) 340px; gap: 14px; align-items: start; }
        .detail-main, .detail-side { display: grid; gap: 14px; min-width: 0; }
        .detail-side { position: sticky; top: 88px; }
        .detail-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .detail-list { display: grid; gap: 9px; }
        .detail-row { display: grid; grid-template-columns: 130px minmax(0, 1fr); gap: 10px; padding-bottom: 8px; border-bottom: 1px solid #edf1ea; font-size: 12px; }
        .detail-row:last-child { padding-bottom: 0; border-bottom: 0; }
        .detail-row span:first-child { color: var(--admin-subtle); }
        .item-cell { display: flex; align-items: center; gap: 9px; min-width: 240px; }
        .item-cell img { width: 54px; height: 54px; flex: 0 0 54px; border: 1px solid var(--admin-line); border-radius: 6px; object-fit: cover; background: #f4f7f2; }
        .summary-list { display: grid; gap: 8px; }
        .summary-row { display: flex; justify-content: space-between; gap: 12px; font-size: 12px; }
        .summary-row.total { margin-top: 4px; padding-top: 10px; border-top: 1px solid var(--admin-line); font-size: 16px; font-weight: 850; }
        .action-panel { display: grid; gap: 10px; }
        .action-panel h3 { margin: 0; font-size: 14px; }
        .action-panel form { display: grid; gap: 8px; }
        .action-panel .btn { justify-content: center; }
        .btn-danger { border-color: #efbdb3; background: #fff1ee; color: #9a3e2c; }
        .btn-danger:hover { border-color: #d88f80; background: #ffe7e1; }
        .notice { padding: 10px; border: 1px solid #dce8d7; border-radius: 7px; background: #f8fbf6; color: #405b46; font-size: 11px; line-height: 1.5; }
        .notice.warning { border-color: #efd398; background: #fff9eb; color: #715000; }
        .notice.danger { border-color: #efbdb3; background: #fff3f0; color: #8f3122; }
        .request-box { display: grid; gap: 8px; padding: 11px; border: 1px solid #d9e4d5; border-radius: 7px; background: #fff; }
        .request-head { display: flex; justify-content: space-between; gap: 8px; align-items: start; }
        .request-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .request-actions form { display: grid; gap: 7px; }
        .request-actions .wide { grid-column: 1 / -1; }
        .timeline { display: grid; gap: 0; }
        .timeline-item { position: relative; display: grid; grid-template-columns: 18px minmax(0, 1fr); gap: 10px; padding-bottom: 15px; }
        .timeline-item:not(:last-child)::before { content: ''; position: absolute; left: 7px; top: 16px; bottom: 0; width: 1px; background: #cadac9; }
        .timeline-dot { width: 15px; height: 15px; border: 4px solid #e8f3e5; border-radius: 50%; background: var(--admin-primary); }
        .timeline-body { display: grid; gap: 3px; font-size: 12px; }
        .timeline-body small { color: var(--admin-subtle); }
        @media (max-width: 1180px) { .status-grid { grid-template-columns: repeat(3, 1fr); } .filter-grid, .filter-grid.secondary { grid-template-columns: repeat(2, minmax(0, 1fr)); } .order-detail-grid { grid-template-columns: 1fr; } .detail-side { position: static; } }
        @media (max-width: 760px) { .status-grid, .attention-strip, .filter-grid, .filter-grid.secondary, .detail-grid-2 { grid-template-columns: 1fr; } .detail-row { grid-template-columns: 1fr; gap: 3px; } .request-actions { grid-template-columns: 1fr; } .request-actions .wide { grid-column: auto; } }
    </style>
@endsection
