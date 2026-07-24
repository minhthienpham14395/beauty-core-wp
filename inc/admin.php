<?php
/**
 * Beauty Core administration, roles, dashboard and audit trail.
 *
 * The booking and CRM modules are intentionally consumed through small
 * filters/hooks here. That keeps the dashboard useful before those modules
 * are introduced in later phases without coupling the theme to a plugin.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BEAUTYCORE_ADMIN_VERSION', '1.3.11');

function beautycore_admin_role_labels() {
    return array(
        'administrator' => 'Quản trị viên',
        'owner'         => 'Chủ cơ sở',
        'manager'       => 'Quản lý',
        'receptionist'  => 'Lễ tân',
        'staff'         => 'Nhân viên',
        'editor'        => 'Biên tập viên',
        'author'        => 'Tác giả',
        'contributor'   => 'Cộng tác viên',
        'subscriber'    => 'Thành viên',
    );
}

/**
 * Return the capabilities used by the Beauty Core admin area.
 */
function beautycore_admin_role_capabilities() {
    return array(
        'owner' => array(
        'read'                         => true,
        'view_beautycore_dashboard'    => true,
        'view_beautycore_services'     => true,
        'manage_beautycore_services'   => true,
        'manage_beautycore_pricing'    => true,
        'view_beautycore_schedule'     => true,
        'manage_beautycore_appointments'=> true,
        'view_beautycore_reports'       => true,
            'view_beautycore_settings'      => true,
            'view_beautycore_audit'         => true,
        ),
        'manager' => array(
            'read'                         => true,
            'view_beautycore_dashboard'    => true,
            'view_beautycore_schedule'      => true,
            'view_beautycore_services'     => true,
            'view_beautycore_branches'     => true,
            'view_beautycore_reports'      => true,
            'view_beautycore_audit'        => true,
            'manage_beautycore_appointments' => true,
            'manage_beautycore_services'     => true,
            'manage_beautycore_pricing'     => true,
            'manage_beautycore_staff'       => true,
            'manage_beautycore_branches'    => true,
            'manage_beautycore_customers'   => true,
            'manage_beautycore_promotions'  => true,
            'manage_beautycore_reviews'     => true,
        ),
        'receptionist' => array(
            'read'                         => true,
            'view_beautycore_dashboard'    => true,
            'view_beautycore_schedule'      => true,
            'view_beautycore_services'     => true,
            'view_beautycore_branches'     => true,
            'manage_beautycore_appointments' => true,
            'manage_beautycore_customers'   => true,
        ),
        'staff' => array(
            'read'                         => true,
            'view_beautycore_dashboard'    => true,
            'view_beautycore_schedule'      => true,
            'view_beautycore_services'     => true,
            'view_beautycore_branches'     => true,
            'view_beautycore_own_schedule' => true,
        ),
        'editor' => array(
            'read'                         => true,
            'view_beautycore_dashboard'    => true,
            'edit_posts'                   => true,
            'publish_posts'                => true,
            'delete_posts'                 => true,
            'edit_pages'                   => true,
            'publish_pages'                => true,
            'delete_pages'                 => true,
            'upload_files'                 => true,
            'manage_categories'            => true,
        ),
    );
}

/**
 * Create/update the five business roles without changing existing users.
 */
function beautycore_register_roles() {
    $capabilities = beautycore_admin_role_capabilities();
    $all_labels = beautycore_admin_role_labels();
    $labels = array_intersect_key($all_labels, $capabilities);

    foreach ($labels as $role_key => $label) {
        $role = get_role($role_key);
        if (!$role) {
            $role = add_role($role_key, $label, $capabilities[$role_key]);
        }

        if ($role) {
            foreach ($capabilities[$role_key] as $capability => $granted) {
                if ($granted) {
                    $role->add_cap($capability);
                }
            }
        }
    }

    // Role names are stored separately from capabilities. Update the saved
    // definitions so existing roles are renamed without removing user access.
    $wp_roles = wp_roles();
    $stored_roles = get_option($wp_roles->role_key, array());
    if (is_array($stored_roles)) {
        foreach ($all_labels as $role_key => $label) {
            if (isset($stored_roles[$role_key])) {
                $stored_roles[$role_key]['name'] = $label;
            }
        }
        update_option($wp_roles->role_key, $stored_roles);
        $wp_roles->roles = $stored_roles;
        $wp_roles->role_names = array();
        foreach ($stored_roles as $role_key => $role_data) {
            $wp_roles->role_names[$role_key] = $role_data['name'];
        }
    }

    $administrator = get_role('administrator');
    if ($administrator) {
        foreach (array_keys($capabilities['manager']) as $capability) {
            $administrator->add_cap($capability);
        }
        foreach (array(
            'view_beautycore_settings',
            'view_beautycore_audit',
            'view_beautycore_own_schedule',
        ) as $capability) {
            $administrator->add_cap($capability);
        }
    }

    update_option('beautycore_roles_version', BEAUTYCORE_ADMIN_VERSION, false);
}
add_action('after_switch_theme', 'beautycore_register_roles');

function beautycore_maybe_register_roles() {
    if (get_option('beautycore_roles_version') !== BEAUTYCORE_ADMIN_VERSION) {
        beautycore_register_roles();
    }
}
add_action('admin_init', 'beautycore_maybe_register_roles', 1);

