<?php
if (!defined('ABSPATH')) {
    exit;
}

function beautycore_register_content_types() {
    register_post_type('beautycore_blog', array(
        'labels' => array(
            'name'          => 'Bài viết',
            'singular_name' => 'Bài viết',
            'add_new_item'  => 'Thêm bài viết',
            'edit_item'     => 'Sửa bài viết',
        ),
        'public'       => true,
        'show_in_rest' => true,
        'show_in_menu' => 'beautycore-dashboard',
        'menu_icon'    => 'dashicons-welcome-write-blog',
        'supports'     => array('title', 'editor', 'thumbnail', 'excerpt', 'author'),
        'has_archive'  => 'blog',
        'rewrite'      => array('slug' => 'blog', 'with_front' => false),
    ));

    register_taxonomy('beautycore_category', 'beautycore_blog', array(
        'labels' => array(
            'name'          => 'Danh mục blog',
            'singular_name' => 'Danh mục blog',
        ),
        'public'       => true,
        'show_in_rest' => true,
        'hierarchical' => true,
        'rewrite'      => array('slug' => 'danh-muc', 'with_front' => false),
    ));
}
add_action('init', 'beautycore_register_content_types');

function beautycore_blog_meta($post_id = 0) {
    $post_id = $post_id ?: get_the_ID();
    $faqs = get_post_meta($post_id, '_beautycore_faqs', true);
    $image_id = (int) get_post_meta($post_id, '_beautycore_image_id', true);
    $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'full') : '';

    return array(
        'description' => (string) get_post_meta($post_id, '_beautycore_description', true),
        'image'       => (string) ($image_url ?: get_post_meta($post_id, '_beautycore_image', true)),
        'image_id'    => $image_id,
        'image_alt'   => (string) get_post_meta($post_id, '_beautycore_image_alt', true),
        'author'      => (string) (get_post_meta($post_id, '_beautycore_author', true) ?: 'Beauty Core'),
        'published'   => (string) (get_post_meta($post_id, '_beautycore_published_at', true) ?: get_the_date('Y-m-d', $post_id)),
        'updated'     => (string) (get_post_meta($post_id, '_beautycore_updated_at', true) ?: get_the_modified_date('Y-m-d', $post_id)),
        'faqs'        => is_array($faqs) ? $faqs : array(),
    );
}

function beautycore_format_date($date) {
    $timestamp = strtotime($date);

    if (!$timestamp) {
        return $date;
    }

    return date_i18n('j', $timestamp) . ' tháng ' . date_i18n('n', $timestamp) . ' năm ' . date_i18n('Y', $timestamp);
}

function beautycore_slugify($text) {
    return sanitize_title($text);
}

function beautycore_parse_blog_file($file) {
    $raw = file_get_contents($file);
    $parts = preg_split('/^---\s*$/m', (string) $raw, 3);

    if (count($parts) < 3) {
        return false;
    }

    $frontmatter = $parts[1];
    $body = trim($parts[2]);
    $value = function ($key, $default = '') use ($frontmatter) {
        $pattern = '/^' . preg_quote($key, '/') . ':\s*(?:"([^"]*)"|([^\r\n]*))\r?$/mu';
        if (!preg_match($pattern, $frontmatter, $matches)) {
            return $default;
        }

        return trim($matches[1] !== '' ? $matches[1] : $matches[2]);
    };

    $faqs = array();
    if (preg_match_all('/-\s+question:\s*"([^"]*)"\s*\r?\n\s+answer:\s*"([^"]*)"/u', $frontmatter, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $faqs[] = array('question' => $match[1], 'answer' => $match[2]);
        }
    }

    return array(
        'slug'        => sanitize_title(basename($file, '.md')),
        'title'       => $value('title'),
        'description' => $value('description'),
        'published'   => $value('publishedAt'),
        'updated'     => $value('updatedAt'),
        'author'      => $value('author', 'Beauty Core'),
        'category'    => $value('category', 'Chăm sóc và thư giãn'),
        'image'       => $value('image'),
        'image_alt'   => $value('imageAlt'),
        'faqs'        => $faqs,
        'draft'       => $value('draft', 'false') === 'true',
        'body'        => $body,
    );
}

