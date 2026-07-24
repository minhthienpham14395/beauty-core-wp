<?php get_header(); ?>
<main class="not-found container"><p class="section-label">Lỗi 404</p><h1>Không tìm thấy trang bạn cần</h1><p>Đường dẫn có thể đã thay đổi hoặc không còn tồn tại. Bạn có thể quay lại trang chủ, xem dịch vụ hoặc đọc các bài viết mới nhất.</p><div class="not-found__actions"><a href="<?php echo esc_url(beautycore_home_path()); ?>" class="btn btn-primary">Về trang chủ</a><a href="<?php echo esc_url(home_url('/blog/')); ?>" class="btn btn-secondary">Xem Blog</a></div></main>
<?php get_footer(); ?>
