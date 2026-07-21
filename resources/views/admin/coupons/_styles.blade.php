<style>
    .coupon-stats { display:grid; grid-template-columns:repeat(5,minmax(0,1fr)); gap:10px; margin-bottom:14px; }
    .coupon-stat { display:block; padding:14px; border:1px solid var(--admin-line); border-radius:8px; background:#fff; color:inherit; }
    .coupon-stat:hover { border-color:#9bc7aa; }
    .coupon-stat span { display:block; color:var(--admin-subtle); font-size:12px; margin-bottom:5px; }
    .coupon-stat strong { color:var(--admin-ink); font:700 23px 'Sora',sans-serif; }
    .coupon-alert { margin-bottom:14px; padding:12px 14px; border:1px solid; border-radius:8px; font-weight:700; }
    .coupon-alert.success { color:#1d6b39; background:#edf8ef; border-color:#c8e6cf; }
    .coupon-alert.danger { color:#a93622; background:#fff1ee; border-color:#efc2b8; }
    .coupon-filter { margin-bottom:14px; padding:14px; border:1px solid var(--admin-line); border-radius:8px; background:#fff; }
    .coupon-filter-grid { display:grid; grid-template-columns:minmax(210px,1.5fr) repeat(4,minmax(120px,.7fr)) auto; gap:10px; align-items:end; }
    .coupon-field { display:grid; gap:5px; min-width:0; }
    .coupon-field.full { grid-column:1 / -1; }
    .coupon-field.span-2 { grid-column:span 2; }
    .coupon-field label { color:var(--admin-subtle); font-size:12px; font-weight:800; }
    .coupon-input,.coupon-select,.coupon-textarea { width:100%; border:1px solid var(--admin-line); border-radius:8px; background:#fff; color:var(--admin-ink); font:inherit; }
    .coupon-input,.coupon-select { height:40px; padding:0 10px; }
    .coupon-textarea { min-height:90px; padding:10px; resize:vertical; }
    .coupon-input:focus,.coupon-select:focus,.coupon-textarea:focus { border-color:#3d8a59; outline:2px solid rgba(61,138,89,.12); }
    .coupon-actions,.coupon-row-actions { display:flex; align-items:center; flex-wrap:wrap; gap:8px; }
    .coupon-row-actions form { margin:0; }
    .coupon-code { color:var(--admin-primary); font:800 13px 'Sora',sans-serif; }
    .coupon-muted { color:var(--admin-subtle); font-size:12px; line-height:1.5; }
    .coupon-status { display:inline-flex; align-items:center; gap:5px; min-height:27px; padding:0 9px; border-radius:999px; font-size:11px; font-weight:800; white-space:nowrap; }
    .coupon-status.active { color:#176237; background:#e9f7ee; }
    .coupon-status.scheduled { color:#735200; background:#fff5ce; }
    .coupon-status.expired,.coupon-status.exhausted,.coupon-status.unavailable { color:#a33c29; background:#fff0ed; }
    .coupon-status.inactive,.coupon-status.archived { color:#5f6870; background:#eef0f1; }
    .coupon-type { display:inline-flex; padding:4px 7px; border:1px solid var(--admin-line); border-radius:6px; font-size:11px; font-weight:700; }
    .coupon-form-shell { max-width:1080px; }
    .coupon-form-section { margin-bottom:14px; padding:18px; border:1px solid var(--admin-line); border-radius:8px; background:#fff; }
    .coupon-form-section h2 { margin:0 0 4px; font:700 17px 'Sora',sans-serif; }
    .coupon-form-section > p { margin:0 0 15px; color:var(--admin-subtle); font-size:13px; }
    .coupon-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .coupon-checks { display:flex; align-items:center; flex-wrap:wrap; gap:18px; padding-top:8px; }
    .coupon-check { display:flex; align-items:center; gap:8px; font-weight:700; }
    .coupon-help { color:var(--admin-subtle); font-size:11px; line-height:1.5; }
    .coupon-lock-note { margin-bottom:14px; padding:11px 13px; border-left:3px solid #e2a51a; background:#fff9e9; color:#6b5214; font-size:13px; }
    .coupon-detail-grid { display:grid; grid-template-columns:minmax(0,1.5fr) minmax(280px,.7fr); gap:14px; margin-bottom:14px; }
    .coupon-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    .coupon-summary div { padding-bottom:10px; border-bottom:1px solid var(--admin-line); }
    .coupon-summary span { display:block; color:var(--admin-subtle); font-size:11px; margin-bottom:4px; }
    .coupon-summary strong { color:var(--admin-ink); font-size:14px; }
    .assignment-grid { display:grid; gap:10px; }
    .coupon-radio-group { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
    .coupon-radio { display:flex; align-items:flex-start; gap:8px; padding:10px; border:1px solid var(--admin-line); border-radius:8px; cursor:pointer; }
    .coupon-radio strong,.coupon-radio small { display:block; }
    .coupon-radio small { margin-top:3px; color:var(--admin-subtle); }
    .coupon-pager { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:12px 14px; border-top:1px solid var(--admin-line); font-size:12px; }
    .coupon-pager div { display:flex; gap:8px; }
    .coupon-pager a,.coupon-pager span { padding:6px 9px; border:1px solid var(--admin-line); border-radius:6px; }
    .coupon-pager .disabled { color:#9aa1a5; background:#f4f5f5; }
    .coupon-empty { padding:24px; text-align:center; color:var(--admin-subtle); }
    .coupon-danger { color:#a93622 !important; }
    @media(max-width:1150px){ .coupon-stats{grid-template-columns:repeat(3,1fr)} .coupon-filter-grid{grid-template-columns:repeat(3,1fr)} }
    @media(max-width:820px){ .coupon-stats{grid-template-columns:repeat(2,1fr)} .coupon-filter-grid,.coupon-form-grid,.coupon-detail-grid{grid-template-columns:1fr} .coupon-field.span-2{grid-column:auto} }
    @media(max-width:520px){ .coupon-stats,.coupon-radio-group{grid-template-columns:1fr} }
</style>
