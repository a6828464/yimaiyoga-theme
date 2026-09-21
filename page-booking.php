<?php
/**
 * 预约页模板：对应原站 views/pages/booking.php
 *
 * @package yimaiyoga
 */

get_header();

$config = yimai_config();
$copy   = $config['copy']['booking'] ?? [];
$images = $config['images'] ?? [];
?>
<?php
$notice = function_exists('yimai_active_announcement') ? yimai_active_announcement() : [];
$stripOn = ($config['announcements']['stripEnabled'] ?? true) && $notice;
?>
<?php if ($stripOn): ?>
<a class="activity-strip" href="<?php echo esc_url(yimai_notice_link($notice) ?: '#'); ?>" data-activity-strip>
  <span class="activity-strip-tag">活动</span>
  <span class="activity-strip-text"><strong><?php echo esc_html($notice['title'] ?? ''); ?></strong><?php $short = trim((string) ($notice['content'] ?? '')); if ($short !== ''): ?> · <?php echo esc_html(mb_strimwidth(preg_replace('/\s+/u', ' ', $short), 0, 60, '…')); endif; ?></span>
  <?php if (yimai_notice_link($notice) || !empty($notice['content'])): ?><span class="activity-strip-more">查看详情 →</span><?php endif; ?>
</a>
<?php endif; ?>
<section class="booking-hero page-block">
  <div class="section-intro reveal">
    <p class="eyebrow"><?php echo esc_html($copy['eyebrow'] ?? ''); ?></p>
    <h1><?php echo esc_html($copy['title'] ?? ''); ?></h1>
    <p><?php echo esc_html($copy['description'] ?? ''); ?></p>
  </div>
  <img src="<?php echo esc_url(yimai_image_url($images['bookingHero'] ?? $images['homeHero'] ?? '')); ?>" alt="预约体验" loading="lazy">
</section>
<section class="story-panel studio-hero reveal">
  <img src="<?php echo esc_url(yimai_image_url($images['studioHero'] ?? '')); ?>" alt="一麦空间光影" loading="lazy">
  <div><p class="eyebrow muted-light"><?php echo esc_html($config['copy']['studio']['heroEyebrow'] ?? ''); ?></p><h2><?php echo esc_html($config['copy']['studio']['heroTitle'] ?? ''); ?></h2></div>
</section>
<?php get_template_part('template-parts/studio-cards', null, ['images' => $images]); ?>
<section class="booking-section compact">
  <div class="section-intro reveal">
    <p class="eyebrow"><?php echo esc_html($config['copy']['studio']['sideEyebrow'] ?? ''); ?></p>
    <h2><?php echo esc_html($config['copy']['studio']['sideDescription'] ?? ''); ?></h2>
  </div>
  <?php get_template_part('template-parts/booking-form'); ?>
</section>
<?php
get_footer();
