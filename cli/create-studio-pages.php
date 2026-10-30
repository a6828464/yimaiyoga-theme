#!/usr/bin/env php
<?php
/**
 * 门店详情页生成工具（服务器端一次性运行）
 *
 * 为什么需要它：/studio/east 与 /studio/greenland 是「空间与门店」(/studio) 的子页面。
 * WordPress 的页面 slug 由标题自动生成——中文标题会变成一串 URL 编码（%e4%b8%9c…），
 * 而模板层级 page-east.php / page-greenland.php 认的是 **英文 slug**。
 * 在后台手建页面时很容易把 slug 留成中文，页面就会掉到 index.php 变成空白页。
 * 这个脚本按注册表把两个子页面建好，slug 一定是 east / greenland。
 *
 * 用法（在 WordPress 根目录或任意目录均可）：
 *   php wp-content/themes/yimaiyoga/cli/create-studio-pages.php
 *
 * 安全说明：
 * - 只在 CLI 下运行，Web 访问直接拒绝
 * - 幂等：已存在的页面只报告、不覆盖（不会改动后台编辑过的标题/内容）
 * - 前置条件：先有 slug 为 studio 的「空间与门店」页面
 * - 门店数据（地址/电话/营业时间）仍由后台「门店」维护，本脚本只建页面壳子
 *
 * @package yimaiyoga
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("此脚本仅允许命令行运行。\n");
}

// 定位 wp-load.php：从主题目录向上找到 WordPress 根
$dir = __DIR__;
$root = null;
for ($i = 0; $i < 8; $i++) {
    $dir = dirname($dir);
    if (is_file($dir . '/wp-load.php')) {
        $root = $dir;
        break;
    }
}
if ($root === null) {
    fwrite(STDERR, "未找到 wp-load.php，请在 WordPress 站点内运行。\n");
    exit(1);
}

define('WP_USE_THEMES', false);
require $root . '/wp-load.php';

if (!function_exists('wp_insert_post')) {
    fwrite(STDERR, "WordPress 未正确加载。\n");
    exit(1);
}

$themeDir = get_template_directory();
require_once $themeDir . '/inc/studio-pages.php';

$parent = get_page_by_path('studio');
if (!$parent instanceof WP_Post) {
    fwrite(STDERR, "未找到 /studio 页面。请先在后台创建「空间与门店」页面并把 slug 设为 studio，再运行本脚本。\n");
    exit(1);
}

fwrite(STDOUT, "父页面：{$parent->post_title}（ID {$parent->ID}）\n\n");

$created = 0;
$skipped = 0;
$failed = 0;

foreach (yimai_studio_page_registry() as $key => $entry) {
    $path = 'studio/' . $entry['slug'];
    $existing = get_page_by_path($path, OBJECT, 'page');
    if ($existing instanceof WP_Post) {
        $skipped++;
        fwrite(STDOUT, "跳过 {$path}：已存在（ID {$existing->ID}，标题「{$existing->post_title}」）\n");
        continue;
    }

    $data = yimai_studio_page_data($key);
    $title = $data !== [] ? yimai_studio_page_h1($data['studio'], $data['entry']) : $entry['name'];

    $pageId = wp_insert_post([
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'post_title'     => $title,
        'post_name'      => $entry['slug'],
        'post_parent'    => $parent->ID,
        'post_content'   => '',
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
    ], true);

    if (is_wp_error($pageId) || (int) $pageId <= 0) {
        $failed++;
        $reason = is_wp_error($pageId) ? $pageId->get_error_message() : '未知错误';
        fwrite(STDERR, "失败 {$path}：{$reason}\n");
        continue;
    }

    $created++;
    fwrite(STDOUT, "已创建 {$path}（ID " . (int) $pageId . "，标题「{$title}」）\n");
    fwrite(STDOUT, "  → " . home_url('/' . $path . '/') . "\n");
}

fwrite(STDOUT, "\n结果：新建 {$created}，跳过 {$skipped}，失败 {$failed}。\n");
if ($failed > 0) {
    exit(1);
}
fwrite(STDOUT, "自检：前台打开两个门店页，确认 H1 含「宁波 + 区 + 业务 + 店名」、电话可拨、页面源码里有 HealthAndBeautyBusiness 与 FAQPage。\n");
exit(0);
