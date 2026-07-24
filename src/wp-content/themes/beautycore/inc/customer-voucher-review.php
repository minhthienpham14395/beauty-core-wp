<?php
/**
 * Beauty Core customer, voucher and review management.
 *
 * Customer records are private and are identified by a normalized phone number.
 * Vouchers and reviews remain private WordPress records so their workflow is
 * handled entirely by the Beauty Core admin screens.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BEAUTYCORE_CUSTOMER_POST_TYPE', 'beautycore_customer');
define('BEAUTYCORE_VOUCHER_POST_TYPE', 'beautycore_voucher');
define('BEAUTYCORE_REVIEW_POST_TYPE', 'beautycore_review');
define('BEAUTYCORE_STAGE5_VERSION', '1.0.0');

function beautycore_stage5_register_content_types() {
    $types = array(
        BEAUTYCORE_CUSTOMER_POST_TYPE => array('name' => 'Khách hàng', 'singular_name' => 'Khách hàng', 'supports' => array('title', 'editor')),
        BEAUTYCORE_VOUCHER_POST_TYPE  => array('name' => 'Voucher', 'singular_name' => 'Voucher', 'supports' => array('title')),
        BEAUTYCORE_REVIEW_POST_TYPE   => array('name' => 'Đánh giá', 'singular_name' => 'Đánh giá', 'supports' => array('title', 'editor')),
    );

    foreach ($types as $type => $labels) {
        register_post_type($type, array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false,
            'show_in_menu'       => false,
            'show_in_rest'       => false,
            'supports'           => $labels['supports'],
            'map_meta_cap'       => true,
        ));
    }
}
add_action('init', 'beautycore_stage5_register_content_types', 3);

function beautycore_customer_phone_key($phone) {
    $phone = preg_replace('/\D+/', '', (string) $phone);
    if (strpos($phone, '84') === 0 && strlen($phone) === 11) {
        $phone = '0' . substr($phone, 2);
    }

    return $phone;
}

function beautycore_stage5_date($value) {
    $value = sanitize_text_field((string) $value);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
}

function beautycore_stage5_money($value) {
    return max(0, (float) str_replace(',', '', sanitize_text_field((string) $value)));
}

function beautycore_stage5_clean_post_ids($values, $post_type) {
    $ids = array();
    foreach ((array) $values as $value) {
        $id = absint($value);
        if ($id && get_post_type($id) === $post_type) {
            $ids[] = $id;
        }
    }

    return array_values(array_unique($ids));
}

function beautycore_stage5_customer_posts() {
    return get_posts(array(
        'post_type'      => BEAUTYCORE_CUSTOMER_POST_TYPE,
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));
}

function beautycore_stage5_find_customer_by_phone($phone_key, $exclude_id = 0) {
    if (!$phone_key) {
        return null;
    }

    $customers = get_posts(array(
        'post_type'      => BEAUTYCORE_CUSTOMER_POST_TYPE,
        'post_status'    => 'any',
        'posts_per_page' => 2,
        'meta_key'       => '_beautycore_customer_phone_key',
        'meta_value'     => $phone_key,
        'exclude'        => $exclude_id ? array(absint($exclude_id)) : array(),
    ));

    return $customers ? $customers[0] : null;
}

function beautycore_customer_appointments($customer_id, $phone = '') {
    if (!function_exists('beautycore_appointment_get_all')) {
        return array();
    }

    $phone_key = beautycore_customer_phone_key($phone);
    $appointments = array_filter(beautycore_appointment_get_all(), function ($appointment) use ($customer_id, $phone_key) {
        $appointment_customer_id = absint(get_post_meta($appointment['id'], '_beautycore_customer_id', true));
        return $appointment_customer_id === absint($customer_id) || ($phone_key && beautycore_customer_phone_key($appointment['customer_phone']) === $phone_key);
    });

    usort($appointments, function ($left, $right) {
        return $right['timestamp'] <=> $left['timestamp'];
    });

    return array_values($appointments);
}

function beautycore_customer_data($post) {
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post || $post->post_type !== BEAUTYCORE_CUSTOMER_POST_TYPE) {
        return array();
    }

    $phone = (string) get_post_meta($post->ID, '_beautycore_customer_phone', true);
    $appointments = beautycore_customer_appointments($post->ID, $phone);
    $visits = array_values(array_filter($appointments, function ($appointment) {
        return !function_exists('beautycore_appointment_is_cancelled') || !beautycore_appointment_is_cancelled($appointment['status']);
    }));
    $last_visit = $visits ? $visits[0] : array();

    return array(
        'id'           => (int) $post->ID,
        'name'         => $post->post_title,
        'phone'        => $phone,
        'phone_key'    => beautycore_customer_phone_key($phone),
        'email'        => (string) get_post_meta($post->ID, '_beautycore_customer_email', true),
        'birthday'     => (string) get_post_meta($post->ID, '_beautycore_customer_birthday', true),
        'notes'        => $post->post_content,
        'appointments' => $appointments,
        'visit_count'  => count($visits),
        'last_visit'   => $last_visit,
    );
}

function beautycore_customer_groups($customer) {
    $groups = array();
    $today = current_time('Y-m-d');
    $last_date = !empty($customer['last_visit']['start']) ? substr($customer['last_visit']['start'], 0, 10) : '';

    if ((int) $customer['visit_count'] <= 1) {
        $groups['new'] = 'Khách mới';
    }
    if ((int) $customer['visit_count'] >= 2) {
        $groups['returning'] = 'Khách quay lại';
    }
    if ((int) $customer['visit_count'] >= 5) {
        $groups['vip'] = 'VIP';
    }
    if ($last_date && strtotime($last_date) < strtotime($today . ' -90 days')) {
        $groups['inactive'] = 'Lâu chưa đến';
    }
    if (!empty($customer['birthday']) && substr($customer['birthday'], 5, 2) === current_time('m')) {
        $groups['birthday'] = 'Sinh nhật tháng';
    }

    return $groups;
}

function beautycore_customer_matches_segment($customer, $segment) {
    return $segment === 'all' || isset(beautycore_customer_groups($customer)[$segment]);
}

function beautycore_stage5_upsert_customer_from_appointment($appointment_id, $is_update = false, $normalized = array()) {
    $appointment = function_exists('beautycore_appointment_data') ? beautycore_appointment_data($appointment_id) : array();
    if (!$appointment || empty($appointment['customer_phone'])) {
        return;
    }

    $phone_key = beautycore_customer_phone_key($appointment['customer_phone']);
    if (!$phone_key) {
        return;
    }
    $customer_id = absint(get_post_meta($appointment_id, '_beautycore_customer_id', true));
    $customer = $customer_id ? get_post($customer_id) : null;
    if (!$customer || $customer->post_type !== BEAUTYCORE_CUSTOMER_POST_TYPE) {
        $customer = beautycore_stage5_find_customer_by_phone($phone_key);
    }

    $customer_id = $customer ? $customer->ID : wp_insert_post(array(
        'post_type'   => BEAUTYCORE_CUSTOMER_POST_TYPE,
        'post_status' => 'publish',
        'post_title'  => $appointment['customer_name'],
    ), true);
    if (is_wp_error($customer_id) || !$customer_id) {
        return;
    }

    wp_update_post(array('ID' => $customer_id, 'post_title' => $appointment['customer_name']));
    update_post_meta($customer_id, '_beautycore_customer_phone', $appointment['customer_phone']);
    update_post_meta($customer_id, '_beautycore_customer_phone_key', $phone_key);
    if (!empty($appointment['customer_email'])) {
        update_post_meta($customer_id, '_beautycore_customer_email', $appointment['customer_email']);
    }
    update_post_meta($appointment_id, '_beautycore_customer_id', $customer_id);
}
add_action('beautycore_appointment_saved', 'beautycore_stage5_upsert_customer_from_appointment', 20, 3);

function beautycore_stage5_maybe_sync_customers() {
    if (get_option('beautycore_stage5_customer_sync_version') === BEAUTYCORE_STAGE5_VERSION || !function_exists('beautycore_appointment_get_all')) {
        return;
    }

    foreach (beautycore_appointment_get_all() as $appointment) {
        beautycore_stage5_upsert_customer_from_appointment($appointment['id']);
    }
    update_option('beautycore_stage5_customer_sync_version', BEAUTYCORE_STAGE5_VERSION, false);
}
add_action('admin_init', 'beautycore_stage5_maybe_sync_customers', 20);

function beautycore_voucher_discount_types() {
    return array('percent' => 'Giảm theo phần trăm', 'fixed' => 'Giảm tiền cố định');
}

function beautycore_voucher_data($post) {
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post || $post->post_type !== BEAUTYCORE_VOUCHER_POST_TYPE) {
        return array();
    }

    $data = array(
        'id'          => (int) $post->ID,
        'code'        => (string) get_post_meta($post->ID, '_beautycore_voucher_code', true),
        'type'        => (string) get_post_meta($post->ID, '_beautycore_voucher_discount_type', true),
        'value'       => (float) get_post_meta($post->ID, '_beautycore_voucher_value', true),
        'start_date'  => (string) get_post_meta($post->ID, '_beautycore_voucher_start_date', true),
        'end_date'    => (string) get_post_meta($post->ID, '_beautycore_voucher_end_date', true),
        'service_ids' => array_map('absint', (array) get_post_meta($post->ID, '_beautycore_voucher_service_ids', true)),
        'branch_ids'  => array_map('strval', (array) get_post_meta($post->ID, '_beautycore_voucher_branch_ids', true)),
        'usage_limit' => absint(get_post_meta($post->ID, '_beautycore_voucher_usage_limit', true)),
        'used_count'  => absint(get_post_meta($post->ID, '_beautycore_voucher_used_count', true)),
        'min_order'   => (float) get_post_meta($post->ID, '_beautycore_voucher_min_order', true),
        'enabled'     => get_post_meta($post->ID, '_beautycore_voucher_enabled', true) !== '0',
    );
    $data['type'] = isset(beautycore_voucher_discount_types()[$data['type']]) ? $data['type'] : 'fixed';
    $data['status'] = beautycore_voucher_status($data);

    return $data;
}

function beautycore_voucher_status($voucher) {
    $today = current_time('Y-m-d');
    if (empty($voucher['enabled'])) {
        return 'inactive';
    }
    if ((!empty($voucher['start_date']) && $voucher['start_date'] > $today) || (!empty($voucher['end_date']) && $voucher['end_date'] < $today)) {
        return 'expired';
    }
    if (!empty($voucher['usage_limit']) && (int) $voucher['used_count'] >= (int) $voucher['usage_limit']) {
        return 'exhausted';
    }

    return 'active';
}

function beautycore_voucher_status_label($status) {
    return array(
        'active' => 'Đang hoạt động', 'inactive' => 'Tạm dừng', 'expired' => 'Ngoài thời hạn', 'exhausted' => 'Đã hết lượt',
    )[$status] ?? 'Không xác định';
}

function beautycore_find_voucher_by_code($code) {
    $code = strtoupper(sanitize_text_field((string) $code));
    if (!$code) {
        return null;
    }
    $vouchers = get_posts(array(
        'post_type'      => BEAUTYCORE_VOUCHER_POST_TYPE,
        'post_status'    => 'any',
        'posts_per_page' => 1,
        'meta_key'       => '_beautycore_voucher_code',
        'meta_value'     => $code,
    ));

    return $vouchers ? beautycore_voucher_data($vouchers[0]) : null;
}

/**
 * Validate a voucher for a booking or order without consuming one use.
 * Context accepts service_id, branch_id and order_total.
 */
