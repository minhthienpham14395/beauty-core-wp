<?php
/**
 * Staff and branch management screens for phase 4.
 */

if (!defined('ABSPATH')) {
    exit;
}

function beautycore_stage4_admin_menu() {
    add_submenu_page(
        null,
        'Thêm / sửa nhân viên',
        'Thêm / sửa nhân viên',
        'manage_beautycore_staff',
        'beautycore-staff-edit',
        'beautycore_render_staff_edit_page'
    );
    add_submenu_page(
        null,
        'Thêm / sửa chi nhánh',
        'Thêm / sửa chi nhánh',
        'manage_beautycore_branches',
        'beautycore-branch-edit',
        'beautycore_render_branch_edit_page'
    );
}
add_action('admin_menu', 'beautycore_stage4_admin_menu', 10);

function beautycore_stage4_admin_assets() {
    if (empty($_GET['page'])) {
        return;
    }

    $page = sanitize_key(wp_unslash($_GET['page']));
    if (!in_array($page, array('beautycore-staff', 'beautycore-staff-edit', 'beautycore-branches', 'beautycore-branch-edit'), true)) {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'beautycore-staff-branch-admin',
        get_theme_file_uri('/assets/js/admin-staff-branch.js'),
        array('jquery'),
        BEAUTYCORE_ADMIN_VERSION,
        true
    );
    wp_localize_script('beautycore-staff-branch-admin', 'BEAUTYCORE_STAGE4_ADMIN', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('beautycore_stage4_modal'),
    ));
}
add_action('admin_enqueue_scripts', 'beautycore_stage4_admin_assets');

function beautycore_stage4_admin_redirect($page, $args = array()) {
    wp_safe_redirect(add_query_arg(array_merge(array('page' => $page), $args), admin_url('admin.php')));
    exit;
}

