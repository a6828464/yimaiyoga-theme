<?php
/**
 * 一麦官网内容后台（移植自旧站 yimaiyoga.com admin）
 * 入口：/admin （由 functions.php 的 template_redirect 路由）
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) {
    // 直接访问时引导到 WordPress
    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $up = dirname(dirname(dirname($base)));
    header('Location: ' . $up . '/');
    exit;
}

// 会话
if (session_status() === PHP_SESSION_NONE) {
    session_name('yimai_admin_session');
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 8,
        'path' => '/',
        'secure' => is_ssl(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// 加载后台辅助逻辑
require_once __DIR__ . '/inc/bootstrap.php';
require_once YIMAI_THEME_DIR . '/inc/updater.php';

// 一次性安全迁移：作废旧版可推导默认口令（幂等，只真正执行一次）
yimai_migrate_legacy_default_password();

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$path = rtrim((string) $path, '/') ?: '/admin';

switch (true) {
    case $path === '/admin/login':
        if (request_method() === 'POST') {
            verify_csrf(true);
            $username = trim((string) wp_unslash($_POST['username'] ?? ''));
            $password = (string) wp_unslash($_POST['password'] ?? '');
            if (admin_is_locked_out()) {
                $_SESSION['flash_error'] = '尝试次数过多，请 ' . ceil(admin_lockout_remaining() / 60) . ' 分钟后再试';
                redirect_to(yimai_admin_path('login'));
            }
            if (admin_login($username, $password)) {
                redirect_to(yimai_admin_path());
            }
            $_SESSION['flash_error'] = '账号或密码错误';
            redirect_to(yimai_admin_path('login'));
        }
        render_view('login');
        break;

    case $path === '/admin/logout':
        admin_logout();
        redirect_to(yimai_admin_path('login'));
        break;

    case $path === '/admin/save':
        require_admin();
        if (request_method() !== 'POST') {
            json_response(['message' => 'Method Not Allowed'], 405);
        }
        verify_csrf();
        $raw = (string) wp_unslash($_POST['config_json'] ?? '');
        $config = json_decode($raw, true);
        if (!is_array($config)) {
            json_response(['message' => '配置 JSON 格式错误'], 400);
        }
        try {
            save_config($config);
            json_response(['message' => '已保存']);
        } catch (Yimai_Config_Type_Error $error) {
            // 类型不符：明确告知字段路径，不写库
            json_response(['message' => '配置类型不正确：' . $error->getMessage()], 400);
        } catch (Throwable $error) {
            json_response(['message' => '保存失败：' . $error->getMessage()], 500);
        }
        break;

    case $path === '/admin/upload':
        require_admin();
        if (request_method() !== 'POST') {
            json_response(['message' => 'Method Not Allowed'], 405);
        }
        verify_csrf();
        try {
            $result = save_uploaded_image($_FILES['file'] ?? [], (string) wp_unslash($_POST['field'] ?? ''));
        } catch (Throwable $error) {
            // 任何未预期异常都返回可读错误，而不是 500 白页
            json_response(['message' => '上传失败：' . $error->getMessage()], 500);
        }
        if (!$result['ok']) {
            json_response(['message' => $result['message']], 400);
        }
        json_response(['path' => $result['path'], 'imgbed' => $result['imgbed'] ?? null]);
        break;

    case $path === '/admin/imgbed-sync':
        require_admin();
        if (request_method() !== 'POST') {
            json_response(['message' => 'Method Not Allowed'], 405);
        }
        verify_csrf();
        @set_time_limit(60);
        $result = yimai_imgbed_sync_existing((string) wp_unslash($_POST['path'] ?? ''));
        json_response($result, $result['ok'] ? 200 : 400);
        break;

    case $path === '/admin/changepass':
        require_admin();
        if (request_method() !== 'POST') {
            json_response(['message' => 'Method Not Allowed'], 405);
        }
        verify_csrf();
        $old = (string) wp_unslash($_POST['old_password'] ?? '');
        $new = (string) wp_unslash($_POST['new_password'] ?? '');
        if (!admin_login(admin_username(), $old)) {
            json_response(['message' => '原密码不正确'], 400);
        }
        if (strlen($new) < 8) {
            json_response(['message' => '新密码至少 8 位'], 400);
        }
        change_admin_password($new);
        json_response(['message' => '密码已修改']);
        break;

    case $path === '/admin/library':
        require_admin();
        json_response(yimai_image_library());
        break;

    case $path === '/admin/update-check':
        require_admin();
        json_response(yimai_updater_check());
        break;

    case $path === '/admin/update-run':
        require_admin();
        if (request_method() !== 'POST') {
            json_response(['message' => 'Method Not Allowed'], 405);
        }
        verify_csrf();
        @set_time_limit(300);
        json_response(yimai_updater_run());
        break;

    default:
        require_admin();
        render_view('dashboard');
        break;
}
