@extends('layouts.admin')
@section('title', 'Tạo voucher | FruitShop Admin')
@section('head') @include('admin.coupons._styles') @endsection
@section('admin_content')
    <section class="page-head"><div><h1 class="page-title">Tạo voucher</h1><p class="page-subtitle">Thiết lập quyền lợi, điều kiện và phạm vi trước khi phát cho khách.</p></div></section>
    @include('admin.coupons._errors')
    <form class="coupon-form-shell" method="post" action="{{ route('admin.coupons.store') }}">@csrf @include('admin.coupons._form',['submitLabel'=>'Tạo voucher'])</form>
@endsection
@section('scripts') @include('admin.coupons._form_script') @endsection