function beautycore_stage4_admin_notice($entity) {
    $label = $entity === 'staff' ? 'Nhân viên' : 'Chi nhánh';
    if (!empty($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($label) . ' đã được lưu thành công.</p></div>';
    }
    if (!empty($_GET['deleted'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($label) . ' đã được xóa.</p></div>';
    }
    if (!empty($_GET['error'])) {
        echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash($_GET['error'])) . '</p></div>';
    }
}

function beautycore_stage4_branch_name($branch_id) {
    $branch = get_post(absint($branch_id));
    return $branch && $branch->post_type === BEAUTYCORE_BRANCH_POST_TYPE ? $branch->post_title : '';
}

function beautycore_stage4_service_name($service_id) {
    $service = get_post(absint($service_id));
    return $service && $service->post_type === 'beautycore_service' ? $service->post_title : '';
}

function beautycore_stage4_join_names($ids, $callback, $empty = 'Chưa gán') {
    $names = array();
    foreach ((array) $ids as $id) {
        $name = call_user_func($callback, $id);
        if ($name) {
            $names[] = $name;
        }
    }
    return $names ? implode(', ', $names) : $empty;
}

function beautycore_stage4_current_filters($entity) {
    return array(
        's'         => isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '',
        'status'    => isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all',
        'branch_id' => $entity === 'staff' && isset($_GET['branch_id']) ? absint($_GET['branch_id']) : 0,
    );
}

function beautycore_stage4_render_filters($entity) {
    $filters = beautycore_stage4_current_filters($entity);
    $page = $entity === 'staff' ? 'beautycore-staff' : 'beautycore-branches';
    $statuses = $entity === 'staff' ? beautycore_staff_statuses() : beautycore_branch_statuses();
    $placeholder = $entity === 'staff' ? 'Tìm tên, email, số điện thoại...' : 'Tìm tên, địa chỉ, số điện thoại...';

    echo '<form class="beautycore-service-filters beautycore-stage4-filters" method="get" data-beautycore-stage4-filter>';
    echo '<input type="hidden" name="page" value="' . esc_attr($page) . '">';
    echo '<label class="screen-reader-text" for="beautycore-stage4-search">Tìm kiếm</label>';
    echo '<input id="beautycore-stage4-search" type="search" name="s" value="' . esc_attr($filters['s']) . '" placeholder="' . esc_attr($placeholder) . '">';
    echo '<select name="status"><option value="all">Tất cả trạng thái</option>';
    foreach ($statuses as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($filters['status'], $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select>';
    if ($entity === 'staff') {
        echo '<select name="branch_id"><option value="0">Tất cả chi nhánh</option>';
        foreach (beautycore_stage4_branch_posts(true) as $branch) {
            echo '<option value="' . esc_attr($branch->ID) . '" ' . selected($filters['branch_id'], $branch->ID, false) . '>' . esc_html($branch->post_title) . '</option>';
        }
        echo '</select>';
    }
    echo '<noscript><button type="submit" class="button button-secondary">Lọc</button></noscript>';
    if ($filters['s'] || $filters['status'] !== 'all' || $filters['branch_id']) {
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=' . $page)) . '">Xóa bộ lọc</a>';
    }
    echo '</form>';
}

function beautycore_stage4_filtered_staff() {
    $filters = beautycore_stage4_current_filters('staff');
    return array_values(array_filter(beautycore_stage4_staff_users(true), function ($user) use ($filters) {
        $staff = beautycore_staff_data($user);
        if ($filters['status'] !== 'all' && $staff['status'] !== $filters['status']) {
            return false;
        }
        if ($filters['branch_id'] && !in_array($filters['branch_id'], $staff['branch_ids'], true)) {
            return false;
        }
        if ($filters['s']) {
            $haystack = strtolower(remove_accents(implode(' ', array($staff['name'], $staff['email'], $staff['phone'], $staff['title'], $staff['specialty']))));
            $needle = strtolower(remove_accents($filters['s']));
            if (strpos($haystack, $needle) === false) {
                return false;
            }
        }
        return true;
    }));
}

function beautycore_stage4_filtered_branches() {
    $filters = beautycore_stage4_current_filters('branch');
    return array_values(array_filter(beautycore_stage4_branch_posts(true), function ($branch) use ($filters) {
        $data = beautycore_branch_data($branch);
        if ($filters['status'] !== 'all' && $data['status'] !== $filters['status']) {
            return false;
        }
        if ($filters['s']) {
            $haystack = strtolower(remove_accents(implode(' ', array($data['name'], $data['address'], $data['phone'], $data['email']))));
            $needle = strtolower(remove_accents($filters['s']));
            if (strpos($haystack, $needle) === false) {
                return false;
            }
        }
        return true;
    }));
}

function beautycore_render_staff_admin_page() {
    if (!current_user_can('manage_beautycore_staff')) {
        wp_die('Bạn không có quyền quản lý nhân viên.');
    }

    beautycore_stage4_admin_notice('staff');
    echo '<div class="beautycore-service-toolbar"><div><a class="button button-primary beautycore-stage4-open" data-entity="staff" data-object-id="0" href="' . esc_url(admin_url('admin.php?page=beautycore-staff-edit')) . '">Thêm nhân viên</a></div><span class="description">Quản lý hồ sơ, kỹ năng, chi nhánh và lịch làm việc của nhân viên.</span></div>';
    beautycore_stage4_render_filters('staff');

    $staff_users = beautycore_stage4_filtered_staff();
    if (!$staff_users) {
        echo '<div class="beautycore-empty-state"><strong>Chưa có nhân viên</strong><span>Hãy thêm nhân viên đầu tiên hoặc kiểm tra lại bộ lọc.</span></div>';
    } else {
        echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table beautycore-stage4-table"><thead><tr><th>Nhân viên</th><th>Chức danh / chuyên môn</th><th>Chi nhánh</th><th>Dịch vụ</th><th>Trạng thái</th><th></th></tr></thead><tbody>';
        foreach ($staff_users as $user) {
            $staff = beautycore_staff_data($user);
            $edit_url = admin_url('admin.php?page=beautycore-staff-edit&id=' . $user->ID);
            $toggle_label = $staff['status'] === 'active' ? 'Tạm nghỉ' : 'Kích hoạt';
            $avatar = $staff['image_id'] ? wp_get_attachment_image($staff['image_id'], array(48, 48)) : get_avatar($user->ID, 48);
            echo '<tr><td><div class="beautycore-person-cell">' . wp_kses_post($avatar) . '<div><strong><a class="beautycore-stage4-open" data-entity="staff" data-object-id="' . esc_attr($user->ID) . '" href="' . esc_url($edit_url) . '">' . esc_html($staff['name']) . '</a></strong><small>' . esc_html($staff['phone'] ?: $staff['email']) . '</small><div class="row-actions"><span><a class="beautycore-stage4-open" data-entity="staff" data-object-id="' . esc_attr($user->ID) . '" href="' . esc_url($edit_url) . '">Sửa</a></span> | <span><a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=beautycore_toggle_staff&id=' . $user->ID), 'beautycore_toggle_staff_' . $user->ID)) . '">' . esc_html($toggle_label) . '</a></span>';
            if ($user->ID !== get_current_user_id() && in_array('staff', (array) $user->roles, true)) {
                echo ' | <span class="trash"><a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=beautycore_delete_staff&id=' . $user->ID), 'beautycore_delete_staff_' . $user->ID)) . '" onclick="return confirm(\'Xóa nhân viên này?\');">Xóa</a></span>';
            }
            echo '</div></div></div></td>';
            echo '<td><strong>' . esc_html($staff['title'] ?: 'Nhân viên') . '</strong><small>' . esc_html($staff['specialty'] ?: 'Chưa cập nhật chuyên môn') . '</small></td>';
            echo '<td>' . esc_html(beautycore_stage4_join_names($staff['branch_ids'], 'beautycore_stage4_branch_name')) . '</td>';
            echo '<td>' . esc_html(count($staff['service_ids']) ? count($staff['service_ids']) . ' dịch vụ' : 'Chưa gán') . '</td>';
            echo '<td><span class="beautycore-status beautycore-status-' . esc_attr($staff['status']) . '">' . esc_html(beautycore_staff_statuses()[$staff['status']]) . '</span>' . (!$staff['visible'] ? '<small>Ẩn trên website</small>' : '') . '</td>';
            echo '<td><a class="button button-small beautycore-stage4-open" data-entity="staff" data-object-id="' . esc_attr($user->ID) . '" href="' . esc_url($edit_url) . '">Mở</a></td></tr>';
        }
        echo '</tbody></table></div>';
    }

    beautycore_stage4_render_modal();
}

