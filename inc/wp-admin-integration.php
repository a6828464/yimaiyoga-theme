<?php
/**
 * WP 后台集成（/wp-admin → 「一麦官网」菜单）
 *
 * 架构：旧的独立 /admin 入口（admin/index.php）保留不动作为兜底；
 * 这里把同一套后台挂进 wp-admin：
 * - 页面：add_menu_page 注册「一麦官网」，回调渲染 admin/views/dashboard.php
 * - 数据接口：admin-ajax.php（action=yimai_admin_api&op=save|upload|imgbed-sync|library|update-check|update-run）
 * - 认证：current_user_can('manage_options')；CSRF 用 wp_create_nonce('yimai_admin_api')
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) { exit; }

require_once YIMAI_THEME_DIR . '/admin/inc/bootstrap.php';
require_once YIMAI_THEME_DIR . '/inc/updater.php';

const YIMAI_ADMIN_MENU_SLUG = 'yimai-site';
const YIMAI_ADMIN_NONCE_ACTION = 'yimai_admin_api';

/** wp-admin 内后台页 URL（取代独立入口的 yimai_admin_path()） */
function yimai_wpadmin_url(array $args = []): string
{
    return add_query_arg(array_merge(['page' => YIMAI_ADMIN_MENU_SLUG], $args), admin_url('admin.php'));
}

add_action('admin_menu', function (): void {
    add_menu_page(
        '一麦官网 · 内容后台',
        '一麦官网',
        'manage_options',
        YIMAI_ADMIN_MENU_SLUG,
        'yimai_wpadmin_render',
        // 内联 SVG 图标（莲花/叶形，沿用主题色系）
        'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 21c-4.5-2.2-7-5.3-7-9.2C5 8 8 5.6 12 3c4 2.6 7 5 7 8.8 0 3.9-2.5 7-7 9.2z" stroke="#a9795f" stroke-width="1.6"/><path d="M12 21c-1.8-3.4-1.8-8 0-12 1.8 4 1.8 8.6 0 12z" fill="#a9795f"/></svg>'
        ),
        58 // 排在「外观」之后附近
    );
});

/** 页面回调：渲染 dashboard 视图 */
function yimai_wpadmin_render(): void
{
    if (!current_user_can('manage_options')) {
        wp_die('权限不足');
    }
    render_view('dashboard');
}

/**
 * 后台数据接口（admin-ajax.php）
 * 所有 op 都要求 manage_options + nonce 校验，返回 JSON。
 */
add_action('wp_ajax_yimai_admin_api', function (): void {
    if (!current_user_can('manage_options')) {
        json_response(['message' => '权限不足'], 403);
    }
    // 我们的前端走自定义 csrf_token 字段或 X-CSRF-TOKEN 头，check_ajax_referer
    // 默认只读 _ajax_nonce/_wpnonce/QUERY，故先回填。
    if (empty($_REQUEST['csrf_token'])) {
        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (is_string($headerToken) && $headerToken !== '') {
            $_REQUEST['csrf_token'] = $headerToken;
        }
    }
    // 不用 check_ajax_referer：它失败时 wp_die('-1')，前端 res.json() 拿到裸 -1、
    // 没有 message 字段，只能显示一句无信息量的「保存失败」。这里自行校验并返回
    // 可读原因——校验强度等同（wp_verify_nonce，12/24 小时时限不变）。
    $nonce = $_REQUEST['csrf_token'] ?? '';
    if (!is_string($nonce) || !wp_verify_nonce($nonce, YIMAI_ADMIN_NONCE_ACTION)) {
        json_response(['message' => '页面令牌已过期（通常超过 24 小时未刷新），请按 Ctrl+F5 刷新本页后重新保存'], 403);
    }

    $op = (string) ($_REQUEST['op'] ?? '');

    switch ($op) {
        case 'save':
            $raw = (string) wp_unslash($_POST['config_json'] ?? '');
            $config = json_decode($raw, true);
            if (!is_array($config)) {
                json_response(['message' => '配置 JSON 格式错误'], 400);
            }
            try {
                save_config($config);
                json_response(['message' => '已保存']);
            } catch (Yimai_Config_Type_Error $error) {
                json_response(['message' => '配置类型不正确：' . $error->getMessage()], 400);
            } catch (Throwable $error) {
                json_response(['message' => '保存失败：' . $error->getMessage()], 500);
            }
            // no break — json_response exits

        case 'upload':
            try {
                $result = save_uploaded_image($_FILES['file'] ?? [], (string) wp_unslash($_POST['field'] ?? ''));
            } catch (Throwable $error) {
                json_response(['message' => '上传失败：' . $error->getMessage()], 500);
            }
            if (!$result['ok']) {
                json_response(['message' => $result['message']], 400);
            }
            json_response(['path' => $result['path'], 'imgbed' => $result['imgbed'] ?? null]);

        case 'imgbed-sync':
            @set_time_limit(60);
            $result = yimai_imgbed_sync_existing((string) wp_unslash($_POST['path'] ?? ''));
            json_response($result, $result['ok'] ? 200 : 400);

        case 'library':
            json_response(yimai_image_library());

        case 'update-check':
            json_response(yimai_updater_check());

        case 'update-run':
            @set_time_limit(300);
            json_response(yimai_updater_run());

        case 'update-upload':
            @set_time_limit(300);
            json_response(yimai_updater_run_upload($_FILES['file'] ?? []));

        case 'ping':
            json_response(['ok' => true, 'pong' => time()]);

        default:
            json_response(['message' => '未知操作：' . $op], 400);
    }
});
