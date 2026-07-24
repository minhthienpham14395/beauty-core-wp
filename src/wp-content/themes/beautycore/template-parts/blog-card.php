<?php
$post_id = get_the_ID();
$meta = beautycore_blog_meta($post_id);
$terms = get_the_terms($post_id, 'beautycore_category');
$term = (!is_wp_error($terms) && !empty($terms)) ? $terms[0] : null;
$image = $meta['image'] ?: '/images/hero-default.jpg';
?>
<article class="blog-card">
    <a href="<?php the_permalink(); ?>" class="blog-card__image"><img src="<?php echo esc_url(beautycore_asset_url($image)); ?>" alt="<?php echo esc_attr($meta['image_alt'] ?: get_the_title()); ?>" width="1200" height="675" loading="lazy"></a>
    <div class="blog-card__content">
        <?php if ($term) : ?><p class="blog-card__category"><a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a></p><?php endif; ?>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <p><?php echo esc_html($meta['description'] ?: get_the_excerpt()); ?></p>
        <div class="blog-card__meta"><span><?php echo esc_html($meta['author']); ?></span><span><?php echo esc_html(beautycore_format_date($meta['published'])); ?></span></div>
        <a class="blog-card__link" href="<?php the_permalink(); ?>">Đọc bài viết <span aria-hidden="true">→</span></a>
    </div>
</article>