function beautycore_admin_menu() {
    add_menu_page(
        'Beauty Core',
        'Beauty Core',
        'view_beautycore_dashboard',
        'beautycore-dashboard',
        'beautycore_render_dashboard_page',
        'dashicons-heart',
        1
    );

    add_submenu_page('beautycore-dashboard', 'Tổng quan', 'Tổng quan', 'view_beautycore_dashboard', 'beautycore-dashboard', 'beautycore_render_dashboard_page');
    add_submenu_page('beautycore-dashboard', 'Lịch hẹn', 'Lịch hẹn', 'view_beautycore_schedule', 'beautycore-appointments', 'beautycore_render_module_page');
    add_submenu_page('beautycore-dashboard', 'Dịch vụ', 'Dịch vụ', 'view_beautycore_services', 'beautycore-services', 'beautycore_render_module_page');
    add_submenu_page('beautycore-dashboard', 'Nhân viên', 'Nhân viên', 'manage_beautycore_staff', 'beautycore-staff', 'beautycore_render_module_page');
    add_submenu_page('beautycore-dashboard', 'Chi nhánh', 'Chi nhánh', 'view_beautycore_branches', 'beautycore-branches', 'beautycore_render_module_page');
    add_submenu_page('beautycore-dashboard', 'Khách hàng', 'Khách hàng', 'manage_beautycore_customers', 'beautycore-customers', 'beautycore_render_module_page');
    add_submenu_page('beautycore-dashboard', 'Khuyến mãi', 'Khuyến mãi', 'manage_beautycore_promotions', 'beautycore-promotions', 'beautycore_render_module_page');
    add_submenu_page('beautycore-dashboard', 'Đánh giá', 'Đánh giá', 'manage_beautycore_reviews', 'beautycore-reviews', 'beautycore_render_module_page');
    add_submenu_page('beautycore-dashboard', 'Báo cáo', 'Báo cáo', 'view_beautycore_reports', 'beautycore-reports', 'beautycore_render_module_page');
    add_submenu_page('beautycore-dashboard', 'Cấu hình', 'Cấu hình', 'view_beautycore_settings', 'beautycore-settings', 'beautycore_render_module_page');
    add_submenu_page('beautycore-dashboard', 'Nhật ký thao tác', 'Nhật ký thao tác', 'view_beautycore_audit', 'beautycore-audit', 'beautycore_render_module_page');
    add_submenu_page(null, 'Sửa lịch hẹn', 'Sửa lịch hẹn', 'manage_beautycore_appointments', 'beautycore-appointment-edit', 'beautycore_render_appointment_edit_page');
}
add_action('admin_menu', 'beautycore_admin_menu', 9);

function beautycore_redirect_legacy_pricing_page() {
    if (!isset($_GET['page']) || sanitize_key(wp_unslash($_GET['page'])) !== 'beautycore-pricing') {
        return;
    }
    if (!current_user_can('view_beautycore_services')) {
        return;
    }

    wp_safe_redirect(admin_url('admin.php?page=beautycore-services'));
    exit;
}
add_action('admin_init', 'beautycore_redirect_legacy_pricing_page', 2);

function beautycore_wordpress_menu_page() {
    if (!current_user_can('read')) {
        wp_die('Bạn không có quyền truy cập khu vực WordPress.');
    }

    wp_safe_redirect(admin_url('index.php'));
    exit;
}

function beautycore_wordpress_admin_menu() {
    add_menu_page(
        'WordPress',
        'WordPress',
        'read',
        'beautycore-wordpress',
        'beautycore_wordpress_menu_page',
        'dashicons-wordpress',
        2
    );
}
add_action('admin_menu', 'beautycore_wordpress_admin_menu', 8);

function beautycore_admin_styles($hook) {
    if (strpos($hook, 'beautycore') === false && $hook !== 'index.php') {
        return;
    }

    wp_enqueue_style(
        'beautycore-admin',
        get_theme_file_uri('/assets/css/admin.css'),
        array(),
        BEAUTYCORE_ADMIN_VERSION
    );
}
add_action('admin_enqueue_scripts', 'beautycore_admin_styles');

function beautycore_is_administrator() {
    $user = wp_get_current_user();

    return $user && in_array('administrator', (array) $user->roles, true);
}

/**
 * Remove system controls from the menu for all non-Administrators.
 */
function beautycore_restrict_admin_menu() {
    if (beautycore_is_administrator()) {
        return;
    }

    foreach (array('plugins.php', 'themes.php', 'options-general.php', 'tools.php', 'update-core.php') as $menu_slug) {
        remove_menu_page($menu_slug);
    }
}
add_action('admin_menu', 'beautycore_restrict_admin_menu', 999);

/**
 * Keep native WordPress screens under their own top-level menu. Beauty Core
 * remains a separate top-level menu for spa operations and reports.
 */
