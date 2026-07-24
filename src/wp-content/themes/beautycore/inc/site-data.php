<?php
if (!defined('ABSPATH')) {
    exit;
}

function beautycore_site_config() {
    static $config;

    if (!$config) {
        $config = array(
            'name'          => 'Beauty Core',
            'website'       => 'https://beautycore.io.vn',
            'slogan'        => 'Nơi Tâm Hồn Được Lắng Nghe Và An Yên',
            'description'   => 'Gội đầu thư giãn, massage và chăm sóc cơ thể trong không gian thư thái tại Beauty Core.',
            'phone'         => '0387972769',
            'phone_display' => '0387 972 769',
            'zalo_url'      => 'https://zalo.me/0387972769',
            'email'         => 'Dhiuvietnam@gmail.com',
            'facebook_url'  => 'https://www.facebook.com/beautycore',
            'booking_url'   => 'https://booking.easysalon.vn/beautycore',
            'address'       => '281/31/11 Lê Văn Sỹ, Phường Tân Sơn Hòa, TP. Hồ Chí Minh',
            'google_map_url'=> 'https://www.google.com/maps/place/C%C3%94+N%C4%82M+SPA+-+Ti%E1%BB%87m+G%E1%BB%99i+%C4%91%E1%BA%A7u+%26+Massage/@10.792604,106.667016,17z/data=!3m1!4b1!4m6!3m5!1s0x317529a509686021:0xe397a23de90035ac!8m2!3d10.792604!4d106.667016!16s%2Fg%2F11w3xn1697?entry=ttu&g_ep=EgoyMDI2MDcwOC4wIKXMDSoASAFQAw%3D%3D',
            'opening_hours' => '9:00 sáng đến 20:00 tối, Hàng ngày',
            'price_range'   => '89k - 650k',
            'latitude'      => 10.792604,
            'longitude'     => 106.667016,
            'og_image'      => '/images/hero/727457690_1059334386607528_499987599721631516_n.jpg',
        );
    }

    return $config;
}

function beautycore_footer_navigation() {
    return array(
        array('label' => 'Giới thiệu', 'url' => beautycore_page_url('gioi-thieu')),
        array('label' => 'Liên hệ', 'url' => beautycore_page_url('lien-he')),
        array('label' => 'Blog', 'url' => beautycore_blog_url()),
        array('label' => 'Chính sách bảo mật', 'url' => beautycore_page_url('chinh-sach-bao-mat')),
        array('label' => 'Điều khoản dịch vụ', 'url' => beautycore_page_url('dieu-khoan-su-dung')),
        array('label' => 'Chính sách cookie', 'url' => beautycore_page_url('chinh-sach-cookie')),
        array('label' => 'Miễn trừ trách nhiệm', 'url' => beautycore_page_url('mien-tru-trach-nhiem')),
        array('label' => 'Chính sách đặt lịch', 'url' => beautycore_page_url('chinh-sach-dat-lich')),
        array('label' => 'Chính sách hủy, đổi lịch', 'url' => beautycore_page_url('chinh-sach-huy-doi-lich')),
    );
}

