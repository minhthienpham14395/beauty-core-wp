<?php $config = beautycore_site_config(); ?>
<section class="about-section" id="rituals">
    <div class="container"><div class="about-inner">
        <div class="about-visual fade-in"><div class="about-video-main">
            <video autoplay controls muted loop playsinline preload="metadata" poster="<?php echo esc_url(beautycore_asset_url('/images/about-main.jpg')); ?>" aria-label="Video giới thiệu không gian và dịch vụ Beauty Core">
                <source src="<?php echo esc_url(beautycore_asset_url('/videos/1782148547003_7280312537000100950_7280312537000100950.mp4')); ?>" type="video/mp4">Trình duyệt của bạn không hỗ trợ phát video.
            </video>
        </div></div>
        <div class="about-content fade-in">
            <span class="about-tagline">Câu chuyện của chúng tôi</span>
            <h2>Thấu Hiểu Tiếng Nói Từ Thiên Nhiên</h2>
            <p>Tại Beauty Core, chúng tôi tin rằng vẻ đẹp bền vững bắt nguồn từ sự hòa hợp với đất mẹ. Mỗi thành phần được sử dụng đều là thảo mộc hữu cơ, được tuyển chọn kỹ lưỡng từ những khu vườn bền vững.</p>
            <p>Chúng tôi từ chối các hoạt chất mạnh, ưu tiên các liệu pháp đánh thức giác quan và nuôi dưỡng da bằng dưỡng chất một cách thuần khiết nhất.</p>
            <div class="about-stats"><div><div class="stat-number">98%</div><div class="stat-label">Hữu Cơ Thuần Khiết</div></div><div><div class="stat-number">8+</div><div class="stat-label">Năm Kinh Nghiệm</div></div></div>
            <a href="<?php echo esc_url($config['booking_url']); ?>" class="btn btn-primary about-button">Đặt lịch ngay</a>
        </div>
    </div></div>
</section>
