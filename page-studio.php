<?php
/**
 * 空间页模板：对应原站 views/pages/studio.php
 *
 * @package yimaiyoga
 */

get_header();

$config = yimai_config();
$copy   = $config['copy']['studio'] ?? [];
$images = $config['images'] ?? [];
?>
<section class="page-hero reveal">
  <div>
    <p class="eyebrow"><?php echo esc_html($copy['eyebrow'] ?? ''); ?></p>
    <h1><?php echo wp_kses_post(nl2br(esc_html($copy['title'] ?? ''))); ?></h1>
  </div>
  <?php if (!empty($copy['sideDescription'])): ?>
    <aside>
      <p class="eyebrow"><?php echo esc_html($copy['sideEyebrow'] ?? ''); ?></p>
      <p><?php echo esc_html($copy['sideDescription']); ?></p>
    </aside>
  <?php endif; ?>
</section>
<section class="story-panel studio-hero reveal">
  <img src="<?php echo esc_url(yimai_image_url($images['studioHero'] ?? '')); ?>" alt="一麦空间光影" loading="lazy">
  <div><p class="eyebrow muted-light"><?php echo esc_html($copy['heroEyebrow'] ?? ''); ?></p><h2><?php echo esc_html($copy['heroTitle'] ?? ''); ?></h2></div>
</section>
<section class="studio-grid page-block">
  <?php foreach (yimai_studios() as $index => $studio): $img = $images['studioImages'][$index] ?? ($images['studioHero'] ?? ''); ?>
    <article class="reveal"><img src="<?php echo esc_url(yimai_image_url($img)); ?>" alt="<?php echo esc_attr($studio['name']); ?>" loading="lazy"><div><h2><?php echo esc_html($studio['name']); ?></h2><p><?php echo esc_html($studio['address']); ?></p><strong><?php echo esc_html($studio['area']); ?></strong><span><?php echo esc_html($studio['phone']); ?></span></div></article>
  <?php endforeach; ?>
</section>
<?php
get_footer();
