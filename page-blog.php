<?php
/**
 * 博客列表模板：展示最新文章（软文）
 * 对应导航「博客」/blog
 *
 * @package yimaiyoga
 */

get_header();

$config = yimai_config();
?>
<section class="page-hero reveal">
  <div>
    <p class="eyebrow">Yi Mai Journal</p>
    <h1>博客</h1>
  </div>
  <aside>
    <p class="eyebrow">Weekly Notes</p>
    <p>关于练习、身体与生活方式的一麦笔记。定期更新。</p>
  </aside>
</section>

<section class="page-block">
  <?php
  $paged = max(1, (int) get_query_var('paged'));
  $query = new WP_Query([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 10,
    'paged'          => $paged,
  ]);

  // 默认兜底缩略图（复用媒体库瑜伽图，无特色图时显示）
  $fallback_thumb = wp_get_attachment_image_url(475, 'medium_large');

  if ($query->have_posts()):
    while ($query->have_posts()): $query->the_post();
      $thumb = get_the_post_thumbnail_url(null, 'medium_large');
      $thumb = $thumb ?: $fallback_thumb;
      ?>
      <article class="blog-card reveal" style="display:grid;grid-template-columns:220px 1fr;gap:2rem;align-items:center;border-bottom:1px solid var(--color-stone,#d8d0c2);padding:32px 0;">
        <?php if ($thumb): ?>
          <a href="<?php the_permalink(); ?>" style="display:block;aspect-ratio:4/3;overflow:hidden;background:var(--color-linen,#F4F0E8);">
            <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;">
          </a>
        <?php endif; ?>
        <div>
          <p class="eyebrow"><?php echo esc_html(get_the_date('Y年n月j日')); ?></p>
          <h2 style="font-family:Georgia,serif;font-size:2rem;line-height:1.2;margin:.6rem 0;"><a href="<?php the_permalink(); ?>" style="color:inherit;text-decoration:none;"><?php the_title(); ?></a></h2>
          <p style="line-height:2;color:rgb(var(--color-ink-rgb)/.55);"><?php echo esc_html(wp_trim_words(strip_tags(get_the_excerpt() ?: get_the_content()), 55, '…')); ?></p>
          <a class="text-link" href="<?php the_permalink(); ?>">阅读全文 →</a>
        </div>
      </article>
      <?php
    endwhile;
    wp_reset_postdata();
  else:
    ?>
    <p class="eyebrow">还没有文章</p>
    <p>博客即将上线，敬请期待。</p>
  <?php endif; ?>
</section>
<?php
get_footer();
