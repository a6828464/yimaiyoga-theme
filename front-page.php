<?php
/**
 * 首页模板：对应原站 views/pages/home.php
 *
 * @package yimaiyoga
 */

get_header();

$config   = yimai_config();
$copy     = $config['copy']['home'] ?? [];
$courses  = array_slice($config['courseThemes'] ?? [], 0, 6);
$teachers = array_slice($config['instructors'] ?? [], 0, 6);
$images   = $config['images'] ?? [];
?>
<section class="home-hero reveal">
  <img src="<?php echo esc_url(yimai_image_url($images['homeHero'] ?? '')); ?>" alt="一麦瑜伽空间" fetchpriority="high">
  <div class="hero-copy">
    <p class="eyebrow muted-light"><?php echo esc_html($copy['heroEyebrow'] ?? ''); ?></p>
    <h1><?php echo wp_kses_post(nl2br(esc_html($copy['heroTitle'] ?? ''))); ?></h1>
    <p><?php echo esc_html($copy['heroDescription'] ?? ''); ?></p>
    <span><?php echo esc_html($copy['heroNote'] ?? ''); ?></span>
  </div>
</section>

<section class="statement reveal">
  <p class="eyebrow"><?php echo esc_html($copy['philosophyEyebrow'] ?? ''); ?></p>
  <h2><?php echo esc_html($copy['philosophyTitle'] ?? ''); ?></h2>
  <p><?php echo esc_html($copy['philosophyDescription'] ?? ''); ?></p>
</section>

<section class="split-section">
  <div class="section-intro reveal">
    <p class="eyebrow"><?php echo esc_html($copy['themesEyebrow'] ?? ''); ?></p>
    <h2><?php echo esc_html($copy['themesTitle'] ?? ''); ?></h2>
    <p><?php echo esc_html($copy['themesDescription'] ?? ''); ?></p>
  </div>
  <div class="card-grid three">
    <?php foreach ($courses as $course): ?>
      <article class="course-card reveal">
        <div class="image-frame"><img src="<?php echo esc_url(yimai_image_url($course['image'] ?? '')); ?>" alt="<?php echo esc_attr($course['title'] ?? ''); ?>" loading="lazy"></div>
        <p class="eyebrow"><?php echo esc_html($course['type'] ?? ''); ?></p>
        <h3><?php echo esc_html($course['title'] ?? ''); ?></h3>
        <p><?php echo esc_html($course['effect'] ?? ''); ?></p>
        <small><?php echo esc_html($course['suited'] ?? ''); ?></small>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="path-strip reveal">
  <?php foreach ($config['classPaths'] ?? [] as $path): ?>
    <article>
      <p class="eyebrow"><?php echo esc_html($path['title'] ?? ''); ?></p>
      <p><?php echo esc_html($path['description'] ?? ''); ?></p>
    </article>
  <?php endforeach; ?>
</section>

<section class="studio-feature reveal">
  <div>
    <p class="eyebrow muted-light"><?php echo esc_html($copy['studioEyebrow'] ?? ''); ?></p>
    <h2><?php echo esc_html($copy['studioTitle'] ?? ''); ?></h2>
    <p><?php echo esc_html($copy['studioDescription'] ?? ''); ?></p>
  </div>
  <img src="<?php echo esc_url(yimai_image_url($images['homeStudio'] ?? $images['studioHero'] ?? '')); ?>" alt="一麦瑜伽空间氛围" loading="lazy">
</section>

<section class="split-section teachers-section">
  <div class="section-intro reveal">
    <p class="eyebrow"><?php echo esc_html($copy['instructorsEyebrow'] ?? ''); ?></p>
    <h2><?php echo esc_html($copy['instructorsTitle'] ?? ''); ?></h2>
    <p><?php echo esc_html($copy['instructorsDescription'] ?? ''); ?></p>
  </div>
  <div class="card-grid three">
    <?php foreach ($teachers as $teacher): ?>
      <a class="teacher-card reveal" href="<?php echo esc_url(home_url('/instructors')); ?>">
        <img src="<?php echo esc_url(yimai_image_url($teacher['image'] ?? '')); ?>" alt="<?php echo esc_attr($teacher['name'] ?? ''); ?>老师" loading="lazy">
        <div><h3><?php echo esc_html($teacher['name'] ?? ''); ?></h3><span><?php echo esc_html($teacher['years'] ?? ''); ?></span></div>
        <p><?php echo esc_html($teacher['focus'] ?? ''); ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="membership-band reveal">
  <div>
    <p class="eyebrow"><?php echo esc_html($copy['membershipEyebrow'] ?? ''); ?></p>
    <h2><?php echo esc_html($copy['membershipTitle'] ?? ''); ?></h2>
  </div>
  <div class="membership-list">
    <?php foreach (yimai_memberships() as $plan): ?>
      <a href="<?php echo esc_url(home_url('/membership')); ?>"><span><?php echo esc_html($plan['accent']); ?></span><strong><?php echo esc_html($plan['name']); ?></strong><em><?php echo esc_html($plan['label']); ?></em></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="story-panel reveal">
  <img src="<?php echo esc_url(yimai_image_url($images['homeStory'] ?? $images['studioHero'] ?? '')); ?>" alt="柔和光线中的瑜伽练习" loading="lazy">
  <div>
    <p class="eyebrow muted-light"><?php echo esc_html($copy['storyEyebrow'] ?? ''); ?></p>
    <h2><?php echo esc_html($copy['storyTitle'] ?? ''); ?></h2>
    <a class="text-link light" href="<?php echo esc_url(home_url('/studio')); ?>">探索空间</a>
  </div>
</section>

<section class="booking-section">
  <div class="section-intro reveal">
    <p class="eyebrow"><?php echo esc_html($copy['bookingEyebrow'] ?? ''); ?></p>
    <h2><?php echo esc_html($copy['bookingTitle'] ?? ''); ?></h2>
    <p><?php echo esc_html($copy['bookingDescription'] ?? ''); ?></p>
  </div>
  <?php get_template_part('template-parts/booking-form'); ?>
</section>
<?php
get_footer();
