<?php
get_header();
$current_page = max(1, (int) get_query_var('paged'));
$total_pages = (int) $wp_query->max_num_pages;
?>
<main class="blog-page container"><nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a><span aria-hidden="true">/</span><span>Blog</span></nav><header class="blog-header"><p class="section-label">Cẩm nang Beauty Core</p><h1>Chăm sóc và thư giãn</h1><p><?php echo $current_page > 1 ? 'Trang ' . esc_html($current_page) . ' trong các bài viết về chăm sóc và thư giãn.' : 'Những chia sẻ thực tế để bạn chuẩn bị tốt hơn trước và sau một buổi chăm sóc tại spa.'; ?></p></header>
    <div class="blog-grid"><?php if (have_posts()) : while (have_posts()) : the_post(); get_template_part('template-parts/blog-card'); endwhile; else : ?><p>Chưa có bài viết.</p><?php endif; ?></div>
    <?php if ($total_pages > 1) : ?><nav class="pagination" aria-label="Phân trang bài viết"><div><?php if ($current_page > 1) : ?><a href="<?php echo esc_url($current_page === 2 ? home_url('/blog/') : home_url('/blog/page/' . ($current_page - 1) . '/')); ?>" rel="prev">← Trang trước</a><?php else : ?><span aria-hidden="true">← Trang trước</span><?php endif; ?></div><div class="pagination__pages"><?php for ($page = 1; $page <= $total_pages; $page++) : ?><a href="<?php echo esc_url($page === 1 ? home_url('/blog/') : home_url('/blog/page/' . $page . '/')); ?>"<?php echo $page === $current_page ? ' aria-current="page"' : ''; ?>><?php echo esc_html($page); ?></a><?php endfor; ?></div><div><?php if ($current_page < $total_pages) : ?><a href="<?php echo esc_url(home_url('/blog/page/' . ($current_page + 1) . '/')); ?>" rel="next">Trang sau →</a><?php else : ?><span aria-hidden="true">Trang sau →</span><?php endif; ?></div></nav><?php endif; ?>
</main>
<?php get_footer(); ?>
