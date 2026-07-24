<?php
/**
 * Beauty Core service/pricing management screens and write handlers.
 */

if (!defined('ABSPATH')) {
    exit;
}

function beautycore_service_admin_menu() {
    add_submenu_page(
        null,
        'Thêm / sửa dịch vụ',
        'Thêm / sửa dịch vụ',
        'manage_beautycore_services',
        'beautycore-service-edit',
        'beautycore_render_service_edit_page'
    );
}
add_action('admin_menu', 'beautycore_service_admin_menu', 10);

function beautycore_service_admin_assets($hook) {
    if (empty($_GET['page'])) {
        return;
    }

    $page = sanitize_key(wp_unslash($_GET['page']));
    if ($page !== 'beautycore-services' && $page !== 'beautycore-pricing' && $page !== 'beautycore-service-edit') {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'beautycore-service-admin',
        get_theme_file_uri('/assets/js/admin-service.js'),
        array('jquery'),
        BEAUTYCORE_ADMIN_VERSION,
        true
    );
}
add_action('admin_enqueue_scripts', 'beautycore_service_admin_assets');

function beautycore_service_admin_redirect($args = array(), $page = 'beautycore-services') {
    $url = add_query_arg(array_merge(array('page' => $page), $args), admin_url('admin.php'));
    wp_safe_redirect($url);
    exit;
}

