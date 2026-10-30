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
// 门店直达入口（studio-feature 区块）：只收「门店页真的存在」的门店，
// 锚文本自带 店名 + 区 + 商圈；一个都没有时整块不渲染（不留 404，见 yimai_home_studio_links）。
$homeStudioLinks = yimai_home_studio_links();
// 首页实体文案（hero 之后）：三个扁平键都在 copy.home（后台「页面文案」可改，模板不写死中文）。
// 正文段落用空行分隔——后台每个键是一个 textarea，模板按空行拆段，避免嵌套数组。
$homeIntroEyebrow = trim((string) ($copy['homeIntroEyebrow'] ?? ''));
$homeIntroTitle   = trim((string) ($copy['homeIntroTitle'] ?? ''));
$introBody        = [];
foreach (preg_split('/\R{2,}/u', (string) ($copy['homeIntroBody'] ?? '')) ?: [] as $introParagraph) {
    $introParagraph = trim($introParagraph);
    if ($introParagraph !== '') {
        $introBody[] = $introParagraph;
    }
}
?>
<section class="home-hero reveal">
  <img src="<?php echo esc_url(yimai_image_url($images['homeHero'] ?? '')); ?>" alt="一麦瑜伽空间" fetchpriority="high">
  <div class="hero-copy">
    <p class="eyebrow muted-light"><?php echo esc_html($copy['heroEyebrow'] ?? ''); ?></p>
    <h1><?php echo nl2br(esc_html($copy['heroTitle'] ?? '')); ?></h1>
    <p><?php echo esc_html($copy['heroDescription'] ?? ''); ?></p>
    <span><?php echo esc_html($copy['heroNote'] ?? ''); ?></span>
    <?php // 首屏转化入口：文案全部来自 copy.home，模板里不写死中文 ?>
    <div class="hero-actions">
      <?php if (($copy['ctaPrimary'] ?? '') !== ''): ?>
        <a class="button primary" href="<?php echo esc_url(home_url('/booking')); ?>"><?php echo esc_html($copy['ctaPrimary']); ?></a>
      <?php endif; ?>
      <?php if (($copy['ctaSecondary'] ?? '') !== ''): ?>
        <a class="text-link light" href="<?php echo esc_url(home_url('/classes')); ?>"><?php echo esc_html($copy['ctaSecondary']); ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if ($homeIntroTitle !== '' || $introBody !== []): ?>
<section class="split-section reveal">
  <div class="section-intro">
    <p class="eyebrow"><?php echo esc_html($homeIntroEyebrow); ?></p>
    <h2><?php echo esc_html($homeIntroTitle); ?></h2>
  </div>
  <div class="home-intro-body">
    <?php foreach ($introBody as $introParagraph): ?>
      <p><?php echo esc_html($introParagraph); ?></p>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

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
      <?php // 整卡可点：<a> 直接承担卡片容器（卡内无其它链接，不会出现 <a> 嵌 <a>） ?>
      <a class="course-card reveal" href="<?php echo esc_url(home_url('/classes')); ?>">
        <div class="image-frame"><img src="<?php echo esc_url(yimai_image_url($course['image'] ?? '')); ?>" alt="<?php echo esc_attr($course['title'] ?? ''); ?>" loading="lazy"></div>
        <p class="eyebrow"><?php echo esc_html($course['type'] ?? ''); ?></p>
        <h3><?php echo esc_html($course['title'] ?? ''); ?></h3>
        <p><?php echo esc_html($course['effect'] ?? ''); ?></p>
        <small><?php echo esc_html($course['suited'] ?? ''); ?></small>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="path-strip reveal">
  <?php foreach ($config['classPaths'] ?? [] as $path): ?>
    <article>
      <p class="eyebrow"><?php echo esc_html($path['title'] ?? ''); ?></p>
      <h3 class="path-strip-description"><?php echo esc_html($path['description'] ?? ''); ?></h3>
    </article>
  <?php endforeach; ?>
</section>

<section class="studio-feature reveal">
  <div>
    <?php /* 内层 div 不参与样式：外层 .studio-feature>div 是 flex + space-between，
             文案与门店入口分成上下两组，中间自然留白，不必新造版式 */ ?>
    <div>
      <p class="eyebrow muted-light"><?php echo esc_html($copy['studioEyebrow'] ?? ''); ?></p>
      <h2><?php echo esc_html($copy['studioTitle'] ?? ''); ?></h2>
      <p><?php echo esc_html($copy['studioDescription'] ?? ''); ?></p>
    </div>
    <?php if ($homeStudioLinks !== []): ?>
      <?php // 同城转化最短路径：首页直接落到门店页，不用先绕 /studio。锚文本＝店名＋区＋商圈 ?>
      <nav class="studio-links" aria-label="门店直达">
        <?php foreach ($homeStudioLinks as $studioLink): ?>
          <a class="studio-link" href="<?php echo esc_url($studioLink['url']); ?>">
            <span class="studio-link-name"><?php echo esc_html($studioLink['name']); ?></span>
            <?php if ($studioLink['where'] !== ''): ?>
              <span class="studio-link-where"><?php echo esc_html($studioLink['where']); ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </nav>
    <?php endif; ?>
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
