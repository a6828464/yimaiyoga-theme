<?php
/**
 * 404 页面：对应原站 views/pages/not-found.php
 *
 * SEO：404 页禁止索引、保留链接跟随。
 * 主实现是 functions.php 的 yimai_robots_404（挂 wp_robots 过滤器，能与其他
 * robots 指令合并）。此处仅在它不可用时（例如模板被单独复用）兜底注册一个
 * wp_robots 过滤器，因此必须写在 get_header() 之前——wp_head() 在 header.php
 * 内就已经执行。两个回调即使同时生效也不会重复输出：wp_robots 的指令是按
 * 键名去重的关联数组。
 *
 * @package yimaiyoga
 */

if (!has_filter('wp_robots', 'yimai_robots_404')) {
    add_filter('wp_robots', function (array $robots): array {
        $robots['noindex'] = true;
        $robots['follow']  = true;
        return $robots;
    }, 20);
}

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