function beautycore_validate_voucher($code, $context = array()) {
    $voucher = beautycore_find_voucher_by_code($code);
    if (!$voucher) {
        return new WP_Error('beautycore_voucher_not_found', 'Voucher không tồn tại.');
    }
    if ($voucher['status'] === 'expired') {
        return new WP_Error('beautycore_voucher_expired', 'Voucher đã hết hạn hoặc chưa đến ngày hiệu lực.');
    }
    if ($voucher['status'] === 'exhausted') {
        return new WP_Error('beautycore_voucher_exhausted', 'Voucher đã hết lượt sử dụng.');
    }
    if ($voucher['status'] !== 'active') {
        return new WP_Error('beautycore_voucher_inactive', 'Voucher hiện không hoạt động.');
    }

    $service_id = !empty($context['service_id']) ? absint($context['service_id']) : 0;
    $branch_id = isset($context['branch_id']) ? (string) $context['branch_id'] : '';
    $order_total = isset($context['order_total']) ? beautycore_stage5_money($context['order_total']) : 0;
    if ($voucher['service_ids'] && (! $service_id || !in_array($service_id, $voucher['service_ids'], true))) {
        return new WP_Error('beautycore_voucher_service', 'Voucher không áp dụng cho dịch vụ đã chọn.');
    }
    if ($voucher['branch_ids'] && (! $branch_id || !in_array($branch_id, $voucher['branch_ids'], true))) {
        return new WP_Error('beautycore_voucher_branch', 'Voucher không áp dụng tại chi nhánh đã chọn.');
    }
    if ($voucher['min_order'] > $order_total) {
        return new WP_Error('beautycore_voucher_minimum', sprintf('Đơn hàng cần từ %s để dùng voucher này.', beautycore_service_format_price($voucher['min_order'])));
    }

    $discount = $voucher['type'] === 'percent' ? $order_total * ($voucher['value'] / 100) : $voucher['value'];
    $voucher['discount_amount'] = min($order_total, $discount);
    return $voucher;
}

function beautycore_redeem_voucher($code, $context = array()) {
    $voucher = beautycore_validate_voucher($code, $context);
    if (is_wp_error($voucher)) {
        return $voucher;
    }

    update_post_meta($voucher['id'], '_beautycore_voucher_used_count', $voucher['used_count'] + 1);
    return $voucher;
}

