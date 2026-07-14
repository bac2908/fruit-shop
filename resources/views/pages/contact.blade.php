@extends('layouts.app')

@section('title', 'Liên hệ - Thế Giới Trái Cây')

@section('content')
<section class="bread_crumb py-4">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <ul class="breadcrumb" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
                    <li class="home">
                        <a itemprop="url" href="{{ route('home') }}"><span itemprop="title">Trang chủ</span></a>
                        <span> <i class="fa fa-angle-right"></i> </span>
                    </li>
                    <li><strong itemprop="title">Liên hệ</strong></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<div class="container contact-page-wrap margin-bottom-30">
    <div class="box-heading hidden relative">
        <h1 class="title-head">Liên hệ</h1>
    </div>

    <h2 class="title-head contact-title"><span>Gửi tin nhắn cho chúng tôi</span></h2>

    <div class="row">
        <div class="col-sm-12">
            <div class="row">
                <div class="col-md-6">
                    <div class="contact-form-box">
                        <form class="contact-form" method="post" action="{{ route('contact.submit') }}">
                            @csrf
                            <input type="hidden" name="contact_token" value="{{ $contactFormToken }}">
                            <div class="contact-honeypot" aria-hidden="true">
                                <label for="contact-website">Website</label>
                                <input id="contact-website" type="text" name="website" value="" tabindex="-1" autocomplete="off">
                            </div>
                            <p class="error-fills"></p>

                            <div class="form-signup form_contact clearfix">
                                <div class="row row-8Gutter">
                                    <div class="col-md-12">
                                        <fieldset class="form-group">
                                            <input type="text" name="name" value="{{ old('name') }}" placeholder="Họ tên*" class="form-control form-control-lg @error('name') is-invalid @enderror" required>
                                            @error('name')
                                                <small class="contact-field-error">{{ $message }}</small>
                                            @enderror
                                        </fieldset>
                                    </div>
                                    <div class="col-md-12">
                                        <fieldset class="form-group">
                                            <input type="email" name="email" value="{{ old('email') }}" placeholder="Email*" class="form-control form-control-lg @error('email') is-invalid @enderror" required>
                                            @error('email')
                                                <small class="contact-field-error">{{ $message }}</small>
                                            @enderror
                                        </fieldset>
                                    </div>
                                    <div class="col-md-12">
                                        <fieldset class="form-group">
                                            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="Điện thoại" class="form-control form-control-lg @error('phone') is-invalid @enderror">
                                            @error('phone')
                                                <small class="contact-field-error">{{ $message }}</small>
                                            @enderror
                                        </fieldset>
                                    </div>
                                </div>

                                <fieldset class="form-group">
                                    <textarea name="message" placeholder="Nhập nội dung*" class="form-control form-control-lg @error('message') is-invalid @enderror" rows="6" required>{{ old('message', $suggestedMessage ?? '') }}</textarea>
                                    @error('message')
                                        <small class="contact-field-error">{{ $message }}</small>
                                    @enderror
                                </fieldset>

                                <div>
                                    <button type="submit" class="btn btn-primary btn-send-contact">Gửi liên hệ</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="contact-box-info clearfix margin-bottom-30">
                        <div class="item">
                            <div class="info-line">
                                <i class="fa fa-map-marker"></i>
                                <div class="info">
                                    <label>Địa chỉ liên hệ</label>
                                    74 Trần Thái Tông
                                </div>
                            </div>

                            <div class="info-line">
                                <i class="fa fa-phone"></i>
                                <div class="info">
                                    <label>Số điện thoại</label>
                                    <a href="tel:0333499426">0333499426</a>
                                    <p>Thứ 2 - Chủ nhật: 7:00 - 21:00</p>
                                </div>
                            </div>

                            <div class="info-line">
                                <i class="fa fa-envelope"></i>
                                <div class="info">
                                    <label>Email</label>
                                    <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-12">
            <div class="box-maps margin-bottom-30">
                <div class="iFrameMap">
                    <div class="google-map">
                        <iframe src="https://www.google.com/maps?q=74%20Tr%E1%BA%A7n%20Th%C3%A1i%20T%C3%B4ng&output=embed" width="100%" height="450" frameborder="0" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section section-brand mb-5 mt-5 contact-brand-section">
    <div class="container">
        <h2 class="hidden">Thương hiệu</h2>
        <div class="contact-brand-list">
            <div class="brand-item">
                <a href="javascript:;" class="img25"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand1.png?v=1064" alt="Thế Giới Trái Cây"></a>
            </div>
            <div class="brand-item">
                <a href="javascript:;" class="img25"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand2.png?v=1064" alt="Thế Giới Trái Cây"></a>
            </div>
            <div class="brand-item">
                <a href="javascript:;" class="img25"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand3.png?v=1064" alt="Thế Giới Trái Cây"></a>
            </div>
            <div class="brand-item">
                <a href="javascript:;" class="img25"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand4.png?v=1064" alt="Thế Giới Trái Cây"></a>
            </div>
            <div class="brand-item">
                <a href="javascript:;" class="img25"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand5.png?v=1064" alt="Thế Giới Trái Cây"></a>
            </div>
            <div class="brand-item">
                <a href="javascript:;" class="img25"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand6.png?v=1064" alt="Thế Giới Trái Cây"></a>
            </div>
            <div class="brand-item">
                <a href="javascript:;" class="img25"><img src="//theme.hstatic.net/200000157781/1001036201/14/brand7.png?v=1064" alt="Thế Giới Trái Cây"></a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .contact-honeypot { position: absolute !important; left: -10000px !important; width: 1px !important; height: 1px !important; overflow: hidden !important; }
    body {
        background: #efefef;
    }

    .contact-page-wrap {
        max-width: 980px;
    }

    .contact-title {
        font-size: 22px;
        font-weight: 700;
        margin: 0 0 12px;
        color: #2f2f2f;
    }

    .contact-form-box .form-group {
        margin-bottom: 10px;
    }

    .contact-form-box .form-control {
        height: 34px;
        border-radius: 3px;
        border: 1px solid #ddd;
        box-shadow: none;
        font-size: 13px;
        color: #555;
        background: #fff;
    }

    .contact-form-box .form-control.is-invalid {
        border-color: #e85c4a;
    }

    .contact-field-error {
        color: #c0392b;
        display: block;
        font-size: 12px;
        margin-top: 4px;
    }

    .contact-form-box textarea.form-control {
        height: auto;
        min-height: 95px;
        resize: vertical;
    }

    .btn-send-contact {
        padding: 0 38px;
        height: 34px;
        border-radius: 3px;
        font-size: 13px;
        text-transform: inherit;
        background: #80ba3f;
        border-color: #80ba3f;
    }

    .btn-send-contact:hover {
        background: #73ab37;
        border-color: #73ab37;
    }

    .contact-box-info .item {
        padding-left: 6px;
    }

    .contact-box-info .info-line {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 14px;
    }

    .contact-box-info .info-line i {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #80ba3f;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .contact-box-info .info {
        color: #555;
        font-size: 13px;
        line-height: 1.6;
    }

    .contact-box-info .info label {
        display: block;
        margin: 0 0 1px;
        font-size: 13px;
        font-weight: 700;
        color: #2f2f2f;
    }

    .contact-box-info .info a {
        color: #f7941e;
        text-decoration: none;
    }

    .contact-box-info .info p {
        margin: 0;
        color: #555;
    }

    .box-maps {
        margin-top: 8px;
    }

    .google-map {
        width: 100%;
        background: #dedede;
    }

    .contact-brand-section {
        margin-top: 18px;
    }

    .contact-brand-list {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 24px;
        flex-wrap: wrap;
    }

    .contact-brand-list .brand-item img {
        max-height: 42px;
        width: auto;
        display: block;
        opacity: 0.95;
    }

    .contact-brand-list .brand-item img:hover {
        opacity: 1;
    }

    @media (max-width: 991px) {
        .contact-box-info {
            margin-top: 16px;
        }

        .contact-brand-list {
            gap: 16px;
        }

        .contact-brand-list .brand-item img {
            max-height: 36px;
        }
    }
</style>
@endpush