function beautycore_service_admin_notice() {
    if (!empty($_GET['updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Dịch vụ đã được lưu thành công.</p></div>';
    }
    if (!empty($_GET['deleted'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Dịch vụ đã được xóa.</p></div>';
    }
    if (!empty($_GET['category_updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Danh mục đã được cập nhật.</p></div>';
    }
    if (!empty($_GET['error'])) {
        echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash($_GET['error'])) . '</p></div>';
    }
}

function beautycore_service_admin_status_label($status) {
    $statuses = beautycore_service_statuses();
    return isset($statuses[$status]) ? $statuses[$status] : $status;
}

function beautycore_render_service_filters($pricing_view = false) {
    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $category = isset($_GET['category']) ? absint($_GET['category']) : 0;
    $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all';
    $featured = isset($_GET['featured']) ? sanitize_key(wp_unslash($_GET['featured'])) : 'all';
    $terms = get_terms(array('taxonomy' => 'beautycore_service_category', 'hide_empty' => false));
    $statuses = beautycore_service_statuses();

    echo '<form class="beautycore-service-filters" method="get">';
    echo '<input type="hidden" name="page" value="' . esc_attr($pricing_view ? 'beautycore-pricing' : 'beautycore-services') . '">';
    echo '<label class="screen-reader-text" for="beautycore-service-search">Tìm dịch vụ</label><input id="beautycore-service-search" type="search" name="s" value="' . esc_attr($search) . '" placeholder="Tìm theo tên dịch vụ...">';
    echo '<select name="category"><option value="0">Tất cả danh mục</option>';
    if (!is_wp_error($terms)) {
        foreach ($terms as $term) {
            echo '<option value="' . esc_attr($term->term_id) . '" ' . selected($category, $term->term_id, false) . '>' . esc_html($term->name) . '</option>';
        }
    }
    echo '</select><select name="status"><option value="all">Tất cả trạng thái</option>';
    foreach ($statuses as $status_key => $status_label) {
        echo '<option value="' . esc_attr($status_key) . '" ' . selected($status, $status_key, false) . '>' . esc_html($status_label) . '</option>';
    }
    echo '</select><select name="featured"><option value="all">Nổi bật: tất cả</option><option value="1" ' . selected($featured, '1', false) . '>Đang nổi bật</option><option value="0" ' . selected($featured, '0', false) . '>Không nổi bật</option></select>';
    submit_button('Lọc', 'secondary', '', false);
    if ($search || $category || $status !== 'all' || $featured !== 'all') {
        echo ' <a class="button" href="' . esc_url(admin_url('admin.php?page=' . ($pricing_view ? 'beautycore-pricing' : 'beautycore-services'))) . '">Xóa bộ lọc</a>';
    }
    echo '</form>';
}

function beautycore_get_admin_services() {
    $status = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : 'all';
    $status_options = beautycore_service_statuses();
    $args = array(
        'post_type'      => 'beautycore_service',
        'post_status'    => $status !== 'all' && isset($status_options[$status]) ? $status : array_keys($status_options),
        'posts_per_page' => -1,
        's'              => isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '',
        'orderby'        => 'title',
        'order'          => 'ASC',
    );
    $category = isset($_GET['category']) ? absint($_GET['category']) : 0;
    $featured = isset($_GET['featured']) ? sanitize_key(wp_unslash($_GET['featured'])) : 'all';
    if ($category) {
        $args['tax_query'] = array(array(
            'taxonomy' => 'beautycore_service_category',
            'field'    => 'term_id',
            'terms'    => $category,
        ));
    }
    if ($featured !== 'all') {
        $args['meta_query'] = array(array(
            'key'   => '_beautycore_featured',
            'value' => $featured === '1' ? '1' : '0',
        ));
    }

    return get_posts($args);
}

function beautycore_service_category_names($post_id) {
    $terms = get_the_terms($post_id, 'beautycore_service_category');
    if (!$terms || is_wp_error($terms)) {
        return 'Chưa phân loại';
    }

    return implode(', ', wp_list_pluck($terms, 'name'));
}

function beautycore_render_services_page($pricing_view = false) {
    if (!current_user_can('view_beautycore_services')) {
        wp_die('Bạn không có quyền xem dịch vụ.');
    }

    beautycore_service_admin_notice();
    echo '<div class="beautycore-service-toolbar"><div>';
    if (current_user_can('manage_beautycore_services')) {
        echo '<a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=beautycore-service-edit')) . '">Thêm dịch vụ</a> <a class="button" href="#beautycore-categories">Quản lý danh mục</a>';
    }
    echo '</div><span class="description">' . esc_html($pricing_view ? 'Quản lý giá gốc, giá khuyến mãi và thời gian áp dụng.' : 'Quản lý thông tin, trạng thái và cách hiển thị dịch vụ.') . '</span></div>';
    beautycore_render_service_filters($pricing_view);

    $services = beautycore_get_admin_services();
    if (!$services) {
        echo '<div class="beautycore-empty-state"><strong>Chưa có dịch vụ</strong><span>Hãy thêm dịch vụ đầu tiên hoặc kiểm tra lại bộ lọc.</span></div>';
    } else {
        echo '<div class="beautycore-table-wrap"><table class="widefat striped beautycore-admin-table beautycore-service-table"><thead><tr><th>Tên dịch vụ</th><th>Danh mục</th><th>Giá</th><th>Thời lượng</th><th>Chi nhánh</th><th>Trạng thái</th><th>Cập nhật</th><th></th></tr></thead><tbody>';
        foreach ($services as $service) {
            $meta = beautycore_service_meta($service->ID);
            $edit_url = admin_url('admin.php?page=beautycore-service-edit&id=' . $service->ID);
            $branch_options = beautycore_get_service_branch_options();
            $branch_count = count(array_intersect(array_keys($branch_options), $meta['branch_ids']));
            if (!$branch_count) {
                $branch_count = count($meta['branch_ids']);
            }
            echo '<tr>';
            echo '<td><strong><a href="' . esc_url($edit_url) . '">' . esc_html($service->post_title) . '</a></strong>' . ($meta['featured'] ? ' <span class="beautycore-featured">Nổi bật</span>' : '') . '<div class="row-actions"><span><a href="' . esc_url($edit_url) . '">Sửa</a></span> | <span><a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=beautycore_toggle_service&id=' . $service->ID), 'beautycore_toggle_service_' . $service->ID)) . '">' . ($service->post_status === 'publish' ? 'Ẩn nhanh' : 'Xuất bản') . '</a></span> | <span class="trash"><a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=beautycore_delete_service&id=' . $service->ID), 'beautycore_delete_service_' . $service->ID)) . '" onclick="return confirm(\'Xóa dịch vụ này?\');">Xóa</a></span></div></td>';
            echo '<td>' . esc_html(beautycore_service_category_names($service->ID)) . '</td>';
            echo '<td><strong>' . wp_kses_post(beautycore_service_price_html($meta)) . '</strong></td>';
            echo '<td>' . esc_html($meta['duration'] ? $meta['duration'] . ' phút' : '—') . '</td>';
            echo '<td>' . esc_html($branch_count ? $branch_count . ' chi nhánh' : 'Chưa gán') . '</td>';
            echo '<td><span class="beautycore-status beautycore-status-' . esc_attr($service->post_status) . '">' . esc_html(beautycore_service_admin_status_label($service->post_status)) . '</span></td>';
            echo '<td>' . esc_html(get_the_modified_date('d/m/Y H:i', $service)) . '</td>';
            echo '<td><a class="button button-small" href="' . esc_url($edit_url) . '">Mở</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }

    beautycore_render_service_categories_panel();
}