function beautycore_review_statuses() {
    return array('pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'hidden' => 'Hidden');
}

function beautycore_review_data($post) {
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post || $post->post_type !== BEAUTYCORE_REVIEW_POST_TYPE) {
        return array();
    }
    $status = sanitize_key((string) get_post_meta($post->ID, '_beautycore_review_status', true));
    $status = isset(beautycore_review_statuses()[$status]) ? $status : 'pending';

    return array(
        'id'          => (int) $post->ID,
        'name'        => $post->post_title,
        'email'       => (string) get_post_meta($post->ID, '_beautycore_review_email', true),
        'customer_id' => absint(get_post_meta($post->ID, '_beautycore_review_customer_id', true)),
        'service_id'  => absint(get_post_meta($post->ID, '_beautycore_review_service_id', true)),
        'staff_id'    => absint(get_post_meta($post->ID, '_beautycore_review_staff_id', true)),
        'rating'      => max(1, min(5, absint(get_post_meta($post->ID, '_beautycore_review_rating', true)))),
        'source'      => (string) get_post_meta($post->ID, '_beautycore_review_source', true),
        'status'      => $status,
        'content'     => $post->post_content,
        'date'        => $post->post_date,
    );
}

function beautycore_submit_review($data) {
    $data = is_array($data) ? $data : array();
    $name = isset($data['name']) ? sanitize_text_field($data['name']) : '';
    $content = isset($data['content']) ? sanitize_textarea_field($data['content']) : '';
    if (!$name || !$content) {
        return new WP_Error('beautycore_review_required', 'Tên khách và nội dung đánh giá là bắt buộc.');
    }
    $review_id = wp_insert_post(wp_slash(array(
        'post_type' => BEAUTYCORE_REVIEW_POST_TYPE,
        'post_status' => 'publish',
        'post_title' => $name,
        'post_content' => $content,
    )), true);
    if (is_wp_error($review_id)) {
        return $review_id;
    }
    update_post_meta($review_id, '_beautycore_review_status', 'pending');
    update_post_meta($review_id, '_beautycore_review_rating', max(1, min(5, absint($data['rating'] ?? 5))));
    update_post_meta($review_id, '_beautycore_review_email', sanitize_email($data['email'] ?? ''));
    update_post_meta($review_id, '_beautycore_review_customer_id', absint($data['customer_id'] ?? 0));
    update_post_meta($review_id, '_beautycore_review_service_id', absint($data['service_id'] ?? 0));
    update_post_meta($review_id, '_beautycore_review_source', sanitize_key($data['source'] ?? 'website'));
    return $review_id;
}

