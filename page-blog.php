<?php
/**
 * 博客列表模板：展示最新文章（软文）
 * 对应导航「博客」/blog
 *
 * 注：卡片样式原以内联 style 写在本模板，已全部移除，改由 assets/css 统一维护。
 *
 * @package yimaiyoga
 */

get_header();
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
  /*
   * 分页变量用 paged（不是 page）：
   * paginate_links() 生成的地址是 /blog/page/2/（pretty permalink）或 ?paged=2，
   * 两种形式由 WP 重写规则与 paginate_links() 都写入 paged；
   * 而 page 是「同一页面内容分页」（<!--nextpage-->）的变量，读它会串页。
   */
  $paged = max(1, (int) get_query_var('paged'));
  $query = new WP_Query([
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 10,
    'paged'          => $paged,
  ]);

  // 默认兜底缩略图：优先用后台可维护的配置值，未配置时不强行指定；
  // 无特色图且无配置时由 yimai_image_url('') 回退到主题占位图。
  $fallback_thumb_raw = yimai_config()['images']['blogFallback'] ?? '';
  $fallback_thumb     = $fallback_thumb_raw !== '' ? yimai_image_url($fallback_thumb_raw) : '';

  if ($query->have_posts()):
    while ($query->have_posts()): $query->the_post();
      $thumb = get_the_post_thumbnail_url(null, 'medium_large');
      // width/height 取媒体库真实尺寸（无特色图回退到占位图时不输出，避免编造尺寸）
      $thumbSize = $thumb ? wp_get_attachment_image_src((int) get_post_thumbnail_id(), 'medium_large') : false;
      $thumbW    = is_array($thumbSize) ? (int) $thumbSize[1] : 0;
      $thumbH    = is_array($thumbSize) ? (int) $thumbSize[2] : 0;
      $thumb     = $thumb ?: $fallback_thumb;
      ?>
      <article class="blog-card reveal">
        <?php if ($thumb): ?>
          <a class="blog-card-media" href="<?php the_permalink(); ?>">
            <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>"<?php if ($thumbW > 0 && $thumbH > 0): ?> width="<?php echo esc_attr((string) $thumbW); ?>" height="<?php echo esc_attr((string) $thumbH); ?>"<?php endif; ?> loading="lazy">
          </a>
        <?php endif; ?>
        <div class="blog-card-body">
          <p class="eyebrow"><?php echo esc_html(get_the_date('Y年n月j日')); ?></p>
          <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <p class="blog-card-excerpt"><?php echo esc_html(wp_trim_words(strip_tags(get_the_excerpt() ?: get_the_content()), 55, '…')); ?></p>
          <a class="text-link" href="<?php the_permalink(); ?>">阅读全文 →</a>
        </div>
      </article>
      <?php
    endwhile;
    ?>
    <?php
    // 分页：自定义 WP_Query 需显式输出，否则第 2 页不可达
    $pagination = paginate_links([
        'total'     => (int) $query->max_num_pages,
        'current'   => $paged,
        'mid_size'  => 1,
        'end_size'  => 1,
        'prev_text' => '← 上一页',
        'next_text' => '下一页 →',
        'type'      => 'list',
    ]);
    ?>
    <?php if ($pagination): ?>
      <nav class="pagination" aria-label="文章分页"><?php echo wp_kses_post($pagination); ?></nav>
    <?php endif; ?>
    <?php
    wp_reset_postdata();
  else:
    ?>
    <p class="eyebrow">还没有文章</p>
    <p>博客即将上线，敬请期待。</p>
  <?php endif; ?>
</section>
<?php
get_footer();
