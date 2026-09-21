<?php
/**
 * 会员方案行区块（共享片段）
 * 用于 page-membership.php 与 page-contact.php，两处标记此前逐字节重复。
 *
 * @package yimaiyoga
 */
?>
<section class="membership-rows page-block">
  <?php foreach (yimai_memberships() as $index => $plan): ?>
    <article class="reveal"><span>0<?php echo (int) $index + 1; ?></span><div><em><?php echo esc_html($plan['accent']); ?></em><h2><?php echo esc_html($plan['name']); ?></h2></div><p><?php echo esc_html($plan['feature']); ?></p><strong><?php echo esc_html($plan['label']); ?></strong></article>
  <?php endforeach; ?>
</section>
