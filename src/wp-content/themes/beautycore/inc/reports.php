<?php
/**
 * Beauty Core operational reporting and CSV export.
 */

if (!defined('ABSPATH')) {
    exit;
}

function beautycore_report_filters($source = null) {
    $source = is_array($source) ? $source : $_GET;
    $today = current_time('Y-m-d');
    $from = isset($source['date_from']) ? sanitize_text_field(wp_unslash($source['date_from'])) : substr($today, 0, 8) . '01';
    $to = isset($source['date_to']) ? sanitize_text_field(wp_unslash($source['date_to'])) : $today;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
        $from = substr($today, 0, 8) . '01';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
        $to = $today;
    }
    if ($from > $to) {
        $swap = $from;
        $from = $to;
        $to = $swap;
    }

    $status = isset($source['status']) ? sanitize_key(wp_unslash($source['status'])) : 'all';
    $status = $status === 'all' || isset(beautycore_appointment_statuses()[$status]) ? $status : 'all';
    return array(
        'date_from' => $from,
        'date_to'   => $to,
        'branch_id' => isset($source['branch_id']) ? sanitize_text_field(wp_unslash($source['branch_id'])) : '',
        'service_id'=> isset($source['service_id']) ? absint($source['service_id']) : 0,
        'staff_id'  => isset($source['staff_id']) ? absint($source['staff_id']) : 0,
        'status'    => $status,
    );
}

function beautycore_report_matches_filter($appointment, $filters) {
    $date = substr((string) $appointment['start'], 0, 10);
    if (!$date || $date < $filters['date_from'] || $date > $filters['date_to']) {
        return false;
    }
    if ($filters['branch_id'] !== '' && (string) $appointment['branch_id'] !== (string) $filters['branch_id']) {
        return false;
    }
    if ($filters['service_id'] && (int) $appointment['service_id'] !== (int) $filters['service_id']) {
        return false;
    }
    if ($filters['staff_id'] && (int) $appointment['staff_id'] !== (int) $filters['staff_id']) {
        return false;
    }
    return $filters['status'] === 'all' || $appointment['status'] === $filters['status'];
}

function beautycore_report_appointments($filters) {
    $appointments = function_exists('beautycore_appointment_get_all') ? beautycore_appointment_get_all() : array();
    $appointments = array_values(array_filter($appointments, function ($appointment) use ($filters) {
        return beautycore_report_matches_filter($appointment, $filters);
    }));
    usort($appointments, function ($left, $right) {
        return $left['timestamp'] <=> $right['timestamp'];
    });
    return $appointments;
}

function beautycore_report_customer_key($appointment) {
    $customer_id = absint(get_post_meta($appointment['id'], '_beautycore_customer_id', true));
    if ($customer_id) {
        return 'customer:' . $customer_id;
    }
    $phone = function_exists('beautycore_customer_phone_key') ? beautycore_customer_phone_key($appointment['customer_phone']) : preg_replace('/\D+/', '', (string) $appointment['customer_phone']);
    return $phone ? 'phone:' . $phone : 'appointment:' . $appointment['id'];
}

function beautycore_report_revenue($appointment) {
    if ($appointment['status'] !== 'completed' || ($appointment['payment_status'] ?? 'unpaid') !== 'paid') {
        return 0;
    }
    return max(0, (float) $appointment['price'] - (float) ($appointment['refund_amount'] ?? 0));
}

