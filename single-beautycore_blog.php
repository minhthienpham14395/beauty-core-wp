<?php
get_header();
$post_id = get_the_ID();
$meta = beautycore_blog_meta($post_id);
$raw_content = get_post_field('post_content', $post_id);
$headings = beautycore_extract_headings($raw_content);
$terms = get_the_terms($post_id, 'beautycore_category');
$term = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;
?>
<main class="article-page container"><nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a><span aria-hidden="true">/</span><a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a><span aria-hidden="true">/</span><span><?php the_title(); ?></span></nav>
    <article class="article-content"><header class="article-header"><?php if ($term) : ?><p class="article-category"><a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a></p><?php endif; ?><h1><?php the_title(); ?></h1><p class="article-lead"><?php echo esc_html($meta['description']); ?></p><div class="article-meta"><span>Tác giả: <?php echo esc_html($meta['author']); ?></span><span>Đăng: <?php echo esc_html(beautycore_format_date($meta['published'])); ?></span><span>Cập nhật: <?php echo esc_html(beautycore_format_date($meta['updated'])); ?></span></div></header>
        <?php if ($meta['image']) : ?><img class="article-featured-image" src="<?php echo esc_url(beautycore_asset_url($meta['image'])); ?>" alt="<?php echo esc_attr($meta['image_alt'] ?: get_the_title()); ?>" width="1200" height="675"><?php endif; ?>
        <?php if ($headings) : ?><nav class="table-of-contents" aria-label="Mục lục bài viết"><p>Mục lục</p><ol><?php foreach ($headings as $heading) : ?><li<?php echo $heading['depth'] === 3 ? ' class="table-of-contents__subitem"' : ''; ?>><a href="#<?php echo esc_attr($heading['slug']); ?>"><?php echo esc_html($heading['text']); ?></a></li><?php endforeach; ?></ol></nav><?php endif; ?>
        <div class="article-body"><?php echo beautycore_render_markdown($raw_content); ?></div>
        <?php if (!empty($meta['faqs'])) : ?><section class="article-faq"><h2>Câu hỏi thường gặp</h2><?php foreach ($meta['faqs'] as $faq) : ?><details><summary><?php echo esc_html($faq['question']); ?></summary><p><?php echo esc_html($faq['answer']); ?></p></details><?php endforeach; ?></section><?php endif; ?>
        <aside class="article-disclaimer">Nội dung mang tính tham khảo về chăm sóc và thư giãn, không thay thế tư vấn, chẩn đoán hoặc điều trị y khoa.</aside>
        <aside class="author-box"><img src="<?php echo esc_url(beautycore_asset_url('/images/logo.jpg')); ?>" alt="Logo Beauty Core" width="1000" height="1000" loading="lazy"><div><p class="author-box__label">Nội dung được biên soạn và rà soát bởi</p><h2><?php echo esc_html($meta['author']); ?></h2><p>Thông tin được cập nhật theo dịch vụ tại Beauty Core và chỉ nhằm mục đích tham khảo, không thay thế tư vấn y khoa.</p><a href="<?php echo esc_url(beautycore_page_url('chinh-sach-bien-soan-noi-dung')); ?>">Xem chính sách biên soạn nội dung</a></div></aside>
        <?php
        if ($term) :
            $related = new WP_Query(array('post_type' => 'beautycore_blog', 'posts_per_page' => 3, 'post__not_in' => array($post_id), 'tax_query' => array(array('taxonomy' => 'beautycore_category', 'field' => 'term_id', 'terms' => $term->term_id))));
            if ($related->have_posts()) :
        ?><section class="related-posts"><h2>Bài viết liên quan</h2><div class="blog-grid"><?php while ($related->have_posts()) : $related->the_post(); get_template_part('template-parts/blog-card'); endwhile; ?></div></section><?php endif; wp_reset_postdata(); endif; ?>
    </article>
</main>
<?php get_footer(); ?>