function beautycore_render_branches_admin_page() {
    if (!current_user_can('view_beautycore_branches')) {
        wp_die('Bạn không có quyền xem chi nhánh.');
    }

    beautycore_stage4_admin_notice('branch');
    echo '<div class="beautycore-service-toolbar"><div>';
    if (current_user_can('manage_beautycore_branches')) {
        echo '<a class="button button-primary beautycore-stage4-open" data-entity="branch" data-object-id="0" href="' . esc_url(admin_url('admin.php?page=beautycore-branch-edit')) . '">Thêm chi nhánh</a>';
    }
    echo '</div><span class="description">Quản lý địa điểm, giờ mở cửa, dịch vụ và nhân viên tại từng chi nhánh.</span></div>';
    beautycore_stage4_render_filters('branch');

    $branches = beautycore_stage4_filtered_branches();
    if (!$branches) {
        echo '<div class="beautycore-empty-state"><strong>Chưa có chi nhánh</strong><span>Hãy thêm chi nhánh đầu tiên hoặc kiểm tra lại bộ lọc.</span></div>';
    } else {
        echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table beautycore-stage4-table"><thead><tr><th>Chi nhánh</th><th>Liên hệ</th><th>Giờ hoạt động</th><th>Dịch vụ</th><th>Nhân viên</th><th>Trạng thái</th><th></th></tr></thead><tbody>';
        foreach ($branches as $branch) {
            $data = beautycore_branch_data($branch);
            $edit_url = admin_url('admin.php?page=beautycore-branch-edit&id=' . $branch->ID);
            $toggle_label = $data['status'] === 'publish' ? 'Tạm đóng' : 'Mở lại';
            $today = $data['schedule'][(string) current_time('w')];
            $hours = !empty($today['enabled']) ? $today['start'] . '–' . $today['end'] : 'Hôm nay đóng cửa';
            echo '<tr><td><strong><a class="beautycore-stage4-open" data-entity="branch" data-object-id="' . esc_attr($branch->ID) . '" href="' . esc_url($edit_url) . '">' . esc_html($data['name']) . '</a></strong><small>' . esc_html($data['address'] ?: 'Chưa cập nhật địa chỉ') . '</small><div class="row-actions">';
            if (current_user_can('manage_beautycore_branches')) {
                echo '<span><a class="beautycore-stage4-open" data-entity="branch" data-object-id="' . esc_attr($branch->ID) . '" href="' . esc_url($edit_url) . '">Sửa</a></span> | <span><a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=beautycore_toggle_branch&id=' . $branch->ID), 'beautycore_toggle_branch_' . $branch->ID)) . '">' . esc_html($toggle_label) . '</a></span> | <span class="trash"><a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=beautycore_delete_branch&id=' . $branch->ID), 'beautycore_delete_branch_' . $branch->ID)) . '" onclick="return confirm(\'Xóa chi nhánh này?\');">Xóa</a></span>';
            }
            echo '</div></td><td>' . esc_html($data['phone'] ?: '—') . '<small>' . esc_html($data['email']) . '</small></td><td>' . esc_html($hours) . '</td><td>' . esc_html(count($data['service_ids']) ? count($data['service_ids']) . ' dịch vụ' : 'Chưa gán') . '</td><td>' . esc_html(count($data['staff_ids']) ? count($data['staff_ids']) . ' nhân viên' : 'Chưa gán') . '</td>';
            echo '<td><span class="beautycore-status beautycore-status-' . esc_attr($data['status']) . '">' . esc_html(beautycore_branch_statuses()[$data['status']]) . '</span></td><td>';
            if (current_user_can('manage_beautycore_branches')) {
                echo '<a class="button button-small beautycore-stage4-open" data-entity="branch" data-object-id="' . esc_attr($branch->ID) . '" href="' . esc_url($edit_url) . '">Mở</a>';
            }
            echo '</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    if (current_user_can('manage_beautycore_branches')) {
        beautycore_stage4_render_modal();
    }
}

function beautycore_stage4_render_modal() {
    echo '<div id="beautycore-stage4-modal" class="beautycore-service-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="beautycore-stage4-modal-title"><div class="beautycore-service-modal__backdrop" data-beautycore-stage4-close></div><div class="beautycore-service-modal__dialog"><div class="beautycore-service-modal__header"><h2 id="beautycore-stage4-modal-title">Thêm mới</h2><button type="button" class="button-link beautycore-service-modal__close" data-beautycore-stage4-close aria-label="Đóng">&times;</button></div><div id="beautycore-stage4-modal-body" class="beautycore-service-modal__body"><p class="beautycore-modal-loading">Đang chuẩn bị biểu mẫu...</p></div></div></div>';
}

function beautycore_stage4_render_field($label, $name, $value, $type = 'text', $description = '', $required = false) {
    $id = 'beautycore-' . str_replace('_', '-', $name);
    echo '<div class="beautycore-form-field"><label for="' . esc_attr($id) . '"><strong>' . esc_html($label) . ($required ? ' *' : '') . '</strong></label>';
    if ($type === 'textarea') {
        echo '<textarea id="' . esc_attr($id) . '" name="' . esc_attr($name) . '" rows="5"' . ($required ? ' required' : '') . '>' . esc_textarea($value) . '</textarea>';
    } else {
        echo '<input id="' . esc_attr($id) . '" type="' . esc_attr($type) . '" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '"' . ($required ? ' required' : '') . '>';
    }
    if ($description) {
        echo '<p class="description">' . esc_html($description) . '</p>';
    }
    echo '</div>';
}

