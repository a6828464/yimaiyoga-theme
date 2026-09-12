<?php
/**
 * 页脚模板：对应原站 views/partials/floating-actions.php 与 views/partials/footer.php
 *
 * @package yimaiyoga
 */

$config    = yimai_config();
$site      = $config['site'] ?? [];
$wechatQr  = $site['wechatQr'] ?? '';
$icpNumber = $site['icpNumber'] ?? '';
?>
</main>
<div class="floating-actions">
  <a href="<?php echo esc_url(home_url('/booking')); ?>">预约</a>
  <a href="<?php echo esc_url(home_url('/contact')); ?>">联系</a>
  <a class="scroll-top" href="#top" aria-label="回到顶部">↑</a>
</div>
<footer class="site-footer">
  <div class="footer-grid">
    <div>
      <p class="eyebrow muted-light">Yi Mai Yoga</p>
      <h2>看见·包容·超越</h2>
      <p>通过瑜伽与普拉提，让都市女性面对自我，对话身体和心灵，在一麦遇见更好的自己。</p>
    </div>
    <div class="footer-links">
      <a href="<?php echo esc_url(home_url('/classes')); ?>">课程体系</a>
      <a href="<?php echo esc_url(home_url('/membership')); ?>">会员方案</a>
      <a href="<?php echo esc_url(home_url('/booking')); ?>">预约体验</a>
      <a href="<?php echo esc_url(home_url('/studio')); ?>">空间与门店</a>
    </div>
    <div class="footer-studios">
      <?php foreach (yimai_studios() as $studio): ?>
        <div>
          <strong><?php echo esc_html($studio['name']); ?></strong>
          <span><?php echo esc_html($studio['address']); ?></span>
          <span><?php echo esc_html($studio['phone']); ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <div>
      <?php if ($wechatQr): ?>
        <div class="qr-box"><img src="<?php echo esc_url(yimai_image_url($wechatQr)); ?>" alt="一麦瑜伽公众号二维码" loading="lazy"></div>
      <?php endif; ?>
      <p class="qr-note">扫码关注公众号，了解课程、活动与练习内容。</p>
    </div>
  </div>
  <?php if ($icpNumber): ?>
    <div class="icp"><a href="https://beian.miit.gov.cn/" target="_blank" rel="noreferrer"><?php echo esc_html($icpNumber); ?></a></div>
  <?php endif; ?>
</footer>
<?php wp_footer(); ?>
<?php
/* 活动公告弹窗：有启用中的活动时输出；app.js 只在首页自动弹出（每个活动仅一次），
   其他页面的「活动条」点击也可打开本弹窗。 */
$imgbedFallback = function_exists('yimai_imgbed_fallback_map') ? yimai_imgbed_fallback_map() : [];
?>
<?php if ($imgbedFallback): ?>
<script>window.YIMAI_IMG_FALLBACK = <?php echo wp_json_encode($imgbedFallback, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;</script>
<?php endif; ?>
<?php $notice = function_exists('yimai_active_announcement') ? yimai_active_announcement() : []; ?>
<?php if ($notice): ?>
<div class="notice-backdrop" data-notice-modal data-notice-id="<?php echo esc_attr($notice['id'] ?? ('act-' . md5((string) ($notice['title'] ?? '')))); ?>" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($notice['title'] ?? '活动公告'); ?>">
  <div class="notice-card">
    <button class="notice-close" type="button" data-notice-close aria-label="关闭公告">×</button>
    <?php if (!empty($notice['image'])): ?>
      <div class="notice-media"><img src="<?php echo esc_url(yimai_image_url($notice['image'])); ?>" alt="<?php echo esc_attr($notice['title'] ?? '活动配图'); ?>"></div>
    <?php endif; ?>
    <div class="notice-body">
      <p class="notice-eyebrow">Yi Mai · 活动通知</p>
      <h3><?php echo esc_html($notice['title'] ?? ''); ?></h3>
      <div class="notice-content"><?php echo wp_kses_post(nl2br(esc_html($notice['content'] ?? ''))); ?></div>
      <?php $noticeLink = yimai_notice_link($notice); ?>
      <?php if ($noticeLink): ?>
        <a class="button primary notice-cta" href="<?php echo esc_url($noticeLink); ?>" data-notice-cta><?php echo esc_html(trim((string) ($notice['linkText'] ?? '')) !== '' ? $notice['linkText'] : '查看详情'); ?></a>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?>
</body>
</html>
