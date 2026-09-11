<?php
/**
 * 会员与联系页模板（会员方案 + 门店 + FAQ 合并）
 * 对应导航「会员与联系」/contact
 *
 * @package yimaiyoga
 */

get_header();

$config = yimai_config();
$copy   = $config['copy']['contact'] ?? [];
$faqs   = yimai_faqs();
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

<section class="membership-rows page-block">
  <?php foreach (yimai_memberships() as $index => $plan): ?>
    <article class="reveal"><span>0<?php echo (int) $index + 1; ?></span><div><em><?php echo esc_html($plan['accent']); ?></em><h2><?php echo esc_html($plan['name']); ?></h2></div><p><?php echo esc_html($plan['feature']); ?></p><strong><?php echo esc_html($plan['label']); ?></strong></article>
  <?php endforeach; ?>
</section>

<section class="contact-grid page-block">
  <div>
    <?php foreach (yimai_studios() as $studio): ?>
      <article class="reveal"><h2><?php echo esc_html($studio['name']); ?></h2><p><?php echo esc_html($studio['address']); ?></p><strong><?php echo esc_html($studio['phone']); ?></strong></article>
    <?php endforeach; ?>
  </div>
  <div class="faq-panel reveal">
    <p class="eyebrow muted-light"><?php echo esc_html($copy['faqEyebrow'] ?? ''); ?></p>
    <h2><?php echo esc_html($copy['faqTitle'] ?? ''); ?></h2>
    <?php foreach ($faqs as $faq): ?>
      <details><summary><?php echo esc_html($faq[0]); ?></summary><p><?php echo esc_html($faq[1]); ?></p></details>
    <?php endforeach; ?>
  </div>
</section>

<section class="cta-block reveal">
  <p class="eyebrow muted-light"><?php echo esc_html($copy['ctaEyebrow'] ?? $config['copy']['membership']['ctaEyebrow'] ?? ''); ?></p>
  <h2><?php echo esc_html($copy['ctaTitle'] ?? $config['copy']['membership']['ctaTitle'] ?? ''); ?></h2>
  <a class="text-link light" href="<?php echo esc_url(home_url('/booking')); ?>">预约咨询</a>
</section>
<?php
get_footer();