function beautycore_sync_wordpress_admin_menus() {
    $menus = array(
        array('WordPress Dashboard', 'Dashboard hệ thống', 'read', 'index.php'),
        array('Bài viết WordPress', 'Bài viết WordPress', 'edit_posts', 'edit.php'),
        array('Media', 'Thư viện Media', 'upload_files', 'upload.php'),
        array('Trang', 'Trang website', 'edit_pages', 'edit.php?post_type=page'),
        array('Bình luận', 'Bình luận', 'moderate_comments', 'edit-comments.php'),
        array('Tài khoản của tôi', 'Tài khoản của tôi', 'read', 'profile.php'),
    );

    if (beautycore_is_administrator()) {
        $menus = array_merge($menus, array(
            array('Người dùng', 'Người dùng', 'list_users', 'users.php'),
            array('Plugins', 'Plugins', 'manage_options', 'plugins.php'),
            array('Giao diện', 'Giao diện', 'manage_options', 'themes.php'),
            array('Tùy biến giao diện', 'Tùy biến giao diện', 'manage_options', 'customize.php'),
            array('Menu WordPress', 'Menu WordPress', 'manage_options', 'nav-menus.php'),
            array('Widgets', 'Widgets', 'manage_options', 'widgets.php'),
            array('Cài đặt WordPress', 'Cài đặt WordPress', 'manage_options', 'options-general.php'),
            array('Công cụ WordPress', 'Công cụ WordPress', 'manage_options', 'tools.php'),
            array('Beauty Core Import', 'Beauty Core Import', 'manage_options', 'admin.php?page=beautycore-import'),
        ));
    }

    foreach ($menus as $menu) {
        if (current_user_can($menu[2])) {
            add_submenu_page('beautycore-wordpress', $menu[0], $menu[1], $menu[2], $menu[3]);
        }
    }

    $native_menus = array(
        'index.php',
        'edit.php',
        'upload.php',
        'edit.php?post_type=page',
        'edit-comments.php',
    );

    if (beautycore_is_administrator()) {
        $native_menus = array_merge($native_menus, array(
            'users.php',
            'plugins.php',
            'themes.php',
            'options-general.php',
            'tools.php',
        ));
    }

    foreach ($native_menus as $menu_slug) {
        remove_menu_page($menu_slug);
    }
}
add_action('admin_menu', 'beautycore_sync_wordpress_admin_menus', 1001);

/**
 * Protect direct URLs as well as hiding their menu entries.
 */
function beautycore_restrict_admin_pages() {
    if (beautycore_is_administrator() || !is_user_logged_in()) {
        return;
    }

    global $pagenow;
    $blocked_pages = array(
        'plugins.php',
        'plugin-install.php',
        'plugin-editor.php',
        'themes.php',
        'theme-install.php',
        'theme-editor.php',
        'customize.php',
        'widgets.php',
        'options-general.php',
        'options.php',
        'update-core.php',
        'update.php',
        'site-health.php',
        'export.php',
        'import.php',
    );

    if (in_array($pagenow, $blocked_pages, true)) {
        wp_die('Khu vực này chỉ dành cho Quản trị viên.');
    }

    if ($pagenow === 'admin.php' && isset($_GET['page'])) {
        $admin_page = sanitize_key(wp_unslash($_GET['page']));
        if (strpos($admin_page, 'beautycore-') !== 0) {
            wp_die('Trang quản trị mở rộng chỉ dành cho Quản trị viên.');
        }
    }

    if (in_array($pagenow, array('user-edit.php', 'user-new.php'), true) && !current_user_can('edit_users')) {
        wp_die('Bạn không có quyền quản lý tài khoản người dùng.');
    }
}
add_action('admin_init', 'beautycore_restrict_admin_pages', 5);

function beautycore_admin_page_header($title, $description = '') {
    echo '<div class="wrap beautycore-admin-page">';
    echo '<h1>' . esc_html($title) . '</h1>';
    if ($description) {
        echo '<p class="beautycore-page-description">' . esc_html($description) . '</p>';
    }
}

function beautycore_admin_page_footer() {
    echo '</div>';
}

function beautycore_dashboard_meta($post_id, $keys, $default = '') {
    foreach ((array) $keys as $key) {
        $value = get_post_meta($post_id, $key, true);
        if ($value !== '' && $value !== null) {
            return $value;
        }
    }

    return $default;
}

function beautycore_dashboard_appointment_post_types() {
    return apply_filters('beautycore_dashboard_appointment_post_types', array(
        'beautycore_appointment',
        'beautycore_booking',
    ));
}

function beautycore_dashboard_appointment_data($post) {
    $date = beautycore_dashboard_meta($post->ID, array(
        '_beautycore_appointment_start',
        '_beautycore_appointment_datetime',
        '_beautycore_booking_datetime',
        '_beautycore_appointment_date',
        '_beautycore_booking_date',
        'appointment_datetime',
        'booking_datetime',
        'appointment_date',
    ));
    $status = beautycore_dashboard_meta($post->ID, array(
        '_beautycore_appointment_status',
        '_beautycore_booking_status',
        'appointment_status',
        'booking_status',
    ), $post->post_status);
    $staff_id = absint(beautycore_dashboard_meta($post->ID, array(
        '_beautycore_staff_id',
        '_beautycore_employee_id',
        'staff_id',
    ), 0));

    return array(
        'id'       => (int) $post->ID,
        'timestamp'=> $date ? strtotime($date) : 0,
        'date'     => (string) $date,
        'status'   => is_scalar($status) ? sanitize_key((string) $status) : '',
        'customer' => (string) beautycore_dashboard_meta($post->ID, array('_beautycore_customer_name', 'customer_name', 'customer'), $post->post_title),
        'service'  => (string) beautycore_dashboard_meta($post->ID, array('_beautycore_service_name', 'service_name', 'service'), 'Chưa chọn dịch vụ'),
        'staff_id' => $staff_id,
        'branch'   => (string) beautycore_dashboard_meta($post->ID, array('_beautycore_branch_name', 'branch_name', 'branch'), ''),
        'edit_url' => get_edit_post_link($post->ID, 'display'),
    );
}

