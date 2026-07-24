<?php
$promotions = array(
    array('image' => '/images/uudai/6d6428eb-7780-4b0f-aadd-87dad04f2bcf.jpg', 'alt' => 'Ưu đãi gội đầu thư giãn 50 phút – 169.000đ'),
    array('image' => '/images/uudai/8bc58f23-31c4-45db-be08-44a40984c124.jpg', 'alt' => 'Ưu đãi gội đầu chuyên sâu 60 phút – 189.000đ'),
    array('image' => '/images/uudai/6cbcd60b-ecbf-4710-bf28-8b0b3a5e17bb.jpg', 'alt' => 'Ưu đãi combo gội đầu và massage tay chân – 339.000đ'),
    array('image' => '/images/uudai/ccfc2a28-5017-4d8b-9508-56897ebcd695.jpg', 'alt' => 'Ưu đãi combo massage body và gội đầu – 389.000đ'),
);
?>
<section class="promotions-section" id="uu-dai"><div class="container">
    <header class="promotions-header fade-in"><span class="section-label">Chương Trình Đặc Biệt</span><h2 class="section-title">Ưu Đãi Tại Beauty Core</h2><p class="section-desc">Khám phá các liệu trình đang có mức giá ưu đãi và đặt lịch để giữ khung giờ phù hợp.</p></header>
    <div class="promotions-grid">
        <?php foreach ($promotions as $promotion) : ?><a href="<?php echo esc_url(beautycore_booking_section_url()); ?>" class="promotion-card fade-in" aria-label="<?php echo esc_attr($promotion['alt']); ?> - Đặt lịch ngay"><img src="<?php echo esc_url(beautycore_asset_url($promotion['image'])); ?>" alt="<?php echo esc_attr($promotion['alt']); ?>" loading="lazy"><span>Đặt lịch ngay</span></a><?php endforeach; ?>
    </div>
</div></section>
