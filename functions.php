<?php
/**
 * Beauty Core WordPress theme bootstrap.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BEAUTYCORE_VERSION', '1.0.0');
define('BEAUTYCORE_CONTENT_VERSION', '2.0.0');
define('BEAUTYCORE_THEME_DIR', get_template_directory());

require_once BEAUTYCORE_THEME_DIR . '/inc/site-data.php';
require_once BEAUTYCORE_THEME_DIR . '/inc/content.php';
require_once BEAUTYCORE_THEME_DIR . '/inc/services.php';
require_once BEAUTYCORE_THEME_DIR . '/inc/import.php';
require_once BEAUTYCORE_THEME_DIR . '/inc/ai.php';
require_once BEAUTYCORE_THEME_DIR . '/inc/seo.php';
require_once BEAUTYCORE_THEME_DIR . '/inc/admin.php';
require_once BEAUTYCORE_THEME_DIR . '/inc/service-admin.php';

function beautycore_setup() {
    load_theme_textdomain('beautycore', BEAUTYCORE_THEME_DIR . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 1000,
        'width'       => 1000,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));

    register_nav_menus(array(
        'primary' => __('Menu chính', 'beautycore'),
        'footer'  => __('Menu chân trang', 'beautycore'),
    ));
}
add_action('after_setup_theme', 'beautycore_setup');

function beautycore_enqueue_assets() {
    wp_enqueue_style(
        'beautycore-site',
        get_theme_file_uri('/assets/css/site.css'),
        array(),
        BEAUTYCORE_VERSION
    );

    wp_enqueue_script(
        'beautycore-theme',
        get_theme_file_uri('/assets/js/theme.js'),
        array(),
        BEAUTYCORE_VERSION,
        true
    );

    wp_localize_script('beautycore-theme', 'BEAUTYCORE_CONFIG', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('beautycore_chat'),
    ));
}
add_action('wp_enqueue_scripts', 'beautycore_enqueue_assets');

function beautycore_asset_url($path) {
    if (!$path) {
        return '';
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if (function_exists('beautycore_attachment_id_for_source')) {
        $attachment_id = beautycore_attachment_id_for_source($path);
        if ($attachment_id) {
            $attachment_url = wp_get_attachment_url($attachment_id);
            if ($attachment_url) {
                return $attachment_url;
            }
        }
    }

    return get_theme_file_uri('/public/' . ltrim($path, '/'));
}

function beautycore_blog_url() {
    $archive_url = get_post_type_archive_link('beautycore_blog');

    return $archive_url ?: home_url('/blog/');
}

function beautycore_page_url($slug, $fallback = '') {
    $page = get_page_by_path(trim($slug, '/'));

    if ($slug === 'blog') {
        return beautycore_blog_url();
    }

    if ($page instanceof WP_Post) {
        return get_permalink($page);
    }

    return $fallback ? home_url('/' . trim($fallback, '/') . '/') : home_url('/' . trim($slug, '/') . '/');
}

function beautycore_is_active_path($slug) {
    if ($slug === 'trang-chu') {
        return is_front_page();
    }

    if ($slug === 'blog') {
        return is_post_type_archive('beautycore_blog') || is_singular('beautycore_blog') || is_tax('beautycore_category');
    }

    return is_page($slug) || is_page($slug . '/') || (is_post_type_archive('beautycore_blog') && $slug === 'blog');
}

function beautycore_primary_fallback() {
    $items = array(
        array('label' => 'Trang chủ', 'url' => home_url('/'), 'slug' => 'trang-chu'),
        array('label' => 'Giới thiệu', 'url' => beautycore_page_url('gioi-thieu'), 'slug' => 'gioi-thieu'),
        array('label' => 'Dịch vụ', 'url' => beautycore_page_url('dich-vu'), 'slug' => 'dich-vu'),
        array('label' => 'Blog', 'url' => beautycore_page_url('blog'), 'slug' => 'blog'),
        array('label' => 'Liên hệ', 'url' => beautycore_page_url('lien-he'), 'slug' => 'lien-he'),
    );

    echo '<ul class="nav-menu">';
    foreach ($items as $item) {
        $active = beautycore_is_active_path($item['slug']);
        printf(
            '<li><a href="%s" class="%s" %s>%s</a></li>',
            esc_url($item['url']),
            $active ? 'active' : '',
            $active ? 'aria-current="page"' : '',
            esc_html($item['label'])
        );
    }
    echo '</ul>';
}

function beautycore_footer_fallback() {
    $items = beautycore_footer_navigation();

    foreach ($items as $item) {
        printf('<a href="%s">%s</a>', esc_url($item['url']), esc_html($item['label']));
    }
}

function beautycore_body_class($classes) {
    if (is_front_page()) {
        $classes[] = 'beautycore-home';
    }

    return $classes;
}
add_filter('body_class', 'beautycore_body_class');
