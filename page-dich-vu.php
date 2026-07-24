<?php
get_header();
?>
<main class="service-page">
    <div class="service-page__intro container"><nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a><span aria-hidden="true">/</span><span>Dịch vụ</span></nav><p class="section-label">Dịch vụ Beauty Core</p><h1>Chăm sóc và thư giãn theo thời gian của bạn</h1><p>Tham khảo các gói gội đầu, massage và combo. Vui lòng liên hệ để xác nhận khung giờ, quy trình và mức giá trước khi đặt lịch.</p></div>
    <?php get_template_part('template-parts/home/services'); ?>
</main>
<?php get_footer(); ?>
