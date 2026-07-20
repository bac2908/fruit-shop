@section('head')
    <style>
        .page-kicker { margin: 0 0 6px; color: var(--admin-primary); font-size: 12px; font-weight: 800; text-transform: uppercase; }
        .page-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .admin-alert { display: grid; gap: 4px; margin-bottom: 14px; padding: 12px 14px; border: 1px solid; border-radius: 8px; font-size: 13px; }
        .admin-alert.success { color: #245f35; border-color: #bfe3c8; background: #edf9f0; }
        .admin-alert.error { color: #8f3122; border-color: #f0bcb1; background: #fff1ee; }
        .editor-layout { display: grid; grid-template-columns: minmax(0, 1fr) 300px; gap: 14px; align-items: start; }
        .editor-main { display: grid; gap: 14px; min-width: 0; }
        .form-panel { border-radius: 8px; box-shadow: 0 8px 22px rgba(23, 52, 37, .05); }
        .form-grid { display: grid; gap: 12px; }
        .form-grid.two-columns { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .form-grid.three-columns { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .span-2 { grid-column: 1 / -1; }
        .span-3 { grid-column: 1 / -1; }
        .field { display: grid; gap: 6px; min-width: 0; }
        .field label { color: #42594b; font-size: 12px; font-weight: 700; }
        .field label span { color: var(--admin-danger); }
        .input, .select, .textarea { width: 100%; border: 1px solid #cad8ca; border-radius: 7px; padding: 10px 11px; background: #fff; color: var(--admin-ink); font: inherit; font-size: 13px; }
        .textarea { min-height: 180px; resize: vertical; line-height: 1.55; }
        .textarea.compact { min-height: 88px; }
        .input:focus, .select:focus, .textarea:focus { outline: 2px solid rgba(31, 122, 74, .18); border-color: var(--admin-primary); }
        .input.invalid, .select.invalid, .textarea.invalid { border-color: var(--admin-danger); }
        .field-help { color: var(--admin-subtle); font-size: 11px; }
        .field-error { display: block; color: var(--admin-danger); font-size: 11px; font-weight: 700; }
        .sticky-panel { position: sticky; top: 88px; display: grid; gap: 13px; }
        .check-card { display: flex; align-items: flex-start; gap: 10px; padding: 11px; border: 1px solid var(--admin-line); border-radius: 7px; background: #fff; cursor: pointer; }
        .check-card input { margin-top: 3px; }
        .check-card strong, .check-card small { display: block; }
        .check-card strong { font-size: 13px; }
        .check-card small { margin-top: 4px; color: var(--admin-subtle); font-size: 11px; line-height: 1.45; }
        .save-actions { display: grid; grid-template-columns: 1fr auto; gap: 8px; padding-top: 4px; }
        .save-actions .btn { justify-content: center; }
        .record-meta { display: grid; gap: 4px; padding-top: 12px; border-top: 1px solid var(--admin-line); color: var(--admin-subtle); font-size: 11px; }
        .upload-box { display: grid; gap: 8px; padding: 14px; border: 1px dashed #a9c4ac; border-radius: 7px; background: #f8fbf7; }
        .upload-box label { color: var(--admin-primary); font-size: 13px; font-weight: 800; }
        .upload-box small { color: var(--admin-subtle); font-size: 11px; }
        .image-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
        .image-item { overflow: hidden; border: 1px solid var(--admin-line); border-radius: 7px; background: #fff; }
        .image-item > img { display: block; width: 100%; aspect-ratio: 1; object-fit: cover; background: #f5f7f3; }
        .image-controls { display: grid; grid-template-columns: auto 70px; gap: 7px; align-items: center; padding: 8px; font-size: 11px; }
        .image-controls .input { padding: 7px; }
        .check-line { display: flex; align-items: center; gap: 6px; grid-column: 1 / -1; font-weight: 700; cursor: pointer; }
        .danger-check { color: var(--admin-danger); }
        @media (max-width: 1100px) { .editor-layout { grid-template-columns: 1fr; } .sticky-panel { position: static; } }
        @media (max-width: 760px) { .form-grid.two-columns, .form-grid.three-columns { grid-template-columns: 1fr; } .span-2, .span-3 { grid-column: auto; } .image-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } .save-actions { grid-template-columns: 1fr; } }
    </style>
@endsection
