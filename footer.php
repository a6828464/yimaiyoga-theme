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
<?php $imgbedFallback = function_exists('yimai_imgbed_fallback_map') ? yimai_imgbed_fallback_map() : []; ?>
<?php if ($imgbedFallback): ?>
<script>window.YIMAI_IMG_FALLBACK = <?php echo wp_json_encode($imgbedFallback, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;</script>
<?php endif; ?>
</body>
</html>
