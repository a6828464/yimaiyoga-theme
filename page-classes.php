<?php
/**
 * 课程页模板：对应原站 views/pages/classes.php
 *
 * @package yimaiyoga
 */

get_header();

$config = yimai_config();
$copy   = $config['copy']['classes'] ?? [];
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
<section class="card-grid three page-block">
  <?php foreach ($config['courseThemes'] ?? [] as $course): ?>
    <article class="course-card reveal">
      <div class="image-frame"><img src="<?php echo esc_url(yimai_image_url($course['image'] ?? '')); ?>" alt="<?php echo esc_attr($course['title'] ?? ''); ?>" loading="lazy"></div>
      <p class="eyebrow"><?php echo esc_html($course['type'] ?? ''); ?></p>
      <h3><?php echo esc_html($course['title'] ?? ''); ?></h3>
      <p><?php echo esc_html($course['effect'] ?? ''); ?></p>
      <small><?php echo esc_html($course['suited'] ?? ''); ?></small>
    </article>
  <?php endforeach; ?>
</section>
<section class="path-strip reveal">
  <?php foreach ($config['classPaths'] ?? [] as $path): ?>
    <article>
      <p class="eyebrow"><?php echo esc_html($path['title'] ?? ''); ?></p>
      <h3><?php echo esc_html($path['description'] ?? ''); ?></h3>
      <div class="tags"><?php foreach ($path['tags'] ?? [] as $tag): ?><span><?php echo esc_html($tag); ?></span><?php endforeach; ?></div>
    </article>
  <?php endforeach; ?>
</section>
<?php
get_footer();