function beautycore_stage4_render_schedule($schedule) {
    $schedule = beautycore_stage4_normalize_schedule($schedule);
    echo '<div class="beautycore-schedule-table-wrap"><table class="widefat beautycore-schedule-table"><thead><tr><th>Ngày</th><th>Làm việc</th><th>Bắt đầu</th><th>Kết thúc</th><th>Nghỉ từ</th><th>Nghỉ đến</th></tr></thead><tbody>';
    foreach (beautycore_stage4_weekdays() as $day => $label) {
        $row = $schedule[$day];
        echo '<tr><th>' . esc_html($label) . '</th><td><label><input type="checkbox" name="schedule[' . esc_attr($day) . '][enabled]" value="1" ' . checked(!empty($row['enabled']), true, false) . '> Mở</label></td>';
        foreach (array('start', 'end', 'break_start', 'break_end') as $field) {
            echo '<td><input type="time" name="schedule[' . esc_attr($day) . '][' . esc_attr($field) . ']" value="' . esc_attr($row[$field]) . '"></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function beautycore_stage4_special_row($row, $index) {
    $row = wp_parse_args((array) $row, array(
        'date' => '', 'status' => 'open', 'start' => '09:00', 'end' => '21:00', 'break_start' => '', 'break_end' => '',
    ));
    echo '<tr data-beautycore-special-row><td><input type="date" name="special_schedule[' . esc_attr($index) . '][date]" value="' . esc_attr($row['date']) . '"></td><td><select name="special_schedule[' . esc_attr($index) . '][status]"><option value="open" ' . selected($row['status'], 'open', false) . '>Mở cửa</option><option value="closed" ' . selected($row['status'], 'closed', false) . '>Nghỉ</option></select></td><td><input type="time" name="special_schedule[' . esc_attr($index) . '][start]" value="' . esc_attr($row['start']) . '"></td><td><input type="time" name="special_schedule[' . esc_attr($index) . '][end]" value="' . esc_attr($row['end']) . '"></td><td><input type="time" name="special_schedule[' . esc_attr($index) . '][break_start]" value="' . esc_attr($row['break_start']) . '"></td><td><input type="time" name="special_schedule[' . esc_attr($index) . '][break_end]" value="' . esc_attr($row['break_end']) . '"></td><td><button type="button" class="button-link-delete" data-beautycore-remove-special>Xóa</button></td></tr>';
}

function beautycore_stage4_render_special_schedule($items) {
    $items = (array) $items;
    echo '<div class="beautycore-special-schedule"><div class="beautycore-panel-heading"><div><h3>Lịch đặc biệt</h3><p class="description">Ghi đè lịch tuần cho ngày lễ, ngày nghỉ hoặc ngày làm việc đặc biệt.</p></div><button type="button" class="button" data-beautycore-add-special>Thêm ngày</button></div><div class="beautycore-schedule-table-wrap"><table class="widefat beautycore-schedule-table"><thead><tr><th>Ngày</th><th>Trạng thái</th><th>Bắt đầu</th><th>Kết thúc</th><th>Nghỉ từ</th><th>Nghỉ đến</th><th></th></tr></thead><tbody data-beautycore-special-rows data-next-index="' . esc_attr(count($items)) . '">';
    foreach ($items as $index => $row) {
        beautycore_stage4_special_row($row, $index);
    }
    echo '</tbody></table></div><template data-beautycore-special-template>';
    beautycore_stage4_special_row(array(), '__INDEX__');
    echo '</template></div>';
}

function beautycore_stage4_render_assignments($selected_branches, $selected_services, $selected_staff = null) {
    echo '<div class="beautycore-assignment-grid">';
    if (is_array($selected_branches)) {
        echo '<div><h3>Chi nhánh</h3>';
        $branches = beautycore_stage4_branch_posts(true);
        if ($branches) {
            foreach ($branches as $branch) {
                echo '<label class="beautycore-check-option"><input type="checkbox" name="branch_ids[]" value="' . esc_attr($branch->ID) . '" ' . checked(in_array($branch->ID, $selected_branches, true), true, false) . '> ' . esc_html($branch->post_title) . '</label>';
            }
        } else {
            echo '<p class="description">Chưa có chi nhánh.</p>';
        }
        echo '</div>';
    }
    echo '<div><h3>Dịch vụ / kỹ năng</h3>';
    $services = get_posts(array(
        'post_type' => 'beautycore_service', 'post_status' => array_keys(beautycore_service_statuses()), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC',
    ));
    if ($services) {
        foreach ($services as $service) {
            echo '<label class="beautycore-check-option"><input type="checkbox" name="service_ids[]" value="' . esc_attr($service->ID) . '" ' . checked(in_array($service->ID, (array) $selected_services, true), true, false) . '> ' . esc_html($service->post_title) . '</label>';
        }
    } else {
        echo '<p class="description">Chưa có dịch vụ.</p>';
    }
    echo '</div>';
    if (is_array($selected_staff)) {
        echo '<div><h3>Nhân viên</h3>';
        $staff_users = beautycore_stage4_staff_users(true);
        if ($staff_users) {
            foreach ($staff_users as $user) {
                echo '<label class="beautycore-check-option"><input type="checkbox" name="staff_ids[]" value="' . esc_attr($user->ID) . '" ' . checked(in_array($user->ID, $selected_staff, true), true, false) . '> ' . esc_html($user->display_name ?: $user->user_login) . '</label>';
            }
        } else {
            echo '<p class="description">Chưa có nhân viên.</p>';
        }
        echo '</div>';
    }
    echo '</div>';
}

function beautycore_render_staff_edit_page($staff_id_override = null, $fragment = false) {
    if (!current_user_can('manage_beautycore_staff')) {
        wp_die('Bạn không có quyền chỉnh sửa nhân viên.');
    }

    $staff_id = $staff_id_override === null ? (isset($_GET['id']) ? absint($_GET['id']) : 0) : absint($staff_id_override);
    $user = $staff_id ? get_userdata($staff_id) : null;
    if ($staff_id && !$user) {
        wp_die('Không tìm thấy nhân viên.');
    }
    $staff = $user ? beautycore_staff_data($user) : array(
        'name' => '', 'email' => '', 'phone' => '', 'title' => '', 'specialty' => '', 'bio' => '', 'image_id' => 0,
        'status' => 'active', 'visible' => true, 'branch_ids' => array(), 'service_ids' => array(),
        'schedule' => beautycore_stage4_default_schedule(), 'days_off' => array(), 'special_schedule' => array(),
    );

    if (!$fragment) {
        beautycore_admin_page_header($user ? 'Sửa nhân viên' : 'Thêm nhân viên', 'Cập nhật hồ sơ, kỹ năng, chi nhánh và lịch làm việc.');
    }
    if (!$fragment && !empty($_GET['error'])) {
        echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash($_GET['error'])) . '</p></div>';
    }

    echo '<form class="beautycore-service-form beautycore-stage4-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="beautycore_save_staff"><input type="hidden" name="staff_id" value="' . esc_attr($staff_id) . '"><input id="beautycore-staff-image-id" type="hidden" name="image_id" value="' . esc_attr($staff['image_id']) . '">';
    wp_nonce_field('beautycore_save_staff');
    echo '<div class="beautycore-service-form-layout"><div class="beautycore-service-form-main"><nav class="beautycore-form-tabs"><a href="#staff-info">Hồ sơ</a><a href="#staff-assignment">Phân công</a><a href="#staff-schedule">Lịch làm việc</a></nav>';
    echo '<section id="staff-info" class="beautycore-panel beautycore-form-tab"><h2>Hồ sơ nhân viên</h2><div class="beautycore-image-picker"><div class="beautycore-stage4-image-preview">' . ($staff['image_id'] ? wp_get_attachment_image($staff['image_id'], 'medium') : '<span>Chưa chọn ảnh</span>') . '</div><div><button type="button" class="button" data-beautycore-select-staff-image>Chọn ảnh</button> <button type="button" class="button" data-beautycore-remove-staff-image>Bỏ ảnh</button></div></div><div class="beautycore-form-grid">';
    beautycore_stage4_render_field('Họ tên', 'name', $staff['name'], 'text', '', true);
    beautycore_stage4_render_field('Email', 'email', $staff['email'], 'email', $user ? 'Email đăng nhập có thể được cập nhật.' : 'Hệ thống tự tạo tài khoản nhân viên từ email.', true);
    beautycore_stage4_render_field('Số điện thoại', 'phone', $staff['phone'], 'tel');
    beautycore_stage4_render_field('Chức danh', 'title', $staff['title'], 'text', 'Ví dụ: Chuyên viên nail.');
    beautycore_stage4_render_field('Chuyên môn', 'specialty', $staff['specialty'], 'text', 'Ví dụ: Nail art, chăm sóc móng.');
    echo '</div>';
    beautycore_stage4_render_field('Giới thiệu', 'bio', $staff['bio'], 'textarea');
    echo '</section><section id="staff-assignment" class="beautycore-panel beautycore-form-tab"><h2>Chi nhánh và kỹ năng</h2><p class="description">Nhân viên chỉ nhận được lịch thuộc chi nhánh và dịch vụ đã gán.</p>';
    beautycore_stage4_render_assignments($staff['branch_ids'], $staff['service_ids']);
    echo '</section><section id="staff-schedule" class="beautycore-panel beautycore-form-tab"><h2>Lịch làm việc hàng tuần</h2>';
    beautycore_stage4_render_schedule($staff['schedule']);
    beautycore_stage4_render_field('Ngày nghỉ', 'days_off', implode("\n", $staff['days_off']), 'textarea', 'Mỗi dòng một ngày theo định dạng YYYY-MM-DD.');
    beautycore_stage4_render_special_schedule($staff['special_schedule']);
    echo '</section></div><aside class="beautycore-service-form-side"><section class="beautycore-panel beautycore-form-tab"><h2>Trạng thái</h2><div class="beautycore-form-field"><label for="beautycore-staff-status"><strong>Trạng thái</strong></label><select id="beautycore-staff-status" name="status">';
    foreach (beautycore_staff_statuses() as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($staff['status'], $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></div><div class="beautycore-checkbox-field"><label><input type="checkbox" name="visible" value="1" ' . checked($staff['visible'], true, false) . '> Hiển thị trên website</label></div></section><div class="beautycore-form-actions"><button type="submit" class="button button-primary button-large">Lưu nhân viên</button><a class="button button-large" data-beautycore-stage4-close href="' . esc_url(admin_url('admin.php?page=beautycore-staff')) . '">Hủy</a></div></aside></div></form>';
    if (!$fragment) {
        beautycore_admin_page_footer();
    }
}

function beautycore_render_branch_edit_page($branch_id_override = null, $fragment = false) {
    if (!current_user_can('manage_beautycore_branches')) {
        wp_die('Bạn không có quyền chỉnh sửa chi nhánh.');
    }

    $branch_id = $branch_id_override === null ? (isset($_GET['id']) ? absint($_GET['id']) : 0) : absint($branch_id_override);
    $branch = $branch_id ? get_post($branch_id) : null;
    if ($branch_id && (!$branch || $branch->post_type !== BEAUTYCORE_BRANCH_POST_TYPE)) {
        wp_die('Không tìm thấy chi nhánh.');
    }
    $data = $branch ? beautycore_branch_data($branch) : array(
        'name' => '', 'description' => '', 'status' => 'publish', 'address' => '', 'phone' => '', 'email' => '', 'map_url' => '',
        'schedule' => beautycore_stage4_default_schedule(), 'days_off' => array(), 'special_schedule' => array(),
        'service_ids' => array(), 'staff_ids' => array(),
    );

    if (!$fragment) {
        beautycore_admin_page_header($branch ? 'Sửa chi nhánh' : 'Thêm chi nhánh', 'Cập nhật địa điểm, giờ mở cửa, dịch vụ và nhân viên.');
    }
    if (!$fragment && !empty($_GET['error'])) {
        echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash($_GET['error'])) . '</p></div>';
    }

    echo '<form class="beautycore-service-form beautycore-stage4-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="beautycore_save_branch"><input type="hidden" name="branch_id" value="' . esc_attr($branch_id) . '">';
    wp_nonce_field('beautycore_save_branch');
    echo '<div class="beautycore-service-form-layout"><div class="beautycore-service-form-main"><nav class="beautycore-form-tabs"><a href="#branch-info">Thông tin</a><a href="#branch-assignment">Phân công</a><a href="#branch-schedule">Giờ hoạt động</a></nav>';
    echo '<section id="branch-info" class="beautycore-panel beautycore-form-tab"><h2>Thông tin chi nhánh</h2><div class="beautycore-form-grid">';
    beautycore_stage4_render_field('Tên chi nhánh', 'name', $data['name'], 'text', '', true);
    beautycore_stage4_render_field('Số điện thoại', 'phone', $data['phone'], 'tel');
    beautycore_stage4_render_field('Email', 'email', $data['email'], 'email');
    beautycore_stage4_render_field('Liên kết Google Maps', 'map_url', $data['map_url'], 'url');
    echo '</div>';
    beautycore_stage4_render_field('Địa chỉ', 'address', $data['address'], 'textarea', '', true);
    beautycore_stage4_render_field('Mô tả', 'description', $data['description'], 'textarea');
    echo '</section><section id="branch-assignment" class="beautycore-panel beautycore-form-tab"><h2>Dịch vụ và nhân viên</h2><p class="description">Website chỉ hiển thị các dịch vụ được gán cho chi nhánh đang hoạt động.</p>';
    beautycore_stage4_render_assignments(null, $data['service_ids'], $data['staff_ids']);
    echo '</section><section id="branch-schedule" class="beautycore-panel beautycore-form-tab"><h2>Giờ hoạt động hàng tuần</h2>';
    beautycore_stage4_render_schedule($data['schedule']);
    beautycore_stage4_render_field('Ngày đóng cửa', 'days_off', implode("\n", $data['days_off']), 'textarea', 'Mỗi dòng một ngày theo định dạng YYYY-MM-DD.');
    beautycore_stage4_render_special_schedule($data['special_schedule']);
    echo '</section></div><aside class="beautycore-service-form-side"><section class="beautycore-panel beautycore-form-tab"><h2>Trạng thái</h2><div class="beautycore-form-field"><label for="beautycore-branch-status"><strong>Trạng thái</strong></label><select id="beautycore-branch-status" name="status">';
    foreach (beautycore_branch_statuses() as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($data['status'], $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></div><p class="description">Chi nhánh tạm đóng sẽ không xuất hiện trong form đặt lịch.</p></section><div class="beautycore-form-actions"><button type="submit" class="button button-primary button-large">Lưu chi nhánh</button><a class="button button-large" data-beautycore-stage4-close href="' . esc_url(admin_url('admin.php?page=beautycore-branches')) . '">Hủy</a></div></aside></div></form>';
    if (!$fragment) {
        beautycore_admin_page_footer();
    }
}

function beautycore_ajax_stage4_form() {
    check_ajax_referer('beautycore_stage4_modal');
    $entity = isset($_GET['entity']) ? sanitize_key(wp_unslash($_GET['entity'])) : '';
    $object_id = isset($_GET['object_id']) ? absint($_GET['object_id']) : 0;

    ob_start();
    if ($entity === 'staff' && current_user_can('manage_beautycore_staff')) {
        beautycore_render_staff_edit_page($object_id, true);
    } elseif ($entity === 'branch' && current_user_can('manage_beautycore_branches')) {
        beautycore_render_branch_edit_page($object_id, true);
    } else {
        wp_send_json_error(array('message' => 'Bạn không có quyền thực hiện thao tác này.'), 403);
    }
    wp_send_json_success(ob_get_clean());
}
add_action('wp_ajax_beautycore_stage4_form', 'beautycore_ajax_stage4_form');

function beautycore_stage4_validate_schedule_config($schedule, $special_schedule, $must_open = true) {
    $open_days = 0;
    foreach ((array) $schedule as $row) {
        if (empty($row['enabled'])) {
            continue;
        }
        $open_days++;
        if ($row['start'] >= $row['end']) {
            return new WP_Error('beautycore_invalid_schedule', 'Giờ kết thúc phải sau giờ bắt đầu.');
        }
        if (($row['break_start'] && !$row['break_end']) || (!$row['break_start'] && $row['break_end'])) {
            return new WP_Error('beautycore_invalid_break', 'Giờ nghỉ phải có đủ thời gian bắt đầu và kết thúc.');
        }
        if ($row['break_start'] && ($row['break_start'] >= $row['break_end'] || $row['break_start'] < $row['start'] || $row['break_end'] > $row['end'])) {
            return new WP_Error('beautycore_invalid_break', 'Giờ nghỉ phải nằm trong giờ làm việc.');
        }
    }
    if ($must_open && !$open_days) {
        return new WP_Error('beautycore_schedule_closed', 'Cần chọn ít nhất một ngày làm việc.');
    }
    foreach ((array) $special_schedule as $row) {
        if ($row['status'] === 'open' && $row['start'] >= $row['end']) {
            return new WP_Error('beautycore_invalid_special_schedule', 'Lịch đặc biệt có giờ kết thúc không hợp lệ.');
        }
        if ($row['status'] === 'open' && $row['break_start'] && ($row['break_start'] >= $row['break_end'] || $row['break_start'] < $row['start'] || $row['break_end'] > $row['end'])) {
            return new WP_Error('beautycore_invalid_special_break', 'Giờ nghỉ trong lịch đặc biệt không hợp lệ.');
        }
    }
    return true;
}

function beautycore_stage4_clean_ids($key, $post_type = '') {
    $raw = !empty($_POST[$key]) && is_array($_POST[$key]) ? wp_unslash($_POST[$key]) : array();
    $ids = array_values(array_unique(array_filter(array_map('absint', $raw))));
    return $post_type ? beautycore_stage4_valid_post_ids($ids, $post_type) : $ids;
}

function beautycore_handle_save_staff() {
    if (!current_user_can('manage_beautycore_staff')) {
        wp_die('Bạn không có quyền chỉnh sửa nhân viên.');
    }
    check_admin_referer('beautycore_save_staff');

    $staff_id = isset($_POST['staff_id']) ? absint($_POST['staff_id']) : 0;
    $existing = $staff_id ? get_userdata($staff_id) : null;
    if ($staff_id && !$existing) {
        wp_die('Nhân viên không hợp lệ.');
    }
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
    if (!$name || !is_email($email)) {
        beautycore_stage4_admin_redirect('beautycore-staff', array('error' => 'Họ tên và email hợp lệ là bắt buộc.'));
    }
    $email_user = email_exists($email);
    if ($email_user && (int) $email_user !== $staff_id) {
        beautycore_stage4_admin_redirect('beautycore-staff', array('error' => 'Email này đã được sử dụng bởi tài khoản khác.'));
    }

    $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'active';
    if (!isset(beautycore_staff_statuses()[$status])) {
        $status = 'active';
    }
    $branch_ids = beautycore_stage4_clean_ids('branch_ids', BEAUTYCORE_BRANCH_POST_TYPE);
    $service_ids = beautycore_stage4_clean_ids('service_ids', 'beautycore_service');
    if ($status === 'active' && !$branch_ids) {
        beautycore_stage4_admin_redirect('beautycore-staff', array('error' => 'Nhân viên đang làm việc cần được gán ít nhất một chi nhánh.'));
    }
    $schedule = beautycore_stage4_normalize_schedule(isset($_POST['schedule']) ? wp_unslash($_POST['schedule']) : array());
    $special = beautycore_stage4_sanitize_special_schedule(isset($_POST['special_schedule']) ? wp_unslash($_POST['special_schedule']) : array());
    $valid_schedule = beautycore_stage4_validate_schedule_config($schedule, $special, $status === 'active');
    if (is_wp_error($valid_schedule)) {
        beautycore_stage4_admin_redirect('beautycore-staff', array('error' => $valid_schedule->get_error_message()));
    }

    if ($existing) {
        $result = wp_update_user(array('ID' => $staff_id, 'display_name' => $name, 'user_email' => $email));
    } else {
        $base_login = sanitize_user(strstr($email, '@', true), true);
        if (!$base_login) {
            $base_login = sanitize_user(remove_accents($name), true);
        }
        if (!$base_login) {
            $base_login = 'nhanvien';
        }
        $login = $base_login;
        $suffix = 1;
        while (username_exists($login)) {
            $login = $base_login . $suffix;
            $suffix++;
        }
        $result = wp_insert_user(array(
            'user_login'   => $login,
            'user_email'   => $email,
            'display_name' => $name,
            'user_pass'    => wp_generate_password(24, true, true),
            'role'         => 'staff',
        ));
    }
    if (is_wp_error($result)) {
        beautycore_stage4_admin_redirect('beautycore-staff', array('error' => $result->get_error_message()));
    }
    $staff_id = absint($result);
    update_user_meta($staff_id, '_beautycore_staff_profile', '1');
    update_user_meta($staff_id, '_beautycore_staff_phone', $phone);
    update_user_meta($staff_id, '_beautycore_staff_title', isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '');
    update_user_meta($staff_id, '_beautycore_staff_specialty', isset($_POST['specialty']) ? sanitize_text_field(wp_unslash($_POST['specialty'])) : '');
    update_user_meta($staff_id, 'description', isset($_POST['bio']) ? sanitize_textarea_field(wp_unslash($_POST['bio'])) : '');
    update_user_meta($staff_id, '_beautycore_staff_image_id', isset($_POST['image_id']) ? absint($_POST['image_id']) : 0);
    update_user_meta($staff_id, '_beautycore_staff_status', $status);
    update_user_meta($staff_id, '_beautycore_staff_visible', !empty($_POST['visible']) ? '1' : '0');
    update_user_meta($staff_id, '_beautycore_staff_branch_ids', $branch_ids);
    update_user_meta($staff_id, '_beautycore_staff_service_ids', $service_ids);
    update_user_meta($staff_id, '_beautycore_staff_schedule', $schedule);
    update_user_meta($staff_id, '_beautycore_staff_days_off', beautycore_stage4_sanitize_dates(isset($_POST['days_off']) ? wp_unslash($_POST['days_off']) : ''));
    update_user_meta($staff_id, '_beautycore_staff_special_schedule', $special);
    beautycore_stage4_sync_staff_relations($staff_id, $branch_ids, $service_ids);
    if (function_exists('beautycore_audit_log')) {
        beautycore_audit_log($existing ? 'staff_updated' : 'staff_created', array('name' => $name, 'status' => $status), 'user', $staff_id);
    }
    beautycore_stage4_admin_redirect('beautycore-staff', array('updated' => 1));
}
add_action('admin_post_beautycore_save_staff', 'beautycore_handle_save_staff');

function beautycore_handle_save_branch() {
    if (!current_user_can('manage_beautycore_branches')) {
        wp_die('Bạn không có quyền chỉnh sửa chi nhánh.');
    }
    check_admin_referer('beautycore_save_branch');

    $branch_id = isset($_POST['branch_id']) ? absint($_POST['branch_id']) : 0;
    $existing = $branch_id ? get_post($branch_id) : null;
    if ($branch_id && (!$existing || $existing->post_type !== BEAUTYCORE_BRANCH_POST_TYPE)) {
        wp_die('Chi nhánh không hợp lệ.');
    }
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $address = isset($_POST['address']) ? sanitize_textarea_field(wp_unslash($_POST['address'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    if (!$name || !$address) {
        beautycore_stage4_admin_redirect('beautycore-branches', array('error' => 'Tên và địa chỉ chi nhánh là bắt buộc.'));
    }
    if (!empty($_POST['email']) && !is_email($email)) {
        beautycore_stage4_admin_redirect('beautycore-branches', array('error' => 'Email chi nhánh không hợp lệ.'));
    }
    $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'publish';
    if (!isset(beautycore_branch_statuses()[$status])) {
        $status = 'publish';
    }
    $schedule = beautycore_stage4_normalize_schedule(isset($_POST['schedule']) ? wp_unslash($_POST['schedule']) : array());
    $special = beautycore_stage4_sanitize_special_schedule(isset($_POST['special_schedule']) ? wp_unslash($_POST['special_schedule']) : array());
    $valid_schedule = beautycore_stage4_validate_schedule_config($schedule, $special, $status === 'publish');
    if (is_wp_error($valid_schedule)) {
        beautycore_stage4_admin_redirect('beautycore-branches', array('error' => $valid_schedule->get_error_message()));
    }
    if ($existing && $existing->post_status === 'publish' && $status !== 'publish' && count(beautycore_stage4_branch_posts(false)) <= 1) {
        beautycore_stage4_admin_redirect('beautycore-branches', array('error' => 'Cần giữ ít nhất một chi nhánh đang hoạt động.'));
    }

    $post_id = wp_insert_post(wp_slash(array(
        'ID'           => $branch_id,
        'post_type'    => BEAUTYCORE_BRANCH_POST_TYPE,
        'post_status'  => $status,
        'post_title'   => $name,
        'post_content' => isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '',
    )), true);
    if (is_wp_error($post_id)) {
        beautycore_stage4_admin_redirect('beautycore-branches', array('error' => $post_id->get_error_message()));
    }
    $service_ids = beautycore_stage4_clean_ids('service_ids', 'beautycore_service');
    $staff_ids = beautycore_stage4_clean_ids('staff_ids');
    $staff_ids = array_values(array_filter($staff_ids, 'get_userdata'));
    update_post_meta($post_id, '_beautycore_branch_address', $address);
    update_post_meta($post_id, '_beautycore_branch_phone', isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '');
    update_post_meta($post_id, '_beautycore_branch_email', $email);
    update_post_meta($post_id, '_beautycore_branch_map_url', isset($_POST['map_url']) ? esc_url_raw(wp_unslash($_POST['map_url'])) : '');
    update_post_meta($post_id, '_beautycore_branch_schedule', $schedule);
    update_post_meta($post_id, '_beautycore_branch_days_off', beautycore_stage4_sanitize_dates(isset($_POST['days_off']) ? wp_unslash($_POST['days_off']) : ''));
    update_post_meta($post_id, '_beautycore_branch_special_schedule', $special);
    update_post_meta($post_id, '_beautycore_service_ids', $service_ids);
    update_post_meta($post_id, '_beautycore_staff_ids', $staff_ids);
    beautycore_stage4_sync_branch_relations($post_id, $staff_ids, $service_ids);
    if (function_exists('beautycore_audit_log')) {
        beautycore_audit_log($existing ? 'branch_updated' : 'branch_created', array('name' => $name, 'status' => $status), BEAUTYCORE_BRANCH_POST_TYPE, $post_id);
    }
    beautycore_stage4_admin_redirect('beautycore-branches', array('updated' => 1));
}
add_action('admin_post_beautycore_save_branch', 'beautycore_handle_save_branch');

function beautycore_stage4_has_appointments($meta_key, $object_id) {
    $appointments = get_posts(array(
        'post_type' => BEAUTYCORE_APPOINTMENT_POST_TYPE, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids',
        'meta_query' => array(array('key' => $meta_key, 'value' => (string) absint($object_id))),
    ));
    return (bool) $appointments;
}

function beautycore_handle_toggle_staff() {
    $staff_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    if (!current_user_can('manage_beautycore_staff') || !$staff_id) {
        wp_die('Bạn không có quyền thực hiện thao tác này.');
    }
    check_admin_referer('beautycore_toggle_staff_' . $staff_id);
    $user = get_userdata($staff_id);
    if (!$user) {
        wp_die('Nhân viên không hợp lệ.');
    }
    $staff = beautycore_staff_data($user);
    $status = $staff['status'] === 'active' ? 'inactive' : 'active';
    if ($status === 'active' && !$staff['branch_ids']) {
        beautycore_stage4_admin_redirect('beautycore-staff', array('error' => 'Hãy gán chi nhánh trước khi kích hoạt nhân viên.'));
    }
    if ($status === 'active') {
        $valid_schedule = beautycore_stage4_validate_schedule_config($staff['schedule'], $staff['special_schedule'], true);
        if (is_wp_error($valid_schedule)) {
            beautycore_stage4_admin_redirect('beautycore-staff', array('error' => $valid_schedule->get_error_message()));
        }
    }
    update_user_meta($staff_id, '_beautycore_staff_status', $status);
    beautycore_stage4_admin_redirect('beautycore-staff', array('updated' => 1));
}
add_action('admin_post_beautycore_toggle_staff', 'beautycore_handle_toggle_staff');

function beautycore_handle_delete_staff() {
    $staff_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    if (!current_user_can('manage_beautycore_staff') || !$staff_id) {
        wp_die('Bạn không có quyền xóa nhân viên.');
    }
    check_admin_referer('beautycore_delete_staff_' . $staff_id);
    $user = get_userdata($staff_id);
    if (!$user || !in_array('staff', (array) $user->roles, true) || $staff_id === get_current_user_id()) {
        beautycore_stage4_admin_redirect('beautycore-staff', array('error' => 'Không thể xóa tài khoản nhân viên này.'));
    }
    if (beautycore_stage4_has_appointments('_beautycore_staff_id', $staff_id)) {
        beautycore_stage4_admin_redirect('beautycore-staff', array('error' => 'Nhân viên đã có lịch hẹn. Hãy chuyển sang trạng thái Tạm nghỉ thay vì xóa.'));
    }
    beautycore_stage4_sync_staff_relations($staff_id, array(), array());
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($staff_id);
    beautycore_stage4_admin_redirect('beautycore-staff', array('deleted' => 1));
}
add_action('admin_post_beautycore_delete_staff', 'beautycore_handle_delete_staff');

function beautycore_handle_toggle_branch() {
    $branch_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    if (!current_user_can('manage_beautycore_branches') || !$branch_id) {
        wp_die('Bạn không có quyền thực hiện thao tác này.');
    }
    check_admin_referer('beautycore_toggle_branch_' . $branch_id);
    $branch = get_post($branch_id);
    if (!$branch || $branch->post_type !== BEAUTYCORE_BRANCH_POST_TYPE) {
        wp_die('Chi nhánh không hợp lệ.');
    }
    $new_status = $branch->post_status === 'publish' ? 'hidden' : 'publish';
    if ($new_status === 'hidden' && count(beautycore_stage4_branch_posts(false)) <= 1) {
        beautycore_stage4_admin_redirect('beautycore-branches', array('error' => 'Cần giữ ít nhất một chi nhánh đang hoạt động.'));
    }
    if ($new_status === 'publish') {
        $branch_data = beautycore_branch_data($branch);
        $valid_schedule = beautycore_stage4_validate_schedule_config($branch_data['schedule'], $branch_data['special_schedule'], true);
        if (is_wp_error($valid_schedule)) {
            beautycore_stage4_admin_redirect('beautycore-branches', array('error' => $valid_schedule->get_error_message()));
        }
    }
    wp_update_post(array('ID' => $branch_id, 'post_status' => $new_status));
    beautycore_stage4_admin_redirect('beautycore-branches', array('updated' => 1));
}
add_action('admin_post_beautycore_toggle_branch', 'beautycore_handle_toggle_branch');

function beautycore_handle_delete_branch() {
    $branch_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    if (!current_user_can('manage_beautycore_branches') || !$branch_id) {
        wp_die('Bạn không có quyền xóa chi nhánh.');
    }
    check_admin_referer('beautycore_delete_branch_' . $branch_id);
    $branch = get_post($branch_id);
    if (!$branch || $branch->post_type !== BEAUTYCORE_BRANCH_POST_TYPE) {
        wp_die('Chi nhánh không hợp lệ.');
    }
    if ($branch->post_status === 'publish' && count(beautycore_stage4_branch_posts(false)) <= 1) {
        beautycore_stage4_admin_redirect('beautycore-branches', array('error' => 'Cần giữ ít nhất một chi nhánh đang hoạt động.'));
    }
    if (beautycore_stage4_has_appointments('_beautycore_branch_id', $branch_id)) {
        beautycore_stage4_admin_redirect('beautycore-branches', array('error' => 'Chi nhánh đã có lịch hẹn. Hãy chuyển sang trạng thái Tạm đóng thay vì xóa.'));
    }
    beautycore_stage4_sync_branch_relations($branch_id, array(), array());
    wp_trash_post($branch_id);
    beautycore_stage4_admin_redirect('beautycore-branches', array('deleted' => 1));
}
add_action('admin_post_beautycore_delete_branch', 'beautycore_handle_delete_branch');