function beautycore_dashboard_appointments() {
    $appointments = array();

    foreach (beautycore_dashboard_appointment_post_types() as $post_type) {
        if (!post_type_exists($post_type)) {
            continue;
        }

        $posts = get_posts(array(
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'posts_per_page' => 500,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        foreach ($posts as $post) {
            $appointments[] = beautycore_dashboard_appointment_data($post);
        }
    }

    return apply_filters('beautycore_dashboard_appointments', $appointments);
}

function beautycore_dashboard_is_cancelled($status) {
    $status = is_scalar($status) ? sanitize_key((string) $status) : '';

    return in_array($status, array('cancelled', 'canceled', 'trash', 'refunded'), true);
}

function beautycore_dashboard_metrics() {
    $appointments = beautycore_dashboard_appointments();
    $today = current_time('Y-m-d');
    $now = current_time('timestamp');
    $today_appointments = array();
    $popular_services = array();
    $pending = 0;
    $upcoming = 0;

    foreach ($appointments as $appointment) {
        if (beautycore_dashboard_is_cancelled($appointment['status'])) {
            continue;
        }

        if ($appointment['date'] && strpos($appointment['date'], $today) === 0) {
            $today_appointments[] = $appointment;
        }
        if ($appointment['status'] === 'pending') {
            $pending++;
        }
        if ($appointment['timestamp'] && $appointment['timestamp'] >= $now) {
            $upcoming++;
        }
        if ($appointment['service']) {
            if (!isset($popular_services[$appointment['service']])) {
                $popular_services[$appointment['service']] = 0;
            }
            $popular_services[$appointment['service']]++;
        }
    }

    uasort($popular_services, function ($left, $right) {
        return $right - $left;
    });

    $pending_reviews = get_comments(array(
        'status' => 'hold',
        'count'  => true,
    ));

    return array(
        'today'             => count($today_appointments),
        'pending'           => $pending,
        'upcoming'          => $upcoming,
        'notifications'     => (int) $pending_reviews + $pending,
        'today_appointments'=> $today_appointments,
        'popular_services'  => $popular_services,
    );
}

function beautycore_admin_stat_card($label, $value, $modifier = '') {
    echo '<div class="beautycore-stat-card ' . esc_attr($modifier) . '">';
    echo '<span class="beautycore-stat-label">' . esc_html($label) . '</span>';
    echo '<strong class="beautycore-stat-value">' . esc_html($value) . '</strong>';
    echo '</div>';
}

function beautycore_format_appointment_time($timestamp, $fallback = '') {
    if (!$timestamp) {
        return $fallback ?: 'Chưa xác định';
    }

    return wp_date('H:i, d/m/Y', $timestamp, wp_timezone());
}

function beautycore_render_appointments_table($appointments, $limit = 10) {
    if (!$appointments) {
        echo '<div class="beautycore-empty-state"><strong>Chưa có lịch hẹn</strong><span>Dữ liệu lịch hẹn sẽ xuất hiện tại đây khi phân hệ đặt lịch được bật.</span></div>';
        return;
    }

    echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table">';
    echo '<thead><tr><th>Thời gian</th><th>Khách hàng</th><th>Dịch vụ</th><th>Trạng thái</th><th>Chi nhánh</th></tr></thead><tbody>';
    foreach (array_slice($appointments, 0, $limit) as $appointment) {
        echo '<tr>';
        echo '<td>' . esc_html(beautycore_format_appointment_time($appointment['timestamp'], $appointment['date'])) . '</td>';
        echo '<td>' . esc_html($appointment['customer']) . '</td>';
        echo '<td>' . esc_html($appointment['service']) . '</td>';
        echo '<td><span class="beautycore-status beautycore-status-' . esc_attr($appointment['status']) . '">' . esc_html($appointment['status'] ?: 'Chưa cập nhật') . '</span></td>';
        echo '<td>' . esc_html($appointment['branch'] ?: '—') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function beautycore_render_dashboard_contents($show_header = true) {
    $metrics = beautycore_dashboard_metrics();

    if ($show_header) {
        beautycore_admin_page_header('Tổng quan', 'Theo dõi lịch hẹn và các tín hiệu vận hành quan trọng của Beauty Core.');
    }

    echo '<div class="beautycore-stat-grid">';
    beautycore_admin_stat_card('Lịch hôm nay', $metrics['today'], 'beautycore-stat-primary');
    beautycore_admin_stat_card('Lịch pending', $metrics['pending']);
    beautycore_admin_stat_card('Khách sắp đến', $metrics['upcoming']);
    beautycore_admin_stat_card('Thông báo', $metrics['notifications']);
    echo '</div>';

    echo '<div class="beautycore-dashboard-grid">';
    echo '<section class="beautycore-panel"><div class="beautycore-panel-heading"><h2>Lịch hôm nay</h2><a href="' . esc_url(admin_url('admin.php?page=beautycore-appointments')) . '">Xem lịch hẹn</a></div>';
    beautycore_render_appointments_table($metrics['today_appointments']);
    echo '</section>';

    echo '<section class="beautycore-panel"><div class="beautycore-panel-heading"><h2>Dịch vụ phổ biến</h2><a href="' . esc_url(admin_url('admin.php?page=beautycore-services')) . '">Quản lý dịch vụ</a></div>';
    if ($metrics['popular_services']) {
        echo '<ol class="beautycore-ranking">';
        foreach (array_slice($metrics['popular_services'], 0, 5, true) as $service => $count) {
            echo '<li><span>' . esc_html($service) . '</span><strong>' . esc_html($count) . '</strong></li>';
        }
        echo '</ol>';
    } else {
        echo '<div class="beautycore-empty-state"><strong>Chưa có dữ liệu sử dụng</strong><span>Bảng xếp hạng sẽ được tính từ lịch hẹn đã ghi nhận.</span></div>';
    }
    echo '</section>';
    echo '</div>';

    echo '<section class="beautycore-panel beautycore-notice-panel"><h2>Thông báo vận hành</h2><ul class="beautycore-checklist">';
    echo '<li><span class="dashicons dashicons-yes-alt"></span>Đã bật các vai trò Chủ cơ sở, Quản lý, Lễ tân, Nhân viên và Biên tập viên.</li>';
    echo '<li><span class="dashicons dashicons-yes-alt"></span>Menu hệ thống, plugin, theme và cài đặt WordPress bị giới hạn với nhân viên.</li>';
    echo '<li><span class="dashicons dashicons-yes-alt"></span>Nhật ký thao tác được lưu cho các thay đổi nhạy cảm.</li>';
    echo '</ul></section>';

    if ($show_header) {
        beautycore_admin_page_footer();
    }
}

function beautycore_render_dashboard_page() {
    if (!current_user_can('view_beautycore_dashboard')) {
        wp_die('Bạn không có quyền xem dashboard.');
    }

    beautycore_render_dashboard_contents(true);
}

function beautycore_render_dashboard_widget() {
    if (!current_user_can('view_beautycore_dashboard')) {
        return;
    }

    beautycore_render_dashboard_contents(false);
    echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=beautycore-dashboard')) . '">Mở dashboard Beauty Core</a></p>';
}

function beautycore_register_dashboard_widget() {
    if (current_user_can('view_beautycore_dashboard')) {
        wp_add_dashboard_widget('beautycore_dashboard_overview', 'Beauty Core — Tổng quan', 'beautycore_render_dashboard_widget');
    }
}
add_action('wp_dashboard_setup', 'beautycore_register_dashboard_widget');

function beautycore_render_service_catalog($title = 'Dịch vụ và bảng giá') {
    if (!function_exists('beautycore_service_groups')) {
        echo '<div class="notice notice-warning"><p>Dữ liệu dịch vụ chưa sẵn sàng.</p></div>';
        return;
    }

    echo '<div class="beautycore-service-grid">';
    foreach (beautycore_service_groups() as $group) {
        echo '<section class="beautycore-panel"><h2>' . esc_html($group['title']) . '</h2>';
        if (!empty($group['description'])) {
            echo '<p>' . esc_html($group['description']) . '</p>';
        }
        echo '<table class="widefat striped beautycore-admin-table"><thead><tr><th>Dịch vụ</th><th>Thời lượng</th><th>Giá</th></tr></thead><tbody>';
        foreach ($group['services'] as $service) {
            echo '<tr><td>' . esc_html($service['name']) . '</td><td>' . esc_html($service['duration']) . '</td><td><strong>' . esc_html($service['price']) . '</strong></td></tr>';
        }
        echo '</tbody></table></section>';
    }
    echo '</div>';
}

function beautycore_render_reports_page() {
    $metrics = beautycore_dashboard_metrics();
    $blog_count = wp_count_posts('beautycore_blog');
    $page_count = wp_count_posts('page');
    $media_count = wp_count_attachments();

    echo '<div class="beautycore-stat-grid">';
    beautycore_admin_stat_card('Lịch hôm nay', $metrics['today']);
    beautycore_admin_stat_card('Lịch pending', $metrics['pending']);
    beautycore_admin_stat_card('Bài viết', $blog_count ? (int) $blog_count->publish : 0);
    beautycore_admin_stat_card('Media', $media_count ? (int) $media_count->total : 0);
    echo '</div>';
    echo '<div class="beautycore-panel"><h2>Tóm tắt dữ liệu</h2><p>Đây là lớp báo cáo nền. Các báo cáo doanh thu, tỷ lệ lấp đầy và hiệu suất nhân viên sẽ được nối vào dữ liệu lịch hẹn ở các giai đoạn tiếp theo.</p>';
    echo '<ul class="beautycore-report-list"><li>Trang đã xuất bản: <strong>' . esc_html($page_count ? $page_count->publish : 0) . '</strong></li><li>Khách sắp đến: <strong>' . esc_html($metrics['upcoming']) . '</strong></li></ul></div>';
}

function beautycore_render_settings_page() {
    $config = beautycore_site_config();

    if (!empty($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Cấu hình lịch hẹn đã được lưu.</p></div>';
    } elseif (!empty($_GET['error'])) {
        echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash($_GET['error'])) . '</p></div>';
    }

    echo '<div class="beautycore-settings-grid"><section class="beautycore-panel"><h2>Thông tin cơ sở</h2><dl class="beautycore-definition-list">';
    foreach (array('name' => 'Tên cơ sở', 'phone_display' => 'Điện thoại', 'email' => 'Email', 'address' => 'Địa chỉ', 'opening_hours' => 'Giờ mở cửa') as $key => $label) {
        echo '<dt>' . esc_html($label) . '</dt><dd>' . esc_html(isset($config[$key]) ? $config[$key] : '') . '</dd>';
    }
    echo '</dl><p class="description">Thông tin trên hiện được quản lý từ cấu hình theme. Màn hình chỉnh sửa tập trung sẽ được bổ sung ở giai đoạn cấu hình.</p></section>';
    echo '<section class="beautycore-panel"><h2>Bảo vệ quản trị</h2><ul class="beautycore-checklist">';
    echo '<li><span class="dashicons dashicons-yes-alt"></span>Mật khẩu mới được yêu cầu tối thiểu 12 ký tự, gồm chữ hoa, chữ thường, số và ký tự đặc biệt.</li>';
    echo '<li><span class="dashicons dashicons-yes-alt"></span>Đăng nhập sai nhiều lần sẽ bị khóa tạm thời theo tài khoản và địa chỉ IP.</li>';
    echo '<li><span class="dashicons dashicons-info-outline"></span>Xác thực hai bước cần được bật qua plugin 2FA tương thích WordPress.</li>';
    echo '<li><span class="dashicons dashicons-lock"></span>Chỉ Quản trị viên được truy cập plugin, giao diện và Cài đặt hệ thống.</li>';
    echo '</ul></section></div>';
    if (function_exists('beautycore_render_appointment_settings_panel')) {
        beautycore_render_appointment_settings_panel();
    }
}

function beautycore_render_audit_page() {
    $logs = beautycore_get_audit_logs(100);

    if (!$logs) {
        echo '<div class="beautycore-empty-state"><strong>Chưa có nhật ký</strong><span>Các thao tác sửa giá, hủy lịch, duyệt đánh giá và thay đổi nội dung sẽ được ghi tại đây.</span></div>';
        return;
    }

    echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table"><thead><tr><th>Thời gian</th><th>Người thao tác</th><th>Thao tác</th><th>Đối tượng</th><th>Chi tiết</th></tr></thead><tbody>';
    foreach ($logs as $log) {
        $context = !empty($log['context']) && is_array($log['context']) ? wp_json_encode($log['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        echo '<tr><td>' . esc_html($log['timestamp']) . '</td><td>' . esc_html($log['user_login'] ?: 'Hệ thống') . '</td><td>' . esc_html($log['action']) . '</td><td>' . esc_html($log['object_type'] . ($log['object_id'] ? ' #' . $log['object_id'] : '')) . '</td><td>' . esc_html($context) . '</td></tr>';
    }
    echo '</tbody></table></div>';
}

function beautycore_render_module_page() {
    $slug = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $modules = array(
        'beautycore-appointments' => array('title' => 'Lịch hẹn', 'description' => 'Danh sách, calendar, điều phối và nhắc lịch hẹn.', 'capability' => 'view_beautycore_schedule'),
        'beautycore-services'     => array('title' => 'Dịch vụ', 'description' => 'Danh mục dịch vụ hiện đang được website sử dụng.', 'capability' => 'view_beautycore_services'),
        'beautycore-reports'      => array('title' => 'Báo cáo', 'description' => 'Tổng hợp nhanh các chỉ số quản trị.', 'capability' => 'view_beautycore_reports'),
        'beautycore-settings'     => array('title' => 'Cấu hình', 'description' => 'Thông tin cơ sở và trạng thái bảo vệ dashboard.', 'capability' => 'view_beautycore_settings'),
        'beautycore-audit'        => array('title' => 'Nhật ký thao tác', 'description' => 'Theo dõi người dùng và thời gian thực hiện các thao tác nhạy cảm.', 'capability' => 'view_beautycore_audit'),
        'beautycore-staff'        => array('title' => 'Nhân viên', 'description' => 'Quản lý nhân sự, vai trò và lịch làm việc.', 'capability' => 'manage_beautycore_staff'),
        'beautycore-branches'     => array('title' => 'Chi nhánh', 'description' => 'Quản lý địa điểm, giờ mở cửa và thông tin liên hệ.', 'capability' => 'view_beautycore_branches'),
        'beautycore-customers'    => array('title' => 'Khách hàng', 'description' => 'Quản lý hồ sơ và lịch sử phục vụ khách hàng.', 'capability' => 'manage_beautycore_customers'),
        'beautycore-promotions'   => array('title' => 'Khuyến mãi', 'description' => 'Quản lý voucher và chương trình ưu đãi.', 'capability' => 'manage_beautycore_promotions'),
        'beautycore-reviews'      => array('title' => 'Đánh giá', 'description' => 'Quản lý đánh giá và trạng thái duyệt nội dung.', 'capability' => 'manage_beautycore_reviews'),
    );

    if (!isset($modules[$slug]) || !current_user_can($modules[$slug]['capability'])) {
        wp_die('Bạn không có quyền truy cập khu vực này.');
    }

    $module = $modules[$slug];
    beautycore_admin_page_header($module['title'], $module['description']);

    if ($slug === 'beautycore-appointments') {
        if (function_exists('beautycore_render_appointment_admin_page')) {
            beautycore_render_appointment_admin_page();
        } else {
            beautycore_render_appointments_table(beautycore_dashboard_appointments(), 100);
        }
    } elseif ($slug === 'beautycore-services') {
        beautycore_render_services_page();
    } elseif ($slug === 'beautycore-reports') {
        beautycore_render_reports_page();
    } elseif ($slug === 'beautycore-settings') {
        beautycore_render_settings_page();
    } elseif ($slug === 'beautycore-audit') {
        beautycore_render_audit_page();
    } else {
        echo '<section class="beautycore-panel beautycore-module-placeholder"><span class="dashicons dashicons-admin-tools"></span><h2>Phân hệ đã được tạo</h2><p>Menu và quyền truy cập đã sẵn sàng. Màn hình quản lý dữ liệu chi tiết sẽ được triển khai trong giai đoạn tương ứng.</p></section>';
    }

    beautycore_admin_page_footer();
}

/**
 * Store a bounded audit trail in a non-autoloaded option.
 */
function beautycore_sanitize_audit_context($value) {
    if (is_array($value)) {
        $clean = array();
        foreach ($value as $key => $item) {
            $clean[sanitize_key($key)] = beautycore_sanitize_audit_context($item);
        }
        return $clean;
    }

    if (is_scalar($value)) {
        return sanitize_text_field((string) $value);
    }

    return '';
}

function beautycore_client_ip() {
    return isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
}

function beautycore_audit_log($action, $context = array(), $object_type = '', $object_id = 0) {
    if (!apply_filters('beautycore_audit_log_enabled', true)) {
        return;
    }

    $user = wp_get_current_user();
    $logs = get_option('beautycore_audit_log', array());
    $logs = is_array($logs) ? $logs : array();
    array_unshift($logs, array(
        'timestamp'   => current_time('mysql'),
        'timestamp_gmt'=> current_time('mysql', true),
        'user_id'     => $user ? (int) $user->ID : 0,
        'user_login'  => $user && $user->exists() ? $user->user_login : '',
        'ip'          => beautycore_client_ip(),
        'action'      => sanitize_key($action),
        'object_type' => sanitize_key($object_type),
        'object_id'   => absint($object_id),
        'context'     => beautycore_sanitize_audit_context($context),
    ));
    update_option('beautycore_audit_log', array_slice($logs, 0, 500), false);
}

function beautycore_get_audit_logs($limit = 100) {
    $logs = get_option('beautycore_audit_log', array());

    return array_slice(is_array($logs) ? $logs : array(), 0, absint($limit));
}

function beautycore_audit_sensitive_post_type($post_type) {
    return in_array($post_type, array(
        'beautycore_blog',
        'beautycore_service',
        'beautycore_appointment',
        'beautycore_booking',
        'beautycore_branch',
        'beautycore_customer',
        'beautycore_review',
        'beautycore_voucher',
    ), true);
}

function beautycore_audit_post_save($post_id, $post, $update) {
    if (!$post || wp_is_post_revision($post_id) || (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || !beautycore_audit_sensitive_post_type($post->post_type)) {
        return;
    }

    beautycore_audit_log($update ? 'post_updated' : 'post_created', array('status' => $post->post_status, 'title' => $post->post_title), $post->post_type, $post_id);
}
add_action('save_post', 'beautycore_audit_post_save', 20, 3);

function beautycore_audit_price_change($meta_id, $object_id, $meta_key, $meta_value) {
    $post = get_post($object_id);
    if (!$post || !beautycore_audit_sensitive_post_type($post->post_type) || !preg_match('/(^|_)(price|gia|amount)(_|$)/i', $meta_key)) {
        return;
    }

    beautycore_audit_log('price_updated', array('meta_key' => $meta_key, 'value' => $meta_value), $post->post_type, $object_id);
}
add_action('updated_post_meta', 'beautycore_audit_price_change', 10, 4);
add_action('added_post_meta', 'beautycore_audit_price_change', 10, 4);

function beautycore_audit_appointment_status($meta_id, $object_id, $meta_key, $meta_value) {
    $post = get_post($object_id);
    $status = is_scalar($meta_value) ? sanitize_key((string) $meta_value) : '';
    if (!$post || !in_array($post->post_type, array('beautycore_appointment', 'beautycore_booking'), true) || !preg_match('/status/i', $meta_key) || !in_array($status, array('cancelled', 'canceled'), true)) {
        return;
    }

    beautycore_audit_log('appointment_cancelled', array('meta_key' => $meta_key), $post->post_type, $object_id);
}
add_action('updated_post_meta', 'beautycore_audit_appointment_status', 10, 4);

function beautycore_audit_appointment_transition($new_status, $old_status, $post) {
    if ($post && in_array($post->post_type, array('beautycore_appointment', 'beautycore_booking'), true) && $new_status !== $old_status && in_array($new_status, array('cancelled', 'canceled'), true)) {
        beautycore_audit_log('appointment_cancelled', array('from' => $old_status, 'to' => $new_status), $post->post_type, $post->ID);
    }
}
add_action('transition_post_status', 'beautycore_audit_appointment_transition', 10, 3);

function beautycore_audit_comment_approval($new_status, $old_status, $comment) {
    if ($comment && $new_status === 'approved' && $old_status !== 'approved') {
        beautycore_audit_log('review_approved', array('comment_author' => $comment->comment_author), 'comment', $comment->comment_ID);
    }
}
add_action('transition_comment_status', 'beautycore_audit_comment_approval', 10, 3);

function beautycore_audit_manual_appointment_cancelled($appointment_id) {
    beautycore_audit_log('appointment_cancelled', array('source' => 'module_hook'), 'appointment', $appointment_id);
}
add_action('beautycore_appointment_cancelled', 'beautycore_audit_manual_appointment_cancelled');

function beautycore_audit_manual_review_approved($review_id) {
    beautycore_audit_log('review_approved', array('source' => 'module_hook'), 'review', $review_id);
}
add_action('beautycore_review_approved', 'beautycore_audit_manual_review_approved');

function beautycore_audit_user_register($user_id) {
    $user = get_userdata($user_id);
    beautycore_audit_log('user_registered', array('role' => $user ? implode(',', (array) $user->roles) : ''), 'user', $user_id);
}
add_action('user_register', 'beautycore_audit_user_register');

function beautycore_audit_login($user_login, $user) {
    beautycore_audit_log('login_success', array(), 'user', $user ? $user->ID : 0);
}
add_action('wp_login', 'beautycore_audit_login', 10, 2);

/**
 * Login throttling by username and IP. The lock is temporary and resets after
 * a successful login, so an accidental typo cannot permanently lock a user.
 */
function beautycore_login_lock_key($username, $ip = '') {
    $ip = $ip ?: beautycore_client_ip();
    return 'beautycore_login_' . hash_hmac('sha256', strtolower(trim($username)) . '|' . $ip, wp_salt('auth'));
}

function beautycore_login_lock_settings() {
    return apply_filters('beautycore_login_lock_settings', array(
        'max_attempts' => 5,
        'duration'     => 15 * MINUTE_IN_SECONDS,
    ));
}

function beautycore_block_locked_login($user, $username, $password) {
    if (!$username) {
        return $user;
    }

    $lock = get_transient(beautycore_login_lock_key($username));
    if (is_array($lock) && !empty($lock['locked_until']) && $lock['locked_until'] > time()) {
        return new WP_Error('beautycore_login_locked', 'Tài khoản đang tạm khóa do đăng nhập sai nhiều lần. Vui lòng thử lại sau ít phút.');
    }

    return $user;
}
add_filter('authenticate', 'beautycore_block_locked_login', 30, 3);

function beautycore_record_failed_login($username) {
    if (!$username) {
        return;
    }

    $settings = beautycore_login_lock_settings();
    $key = beautycore_login_lock_key($username);
    $state = get_transient($key);
    $attempts = is_array($state) && isset($state['attempts']) ? (int) $state['attempts'] : 0;
    $attempts++;
    $locked_until = $attempts >= (int) $settings['max_attempts'] ? time() + (int) $settings['duration'] : 0;
    set_transient($key, array('attempts' => $attempts, 'locked_until' => $locked_until), (int) $settings['duration']);

    beautycore_audit_log('login_failed', array('username' => sanitize_user($username), 'attempts' => $attempts), 'login', 0);
}
add_action('wp_login_failed', 'beautycore_record_failed_login');

function beautycore_clear_login_lock($user_login, $user) {
    delete_transient(beautycore_login_lock_key($user_login));
}
add_action('wp_login', 'beautycore_clear_login_lock', 20, 2);

function beautycore_validate_password_strength($password) {
    $errors = array();
    if (strlen($password) < 12) {
        $errors[] = 'ít nhất 12 ký tự';
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'một chữ hoa';
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'một chữ thường';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'một chữ số';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'một ký tự đặc biệt';
    }

    return $errors;
}

function beautycore_validate_profile_password($errors, $update, $user) {
    $password = isset($_POST['pass1']) ? (string) wp_unslash($_POST['pass1']) : '';
    if (!$password && $update) {
        return;
    }

    $password_errors = beautycore_validate_password_strength($password);
    if ($password_errors) {
        $errors->add('beautycore_weak_password', 'Mật khẩu phải có ' . implode(', ', $password_errors) . '.');
    }
}
add_action('user_profile_update_errors', 'beautycore_validate_profile_password', 10, 3);

function beautycore_validate_registration_password($errors, $sanitized_user_login, $user_email) {
    $password = isset($_POST['pass1']) ? (string) wp_unslash($_POST['pass1']) : (isset($_POST['user_pass']) ? (string) wp_unslash($_POST['user_pass']) : '');
    $password_errors = beautycore_validate_password_strength($password);
    if ($password_errors) {
        $errors->add('beautycore_weak_password', 'Mật khẩu phải có ' . implode(', ', $password_errors) . '.');
    }

    return $errors;
}
add_filter('registration_errors', 'beautycore_validate_registration_password', 10, 3);

function beautycore_validate_reset_password($errors, $user) {
    $password = isset($_POST['pass1']) ? (string) wp_unslash($_POST['pass1']) : '';
    if ($password) {
        $password_errors = beautycore_validate_password_strength($password);
        if ($password_errors) {
            $errors->add('beautycore_weak_password', 'Mật khẩu phải có ' . implode(', ', $password_errors) . '.');
        }
    }

    return $errors;
}
add_filter('validate_password_reset', 'beautycore_validate_reset_password', 10, 2);
