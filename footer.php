<?php
if (!defined('ABSPATH')) {
    exit;
}
$config = beautycore_site_config();
?>
<footer class="site-footer" id="contact">
    <div class="container">
        <div class="footer-inner">
            <div class="footer-brand">
                <a href="<?php echo esc_url(beautycore_home_path()); ?>" class="footer-logo" aria-label="Beauty Core - Trang chủ">
                    <img src="<?php echo esc_url(beautycore_asset_url('/images/logo.jpg')); ?>" alt="Beauty Core - Tiệm gội đầu và massage" loading="lazy">
                </a>
                <p><strong>BEAUTY CORE - TIỆM GỘI ĐẦU &amp; MASSAGE</strong></p>
                <div class="footer-location">
                    <div class="footer-contact-item"><span>📍</span><a href="<?php echo esc_url($config['google_map_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($config['address']); ?></a></div>
                    <div class="footer-contact-item"><span>🕐</span><span><?php echo esc_html($config['opening_hours']); ?></span></div>
                </div>
            </div>
            <div class="footer-col">
                <h4>Liên hệ</h4>
                <div class="footer-contact-item"><span>📞</span><a href="tel:<?php echo esc_attr($config['phone']); ?>"><?php echo esc_html($config['phone_display']); ?></a></div>
                <div class="footer-contact-item"><span>💬</span><a href="<?php echo esc_url($config['zalo_url']); ?>" target="_blank" rel="noopener noreferrer">Nhắn tin qua Zalo</a></div>
                <div class="footer-contact-item"><span>✉</span><a href="mailto:<?php echo esc_attr($config['email']); ?>"><?php echo esc_html($config['email']); ?></a></div>
                <div class="footer-contact-item"><span>ⓕ</span><a href="<?php echo esc_url($config['facebook_url']); ?>" target="_blank" rel="noopener noreferrer">facebook.com/beautycore</a></div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© <?php echo esc_html(wp_date('Y')); ?> Beauty Core. Chốn Bình Yên.</p>
            <nav aria-label="Liên kết cuối trang">
                <?php
                $footer_menu = wp_nav_menu(array(
                    'theme_location' => 'footer',
                    'container'      => false,
                    'menu_class'     => '',
                    'fallback_cb'    => false,
                    'echo'           => false,
                ));
                echo $footer_menu ?: '';
                if (!$footer_menu) {
                    beautycore_footer_fallback();
                }
                ?>
            </nav>
        </div>
    </div>
</footer>
<?php get_template_part('template-parts/floating-contact'); ?>
<?php get_template_part('template-parts/cookie-banner'); ?>
<?php wp_footer(); ?>
</body>
</html>
