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
    <?php
    // 可见面包屑：中间层取文章主分类（与插件 BreadcrumbList 的「首页/分类/标题」同层级同来源）。
    // 仅「未分类」与无分类两种情形回退 /blog；此时可见层是 /blog 而插件 JSON-LD 仍取分类对象，
    // 这一处已知差异见 CHANGELOG（属分类归属的数据问题，不改代码）。
    $primaryCat = null;
    $catList = get_the_category();
    if (is_array($catList)) {
        usort($catList, function ($a, $b) {
            return (int) $b->count <=> (int) $a->count;
        });
        $primaryCat = $catList[0] ?? null;
    }
    $crumbUrl = home_url('/blog');
    $crumbLabel = '博客';
    if ($primaryCat instanceof WP_Term && $primaryCat->name !== '' && $primaryCat->name !== '未分类') {
        $catLink = get_category_link($primaryCat);
        if (is_string($catLink) && $catLink !== '') {
            $crumbUrl = $catLink;
            $crumbLabel = (string) $primaryCat->name;
        }
    }
    ?>
    <nav class="breadcrumb" aria-label="面包屑">
      <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
      <span class="breadcrumb-sep" aria-hidden="true">/</span>
      <a href="<?php echo esc_url($crumbUrl); ?>"><?php echo esc_html($crumbLabel); ?></a>
      <span class="breadcrumb-sep" aria-hidden="true">/</span>
      <span class="breadcrumb-current" aria-current="page"><?php the_title(); ?></span>
    </nav>

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
    // 文章评论区：开启评论或已有评论时渲染 WP 原生评论模板（comments.php 缺省时使用核心默认输出）
    if (comments_open() || get_comments_number()):
        ?>
        <section class="page-block post-comments">
          <?php comments_template(); ?>
        </section>
        <?php
    endif;
    ?>

    <?php
    // 文末相关阅读：同分类最多 2 篇 + 固定 1 条课程入口（保证至少 1 条指向 /classes）。
    // 同类不足时用最新文章补齐，避免文末只剩一条链接。
    $currentId = (int) get_the_ID();
    $relatedArgs = [
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 2,
        'post__not_in'        => [$currentId],
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ];
    $categories = wp_get_post_categories($currentId);
    if (is_array($categories) && $categories !== []) {
        $relatedArgs['category__in'] = array_map('intval', $categories);
    }
    $related = get_posts($relatedArgs);
    if (count($related) < 2) {
        unset($relatedArgs['category__in']); // 同分类不足时改用全站最新文章
        $related = get_posts($relatedArgs);
    }
    wp_reset_postdata();
    $relatedLinks = [];
    foreach (array_slice($related, 0, 2) as $relatedPost) {
        if (!$relatedPost instanceof WP_Post) {
            continue;
        }
        $relatedLinks[] = [
            'url'   => (string) get_permalink($relatedPost),
            'label' => (string) get_the_title($relatedPost),
        ];
    }
    $relatedLinks[] = [
        'url'   => home_url('/classes'),
        'label' => '课程体系：找到适合当下身体的练习主题',
    ];
    // 有界补齐到 3 条：固定顺序 /classes → /booking → /blog（已存在同 URL 就跳过）。
    // /classes 排最前，所以「至少 1 条指向 /classes」恒成立；文末不会再出现只有 1-2 条的情况。
    $fillers = [
        ['url' => home_url('/classes'), 'label' => '课程体系：找到适合当下身体的练习主题'],
        ['url' => home_url('/booking'), 'label' => '预约一次体验课，让老师先看你的身体状态'],
        ['url' => home_url('/blog'), 'label' => '更多一麦笔记：练习、身体与生活方式'],
    ];
    $existingUrls = array_column($relatedLinks, 'url');
    foreach ($fillers as $filler) {
        if (count($relatedLinks) >= 3) {
            break;
        }
        if (in_array($filler['url'], $existingUrls, true)) {
            continue;
        }
        $relatedLinks[] = $filler;
        $existingUrls[] = $filler['url'];
    }
    ?>
    <section class="page-block related-posts">
      <p class="eyebrow">Related · 继续阅读</p>
      <ul class="related-list">
        <?php foreach ($relatedLinks as $relatedLink): ?>
          <li><a href="<?php echo esc_url($relatedLink['url']); ?>"><?php echo esc_html($relatedLink['label']); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php
endwhile;

get_footer();