function beautycore_seed_pages() {
    $pages = array(
        array('slug' => 'trang-chu', 'title' => 'Trang chủ'),
        array('slug' => 'gioi-thieu', 'title' => 'Giới thiệu'),
        array('slug' => 'dich-vu', 'title' => 'Dịch vụ'),
        array('slug' => 'lien-he', 'title' => 'Liên hệ'),
        array('slug' => 'cau-hoi-thuong-gap', 'title' => 'Câu hỏi thường gặp'),
    );

    foreach (beautycore_legal_pages() as $page) {
        $pages[] = array('slug' => $page['slug'], 'title' => $page['title']);
    }

    foreach ($pages as $page) {
        $existing = get_page_by_path($page['slug']);
        if (!$existing) {
            wp_insert_post(array(
                'post_type'    => 'page',
                'post_status'  => 'publish',
                'post_title'   => $page['title'],
                'post_name'    => $page['slug'],
                'post_content' => '',
            ));
        }
    }

    $home = get_page_by_path('trang-chu');
    if ($home instanceof WP_Post) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $home->ID);
    }
}

function beautycore_seed_blog_posts() {
    $files = glob(BEAUTYCORE_THEME_DIR . '/content/blog/*.md');

    foreach ((array) $files as $file) {
        $data = beautycore_parse_blog_file($file);
        if (!$data || !$data['title']) {
            continue;
        }

        $existing = get_page_by_path($data['slug'], OBJECT, 'beautycore_blog');
        $post_args = array(
            'post_type'    => 'beautycore_blog',
            'post_status'  => $data['draft'] ? 'draft' : 'publish',
            'post_title'   => $data['title'],
            'post_name'    => $data['slug'],
            'post_content' => $data['body'],
            'post_excerpt' => $data['description'],
            'post_date'    => $data['published'] . ' 12:00:00',
        );

        if ($existing instanceof WP_Post) {
            $post_args['ID'] = $existing->ID;
        }

        $post_id = wp_insert_post(wp_slash($post_args), true);
        if (is_wp_error($post_id)) {
            continue;
        }

        wp_set_object_terms($post_id, $data['category'], 'beautycore_category', false);
        update_post_meta($post_id, '_beautycore_description', $data['description']);
        update_post_meta($post_id, '_beautycore_image', $data['image']);
        update_post_meta($post_id, '_beautycore_image_alt', $data['image_alt']);
        update_post_meta($post_id, '_beautycore_author', $data['author']);
        update_post_meta($post_id, '_beautycore_published_at', $data['published']);
        update_post_meta($post_id, '_beautycore_updated_at', $data['updated']);
        update_post_meta($post_id, '_beautycore_faqs', $data['faqs']);

        if (function_exists('beautycore_import_media_file')) {
            $image_id = beautycore_import_media_file($data['image'], $data['title'], $data['image_alt']);
            if ($image_id) {
                update_post_meta($post_id, '_beautycore_image_id', $image_id);
                set_post_thumbnail($post_id, $image_id);
            }
        }
    }
}

function beautycore_maybe_seed_content() {
    if (get_option('beautycore_content_seeded')) {
        return;
    }

    beautycore_seed_pages();
    beautycore_seed_blog_posts();
    update_option('beautycore_content_seeded', current_time('mysql'));
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'beautycore_maybe_seed_content');

function beautycore_inline_markdown($text) {
    $links = array();
    $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/u', function ($match) use (&$links) {
        $key = '__BEAUTYCORE_LINK_' . count($links) . '__';
        $links[$key] = '<a href="' . esc_url($match[2]) . '">' . esc_html($match[1]) . '</a>';
        return $key;
    }, (string) $text);

    $text = esc_html($text);
    $text = preg_replace('/\*\*(.+?)\*\*/us', '<strong>$1</strong>', $text);
    $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/u', '<em>$1</em>', $text);

    foreach ($links as $key => $link) {
        $text = str_replace(esc_html($key), $link, $text);
    }

    return $text;
}

