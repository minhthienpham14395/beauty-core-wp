<?php
/**
 * Service and pricing data for the Beauty Core dashboard and public website.
 */

if (!defined('ABSPATH')) {
    exit;
}

function beautycore_service_statuses() {
    return array(
        'draft'        => 'Nháp',
        'publish'      => 'Đã xuất bản',
        'hidden'       => 'Đang ẩn',
        'discontinued' => 'Ngừng cung cấp',
    );
}

function beautycore_register_service_statuses() {
    register_post_status('hidden', array(
        'label'                     => 'Đang ẩn',
        'public'                    => false,
        'internal'                  => false,
        'protected'                 => false,
        'private'                   => false,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Đang ẩn <span class="count">(%s)</span>', 'Đang ẩn <span class="count">(%s)</span>', 'beautycore'),
    ));
    register_post_status('discontinued', array(
        'label'                     => 'Ngừng cung cấp',
        'public'                    => false,
        'internal'                  => false,
        'protected'                 => false,
        'private'                   => false,
        'exclude_from_search'       => true,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Ngừng cung cấp <span class="count">(%s)</span>', 'Ngừng cung cấp <span class="count">(%s)</span>', 'beautycore'),
    ));
}
add_action('init', 'beautycore_register_service_statuses', 0);

function beautycore_register_service_content_type() {
    register_post_type('beautycore_service', array(
        'labels' => array(
            'name'          => 'Dịch vụ',
            'singular_name' => 'Dịch vụ',
        ),
        'public'              => true,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'show_ui'             => false,
        'show_in_menu'        => false,
        'show_in_rest'        => true,
        'supports'            => array('title', 'editor', 'excerpt', 'thumbnail'),
        'rewrite'             => false,
        'query_var'           => false,
        'has_archive'         => false,
        'map_meta_cap'        => true,
    ));

    register_taxonomy('beautycore_service_category', 'beautycore_service', array(
        'labels' => array(
            'name'          => 'Danh mục dịch vụ',
            'singular_name' => 'Danh mục dịch vụ',
        ),
        'public'             => false,
        'show_ui'            => false,
        'show_in_rest'       => true,
        'hierarchical'       => true,
        'rewrite'            => false,
        'query_var'          => false,
    ));
}
add_action('init', 'beautycore_register_service_content_type', 1);

function beautycore_service_meta($post_id) {
    $post_id = absint($post_id);
    $image_id = absint(get_post_meta($post_id, '_beautycore_image_id', true));
    $branches = get_post_meta($post_id, '_beautycore_branch_ids', true);
    $staff = get_post_meta($post_id, '_beautycore_staff_ids', true);

    return array(
        'price_original' => (float) get_post_meta($post_id, '_beautycore_price_original', true),
        'price_sale'     => (float) get_post_meta($post_id, '_beautycore_price_sale', true),
        'duration'       => absint(get_post_meta($post_id, '_beautycore_duration', true)),
        'booking_enabled'=> get_post_meta($post_id, '_beautycore_booking_enabled', true) !== '0',
        'booking_url'    => (string) get_post_meta($post_id, '_beautycore_booking_url', true),
        'booking_note'   => (string) get_post_meta($post_id, '_beautycore_booking_note', true),
        'promo_start'    => (string) get_post_meta($post_id, '_beautycore_promo_start', true),
        'promo_end'      => (string) get_post_meta($post_id, '_beautycore_promo_end', true),
        'featured'       => get_post_meta($post_id, '_beautycore_featured', true) === '1',
        'homepage_order' => (int) get_post_meta($post_id, '_beautycore_homepage_order', true),
        'branch_ids'     => is_array($branches) ? array_map('sanitize_text_field', $branches) : array(),
        'staff_ids'      => is_array($staff) ? array_map('sanitize_text_field', $staff) : array(),
        'image_id'       => $image_id,
        'image_url'      => $image_id ? (string) wp_get_attachment_image_url($image_id, 'large') : (string) get_post_meta($post_id, '_beautycore_image_url', true),
        'seo_title'      => (string) get_post_meta($post_id, '_beautycore_seo_title', true),
        'seo_description' => (string) get_post_meta($post_id, '_beautycore_seo_description', true),
    );
}

