<?php
/**
 * 门店详情页模板：/studio/greenland（绿地店 · 江北绿地缤纷城）
 *
 * 与 page-east.php 同构，只有门店 key 不同；模板层级规则见 page-east.php 的文件头。
 * 页面本身需要在后台创建：父级选「空间与门店」（/studio），slug 填 greenland。
 *
 * @package yimaiyoga
 */

get_header();
get_template_part('template-parts/studio-detail', null, ['key' => 'studio-greenland']);
get_footer();
