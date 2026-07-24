<?php
/**
 * Beauty Core appointment management.
 *
 * Appointments live in a private post type so the module works without an
 * additional booking plugin. The public booking form and external booking
 * integrations can use beautycore_create_appointment() and the accompanying
 * filters/actions.
 */

if (!defined('ABSPATH')) {
    exit;
}

define('BEAUTYCORE_APPOINTMENT_VERSION', '1.0.0');
define('BEAUTYCORE_APPOINTMENT_POST_TYPE', 'beautycore_booking');

function beautycore_appointment_statuses() {
    return array(
        'pending'     => 'Chờ xác nhận',
        'confirmed'   => 'Đã xác nhận',
        'checked-in'  => 'Khách đã đến',
        'in-progress' => 'Đang thực hiện',
        'completed'   => 'Hoàn tất',
        'cancelled'   => 'Đã hủy',
        'no-show'     => 'Khách không đến',
    );
}

function beautycore_appointment_active_statuses() {
    return array('pending', 'confirmed', 'checked-in', 'in-progress');
}

function beautycore_register_appointment_content_type() {
    register_post_type(BEAUTYCORE_APPOINTMENT_POST_TYPE, array(
        'labels' => array(
            'name'          => 'Lịch hẹn',
            'singular_name' => 'Lịch hẹn',
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
add_action('init', 'beautycore_register_appointment_content_type', 2);

function beautycore_appointment_settings() {
    $defaults = array(
        'opening_time'   => '09:00',
        'closing_time'   => '21:00',
        'buffer_minutes' => 0,
        'reminder_hours' => 24,
        'workdays'       => array('1', '2', '3', '4', '5', '6'),
    );
    $settings = get_option('beautycore_appointment_settings', array());
    $settings = is_array($settings) ? wp_parse_args($settings, $defaults) : $defaults;
    $settings['workdays'] = array_values(array_intersect(array('0', '1', '2', '3', '4', '5', '6'), (array) $settings['workdays']));

    return apply_filters('beautycore_appointment_settings', $settings);
}

function beautycore_appointment_staff_options() {
    if (function_exists('beautycore_get_service_staff_options')) {
        $staff = (array) beautycore_get_service_staff_options();
    } else {
        $staff = array();
        $users = get_users(array(
            'role__in' => array('owner', 'manager', 'receptionist', 'staff'),
            'orderby'  => 'display_name',
            'order'    => 'ASC',
            'fields'   => array('ID', 'display_name', 'user_login'),
        ));
        foreach ($users as $user) {
            $staff[(string) $user->ID] = $user->display_name ?: $user->user_login;
        }
    }

    return apply_filters('beautycore_appointment_staff_options', $staff);
}

function beautycore_appointment_branch_options() {
    if (function_exists('beautycore_get_service_branch_options')) {
        $branches = (array) beautycore_get_service_branch_options();
    } else {
        $branches = array();
    }

    return (array) apply_filters('beautycore_appointment_branch_options', $branches);
}

function beautycore_appointment_service_options() {
    $services = array();
    if (post_type_exists('beautycore_service')) {
        foreach (get_posts(array(
            'post_type'      => 'beautycore_service',
            'post_status'    => array('publish', 'hidden'),
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        )) as $service) {
            $duration = absint(get_post_meta($service->ID, '_beautycore_duration', true));
            $price = get_post_meta($service->ID, '_beautycore_price_sale', true);
            if ((float) $price <= 0) {
                $price = get_post_meta($service->ID, '_beautycore_price_original', true);
            }
            $services[(string) $service->ID] = array(
                'name'     => $service->post_title,
                'duration' => $duration,
                'price'    => (float) $price,
            );
        }
    }

    return apply_filters('beautycore_appointment_service_options', $services);
}

function beautycore_appointment_meta($post_id, $keys, $default = '') {
    foreach ((array) $keys as $key) {
        $value = get_post_meta($post_id, $key, true);
        if ($value !== '' && $value !== null) {
            return $value;
        }
    }

    return $default;
}

function beautycore_appointment_datetime($date, $time = '00:00') {
    $date = sanitize_text_field((string) $date);
    $time = sanitize_text_field((string) $time);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $time)) {
        return false;
    }

    $datetime = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time, wp_timezone());
    $errors = DateTimeImmutable::getLastErrors();
    if (!$datetime || (is_array($errors) && ($errors['warning_count'] || $errors['error_count']))) {
        return false;
    }

    return $datetime;
}

function beautycore_appointment_data($post) {
    $post = $post instanceof WP_Post ? $post : get_post($post);
    if (!$post) {
        return array();
    }

    $start = beautycore_appointment_meta($post->ID, array(
        '_beautycore_appointment_start',
        '_beautycore_appointment_datetime',
        '_beautycore_booking_datetime',
    ));
    $end = beautycore_appointment_meta($post->ID, array('_beautycore_appointment_end'), '');
    $start_datetime = $start ? beautycore_appointment_datetime(substr((string) $start, 0, 10), substr((string) $start, 11, 5)) : false;
    $end_datetime = $end ? beautycore_appointment_datetime(substr((string) $end, 0, 10), substr((string) $end, 11, 5)) : false;
    $status = sanitize_key((string) beautycore_appointment_meta($post->ID, array(
        '_beautycore_appointment_status',
        '_beautycore_booking_status',
        'appointment_status',
        'booking_status',
    ), 'pending'));
    $statuses = beautycore_appointment_statuses();
    if (!isset($statuses[$status])) {
        $status = 'pending';
    }

    $staff_id = beautycore_appointment_meta($post->ID, array('_beautycore_staff_id', '_beautycore_employee_id', 'staff_id'), 0);
    $branch_id = beautycore_appointment_meta($post->ID, array('_beautycore_branch_id', 'branch_id'), '');
    $service_id = beautycore_appointment_meta($post->ID, array('_beautycore_service_id', 'service_id'), 0);

    return array(
        'id'             => (int) $post->ID,
        'code'           => (string) beautycore_appointment_meta($post->ID, array('_beautycore_appointment_code'), 'BC-' . $post->ID),
        'start'          => (string) $start,
        'end'            => (string) $end,
        'timestamp'      => $start_datetime ? $start_datetime->getTimestamp() : 0,
        'end_timestamp'  => $end_datetime ? $end_datetime->getTimestamp() : 0,
        'status'         => $status,
        'customer_name'  => (string) beautycore_appointment_meta($post->ID, array('_beautycore_customer_name', 'customer_name', 'customer'), $post->post_title),
        'customer_phone' => (string) beautycore_appointment_meta($post->ID, array('_beautycore_customer_phone', 'customer_phone', 'phone'), ''),
        'customer_email' => (string) beautycore_appointment_meta($post->ID, array('_beautycore_customer_email', 'customer_email', 'email'), ''),
        'service_id'     => absint($service_id),
        'service_name'   => (string) beautycore_appointment_meta($post->ID, array('_beautycore_service_name', 'service_name', 'service'), 'Chưa chọn dịch vụ'),
        'duration'       => absint(beautycore_appointment_meta($post->ID, array('_beautycore_duration', 'duration'), 0)),
        'price'          => (float) beautycore_appointment_meta($post->ID, array('_beautycore_price', 'price', 'amount'), 0),
        'staff_id'       => absint($staff_id),
        'staff_name'     => (string) beautycore_appointment_meta($post->ID, array('_beautycore_staff_name', 'staff_name', 'staff'), ''),
        'branch_id'      => (string) $branch_id,
        'branch_name'    => (string) beautycore_appointment_meta($post->ID, array('_beautycore_branch_name', 'branch_name', 'branch'), ''),
        'source'         => sanitize_key((string) beautycore_appointment_meta($post->ID, array('_beautycore_source', 'source'), 'frontdesk')),
        'notes'          => (string) $post->post_content,
        'created_by'     => absint(get_post_meta($post->ID, '_beautycore_created_by', true)),
        'reminder_sent'  => (bool) get_post_meta($post->ID, '_beautycore_reminder_sent', true),
        'edit_url'       => get_edit_post_link($post->ID, 'display'),
    );
}

function beautycore_appointment_get_all() {
    $appointments = array();
    if (!post_type_exists(BEAUTYCORE_APPOINTMENT_POST_TYPE)) {
        return $appointments;
    }

    foreach (get_posts(array(
        'post_type'      => BEAUTYCORE_APPOINTMENT_POST_TYPE,
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value',
        'meta_key'       => '_beautycore_appointment_start',
        'order'          => 'DESC',
    )) as $post) {
        $appointments[] = beautycore_appointment_data($post);
    }

    return apply_filters('beautycore_appointments', $appointments);
}

function beautycore_appointment_is_cancelled($status) {
    return in_array(sanitize_key((string) $status), array('cancelled', 'no-show'), true);
}

function beautycore_appointment_is_within_working_hours($start, $end, $settings = null) {
    $settings = $settings ?: beautycore_appointment_settings();
    $day = $start->format('w');
    if (!in_array((string) $day, (array) $settings['workdays'], true)) {
        return new WP_Error('beautycore_closed_day', 'Chi nhánh không làm việc vào ngày đã chọn.');
    }

    $opening = beautycore_appointment_datetime($start->format('Y-m-d'), $settings['opening_time']);
    $closing = beautycore_appointment_datetime($start->format('Y-m-d'), $settings['closing_time']);
    if (!$opening || !$closing || $start < $opening || $end > $closing) {
        return new WP_Error('beautycore_outside_hours', sprintf('Lịch hẹn phải nằm trong giờ làm việc %s–%s.', $settings['opening_time'], $settings['closing_time']));
    }

    return true;
}

function beautycore_appointment_staff_is_unavailable($staff_id, $date) {
    if (!$staff_id) {
        return false;
    }

    $days_off = get_user_meta($staff_id, '_beautycore_staff_days_off', true);
    if (is_string($days_off)) {
        $days_off = preg_split('/[,\s]+/', $days_off, -1, PREG_SPLIT_NO_EMPTY);
    }
    if (is_array($days_off) && in_array($date, array_map('sanitize_text_field', $days_off), true)) {
        return true;
    }

    return (bool) apply_filters('beautycore_appointment_staff_unavailable', false, $staff_id, $date);
}

function beautycore_appointment_conflict($start, $end, $staff_id, $branch_id, $exclude_id = 0) {
    $settings = beautycore_appointment_settings();
    $buffer = max(0, absint($settings['buffer_minutes'])) * 60;
    $start_timestamp = $start->getTimestamp() - $buffer;
    $end_timestamp = $end->getTimestamp() + $buffer;

    foreach (beautycore_appointment_get_all() as $appointment) {
        if ((int) $appointment['id'] === (int) $exclude_id || !in_array($appointment['status'], beautycore_appointment_active_statuses(), true)) {
            continue;
        }
        if (!$appointment['timestamp'] || !$appointment['end_timestamp']) {
            continue;
        }

        $same_resource = $staff_id && $appointment['staff_id'] === (int) $staff_id;
        if (!$same_resource && !$staff_id && $branch_id && (string) $appointment['branch_id'] === (string) $branch_id && !$appointment['staff_id']) {
            $same_resource = true;
        }
        if ($same_resource && $start_timestamp < $appointment['end_timestamp'] + $buffer && $end_timestamp > $appointment['timestamp'] - $buffer) {
            return $appointment;
        }
    }

    return false;
}

function beautycore_appointment_normalize_data($data, $existing = array()) {
    $data = is_array($data) ? $data : array();
    $date = isset($data['date']) ? sanitize_text_field(wp_unslash($data['date'])) : ($existing['start'] ? substr($existing['start'], 0, 10) : '');
    $start_time = isset($data['start_time']) ? sanitize_text_field(wp_unslash($data['start_time'])) : ($existing['start'] ? substr($existing['start'], 11, 5) : '');
    $start = beautycore_appointment_datetime($date, $start_time);
    if (!$start) {
        return new WP_Error('beautycore_invalid_start', 'Ngày và giờ bắt đầu không hợp lệ.');
    }

    $service_id = isset($data['service_id']) ? absint($data['service_id']) : (isset($existing['service_id']) ? $existing['service_id'] : 0);
    $services = beautycore_appointment_service_options();
    $service = $service_id && isset($services[(string) $service_id]) ? $services[(string) $service_id] : array();
    $duration = isset($data['duration']) && $data['duration'] !== '' ? absint($data['duration']) : (isset($existing['duration']) ? $existing['duration'] : 0);
    if (!$duration && !empty($service['duration'])) {
        $duration = absint($service['duration']);
    }
    if ($duration <= 0) {
        return new WP_Error('beautycore_invalid_duration', 'Thời lượng dịch vụ phải lớn hơn 0 phút.');
    }

    $end_time = isset($data['end_time']) ? sanitize_text_field(wp_unslash($data['end_time'])) : '';
    $end = $end_time ? beautycore_appointment_datetime($date, $end_time) : $start->modify('+' . $duration . ' minutes');
    if (!$end || $end <= $start) {
        return new WP_Error('beautycore_invalid_end', 'Giờ kết thúc phải sau giờ bắt đầu.');
    }

    $status = isset($data['status']) ? sanitize_key(wp_unslash($data['status'])) : (isset($existing['status']) ? $existing['status'] : 'pending');
    if (!isset(beautycore_appointment_statuses()[$status])) {
        return new WP_Error('beautycore_invalid_status', 'Trạng thái lịch hẹn không hợp lệ.');
    }
    $staff_id = isset($data['staff_id']) ? absint($data['staff_id']) : (isset($existing['staff_id']) ? $existing['staff_id'] : 0);
    $branch_id = isset($data['branch_id']) ? sanitize_text_field(wp_unslash($data['branch_id'])) : (isset($existing['branch_id']) ? $existing['branch_id'] : '');
    $branches = beautycore_appointment_branch_options();
    $staff = beautycore_appointment_staff_options();
    if ($staff_id && !isset($staff[(string) $staff_id])) {
        return new WP_Error('beautycore_invalid_staff', 'Nhân viên không hợp lệ.');
    }
    if ($branch_id && !isset($branches[(string) $branch_id])) {
        return new WP_Error('beautycore_invalid_branch', 'Chi nhánh không hợp lệ.');
    }

    return array(
        'date'           => $date,
        'start'          => $start,
        'end'            => $end,
        'status'         => $status,
        'customer_name'  => isset($data['customer_name']) ? sanitize_text_field(wp_unslash($data['customer_name'])) : (isset($existing['customer_name']) ? $existing['customer_name'] : ''),
        'customer_phone' => isset($data['customer_phone']) ? sanitize_text_field(wp_unslash($data['customer_phone'])) : (isset($existing['customer_phone']) ? $existing['customer_phone'] : ''),
        'customer_email' => isset($data['customer_email']) ? sanitize_email(wp_unslash($data['customer_email'])) : (isset($existing['customer_email']) ? $existing['customer_email'] : ''),
        'service_id'     => $service_id,
        'service_name'   => $service ? $service['name'] : (isset($existing['service_name']) ? $existing['service_name'] : ''),
        'duration'       => $duration,
        'price'          => isset($data['price']) && $data['price'] !== '' ? (float) str_replace(',', '', sanitize_text_field(wp_unslash($data['price']))) : ($service ? $service['price'] : (isset($existing['price']) ? $existing['price'] : 0)),
        'staff_id'       => $staff_id,
        'staff_name'     => $staff_id ? $staff[(string) $staff_id] : '',
        'branch_id'      => $branch_id,
        'branch_name'    => $branch_id && isset($branches[(string) $branch_id]) ? $branches[(string) $branch_id] : '',
        'source'         => isset($data['source']) ? sanitize_key(wp_unslash($data['source'])) : (isset($existing['source']) ? $existing['source'] : 'frontdesk'),
        'notes'          => isset($data['notes']) ? sanitize_textarea_field(wp_unslash($data['notes'])) : (isset($existing['notes']) ? $existing['notes'] : ''),
    );
}

function beautycore_appointment_record_history($appointment_id, $action, $context = array()) {
    $history = get_post_meta($appointment_id, '_beautycore_appointment_history', true);
    $history = is_array($history) ? $history : array();
    $user = wp_get_current_user();
    array_unshift($history, array(
        'timestamp'  => current_time('mysql'),
        'user_id'    => $user ? (int) $user->ID : 0,
        'user_login' => $user && $user->exists() ? $user->user_login : 'system',
        'action'     => sanitize_key($action),
        'context'    => is_array($context) ? $context : array(),
    ));
    update_post_meta($appointment_id, '_beautycore_appointment_history', array_slice($history, 0, 100));
}

function beautycore_create_appointment($data, $source = 'website', $appointment_id = 0) {
    $existing_post = $appointment_id ? get_post($appointment_id) : null;
    if ($appointment_id && (!$existing_post || $existing_post->post_type !== BEAUTYCORE_APPOINTMENT_POST_TYPE)) {
        return new WP_Error('beautycore_invalid_appointment', 'Lịch hẹn không tồn tại.');
    }
    $existing = $existing_post ? beautycore_appointment_data($existing_post) : array();
    if ($source) {
        $data['source'] = $source;
    }
    $normalized = beautycore_appointment_normalize_data($data, $existing);
    if (is_wp_error($normalized)) {
        return $normalized;
    }
    if (!$normalized['customer_name'] || !$normalized['customer_phone']) {
        return new WP_Error('beautycore_customer_required', 'Vui lòng nhập tên và số điện thoại khách hàng.');
    }
    if ($normalized['customer_email'] && !is_email($normalized['customer_email'])) {
        return new WP_Error('beautycore_invalid_email', 'Email khách hàng không hợp lệ.');
    }

    $within_hours = beautycore_appointment_is_within_working_hours($normalized['start'], $normalized['end']);
    if (is_wp_error($within_hours)) {
        return $within_hours;
    }
    if (beautycore_appointment_staff_is_unavailable($normalized['staff_id'], $normalized['date'])) {
        return new WP_Error('beautycore_staff_unavailable', 'Nhân viên đã đăng ký nghỉ trong ngày này.');
    }
    if (in_array($normalized['status'], beautycore_appointment_active_statuses(), true)) {
        $conflict = beautycore_appointment_conflict($normalized['start'], $normalized['end'], $normalized['staff_id'], $normalized['branch_id'], $appointment_id);
        if ($conflict) {
            return new WP_Error('beautycore_appointment_conflict', sprintf('Khung giờ bị trùng với lịch %s của %s (%s).', $conflict['code'], $conflict['customer_name'], beautycore_format_appointment_time($conflict['timestamp'])));
        }
    }

    $was_update = (bool) $appointment_id;
    $old_start = $existing ? $existing['start'] : '';
    $old_staff = $existing ? (int) $existing['staff_id'] : 0;
    $post_id = wp_insert_post(wp_slash(array(
        'ID'           => $appointment_id,
        'post_type'    => BEAUTYCORE_APPOINTMENT_POST_TYPE,
        'post_status'  => 'publish',
        'post_title'   => $normalized['customer_name'],
        'post_content' => $normalized['notes'],
    )), true);
    if (is_wp_error($post_id)) {
        return $post_id;
    }

    $code = $was_update ? (string) $existing['code'] : 'BC-' . $normalized['start']->format('Ymd') . '-' . str_pad((string) $post_id, 5, '0', STR_PAD_LEFT);
    $meta = array(
        '_beautycore_appointment_code'   => $code,
        '_beautycore_appointment_start'  => $normalized['start']->format('Y-m-d H:i:s'),
        '_beautycore_appointment_end'    => $normalized['end']->format('Y-m-d H:i:s'),
        '_beautycore_appointment_status' => $normalized['status'],
        '_beautycore_customer_name'      => $normalized['customer_name'],
        '_beautycore_customer_phone'     => $normalized['customer_phone'],
        '_beautycore_customer_email'     => $normalized['customer_email'],
        '_beautycore_service_id'         => $normalized['service_id'],
        '_beautycore_service_name'       => $normalized['service_name'],
        '_beautycore_duration'            => $normalized['duration'],
        '_beautycore_price'               => $normalized['price'],
        '_beautycore_staff_id'            => $normalized['staff_id'],
        '_beautycore_staff_name'          => $normalized['staff_name'],
        '_beautycore_branch_id'           => $normalized['branch_id'],
        '_beautycore_branch_name'         => $normalized['branch_name'],
        '_beautycore_source'              => $normalized['source'],
    );
    if (!$was_update) {
        $meta['_beautycore_created_by'] = get_current_user_id();
        $meta['_beautycore_reminder_sent'] = 0;
    }
    foreach ($meta as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }

    $history_action = $was_update && ($old_start !== $meta['_beautycore_appointment_start'] || $old_staff !== $normalized['staff_id']) ? 'rescheduled' : ($was_update ? 'updated' : 'created');
    beautycore_appointment_record_history($post_id, $history_action, array(
        'status' => $normalized['status'],
        'start'  => $meta['_beautycore_appointment_start'],
        'end'    => $meta['_beautycore_appointment_end'],
        'source' => $normalized['source'],
    ));
    if (function_exists('beautycore_audit_log')) {
        beautycore_audit_log('appointment_' . $history_action, array(
            'code'     => $code,
            'customer' => $normalized['customer_name'],
            'status'   => $normalized['status'],
            'source'   => $normalized['source'],
        ), 'beautycore_appointment', $post_id);
    }
    do_action('beautycore_appointment_saved', $post_id, $was_update, $normalized);
    if (!$was_update || $history_action === 'rescheduled') {
        beautycore_appointment_send_notification($post_id, $was_update ? 'rescheduled' : 'created');
    }

    return $post_id;
}

function beautycore_appointment_send_notification($appointment_id, $event = 'created') {
    $appointment = beautycore_appointment_data($appointment_id);
    if (!$appointment || beautycore_appointment_is_cancelled($appointment['status'])) {
        return false;
    }

    do_action('beautycore_appointment_notify', $appointment_id, $event, $appointment);
    if (!$appointment['customer_email']) {
        return false;
    }
    $subject = sprintf('[Beauty Core] Lịch hẹn %s', $appointment['code']);
    $message = sprintf(
        "Xin chào %s,\n\nBeauty Core đã ghi nhận lịch hẹn của bạn:\n- Mã: %s\n- Thời gian: %s\n- Dịch vụ: %s\n- Nhân viên: %s\n- Chi nhánh: %s\n\nNếu cần thay đổi hoặc hủy lịch, vui lòng liên hệ trực tiếp với Beauty Core.",
        $appointment['customer_name'],
        $appointment['code'],
        beautycore_format_appointment_time($appointment['timestamp']),
        $appointment['service_name'],
        $appointment['staff_name'] ?: 'Chưa phân công',
        $appointment['branch_name'] ?: 'Chưa chọn'
    );

    return wp_mail($appointment['customer_email'], $subject, $message);
}

function beautycore_appointment_reminders_cron() {
    $settings = beautycore_appointment_settings();
    $now = time();
    $limit = $now + max(1, absint($settings['reminder_hours'])) * HOUR_IN_SECONDS;
    foreach (beautycore_appointment_get_all() as $appointment) {
        if ($appointment['reminder_sent'] || !in_array($appointment['status'], array('pending', 'confirmed'), true) || !$appointment['timestamp'] || $appointment['timestamp'] < $now || $appointment['timestamp'] > $limit || !$appointment['customer_email']) {
            continue;
        }
        if (beautycore_appointment_send_notification($appointment['id'], 'reminder')) {
            update_post_meta($appointment['id'], '_beautycore_reminder_sent', 1);
            beautycore_appointment_record_history($appointment['id'], 'reminder_sent', array('hours_before' => $settings['reminder_hours']));
        }
    }
}
add_action('beautycore_appointment_reminders', 'beautycore_appointment_reminders_cron');

function beautycore_appointment_schedule_cron() {
    if (!wp_next_scheduled('beautycore_appointment_reminders')) {
        wp_schedule_event(time() + 300, 'hourly', 'beautycore_appointment_reminders');
    }
}
add_action('init', 'beautycore_appointment_schedule_cron', 20);
add_action('after_switch_theme', 'beautycore_appointment_schedule_cron');

function beautycore_appointment_relative_url($url) {
    return function_exists('wp_make_link_relative') ? wp_make_link_relative($url) : $url;
}

function beautycore_appointment_admin_assets($hook) {
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    if ($page !== 'beautycore-appointments' && $page !== 'beautycore-appointment-edit') {
        return;
    }
    wp_enqueue_script(
        'beautycore-appointment-admin',
        get_theme_file_uri('/assets/js/admin-appointment.js'),
        array(),
        BEAUTYCORE_ADMIN_VERSION,
        true
    );
    wp_localize_script('beautycore-appointment-admin', 'BEAUTYCORE_APPOINTMENT_ADMIN', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('beautycore_appointment_modal'),
    ));
}
add_action('admin_enqueue_scripts', 'beautycore_appointment_admin_assets');

