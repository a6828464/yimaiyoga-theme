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
      <?php
      /*
       * FAQ 形状守卫：默认数据是数字索引元组 ["问题","回答"]，
       * 而后台「新增」路径写入的是 {q,a} 字符串键，两种形状都要能渲染，
       * 避免 PHP 8 下 Undefined array key warning 与空白条目。
       */
      $faq = is_array($faq) ? $faq : [];
      $faqQ = $faq[0] ?? ($faq['q'] ?? '');
      $faqA = $faq[1] ?? ($faq['a'] ?? '');
      ?>
      <details><summary><?php echo esc_html($faqQ); ?></summary><p><?php echo esc_html($faqA); ?></p></details>
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
