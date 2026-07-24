<?php
get_header();
$term = get_queried_object();
?>
<main class="blog-page container"><nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a><span aria-hidden="true">/</span><a href="<?php echo esc_url(home_url('/blog/')); ?>">Blog</a><span aria-hidden="true">/</span><span><?php echo esc_html($term->name); ?></span></nav><header class="blog-header"><p class="section-label">Danh mục Blog</p><h1><?php echo esc_html($term->name); ?></h1><p>Các bài viết tham khảo về chăm sóc và thư giãn tại Beauty Core.</p></header><div class="blog-grid"><?php if (have_posts()) : while (have_posts()) : the_post(); get_template_part('template-parts/blog-card'); endwhile; else : ?><p>Chưa có bài viết trong danh mục này.</p><?php endif; ?></div>
    <?php the_posts_pagination(array('mid_size' => 1, 'prev_text' => '← Trang trước', 'next_text' => 'Trang sau →', 'class' => 'pagination')); ?>
</main>
<?php get_footer(); ?>