function beautycore_render_service_categories_panel() {
    $terms = get_terms(array('taxonomy' => 'beautycore_service_category', 'hide_empty' => false));
    $can_manage = current_user_can('manage_beautycore_services');
    echo '<section class="beautycore-panel beautycore-category-panel" id="beautycore-categories"><div class="beautycore-panel-heading"><h2>Danh mục dịch vụ</h2><span class="description">Dùng danh mục để nhóm dịch vụ trên website.</span></div>';
    if ($can_manage) {
        echo '<form class="beautycore-category-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="beautycore_save_service_category">';
        wp_nonce_field('beautycore_save_service_category');
        echo '<input type="text" name="name" required placeholder="Tên danh mục mới"><input type="text" name="eyebrow" placeholder="Nhãn phụ, ví dụ: Menu Hair Care"><input type="text" name="description" placeholder="Mô tả ngắn"><button class="button button-secondary" type="submit">Thêm danh mục</button></form>';
    }
    if ($terms && !is_wp_error($terms)) {
        echo '<ul class="beautycore-category-list">';
        foreach ($terms as $term) {
            echo '<li><span><strong>' . esc_html($term->name) . '</strong> <small>(' . esc_html((int) $term->count) . ' dịch vụ)</small></span>' . ($can_manage ? '<a href="' . esc_url(wp_nonce_url(admin_url('admin-post.php?action=beautycore_delete_service_category&id=' . $term->term_id), 'beautycore_delete_service_category_' . $term->term_id)) . '" class="submitdelete" onclick="return confirm(\'Xóa danh mục? Dịch vụ sẽ chuyển sang chưa phân loại.\');">Xóa</a>' : '') . '</li>';
        }
        echo '</ul>';
    }
    echo '</section>';
}

function beautycore_render_service_field($label, $field, $value, $type = 'text', $description = '') {
    echo '<div class="beautycore-form-field"><label for="' . esc_attr($field) . '"><strong>' . esc_html($label) . '</strong>';
    if ($type === 'textarea') {
        echo '<textarea id="' . esc_attr($field) . '" name="' . esc_attr($field) . '" rows="5">' . esc_textarea($value) . '</textarea>';
    } elseif ($type === 'number') {
        echo '<input id="' . esc_attr($field) . '" type="number" min="0" step="1" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '">';
    } else {
        echo '<input id="' . esc_attr($field) . '" type="' . esc_attr($type) . '" name="' . esc_attr($field) . '" value="' . esc_attr($value) . '">';
    }
    echo '</label>' . ($description ? '<p class="description">' . esc_html($description) . '</p>' : '') . '</div>';
}