function beautycore_render_markdown($markdown) {
    $lines = preg_split('/\r?\n/', trim((string) $markdown));
    $html = '';
    $paragraph = array();
    $list_type = '';

    $flush_paragraph = function () use (&$html, &$paragraph) {
        if ($paragraph) {
            $html .= '<p>' . beautycore_inline_markdown(implode("\n", $paragraph)) . '</p>';
            $paragraph = array();
        }
    };
    $close_list = function () use (&$html, &$list_type) {
        if ($list_type) {
            $html .= '</' . $list_type . '>';
            $list_type = '';
        }
    };

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '') {
            $flush_paragraph();
            $close_list();
            continue;
        }

        if (preg_match('/^(#{2,3})\s+(.+)$/u', $trimmed, $heading)) {
            $flush_paragraph();
            $close_list();
            $text = trim($heading[2]);
            $slug = beautycore_slugify($text);
            $level = strlen($heading[1]);
            $html .= '<h' . $level . ' id="' . esc_attr($slug) . '">' . beautycore_inline_markdown($text) . '</h' . $level . '>';
            continue;
        }

        if (preg_match('/^[-*+]\s+(.+)$/u', $trimmed, $item)) {
            $flush_paragraph();
            if ($list_type !== 'ul') {
                $close_list();
                $html .= '<ul>';
                $list_type = 'ul';
            }
            $html .= '<li>' . beautycore_inline_markdown($item[1]) . '</li>';
            continue;
        }

        if (preg_match('/^\d+\.\s+(.+)$/u', $trimmed, $item)) {
            $flush_paragraph();
            if ($list_type !== 'ol') {
                $close_list();
                $html .= '<ol>';
                $list_type = 'ol';
            }
            $html .= '<li>' . beautycore_inline_markdown($item[1]) . '</li>';
            continue;
        }

        $close_list();
        $paragraph[] = $trimmed;
    }

    $flush_paragraph();
    $close_list();

    return $html;
}

function beautycore_extract_headings($markdown) {
    $headings = array();

    foreach (preg_split('/\r?\n/', (string) $markdown) as $line) {
        if (preg_match('/^(#{2,3})\s+(.+)$/u', trim($line), $match)) {
            $text = trim($match[2]);
            $headings[] = array(
                'depth' => strlen($match[1]),
                'slug'  => beautycore_slugify($text),
                'text'  => $text,
            );
        }
    }

    return $headings;
}

