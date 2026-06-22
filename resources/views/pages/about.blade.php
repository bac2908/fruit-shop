@extends('layouts.app')

@section('title', 'Giới thiệu - Thế Giới Trái Cây')

@section('content')
<section class="bread_crumb py-4">
    <div class="container">
        <div class="row">
            <div class="col-xs-12">
                <ul class="breadcrumb" itemscope itemtype="http://data-vocabulary.org/Breadcrumb">
                    <li class="home">
                        <a itemprop="url" href="{{ route('home') }}"><span itemprop="title">Trang chủ</span></a>
                        <span><i class="fa fa-angle-right"></i></span>
                    </li>
                    <li><strong itemprop="title">Giới thiệu</strong></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="page about-page-section">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-12">
                <div class="page-title category-title">
                    <h1 class="title-head"><a href="javascript:;">Giới thiệu</a></h1>
                </div>
                <div class="content-page rte about-content-box">
                    <p><strong>Thế giới trái cây (www.thegioitraicay.net) là website thương mại điện tử được sở hữu và vận hành bởi Nguyễn Văn Bắc, địa chỉ tại 74 Trần Thái Tông.</strong></p>

                    <p><strong>Là một trong những đơn vị cung cấp sỉ - lẻ Trái Cây Tươi, Thế giới trái cây có thế mạnh bán lẻ hơn 30 chủng loại trái cây tươi trong và ngoài nước. Cửa hàng luôn chú trọng nguồn hàng đa dạng, phong phú và trải nghiệm mua sắm thuận tiện cho khách hàng.</strong></p>

                    <p><strong>Sở hữu vị trí thuận lợi tại 74 Trần Thái Tông, cửa hàng tạo thuận tiện cho khách hàng trong việc mua sắm Trái Cây Tươi - Giỏ Quà Trái Cây cũng như các sản phẩm khác. Ngoài ra, khách hàng hoàn toàn an tâm với hình thức vận chuyển hàng tận nơi của chúng tôi theo khu vực giao hàng phù hợp.</strong></p>

                    <p><strong>Với nguồn hàng trái cây phong phú đa dạng được chọn lọc kỹ càng từ các đối tác uy tín lâu năm, tiêu biểu như những sản phẩm của Việt Nam: Xoài Cát Hòa Lộc, Bưởi Hồng Da Xanh, Chôm Chôm Nhãn cho đến những sản phẩm nhập khẩu từ NewZeland, Australlia, USA như Táo, Nho, Kiwi, Cherry... là một trong những lợi thế giúp chúng tôi đáp ứng hầu hết tất cả nhu cầu khách hàng về sản phẩm cũng như các dịch vụ liên quan đến Trái Cây Tươi.</strong></p>

                    <p><strong>Ngoài dòng sản phẩm trái cây thông dụng và đặc trưng, chúng tôi còn mang lại cho khách hàng nhiều lựa chọn hơn từ việc kết hợp các loại trái cây sẵn có để tạo nên "Giỏ Quà và Hộp quà" đẹp mắt sang trọng và chất lượng. Giỏ Quà Trái Cây thích hợp dùng làm quà tặng trong các dịp sinh nhật, họp mặt liên hoan, tiệc tân gia, cưới hỏi hoặc dùng làm quà biếu trong các dịp đám tang, phúng điếu...</strong></p>

                    <p><strong>Bên cạnh sản phẩm chính là mặt hàng Trái Cây Tươi, chúng tôi còn phục vụ các dịch vụ đi kèm như Hoa tươi, Rượu bia và bánh kẹo... theo yêu cầu nhằm mang đến sự thuận tiện cũng như tiết kiệm thời gian của quý khách hàng khi mua sắm tại website www.thegioitraicay.net.</strong></p>

                    <p><strong>Hy vọng với dịch vụ đa dạng của Thế giới trái cây quý khách hàng sẽ tìm được cho mình, gia đình người thân và bạn bè một nơi cung cấp Trái Cây - Giỏ Quà Trái Cây ưng ý và đáng tin cậy.</strong></p>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="section section-brand mb-5 mt-5 about-brand-section">
    <div class="container">
        <h2 class="hidden">Thương hiệu</h2>
        <div class="owl-carousel owl-theme" data-lg-items="6" data-md-items="5" data-sm-items="4" data-xs-items="3" data-xss-items="2" data-margin="30">
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
    body {
        background: #efefef;
    }

    .about-page-section .page-title {
        margin-bottom: 8px;
    }

    .about-page-section .title-head {
        margin: 0;
        font-size: 42px;
        line-height: 1.3;
        font-weight: 700;
        color: #2f2f2f;
    }

    .about-page-section .title-head a {
        color: inherit;
        text-decoration: none;
    }

    .about-page-section {
        padding-bottom: 35px;
    }

    .about-content-box {
        background: transparent;
        border: 0;
        border-radius: 0;
        padding: 0;
        box-shadow: none;
    }

    .about-content-box p {
        margin-bottom: 24px;
        color: #4a4a4a;
        line-height: 1.9;
        font-size: 15px;
        letter-spacing: 0.1px;
    }

    .about-content-box strong {
        font-weight: 600;
    }

    .about-content-box p:last-child {
        margin-bottom: 0;
    }

    .about-brand-section {
        margin-top: 24px;
    }

    .about-brand-section .brand-item {
        padding: 8px;
    }

    .about-brand-section .brand-item img {
        width: 100%;
        max-width: 140px;
        margin: 0 auto;
        display: block;
        opacity: 0.9;
        transition: all 0.25s ease;
    }

    .about-brand-section .brand-item img:hover {
        opacity: 1;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .about-page-section .title-head {
            font-size: 34px;
        }

        .about-content-box p {
            font-size: 14px;
            line-height: 1.8;
            margin-bottom: 18px;
        }
    }
</style>
@endpush