function beautycore_render_service_edit_page() {
    if (!current_user_can('manage_beautycore_services')) {
        wp_die('Bạn không có quyền chỉnh sửa dịch vụ.');
    }

    $service_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    $service = $service_id ? get_post($service_id) : null;
    if ($service_id && (!$service || $service->post_type !== 'beautycore_service')) {
        wp_die('Không tìm thấy dịch vụ.');
    }
    $meta = $service ? beautycore_service_meta($service->ID) : array(
        'price_original' => '', 'price_sale' => '', 'duration' => '', 'booking_enabled' => true, 'booking_url' => '', 'booking_note' => '',
        'promo_start' => '', 'promo_end' => '', 'featured' => false, 'homepage_order' => 0, 'branch_ids' => array(), 'staff_ids' => array(),
        'image_id' => 0, 'image_url' => '', 'seo_title' => '', 'seo_description' => '',
    );
    $assigned_terms = $service ? wp_get_object_terms($service->ID, 'beautycore_service_category', array('fields' => 'ids')) : array();
    $categories = get_terms(array('taxonomy' => 'beautycore_service_category', 'hide_empty' => false));
    $branches = beautycore_get_service_branch_options();
    $staff = beautycore_get_service_staff_options();
    $statuses = beautycore_service_statuses();
    $status = $service ? $service->post_status : 'draft';
    $slug = $service ? $service->post_name : '';

    beautycore_admin_page_header($service ? 'Sửa dịch vụ' : 'Thêm dịch vụ', 'Cập nhật một nơi để website và bảng giá luôn dùng cùng một dữ liệu.');
    if (!empty($_GET['error'])) {
        echo '<div class="notice notice-error"><p>' . esc_html(wp_unslash($_GET['error'])) . '</p></div>';
    }
    echo '<form class="beautycore-service-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    echo '<input type="hidden" name="action" value="beautycore_save_service"><input type="hidden" name="service_id" value="' . esc_attr($service_id) . '"><input type="hidden" name="image_id" id="beautycore-image-id" value="' . esc_attr($meta['image_id']) . '">';
    wp_nonce_field('beautycore_save_service');
    echo '<div class="beautycore-service-form-layout"><div class="beautycore-service-form-main"><nav class="beautycore-form-tabs" aria-label="Các nhóm thông tin"><a href="#tab-info">Thông tin</a><a href="#tab-price">Giá</a><a href="#tab-booking">Đặt lịch</a><a href="#tab-branch">Chi nhánh</a><a href="#tab-image">Hình ảnh</a><a href="#tab-seo">SEO</a><a href="#tab-status">Trạng thái</a></nav>';

    echo '<section id="tab-info" class="beautycore-panel beautycore-form-tab"><h2>Thông tin dịch vụ</h2>';
    beautycore_render_service_field('Tên dịch vụ', 'title', $service ? $service->post_title : '', 'text');
    beautycore_render_service_field('Slug', 'slug', $slug, 'text', 'Để trống để tự tạo từ tên dịch vụ.');
    echo '<div class="beautycore-form-field"><label for="service-category"><strong>Danh mục</strong></label><select id="service-category" name="category_id"><option value="0">Chưa phân loại</option>';
    if ($categories && !is_wp_error($categories)) {
        foreach ($categories as $category) {
            echo '<option value="' . esc_attr($category->term_id) . '" ' . selected(in_array($category->term_id, $assigned_terms, true), true, false) . '>' . esc_html($category->name) . '</option>';
        }
    }
    echo '</select></div>';
    beautycore_render_service_field('Nội dung / quy trình', 'content', $service ? $service->post_content : '', 'textarea', 'Mô tả ngắn sẽ xuất hiện trên website và giúp nhân viên tư vấn.');
    echo '</section>';

    echo '<section id="tab-price" class="beautycore-panel beautycore-form-tab"><h2>Giá và khuyến mãi</h2><div class="beautycore-form-grid">';
    beautycore_render_service_field('Giá gốc (VNĐ)', 'price_original', $meta['price_original'], 'number', 'Không được âm.');
    beautycore_render_service_field('Giá khuyến mãi (VNĐ)', 'price_sale', $meta['price_sale'] ?: '', 'number', 'Để trống nếu không khuyến mãi; phải nhỏ hơn giá gốc.');
    beautycore_render_service_field('Thời lượng (phút)', 'duration', $meta['duration'], 'number', 'Phải lớn hơn 0 với dịch vụ có thể đặt lịch.');
    beautycore_render_service_field('Ngày bắt đầu', 'promo_start', $meta['promo_start'], 'date');
    beautycore_render_service_field('Ngày kết thúc', 'promo_end', $meta['promo_end'], 'date', 'Ngày kết thúc phải sau ngày bắt đầu.');
    echo '</div></section>';

    echo '<section id="tab-booking" class="beautycore-panel beautycore-form-tab"><h2>Thiết lập đặt lịch</h2><div class="beautycore-checkbox-field"><label><input type="checkbox" name="booking_enabled" value="1" ' . checked($meta['booking_enabled'], true, false) . '> Cho phép khách đặt lịch dịch vụ này</label></div>';
    beautycore_render_service_field('URL đặt lịch', 'booking_url', $meta['booking_url'], 'url', 'Để trống để dùng URL đặt lịch chung của website.');
    beautycore_render_service_field('Ghi chú khi đặt lịch', 'booking_note', $meta['booking_note'], 'textarea');
    echo '</section>';

    echo '<section id="tab-branch" class="beautycore-panel beautycore-form-tab"><h2>Chi nhánh và nhân viên</h2><p class="description">Có thể mở rộng danh sách này từ module Chi nhánh/Nhân viên qua các filter dữ liệu của Beauty Core.</p><div class="beautycore-assignment-grid"><div><h3>Chi nhánh phục vụ</h3>';
    if ($branches) {
        foreach ($branches as $branch_id => $branch_name) {
            echo '<label class="beautycore-check-option"><input type="checkbox" name="branch_ids[]" value="' . esc_attr($branch_id) . '" ' . checked(in_array((string) $branch_id, $meta['branch_ids'], true), true, false) . '> ' . esc_html($branch_name) . '</label>';
        }
    } else {
        echo '<p class="description">Chưa có chi nhánh để gán.</p>';
    }
    echo '</div><div><h3>Nhân viên thực hiện</h3>';
    if ($staff) {
        foreach ($staff as $staff_id => $staff_name) {
            echo '<label class="beautycore-check-option"><input type="checkbox" name="staff_ids[]" value="' . esc_attr($staff_id) . '" ' . checked(in_array((string) $staff_id, $meta['staff_ids'], true), true, false) . '> ' . esc_html($staff_name) . '</label>';
        }
    } else {
        echo '<p class="description">Chưa có nhân viên để gán.</p>';
    }
    echo '</div></div></section>';

    echo '<section id="tab-image" class="beautycore-panel beautycore-form-tab"><h2>Hình ảnh</h2><div class="beautycore-image-picker"><div id="beautycore-image-preview">' . ($meta['image_url'] ? '<img src="' . esc_url($meta['image_url']) . '" alt="">' : '<span>Chưa chọn ảnh</span>') . '</div><div><button type="button" class="button" id="beautycore-select-image">Chọn từ Media</button><button type="button" class="button" id="beautycore-remove-image">Bỏ ảnh</button></div></div>';
    beautycore_render_service_field('URL ảnh dự phòng', 'image_url', $meta['image_url'], 'url', 'Dùng khi ảnh không nằm trong Media Library.');
    echo '</section>';

    echo '<section id="tab-seo" class="beautycore-panel beautycore-form-tab"><h2>SEO</h2>';
    beautycore_render_service_field('SEO title', 'seo_title', $meta['seo_title'], 'text');
    beautycore_render_service_field('SEO description', 'seo_description', $meta['seo_description'], 'textarea');
    echo '</section></div><aside class="beautycore-service-form-side"><section id="tab-status" class="beautycore-panel beautycore-form-tab"><h2>Trạng thái và hiển thị</h2><div class="beautycore-form-field"><label for="service-status"><strong>Trạng thái</strong></label><select id="service-status" name="status">';
    foreach ($statuses as $status_key => $status_label) {
        echo '<option value="' . esc_attr($status_key) . '" ' . selected($status, $status_key, false) . '>' . esc_html($status_label) . '</option>';
    }
    echo '</select></div><div class="beautycore-checkbox-field"><label><input type="checkbox" name="featured" value="1" ' . checked($meta['featured'], true, false) . '> Đánh dấu nổi bật trên trang chủ</label></div>';
    beautycore_render_service_field('Thứ tự trang chủ', 'homepage_order', $meta['homepage_order'], 'number', 'Số nhỏ hơn sẽ hiển thị trước.');
    echo '</section><div class="beautycore-form-actions"><button type="submit" class="button button-primary button-large">Lưu dịch vụ</button><a class="button button-large" href="' . esc_url(admin_url('admin.php?page=beautycore-services')) . '">Hủy</a></div></aside></div></form>';
    beautycore_admin_page_footer();
}