function beautycore_service_groups($featured_only = false) {
    static $groups;

    if (function_exists('beautycore_get_public_service_groups')) {
        $managed_groups = beautycore_get_public_service_groups($featured_only);
        $has_managed_services = post_type_exists('beautycore_service') && get_posts(array(
            'post_type'      => 'beautycore_service',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'fields'         => 'ids',
        ));
        if ($managed_groups || $has_managed_services) {
            return $managed_groups;
        }
    }

    if ($groups && !$featured_only) {
        return $groups;
    }

    $groups = array(
        array(
            'id' => 'goi-dau',
            'eyebrow' => 'Menu Hair Care',
            'title' => 'Dịch vụ gội đầu',
            'description' => 'Gội đầu thư giãn và gội đầu chuyên sâu.',
            'services' => array(
                array('name' => 'Gội thư giãn', 'duration' => "30'", 'price' => '89k', 'details' => 'Tẩy trang - Rửa mặt - Massage đầu khô - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc'),
                array('name' => 'Gội thư giãn', 'duration' => "40'", 'price' => '129k', 'details' => 'Tẩy trang - Rửa mặt - Massage mặt - Massage đầu khô - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc'),
                array('name' => 'Gội thư giãn', 'duration' => "50'", 'price' => '179k', 'details' => 'Tẩy trang - Rửa mặt - Massage đầu khô - Massage mặt - Massage Cổ Vai Gáy - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc'),
                array('name' => 'Gội chuyên sâu', 'duration' => "60'", 'price' => '209k', 'details' => 'Tẩy trang - Rửa mặt - Massage đầu khô - Massage mặt - Massage Cổ Vai Gáy - Đắp mặt nạ thạch - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc'),
                array('name' => 'Gội chuyên sâu', 'duration' => "80'", 'price' => '279k', 'details' => 'Tẩy trang - Rửa mặt - Massage đầu khô - Massage mặt - Massage Cổ Vai Gáy - Massage tay - Đắp mặt nạ thạch - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Dưỡng tóc - Sấy tóc'),
                array('name' => 'Gội chuyên sâu', 'duration' => "90'", 'price' => '369k', 'details' => 'Tẩy trang - Rửa mặt - Massage đầu khô - Massage mặt - Massage Cổ Vai Gáy - Massage tay, chân - Đắp mặt nạ thạch - Chườm túi thảo dược - Gội đầu lần 1 - Gội đầu lần 2 - Ủ xả tóc - Sấy tóc (Dùng dầu gội nhập không tính phí)'),
            ),
            'extras' => array('Mặt nạ thạch Cool Bạc hà - 40k', 'Mặt nạ cấp ẩm/ Trẻ hoá/ Trắng sáng - 60k', 'Tẩy tế bào chết da mặt - 40k', 'Dầu gội không tính phí: Dove, Sunsilk, Clear', 'Dầu gội nhập: TIGI/ Nexxus/ Collagen - 40k', 'Dầu gội thảo dược - 10k', 'Tẩy tế bào chết da đầu - 40k', "Massage mặt 10' - 50k"),
        ),
        array(
            'id' => 'mat-xa',
            'eyebrow' => 'Menu Massage',
            'title' => 'Dịch vụ massage',
            'description' => 'Massage thư giãn và massage trị liệu.',
            'services' => array(
                array('name' => 'Massage thư giãn cổ vai gáy', 'duration' => "60'", 'price' => '300k', 'details' => 'Ngâm chân - Massage lưng mặt úp: Thắt lưng, Cổ vai gáy, Tay - Massage ngửa: Tay, Cổ vai gáy ngửa, Đầu, Mặt - Chườm nóng thảo dược - Lau khăn ấm'),
                array('name' => 'Massage thư giãn cổ vai gáy', 'duration' => "90'", 'price' => '420k', 'details' => 'Ngâm chân - Massage lưng mặt úp: Thắt lưng, Cổ vai gáy, Tay - Massage ngửa: Tay, Cổ vai gáy ngửa, Đầu, Mặt - Chườm nóng thảo dược - Lau khăn ấm'),
                array('name' => 'Massage thư giãn toàn thân', 'duration' => "60'", 'price' => '330k', 'details' => 'Ngâm chân - Massage body mặt úp: Thắt lưng, Cổ vai gáy, Tay, Chân & Massage đá nóng lưng - Massage ngửa: Tay, Chân, đầu, mặt - Chườm nóng thảo dược - Lau khăn ấm'),
                array('name' => 'Massage thư giãn toàn thân', 'duration' => "90'", 'price' => '460k', 'details' => 'Ngâm chân - Massage body mặt úp: Thắt lưng, Cổ vai gáy, Tay, Chân & Massage đá nóng lưng - Massage ngửa: Tay, Chân, đầu, mặt - Chườm nóng thảo dược - Lau khăn ấm'),
                array('name' => 'Massage toàn thân đá nóng', 'duration' => "90'", 'price' => '480k', 'details' => 'Ngâm chân - Massage body mặt úp: Thắt lưng, Cổ vai gáy, Tay, Chân & Massage đá nóng toàn vùng - Massage ngửa: Tay, chân, Đầu, Mặt - Chườm nóng thảo dược - Lau khăn ấm'),
                array('name' => 'Massage trị liệu cổ vai gáy', 'duration' => "80'", 'price' => '550k', 'details' => 'Ngâm chân - Làm nóng dốc mạch bằng rượu thuốc - Khai huyệt - Massage trị liệu truy vết điểm đau tắt nghẽn Thắt lưng, Cổ vai gáy - Massage đá nóng - Chườm nóng thảo dược - Massage tay - Massage đầu - Lau khăn nóng'),
                array('name' => 'Massage trị liệu toàn thân', 'duration' => "90'", 'price' => '650k', 'details' => 'Ngâm chân - Làm nóng dốc mạch bằng rượu thuốc - Khai huyệt - Massage trị liệu truy vết điểm đau tắt nghẽn Thắt lưng, Cổ vai gáy - Massage tay, chân - Massage đá nóng - Đắp thuốc thảo dược - Chườm nóng thảo dược - Massage đầu - Lau khăn nóng'),
            ),
            'extras' => array("Trượt giác & giác hơi 20' - 150k", 'Đắp thuốc thảo dược - 100k'),
        ),
        array(
            'id' => 'combo',
            'eyebrow' => 'Menu Combo',
            'title' => 'Dịch vụ combo tiết kiệm',
            'description' => 'Combo gội đầu chuyên sâu kết hợp massage.',
            'services' => array(
                array('name' => 'Gội đầu chuyên sâu kết hợp massage thư giãn tay chân', 'duration' => "90'", 'price' => '339k', 'details' => "Kết hợp các bước gói gội đầu 60' + 30' massage tay chân"),
                array('name' => 'Gội đầu chuyên sâu kết hợp massage thư giãn cổ vai gáy, lưng úp', 'duration' => "90'", 'price' => '349k', 'details' => "Kết hợp các bước gói gội đầu 60' + 30' massage cổ vai gáy lưng úp"),
                array('name' => 'Massage thư giãn cổ vai gáy & gội đầu', 'duration' => "90'", 'price' => '369k', 'details' => "Kết hợp các bước gói Massage thư giãn CVG 60' + Các bước của gói gội đầu 30' theo menu"),
                array('name' => 'Massage thư giãn toàn thân & gội đầu', 'duration' => "90'", 'price' => '389k', 'details' => "Kết hợp các bước gói Massage thư giãn toàn thân 60' + Các bước của gói gội đầu 30' theo menu"),
                array('name' => 'Massage CVG kết hợp trị liệu CVG', 'duration' => "80'", 'price' => '429k', 'details' => "Kết hợp các bước gói Massage thư giãn CVG 60' + làm nóng đốc mạch, đẩy hàn khí CVG 20'"),
                array('name' => 'Massage body kết hợp trị liệu CVG', 'duration' => "90'", 'price' => '499k', 'details' => "Kết hợp các bước gói Massage thư giãn toàn thân 60' + làm nóng đốc mạch, đẩy hàn khí CVG 30'"),
            ),
            'extras' => array(),
        ),
    );

    return $groups;
}

