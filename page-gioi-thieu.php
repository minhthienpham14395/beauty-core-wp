<?php
get_header();
$config = beautycore_site_config();
?>
<main class="page-content container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a><span aria-hidden="true">/</span><span>Giới thiệu</span></nav>
    <article><header class="page-header"><h1>Giới thiệu</h1></header>
        <section class="prose-section"><h2>Beauty Core</h2><p>Beauty Core là không gian gội đầu thư giãn và massage tại <?php echo esc_html($config['address']); ?>. Website này được vận hành để khách hàng tìm hiểu dịch vụ, tham khảo bảng giá và đặt lịch thuận tiện.</p><p>Chúng tôi hướng đến trải nghiệm chỉn chu, dễ chịu và phù hợp với nhu cầu nghỉ ngơi của từng khách hàng. Thông tin về địa chỉ, giờ mở cửa, số điện thoại và các kênh liên hệ được công khai để khách hàng có thể xác nhận trước khi đến.</p></section>
        <section class="prose-section"><h2>Cách chúng tôi phục vụ</h2><p>Trước mỗi buổi, khách hàng có thể trao đổi về thời lượng và dịch vụ mong muốn. Nhân viên sẽ hỗ trợ lựa chọn gói phù hợp trong phạm vi dịch vụ chăm sóc và thư giãn tại spa.</p><p>Để có trải nghiệm tốt nhất, vui lòng đặt lịch trước và thông báo các lưu ý cần thiết khi xác nhận lịch hẹn. Giá, thời lượng và quy trình hiển thị trên website là thông tin tham khảo; chúng tôi sẽ xác nhận lại khi đặt lịch.</p></section>
        <section class="prose-section"><h2>Nội dung trên website</h2><p>Các bài viết được đội ngũ Beauty Core biên soạn và rà soát từ thông tin dịch vụ cùng các lưu ý chăm sóc phổ biến. Chúng tôi phân biệt rõ nội dung tham khảo với tư vấn y khoa; những nội dung này không thay thế việc khám, chẩn đoán hoặc điều trị.</p><p>Bạn có thể xem <a href="<?php echo esc_url(beautycore_page_url('chinh-sach-bien-soan-noi-dung')); ?>">chính sách biên soạn nội dung</a>, <a href="<?php echo esc_url(beautycore_page_url('chinh-sach-bao-mat')); ?>">chính sách bảo mật</a> và các điều khoản sử dụng tại footer.</p></section>
        <section class="prose-section"><h2>Thông tin liên hệ</h2><p>Địa chỉ: <?php echo esc_html($config['address']); ?></p><p>Giờ mở cửa: <?php echo esc_html($config['opening_hours']); ?></p><p>Điện thoại: <a href="tel:<?php echo esc_attr($config['phone']); ?>"><?php echo esc_html($config['phone_display']); ?></a> · Email: <a href="mailto:<?php echo esc_attr($config['email']); ?>"><?php echo esc_html($config['email']); ?></a></p></section>
    </article>
</main>
<?php get_footer(); ?>
