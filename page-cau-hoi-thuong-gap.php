<?php
get_header();
$faqs = beautycore_booking_faqs();
?>
<main class="page-content container"><nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a><span aria-hidden="true">/</span><span>Câu hỏi thường gặp</span></nav><article><header class="page-header"><h1>Câu hỏi thường gặp</h1><p>Cập nhật: 13/07/2026</p></header>
    <section class="booking-faq article-faq" aria-label="Câu hỏi thường gặp về dịch vụ và đặt lịch"><p class="booking-faq__intro">Bạn có thể xem nhanh các thông tin phổ biến trước khi đặt lịch. Nếu cần hỗ trợ riêng, hãy liên hệ trực tiếp với Beauty Core.</p>
        <?php foreach ($faqs as $faq) : ?><details><summary><?php echo esc_html($faq['question']); ?></summary><p><?php echo esc_html($faq['answer']); ?></p></details><?php endforeach; ?>
    </section><p><a class="btn btn-primary" href="<?php echo esc_url(beautycore_page_url('lien-he')); ?>">Liên hệ Beauty Core</a></p>
</article></main>
<?php get_footer(); ?>