function beautycore_booking_faqs() {
    return array(
        array('question' => 'Có cần đặt lịch trước không?', 'answer' => 'Nên đặt lịch trước để Beauty Core kiểm tra khung giờ còn trống và hỗ trợ bạn chọn dịch vụ phù hợp. Bạn có thể đặt qua EasySalon hoặc liên hệ trực tiếp qua điện thoại, Zalo hay Facebook.'),
        array('question' => 'Một buổi dịch vụ kéo dài bao lâu?', 'answer' => 'Thời lượng tùy theo gói bạn chọn. Các gói gội đầu và massage có thời lượng cụ thể trong bảng giá; thời gian thực tế có thể được điều chỉnh sau khi trao đổi về nhu cầu của bạn.'),
        array('question' => 'Nếu đến trễ thì sao?', 'answer' => 'Vui lòng báo cho Beauty Core sớm nhất có thể. Tùy lịch hẹn tiếp theo, thời lượng phục vụ có thể cần điều chỉnh để không ảnh hưởng đến các khách hàng khác.'),
        array('question' => 'Beauty Core nhận thanh toán bằng hình thức nào?', 'answer' => 'Phương thức thanh toán sẽ được xác nhận khi đặt lịch hoặc trước khi sử dụng dịch vụ. Nếu bạn cần chuẩn bị một hình thức cụ thể, hãy liên hệ trước để được hỗ trợ chính xác.'),
        array('question' => 'Đang mang thai hoặc có bệnh nền có sử dụng dịch vụ được không?', 'answer' => 'Bạn nên thông báo trước khi đặt lịch để nhân viên hiểu nhu cầu và những lưu ý cần thiết. Nếu đang mang thai, có bệnh nền hoặc đang điều trị, hãy tham khảo ý kiến nhân viên y tế phù hợp trước khi sử dụng dịch vụ.'),
        array('question' => 'Đau cổ vai gáy có nên massage không?', 'answer' => 'Massage tại spa hướng đến thư giãn và không thay thế khám, chẩn đoán hoặc điều trị. Nếu đau kéo dài, đau sau chấn thương, tê yếu tay chân hoặc có triệu chứng bất thường, bạn nên tìm tư vấn y tế trước.'),
    );
}
