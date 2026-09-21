#!/usr/bin/env php
<?php
/**
 * 一麦官网后台 · 初始密码设置工具（服务器端运行）
 *
 * 为什么需要它：后台口令不能有「可推导的默认值」（旧实现 Yimai@+当天日期，
 * 任何人读过源码即可抢先登录）。因此初始密码必须由拥有服务器访问权限的人
 * 在服务器上设置——服务器权限本身就是真实信任边界。
 *
 * 用法（在 WordPress 根目录）：
 *   php wp-content/themes/yimaiyoga/cli/set-admin-password.php
 *   或指定密码（注意 shell 历史残留）：
 *   php .../set-admin-password.php '你的新密码'
 *
 * 安全说明：
 * - 只在 CLI 下运行，Web 访问直接拒绝
 * - 密码最少 12 位
 * - 设置成功后删除占位哈希，正常走 wp_hash_password
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

if (!function_exists('wp_hash_password')) {
    fwrite(STDERR, "WordPress 未正确加载。\n");
    exit(1);
}

$password = $argv[1] ?? '';
if ($password === '') {
    fwrite(STDOUT, "请输入新的后台密码（至少 12 位，输入不回显）：");
    // 关闭回显（Windows 下退化为可见输入）
    $isWindows = stripos(PHP_OS_FAMILY, 'Windows') !== false;
    if (!$isWindows) {
        shell_exec('stty -echo 2>/dev/null');
    }
    $password = rtrim((string) fgets(STDIN), "\r\n");
    if (!$isWindows) {
        shell_exec('stty echo 2>/dev/null');
    }
    fwrite(STDOUT, "\n");
}

if (strlen($password) < 12) {
    fwrite(STDERR, "密码至少 12 位。\n");
    exit(1);
}

update_option('yimai_admin_password_hash', wp_hash_password($password), false);
// 顺带清掉历史遗留的失败计数，避免刚设置完就被锁定
update_option('yimai_admin_login_attempts', ['count' => 0, 'last' => 0], false);

fwrite(STDOUT, "✓ 后台密码已更新（账号：admin）\n");
fwrite(STDOUT, "  登录地址：" . home_url('/admin/login') . "\n");
exit(0);
