<?php
/**
 * 门店详情页模板：/studio/east（东部店 · 鄞州杉杉国贸天地）
 *
 * 为什么文件名是 page-east.php：WP 的页面模板层级取「页面 slug」——
 * /studio/east 的子页面 slug 是 east，core 的 get_page_template() 会把
 * 查询变量 pagename 裁成末段后去找 page-east.php（见 wp-includes/class-wp-query.php）。
 * 页面本身需要在后台创建：父级选「空间与门店」（/studio），slug 填 east。
 *
 * 内容全部由 template-parts/studio-detail.php 渲染，本文件只负责把门店 key 传进去。
 *
 * @package yimaiyoga
 */

get_header();
get_template_part('template-parts/studio-detail', null, ['key' => 'studio-east']);
get_footer();
