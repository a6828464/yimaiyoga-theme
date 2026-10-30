<?php
/**
 * 门店卡片区块（共享片段）
 * 用于 page-studio.php 与 page-booking.php，两处标记此前逐字节重复。
 *
 * 传入参数（get_template_part 第三参数）：
 *   $args['images'] => yimai_config()['images']，用于取每店配图（studioImages[index] → studioHero 兜底）
 *   $args['titleTag'] => 门店名标题标签，默认 h2（保持改动前的输出）
 *
 * 门店详情页（/studio/east、/studio/greenland）的站内入口挂在这里：
 * 一店一页如果没有任何站内链接指向，就是两张孤岛页，权重与收录都上不去。
 * 页面还没建时 yimai_studio_page_url_by_name() 返回空串，卡片不渲染链接（不留 404）。
 *
 * @package yimaiyoga
 */

$images   = is_array($args['images'] ?? null) ? $args['images'] : [];
$titleTag = (string) ($args['titleTag'] ?? 'h2');
if (!in_array($titleTag, ['h2', 'h3'], true)) {
    $titleTag = 'h2';
}
?>
<section class="studio-grid page-block">
  <?php foreach (yimai_studios() as $index => $studio): ?>
    <?php
    $img = $images['studioImages'][$index] ?? ($images['studioHero'] ?? '');
    $detailUrl = yimai_studio_page_url_by_name((string) ($studio['name'] ?? ''));
    ?>
    <article class="reveal"><img src="<?php echo esc_url(yimai_image_url($img)); ?>" alt="<?php echo esc_attr($studio['name']); ?>" loading="lazy"><div><?php echo '<' . $titleTag; ?>><?php echo esc_html($studio['name']); ?><?php echo '</' . $titleTag . '>'; ?><p><?php echo esc_html($studio['address']); ?></p><strong><?php echo esc_html($studio['area']); ?></strong><span><?php echo esc_html($studio['phone']); ?></span><?php if ($detailUrl !== ''): ?><a class="text-link" href="<?php echo esc_url($detailUrl); ?>">门店详情 →</a><?php endif; ?></div></article>
  <?php endforeach; ?>
</section>
