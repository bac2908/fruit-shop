@extends('layouts.admin')

@section('title', 'Đổi mật khẩu quản trị | FruitShop Admin')

@section('head')
<style>
    .settings-input { width:100%; height:44px; margin-top:6px; border:1px solid var(--admin-line); border-radius:8px; padding:0 11px; background:#fff; color:var(--admin-ink); font:inherit; }
</style>
@endsection

@section('admin_content')
<section class="page-head">
    <div><h1 class="page-title">Đổi mật khẩu quản trị</h1><p class="page-subtitle">Mật khẩu cần tối thiểu 12 ký tự, có chữ hoa, chữ thường, số và ký tự đặc biệt.</p></div>
</section>
<section class="panel" style="max-width:620px">
    @if($errors->any())<div style="padding:12px;margin-bottom:14px;border:1px solid #f1b1a5;background:#fff1ee;color:#a33724;border-radius:7px">{{ $errors->first() }}</div>@endif
    <form method="post" action="{{ route('admin.security.password.update') }}" style="display:grid;gap:13px">
        @csrf @method('PUT')
        <label>Mật khẩu hiện tại<input class="settings-input" type="password" name="current_password" required autocomplete="current-password"></label>
        <label>Mật khẩu mới<input class="settings-input" type="password" name="password" required autocomplete="new-password"></label>
        <label>Nhập lại mật khẩu mới<input class="settings-input" type="password" name="password_confirmation" required autocomplete="new-password"></label>
        <button class="btn btn-primary" type="submit"><i class="ri-lock-password-line"></i>Cập nhật mật khẩu</button>
    </form>
</section>
@endsection
