<?php
get_header();
?>
<main class="page-content container"><nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url(beautycore_home_path()); ?>">Trang chủ</a><span aria-hidden="true">/</span><span><?php echo esc_html(wp_get_document_title()); ?></span></nav><article>
    <header class="page-header"><h1><?php echo esc_html(wp_get_document_title()); ?></h1></header>
    <?php if (have_posts()) : while (have_posts()) : the_post(); ?><section class="prose-section"><h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2><?php the_excerpt(); ?></section><?php endwhile; else : ?><section class="prose-section"><p>Chưa có nội dung.</p></section><?php endif; ?>
</article></main>
<?php get_footer(); ?>
