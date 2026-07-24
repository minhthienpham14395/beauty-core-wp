<?php
if (!defined('ABSPATH')) {
    exit;
}
$config = beautycore_site_config();
$is_home = is_front_page();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#773600">
    <link rel="icon" type="image/png" href="<?php echo esc_url(beautycore_asset_url('/favicon.png')); ?>">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header<?php echo $is_home ? ' hero-mode' : ' scrolled'; ?>" id="site-header">
    <div class="header-inner">
        <a href="<?php echo esc_url(beautycore_home_path()); ?>" class="site-logo" aria-label="Beauty Core - Trang chủ">
            <img src="<?php echo esc_url(beautycore_asset_url('/images/logo.jpg')); ?>" alt="Beauty Core - Tiệm Gội Đầu Thư Giãn">
        </a>
        <nav class="main-nav" id="main-nav" aria-label="Điều hướng chính">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'nav-menu',
                'fallback_cb'    => 'beautycore_primary_fallback',
            ));
            ?>
            <a href="<?php echo esc_url(beautycore_booking_section_url()); ?>" class="btn btn-primary nav-cta" data-track="booking_click">Đặt lịch ngay</a>
        </nav>
        <button class="mobile-menu-toggle" id="mobile-toggle" type="button" aria-expanded="false" aria-controls="main-nav" aria-label="Mở menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>
