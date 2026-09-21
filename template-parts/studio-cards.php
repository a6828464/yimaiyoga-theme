<?php
/**
 * 门店卡片区块（共享片段）
 * 用于 page-studio.php 与 page-booking.php，两处标记此前逐字节重复。
 *
 * 传入参数（get_template_part 第三参数）：
 *   $args['images'] => yimai_config()['images']，用于取每店配图（studioImages[index] → studioHero 兜底）
 *   $args['titleTag'] => 门店名标题标签，默认 h2（保持改动前的输出）
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
  <?php foreach (yimai_studios() as $index => $studio): $img = $images['studioImages'][$index] ?? ($images['studioHero'] ?? ''); ?>
    <article class="reveal"><img src="<?php echo esc_url(yimai_image_url($img)); ?>" alt="<?php echo esc_attr($studio['name']); ?>" loading="lazy"><div><?php echo '<' . $titleTag; ?>><?php echo esc_html($studio['name']); ?><?php echo '</' . $titleTag . '>'; ?><p><?php echo esc_html($studio['address']); ?></p><strong><?php echo esc_html($studio['area']); ?></strong><span><?php echo esc_html($studio['phone']); ?></span></div></article>
  <?php endforeach; ?>
</section>
