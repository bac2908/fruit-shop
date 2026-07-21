@extends('layouts.admin')
@section('title', 'Sửa '.$coupon->code.' | FruitShop Admin')
@section('head') @include('admin.coupons._styles') @endsection
@section('admin_content')
    <section class="page-head"><div><h1 class="page-title">Chỉnh sửa {{ $coupon->code }}</h1><p class="page-subtitle">Các điều kiện tài chính sẽ được khóa sau lượt sử dụng đầu tiên.</p></div><a class="btn btn-ghost" href="{{ route('admin.coupons.show',$coupon) }}"><i class="ri-eye-line"></i>Chi tiết</a></section>
    @include('admin.coupons._errors')
    <form class="coupon-form-shell" method="post" action="{{ route('admin.coupons.update',$coupon) }}">@csrf @method('PUT') @include('admin.coupons._form',['submitLabel'=>'Lưu thay đổi'])</form>
@endsection
@section('scripts') @include('admin.coupons._form_script') @endsection
