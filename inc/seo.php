<?php
if (!defined('ABSPATH')) {
    exit;
}

function beautycore_current_description() {
    $config = beautycore_site_config();

    if (is_singular('beautycore_blog')) {
        $meta = beautycore_blog_meta();
        return $meta['description'] ?: wp_strip_all_tags(get_the_excerpt());
    }

    $descriptions = array(
        'gioi-thieu'            => 'Thông tin về không gian, định hướng phục vụ và cách liên hệ Beauty Core.',
        'dich-vu'               => 'Xem bảng giá gội đầu thư giãn, massage cổ vai gáy, massage toàn thân và các combo tại Beauty Core.',
        'lien-he'               => 'Thông tin liên hệ, giờ mở cửa và chỉ đường đến Beauty Core.',
        'cau-hoi-thuong-gap'    => 'Giải đáp về đặt lịch, thời lượng dịch vụ và những lưu ý trước khi đến Beauty Core.',
        'chinh-sach-bao-mat'    => 'Cách Beauty Core thu thập, sử dụng và bảo vệ thông tin cá nhân.',
        'dieu-khoan-su-dung'    => 'Điều khoản áp dụng khi sử dụng website Beauty Core.',
        'chinh-sach-cookie'     => 'Thông tin về cookie và lựa chọn quyền riêng tư trên website.',
        'mien-tru-trach-nhiem'  => 'Giới hạn trách nhiệm đối với nội dung và dịch vụ tại Beauty Core.',
        'chinh-sach-dat-lich'   => 'Quy định đặt lịch dịch vụ tại Beauty Core.',
        'chinh-sach-huy-doi-lich' => 'Hướng dẫn thay đổi hoặc hủy lịch hẹn tại Beauty Core.',
        'chinh-sach-bien-soan-noi-dung' => 'Cách Beauty Core xây dựng và cập nhật nội dung trên website.',
    );

    if (is_front_page()) {
        return 'Gội đầu thư giãn, massage cổ vai gáy và chăm sóc cơ thể tại Beauty Core, TP. Hồ Chí Minh.';
    }

    if (is_post_type_archive('beautycore_blog') || is_tax('beautycore_category')) {
        return 'Bài viết về gội đầu thư giãn, massage và những lưu ý khi trải nghiệm spa.';
    }

    if (is_page()) {
        $slug = get_post_field('post_name', get_queried_object_id());
        if (isset($descriptions[$slug])) {
            return $descriptions[$slug];
        }
    }

    return $config['description'];
}

function beautycore_document_title($parts) {
    unset($parts['site']);
    $config = beautycore_site_config();

    if (is_front_page()) {
        $parts['title'] = 'Beauty Core – Gội đầu dưỡng sinh và massage';
    } elseif (is_singular('beautycore_blog')) {
        $parts['title'] = get_the_title() . ' | ' . $config['name'];
    } elseif (is_post_type_archive('beautycore_blog')) {
        $parts['title'] = 'Blog chăm sóc và thư giãn | ' . $config['name'];
    } elseif (is_tax('beautycore_category')) {
        $parts['title'] = single_term_title('', false) . ' | Blog ' . $config['name'];
    } elseif (is_page('dich-vu')) {
        $parts['title'] = 'Dịch vụ gội đầu thư giãn và massage | ' . $config['name'];
    } elseif (is_page('cau-hoi-thuong-gap')) {
        $parts['title'] = 'Câu hỏi thường gặp | ' . $config['name'];
    }

    return $parts;
}
add_filter('document_title_parts', 'beautycore_document_title');

function beautycore_breadcrumbs() {
    $items = array(array('name' => 'Trang chủ', 'url' => home_url('/')));

    if (is_singular('beautycore_blog')) {
        $items[] = array('name' => 'Blog', 'url' => beautycore_blog_url());
        $items[] = array('name' => get_the_title(), 'url' => get_permalink());
    } elseif (is_tax('beautycore_category')) {
        $items[] = array('name' => 'Blog', 'url' => beautycore_blog_url());
        $items[] = array('name' => single_term_title('', false), 'url' => get_term_link(get_queried_object()));
    } elseif (!is_front_page()) {
        $items[] = array('name' => wp_get_document_title(), 'url' => get_permalink());
    }

    return $items;
}

