<?php
/**
 * 404 页面：对应原站 views/pages/not-found.php
 *
 * @package yimaiyoga
 */

get_header();
?>
<section class="statement page-block">
  <p class="eyebrow">404</p>
  <h1>页面不存在</h1>
  <p>你访问的页面可能已移动或暂时不可用。</p>
  <a class="button primary" href="<?php echo esc_url(home_url('/')); ?>">返回首页</a>
</section>
<?php
get_footer();
