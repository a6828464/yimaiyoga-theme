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
    <div class="post-cover" style="max-width:1440px;margin:0 auto;padding:0 3rem;">
      <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" style="width:100%;aspect-ratio:16/9;object-fit:cover;">
    </div>
    <?php endif; ?>

    <section class="page-block post-content">
      <?php the_content(); ?>
      <p style="margin-top:3rem;">
        <a class="text-link" href="<?php echo esc_url(home_url('/blog')); ?>">← 返回博客</a>
      </p>
    </section>
    <?php
endwhile;

get_footer();