function beautycore_faq_schema($faqs) {
    if (!$faqs) {
        return null;
    }

    $questions = array();
    foreach ($faqs as $faq) {
        $questions[] = array(
            '@type' => 'Question',
            'name' => $faq['question'],
            'acceptedAnswer' => array('@type' => 'Answer', 'text' => $faq['answer']),
        );
    }

    return array('@type' => 'FAQPage', 'mainEntity' => $questions);
}

function beautycore_output_head_meta() {
    $config = beautycore_site_config();
    $description = beautycore_current_description();
    $image = $config['og_image'];
    if (is_singular('beautycore_blog')) {
        $meta = beautycore_blog_meta();
        $image = $meta['image'] ?: $image;
    }

    $canonical = is_singular() ? get_permalink() : home_url(add_query_arg(array(), $GLOBALS['wp']->request ?? ''));
    if (is_front_page()) {
        $canonical = home_url('/');
    }

    echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr(wp_get_document_title()) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    echo '<meta property="og:image" content="' . esc_url(beautycore_asset_url($image)) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
    echo '<meta property="og:type" content="' . (is_singular('beautycore_blog') ? 'article' : 'website') . '">' . "\n";
    echo '<script async src="https://www.googletagmanager.com/gtag/js?id=G-67M1N3RC4E"></script>' . "\n";
    echo '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}window.gtag=gtag;gtag("js",new Date());gtag("config","G-67M1N3RC4E",{anonymize_ip:true});</script>' . "\n";
}
add_action('wp_head', 'beautycore_output_head_meta', 2);

function beautycore_output_structured_data() {
    $config = beautycore_site_config();
    $graph = array(
        array(
            '@context' => 'https://schema.org',
            '@type' => 'HealthAndBeautyBusiness',
            '@id' => trailingslashit($config['website']) . '#localbusiness',
            'name' => $config['name'],
            'description' => $config['description'],
            'url' => $config['website'],
            'telephone' => $config['phone'],
            'email' => $config['email'],
            'priceRange' => $config['price_range'],
            'image' => beautycore_asset_url($config['og_image']),
            'logo' => beautycore_asset_url('/images/logo.jpg'),
            'sameAs' => array($config['facebook_url']),
            'address' => array('@type' => 'PostalAddress', 'streetAddress' => '281/31/11 Lê Văn Sỹ, Phường Tân Sơn Hòa', 'addressLocality' => 'Hồ Chí Minh', 'addressCountry' => 'VN'),
            'geo' => array('@type' => 'GeoCoordinates', 'latitude' => $config['latitude'], 'longitude' => $config['longitude']),
            'openingHoursSpecification' => array(array('@type' => 'OpeningHoursSpecification', 'dayOfWeek' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), 'opens' => '09:00', 'closes' => '20:00')),
        ),
    );

    $breadcrumbs = beautycore_breadcrumbs();
    if (count($breadcrumbs) > 1) {
        $elements = array();
        foreach ($breadcrumbs as $index => $item) {
            $elements[] = array('@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['name'], 'item' => $item['url']);
        }
        $graph[] = array('@type' => 'BreadcrumbList', 'itemListElement' => $elements);
    }

    if (is_singular('beautycore_blog')) {
        $meta = beautycore_blog_meta();
        $graph[] = array('@type' => 'Article', 'headline' => get_the_title(), 'description' => $meta['description'], 'image' => beautycore_asset_url($meta['image']), 'datePublished' => date('c', strtotime($meta['published'])), 'dateModified' => date('c', strtotime($meta['updated'])), 'author' => array('@type' => 'Organization', 'name' => $meta['author']), 'publisher' => array('@type' => 'Organization', 'name' => $config['name']));
        $faq = beautycore_faq_schema($meta['faqs']);
        if ($faq) {
            $graph[] = $faq;
        }
    } elseif (is_page('cau-hoi-thuong-gap')) {
        $faq = beautycore_faq_schema(beautycore_booking_faqs());
        if ($faq) {
            $graph[] = $faq;
        }
    }

    echo '<script type="application/ld+json">' . wp_json_encode(array('@context' => 'https://schema.org', '@graph' => $graph), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}
add_action('wp_head', 'beautycore_output_structured_data', 3);