function beautycore_service_format_price($amount) {
    if ((float) $amount <= 0) {
        return 'Liên hệ';
    }

    return number_format_i18n((float) $amount, 0) . 'đ';
}

function beautycore_service_promotion_is_active($meta) {
    if ((float) $meta['price_sale'] <= 0 || (float) $meta['price_sale'] >= (float) $meta['price_original']) {
        return false;
    }

    $today = current_time('Y-m-d');
    if (!empty($meta['promo_start']) && $today < $meta['promo_start']) {
        return false;
    }
    if (!empty($meta['promo_end']) && $today > $meta['promo_end']) {
        return false;
    }

    return true;
}

function beautycore_service_price_html($meta, $respect_schedule = false) {
    $original = (float) $meta['price_original'];
    $sale = (float) $meta['price_sale'];

    if ($sale > 0 && $sale < $original && (!$respect_schedule || beautycore_service_promotion_is_active($meta))) {
        return '<span class="service-price-sale">' . esc_html(beautycore_service_format_price($sale)) . '</span> <del>' . esc_html(beautycore_service_format_price($original)) . '</del>';
    }

    return esc_html(beautycore_service_format_price($original));
}

function beautycore_get_service_branch_options() {
    $branches = apply_filters('beautycore_service_branch_options', array());
    if (is_array($branches) && $branches) {
        return $branches;
    }

    if (post_type_exists('beautycore_branch')) {
        $branch_posts = get_posts(array(
            'post_type'      => 'beautycore_branch',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ));
        foreach ($branch_posts as $branch) {
            $branches[(string) $branch->ID] = $branch->post_title;
        }
    }

    if (!$branches && function_exists('beautycore_site_config')) {
        $config = beautycore_site_config();
        $branches['main'] = !empty($config['address']) ? 'Chi nhánh chính — ' . $config['address'] : 'Chi nhánh chính';
    }

    return $branches;
}

function beautycore_get_service_staff_options() {
    $staff = apply_filters('beautycore_service_staff_options', array());
    if (is_array($staff) && $staff) {
        return $staff;
    }

    $users = get_users(array(
        'role__in' => array('owner', 'manager', 'receptionist', 'staff'),
        'orderby'  => 'display_name',
        'order'    => 'ASC',
        'fields'   => array('ID', 'display_name', 'user_login'),
    ));
    foreach ($users as $user) {
        $staff[(string) $user->ID] = $user->display_name ?: $user->user_login;
    }

    return $staff;
}

