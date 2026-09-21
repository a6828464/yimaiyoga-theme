<?php
/**
 * 会员页模板：对应原站 views/pages/membership.php
 *
 * @package yimaiyoga
 */

get_header();

$config = yimai_config();
$copy   = $config['copy']['membership'] ?? [];
?>
<section class="page-hero reveal">
  <div>
    <p class="eyebrow"><?php echo esc_html($copy['eyebrow'] ?? ''); ?></p>
    <h1><?php echo nl2br(esc_html($copy['title'] ?? '')); ?></h1>
  </div>
  <?php if (!empty($copy['sideDescription'])): ?>
    <aside>
      <p class="eyebrow"><?php echo esc_html($copy['sideEyebrow'] ?? ''); ?></p>
      <p><?php echo esc_html($copy['sideDescription']); ?></p>
    </aside>
  <?php endif; ?>
</section>
<?php get_template_part('template-parts/membership-rows'); ?>
<section class="cta-block reveal">
  <p class="eyebrow muted-light"><?php echo esc_html($copy['ctaEyebrow'] ?? ''); ?></p>
  <h2><?php echo esc_html($copy['ctaTitle'] ?? ''); ?></h2>
  <a class="text-link light" href="<?php echo esc_url(home_url('/booking')); ?>">预约咨询</a>
</section>
<?php
get_footer();
