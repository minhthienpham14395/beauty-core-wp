<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Import source assets into the WordPress Media Library.
 *
 * The source path is stored on each attachment so the importer is safe to
 * run again after new assets are added to the theme.
 */
function beautycore_normalize_source_path($path) {
    $path = str_replace('\\', '/', (string) $path);
    $path = preg_replace('#^https?://#i', '', $path) === $path ? $path : '';
    $path = ltrim($path, '/');
    $path = preg_replace('#^public/#i', '', $path);

    if ($path === '' || strpos($path, '..') !== false) {
        return '';
    }

    return $path;
}

function beautycore_attachment_id_for_source($path) {
    $source = beautycore_normalize_source_path($path);

    if (!$source) {
        return 0;
    }

    static $map = null;
    if ($map === null) {
        $map = get_option('beautycore_media_map', array());
        $map = is_array($map) ? $map : array();
    }

    if (!empty($map[$source])) {
        $mapped_id = (int) $map[$source];
        if (get_post_type($mapped_id) === 'attachment') {
            return $mapped_id;
        }

        unset($map[$source]);
    }

    $attachments = get_posts(array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_beautycore_source_file',
        'meta_value'     => $source,
    ));

    $map[$source] = !empty($attachments) ? (int) $attachments[0] : 0;
    return $map[$source];
}

function beautycore_media_mime_type($filename) {
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $types = array(
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jfif' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'svg'  => 'image/svg+xml',
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'mov'  => 'video/quicktime',
        'ogg'  => 'video/ogg',
    );

    return isset($types[$extension]) ? $types[$extension] : '';
}

function beautycore_import_media_file($source_path, $title = '', $alt = '') {
    $source = beautycore_normalize_source_path($source_path);
    if (!$source) {
        return 0;
    }

    $existing_id = beautycore_attachment_id_for_source($source);
    if ($existing_id) {
        if ($alt !== '') {
            update_post_meta($existing_id, '_wp_attachment_image_alt', $alt);
        }
        return $existing_id;
    }

    $public_dir = realpath(BEAUTYCORE_THEME_DIR . '/public');
    $source_file = realpath(BEAUTYCORE_THEME_DIR . '/public/' . $source);
    if (!$public_dir || !$source_file || strpos($source_file, $public_dir . DIRECTORY_SEPARATOR) !== 0 || !is_file($source_file)) {
        return 0;
    }

    $upload = wp_upload_dir();
    if (!empty($upload['error']) || empty($upload['path']) || !wp_mkdir_p($upload['path'])) {
        return 0;
    }

    $filename = wp_unique_filename($upload['path'], basename($source_file));
    $destination = trailingslashit($upload['path']) . $filename;
    if (!copy($source_file, $destination)) {
        return 0;
    }

    $mime = wp_check_filetype($filename, null);
    $mime_type = !empty($mime['type']) ? $mime['type'] : beautycore_media_mime_type($filename);
    if (!$mime_type) {
        @unlink($destination);
        return 0;
    }

    $attachment_title = $title ?: pathinfo($source_file, PATHINFO_FILENAME);
    $attachment_id = wp_insert_attachment(wp_slash(array(
        'post_mime_type' => $mime_type,
        'post_title'     => $attachment_title,
        'post_status'    => 'inherit',
    )), $destination, 0);

    if (is_wp_error($attachment_id)) {
        @unlink($destination);
        return 0;
    }

    update_post_meta($attachment_id, '_beautycore_source_file', $source);
    if ($alt !== '') {
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
    }

    if (wp_attachment_is_image($attachment_id)) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata($attachment_id, $destination);
        if (!is_wp_error($metadata) && $metadata) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }
    }

    $map = get_option('beautycore_media_map', array());
    $map = is_array($map) ? $map : array();
    $map[$source] = (int) $attachment_id;
    update_option('beautycore_media_map', $map, false);

    return (int) $attachment_id;
}

function beautycore_import_media_library() {
    $public_dir = realpath(BEAUTYCORE_THEME_DIR . '/public');
    if (!$public_dir || !class_exists('RecursiveDirectoryIterator')) {
        return array();
    }

    $allowed = array('jpg', 'jpeg', 'jfif', 'png', 'gif', 'webp', 'avif', 'svg', 'mp4', 'webm', 'mov', 'ogg');
    $map = get_option('beautycore_media_map', array());
    $map = is_array($map) ? $map : array();
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($public_dir, FilesystemIterator::SKIP_DOTS));

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $extension = strtolower($file->getExtension());
        if (!in_array($extension, $allowed, true)) {
            continue;
        }

        $source = str_replace('\\', '/', substr($file->getPathname(), strlen($public_dir) + 1));
        $attachment_id = beautycore_import_media_file($source);
        if ($attachment_id) {
            $map[$source] = $attachment_id;
        }
    }

    update_option('beautycore_media_map', $map, false);
    update_option('beautycore_media_imported', current_time('mysql'), false);
    return $map;
}

function beautycore_import_site_content() {
    beautycore_seed_pages();
    beautycore_import_media_library();
    beautycore_seed_blog_posts();
    update_option('beautycore_content_import_version', BEAUTYCORE_CONTENT_VERSION, false);
    update_option('beautycore_content_seeded', current_time('mysql'), false);
    flush_rewrite_rules();
}

function beautycore_maybe_import_site_content() {
    if (get_option('beautycore_content_import_version') === BEAUTYCORE_CONTENT_VERSION) {
        return;
    }

    if (get_option('beautycore_content_import_running')) {
        return;
    }

    update_option('beautycore_content_import_running', 1, false);
    beautycore_import_site_content();
    delete_option('beautycore_content_import_running');
}

function beautycore_register_import_page() {
    add_management_page(
        'Import Beauty Core',
        'Beauty Core Import',
        'manage_options',
        'beautycore-import',
        'beautycore_render_import_page'
    );
}
add_action('admin_menu', 'beautycore_register_import_page');

function beautycore_render_import_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>Import nội dung Beauty Core</h1>
        <p>Đồng bộ bài viết, ảnh và video từ theme vào WordPress Media Library.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="beautycore_import_content">
            <?php wp_nonce_field('beautycore_import_content'); ?>
            <?php submit_button('Chạy import / đồng bộ lại'); ?>
        </form>
        <?php if (get_option('beautycore_content_import_version')) : ?>
            <p>Đã đồng bộ lần cuối: <?php echo esc_html(get_option('beautycore_content_seeded')); ?></p>
        <?php endif; ?>
    </div>
    <?php
}

function beautycore_handle_manual_import() {
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền thực hiện thao tác này.');
    }

    check_admin_referer('beautycore_import_content');
    beautycore_import_site_content();
    wp_safe_redirect(add_query_arg(array('page' => 'beautycore-import', 'imported' => '1'), admin_url('tools.php')));
    exit;
}
add_action('admin_post_beautycore_import_content', 'beautycore_handle_manual_import');

function beautycore_ensure_pretty_permalinks() {
    if (get_option('permalink_structure') !== '') {
        return;
    }

    update_option('permalink_structure', '/%postname%/');
    flush_rewrite_rules();
}

add_action('admin_init', 'beautycore_ensure_pretty_permalinks', 5);
add_action('after_switch_theme', 'beautycore_maybe_import_site_content');
add_action('admin_init', 'beautycore_maybe_import_site_content', 20);
