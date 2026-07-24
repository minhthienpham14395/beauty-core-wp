<?php
get_header();
$slug = get_post_field('post_name', get_the_ID());
$legal = beautycore_get_legal_page($slug);
?>
<main class="page-content container"><nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?php echo esc_url(home_url('/')); ?>">Trang chủ</a><span aria-hidden="true">/</span><span><?php the_title(); ?></span></nav><article>
    <header class="page-header"><h1><?php the_title(); ?></h1><?php if ($legal) : ?><p>Cập nhật: <?php echo esc_html($legal['updated']); ?></p><?php endif; ?></header>
    <?php if ($legal) : ?>
        <?php foreach ($legal['sections'] as $section) : ?><section class="prose-section"><h2><?php echo esc_html($section['heading']); ?></h2><?php foreach ($section['paragraphs'] as $paragraph) : ?><p><?php echo esc_html($paragraph); ?></p><?php endforeach; ?><?php if (!empty($section['items'])) : ?><ul><?php foreach ($section['items'] as $item) : ?><li><?php echo esc_html($item); ?></li><?php endforeach; ?></ul><?php endif; ?></section><?php endforeach; ?>
    <?php else : ?><section class="prose-section"><?php the_content(); ?></section><?php endif; ?>
</article></main>
<?php get_footer(); ?>