function beautycore_handle_save_service() {
    if (!current_user_can('manage_beautycore_services')) {
        wp_die('Bạn không có quyền chỉnh sửa dịch vụ.');
    }
    check_admin_referer('beautycore_save_service');

    $service_id = isset($_POST['service_id']) ? absint($_POST['service_id']) : 0;
    $existing = $service_id ? get_post($service_id) : null;
    if ($service_id && (!$existing || $existing->post_type !== 'beautycore_service')) {
        wp_die('Dịch vụ không hợp lệ.');
    }
    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    if (!$title) {
        beautycore_service_admin_redirect(array('error' => 'Tên dịch vụ là bắt buộc.', 'id' => $service_id), 'beautycore-service-edit');
    }

    $original_raw = isset($_POST['price_original']) ? str_replace(',', '', sanitize_text_field(wp_unslash($_POST['price_original']))) : '';
    $sale_raw = isset($_POST['price_sale']) ? str_replace(',', '', sanitize_text_field(wp_unslash($_POST['price_sale']))) : '';
    $original = $original_raw === '' ? 0 : (float) $original_raw;
    $sale = $sale_raw === '' ? 0 : (float) $sale_raw;
    $start = isset($_POST['promo_start']) ? sanitize_text_field(wp_unslash($_POST['promo_start'])) : '';
    $end = isset($_POST['promo_end']) ? sanitize_text_field(wp_unslash($_POST['promo_end'])) : '';
    $duration = isset($_POST['duration']) ? absint($_POST['duration']) : 0;
    $error = '';
    if ($original < 0 || $sale < 0) {
        $error = 'Giá không được âm.';
    } elseif ($sale > 0 && $sale >= $original) {
        $error = 'Giá khuyến mãi phải nhỏ hơn giá gốc.';
    } elseif ($start && $end && strtotime($end) <= strtotime($start)) {
        $error = 'Ngày kết thúc phải sau ngày bắt đầu.';
    } elseif ($duration <= 0) {
        $error = 'Thời lượng phải lớn hơn 0 phút.';
    }
    if ($error) {
        beautycore_service_admin_redirect(array('error' => $error, 'id' => $service_id), 'beautycore-service-edit');
    }

    $allowed_statuses = array_keys(beautycore_service_statuses());
    $status = isset($_POST['status']) ? sanitize_key(wp_unslash($_POST['status'])) : 'draft';
    if (!in_array($status, $allowed_statuses, true)) {
        $status = 'draft';
    }
    $post_id = wp_insert_post(wp_slash(array(
        'ID'           => $service_id,
        'post_type'    => 'beautycore_service',
        'post_status'  => $status,
        'post_title'   => $title,
        'post_name'    => isset($_POST['slug']) && trim((string) $_POST['slug']) !== '' ? sanitize_title(wp_unslash($_POST['slug'])) : sanitize_title($title),
        'post_content' => isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '',
    )), true);
    if (is_wp_error($post_id)) {
        beautycore_service_admin_redirect(array('error' => $post_id->get_error_message(), 'id' => $service_id), 'beautycore-service-edit');
    }

    $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;
    wp_set_object_terms($post_id, $category_id ? array($category_id) : array(), 'beautycore_service_category');
    $meta = array(
        '_beautycore_price_original'  => $original,
        '_beautycore_price_sale'      => $sale,
        '_beautycore_duration'        => $duration,
        '_beautycore_booking_enabled' => !empty($_POST['booking_enabled']) ? '1' : '0',
        '_beautycore_booking_url'     => isset($_POST['booking_url']) ? esc_url_raw(wp_unslash($_POST['booking_url'])) : '',
        '_beautycore_booking_note'    => isset($_POST['booking_note']) ? sanitize_textarea_field(wp_unslash($_POST['booking_note'])) : '',
        '_beautycore_promo_start'     => $start,
        '_beautycore_promo_end'       => $end,
        '_beautycore_featured'        => !empty($_POST['featured']) ? '1' : '0',
        '_beautycore_homepage_order'  => isset($_POST['homepage_order']) ? absint($_POST['homepage_order']) : 0,
        '_beautycore_image_id'        => isset($_POST['image_id']) ? absint($_POST['image_id']) : 0,
        '_beautycore_image_url'       => isset($_POST['image_url']) ? esc_url_raw(wp_unslash($_POST['image_url'])) : '',
        '_beautycore_seo_title'        => isset($_POST['seo_title']) ? sanitize_text_field(wp_unslash($_POST['seo_title'])) : '',
        '_beautycore_seo_description'  => isset($_POST['seo_description']) ? sanitize_textarea_field(wp_unslash($_POST['seo_description'])) : '',
    );
    foreach ($meta as $key => $value) {
        update_post_meta($post_id, $key, $value);
    }
    $branches = !empty($_POST['branch_ids']) && is_array($_POST['branch_ids']) ? array_values(array_map('sanitize_text_field', wp_unslash($_POST['branch_ids']))) : array();
    $staff = !empty($_POST['staff_ids']) && is_array($_POST['staff_ids']) ? array_values(array_map('sanitize_text_field', wp_unslash($_POST['staff_ids']))) : array();
    update_post_meta($post_id, '_beautycore_branch_ids', $branches);
    update_post_meta($post_id, '_beautycore_staff_ids', $staff);
    clean_post_cache($post_id);
    if (function_exists('beautycore_audit_log')) {
        beautycore_audit_log($service_id ? 'service_updated' : 'service_created', array('title' => $title, 'status' => $status), 'beautycore_service', $post_id);
    }
    do_action('beautycore_service_saved', $post_id, (bool) $service_id);
    beautycore_service_admin_redirect(array('updated' => 1), 'beautycore-services');
}
add_action('admin_post_beautycore_save_service', 'beautycore_handle_save_service');

