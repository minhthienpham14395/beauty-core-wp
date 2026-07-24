<?php
/**
 * Staff and branch domain data for phase 4.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BEAUTYCORE_STAGE4_VERSION', '1.0.0');
define('BEAUTYCORE_BRANCH_POST_TYPE', 'beautycore_branch');

function beautycore_staff_statuses() {
    return array(
        'active'   => 'Đang làm việc',
        'inactive' => 'Tạm nghỉ',
    );
}

function beautycore_branch_statuses() {
    return array(
        'publish' => 'Đang hoạt động',
        'hidden'  => 'Tạm đóng',
    );
}

function beautycore_stage4_weekdays() {
    return array(
        '1' => 'Thứ Hai',
        '2' => 'Thứ Ba',
        '3' => 'Thứ Tư',
        '4' => 'Thứ Năm',
        '5' => 'Thứ Sáu',
        '6' => 'Thứ Bảy',
        '0' => 'Chủ Nhật',
    );
}

function beautycore_register_branch_content_type() {
    register_post_type(BEAUTYCORE_BRANCH_POST_TYPE, array(
        'labels' => array(
            'name'          => 'Chi nhánh',
            'singular_name' => 'Chi nhánh',
        ),
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => false,
        'show_in_menu'       => false,
        'show_in_rest'       => false,
        'supports'           => array('title', 'editor'),
        'capability_type'    => 'post',
        'map_meta_cap'       => true,
    ));
}
add_action('init', 'beautycore_register_branch_content_type', 1);

function beautycore_stage4_valid_time($value, $fallback = '') {
    $value = sanitize_text_field((string) $value);
    return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : $fallback;
}

function beautycore_stage4_default_schedule($opening = '', $closing = '') {
    $settings = function_exists('beautycore_appointment_settings') ? beautycore_appointment_settings() : array();
    $opening = beautycore_stage4_valid_time($opening, !empty($settings['opening_time']) ? $settings['opening_time'] : '09:00');
    $closing = beautycore_stage4_valid_time($closing, !empty($settings['closing_time']) ? $settings['closing_time'] : '21:00');
    $workdays = !empty($settings['workdays']) ? array_map('strval', (array) $settings['workdays']) : array('1', '2', '3', '4', '5', '6');
    $schedule = array();

    foreach (beautycore_stage4_weekdays() as $day => $label) {
        $schedule[$day] = array(
            'enabled'     => in_array((string) $day, $workdays, true),
            'start'       => $opening,
            'end'         => $closing,
            'break_start' => '',
            'break_end'   => '',
        );
    }

    return $schedule;
}

function beautycore_stage4_normalize_schedule($raw, $opening = '', $closing = '') {
    $defaults = beautycore_stage4_default_schedule($opening, $closing);
    if (!is_array($raw) || !$raw) {
        return $defaults;
    }

    $schedule = array();
    foreach ($defaults as $day => $default) {
        $row = isset($raw[$day]) && is_array($raw[$day]) ? $raw[$day] : array();
        $schedule[$day] = array(
            'enabled'     => !empty($row['enabled']),
            'start'       => beautycore_stage4_valid_time(isset($row['start']) ? $row['start'] : '', $default['start']),
            'end'         => beautycore_stage4_valid_time(isset($row['end']) ? $row['end'] : '', $default['end']),
            'break_start' => beautycore_stage4_valid_time(isset($row['break_start']) ? $row['break_start'] : ''),
            'break_end'   => beautycore_stage4_valid_time(isset($row['break_end']) ? $row['break_end'] : ''),
        );
    }

    return $schedule;
}

function beautycore_stage4_sanitize_dates($raw) {
    if (is_string($raw)) {
        $raw = preg_split('/[\s,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
    }

    $dates = array();
    foreach ((array) $raw as $date) {
        $date = sanitize_text_field((string) $date);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dates[] = $date;
        }
    }

    return array_values(array_unique($dates));
}

function beautycore_stage4_sanitize_special_schedule($raw) {
    $items = array();
    foreach ((array) $raw as $row) {
        if (!is_array($row)) {
            continue;
        }
        $date = isset($row['date']) ? sanitize_text_field($row['date']) : '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            continue;
        }
        $status = isset($row['status']) && sanitize_key($row['status']) === 'closed' ? 'closed' : 'open';
        $items[] = array(
            'date'        => $date,
            'status'      => $status,
            'start'       => beautycore_stage4_valid_time(isset($row['start']) ? $row['start'] : '', '09:00'),
            'end'         => beautycore_stage4_valid_time(isset($row['end']) ? $row['end'] : '', '21:00'),
            'break_start' => beautycore_stage4_valid_time(isset($row['break_start']) ? $row['break_start'] : ''),
            'break_end'   => beautycore_stage4_valid_time(isset($row['break_end']) ? $row['break_end'] : ''),
        );
    }

    usort($items, function ($left, $right) {
        return strcmp($left['date'], $right['date']);
    });

    $unique = array();
    foreach ($items as $item) {
        $unique[$item['date']] = $item;
    }

    return array_values($unique);
}

function beautycore_stage4_array_meta($object_id, $key, $type = 'post') {
    $value = $type === 'user' ? get_user_meta($object_id, $key, true) : get_post_meta($object_id, $key, true);
    if (!is_array($value)) {
        return array();
    }

    return array_values(array_unique(array_filter(array_map('sanitize_text_field', $value), 'strlen')));
}

function beautycore_branch_data($branch) {
    $branch = $branch instanceof WP_Post ? $branch : get_post(absint($branch));
    if (!$branch || $branch->post_type !== BEAUTYCORE_BRANCH_POST_TYPE) {
        return array();
    }

    return array(
        'id'               => (int) $branch->ID,
        'name'             => $branch->post_title,
        'description'      => $branch->post_content,
        'status'           => $branch->post_status === 'publish' ? 'publish' : 'hidden',
        'address'          => (string) get_post_meta($branch->ID, '_beautycore_branch_address', true),
        'phone'            => (string) get_post_meta($branch->ID, '_beautycore_branch_phone', true),
        'email'            => (string) get_post_meta($branch->ID, '_beautycore_branch_email', true),
        'map_url'          => (string) get_post_meta($branch->ID, '_beautycore_branch_map_url', true),
        'schedule'         => beautycore_stage4_normalize_schedule(get_post_meta($branch->ID, '_beautycore_branch_schedule', true)),
        'days_off'         => beautycore_stage4_sanitize_dates(get_post_meta($branch->ID, '_beautycore_branch_days_off', true)),
        'special_schedule' => beautycore_stage4_sanitize_special_schedule(get_post_meta($branch->ID, '_beautycore_branch_special_schedule', true)),
        'service_ids'      => array_map('absint', beautycore_stage4_array_meta($branch->ID, '_beautycore_service_ids')),
        'staff_ids'        => array_map('absint', beautycore_stage4_array_meta($branch->ID, '_beautycore_staff_ids')),
    );
}

function beautycore_stage4_branch_posts($include_inactive = true) {
    return get_posts(array(
        'post_type'      => BEAUTYCORE_BRANCH_POST_TYPE,
        'post_status'    => $include_inactive ? array('publish', 'hidden') : array('publish'),
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
    ));
}

function beautycore_staff_data($user) {
    $user = $user instanceof WP_User ? $user : get_userdata(absint($user));
    if (!$user) {
        return array();
    }

    $status = sanitize_key((string) get_user_meta($user->ID, '_beautycore_staff_status', true));
    if (!isset(beautycore_staff_statuses()[$status])) {
        $status = 'active';
    }

    return array(
        'id'               => (int) $user->ID,
        'name'             => $user->display_name ?: $user->user_login,
        'login'            => $user->user_login,
        'email'            => $user->user_email,
        'phone'            => (string) get_user_meta($user->ID, '_beautycore_staff_phone', true),
        'title'            => (string) get_user_meta($user->ID, '_beautycore_staff_title', true),
        'specialty'        => (string) get_user_meta($user->ID, '_beautycore_staff_specialty', true),
        'bio'              => (string) get_user_meta($user->ID, 'description', true),
        'image_id'         => absint(get_user_meta($user->ID, '_beautycore_staff_image_id', true)),
        'status'           => $status,
        'visible'          => get_user_meta($user->ID, '_beautycore_staff_visible', true) !== '0',
        'profile'          => get_user_meta($user->ID, '_beautycore_staff_profile', true) === '1',
        'branch_ids'       => array_map('absint', beautycore_stage4_array_meta($user->ID, '_beautycore_staff_branch_ids', 'user')),
        'service_ids'      => array_map('absint', beautycore_stage4_array_meta($user->ID, '_beautycore_staff_service_ids', 'user')),
        'schedule'         => beautycore_stage4_normalize_schedule(get_user_meta($user->ID, '_beautycore_staff_schedule', true)),
        'days_off'         => beautycore_stage4_sanitize_dates(get_user_meta($user->ID, '_beautycore_staff_days_off', true)),
        'special_schedule' => beautycore_stage4_sanitize_special_schedule(get_user_meta($user->ID, '_beautycore_staff_special_schedule', true)),
    );
}

function beautycore_stage4_staff_users($include_inactive = true) {
    $users = get_users(array(
        'role__in' => array('owner', 'manager', 'receptionist', 'staff'),
        'orderby'  => 'display_name',
        'order'    => 'ASC',
    ));

    return array_values(array_filter($users, function ($user) use ($include_inactive) {
        $is_profile = get_user_meta($user->ID, '_beautycore_staff_profile', true) === '1';
        $is_staff_role = in_array('staff', (array) $user->roles, true);
        if (!$is_profile && !$is_staff_role) {
            return false;
        }
        return $include_inactive || beautycore_staff_data($user)['status'] === 'active';
    }));
}

function beautycore_stage4_branch_options($options = array()) {
    $branches = array();
    foreach (beautycore_stage4_branch_posts(false) as $branch) {
        $branches[(string) $branch->ID] = $branch->post_title;
    }
    return $branches ?: $options;
}
add_filter('beautycore_service_branch_options', 'beautycore_stage4_branch_options', 20);

function beautycore_stage4_staff_options($options = array()) {
    $staff = array();
    foreach (beautycore_stage4_staff_users(false) as $user) {
        $staff[(string) $user->ID] = $user->display_name ?: $user->user_login;
    }
    return $staff ?: $options;
}
add_filter('beautycore_service_staff_options', 'beautycore_stage4_staff_options', 20);

function beautycore_stage4_all_service_ids() {
    return get_posts(array(
        'post_type'      => 'beautycore_service',
        'post_status'    => array_keys(beautycore_service_statuses()),
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ));
}

function beautycore_stage4_valid_post_ids($ids, $post_type) {
    $valid = array();
    foreach (array_map('absint', (array) $ids) as $id) {
        $post = get_post($id);
        if ($post && $post->post_type === $post_type && $post->post_status !== 'trash') {
            $valid[] = $id;
        }
    }
    return array_values(array_unique($valid));
}

function beautycore_stage4_update_relation_meta($object_id, $key, $related_id, $assigned, $type = 'post') {
    $values = beautycore_stage4_array_meta($object_id, $key, $type);
    $related_id = (string) absint($related_id);
    $values = array_values(array_diff(array_map('strval', $values), array($related_id)));
    if ($assigned) {
        $values[] = $related_id;
    }
    $values = array_values(array_unique($values));
    if ($type === 'user') {
        update_user_meta($object_id, $key, $values);
    } else {
        update_post_meta($object_id, $key, $values);
    }
}

function beautycore_stage4_sync_staff_relations($staff_id, $branch_ids, $service_ids) {
    $staff_id = absint($staff_id);
    $branch_ids = array_map('absint', (array) $branch_ids);
    $service_ids = array_map('absint', (array) $service_ids);

    foreach (beautycore_stage4_branch_posts(true) as $branch) {
        beautycore_stage4_update_relation_meta($branch->ID, '_beautycore_staff_ids', $staff_id, in_array($branch->ID, $branch_ids, true));
    }
    foreach (beautycore_stage4_all_service_ids() as $service_id) {
        beautycore_stage4_update_relation_meta($service_id, '_beautycore_staff_ids', $staff_id, in_array((int) $service_id, $service_ids, true));
    }
}

function beautycore_stage4_sync_branch_relations($branch_id, $staff_ids, $service_ids) {
    $branch_id = absint($branch_id);
    $staff_ids = array_map('absint', (array) $staff_ids);
    $service_ids = array_map('absint', (array) $service_ids);

    foreach (beautycore_stage4_staff_users(true) as $user) {
        beautycore_stage4_update_relation_meta($user->ID, '_beautycore_staff_branch_ids', $branch_id, in_array($user->ID, $staff_ids, true), 'user');
    }
    foreach (beautycore_stage4_all_service_ids() as $service_id) {
        beautycore_stage4_update_relation_meta($service_id, '_beautycore_branch_ids', $branch_id, in_array((int) $service_id, $service_ids, true));
    }
}

function beautycore_stage4_sync_service_relations($service_id) {
    $service_id = absint($service_id);
    $branch_ids = array_map('absint', beautycore_stage4_array_meta($service_id, '_beautycore_branch_ids'));
    $staff_ids = array_map('absint', beautycore_stage4_array_meta($service_id, '_beautycore_staff_ids'));

    foreach (beautycore_stage4_branch_posts(true) as $branch) {
        beautycore_stage4_update_relation_meta($branch->ID, '_beautycore_service_ids', $service_id, in_array($branch->ID, $branch_ids, true));
    }
    foreach (beautycore_stage4_staff_users(true) as $user) {
        beautycore_stage4_update_relation_meta($user->ID, '_beautycore_staff_service_ids', $service_id, in_array($user->ID, $staff_ids, true), 'user');
    }
}
add_action('beautycore_service_saved', 'beautycore_stage4_sync_service_relations', 20);

function beautycore_stage4_maybe_seed_data() {
    if (get_option('beautycore_stage4_data_version') === BEAUTYCORE_STAGE4_VERSION) {
        return;
    }

    $branches = beautycore_stage4_branch_posts(true);
    if (!$branches) {
        $config = function_exists('beautycore_site_config') ? beautycore_site_config() : array();
        $branch_id = wp_insert_post(wp_slash(array(
            'post_type'    => BEAUTYCORE_BRANCH_POST_TYPE,
            'post_status'  => 'publish',
            'post_title'   => 'Chi nhánh chính',
            'post_content' => '',
        )));
        if ($branch_id && !is_wp_error($branch_id)) {
            update_post_meta($branch_id, '_beautycore_branch_address', isset($config['address']) ? $config['address'] : '');
            update_post_meta($branch_id, '_beautycore_branch_phone', isset($config['phone_display']) ? $config['phone_display'] : '');
            update_post_meta($branch_id, '_beautycore_branch_email', isset($config['email']) ? $config['email'] : '');
            update_post_meta($branch_id, '_beautycore_branch_map_url', isset($config['google_map_url']) ? $config['google_map_url'] : '');
            update_post_meta($branch_id, '_beautycore_branch_schedule', beautycore_stage4_default_schedule());
            $branches = array(get_post($branch_id));
        }
    }

    $default_branch = $branches ? reset($branches) : null;
    if ($default_branch) {
        $assigned_services = array();
        foreach (beautycore_stage4_all_service_ids() as $service_id) {
            $branch_ids = beautycore_stage4_array_meta($service_id, '_beautycore_branch_ids');
            if (!$branch_ids || in_array('main', $branch_ids, true)) {
                $branch_ids = array_values(array_diff($branch_ids, array('main')));
                $branch_ids[] = (string) $default_branch->ID;
                update_post_meta($service_id, '_beautycore_branch_ids', array_values(array_unique($branch_ids)));
            }
            if (in_array((string) $default_branch->ID, array_map('strval', $branch_ids), true)) {
                $assigned_services[] = (string) $service_id;
            }
        }
        update_post_meta($default_branch->ID, '_beautycore_service_ids', $assigned_services);

        $appointments = get_posts(array(
            'post_type'      => BEAUTYCORE_APPOINTMENT_POST_TYPE,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ));
        foreach ($appointments as $appointment_id) {
            if ((string) get_post_meta($appointment_id, '_beautycore_branch_id', true) === 'main') {
                update_post_meta($appointment_id, '_beautycore_branch_id', (string) $default_branch->ID);
                update_post_meta($appointment_id, '_beautycore_branch_name', $default_branch->post_title);
            }
        }
    }

    update_option('beautycore_stage4_data_version', BEAUTYCORE_STAGE4_VERSION, false);
}
add_action('init', 'beautycore_stage4_maybe_seed_data', 25);

function beautycore_stage4_special_for_date($special_schedule, $date) {
    foreach ((array) $special_schedule as $item) {
        if (!empty($item['date']) && $item['date'] === $date) {
            return $item;
        }
    }
    return array();
}

function beautycore_stage4_validate_schedule_slot($schedule, $days_off, $special_schedule, $start, $end, $subject) {
    $date = $start->format('Y-m-d');
    if (in_array($date, (array) $days_off, true)) {
        return new WP_Error('beautycore_resource_day_off', sprintf('%s nghỉ vào ngày đã chọn.', $subject));
    }

    $special = beautycore_stage4_special_for_date($special_schedule, $date);
    if ($special) {
        if ($special['status'] === 'closed') {
            return new WP_Error('beautycore_resource_closed_special', sprintf('%s không làm việc vào ngày đã chọn.', $subject));
        }
        $row = $special;
        $row['enabled'] = true;
    } else {
        $day = $start->format('w');
        $row = isset($schedule[$day]) ? $schedule[$day] : array('enabled' => false);
    }

    if (empty($row['enabled'])) {
        return new WP_Error('beautycore_resource_closed_day', sprintf('%s không làm việc vào ngày đã chọn.', $subject));
    }

    $opening = beautycore_appointment_datetime($date, $row['start']);
    $closing = beautycore_appointment_datetime($date, $row['end']);
    if (!$opening || !$closing || $start < $opening || $end > $closing) {
        return new WP_Error('beautycore_resource_outside_hours', sprintf('Lịch hẹn phải nằm trong giờ làm việc của %s (%s–%s).', $subject, $row['start'], $row['end']));
    }

    if (!empty($row['break_start']) && !empty($row['break_end'])) {
        $break_start = beautycore_appointment_datetime($date, $row['break_start']);
        $break_end = beautycore_appointment_datetime($date, $row['break_end']);
        if ($break_start && $break_end && $start < $break_end && $end > $break_start) {
            return new WP_Error('beautycore_resource_break', sprintf('Khung giờ đã chọn trùng giờ nghỉ của %s.', $subject));
        }
    }

    return true;
}

function beautycore_stage4_validate_appointment_resources($normalized) {
    $branch_id = !empty($normalized['branch_id']) ? absint($normalized['branch_id']) : 0;
    $staff_id = !empty($normalized['staff_id']) ? absint($normalized['staff_id']) : 0;
    $service_id = !empty($normalized['service_id']) ? absint($normalized['service_id']) : 0;

    if ($branch_id) {
        $branch = get_post($branch_id);
        if (!$branch || $branch->post_type !== BEAUTYCORE_BRANCH_POST_TYPE || $branch->post_status !== 'publish') {
            return new WP_Error('beautycore_branch_closed', 'Chi nhánh đã đóng hoặc không còn hoạt động.');
        }
        $branch_data = beautycore_branch_data($branch);
        $valid = beautycore_stage4_validate_schedule_slot(
            $branch_data['schedule'],
            $branch_data['days_off'],
            $branch_data['special_schedule'],
            $normalized['start'],
            $normalized['end'],
            $branch_data['name']
        );
        if (is_wp_error($valid)) {
            return $valid;
        }
        if ($service_id) {
            $service_branches = array_map('absint', beautycore_stage4_array_meta($service_id, '_beautycore_branch_ids'));
            if ($service_branches && !in_array($branch_id, $service_branches, true)) {
                return new WP_Error('beautycore_service_not_at_branch', 'Dịch vụ chưa được cung cấp tại chi nhánh đã chọn.');
            }
        }
    } else {
        $valid = beautycore_appointment_is_within_working_hours($normalized['start'], $normalized['end']);
        if (is_wp_error($valid)) {
            return $valid;
        }
    }

    if ($staff_id) {
        $user = get_userdata($staff_id);
        if (!$user) {
            return new WP_Error('beautycore_staff_missing', 'Nhân viên không tồn tại.');
        }
        $staff = beautycore_staff_data($user);
        if ($staff['status'] !== 'active') {
            return new WP_Error('beautycore_staff_inactive', 'Nhân viên đang tạm nghỉ.');
        }
        if ($staff['profile']) {
            if ($branch_id && !in_array($branch_id, $staff['branch_ids'], true)) {
                return new WP_Error('beautycore_staff_not_at_branch', 'Nhân viên không làm việc tại chi nhánh đã chọn.');
            }
            if ($service_id && $staff['service_ids'] && !in_array($service_id, $staff['service_ids'], true)) {
                return new WP_Error('beautycore_staff_missing_skill', 'Nhân viên chưa được gán kỹ năng cho dịch vụ đã chọn.');
            }
            $valid = beautycore_stage4_validate_schedule_slot(
                $staff['schedule'],
                $staff['days_off'],
                $staff['special_schedule'],
                $normalized['start'],
                $normalized['end'],
                $staff['name']
            );
            if (is_wp_error($valid)) {
                return $valid;
            }
        } elseif (beautycore_appointment_staff_is_unavailable($staff_id, $normalized['date'])) {
            return new WP_Error('beautycore_staff_unavailable', 'Nhân viên đã đăng ký nghỉ trong ngày này.');
        }
    }

    return true;
}
