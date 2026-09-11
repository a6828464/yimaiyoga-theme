<?php
/**
 * 师资页模板：对应原站 views/pages/instructors.php
 *
 * @package yimaiyoga
 */

get_header();

$config   = yimai_config();
$copy     = $config['copy']['instructors'] ?? [];
$teachers = $config['instructors'] ?? [];
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
<section class="instructor-layout page-block" data-instructors>
  <div class="teacher-tabs">
    <?php foreach ($teachers as $index => $teacher): ?>
      <button type="button" class="<?php echo $index === 0 ? 'active' : ''; ?>" data-teacher-tab="<?php echo (int) $index; ?>"><span><?php echo esc_html($teacher['name'] ?? ''); ?></span><em><?php echo esc_html($teacher['years'] ?? ''); ?></em></button>
    <?php endforeach; ?>
  </div>
  <div class="teacher-details">
    <?php foreach ($teachers as $index => $teacher): ?>
      <article class="teacher-detail <?php echo $index === 0 ? 'active' : ''; ?>" data-teacher-panel="<?php echo (int) $index; ?>">
        <img src="<?php echo esc_url(yimai_image_url($teacher['image'] ?? '')); ?>" alt="<?php echo esc_attr($teacher['name'] ?? ''); ?>老师" loading="lazy">
        <div>
          <p class="eyebrow">Teacher Profile</p>
          <h2><?php echo esc_html($teacher['name'] ?? ''); ?></h2>
          <p class="lead"><?php echo esc_html($teacher['focus'] ?? ''); ?></p>
          <p><?php echo esc_html($teacher['summary'] ?? ''); ?></p>
          <div class="detail-columns">
            <section><h3>资质与学习</h3><?php foreach ($teacher['credentials'] ?? [] as $item): ?><p><?php echo esc_html($item); ?></p><?php endforeach; ?></section>
            <section><h3>擅长方向</h3><div class="tags"><?php foreach ($teacher['specialties'] ?? [] as $item): ?><span><?php echo esc_html($item); ?></span><?php endforeach; ?></div></section>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php
get_footer();