function beautycore_report_data($filters) {
    $records = beautycore_report_appointments($filters);
    $all_appointments = function_exists('beautycore_appointment_get_all') ? beautycore_appointment_get_all() : array();
    usort($all_appointments, function ($left, $right) {
        return $left['timestamp'] <=> $right['timestamp'];
    });
    $first_visits = array();
    foreach ($all_appointments as $appointment) {
        if (function_exists('beautycore_appointment_is_cancelled') && beautycore_appointment_is_cancelled($appointment['status'])) {
            continue;
        }
        $key = beautycore_report_customer_key($appointment);
        if (!isset($first_visits[$key])) {
            $first_visits[$key] = substr($appointment['start'], 0, 10);
        }
    }

    $review_ratings = array();
    if (function_exists('beautycore_review_data')) {
        foreach (get_posts(array('post_type' => BEAUTYCORE_REVIEW_POST_TYPE, 'post_status' => 'publish', 'posts_per_page' => -1, 'meta_key' => '_beautycore_review_status', 'meta_value' => 'approved')) as $review_post) {
            $review = beautycore_review_data($review_post);
            if (!$review['staff_id']) {
                continue;
            }
            if (!isset($review_ratings[$review['staff_id']])) {
                $review_ratings[$review['staff_id']] = array('total' => 0, 'count' => 0);
            }
            $review_ratings[$review['staff_id']]['total'] += $review['rating'];
            $review_ratings[$review['staff_id']]['count']++;
        }
    }
    $kpi = array('today' => 0, 'pending' => 0, 'completed' => 0, 'cancelled' => 0, 'no-show' => 0, 'new_customers' => 0, 'returning_customers' => 0, 'revenue' => 0);
    $services = array();
    $staff = array();
    $trend = array();
    $customers = array();
    foreach ($records as $appointment) {
        $status = $appointment['status'];
        if (isset($kpi[$status])) {
            $kpi[$status]++;
        }
        if (substr($appointment['start'], 0, 10) === current_time('Y-m-d')) {
            $kpi['today']++;
        }
        $revenue = beautycore_report_revenue($appointment);
        $kpi['revenue'] += $revenue;
        $customer_key = beautycore_report_customer_key($appointment);
        if (!isset($customers[$customer_key]) && !(function_exists('beautycore_appointment_is_cancelled') && beautycore_appointment_is_cancelled($status))) {
            $customers[$customer_key] = true;
            if (($first_visits[$customer_key] ?? '') >= $filters['date_from'] && ($first_visits[$customer_key] ?? '') <= $filters['date_to']) {
                $kpi['new_customers']++;
            } else {
                $kpi['returning_customers']++;
            }
        }

        $service_key = $appointment['service_id'] ? (string) $appointment['service_id'] : $appointment['service_name'];
        if (!isset($services[$service_key])) {
            $services[$service_key] = array('name' => $appointment['service_name'] ?: 'Chưa chọn dịch vụ', 'bookings' => 0, 'completed' => 0, 'revenue' => 0);
        }
        $services[$service_key]['bookings']++;
        $services[$service_key]['completed'] += $status === 'completed' ? 1 : 0;
        $services[$service_key]['revenue'] += $revenue;

        $staff_key = $appointment['staff_id'] ? (string) $appointment['staff_id'] : 'unassigned';
        if (!isset($staff[$staff_key])) {
            $rating = !empty($review_ratings[$appointment['staff_id']]) ? $review_ratings[$appointment['staff_id']] : array('total' => 0, 'count' => 0);
            $staff[$staff_key] = array('name' => $appointment['staff_name'] ?: 'Chưa phân công', 'bookings' => 0, 'completed' => 0, 'rating' => $rating);
        }
        $staff[$staff_key]['bookings']++;
        $staff[$staff_key]['completed'] += $status === 'completed' ? 1 : 0;

        $date = substr($appointment['start'], 0, 10);
        if (!isset($trend[$date])) {
            $trend[$date] = array('bookings' => 0, 'completed' => 0);
        }
        $trend[$date]['bookings']++;
        $trend[$date]['completed'] += $status === 'completed' ? 1 : 0;
    }
    foreach (array('services', 'staff') as $collection) {
        uasort($$collection, function ($left, $right) {
            return $right['bookings'] <=> $left['bookings'];
        });
    }
    ksort($trend);

    return array('filters' => $filters, 'records' => $records, 'kpi' => $kpi, 'services' => $services, 'staff' => $staff, 'trend' => $trend);
}

function beautycore_report_format_money($amount) {
    return function_exists('beautycore_service_format_price') ? beautycore_service_format_price($amount) : number_format_i18n($amount, 0) . 'đ';
}

function beautycore_report_render_filters($filters) {
    $services = function_exists('beautycore_appointment_service_options') ? beautycore_appointment_service_options() : array();
    $branches = function_exists('beautycore_appointment_branch_options') ? beautycore_appointment_branch_options() : array();
    $staff = function_exists('beautycore_appointment_staff_options') ? beautycore_appointment_staff_options() : array();
    echo '<form method="get" class="beautycore-appointment-filters beautycore-report-filters"><input type="hidden" name="page" value="beautycore-reports"><input type="date" name="date_from" value="' . esc_attr($filters['date_from']) . '" aria-label="Từ ngày"><input type="date" name="date_to" value="' . esc_attr($filters['date_to']) . '" aria-label="Đến ngày"><select name="branch_id"><option value="">Tất cả chi nhánh</option>';
    foreach ($branches as $id => $label) { echo '<option value="' . esc_attr($id) . '" ' . selected($filters['branch_id'], (string) $id, false) . '>' . esc_html($label) . '</option>'; }
    echo '</select><select name="service_id"><option value="0">Tất cả dịch vụ</option>';
    foreach ($services as $id => $service) { echo '<option value="' . esc_attr($id) . '" ' . selected($filters['service_id'], (int) $id, false) . '>' . esc_html($service['name']) . '</option>'; }
    echo '</select><select name="staff_id"><option value="0">Tất cả nhân viên</option>';
    foreach ($staff as $id => $label) { echo '<option value="' . esc_attr($id) . '" ' . selected($filters['staff_id'], (int) $id, false) . '>' . esc_html($label) . '</option>'; }
    echo '</select><select name="status"><option value="all">Tất cả trạng thái</option>';
    foreach (beautycore_appointment_statuses() as $id => $label) { echo '<option value="' . esc_attr($id) . '" ' . selected($filters['status'], $id, false) . '>' . esc_html($label) . '</option>'; }
    echo '</select><button class="button">Lọc</button></form>';
}