function beautycore_get_public_service_groups($featured_only = false) {
    $args = array(
        'post_type'      => 'beautycore_service',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    );
    if ($featured_only) {
        $args['meta_query'] = array(array(
            'key'     => '_beautycore_featured',
            'value'   => '1',
            'compare' => '=',
        ));
        $args['meta_key'] = '_beautycore_homepage_order';
        $args['orderby'] = array('meta_value_num' => 'ASC', 'title' => 'ASC');
    }

    $posts = get_posts($args);
    if (!$posts) {
        return array();
    }

    $groups = array();
    foreach ($posts as $post) {
        $terms = get_the_terms($post->ID, 'beautycore_service_category');
        $term = ($terms && !is_wp_error($terms)) ? reset($terms) : null;
        $group_id = $term ? (string) $term->term_id : 'uncategorized';
        if (!isset($groups[$group_id])) {
            $groups[$group_id] = array(
                'id'          => $term ? $term->slug : 'dich-vu-khac',
                'eyebrow'     => $term ? (string) get_term_meta($term->term_id, '_beautycore_category_eyebrow', true) : 'Beauty Core Menu',
                'title'       => $term ? $term->name : 'Dịch vụ khác',
                'description' => $term ? $term->description : '',
                'services'    => array(),
                'extras'      => array(),
            );
            if (!$groups[$group_id]['eyebrow']) {
                $groups[$group_id]['eyebrow'] = 'Beauty Core Menu';
            }
        }

        $meta = beautycore_service_meta($post->ID);
        $details = trim(wp_strip_all_tags($post->post_content));
        $booking_url = $meta['booking_url'];
        if (!$booking_url && function_exists('beautycore_site_config')) {
            $config = beautycore_site_config();
            $booking_url = isset($config['booking_url']) ? $config['booking_url'] : '';
        }
        $active_sale = beautycore_service_promotion_is_active($meta) ? $meta['price_sale'] : 0;
        $groups[$group_id]['services'][] = array(
            'id'          => (int) $post->ID,
            'name'        => $post->post_title,
            'duration'    => $meta['duration'] ? $meta['duration'] . "'" : '',
            'price'       => beautycore_service_format_price($active_sale > 0 ? $active_sale : $meta['price_original']),
            'price_html'  => beautycore_service_price_html($meta, true),
            'details'     => $details,
            'booking_url' => $booking_url,
            'image'       => $meta['image_url'],
        );
    }

    return array_values($groups);
}

/**
 * Populate the new data model from the original theme catalog once. Existing
 * managed services are never overwritten by this migration.
 */
function beautycore_seed_services_from_legacy() {
    if (get_option('beautycore_services_seeded')) {
        return;
    }

    $existing = get_posts(array(
        'post_type'      => 'beautycore_service',
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'fields'         => 'ids',
    ));
    if ($existing) {
        update_option('beautycore_services_seeded', current_time('mysql'), false);
        return;
    }

    if (!function_exists('beautycore_service_groups')) {
        return;
    }

    $groups = beautycore_service_groups(false);
    $order = 0;
    foreach ($groups as $group) {
        $term = term_exists($group['title'], 'beautycore_service_category');
        if (!$term) {
            $term = wp_insert_term($group['title'], 'beautycore_service_category', array('description' => $group['description']));
        }
        if (is_wp_error($term)) {
            continue;
        }
        $term_id = (int) (is_array($term) ? $term['term_id'] : $term);
        update_term_meta($term_id, '_beautycore_category_eyebrow', sanitize_text_field($group['eyebrow']));

        foreach ($group['services'] as $service) {
            $price = preg_replace('/[^0-9.]/', '', (string) $service['price']);
            $price = (float) $price;
            if (strpos(strtolower((string) $service['price']), 'k') !== false) {
                $price *= 1000;
            }
            $duration = absint(preg_replace('/[^0-9]/', '', (string) $service['duration']));
            $post_id = wp_insert_post(wp_slash(array(
                'post_type'    => 'beautycore_service',
                'post_status'  => 'publish',
                'post_title'   => sanitize_text_field($service['name']),
                'post_name'    => sanitize_title($service['name'] . '-' . $duration . '-' . $price),
                'post_content' => wp_kses_post($service['details']),
            )), true);
            if (is_wp_error($post_id)) {
                continue;
            }
            wp_set_object_terms($post_id, array($term_id), 'beautycore_service_category');
            update_post_meta($post_id, '_beautycore_price_original', $price);
            update_post_meta($post_id, '_beautycore_price_sale', 0);
            update_post_meta($post_id, '_beautycore_duration', $duration);
            update_post_meta($post_id, '_beautycore_booking_enabled', '1');
            update_post_meta($post_id, '_beautycore_branch_ids', array('main'));
            update_post_meta($post_id, '_beautycore_featured', '1');
            update_post_meta($post_id, '_beautycore_homepage_order', $order++);
        }
    }

    update_option('beautycore_services_seeded', current_time('mysql'), false);
}
add_action('admin_init', 'beautycore_seed_services_from_legacy', 3);
add_action('after_switch_theme', 'beautycore_seed_services_from_legacy', 20);