function beautycore_handle_toggle_service() {
    $service_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    if (!current_user_can('manage_beautycore_services') || !$service_id) {
        wp_die('Bạn không có quyền thực hiện thao tác này.');
    }
    check_admin_referer('beautycore_toggle_service_' . $service_id);
    $service = get_post($service_id);
    if (!$service || $service->post_type !== 'beautycore_service') {
        wp_die('Dịch vụ không hợp lệ.');
    }
    $new_status = $service->post_status === 'publish' ? 'hidden' : 'publish';
    wp_update_post(array('ID' => $service_id, 'post_status' => $new_status));
    if (function_exists('beautycore_audit_log')) {
        beautycore_audit_log('service_status_updated', array('status' => $new_status), 'beautycore_service', $service_id);
    }
    beautycore_service_admin_redirect(array('updated' => 1));
}
add_action('admin_post_beautycore_toggle_service', 'beautycore_handle_toggle_service');

function beautycore_handle_delete_service() {
    $service_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    if (!current_user_can('manage_beautycore_services') || !$service_id) {
        wp_die('Bạn không có quyền xóa dịch vụ.');
    }
    check_admin_referer('beautycore_delete_service_' . $service_id);
    $service = get_post($service_id);
    if (!$service || $service->post_type !== 'beautycore_service') {
        wp_die('Dịch vụ không hợp lệ.');
    }
    wp_delete_post($service_id, true);
    if (function_exists('beautycore_audit_log')) {
        beautycore_audit_log('service_deleted', array('title' => $service->post_title), 'beautycore_service', $service_id);
    }
    beautycore_service_admin_redirect(array('deleted' => 1));
}
add_action('admin_post_beautycore_delete_service', 'beautycore_handle_delete_service');