function beautycore_legal_pages() {
    return array(
        array(
            'slug' => 'chinh-sach-bao-mat',
            'title' => 'Chính sách bảo mật',
            'description' => 'Cách Beauty Core thu thập, sử dụng và bảo vệ thông tin cá nhân.',
            'updated' => '13/07/2026',
            'sections' => array(
                array('heading' => 'Thông tin có thể được thu thập', 'paragraphs' => array('Khi khách hàng liên hệ hoặc đặt lịch qua nền tảng đặt lịch bên thứ ba, Beauty Core có thể nhận được thông tin như họ tên, số điện thoại, email và nội dung yêu cầu. Google Analytics 4 có thể thu thập dữ liệu sử dụng không trực tiếp định danh như lượt xem trang, loại thiết bị, trình duyệt và tương tác với website.')),
                array('heading' => 'Mục đích sử dụng', 'paragraphs' => array('Thông tin được dùng để phản hồi yêu cầu, xác nhận lịch hẹn, hỗ trợ khách hàng và cải thiện chất lượng phục vụ. Dữ liệu phân tích được dùng để hiểu cách website được sử dụng và cải thiện nội dung. Chúng tôi không bán thông tin cá nhân cho bên thứ ba.')),
                array('heading' => 'Lưu trữ và chia sẻ', 'paragraphs' => array('Thông tin đặt lịch được xử lý theo chính sách của nền tảng EasySalon. Chúng tôi chỉ chia sẻ thông tin khi cần thiết để thực hiện lịch hẹn, theo yêu cầu pháp luật hoặc khi có sự đồng ý của khách hàng.')),
                array('heading' => 'Quảng cáo và lựa chọn quyền riêng tư', 'paragraphs' => array('Nếu website triển khai quảng cáo của Google trong tương lai, thông tin về công nghệ quảng cáo, các đối tác liên quan và lựa chọn của người dùng sẽ được thông báo rõ ràng. Với người dùng tại các khu vực yêu cầu sự đồng ý theo quy định, website sẽ sử dụng cơ chế quản lý sự đồng ý phù hợp trước khi lưu trữ hoặc truy cập dữ liệu cho mục đích quảng cáo cá nhân hóa.')),
                array('heading' => 'Quyền của bạn', 'paragraphs' => array('Bạn có thể yêu cầu xem, chỉnh sửa hoặc xóa thông tin mà Beauty Core đang lưu giữ bằng cách liên hệ qua email hoặc số điện thoại công bố trên website.')),
            ),
        ),
        array(
            'slug' => 'dieu-khoan-su-dung',
            'title' => 'Điều khoản sử dụng',
            'description' => 'Điều khoản áp dụng khi sử dụng website Beauty Core.',
            'updated' => '13/07/2026',
            'sections' => array(
                array('heading' => 'Phạm vi', 'paragraphs' => array('Website cung cấp thông tin tham khảo về dịch vụ, giá và cách đặt lịch tại Beauty Core. Việc truy cập website đồng nghĩa với việc bạn đồng ý các điều khoản này.')),
                array('heading' => 'Thông tin dịch vụ', 'paragraphs' => array('Giá, thời lượng và quy trình có thể thay đổi theo tình trạng thực tế và được xác nhận khi đặt lịch. Nội dung trên website không phải là chẩn đoán hoặc chỉ định y khoa.')),
                array('heading' => 'Sở hữu nội dung', 'paragraphs' => array('Nội dung, hình ảnh và nhận diện trên website thuộc Beauty Core hoặc bên cấp phép. Không sao chép hay sử dụng lại khi chưa được chấp thuận.')),
            ),
        ),
        array(
            'slug' => 'chinh-sach-cookie',
            'title' => 'Chính sách cookie',
            'description' => 'Thông tin về cookie và lựa chọn quyền riêng tư trên website.',
            'updated' => '13/07/2026',
            'sections' => array(
                array('heading' => 'Cookie là gì', 'paragraphs' => array('Cookie là tệp nhỏ được trình duyệt lưu để ghi nhớ một số lựa chọn hoặc hỗ trợ hoạt động của website.')),
                array('heading' => 'Cách chúng tôi sử dụng', 'paragraphs' => array('Website sử dụng lưu trữ cần thiết để ghi nhớ thông báo cookie. Google Analytics 4 được dùng để đo lường lượt xem trang và tương tác tổng quát; chúng tôi không gửi tên, số điện thoại hoặc email của bạn vào Analytics.')),
                array('heading' => 'Quản lý cookie', 'paragraphs' => array('Bạn có thể xóa dữ liệu website hoặc chặn cookie trong phần cài đặt trình duyệt. Việc này có thể ảnh hưởng đến một số chức năng hoặc khiến thông báo cookie xuất hiện lại.')),
                array('heading' => 'Khi có quảng cáo', 'paragraphs' => array('Nếu website sử dụng Google AdSense, một nền tảng quản lý sự đồng ý được Google chứng nhận sẽ được hiển thị cho người dùng tại những khu vực áp dụng. Bạn có thể chọn, từ chối hoặc thay đổi lựa chọn về các mục đích quảng cáo theo hướng dẫn trong thông báo đó.')),
            ),
        ),
        array(
            'slug' => 'mien-tru-trach-nhiem',
            'title' => 'Miễn trừ trách nhiệm',
            'description' => 'Giới hạn trách nhiệm đối với nội dung và dịch vụ tại Beauty Core.',
            'updated' => '13/07/2026',
            'sections' => array(
                array('heading' => 'Thông tin chăm sóc và thư giãn', 'paragraphs' => array('Các dịch vụ tại Beauty Core nhằm mục đích chăm sóc và thư giãn, không thay thế cho việc khám, chẩn đoán hoặc điều trị y khoa. Nếu có triệu chứng đau kéo dài, chấn thương, bệnh nền, đang mang thai hoặc đang điều trị, bạn nên hỏi ý kiến nhân viên y tế trước khi sử dụng dịch vụ.')),
                array('heading' => 'Nội dung website', 'paragraphs' => array('Chúng tôi nỗ lực giữ thông tin chính xác nhưng không bảo đảm toàn bộ nội dung luôn đầy đủ hoặc cập nhật tại mọi thời điểm. Vui lòng liên hệ trực tiếp để xác nhận trước khi đặt lịch.')),
            ),
        ),
        array(
            'slug' => 'chinh-sach-dat-lich',
            'title' => 'Chính sách đặt lịch',
            'description' => 'Quy định đặt lịch dịch vụ tại Beauty Core.',
            'updated' => '13/07/2026',
            'sections' => array(
                array('heading' => 'Xác nhận lịch hẹn', 'paragraphs' => array('Khách hàng có thể đặt lịch qua EasySalon hoặc liên hệ trực tiếp. Lịch hẹn chỉ được xem là xác nhận sau khi nhận được phản hồi từ Beauty Core hoặc nền tảng đặt lịch.')),
                array('heading' => 'Thông tin cần cung cấp', 'paragraphs' => array('Vui lòng cung cấp thông tin liên hệ chính xác và thông báo trước các lưu ý liên quan đến sức khỏe, dị ứng hoặc nhu cầu đặc biệt để nhân viên hỗ trợ phù hợp.')),
                array('heading' => 'Thay đổi dịch vụ', 'paragraphs' => array('Dịch vụ và thời lượng có thể được điều chỉnh sau khi trao đổi với khách hàng, tùy thuộc vào tình trạng phục vụ thực tế.')),
            ),
        ),
        array(
            'slug' => 'chinh-sach-huy-doi-lich',
            'title' => 'Chính sách hủy, đổi lịch',
            'description' => 'Hướng dẫn thay đổi hoặc hủy lịch hẹn tại Beauty Core.',
            'updated' => '13/07/2026',
            'sections' => array(
                array('heading' => 'Đổi hoặc hủy lịch', 'paragraphs' => array('Nếu cần thay đổi hoặc hủy lịch, vui lòng liên hệ Beauty Core sớm nhất có thể để chúng tôi hỗ trợ sắp xếp lại khung giờ.')),
                array('heading' => 'Đến trễ', 'paragraphs' => array('Khách đến trễ có thể cần rút ngắn thời lượng phục vụ để không ảnh hưởng đến lịch hẹn sau. Chúng tôi sẽ cố gắng hỗ trợ trong khả năng thực tế.')),
            ),
        ),
        array(
            'slug' => 'chinh-sach-bien-soan-noi-dung',
            'title' => 'Chính sách biên soạn nội dung',
            'description' => 'Cách Beauty Core xây dựng và cập nhật nội dung trên website.',
            'updated' => '13/07/2026',
            'sections' => array(
                array('heading' => 'Mục đích nội dung', 'paragraphs' => array('Website cung cấp thông tin về dịch vụ, cách đặt lịch và các bài viết tham khảo về chăm sóc tóc, thư giãn và trải nghiệm đi spa. Nội dung được xây dựng để giúp khách hàng hiểu rõ hơn trước khi lựa chọn dịch vụ.')),
                array('heading' => 'Biên soạn và rà soát', 'paragraphs' => array('Nội dung được biên soạn bởi đội ngũ Beauty Core, dựa trên thông tin dịch vụ đang cung cấp và các lưu ý chăm sóc phổ biến. Chúng tôi rà soát thông tin về giá, thời lượng, lịch hẹn và liên hệ khi có thay đổi.')),
                array('heading' => 'Giới hạn chuyên môn', 'paragraphs' => array('Các bài viết chỉ mang tính tham khảo, không thay thế chẩn đoán hoặc điều trị y khoa. Với triệu chứng kéo dài, chấn thương, bệnh nền, đang mang thai hoặc đang điều trị, khách hàng nên tham khảo ý kiến nhân viên y tế phù hợp trước khi sử dụng dịch vụ.')),
                array('heading' => 'Cập nhật và phản hồi', 'paragraphs' => array('Nếu phát hiện thông tin cần điều chỉnh hoặc muốn góp ý về nội dung, bạn có thể liên hệ Beauty Core qua email hoặc số điện thoại công bố trên website. Chúng tôi sẽ xem xét và cập nhật khi cần thiết.')),
            ),
        ),
    );
}

function beautycore_get_legal_page($slug) {
    foreach (beautycore_legal_pages() as $page) {
        if ($page['slug'] === $slug) {
            return $page;
        }
    }

    return null;
}

function beautycore_set_blog_archive_size($query) {
    if (!is_admin() && $query->is_main_query() && ($query->is_post_type_archive('beautycore_blog') || $query->is_tax('beautycore_category'))) {
        $query->set('posts_per_page', 6);
    }
}
add_action('pre_get_posts', 'beautycore_set_blog_archive_size');
