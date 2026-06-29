<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public function frontpage()
    {
        return $this->render('frontpage');
    }

    public function faq()
    {
        return $this->render('cau-hoi-thuong-gap');
    }

    public function privacyPolicy()
    {
        return $this->render('chinh-sach-bao-mat');
    }

    public function privacyInfo()
    {
        return $this->render('chinh-sach-bao-mat-thong-tin');
    }

    public function returnPolicy()
    {
        return $this->render('chinh-sach-doi-tra');
    }

    public function shippingPaymentPolicy()
    {
        return $this->render('chinh-sach-giao-hang-va-thanh-toan');
    }

    public function termsOfService()
    {
        return $this->render('dieu-khoan-dich-vu');
    }

    public function corporateCustomers()
    {
        return $this->render('khach-hang-doanh-nghiep');
    }

    private function render(string $slug)
    {
        $pages = $this->pageContent();

        abort_unless(isset($pages[$slug]), 404);

        return view('pages.static', [
            'page' => $pages[$slug],
        ]);
    }

    private function pageContent(): array
    {
        return [
            'frontpage' => [
                'title' => 'Trang chủ',
                'intro' => 'Thế Giới Trái Cây cung cấp trái cây tươi, giỏ quà trái cây và dịch vụ giao nhanh trong ngày tại TP.HCM.',
                'sections' => [
                    [
                        'heading' => 'Danh mục nổi bật',
                        'paragraphs' => [
                            'Trái cây Việt Nam loại 1, trái cây nhập khẩu tuyển chọn, giỏ quà theo ngân sách, mâm dĩa ngũ quả và set quà doanh nghiệp.',
                        ],
                        'list' => [
                            'Trái cây theo mùa, hàng vào mới mỗi ngày.',
                            'Giỏ quà và hộp quà thiết kế theo yêu cầu.',
                            'Hỗ trợ in thiệp, xuất hoa don và giao theo khung giờ.',
                        ],
                    ],
                    [
                        'heading' => 'Cam kết dịch vụ',
                        'paragraphs' => [
                            'Chúng tôi ưu tiên trái cây đúng chuẩn, hình ảnh thật, tư vấn đúng nhu cầu và hỗ trợ đổi trả minh bạch.',
                        ],
                        'list' => [
                            'Kiểm tra chất lượng trước khi giao.',
                            'Đội ngũ chăm sóc hỗ trợ 7:00 - 21:00.',
                            'Giao nhanh nội thành, đóng gói cẩn thận.',
                        ],
                    ],
                ],
                'actions' => [
                    [
                        'label' => 'Xem tất cả sản phẩm',
                        'url' => route('products.index'),
                    ],
                    [
                        'label' => 'Liên hệ tư vấn',
                        'url' => route('contact.page'),
                    ],
                ],
            ],
            'cau-hoi-thuong-gap' => [
                'title' => 'Câu hỏi thường gặp',
                'intro' => 'Một số câu hỏi khách hàng thường gặp khi mua trái cây tươi, giỏ quà và sử dụng dịch vụ giao hàng.',
                'faqs' => [
                    [
                        'q' => 'Thời gian giao hàng bao lâu?',
                        'a' => 'Nội thành TP.HCM thường từ 20-120 phút tùy khu vực và khung giờ. Các khu vực xa hoặc tỉnh thành sẽ có thời gian giao theo đơn vị vận chuyển.',
                    ],
                    [
                        'q' => 'Tôi có thể hẹn giờ giao không?',
                        'a' => 'Có. Bạn có thể ghi chú khung giờ mong muốn trong bước checkout hoặc liên hệ hotline để được hỗ trợ xác nhận.',
                    ],
                    [
                        'q' => 'Giỏ quà có thể thay đổi loại trái cây không?',
                        'a' => 'Có. Chúng tôi hỗ trợ thay thế theo ngân sách, tông màu và mục đích biếu tặng (sinh nhật, khai trương, thăm bệnh, phung vieng...).',
                    ],
                    [
                        'q' => 'Làm sao áp mã khuyến mãi?',
                        'a' => 'Tại trang giỏ hàng, nhập mã coupon vào ô Mã giảm giá rồi bấm Áp dụng. Hệ thống tự tính mức giảm hợp lệ theo điều kiện mã.',
                    ],
                    [
                        'q' => 'Có xuất hoa don cho doanh nghiệp không?',
                        'a' => 'Có. Vui lòng cung cấp thông tin xuất hoa don tại bước checkout hoặc ở phần ghi chú đơn hàng.',
                    ],
                    [
                        'q' => 'Nếu sản phẩm giao không đúng chất lượng thì sao?',
                        'a' => 'Bạn vui lòng phản hồi sớm qua hotline kèm hình ảnh. Chúng tôi hỗ trợ đổi trả theo chính sách đổi trả hiện hành.',
                    ],
                ],
            ],
            'chinh-sach-bao-mat' => [
                'title' => 'Chính sách bảo mật',
                'intro' => 'Chính sách này mô tả cách Thế Giới Trái Cây thu thập, sử dụng và bảo vệ dữ liệu khi khách hàng truy cập và mua sắm.',
                'sections' => [
                    [
                        'heading' => 'Dữ liệu thu thập',
                        'paragraphs' => [
                            'Chúng tôi có thể thu thập thông tin cơ bản như họ tên, số điện thoại, email, địa chỉ giao hàng và lịch sử đơn hàng để phục vụ giao dịch.',
                        ],
                        'list' => [
                            'Thông tin nhận hàng và liên hệ.',
                            'Nội dung đơn hàng, mã giảm giá đã sử dụng.',
                            'Dữ liệu kỹ thuật cơ bản để cải thiện trải nghiệm.',
                        ],
                    ],
                    [
                        'heading' => 'Mục đích sử dụng',
                        'paragraphs' => [
                            'Thông tin được dùng để xác nhận đơn, giao hàng, chăm sóc sau bán và gửi thông báo liên quan đến dịch vụ.',
                        ],
                    ],
                    [
                        'heading' => 'Bảo vệ thông tin',
                        'paragraphs' => [
                            'Chúng tôi áp dụng biện pháp kỹ thuật và quy trình nội bộ phù hợp để hạn chế truy cập trái phép, lộ lọt hoặc sử dụng sai mục đích.',
                        ],
                    ],
                ],
            ],
            'chinh-sach-bao-mat-thong-tin' => [
                'title' => 'Chính sách bảo mật thông tin',
                'intro' => 'Chi tiết về việc lưu trữ, quyền truy cập và thời gian xử lý dữ liệu cá nhân của khách hàng.',
                'sections' => [
                    [
                        'heading' => 'Phạm vi sử dụng thông tin',
                        'paragraphs' => [
                            'Thông tin cá nhân chỉ dùng cho các hoạt động liên quan đến đơn hàng, chăm sóc khách hàng và cải thiện chất lượng dịch vụ.',
                        ],
                    ],
                    [
                        'heading' => 'Thời gian lưu trữ',
                        'paragraphs' => [
                            'Dữ liệu được lưu trữ trong thời gian cần thiết để phục vụ giao dịch, hậu mãi và tuân thủ quy định pháp luật hiện hành.',
                        ],
                    ],
                    [
                        'heading' => 'Đối tượng được tiếp cận',
                        'paragraphs' => [
                            'Chỉ nhân sự, bộ phận liên quan hoặc đơn vị vận chuyển/đối tác dịch vụ có liên quan trực tiếp đến việc xử lý đơn hàng mới được phép tiếp cận dữ liệu cần thiết.',
                        ],
                        'list' => [
                            'Bộ phận bán hàng và chăm sóc khách hàng.',
                            'Bộ phận vận hành giao nhận.',
                            'Đơn vị kỹ thuật hệ thống khi cần thiết.',
                        ],
                    ],
                ],
            ],
            'chinh-sach-doi-tra' => [
                'title' => 'Chính sách đổi trả',
                'intro' => 'Chúng tôi hỗ trợ đổi trả khi sản phẩm gặp vấn đề chất lượng hoặc giao sai thông tin so với xác nhận đơn.',
                'sections' => [
                    [
                        'heading' => 'Điều kiện đổi trả',
                        'paragraphs' => [
                            'Sản phẩm được xem xét đổi trả khi có lỗi do vận chuyển, dập hư hỏng nặng, giao sai loại hoặc sai số lượng đáng kể.',
                        ],
                        'list' => [
                            'Phản hồi trong vòng 2 giờ từ thời điểm nhận hàng đối với nội thành.',
                            'Giữ nguyên hiện trạng sản phẩm và bao bì khi có thể.',
                            'Cung cấp ảnh/video để xác minh nhanh.',
                        ],
                    ],
                    [
                        'heading' => 'Hình thức xử lý',
                        'paragraphs' => [
                            'Tùy trường hợp, chúng tôi sẽ đổi sản phẩm tương đương, hoàn tiền một phần hoặc hoàn tiền toàn bộ theo thỏa thuận.',
                        ],
                    ],
                    [
                        'heading' => 'Trường hợp không áp dụng',
                        'paragraphs' => [
                            'Không áp dụng đổi trả với trường hợp sản phẩm đã sử dụng phần lớn, bảo quản sai hướng dẫn hoặc thông tin phản hồi quá thời gian quy định.',
                        ],
                    ],
                ],
            ],
            'chinh-sach-giao-hang-va-thanh-toan' => [
                'title' => 'Chính sách giao hàng và thanh toán',
                'intro' => 'Thông tin phương thức giao hàng, khu vực phục vụ và các hình thức thanh toán đang hỗ trợ.',
                'sections' => [
                    [
                        'heading' => 'Phạm vi và thời gian giao hàng',
                        'paragraphs' => [
                            'Hàng tươi được ưu tiên giao nhanh tại TP.HCM. Các đơn ngoài TP.HCM sẽ được shop kiểm tra khả năng đóng gói, tuyến vận chuyển và thời gian nhận hàng trước khi xử lý.',
                        ],
                        'list' => [
                            'TP.HCM: giao nhanh dự kiến 30 - 90 phút tùy khu vực và thời điểm đặt hàng.',
                            'Tỉnh/Thành khác: phí trên website là tạm tính, shop sẽ xác nhận lại trước khi giao.',
                            'Khu vực đặc biệt/đặc khu/hải đảo: cần shop xác nhận riêng về phí và thời gian.',
                            'Đơn cao điểm lễ/tết có thể cần xác nhận thêm.',
                        ],
                    ],
                    [
                        'heading' => 'Phí giao hàng',
                        'paragraphs' => [
                            'Phí giao hàng tại TP.HCM được chốt tự động theo khu vực và có thể miễn phí khi đơn đạt điều kiện khuyến mãi.',
                            'Với đơn giao tỉnh, phí hiển thị là tạm tính để khách tham khảo. Shop sẽ xác nhận phí cuối cùng, phương án đóng gói và thời gian giao trước khi xử lý đơn hàng.',
                        ],
                    ],
                    [
                        'heading' => 'Phương thức thanh toán',
                        'paragraphs' => [
                            'Chấp nhận thanh toán tiền mặt khi nhận hàng (COD), chuyển khoản ngân hàng và các hình thức trực tuyến theo từng thời điểm triển khai.',
                        ],
                    ],
                ],
            ],
            'dieu-khoan-dich-vu' => [
                'title' => 'Điều khoản dịch vụ',
                'intro' => 'Khi truy cập và đặt hàng trên website, bạn đồng ý với các điều khoản sử dụng dịch vụ được công bố tại đây.',
                'sections' => [
                    [
                        'heading' => 'Quy định chung',
                        'paragraphs' => [
                            'Website cung cấp thông tin sản phẩm và dịch vụ đặt hàng trực tuyến. Người dùng cần cung cấp thông tin chính xác khi giao dịch.',
                        ],
                    ],
                    [
                        'heading' => 'Giá và tồn kho',
                        'paragraphs' => [
                            'Giá bán và tồn kho có thể thay đổi theo thời điểm. Chúng tôi sẽ xác nhận lại trong trường hợp phát sinh chênh lệch trước khi hoàn tất đơn hàng.',
                        ],
                    ],
                    [
                        'heading' => 'Giới hạn trách nhiệm',
                        'paragraphs' => [
                            'Chúng tôi nỗ lực đảm bảo thông tin chính xác, tuy nhiên không chịu trách nhiệm cho gián đoạn do yếu tố khách quan như sự cố mạng hoặc dịch vụ bên thứ ba.',
                        ],
                    ],
                ],
            ],
            'khach-hang-doanh-nghiep' => [
                'title' => 'Khách hàng doanh nghiệp',
                'intro' => 'Giải pháp quà tặng trái cây cho doanh nghiệp theo ngân sách, theo chiến dịch và theo bộ nhận diện thương hiệu.',
                'sections' => [
                    [
                        'heading' => 'Dịch vụ dành cho doanh nghiệp',
                        'paragraphs' => [
                            'Chúng tôi tư vấn gói quà theo mục tiêu sử dụng: tri ân khách hàng, quà tết nhân viên, đối tác chiến lược, sự kiện nội bộ hoặc truyền thông thương hiệu.',
                        ],
                        'list' => [
                            'Set quà theo mức ngân sách linh hoạt.',
                            'Thiệp thương hiệu, in logo, đóng gói theo tone màu.',
                            'Lập kế hoạch giao hàng nhiều điểm nhận.',
                        ],
                    ],
                    [
                        'heading' => 'Quy trình triển khai',
                        'paragraphs' => [
                            'Từ nhu cầu ban đầu, chúng tôi sẽ đề xuất mẫu, duyệt ngân sách, xác nhận thời gian giao và theo dõi chất lượng từng lô giao.',
                        ],
                    ],
                    [
                        'heading' => 'Liên hệ hợp tác',
                        'paragraphs' => [
                            'Vui lòng liên hệ hotline hoặc trang Liên hệ để nhận báo giá nhanh cho đơn số lượng lớn và dự án theo mùa.',
                        ],
                    ],
                ],
                'actions' => [
                    [
                        'label' => 'Yêu cầu tư vấn doanh nghiệp',
                        'url' => route('contact.page'),
                    ],
                    [
                        'label' => 'Xem giỏ quà hiện có',
                        'url' => route('products.index', ['category' => 'gio-qua-va-set-qua']),
                    ],
                ],
            ],
        ];
    }
}