function beautycore_handle_save_service_category() {
    if (!current_user_can('manage_beautycore_services')) {
        wp_die('Bạn không có quyền quản lý danh mục.');
    }
    check_admin_referer('beautycore_save_service_category');
    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    if (!$name) {
        beautycore_service_admin_redirect(array('error' => 'Tên danh mục là bắt buộc.'));
    }
    $term = wp_insert_term($name, 'beautycore_service_category', array(
        'slug'        => isset($_POST['slug']) ? sanitize_title(wp_unslash($_POST['slug'])) : '',
        'description' => isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '',
    ));
    if (is_wp_error($term)) {
        beautycore_service_admin_redirect(array('error' => $term->get_error_message()));
    }
    update_term_meta((int) $term['term_id'], '_beautycore_category_eyebrow', isset($_POST['eyebrow']) ? sanitize_text_field(wp_unslash($_POST['eyebrow'])) : 'Beauty Core Menu');
    beautycore_service_admin_redirect(array('category_updated' => 1));
}
add_action('admin_post_beautycore_save_service_category', 'beautycore_handle_save_service_category');

function beautycore_handle_delete_service_category() {
    $term_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
    if (!current_user_can('manage_beautycore_services') || !$term_id) {
        wp_die('Bạn không có quyền xóa danh mục.');
    }
    check_admin_referer('beautycore_delete_service_category_' . $term_id);
    wp_delete_term($term_id, 'beautycore_service_category');
    beautycore_service_admin_redirect(array('category_updated' => 1));
}
add_action('admin_post_beautycore_delete_service_category', 'beautycore_handle_delete_service_category');
