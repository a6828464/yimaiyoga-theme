<?php
/**
 * 页头模板：对应原站 views/layouts/main.php 的 <head> 与 views/partials/nav.php
 *
 * @package yimaiyoga
 */

$config    = yimai_config();
$site      = $config['site'] ?? [];
$vars      = yimai_theme_vars();
$style     = '';
foreach ($vars as $key => $value) {
    $style .= '--color-' . $key . ':' . $value . ';--color-' . $key . '-rgb:' . yimai_hex_to_rgb($value) . ';';
}
$favicon     = $site['favicon'] ?? '';
?>
<!doctype html>
<html <?php language_attributes(); ?> style="<?php echo esc_attr($style); ?>">
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php // meta description / keywords 由 functions.php 的 wp_head 钩子统一输出，此处不再重复。 ?>
<?php if ($favicon): ?><link rel="icon" href="<?php echo esc_url(yimai_image_url($favicon)); ?>"><?php endif; ?>
<?php wp_head(); ?>
<noscript><style>.reveal{opacity:1;transform:none}</style></noscript>
</head>
<body <?php body_class(); ?> id="top">
<?php wp_body_open(); ?>
<header class="site-header">
  <nav class="nav-shell">
    <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
      <?php if (!empty($site['logo'])): ?>
        <img class="brand-logo" src="<?php echo esc_url(yimai_image_url($site['logo'])); ?>" alt="<?php echo esc_attr($site['brand'] ?? 'YI MAI'); ?>" style="height:<?php echo esc_attr((int) ($site['logoHeight'] ?? 13)); ?>px" loading="lazy">
      <?php else: ?>
        <?php echo esc_html($site['brand'] ?? 'YI MAI'); ?>
      <?php endif; ?>
    </a>
    <div class="nav-links">
      <?php foreach (yimai_nav_items() as $item): ?>
        <?php
        $href = $item['href'];
        if ($href === '/') {
            $href = home_url('/');
        } else {
            $href = home_url($href);
        }
        $active = (is_front_page() && $item['href'] === '/') ? 'active' : '';
        if (!$active && $item['href'] !== '/') {
            $slug = ltrim($item['href'], '/');
            if (is_page($slug)) {
                $active = 'active';
            }
        }
        ?>
        <a class="<?php echo esc_attr($active); ?>" href="<?php echo esc_url($href); ?>"><?php echo esc_html($item['label']); ?></a>
      <?php endforeach; ?>
    </div>
    <a class="nav-cta" href="<?php echo esc_url(home_url('/booking')); ?>">预约体验</a>
    <button class="menu-toggle" type="button" data-menu-toggle aria-label="打开菜单" aria-expanded="false" aria-controls="mobile-menu">菜单</button>
  </nav>
  <div class="mobile-menu" id="mobile-menu" data-mobile-menu>
    <?php foreach (yimai_nav_items() as $item): ?>
      <?php
      $href = ($item['href'] === '/') ? home_url('/') : home_url($item['href']);
      ?>
      <a href="<?php echo esc_url($href); ?>"><?php echo esc_html($item['label']); ?></a>
    <?php endforeach; ?>
  </div>
</header>
<main>