function beautycore_report_export_url($filters) {
    $url = add_query_arg(array_merge(array('action' => 'beautycore_export_reports'), $filters), admin_url('admin-post.php'));
    return wp_nonce_url($url, 'beautycore_export_reports');
}

function beautycore_report_render_trend($trend) {
    if (!$trend) {
        echo '<div class="beautycore-empty-state"><strong>Chưa có dữ liệu xu hướng</strong><span>Chọn khoảng thời gian có lịch hẹn để xem diễn biến đặt lịch.</span></div>';
        return;
    }
    $values = array_column($trend, 'bookings');
    $maximum = max(1, max($values));
    $count = count($trend);
    $points = array();
    $index = 0;
    foreach ($trend as $row) {
        $x = $count > 1 ? ($index / ($count - 1)) * 100 : 50;
        $y = 92 - (($row['bookings'] / $maximum) * 80);
        $points[] = round($x, 2) . ',' . round($y, 2);
        $index++;
    }
    $dates = array_keys($trend);
    echo '<div class="beautycore-report-chart"><svg viewBox="0 0 100 100" preserveAspectRatio="none" role="img" aria-label="Xu hướng số lịch hẹn"><line x1="0" y1="92" x2="100" y2="92"></line><polyline points="' . esc_attr(implode(' ', $points)) . '"></polyline></svg><div class="beautycore-report-chart-labels"><span>' . esc_html(wp_date('d/m', strtotime($dates[0]), wp_timezone())) . '</span><strong>' . esc_html($maximum) . ' lịch/ngày</strong><span>' . esc_html(wp_date('d/m', strtotime($dates[count($dates) - 1]), wp_timezone())) . '</span></div></div>';
}