function beautycore_appointment_notice() {
    if (!empty($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Lịch hẹn đã được lưu.</p></div>';
    } elseif (!empty($_GET['status_updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Trạng thái lịch hẹn đã được cập nhật.</p></div>';
    } elseif (!empty($_GET['error'])) {
        echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash($_GET['error'])) . '</p></div>';
    }
}

function beautycore_appointment_action_url($id, $status) {
    return wp_nonce_url(beautycore_appointment_relative_url(admin_url('admin-post.php?action=beautycore_appointment_status&id=' . absint($id) . '&status=' . rawurlencode($status))), 'beautycore_appointment_status_' . absint($id));
}

function beautycore_appointment_filter_records($appointments) {
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $date = isset($_GET['filter_date']) ? sanitize_text_field(wp_unslash($_GET['filter_date'])) : '';
    if ($date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $date = '';
    }
    $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all';
    if ($status !== 'all' && !isset(beautycore_appointment_statuses()[$status])) {
        $status = 'all';
    }
    $staff_id = isset($_GET['staff_id']) ? absint($_GET['staff_id']) : 0;
    $branch_id = isset($_GET['branch_id']) ? sanitize_text_field(wp_unslash($_GET['branch_id'])) : '';
    if (!empty($_GET['updated'])) {
        $search = '';
        $date = '';
        $status = 'all';
        $staff_id = 0;
        $branch_id = '';
    }
    if (current_user_can('view_beautycore_own_schedule') && !current_user_can('manage_beautycore_appointments')) {
        $staff_id = get_current_user_id();
    }

    return array_values(array_filter($appointments, function ($appointment) use ($search, $date, $status, $staff_id, $branch_id) {
        if ($search && stripos($appointment['code'] . ' ' . $appointment['customer_name'] . ' ' . $appointment['customer_phone'], $search) === false) {
            return false;
        }
        if ($date && strpos($appointment['start'], $date) !== 0) {
            return false;
        }
        if ($status !== 'all' && $appointment['status'] !== $status) {
            return false;
        }
        if ($staff_id && $appointment['staff_id'] !== $staff_id) {
            return false;
        }
        if ($branch_id !== '' && (string) $appointment['branch_id'] !== (string) $branch_id) {
            return false;
        }
        return true;
    }));
}

function beautycore_appointment_render_filters() {
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all';
    $date = isset($_GET['filter_date']) ? sanitize_text_field(wp_unslash($_GET['filter_date'])) : '';
    $staff_id = isset($_GET['staff_id']) ? absint($_GET['staff_id']) : 0;
    $branch_id = isset($_GET['branch_id']) ? sanitize_text_field(wp_unslash($_GET['branch_id'])) : '';
    if (!empty($_GET['updated'])) {
        $search = '';
        $status = 'all';
        $date = '';
        $staff_id = 0;
        $branch_id = '';
    }
    $statuses = beautycore_appointment_statuses();
    echo '<form method="get" class="beautycore-appointment-filters" data-beautycore-auto-filter><input type="hidden" name="page" value="beautycore-appointments"><input type="search" name="s" value="' . esc_attr($search) . '" placeholder="Mã hoặc số điện thoại..." aria-label="Tìm lịch hẹn"><input type="date" name="filter_date" value="' . esc_attr($date) . '" aria-label="Lọc theo ngày"><select name="status"><option value="all">Tất cả trạng thái</option>';
    foreach ($statuses as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($status, $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select><select name="staff_id"><option value="0">Tất cả nhân viên</option>';
    foreach (beautycore_appointment_staff_options() as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($staff_id, absint($key), false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select><select name="branch_id"><option value="" ' . selected($branch_id, '', false) . '>Tất cả chi nhánh</option>';
    foreach (beautycore_appointment_branch_options() as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($branch_id, (string) $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select><noscript><button type="submit" class="button button-secondary">Lọc</button></noscript>';
    if ($search || $date || $status !== 'all' || $staff_id || $branch_id !== '') {
        echo ' <a class="button" href="' . esc_url(beautycore_appointment_relative_url(admin_url('admin.php?page=beautycore-appointments'))) . '">Xóa bộ lọc</a>';
    }
    echo '</form>';
}

function beautycore_appointment_render_list($appointments) {
    if (!$appointments) {
        echo '<div class="beautycore-empty-state"><strong>Chưa có lịch hẹn</strong><span>Hãy tạo lịch tại quầy hoặc kết nối form website để nhận lịch online.</span></div>';
        return;
    }
    $statuses = beautycore_appointment_statuses();
    echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table beautycore-service-table beautycore-appointment-table"><thead><tr><th>Mã lịch</th><th>Thời gian</th><th>Khách hàng</th><th>Dịch vụ</th><th>Nhân viên</th><th>Chi nhánh</th><th>Nguồn</th><th>Trạng thái</th><th></th></tr></thead><tbody>';
    foreach ($appointments as $appointment) {
        $edit_url = beautycore_appointment_relative_url(admin_url('admin.php?page=beautycore-appointment-edit&id=' . $appointment['id']));
        $actions = array();
        if (current_user_can('manage_beautycore_appointments')) {
            if ($appointment['status'] === 'pending') {
                $actions['confirmed'] = 'Xác nhận';
            }
            if (in_array($appointment['status'], array('confirmed', 'pending'), true)) {
                $actions['checked-in'] = 'Khách đã đến';
            }
            if ($appointment['status'] === 'checked-in') {
                $actions['in-progress'] = 'Bắt đầu';
            }
            if ($appointment['status'] === 'in-progress') {
                $actions['completed'] = 'Hoàn tất';
            }
            if (in_array($appointment['status'], array('pending', 'confirmed'), true)) {
                $actions['no-show'] = 'Khách không đến';
                $actions['cancelled'] = 'Hủy';
            }
        }

        echo '<tr><td><strong><a class="beautycore-edit-appointment" data-appointment-id="' . esc_attr($appointment['id']) . '" href="' . esc_url($edit_url) . '">' . esc_html($appointment['code']) . '</a></strong><div class="row-actions"><span><a class="beautycore-edit-appointment" data-appointment-id="' . esc_attr($appointment['id']) . '" href="' . esc_url($edit_url) . '">Sửa</a></span>';
        foreach ($actions as $next_status => $label) {
            echo ' | <span><a href="' . esc_url(beautycore_appointment_action_url($appointment['id'], $next_status)) . '">' . esc_html($label) . '</a></span>';
        }
        echo '</div></td>';
        echo '<td>' . esc_html(beautycore_format_appointment_time($appointment['timestamp'], $appointment['start'])) . '<br><small>' . esc_html($appointment['duration'] . ' phút') . '</small></td>';
        echo '<td><strong>' . esc_html($appointment['customer_name']) . '</strong><br><small>' . esc_html($appointment['customer_phone']) . '</small></td>';
        echo '<td>' . esc_html($appointment['service_name']) . '</td><td>' . esc_html($appointment['staff_name'] ?: 'Chưa phân công') . '</td><td>' . esc_html($appointment['branch_name'] ?: '—') . '</td>';
        echo '<td><span class="beautycore-source beautycore-source-' . esc_attr($appointment['source']) . '">' . esc_html($appointment['source'] === 'website' ? 'Website' : 'Tại quầy') . '</span></td>';
        echo '<td><span class="beautycore-status beautycore-status-' . esc_attr($appointment['status']) . '">' . esc_html($statuses[$appointment['status']]) . '</span></td>';
        echo '<td><a class="button button-small beautycore-edit-appointment" data-appointment-id="' . esc_attr($appointment['id']) . '" href="' . esc_url($edit_url) . '">Mở</a></td></tr>';
    }
    echo '</tbody></table></div>';
}

function beautycore_appointment_calendar_event_class($appointment, $color_by, $staff_index) {
    if ($color_by === 'staff') {
        $staff_number = isset($staff_index[$appointment['staff_id']]) ? $staff_index[$appointment['staff_id']] : 0;
        return 'beautycore-calendar-event--staff-' . (absint($staff_number) % 8);
    }
    return 'beautycore-calendar-event--status-' . sanitize_html_class($appointment['status']);
}

function beautycore_appointment_render_event($appointment, $color_by, $staff_index) {
    $edit_url = beautycore_appointment_relative_url(admin_url('admin.php?page=beautycore-appointment-edit&id=' . $appointment['id']));
    echo '<a class="beautycore-edit-appointment beautycore-calendar-event ' . esc_attr(beautycore_appointment_calendar_event_class($appointment, $color_by, $staff_index)) . '" data-appointment-id="' . esc_attr($appointment['id']) . '" href="' . esc_url($edit_url) . '"><strong>' . esc_html(wp_date('H:i', $appointment['timestamp'], wp_timezone())) . '</strong> ' . esc_html($appointment['customer_name']) . '<small>' . esc_html($appointment['service_name']) . '</small></a>';
}

function beautycore_appointment_render_calendar($appointments, $view, $date, $color_by) {
    $selected = beautycore_appointment_datetime($date, '00:00') ?: new DateTimeImmutable('now', wp_timezone());
    $staff_options = beautycore_appointment_staff_options();
    $staff_index = array();
    $index = 0;
    foreach ($staff_options as $key => $label) {
        $staff_index[(int) $key] = $index++;
    }
    if ($view === 'day') {
        $period_start = $selected->setTime(0, 0);
        $period_end = $period_start->modify('+1 day');
        $days = array($period_start);
        $previous = $period_start->modify('-1 day');
        $next = $period_start->modify('+1 day');
    } elseif ($view === 'week') {
        $period_start = $selected->modify('-' . (int) $selected->format('w') . ' days')->setTime(0, 0);
        $period_end = $period_start->modify('+7 days');
        $days = array();
        for ($i = 0; $i < 7; $i++) {
            $days[] = $period_start->modify('+' . $i . ' days');
        }
        $previous = $period_start->modify('-7 days');
        $next = $period_start->modify('+7 days');
    } else {
        $period_start = $selected->modify('first day of this month')->setTime(0, 0);
        $period_end = $period_start->modify('+1 month');
        $grid_start = $period_start->modify('-' . (int) $period_start->format('w') . ' days');
        $last_day = $period_end->modify('-1 day');
        $grid_end = $last_day->modify('+' . (6 - (int) $last_day->format('w')) . ' days')->modify('+1 day');
        $days = array();
        for ($cursor = $grid_start; $cursor < $grid_end; $cursor = $cursor->modify('+1 day')) {
            $days[] = $cursor;
        }
        $previous = $period_start->modify('-1 month');
        $next = $period_start->modify('+1 month');
    }

    $by_day = array();
    foreach ($appointments as $appointment) {
        if ($appointment['timestamp'] < $period_start->getTimestamp() || $appointment['timestamp'] >= $period_end->getTimestamp()) {
            continue;
        }
        $key = wp_date('Y-m-d', $appointment['timestamp'], wp_timezone());
        $by_day[$key][] = $appointment;
    }
    $base_url = beautycore_appointment_relative_url(admin_url('admin.php?page=beautycore-appointments&view=' . rawurlencode($view)));
    echo '<div class="beautycore-calendar-toolbar"><div class="beautycore-calendar-nav"><a class="button" href="' . esc_url(add_query_arg('appointment_date', $previous->format('Y-m-d'), $base_url)) . '">‹ Trước</a> <a class="button" href="' . esc_url(add_query_arg('appointment_date', current_time('Y-m-d'), $base_url)) . '">Hôm nay</a> <a class="button" href="' . esc_url(add_query_arg('appointment_date', $next->format('Y-m-d'), $base_url)) . '">Sau ›</a></div><strong>' . esc_html($view === 'month' ? wp_date('m/Y', $selected->getTimestamp(), wp_timezone()) : wp_date('d/m/Y', $selected->getTimestamp(), wp_timezone())) . '</strong><label>Màu theo <select onchange="window.location.href=this.value"><option value="' . esc_url(add_query_arg(array('appointment_date' => $date, 'color_by' => 'status'), $base_url)) . '" ' . selected($color_by, 'status', false) . '>trạng thái</option><option value="' . esc_url(add_query_arg(array('appointment_date' => $date, 'color_by' => 'staff'), $base_url)) . '" ' . selected($color_by, 'staff', false) . '>nhân viên</option></select></label></div>';
    echo '<div class="beautycore-calendar beautycore-calendar-' . esc_attr($view) . '">';
    if ($view === 'month') {
        foreach (array('CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7') as $weekday) {
            echo '<div class="beautycore-calendar-heading">' . esc_html($weekday) . '</div>';
        }
    }
    foreach ($days as $day) {
        $day_key = $day->format('Y-m-d');
        $muted = $view === 'month' && $day->format('m') !== $selected->format('m');
        echo '<section class="beautycore-calendar-day ' . ($muted ? 'is-muted' : '') . '"><h3>' . esc_html($view === 'month' ? $day->format('j') : wp_date('D d/m', $day->getTimestamp(), wp_timezone())) . '</h3><div class="beautycore-calendar-events">';
        foreach (isset($by_day[$day_key]) ? $by_day[$day_key] : array() as $appointment) {
            beautycore_appointment_render_event($appointment, $color_by, $staff_index);
        }
        echo '</div></section>';
    }
    echo '</div><div class="beautycore-calendar-legend"><span>Trạng thái:</span>';
    foreach (beautycore_appointment_statuses() as $key => $label) {
        echo '<span><i class="beautycore-calendar-dot beautycore-calendar-dot--status-' . esc_attr($key) . '"></i>' . esc_html($label) . '</span>';
    }
    echo '</div>';
}

function beautycore_render_appointment_admin_page() {
    if (!current_user_can('view_beautycore_schedule')) {
        wp_die('Bạn không có quyền xem lịch hẹn.');
    }
    // Keep the modal available even when a custom admin router provides an
    // unexpected hook suffix and admin_enqueue_scripts misses this screen.
    beautycore_appointment_admin_assets('');
    beautycore_appointment_notice();
    echo '<div class="beautycore-service-toolbar beautycore-appointment-toolbar"><div>';
    if (current_user_can('manage_beautycore_appointments')) {
        $new_appointment_url = beautycore_appointment_relative_url(admin_url('admin.php?page=beautycore-appointment-edit'));
        echo '<a class="button button-primary beautycore-add-appointment" href="' . esc_url($new_appointment_url) . '">+ Tạo lịch hẹn</a>';
    }
    echo '</div><div class="beautycore-view-switcher">';
    $view = isset($_GET['view']) ? sanitize_key(wp_unslash($_GET['view'])) : 'list';
    if (!in_array($view, array('list', 'day', 'week', 'month'), true)) {
        $view = 'list';
    }
    $date = isset($_GET['appointment_date']) ? sanitize_text_field(wp_unslash($_GET['appointment_date'])) : current_time('Y-m-d');
    foreach (array('list' => 'Danh sách', 'day' => 'Ngày', 'week' => 'Tuần', 'month' => 'Tháng') as $key => $label) {
        echo '<a class="button ' . ($view === $key ? 'button-primary' : '') . '" href="' . esc_url(add_query_arg(array('page' => 'beautycore-appointments', 'view' => $key, 'appointment_date' => $date), beautycore_appointment_relative_url(admin_url('admin.php')))) . '">' . esc_html($label) . '</a> ';
    }
    echo '</div></div>';
    beautycore_appointment_render_filters();
    $appointments = beautycore_appointment_filter_records(beautycore_appointment_get_all());
    if ($view === 'list') {
        beautycore_appointment_render_list($appointments);
    } else {
        $color_by = isset($_GET['color_by']) && sanitize_key(wp_unslash($_GET['color_by'])) === 'staff' ? 'staff' : 'status';
        beautycore_appointment_render_calendar($appointments, $view, $date, $color_by);
    }
    beautycore_render_appointment_modal();
}

function beautycore_render_appointment_modal() {
    if (!current_user_can('manage_beautycore_appointments')) {
        return;
    }

    echo '<div id="beautycore-appointment-modal" class="beautycore-service-modal" hidden aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="beautycore-appointment-modal-title"><div class="beautycore-service-modal__backdrop" data-beautycore-modal-close></div><div class="beautycore-service-modal__dialog"><div class="beautycore-service-modal__header"><h2 id="beautycore-appointment-modal-title">Tạo lịch hẹn</h2><button type="button" class="button-link beautycore-service-modal__close" data-beautycore-modal-close aria-label="Đóng">&times;</button></div><div id="beautycore-appointment-modal-body" class="beautycore-service-modal__body"><p class="beautycore-modal-loading">Đang chuẩn bị biểu mẫu...</p></div></div></div>';
}

function beautycore_ajax_appointment_form() {
    if (!current_user_can('manage_beautycore_appointments')) {
        wp_send_json_error(array('message' => 'Bạn không có quyền chỉnh sửa lịch hẹn.'), 403);
    }
    check_ajax_referer('beautycore_appointment_modal');

    $appointment_id = isset($_GET['appointment_id']) ? absint($_GET['appointment_id']) : 0;
    ob_start();
    beautycore_render_appointment_edit_page($appointment_id, true);
    wp_send_json_success(ob_get_clean());
}
add_action('wp_ajax_beautycore_appointment_form', 'beautycore_ajax_appointment_form');

function beautycore_appointment_render_history($appointment_id) {
    $history = get_post_meta($appointment_id, '_beautycore_appointment_history', true);
    if (!is_array($history) || !$history) {
        return;
    }
    echo '<section class="beautycore-panel"><h2>Lịch sử thao tác</h2><div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table"><thead><tr><th>Thời gian</th><th>Người thao tác</th><th>Thao tác</th><th>Chi tiết</th></tr></thead><tbody>';
    foreach ($history as $item) {
        $context = !empty($item['context']) ? wp_json_encode($item['context'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        echo '<tr><td>' . esc_html(isset($item['timestamp']) ? $item['timestamp'] : '') . '</td><td>' . esc_html(isset($item['user_login']) ? $item['user_login'] : 'system') . '</td><td>' . esc_html(isset($item['action']) ? $item['action'] : '') . '</td><td>' . esc_html($context) . '</td></tr>';
    }
    echo '</tbody></table></div></section>';
}

function beautycore_render_appointment_edit_page($appointment_id_override = null, $fragment = false) {
    if (!current_user_can('manage_beautycore_appointments')) {
        wp_die('Bạn không có quyền quản lý lịch hẹn.');
    }
    if (!$fragment) {
        beautycore_appointment_admin_assets('');
    }
    $id = $appointment_id_override === null ? (isset($_GET['id']) ? absint($_GET['id']) : 0) : absint($appointment_id_override);
    $appointment = $id ? beautycore_appointment_data($id) : array();
    if ($id && !$appointment) {
        wp_die('Không tìm thấy lịch hẹn.');
    }
    $services = beautycore_appointment_service_options();
    $staff = beautycore_appointment_staff_options();
    $branches = beautycore_appointment_branch_options();
    $today = current_time('Y-m-d');
    $start = isset($appointment['start']) && $appointment['start'] ? $appointment['start'] : ($today . ' 09:00:00');
    $source = isset($appointment['source']) ? $appointment['source'] : 'frontdesk';
    if (!$fragment) {
        beautycore_admin_page_header($id ? 'Sửa lịch hẹn ' . $appointment['code'] : 'Tạo lịch hẹn', 'Kiểm tra khung giờ trống trước khi lưu. Lịch online mặc định ở trạng thái Chờ xác nhận.');
        beautycore_appointment_notice();
    }
    echo '<form class="beautycore-appointment-form" method="post" action="' . esc_url(beautycore_appointment_relative_url(admin_url('admin-post.php'))) . '"><input type="hidden" name="action" value="beautycore_save_appointment"><input type="hidden" name="appointment_id" value="' . esc_attr($id) . '">';
    wp_nonce_field('beautycore_save_appointment');
    echo '<div class="beautycore-appointment-form-layout"><div><section class="beautycore-panel"><h2>Khách hàng</h2><div class="beautycore-form-grid"><div class="beautycore-form-field"><label for="customer_name"><strong>Họ tên *</strong></label><input id="customer_name" name="customer_name" required value="' . esc_attr(isset($appointment['customer_name']) ? $appointment['customer_name'] : '') . '"></div><div class="beautycore-form-field"><label for="customer_phone"><strong>Số điện thoại *</strong></label><input id="customer_phone" name="customer_phone" required list="beautycore-customer-phones" value="' . esc_attr(isset($appointment['customer_phone']) ? $appointment['customer_phone'] : '') . '"><datalist id="beautycore-customer-phones">';
    $customers = array();
    foreach (beautycore_appointment_get_all() as $record) {
        if ($record['customer_phone']) {
            $customers[$record['customer_phone']] = $record['customer_name'];
        }
    }
    foreach ($customers as $phone => $name) {
        echo '<option value="' . esc_attr($phone) . '">' . esc_html($name) . '</option>';
    }
    echo '</datalist></div></div><div class="beautycore-form-grid"><div class="beautycore-form-field"><label for="customer_email"><strong>Email</strong></label><input id="customer_email" type="email" name="customer_email" value="' . esc_attr(isset($appointment['customer_email']) ? $appointment['customer_email'] : '') . '"></div><div class="beautycore-form-field"><label for="source"><strong>Nguồn lịch</strong></label><select id="source" name="source"><option value="frontdesk" ' . selected($source, 'frontdesk', false) . '>Tại quầy</option><option value="website" ' . selected($source, 'website', false) . '>Website</option><option value="phone" ' . selected($source, 'phone', false) . '>Điện thoại</option></select></div></div></section>';
    echo '<section class="beautycore-panel"><h2>Thời gian và phân công</h2><div class="beautycore-form-grid"><div class="beautycore-form-field"><label for="appointment_date"><strong>Ngày *</strong></label><input id="appointment_date" type="date" name="date" required value="' . esc_attr(substr($start, 0, 10)) . '"></div><div class="beautycore-form-field"><label for="start_time"><strong>Bắt đầu *</strong></label><input id="start_time" type="time" name="start_time" required value="' . esc_attr(substr($start, 11, 5)) . '"></div><div class="beautycore-form-field"><label for="end_time"><strong>Kết thúc</strong></label><input id="end_time" type="time" name="end_time" value="' . esc_attr(isset($appointment['end']) ? substr($appointment['end'], 11, 5) : '') . '"><p class="description">Để trống để tự tính theo thời lượng dịch vụ.</p></div><div class="beautycore-form-field"><label for="duration"><strong>Thời lượng (phút) *</strong></label><input id="duration" type="number" min="1" name="duration" required value="' . esc_attr(isset($appointment['duration']) ? $appointment['duration'] : '') . '"></div></div><div class="beautycore-form-grid"><div class="beautycore-form-field"><label for="staff_id"><strong>Nhân viên</strong></label><select id="staff_id" name="staff_id"><option value="0">Chưa phân công</option>';
    foreach ($staff as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected((int) (isset($appointment['staff_id']) ? $appointment['staff_id'] : 0), (int) $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></div><div class="beautycore-form-field"><label for="branch_id"><strong>Chi nhánh</strong></label><select id="branch_id" name="branch_id"><option value="">Chưa chọn</option>';
    foreach ($branches as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected((string) (isset($appointment['branch_id']) ? $appointment['branch_id'] : ''), (string) $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select></div></div></section>';
    echo '<section class="beautycore-panel"><h2>Dịch vụ và ghi chú</h2><div class="beautycore-form-grid"><div class="beautycore-form-field"><label for="service_id"><strong>Dịch vụ *</strong></label><select id="service_id" name="service_id" required><option value="0">Chọn dịch vụ</option>';
    foreach ($services as $key => $service) {
        echo '<option value="' . esc_attr($key) . '" data-duration="' . esc_attr($service['duration']) . '" data-price="' . esc_attr($service['price']) . '" ' . selected((int) (isset($appointment['service_id']) ? $appointment['service_id'] : 0), (int) $key, false) . '>' . esc_html($service['name']) . '</option>';
    }
    echo '</select></div><div class="beautycore-form-field"><label for="price"><strong>Giá</strong></label><input id="price" type="number" min="0" step="1000" name="price" value="' . esc_attr(isset($appointment['price']) ? $appointment['price'] : '') . '"></div></div><div class="beautycore-form-field"><label for="notes"><strong>Ghi chú</strong></label><textarea id="notes" name="notes" rows="4">' . esc_textarea(isset($appointment['notes']) ? $appointment['notes'] : '') . '</textarea></div></section></div>';
    echo '<aside><section class="beautycore-panel"><h2>Trạng thái</h2><select class="beautycore-appointment-status-select" name="status">';
    foreach (beautycore_appointment_statuses() as $key => $label) {
        echo '<option value="' . esc_attr($key) . '" ' . selected(isset($appointment['status']) ? $appointment['status'] : 'pending', $key, false) . '>' . esc_html($label) . '</option>';
    }
    echo '</select><p class="description">Chờ xác nhận dùng cho lịch mới từ website. Hệ thống sẽ từ chối lịch bị trùng nhân viên, ngày nghỉ hoặc ngoài giờ làm việc.</p><p><button class="button button-primary" type="submit">Lưu lịch hẹn</button> <a class="button" href="' . esc_url(beautycore_appointment_relative_url(admin_url('admin.php?page=beautycore-appointments'))) . '">Quay lại</a></p></section>';
    if ($id) {
        $created_by = !empty($appointment['created_by']) ? get_userdata($appointment['created_by']) : false;
        echo '<section class="beautycore-panel"><h2>Thông tin hệ thống</h2><dl class="beautycore-definition-list"><dt>Mã lịch</dt><dd>' . esc_html($appointment['code']) . '</dd><dt>Tạo bởi</dt><dd>' . esc_html($created_by ? $created_by->display_name : 'Website') . '</dd><dt>Nhắc lịch</dt><dd>' . esc_html($appointment['reminder_sent'] ? 'Đã gửi' : 'Chưa gửi') . '</dd></dl></section>';
    }
    echo '</aside></div></form>';
    if ($id) {
        beautycore_appointment_render_history($id);
    }
    if (!$fragment) {
        beautycore_admin_page_footer();
    }
}

function beautycore_handle_save_appointment() {
    if (!current_user_can('manage_beautycore_appointments')) {
        wp_die('Bạn không có quyền quản lý lịch hẹn.');
    }
    check_admin_referer('beautycore_save_appointment');
    $id = isset($_POST['appointment_id']) ? absint($_POST['appointment_id']) : 0;
    $result = beautycore_create_appointment($_POST, '', $id);
    if (is_wp_error($result)) {
        $url = beautycore_appointment_relative_url(admin_url('admin.php?page=beautycore-appointment-edit' . ($id ? '&id=' . $id : '')));
        wp_safe_redirect(add_query_arg('error', $result->get_error_message(), $url));
        exit;
    }
    wp_safe_redirect(add_query_arg(array(
        'page'       => 'beautycore-appointments',
        'updated'    => 1,
        'filter_date' => '',
        'status'     => 'all',
        'staff_id'   => '',
        'branch_id'  => '',
        's'          => '',
    ), beautycore_appointment_relative_url(admin_url('admin.php'))));
    exit;
}
add_action('admin_post_beautycore_save_appointment', 'beautycore_handle_save_appointment');

function beautycore_handle_appointment_status() {
    if (!current_user_can('manage_beautycore_appointments')) {
        wp_die('Bạn không có quyền cập nhật lịch hẹn.');
    }
    $id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
    check_admin_referer('beautycore_appointment_status_' . $id);
    if (!$id || !isset(beautycore_appointment_statuses()[$status])) {
        wp_die('Thao tác lịch hẹn không hợp lệ.');
    }
    $appointment = beautycore_appointment_data($id);
    if (!$appointment) {
        wp_die('Không tìm thấy lịch hẹn.');
    }
    update_post_meta($id, '_beautycore_appointment_status', $status);
    beautycore_appointment_record_history($id, 'status_changed', array('from' => $appointment['status'], 'to' => $status));
    if (function_exists('beautycore_audit_log')) {
        beautycore_audit_log('appointment_status_changed', array('code' => $appointment['code'], 'from' => $appointment['status'], 'to' => $status), 'beautycore_appointment', $id);
    }
    if ($status === 'cancelled') {
        do_action('beautycore_appointment_cancelled', $id);
    } elseif ($status === 'confirmed') {
        beautycore_appointment_send_notification($id, 'confirmed');
    }
    wp_safe_redirect(beautycore_appointment_relative_url(admin_url('admin.php?page=beautycore-appointments&status_updated=1')));
    exit;
}
add_action('admin_post_beautycore_appointment_status', 'beautycore_handle_appointment_status');

function beautycore_render_appointment_settings_panel() {
    if (!current_user_can('view_beautycore_settings')) {
        return;
    }
    $settings = beautycore_appointment_settings();
    echo '<section class="beautycore-panel beautycore-appointment-settings-panel"><h2>Lịch hẹn và nhắc lịch</h2><form method="post" action="' . esc_url(beautycore_appointment_relative_url(admin_url('admin-post.php'))) . '"><input type="hidden" name="action" value="beautycore_save_appointment_settings">';
    wp_nonce_field('beautycore_save_appointment_settings');
    echo '<div class="beautycore-form-grid"><div class="beautycore-form-field"><label for="opening_time"><strong>Giờ mở cửa</strong></label><input id="opening_time" type="time" name="opening_time" value="' . esc_attr($settings['opening_time']) . '"></div><div class="beautycore-form-field"><label for="closing_time"><strong>Giờ đóng cửa</strong></label><input id="closing_time" type="time" name="closing_time" value="' . esc_attr($settings['closing_time']) . '"></div><div class="beautycore-form-field"><label for="buffer_minutes"><strong>Khoảng đệm giữa lịch (phút)</strong></label><input id="buffer_minutes" type="number" min="0" name="buffer_minutes" value="' . esc_attr($settings['buffer_minutes']) . '"></div><div class="beautycore-form-field"><label for="reminder_hours"><strong>Nhắc trước (giờ)</strong></label><input id="reminder_hours" type="number" min="1" name="reminder_hours" value="' . esc_attr($settings['reminder_hours']) . '"></div></div><fieldset><legend><strong>Ngày làm việc</strong></legend><div class="beautycore-workdays">';
    foreach (array('0' => 'CN', '1' => 'T2', '2' => 'T3', '3' => 'T4', '4' => 'T5', '5' => 'T6', '6' => 'T7') as $key => $label) {
        echo '<label><input type="checkbox" name="workdays[]" value="' . esc_attr($key) . '" ' . checked(in_array((string) $key, $settings['workdays'], true), true, false) . '> ' . esc_html($label) . '</label>';
    }
    echo '</div></fieldset>';
    $staff = beautycore_appointment_staff_options();
    if ($staff) {
        echo '<fieldset class="beautycore-staff-days-off"><legend><strong>Ngày nghỉ nhân viên</strong></legend><p class="description">Nhập ngày theo định dạng YYYY-MM-DD, phân cách bằng dấu phẩy.</p>';
        foreach ($staff as $staff_id => $staff_name) {
            $days_off = get_user_meta(absint($staff_id), '_beautycore_staff_days_off', true);
            $days_off = is_array($days_off) ? implode(', ', $days_off) : (string) $days_off;
            echo '<label for="staff-days-off-' . esc_attr($staff_id) . '">' . esc_html($staff_name) . '<input id="staff-days-off-' . esc_attr($staff_id) . '" type="text" name="staff_days_off[' . esc_attr($staff_id) . ']" value="' . esc_attr($days_off) . '" placeholder="2026-08-15, 2026-08-22"></label>';
        }
        echo '</fieldset>';
    }
    echo '<p class="description">Hệ thống không cho lưu lịch ngoài giờ, vào ngày nghỉ, hoặc bị giao nhau với lịch đang Chờ xác nhận/Đã xác nhận/Khách đã đến/Đang thực hiện.</p><p><button class="button button-primary" type="submit">Lưu cấu hình lịch</button></p></form></section>';
}

function beautycore_handle_save_appointment_settings() {
    if (!current_user_can('view_beautycore_settings')) {
        wp_die('Bạn không có quyền sửa cấu hình.');
    }
    check_admin_referer('beautycore_save_appointment_settings');
    $opening = isset($_POST['opening_time']) ? sanitize_text_field(wp_unslash($_POST['opening_time'])) : '09:00';
    $closing = isset($_POST['closing_time']) ? sanitize_text_field(wp_unslash($_POST['closing_time'])) : '21:00';
    if (!preg_match('/^\d{2}:\d{2}$/', $opening) || !preg_match('/^\d{2}:\d{2}$/', $closing) || $opening >= $closing) {
        wp_safe_redirect(add_query_arg(array('page' => 'beautycore-settings', 'error' => 'Giờ mở cửa và đóng cửa không hợp lệ.'), beautycore_appointment_relative_url(admin_url('admin.php'))));
        exit;
    }
    $workdays = !empty($_POST['workdays']) && is_array($_POST['workdays']) ? array_values(array_intersect(array('0', '1', '2', '3', '4', '5', '6'), array_map('sanitize_text_field', wp_unslash($_POST['workdays'])))) : array();
    update_option('beautycore_appointment_settings', array(
        'opening_time'   => $opening,
        'closing_time'   => $closing,
        'buffer_minutes' => max(0, absint(isset($_POST['buffer_minutes']) ? $_POST['buffer_minutes'] : 0)),
        'reminder_hours' => max(1, absint(isset($_POST['reminder_hours']) ? $_POST['reminder_hours'] : 24)),
        'workdays'       => $workdays,
    ), false);
    $staff_days_off = !empty($_POST['staff_days_off']) && is_array($_POST['staff_days_off']) ? wp_unslash($_POST['staff_days_off']) : array();
    foreach (beautycore_appointment_staff_options() as $staff_id => $staff_name) {
        $raw_days = isset($staff_days_off[$staff_id]) ? sanitize_text_field($staff_days_off[$staff_id]) : '';
        $days = preg_split('/[,\s]+/', $raw_days, -1, PREG_SPLIT_NO_EMPTY);
        $days = array_values(array_unique(array_filter(array_map('sanitize_text_field', $days), function ($day) {
            return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $day);
        })));
        update_user_meta(absint($staff_id), '_beautycore_staff_days_off', $days);
    }
    if (function_exists('beautycore_audit_log')) {
        beautycore_audit_log('appointment_settings_updated', array('opening_time' => $opening, 'closing_time' => $closing), 'settings', 0);
    }
    wp_safe_redirect(add_query_arg(array('page' => 'beautycore-settings', 'updated' => 1), beautycore_appointment_relative_url(admin_url('admin.php'))));
    exit;
}
add_action('admin_post_beautycore_save_appointment_settings', 'beautycore_handle_save_appointment_settings');

function beautycore_public_booking_shortcode() {
    $services = beautycore_appointment_service_options();
    $branches = beautycore_appointment_branch_options();
    ob_start();
    if (!empty($_GET['booking']) && sanitize_key(wp_unslash($_GET['booking'])) === 'success') {
        echo '<div class="beautycore-booking-success">Cảm ơn bạn. Yêu cầu đặt lịch đã được tiếp nhận và đang chờ Beauty Core xác nhận.</div>';
    }
    if (!empty($_GET['booking_error'])) {
        echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash($_GET['booking_error'])) . '</p></div>';
    }
    echo '<form class="beautycore-booking-form" method="post" action="' . esc_url(beautycore_appointment_relative_url(admin_url('admin-post.php'))) . '"><input type="hidden" name="action" value="beautycore_public_create_appointment">';
    wp_nonce_field('beautycore_public_create_appointment');
    echo '<p><label>Họ tên *<input required name="customer_name"></label></p><p><label>Số điện thoại *<input required name="customer_phone"></label></p><p><label>Email<input type="email" name="customer_email"></label></p><p><label>Dịch vụ *<select required name="service_id"><option value="0">Chọn dịch vụ</option>';
    foreach ($services as $key => $service) {
        echo '<option value="' . esc_attr($key) . '">' . esc_html($service['name']) . '</option>';
    }
    echo '</select></label></p><p><label>Chi nhánh<select name="branch_id"><option value="">Chọn chi nhánh</option>';
    foreach ($branches as $key => $label) {
        echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
    }
    echo '</select></label></p><div><label>Ngày *<input required type="date" name="date" min="' . esc_attr(current_time('Y-m-d')) . '"></label> <label>Giờ bắt đầu *<input required type="time" name="start_time"></label></div><p><label>Ghi chú<textarea name="notes" rows="3"></textarea></label></p><p><button type="submit">Gửi yêu cầu đặt lịch</button></p></form>';
    return ob_get_clean();
}
add_shortcode('beautycore_booking_form', 'beautycore_public_booking_shortcode');

function beautycore_handle_public_create_appointment() {
    check_admin_referer('beautycore_public_create_appointment');
    $result = beautycore_create_appointment($_POST, 'website');
    $referer = wp_get_referer() ?: home_url('/');
    if (is_wp_error($result)) {
        wp_safe_redirect(add_query_arg('booking_error', $result->get_error_message(), $referer));
        exit;
    }
    wp_safe_redirect(add_query_arg('booking', 'success', $referer));
    exit;
}
add_action('admin_post_nopriv_beautycore_public_create_appointment', 'beautycore_handle_public_create_appointment');
add_action('admin_post_beautycore_public_create_appointment', 'beautycore_handle_public_create_appointment');
