<?php
/**
 * 单篇文章详情模板：博客文章阅读页（左对齐正文排版）
 *
 * @package yimaiyoga
 */

get_header();

while (have_posts()):
    the_post();
    $thumb = get_the_post_thumbnail_url(null, 'large');
    // width/height 取媒体库真实尺寸用于预留位置、减少 CLS；取不到时不输出（不编造尺寸）
    $thumbSize = $thumb ? wp_get_attachment_image_src((int) get_post_thumbnail_id(), 'large') : false;
    $thumbW    = is_array($thumbSize) ? (int) $thumbSize[1] : 0;
    $thumbH    = is_array($thumbSize) ? (int) $thumbSize[2] : 0;
    ?>
    <section class="page-hero reveal">
      <div>
        <p class="eyebrow"><?php echo esc_html(get_the_date('Y年n月j日')); ?></p>
        <h1><?php the_title(); ?></h1>
      </div>
      <aside>
        <p class="eyebrow">Yi Mai Journal</p>
        <p>一麦笔记：关于练习、身体与生活方式。</p>
      </aside>
    </section>

    <?php if ($thumb): ?>
    <div class="post-cover">
      <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>"<?php if ($thumbW > 0 && $thumbH > 0): ?> width="<?php echo esc_attr((string) $thumbW); ?>" height="<?php echo esc_attr((string) $thumbH); ?>"<?php endif; ?> loading="lazy">
    </div>
    <?php endif; ?>

    <section class="page-block post-content">
      <?php the_content(); ?>
      <p class="post-back">
        <a class="text-link" href="<?php echo esc_url(home_url('/blog')); ?>">← 返回博客</a>
      </p>
    </section>
    <?php
endwhile;

get_footer();
