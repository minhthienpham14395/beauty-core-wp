<?php
get_header();
?>
<main>
    <?php get_template_part('template-parts/home/hero'); ?>
    <?php get_template_part('template-parts/home/services'); ?>
    <aside class="site-disclaimer container">Các dịch vụ tại Beauty Core nhằm mục đích chăm sóc và thư giãn, không thay thế cho việc khám, chẩn đoán hoặc điều trị y khoa.</aside>
    <?php get_template_part('template-parts/home/space'); ?>
    <?php get_template_part('template-parts/home/promotions'); ?>
    <?php get_template_part('template-parts/home/reviews'); ?>
    <?php get_template_part('template-parts/home/about'); ?>
    <?php get_template_part('template-parts/home/cta'); ?>
</main>
<?php get_footer(); ?>