function beautycore_render_advanced_reports_page() {
    $filters = beautycore_report_filters();
    $report = beautycore_report_data($filters);
    $kpi = $report['kpi'];
    beautycore_report_render_filters($filters);
    if (current_user_can('export_beautycore_reports')) {
        echo '<div class="beautycore-report-actions"><a class="button" href="' . esc_url(beautycore_report_export_url($filters)) . '">Xuất CSV</a></div>';
    }
    echo '<div class="beautycore-stat-grid beautycore-report-kpis">';
    beautycore_admin_stat_card('Lịch hôm nay', $kpi['today'], 'beautycore-stat-primary');
    beautycore_admin_stat_card('Pending', $kpi['pending']);
    beautycore_admin_stat_card('Completed', $kpi['completed']);
    beautycore_admin_stat_card('Cancelled', $kpi['cancelled']);
    beautycore_admin_stat_card('No-show', $kpi['no-show']);
    beautycore_admin_stat_card('Khách mới', $kpi['new_customers']);
    beautycore_admin_stat_card('Khách quay lại', $kpi['returning_customers']);
    beautycore_admin_stat_card('Doanh thu hợp lệ', beautycore_report_format_money($kpi['revenue']));
    echo '</div>';
    echo '<div class="beautycore-dashboard-grid"><section class="beautycore-panel"><div class="beautycore-panel-heading"><h2>Xu hướng lịch hẹn</h2><span class="description">Theo ngày trong khoảng đã chọn</span></div>';
    beautycore_report_render_trend($report['trend']);
    echo '</section><section class="beautycore-panel"><h2>Quy tắc doanh thu</h2><ul class="beautycore-report-list"><li>Chỉ tính lịch <strong>Hoàn tất</strong> và <strong>Đã thanh toán</strong>.</li><li>Khoản hoàn tiền được trừ trực tiếp khỏi doanh thu.</li><li>Lịch hủy, no-show và chưa thanh toán không được tính.</li></ul></section></div>';
    echo '<div class="beautycore-dashboard-grid"><section class="beautycore-panel"><div class="beautycore-panel-heading"><h2>Báo cáo dịch vụ</h2><span class="description">Theo số lịch trong khoảng lọc</span></div>';
    if ($report['services']) {
        echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table"><thead><tr><th>Dịch vụ</th><th>Số lượt đặt</th><th>Tỷ lệ hoàn thành</th><th>Doanh thu</th></tr></thead><tbody>';
        foreach ($report['services'] as $service) {
            $rate = $service['bookings'] ? round(($service['completed'] / $service['bookings']) * 100) : 0;
            echo '<tr><td><strong>' . esc_html($service['name']) . '</strong></td><td>' . esc_html($service['bookings']) . '</td><td><div class="beautycore-report-rate"><span style="width:' . esc_attr($rate) . '%"></span></div>' . esc_html($rate) . '%</td><td>' . esc_html(beautycore_report_format_money($service['revenue'])) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    } else { echo '<div class="beautycore-empty-state"><strong>Chưa có dữ liệu dịch vụ</strong><span>Không có lịch hẹn khớp bộ lọc hiện tại.</span></div>'; }
    echo '</section><section class="beautycore-panel"><div class="beautycore-panel-heading"><h2>Báo cáo nhân viên</h2><span class="description">Đánh giá sẽ hiển thị khi review được gán nhân viên.</span></div>';
    if ($report['staff']) {
        echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table"><thead><tr><th>Nhân viên</th><th>Số lịch</th><th>Hoàn tất</th><th>Đánh giá TB</th></tr></thead><tbody>';
        foreach ($report['staff'] as $person) { $rating = $person['rating']['count'] ? number_format_i18n($person['rating']['total'] / $person['rating']['count'], 1) . ' / 5' : '—'; echo '<tr><td><strong>' . esc_html($person['name']) . '</strong></td><td>' . esc_html($person['bookings']) . '</td><td>' . esc_html($person['completed']) . '</td><td>' . esc_html($rating) . '</td></tr>'; }
        echo '</tbody></table></div>';
    } else { echo '<div class="beautycore-empty-state"><strong>Chưa có dữ liệu nhân viên</strong><span>Phân công nhân viên cho lịch hẹn để theo dõi hiệu suất.</span></div>'; }
    echo '</section></div>';
    echo '<section class="beautycore-panel"><div class="beautycore-panel-heading"><h2>Chi tiết lịch hẹn</h2><span class="description">' . esc_html(count($report['records'])) . ' bản ghi</span></div>';
    if ($report['records']) {
        echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table beautycore-report-detail"><thead><tr><th>Thời gian</th><th>Mã lịch</th><th>Dịch vụ</th><th>Nhân viên</th><th>Chi nhánh</th><th>Trạng thái</th><th>Thanh toán</th><th>Doanh thu</th></tr></thead><tbody>';
        foreach ($report['records'] as $appointment) {
            $payment_label = beautycore_appointment_payment_statuses()[$appointment['payment_status'] ?? 'unpaid'] ?? 'Chưa thanh toán';
            echo '<tr><td>' . esc_html(beautycore_format_appointment_time($appointment['timestamp'], $appointment['start'])) . '</td><td>' . esc_html($appointment['code']) . '</td><td>' . esc_html($appointment['service_name']) . '</td><td>' . esc_html($appointment['staff_name'] ?: '—') . '</td><td>' . esc_html($appointment['branch_name'] ?: '—') . '</td><td><span class="beautycore-status beautycore-status-' . esc_attr($appointment['status']) . '">' . esc_html(beautycore_appointment_statuses()[$appointment['status']] ?? $appointment['status']) . '</span></td><td>' . esc_html($payment_label) . '</td><td>' . esc_html(beautycore_report_format_money(beautycore_report_revenue($appointment))) . '</td></tr>';
        }
        echo '</tbody></table></div>';
    } else { echo '<div class="beautycore-empty-state"><strong>Không có lịch hẹn</strong><span>Hãy điều chỉnh bộ lọc để xem dữ liệu chi tiết.</span></div>'; }
    echo '</section>';
}

function beautycore_handle_export_reports() {
    if (!current_user_can('export_beautycore_reports')) {
        wp_die('Bạn không có quyền xuất báo cáo.');
    }
    check_admin_referer('beautycore_export_reports');
    $report = beautycore_report_data(beautycore_report_filters($_GET));
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=beautycore-bao-cao-' . current_time('Ymd') . '.csv');
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Thời gian', 'Mã lịch', 'Dịch vụ', 'Nhân viên', 'Chi nhánh', 'Trạng thái', 'Thanh toán', 'Hoàn tiền', 'Doanh thu hợp lệ'));
    foreach ($report['records'] as $appointment) {
        fputcsv($output, array(
            beautycore_format_appointment_time($appointment['timestamp'], $appointment['start']),
            $appointment['code'], $appointment['service_name'], $appointment['staff_name'], $appointment['branch_name'],
            beautycore_appointment_statuses()[$appointment['status']] ?? $appointment['status'],
            beautycore_appointment_payment_statuses()[$appointment['payment_status'] ?? 'unpaid'] ?? 'Chưa thanh toán',
            $appointment['refund_amount'] ?? 0, beautycore_report_revenue($appointment),
        ));
    }
    fclose($output);
    exit;
}
add_action('admin_post_beautycore_export_reports', 'beautycore_handle_export_reports');
