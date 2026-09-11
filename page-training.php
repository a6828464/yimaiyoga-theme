<?php
/**
 * 教培页模板：对应原站 views/pages/training.php
 *
 * @package yimaiyoga
 */

get_header();

$config    = yimai_config();
$copy      = $config['copy']['training'] ?? [];
$audiences = $config['training_audiences'] ?? [];
$programs  = $config['training_programs'] ?? [];
$rights    = $config['training_rights'] ?? [];
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
<section class="number-grid page-block">
  <?php foreach ($audiences as $i => $item): ?>
    <article class="reveal"><span>0<?php echo (int) $i + 1; ?></span><p><?php echo esc_html($item); ?></p></article>
  <?php endforeach; ?>
</section>
<section class="card-grid three page-block">
  <?php foreach ($programs as $program): ?>
    <article class="program-card reveal">
      <p class="eyebrow"><?php echo esc_html($program['label']); ?></p>
      <h2><?php echo esc_html($program['name']); ?></h2>
      <strong><?php echo esc_html($program['price']); ?></strong>
      <p><?php echo esc_html($program['description']); ?></p>
      <?php foreach ($program['points'] as $point): ?><span><?php echo esc_html($point); ?></span><?php endforeach; ?>
    </article>
  <?php endforeach; ?>
</section>
<section class="number-grid page-block">
  <?php foreach ($rights as $i => $item): ?>
    <article class="reveal"><span>0<?php echo (int) $i + 1; ?></span><p><?php echo esc_html($item); ?></p></article>
  <?php endforeach; ?>
</section>
<section class="cta-block reveal">
  <p class="eyebrow muted-light"><?php echo esc_html($copy['complianceEyebrow'] ?? ''); ?></p>
  <h2><?php echo esc_html($copy['complianceTitle'] ?? ''); ?></h2>
  <p><?php echo esc_html($copy['complianceDescription'] ?? ''); ?></p>
  <a class="text-link light" href="<?php echo esc_url(home_url('/booking')); ?>">预约 RYT200 咨询</a>
</section>
<?php
get_footer();