function beautycore_get_approved_reviews($limit = 4) {
    $reviews = get_posts(array(
        'post_type'      => BEAUTYCORE_REVIEW_POST_TYPE,
        'post_status'    => 'publish',
        'posts_per_page' => max(1, absint($limit)),
        'meta_key'       => '_beautycore_review_status',
        'meta_value'     => 'approved',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    return array_map('beautycore_review_data', $reviews);
}

function beautycore_stage5_admin_menu() {
    foreach (array(
        array('Sửa khách hàng', 'beautycore-customer-edit', 'manage_beautycore_customers', 'beautycore_render_customer_edit_page'),
        array('Sửa voucher', 'beautycore-voucher-edit', 'manage_beautycore_promotions', 'beautycore_render_voucher_edit_page'),
        array('Sửa đánh giá', 'beautycore-review-edit', 'manage_beautycore_reviews', 'beautycore_render_review_edit_page'),
    ) as $page) {
        add_submenu_page(null, $page[0], $page[0], $page[2], $page[1], $page[3]);
    }
}
add_action('admin_menu', 'beautycore_stage5_admin_menu', 11);

function beautycore_stage5_admin_assets() {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if (!in_array($page, array('beautycore-customers', 'beautycore-customer-edit', 'beautycore-promotions', 'beautycore-voucher-edit', 'beautycore-reviews', 'beautycore-review-edit'), true)) {
        return;
    }
    wp_enqueue_script('beautycore-stage5-admin', get_theme_file_uri('/assets/js/admin-stage5.js'), array(), BEAUTYCORE_ADMIN_VERSION, true);
    wp_localize_script('beautycore-stage5-admin', 'BEAUTYCORE_STAGE5_ADMIN', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('beautycore_stage5_modal'),
    ));
}
add_action('admin_enqueue_scripts', 'beautycore_stage5_admin_assets');

function beautycore_stage5_render_modal() {
    echo '<div id="beautycore-stage5-modal" class="beautycore-service-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="beautycore-stage5-modal-title"><div class="beautycore-service-modal__backdrop" data-beautycore-stage5-close></div><div class="beautycore-service-modal__dialog"><div class="beautycore-service-modal__header"><h2 id="beautycore-stage5-modal-title">Thêm mới</h2><button type="button" class="button-link beautycore-service-modal__close" data-beautycore-stage5-close aria-label="Đóng">&times;</button></div><div id="beautycore-stage5-modal-body" class="beautycore-service-modal__body"><p class="beautycore-modal-loading">Đang chuẩn bị biểu mẫu...</p></div></div></div>';
}

function beautycore_ajax_stage5_form() {
    check_ajax_referer('beautycore_stage5_modal');
    $entity = isset($_GET['entity']) ? sanitize_key(wp_unslash($_GET['entity'])) : '';
    $object_id = isset($_GET['object_id']) ? absint($_GET['object_id']) : 0;
    if ($entity === 'customer' && current_user_can('manage_beautycore_customers')) {
        ob_start();
        beautycore_render_customer_edit_page($object_id, true);
        wp_send_json_success(ob_get_clean());
    }
    if ($entity === 'voucher' && current_user_can('manage_beautycore_promotions')) {
        ob_start();
        beautycore_render_voucher_edit_page($object_id, true);
        wp_send_json_success(ob_get_clean());
    }
    if ($entity === 'review' && current_user_can('manage_beautycore_reviews')) {
        ob_start();
        beautycore_render_review_edit_page($object_id, true);
        wp_send_json_success(ob_get_clean());
    }

    wp_send_json_error(array('message' => 'Bạn không có quyền mở biểu mẫu này.'), 403);
}
add_action('wp_ajax_beautycore_stage5_form', 'beautycore_ajax_stage5_form');

function beautycore_stage5_redirect($page, $args = array()) {
    $url = add_query_arg(array_merge(array('page' => $page), $args), admin_url('admin.php'));
    wp_safe_redirect($url);
    exit;
}

function beautycore_stage5_notice() {
    if (!empty($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Dữ liệu đã được lưu.</p></div>';
    } elseif (!empty($_GET['deleted'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Dữ liệu đã được xóa.</p></div>';
    } elseif (!empty($_GET['error'])) {
        echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash($_GET['error'])) . '</p></div>';
    }
}

function beautycore_stage5_edit_url($page, $id = 0) {
    return admin_url('admin.php?page=' . $page . ($id ? '&id=' . absint($id) : ''));
}

function beautycore_stage5_customer_options() {
    $options = array();
    foreach (beautycore_stage5_customer_posts() as $customer) {
        $data = beautycore_customer_data($customer);
        $options[$data['id']] = $data['name'] . ($data['phone'] ? ' — ' . $data['phone'] : '');
    }
    return $options;
}

function beautycore_stage5_render_customer_list() {
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $segment = isset($_GET['segment']) ? sanitize_key(wp_unslash($_GET['segment'])) : 'all';
    $segments = array('all' => 'Tất cả khách', 'new' => 'Khách mới', 'returning' => 'Khách quay lại', 'inactive' => 'Lâu chưa đến', 'vip' => 'VIP', 'birthday' => 'Sinh nhật tháng');
    $segment = isset($segments[$segment]) ? $segment : 'all';
    $customers = array_map('beautycore_customer_data', beautycore_stage5_customer_posts());
    $customers = array_values(array_filter($customers, function ($customer) use ($search, $segment) {
        return beautycore_customer_matches_segment($customer, $segment) && (!$search || stripos($customer['name'] . ' ' . $customer['phone'] . ' ' . $customer['email'], $search) !== false);
    }));

    echo '<div class="beautycore-service-toolbar"><div><a class="button button-primary beautycore-stage5-open" data-stage5-entity="customer" data-object-id="0" href="' . esc_url(beautycore_stage5_edit_url('beautycore-customer-edit')) . '">Thêm khách hàng</a></div><span class="description">Hồ sơ được nhận diện tự động theo số điện thoại từ lịch hẹn.</span></div>';
    beautycore_stage5_render_modal();
    echo '<form method="get" class="beautycore-service-filters beautycore-stage5-filters" data-beautycore-stage5-filter><input type="hidden" name="page" value="beautycore-customers"><input type="search" name="s" value="' . esc_attr($search) . '" placeholder="Tên, số điện thoại hoặc email" aria-label="Tìm khách hàng"><select name="segment">';
    foreach ($segments as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($segment, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select><button class="button">Lọc</button></form>';

    if (!$customers) {
        echo '<div class="beautycore-empty-state"><strong>Chưa có khách hàng</strong><span>Tạo hồ sơ tại đây hoặc thêm lịch hẹn để hệ thống nhận diện khách theo số điện thoại.</span></div>';
        return;
    }
    echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table beautycore-stage5-table"><thead><tr><th>Khách hàng</th><th>Liên hệ</th><th>Phân nhóm</th><th>Số lần đến</th><th>Lần gần nhất</th><th></th></tr></thead><tbody>';
    foreach ($customers as $customer) {
        $groups = beautycore_customer_groups($customer);
        $last_visit = !empty($customer['last_visit']['timestamp']) ? wp_date('d/m/Y H:i', $customer['last_visit']['timestamp'], wp_timezone()) : 'Chưa có lịch';
        echo '<tr><td><strong>' . esc_html($customer['name']) . '</strong><small>Hồ sơ #' . esc_html($customer['id']) . '</small></td><td>' . esc_html($customer['phone'] ?: '—') . '<small>' . esc_html($customer['email']) . '</small></td><td>';
        foreach ($groups as $key => $label) {
            echo '<span class="beautycore-stage5-tag beautycore-stage5-tag--' . esc_attr($key) . '">' . esc_html($label) . '</span>';
        }
        echo '</td><td><strong>' . esc_html($customer['visit_count']) . '</strong></td><td>' . esc_html($last_visit) . '</td><td><a class="button button-small beautycore-stage5-open" data-stage5-entity="customer" data-object-id="' . esc_attr($customer['id']) . '" href="' . esc_url(beautycore_stage5_edit_url('beautycore-customer-edit', $customer['id'])) . '">Xem hồ sơ</a></td></tr>';
    }
    echo '</tbody></table></div>';
}

function beautycore_render_customer_edit_page($customer_id_override = null, $fragment = false) {
    if (!current_user_can('manage_beautycore_customers')) {
        wp_die('Bạn không có quyền quản lý khách hàng.');
    }
    $customer_id = $customer_id_override === null ? (isset($_GET['id']) ? absint($_GET['id']) : 0) : absint($customer_id_override);
    $customer = $customer_id ? beautycore_customer_data($customer_id) : array('id' => 0, 'name' => '', 'phone' => '', 'email' => '', 'birthday' => '', 'notes' => '', 'appointments' => array(), 'visit_count' => 0, 'last_visit' => array());
    if ($customer_id && !$customer) {
        wp_die('Khách hàng không hợp lệ.');
    }
    if (!$fragment) {
        beautycore_admin_page_header($customer_id ? 'Hồ sơ khách hàng' : 'Thêm khách hàng', 'Thông tin liên hệ và lịch sử phục vụ được bảo vệ trong khu vực quản trị.');
        beautycore_stage5_notice();
    }
    echo '<form class="beautycore-service-form beautycore-stage5-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="beautycore_save_customer"><input type="hidden" name="customer_id" value="' . esc_attr($customer['id']) . '">';
    wp_nonce_field('beautycore_save_customer');
    echo '<div class="beautycore-service-form-layout"><div class="beautycore-service-form-main"><section class="beautycore-panel"><h2>Thông tin khách hàng</h2><div class="beautycore-form-grid"><div class="beautycore-form-field"><label for="customer_name"><strong>Họ tên *</strong></label><input id="customer_name" name="name" required value="' . esc_attr($customer['name']) . '"></div><div class="beautycore-form-field"><label for="customer_phone"><strong>Số điện thoại *</strong></label><input id="customer_phone" name="phone" required value="' . esc_attr($customer['phone']) . '"><p class="description">Số điện thoại là khóa nhận diện khách cũ.</p></div><div class="beautycore-form-field"><label for="customer_email"><strong>Email</strong></label><input id="customer_email" type="email" name="email" value="' . esc_attr($customer['email']) . '"></div><div class="beautycore-form-field"><label for="customer_birthday"><strong>Ngày sinh</strong></label><input id="customer_birthday" type="date" name="birthday" value="' . esc_attr($customer['birthday']) . '"></div></div><div class="beautycore-form-field"><label for="customer_notes"><strong>Ghi chú nội bộ</strong></label><textarea id="customer_notes" name="notes">' . esc_textarea($customer['notes']) . '</textarea></div></section>';
    echo '<section class="beautycore-panel"><div class="beautycore-panel-heading"><h2>Lịch sử đặt lịch</h2><span class="description">' . esc_html($customer['visit_count']) . ' lần đến</span></div>';
    if ($customer['appointments']) {
        echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table beautycore-stage5-history"><thead><tr><th>Thời gian</th><th>Dịch vụ</th><th>Nhân viên</th><th>Trạng thái</th></tr></thead><tbody>';
        foreach ($customer['appointments'] as $appointment) {
            echo '<tr><td>' . esc_html(beautycore_format_appointment_time($appointment['timestamp'], $appointment['start'])) . '</td><td>' . esc_html($appointment['service_name']) . '</td><td>' . esc_html($appointment['staff_name'] ?: '—') . '</td><td><span class="beautycore-status beautycore-status-' . esc_attr($appointment['status']) . '">' . esc_html(beautycore_appointment_statuses()[$appointment['status']] ?? $appointment['status']) . '</span></td></tr>';
        }
        echo '</tbody></table></div>';
    } else {
        echo '<div class="beautycore-empty-state"><strong>Chưa có lịch sử</strong><span>Lịch hẹn của khách sẽ tự liên kết vào hồ sơ này.</span></div>';
    }
    echo '</section></div><aside><section class="beautycore-panel"><h2>Hành động</h2><p><button class="button button-primary">Lưu hồ sơ</button></p><p><a class="button" href="' . esc_url(admin_url('admin.php?page=beautycore-customers')) . '">Quay lại danh sách</a></p></section>';
    if ($customer['id'] && $customer['phone_key']) {
        $duplicate = beautycore_stage5_find_customer_by_phone($customer['phone_key'], $customer['id']);
        if ($duplicate) {
            $merge_url = wp_nonce_url(admin_url('admin-post.php?action=beautycore_merge_customer&primary_id=' . $customer['id'] . '&duplicate_id=' . $duplicate->ID), 'beautycore_merge_customer_' . $customer['id'] . '_' . $duplicate->ID);
            echo '<section class="beautycore-panel"><h2>Bản ghi trùng</h2><p>Hồ sơ <strong>' . esc_html($duplicate->post_title) . '</strong> có cùng số điện thoại. Chỉ gộp khi xác nhận là cùng một khách.</p><p><a class="button" href="' . esc_url($merge_url) . '">Gộp vào hồ sơ này</a></p></section>';
        }
    }
    echo '</aside></div></form>';
    if (!$fragment) {
        beautycore_admin_page_footer();
    }
}

function beautycore_handle_save_customer() {
    if (!current_user_can('manage_beautycore_customers')) {
        wp_die('Bạn không có quyền quản lý khách hàng.');
    }
    check_admin_referer('beautycore_save_customer');
    $customer_id = isset($_POST['customer_id']) ? absint($_POST['customer_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    $phone_key = beautycore_customer_phone_key($phone);
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (!$name || !$phone_key) {
        beautycore_stage5_redirect('beautycore-customer-edit', array('id' => $customer_id, 'error' => 'Họ tên và số điện thoại là bắt buộc.'));
    }
    if ($email && !is_email($email)) {
        beautycore_stage5_redirect('beautycore-customer-edit', array('id' => $customer_id, 'error' => 'Email không hợp lệ.'));
    }
    $duplicate = beautycore_stage5_find_customer_by_phone($phone_key, $customer_id);
    if ($duplicate) {
        beautycore_stage5_redirect('beautycore-customer-edit', array('id' => $duplicate->ID, 'error' => 'Số điện thoại này đã có hồ sơ. Hãy kiểm tra và gộp nếu cần.'));
    }
    $post_id = wp_insert_post(wp_slash(array('ID' => $customer_id, 'post_type' => BEAUTYCORE_CUSTOMER_POST_TYPE, 'post_status' => 'publish', 'post_title' => $name, 'post_content' => isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '')), true);
    if (is_wp_error($post_id)) {
        beautycore_stage5_redirect('beautycore-customer-edit', array('id' => $customer_id, 'error' => $post_id->get_error_message()));
    }
    update_post_meta($post_id, '_beautycore_customer_phone', $phone);
    update_post_meta($post_id, '_beautycore_customer_phone_key', $phone_key);
    update_post_meta($post_id, '_beautycore_customer_email', $email);
    update_post_meta($post_id, '_beautycore_customer_birthday', beautycore_stage5_date($_POST['birthday'] ?? ''));
    if (function_exists('beautycore_audit_log')) {
        beautycore_audit_log($customer_id ? 'customer_updated' : 'customer_created', array('name' => $name), 'beautycore_customer', $post_id);
    }
    beautycore_stage5_redirect('beautycore-customers', array('updated' => 1));
}
add_action('admin_post_beautycore_save_customer', 'beautycore_handle_save_customer');

function beautycore_handle_merge_customer() {
    $primary_id = isset($_GET['primary_id']) ? absint($_GET['primary_id']) : 0;
    $duplicate_id = isset($_GET['duplicate_id']) ? absint($_GET['duplicate_id']) : 0;
    if (!current_user_can('manage_beautycore_customers') || !$primary_id || !$duplicate_id) {
        wp_die('Bạn không có quyền gộp hồ sơ khách hàng.');
    }
    check_admin_referer('beautycore_merge_customer_' . $primary_id . '_' . $duplicate_id);
    $primary = beautycore_customer_data($primary_id);
    $duplicate = beautycore_customer_data($duplicate_id);
    if (!$primary || !$duplicate || $primary['phone_key'] !== $duplicate['phone_key']) {
        beautycore_stage5_redirect('beautycore-customer-edit', array('id' => $primary_id, 'error' => 'Chỉ có thể gộp hai hồ sơ cùng số điện thoại.'));
    }
    foreach (beautycore_customer_appointments($duplicate_id, $duplicate['phone']) as $appointment) {
        update_post_meta($appointment['id'], '_beautycore_customer_id', $primary_id);
    }
    wp_delete_post($duplicate_id, true);
    if (function_exists('beautycore_audit_log')) {
        beautycore_audit_log('customer_merged', array('duplicate_id' => $duplicate_id), 'beautycore_customer', $primary_id);
    }
    beautycore_stage5_redirect('beautycore-customers', array('updated' => 1));
}
add_action('admin_post_beautycore_merge_customer', 'beautycore_handle_merge_customer');

function beautycore_stage5_render_voucher_list() {
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all';
    $statuses = array('all' => 'Tất cả trạng thái', 'active' => 'Đang hoạt động', 'inactive' => 'Tạm dừng', 'expired' => 'Ngoài thời hạn', 'exhausted' => 'Đã hết lượt');
    $vouchers = array_map('beautycore_voucher_data', get_posts(array('post_type' => BEAUTYCORE_VOUCHER_POST_TYPE, 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC')));
    $vouchers = array_values(array_filter($vouchers, function ($voucher) use ($search, $status) {
        return (!$search || stripos($voucher['code'], $search) !== false) && ($status === 'all' || $voucher['status'] === $status);
    }));
    echo '<div class="beautycore-service-toolbar"><div><a class="button button-primary beautycore-stage5-open" data-stage5-entity="voucher" data-object-id="0" href="' . esc_url(beautycore_stage5_edit_url('beautycore-voucher-edit')) . '">Thêm voucher</a></div><span class="description">Điều kiện áp dụng được kiểm tra trước khi voucher được dùng.</span></div>';
    beautycore_stage5_render_modal();
    echo '<form method="get" class="beautycore-service-filters beautycore-stage5-filters" data-beautycore-stage5-filter><input type="hidden" name="page" value="beautycore-promotions"><input type="search" name="s" value="' . esc_attr($search) . '" placeholder="Tìm mã voucher" aria-label="Tìm voucher"><select name="status">';
    foreach ($statuses as $key => $label) { echo '<option value="' . esc_attr($key) . '" ' . selected($status, $key, false) . '>' . esc_html($label) . '</option>'; }
    echo '</select><button class="button">Lọc</button></form>';
    if (!$vouchers) { echo '<div class="beautycore-empty-state"><strong>Chưa có voucher</strong><span>Tạo voucher để áp dụng ưu đãi theo dịch vụ, chi nhánh và giá trị đơn.</span></div>'; return; }
    echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table beautycore-stage5-table"><thead><tr><th>Mã</th><th>Ưu đãi</th><th>Hiệu lực</th><th>Đã dùng</th><th>Trạng thái</th><th></th></tr></thead><tbody>';
    foreach ($vouchers as $voucher) {
        $value = $voucher['type'] === 'percent' ? rtrim(rtrim(number_format($voucher['value'], 2, '.', ''), '0'), '.') . '%' : beautycore_service_format_price($voucher['value']);
        $period = ($voucher['start_date'] ?: 'Ngay') . ' – ' . ($voucher['end_date'] ?: 'Không hạn');
        $usage = $voucher['used_count'] . ($voucher['usage_limit'] ? ' / ' . $voucher['usage_limit'] : ' / Không giới hạn');
        echo '<tr><td><strong>' . esc_html($voucher['code']) . '</strong></td><td>' . esc_html($value) . '<small>' . esc_html($voucher['min_order'] ? 'Đơn từ ' . beautycore_service_format_price($voucher['min_order']) : 'Không yêu cầu đơn tối thiểu') . '</small></td><td>' . esc_html($period) . '</td><td>' . esc_html($usage) . '</td><td><span class="beautycore-status beautycore-status-' . esc_attr($voucher['status']) . '">' . esc_html(beautycore_voucher_status_label($voucher['status'])) . '</span></td><td><a class="button button-small beautycore-stage5-open" data-stage5-entity="voucher" data-object-id="' . esc_attr($voucher['id']) . '" href="' . esc_url(beautycore_stage5_edit_url('beautycore-voucher-edit', $voucher['id'])) . '">Sửa</a></td></tr>';
    }
    echo '</tbody></table></div>';
}

function beautycore_stage5_render_assignment_options($name, $selected, $options, $empty_label) {
    echo '<select id="' . esc_attr($name) . '" name="' . esc_attr($name) . '[]" multiple size="8"><option value="">' . esc_html($empty_label) . '</option>';
    foreach ($options as $id => $label) { echo '<option value="' . esc_attr($id) . '" ' . selected(in_array((string) $id, array_map('strval', (array) $selected), true), true, false) . '>' . esc_html($label) . '</option>'; }
    echo '</select>';
}

function beautycore_render_voucher_edit_page($voucher_id_override = null, $fragment = false) {
    if (!current_user_can('manage_beautycore_promotions')) { wp_die('Bạn không có quyền quản lý voucher.'); }
    $voucher_id = $voucher_id_override === null ? (isset($_GET['id']) ? absint($_GET['id']) : 0) : absint($voucher_id_override);
    $voucher = $voucher_id ? beautycore_voucher_data($voucher_id) : array('id' => 0, 'code' => '', 'type' => 'percent', 'value' => '', 'start_date' => '', 'end_date' => '', 'service_ids' => array(), 'branch_ids' => array(), 'usage_limit' => 0, 'used_count' => 0, 'min_order' => '', 'enabled' => true, 'status' => 'active');
    if ($voucher_id && !$voucher) { wp_die('Voucher không hợp lệ.'); }
    $services = function_exists('beautycore_appointment_service_options') ? beautycore_appointment_service_options() : array();
    $service_options = array(); foreach ($services as $id => $service) { $service_options[$id] = $service['name']; }
    $branches = function_exists('beautycore_appointment_branch_options') ? beautycore_appointment_branch_options() : array();
    if (!$fragment) {
        beautycore_admin_page_header($voucher_id ? 'Sửa voucher' : 'Thêm voucher', 'Mã voucher chỉ được dùng khi thỏa điều kiện thời hạn, lượt dùng, dịch vụ, chi nhánh và giá trị đơn.');
        beautycore_stage5_notice();
    }
    echo '<form class="beautycore-service-form beautycore-stage5-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="beautycore_save_voucher"><input type="hidden" name="voucher_id" value="' . esc_attr($voucher['id']) . '">'; wp_nonce_field('beautycore_save_voucher');
    echo '<div class="beautycore-service-form-layout"><div class="beautycore-service-form-main"><section class="beautycore-panel"><h2>Thiết lập ưu đãi</h2><div class="beautycore-form-grid"><div class="beautycore-form-field"><label for="voucher_code"><strong>Mã voucher *</strong></label><input id="voucher_code" name="code" required value="' . esc_attr($voucher['code']) . '" placeholder="BEAUTY10"></div><div class="beautycore-form-field"><label for="voucher_type"><strong>Loại giảm *</strong></label><select id="voucher_type" name="type">';
    foreach (beautycore_voucher_discount_types() as $key => $label) { echo '<option value="' . esc_attr($key) . '" ' . selected($voucher['type'], $key, false) . '>' . esc_html($label) . '</option>'; }
    echo '</select></div><div class="beautycore-form-field"><label for="voucher_value"><strong>Giá trị *</strong></label><input id="voucher_value" type="number" min="0" step="0.01" name="value" required value="' . esc_attr($voucher['value']) . '"></div><div class="beautycore-form-field"><label for="voucher_min_order"><strong>Giá trị đơn tối thiểu</strong></label><input id="voucher_min_order" type="number" min="0" step="1000" name="min_order" value="' . esc_attr($voucher['min_order']) . '"></div><div class="beautycore-form-field"><label for="voucher_start"><strong>Ngày bắt đầu</strong></label><input id="voucher_start" type="date" name="start_date" value="' . esc_attr($voucher['start_date']) . '"></div><div class="beautycore-form-field"><label for="voucher_end"><strong>Ngày kết thúc</strong></label><input id="voucher_end" type="date" name="end_date" value="' . esc_attr($voucher['end_date']) . '"></div><div class="beautycore-form-field"><label for="voucher_limit"><strong>Giới hạn lượt dùng</strong></label><input id="voucher_limit" type="number" min="0" name="usage_limit" value="' . esc_attr($voucher['usage_limit']) . '"><p class="description">Để 0 nếu không giới hạn.</p></div></div></section>';
    echo '<section class="beautycore-panel"><h2>Phạm vi áp dụng</h2><div class="beautycore-assignment-grid"><div><h3>Dịch vụ</h3><p class="description">Không chọn nghĩa là áp dụng cho mọi dịch vụ.</p>'; beautycore_stage5_render_assignment_options('service_ids', $voucher['service_ids'], $service_options, 'Mọi dịch vụ'); echo '</div><div><h3>Chi nhánh</h3><p class="description">Không chọn nghĩa là áp dụng cho mọi chi nhánh.</p>'; beautycore_stage5_render_assignment_options('branch_ids', $voucher['branch_ids'], $branches, 'Mọi chi nhánh'); echo '</div></div></section></div><aside><section class="beautycore-panel"><h2>Trạng thái</h2><p class="beautycore-checkbox-field"><label><input type="checkbox" name="enabled" value="1" ' . checked($voucher['enabled'], true, false) . '> Cho phép sử dụng voucher</label></p><p class="description">Đã dùng: ' . esc_html($voucher['used_count']) . ' lượt.</p><p><button class="button button-primary">Lưu voucher</button></p><p><a class="button" href="' . esc_url(admin_url('admin.php?page=beautycore-promotions')) . '">Quay lại danh sách</a></p></section>';
    if ($voucher['id']) { $delete_url = wp_nonce_url(admin_url('admin-post.php?action=beautycore_delete_voucher&id=' . $voucher['id']), 'beautycore_delete_voucher_' . $voucher['id']); echo '<section class="beautycore-panel"><h2>Xóa voucher</h2><p><a class="button button-link-delete" href="' . esc_url($delete_url) . '" onclick="return confirm(\'Xóa voucher này?\');">Xóa vĩnh viễn</a></p></section>'; }
    echo '</aside></div></form>';
    if (!$fragment) {
        beautycore_admin_page_footer();
    }
}

function beautycore_handle_save_voucher() {
    if (!current_user_can('manage_beautycore_promotions')) { wp_die('Bạn không có quyền quản lý voucher.'); }
    check_admin_referer('beautycore_save_voucher');
    $voucher_id = isset($_POST['voucher_id']) ? absint($_POST['voucher_id']) : 0;
    $code = strtoupper(preg_replace('/[^A-Z0-9_-]/', '', sanitize_text_field(wp_unslash($_POST['code'] ?? ''))));
    $type = isset($_POST['type']) ? sanitize_key(wp_unslash($_POST['type'])) : '';
    $value = beautycore_stage5_money($_POST['value'] ?? 0);
    $start_date = beautycore_stage5_date($_POST['start_date'] ?? ''); $end_date = beautycore_stage5_date($_POST['end_date'] ?? '');
    if (strlen($code) < 3 || !isset(beautycore_voucher_discount_types()[$type]) || $value <= 0 || ($type === 'percent' && $value > 100)) {
        beautycore_stage5_redirect('beautycore-voucher-edit', array('id' => $voucher_id, 'error' => 'Mã voucher hoặc giá trị giảm không hợp lệ.'));
    }
    if ($start_date && $end_date && $start_date > $end_date) { beautycore_stage5_redirect('beautycore-voucher-edit', array('id' => $voucher_id, 'error' => 'Ngày kết thúc phải sau ngày bắt đầu.')); }
    $same_code = beautycore_find_voucher_by_code($code);
    if ($same_code && $same_code['id'] !== $voucher_id) { beautycore_stage5_redirect('beautycore-voucher-edit', array('id' => $voucher_id, 'error' => 'Mã voucher đã tồn tại.')); }
    $post_id = wp_insert_post(array('ID' => $voucher_id, 'post_type' => BEAUTYCORE_VOUCHER_POST_TYPE, 'post_status' => 'publish', 'post_title' => $code), true);
    if (is_wp_error($post_id)) { beautycore_stage5_redirect('beautycore-voucher-edit', array('id' => $voucher_id, 'error' => $post_id->get_error_message())); }
    $service_ids = beautycore_stage5_clean_post_ids($_POST['service_ids'] ?? array(), 'beautycore_service');
    $branches = function_exists('beautycore_appointment_branch_options') ? beautycore_appointment_branch_options() : array();
    $branch_ids = array_values(array_filter(array_map('sanitize_text_field', (array) ($_POST['branch_ids'] ?? array())), function ($id) use ($branches) { return $id !== '' && isset($branches[$id]); }));
    foreach (array('_beautycore_voucher_code' => $code, '_beautycore_voucher_discount_type' => $type, '_beautycore_voucher_value' => $value, '_beautycore_voucher_start_date' => $start_date, '_beautycore_voucher_end_date' => $end_date, '_beautycore_voucher_service_ids' => $service_ids, '_beautycore_voucher_branch_ids' => $branch_ids, '_beautycore_voucher_usage_limit' => absint($_POST['usage_limit'] ?? 0), '_beautycore_voucher_min_order' => beautycore_stage5_money($_POST['min_order'] ?? 0), '_beautycore_voucher_enabled' => !empty($_POST['enabled']) ? '1' : '0') as $key => $value_to_save) { update_post_meta($post_id, $key, $value_to_save); }
    if (!$voucher_id) { update_post_meta($post_id, '_beautycore_voucher_used_count', 0); }
    if (function_exists('beautycore_audit_log')) { beautycore_audit_log($voucher_id ? 'voucher_updated' : 'voucher_created', array('code' => $code), 'beautycore_voucher', $post_id); }
    beautycore_stage5_redirect('beautycore-promotions', array('updated' => 1));
}
add_action('admin_post_beautycore_save_voucher', 'beautycore_handle_save_voucher');

function beautycore_handle_delete_voucher() {
    $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    if (!current_user_can('manage_beautycore_promotions') || get_post_type($id) !== BEAUTYCORE_VOUCHER_POST_TYPE) { wp_die('Bạn không có quyền xóa voucher.'); }
    check_admin_referer('beautycore_delete_voucher_' . $id);
    wp_delete_post($id, true);
    if (function_exists('beautycore_audit_log')) { beautycore_audit_log('voucher_deleted', array(), 'beautycore_voucher', $id); }
    beautycore_stage5_redirect('beautycore-promotions', array('deleted' => 1));
}
add_action('admin_post_beautycore_delete_voucher', 'beautycore_handle_delete_voucher');

function beautycore_stage5_service_name($service_id) {
    $service = get_post(absint($service_id));
    return $service && $service->post_type === 'beautycore_service' ? $service->post_title : '';
}

function beautycore_stage5_render_review_list() {
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all';
    $statuses = array_merge(array('all' => 'Tất cả trạng thái'), beautycore_review_statuses());
    $reviews = array_map('beautycore_review_data', get_posts(array('post_type' => BEAUTYCORE_REVIEW_POST_TYPE, 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC')));
    $reviews = array_values(array_filter($reviews, function ($review) use ($search, $status) { return (!$search || stripos($review['name'] . ' ' . $review['content'], $search) !== false) && ($status === 'all' || $review['status'] === $status); }));
    echo '<div class="beautycore-service-toolbar"><div><a class="button button-primary beautycore-stage5-open" data-stage5-entity="review" data-object-id="0" href="' . esc_url(beautycore_stage5_edit_url('beautycore-review-edit')) . '">Thêm đánh giá</a></div><span class="description">Chỉ đánh giá Approved mới có thể được lấy ra để hiển thị công khai.</span></div>';
    beautycore_stage5_render_modal();
    echo '<form method="get" class="beautycore-service-filters beautycore-stage5-filters" data-beautycore-stage5-filter><input type="hidden" name="page" value="beautycore-reviews"><input type="search" name="s" value="' . esc_attr($search) . '" placeholder="Tên hoặc nội dung đánh giá" aria-label="Tìm đánh giá"><select name="status">'; foreach ($statuses as $key => $label) { echo '<option value="' . esc_attr($key) . '" ' . selected($status, $key, false) . '>' . esc_html($label) . '</option>'; } echo '</select><button class="button">Lọc</button></form>';
    if (!$reviews) { echo '<div class="beautycore-empty-state"><strong>Chưa có đánh giá</strong><span>Đánh giá mới sẽ ở trạng thái Pending cho đến khi được duyệt.</span></div>'; return; }
    echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table beautycore-stage5-table"><thead><tr><th>Khách hàng</th><th>Đánh giá</th><th>Dịch vụ</th><th>Nguồn</th><th>Trạng thái</th><th></th></tr></thead><tbody>';
    foreach ($reviews as $review) {
        $stars = str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']);
        $edit_url = beautycore_stage5_edit_url('beautycore-review-edit', $review['id']);
        echo '<tr><td><strong>' . esc_html($review['name']) . '</strong><small>' . esc_html($review['email']) . '</small></td><td><span class="beautycore-stage5-stars" aria-label="' . esc_attr($review['rating'] . ' trên 5 sao') . '">' . esc_html($stars) . '</span><p class="beautycore-stage5-review-excerpt">' . esc_html(wp_trim_words($review['content'], 22)) . '</p></td><td>' . esc_html(beautycore_stage5_service_name($review['service_id']) ?: '—') . '</td><td>' . esc_html($review['source'] ?: 'website') . '</td><td><span class="beautycore-status beautycore-status-' . esc_attr($review['status']) . '">' . esc_html(beautycore_review_statuses()[$review['status']]) . '</span></td><td><a class="button button-small beautycore-stage5-open" data-stage5-entity="review" data-object-id="' . esc_attr($review['id']) . '" href="' . esc_url($edit_url) . '">Xem</a></td></tr>';
    }
    echo '</tbody></table></div>';
}

function beautycore_render_review_edit_page($review_id_override = null, $fragment = false) {
    if (!current_user_can('manage_beautycore_reviews')) { wp_die('Bạn không có quyền quản lý đánh giá.'); }
    $review_id = $review_id_override === null ? (isset($_GET['id']) ? absint($_GET['id']) : 0) : absint($review_id_override);
    $review = $review_id ? beautycore_review_data($review_id) : array('id' => 0, 'name' => '', 'email' => '', 'customer_id' => 0, 'service_id' => 0, 'staff_id' => 0, 'rating' => 5, 'source' => 'website', 'status' => 'pending', 'content' => '');
    if ($review_id && !$review) { wp_die('Đánh giá không hợp lệ.'); }
    $services = function_exists('beautycore_appointment_service_options') ? beautycore_appointment_service_options() : array();
    $staff = function_exists('beautycore_appointment_staff_options') ? beautycore_appointment_staff_options() : array();
    if (!$fragment) {
        beautycore_admin_page_header($review_id ? 'Duyệt đánh giá' : 'Thêm đánh giá', 'Đánh giá mới mặc định ở Pending và không được hiển thị ra website.');
        beautycore_stage5_notice();
    }
    echo '<form class="beautycore-service-form beautycore-stage5-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="beautycore_save_review"><input type="hidden" name="review_id" value="' . esc_attr($review['id']) . '">'; wp_nonce_field('beautycore_save_review');
    echo '<div class="beautycore-service-form-layout"><div class="beautycore-service-form-main"><section class="beautycore-panel"><h2>Nội dung đánh giá</h2><div class="beautycore-form-grid"><div class="beautycore-form-field"><label for="review_name"><strong>Tên khách *</strong></label><input id="review_name" name="name" required value="' . esc_attr($review['name']) . '"></div><div class="beautycore-form-field"><label for="review_email"><strong>Email</strong></label><input id="review_email" type="email" name="email" value="' . esc_attr($review['email']) . '"></div><div class="beautycore-form-field"><label for="review_customer"><strong>Hồ sơ khách hàng</strong></label><select id="review_customer" name="customer_id"><option value="0">Chưa liên kết</option>'; foreach (beautycore_stage5_customer_options() as $id => $label) { echo '<option value="' . esc_attr($id) . '" ' . selected($review['customer_id'], $id, false) . '>' . esc_html($label) . '</option>'; } echo '</select></div><div class="beautycore-form-field"><label for="review_service"><strong>Dịch vụ</strong></label><select id="review_service" name="service_id"><option value="0">Chưa chọn dịch vụ</option>'; foreach ($services as $id => $service) { echo '<option value="' . esc_attr($id) . '" ' . selected($review['service_id'], $id, false) . '>' . esc_html($service['name']) . '</option>'; } echo '</select></div><div class="beautycore-form-field"><label for="review_staff"><strong>Nhân viên phục vụ</strong></label><select id="review_staff" name="staff_id"><option value="0">Chưa gán nhân viên</option>'; foreach ($staff as $id => $label) { echo '<option value="' . esc_attr($id) . '" ' . selected($review['staff_id'], $id, false) . '>' . esc_html($label) . '</option>'; } echo '</select></div><div class="beautycore-form-field"><label for="review_rating"><strong>Số sao *</strong></label><select id="review_rating" name="rating">'; for ($star = 5; $star >= 1; $star--) { echo '<option value="' . $star . '" ' . selected($review['rating'], $star, false) . '>' . $star . ' sao</option>'; } echo '</select></div><div class="beautycore-form-field"><label for="review_source"><strong>Nguồn</strong></label><select id="review_source" name="source"><option value="website" ' . selected($review['source'], 'website', false) . '>Website</option><option value="google" ' . selected($review['source'], 'google', false) . '>Google</option><option value="frontdesk" ' . selected($review['source'], 'frontdesk', false) . '>Tại quầy</option></select></div></div><div class="beautycore-form-field"><label for="review_content"><strong>Nội dung *</strong></label><textarea id="review_content" name="content" required>' . esc_textarea($review['content']) . '</textarea></div></section></div><aside><section class="beautycore-panel"><h2>Kiểm duyệt</h2><div class="beautycore-form-field"><label for="review_status"><strong>Trạng thái</strong></label><select id="review_status" name="status">'; foreach (beautycore_review_statuses() as $key => $label) { echo '<option value="' . esc_attr($key) . '" ' . selected($review['status'], $key, false) . '>' . esc_html($label) . '</option>'; } echo '</select></div><p class="description">Chỉ Approved có thể hiển thị công khai. Hidden giữ lại dữ liệu nhưng không hiển thị.</p><p><button class="button button-primary">Lưu đánh giá</button></p><p><a class="button" href="' . esc_url(admin_url('admin.php?page=beautycore-reviews')) . '">Quay lại danh sách</a></p></section></aside></div></form>';
    if (!$fragment) {
        beautycore_admin_page_footer();
    }
}

function beautycore_handle_save_review() {
    if (!current_user_can('manage_beautycore_reviews')) { wp_die('Bạn không có quyền quản lý đánh giá.'); }
    check_admin_referer('beautycore_save_review');
    $review_id = isset($_POST['review_id']) ? absint($_POST['review_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : ''; $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
    $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'pending';
    if (!$name || !$content || !isset(beautycore_review_statuses()[$status])) { beautycore_stage5_redirect('beautycore-review-edit', array('id' => $review_id, 'error' => 'Thông tin đánh giá không hợp lệ.')); }
    $post_id = wp_insert_post(wp_slash(array('ID' => $review_id, 'post_type' => BEAUTYCORE_REVIEW_POST_TYPE, 'post_status' => 'publish', 'post_title' => $name, 'post_content' => $content)), true);
    if (is_wp_error($post_id)) { beautycore_stage5_redirect('beautycore-review-edit', array('id' => $review_id, 'error' => $post_id->get_error_message())); }
    foreach (array('_beautycore_review_status' => $status, '_beautycore_review_rating' => max(1, min(5, absint($_POST['rating'] ?? 5))), '_beautycore_review_email' => sanitize_email(wp_unslash($_POST['email'] ?? '')), '_beautycore_review_customer_id' => absint($_POST['customer_id'] ?? 0), '_beautycore_review_service_id' => absint($_POST['service_id'] ?? 0), '_beautycore_review_staff_id' => absint($_POST['staff_id'] ?? 0), '_beautycore_review_source' => sanitize_key(wp_unslash($_POST['source'] ?? 'website'))) as $key => $value) { update_post_meta($post_id, $key, $value); }
    if ($status === 'approved') { do_action('beautycore_review_approved', $post_id); } elseif (function_exists('beautycore_audit_log')) { beautycore_audit_log($review_id ? 'review_updated' : 'review_created', array('status' => $status), 'beautycore_review', $post_id); }
    beautycore_stage5_redirect('beautycore-reviews', array('updated' => 1));
}
add_action('admin_post_beautycore_save_review', 'beautycore_handle_save_review');
