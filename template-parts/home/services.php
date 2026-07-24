<?php
$config = beautycore_site_config();
$groups = beautycore_service_groups(is_front_page());
?>
<section class="treatments-section" id="services">
    <div class="container">
        <header class="treatments-header fade-in"><span class="section-label">Bảng Giá Beauty Core</span><h2 class="section-title">Dịch Vụ Chăm Sóc &amp; Thư Giãn</h2><p class="section-desc">Chọn liệu trình phù hợp với thời gian và nhu cầu của bạn.</p></header>
        <nav class="service-tabs" aria-label="Danh mục dịch vụ">
            <?php foreach ($groups as $index => $group) : ?>
                <button type="button" class="service-tab<?php echo $index === 0 ? ' active' : ''; ?>" data-service-tab="<?php echo esc_attr($group['id']); ?>" aria-controls="<?php echo esc_attr($group['id']); ?>" aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"><?php echo esc_html($group['title']); ?></button>
            <?php endforeach; ?>
        </nav>
        <div class="service-catalog" id="service-slider">
            <?php foreach ($groups as $group) : ?>
                <section class="service-group" id="<?php echo esc_attr($group['id']); ?>" data-service-panel="<?php echo esc_attr($group['id']); ?>">
                    <header class="service-group-header"><div><span class="section-label"><?php echo esc_html($group['eyebrow']); ?></span><h3><?php echo esc_html($group['title']); ?></h3></div><p><?php echo esc_html($group['description']); ?></p></header>
                    <div class="service-list">
                        <?php foreach ($group['services'] as $service) : ?>
                            <article class="service-item"><div class="service-item-top"><div><h4><?php echo esc_html($service['name']); ?></h4><span class="service-duration"><?php echo esc_html($service['duration']); ?></span></div><strong><?php echo wp_kses_post(isset($service['price_html']) ? $service['price_html'] : esc_html($service['price'])); ?></strong></div><p><?php echo esc_html($service['details']); ?></p><a href="<?php echo esc_url(!empty($service['booking_url']) ? $service['booking_url'] : $config['booking_url']); ?>" class="service-booking" data-track="booking_click">Đặt lịch <span aria-hidden="true">→</span></a></article>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($group['extras'])) : ?><div class="service-extras"><h4>Dịch vụ thêm</h4><ul><?php foreach ($group['extras'] as $extra) : ?><li><?php echo esc_html($extra); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
                </section>
            <?php endforeach; ?>
        </div>
        <div class="service-note"><p>Thời lượng và quy trình có thể được điều chỉnh theo tình trạng thực tế. Vui lòng đặt lịch trước để được phục vụ tốt nhất.</p><a href="<?php echo esc_url($config['booking_url']); ?>" class="btn btn-primary">Đặt lịch ngay</a></div>
    </div>
</section>
