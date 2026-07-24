<?php
$config = beautycore_site_config();
$reviews = array(
    array('name' => 'Trần Mirabel', 'date' => '6 ngày trước', 'image' => '/images/review/727620989_898487703277905_5911380024469238575_n.jpg'),
    array('name' => 'Tuyết Nhi', 'date' => '3 ngày trước', 'image' => '/images/review/729480412_898487743277901_3695240999200371354_n.jpg'),
    array('name' => 'Sasha Lake', 'date' => 'Một tuần trước', 'image' => '/images/review/730558923_898487756611233_4795207206999777793_n.jpg'),
    array('name' => 'Linh Di', 'date' => '3 giờ trước', 'image' => '/images/review/731442740_898487699944572_361610893878314016_n.jpg'),
);
?>
<section class="reviews-section" aria-labelledby="reviews-title"><div class="container">
    <header class="reviews-header"><span class="section-label">Đánh giá từ khách hàng</span><h2 id="reviews-title">Những chia sẻ chân thành</h2><p>Các đánh giá được khách hàng đăng trên Google về trải nghiệm tại Beauty Core.</p></header>
    <div class="reviews-grid">
        <?php foreach ($reviews as $review) : ?><article class="review-card"><a href="<?php echo esc_url($config['google_map_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Xem đánh giá của <?php echo esc_attr($review['name']); ?> trên Google Maps"><img src="<?php echo esc_url(beautycore_asset_url($review['image'])); ?>" alt="Ảnh chụp đánh giá Google 5 sao của <?php echo esc_attr($review['name']); ?>" width="960" height="960" loading="lazy"></a><div class="review-card__meta"><strong><?php echo esc_html($review['name']); ?></strong><span><?php echo esc_html($review['date']); ?></span></div></article><?php endforeach; ?>
    </div>
    <div class="reviews-action"><a href="<?php echo esc_url($config['google_map_url']); ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">Xem đánh giá trên Google</a></div>
</div></section>
