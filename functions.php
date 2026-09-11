<?php
/**
 * 一麦瑜伽官网 WordPress 主题
 * 由 yimaiyoga.com PHP MVC 官网转换而来。
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) {
    exit;
}

define('YIMAI_VERSION', '1.2.1');
define('YIMAI_THEME_DIR', get_template_directory());
define('YIMAI_THEME_URI', get_template_directory_uri());

require_once YIMAI_THEME_DIR . '/inc/site-data.php';
require_once YIMAI_THEME_DIR . '/inc/imgbed.php';

/* -------------------------------------------------------------------------
 * 主题支持
 * ---------------------------------------------------------------------- */
function yimai_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('custom-logo', ['height' => 64, 'width' => 220, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('align-wide');

    register_nav_menus([
        'primary' => '主导航',
        'footer'  => '页脚导航',
    ]);
}
add_action('after_setup_theme', 'yimai_setup');

/* -------------------------------------------------------------------------
 * 样式与脚本
 * ---------------------------------------------------------------------- */
function yimai_enqueue(): void
{
    wp_enqueue_style('yimai-app', YIMAI_THEME_URI . '/assets/css/app.css', [], YIMAI_VERSION);
    wp_enqueue_style('yimai-fix', YIMAI_THEME_URI . '/assets/css/fix.css', ['yimai-app'], YIMAI_VERSION);
    wp_enqueue_style('yimai-mobile-fix', YIMAI_THEME_URI . '/assets/css/mobile-fix.css', ['yimai-app'], YIMAI_VERSION);
    wp_enqueue_script('yimai-app', YIMAI_THEME_URI . '/assets/js/app.js', [], YIMAI_VERSION, true);

    wp_localize_script('yimai-app', 'yimaiAjax', [
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yimai_booking'),
    ]);
}
add_action('wp_enqueue_scripts', 'yimai_enqueue');

/* -------------------------------------------------------------------------
 * 辅助函数（对应原站 app/helpers.php 与 app/themes.php）
 * ---------------------------------------------------------------------- */
function yimai_config(): array
{
    return yimai_site_data();
}

/**
 * 服务器本地密钥（inc/local-secrets.php，git 忽略，不随更新包分发）。
 * 返回形如 ['wecom_webhook' => 'https://...'] 的数组。
 */
function yimai_site_secrets(): array
{
    static $secrets = null;
    if ($secrets === null) {
        $file = YIMAI_THEME_DIR . '/inc/local-secrets.php';
        $secrets = is_file($file) ? (array) require $file : [];
    }
    return $secrets;
}

/**
 * 企业微信 Webhook 解析顺序：后台配置（wp_options）→ 服务器本地密钥文件 → 空。
 */
function yimai_wecom_webhook(): string
{
    $configured = trim((string) (yimai_config()['site']['wecomWebhook'] ?? ''));
    if ($configured !== '') {
        return $configured;
    }
    return trim((string) (yimai_site_secrets()['wecom_webhook'] ?? ''));
}

/* -------------------------------------------------------------------------
 * SEO：标题与描述（用后台配置的 site.title / site.description）
 * ---------------------------------------------------------------------- */
function yimai_document_title_parts(array $parts): array
{
    $config = yimai_config();
    $siteTitle = trim((string) ($config['site']['title'] ?? ''));
    if ($siteTitle !== '') {
        if (is_front_page() || is_home()) {
            $parts['title'] = $siteTitle;
        } else {
            // 子页面：页面名 | 网站名（用配置里的主标题）
            $configName = trim((string) ($config['site']['name'] ?? ''));
            $parts['title'] = ($parts['title'] ?? '') . ' | ' . ($configName !== '' ? $configName : $siteTitle);
        }
    }
    return $parts;
}
add_filter('document_title_parts', 'yimai_document_title_parts', 20);

function yimai_meta_description(): void
{
    $config = yimai_config();
    $desc = trim((string) ($config['site']['description'] ?? ''));
    if ($desc !== '') {
        echo "\n<meta name=\"description\" content=\"" . esc_attr($desc) . "\">";
    }
}
add_action('wp_head', 'yimai_meta_description', 1);

function yimai_theme_vars(): array
{
    $config = yimai_config();
    $themes = [
        'linen' => '#EEEAE2', 'oat' => '#CFC7B8', 'clay' => '#8E7660',
        'moss' => '#6A6B61', 'forest' => '#11110F', 'sage' => '#A9A495',
        'rose' => '#B69B8B', 'ink' => '#151511', 'pearl' => '#F9F6EE',
        'stone' => '#BAB1A2', 'smoke' => '#77736A',
    ];
    $id = $config['site']['theme'] ?? 'ebony-ivory';
    if ($id === 'custom' && !empty($config['site']['customTheme'])) {
        return array_merge($themes, $config['site']['customTheme']);
    }
    // 主题在转换时已固定为 ebony-ivory（黑檀米白），如需切换配色可改此数组。
    return $themes;
}

function yimai_hex_to_rgb(string $hex): string
{
    $value = ltrim($hex, '#');
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $value)) {
        return '21 21 17';
    }
    return hexdec(substr($value, 0, 2)) . ' ' . hexdec(substr($value, 2, 2)) . ' ' . hexdec(substr($value, 4, 2));
}

function yimai_image_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return YIMAI_THEME_URI . '/assets/css/placeholder.svg';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    // 主题内图片目录 assets/images/uploads/ 对应原站 /uploads/
    // 已同步图床的文件优先走图床（app.js 在加载失败时按 footer 的兜底映射换回本地）
    $map = yimai_imgbed_map();
    if (isset($map[$path])) {
        $config = yimai_imgbed_config();
        if ($config !== []) {
            return $config['domain'] . '/' . $map[$path];
        }
    }
    return yimai_local_image_url($path);
}

function yimai_nav_items(): array
{
    $config = yimai_config();
    return $config['nav_items'] ?? [];
}

function yimai_studios(): array
{
    $config = yimai_config();
    return $config['studios'] ?? [];
}

function yimai_memberships(): array
{
    $config = yimai_config();
    return $config['memberships'] ?? [];
}

function yimai_faqs(): array
{
    $config = yimai_config();
    return $config['faqs'] ?? [];
}

/* -------------------------------------------------------------------------
 * 预约表单 AJAX 处理（对应原站 actions/booking-submit.php）
 * 前端 app.js 会把表单提交到 admin-ajax.php。
 * ---------------------------------------------------------------------- */
function yimai_handle_booking(): void
{
    check_ajax_referer('yimai_booking', 'nonce');

    if (!empty($_POST['website'])) {
        wp_send_json_success(['message' => 'ok']);
    }

    $name    = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $phone   = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $studio  = sanitize_text_field(wp_unslash($_POST['studio'] ?? ''));
    $interest = sanitize_text_field(wp_unslash($_POST['interest'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));

    if ($name === '' || $phone === '') {
        wp_send_json_error(['message' => '姓名和手机不能为空'], 400);
    }
    if (!preg_match('/^1[3-9]\d{9}$|^0\d{2,3}-?\d{7,8}$/', $phone)) {
        wp_send_json_error(['message' => '请填写正确的手机号或门店电话'], 400);
    }
    if (mb_strlen($message) > 500) {
        wp_send_json_error(['message' => '备注不能超过 500 字'], 400);
    }

    $webhook = yimai_wecom_webhook();

    if ($webhook !== '') {
        $payload = [
            'name' => $name,
            'phone' => $phone,
            'studio' => $studio,
            'interest' => $interest,
            'message' => $message,
        ];
        $content = "一麦官网预约意向\n姓名：{$payload['name']}\n手机：{$payload['phone']}\n门店：{$payload['studio']}\n方向：{$payload['interest']}\n备注：{$payload['message']}";
        $resp = wp_remote_post($webhook, [
            'timeout' => 15,
            'body' => wp_json_encode(['msgtype' => 'text', 'text' => ['content' => $content]]),
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
        ]);
        if (is_wp_error($resp)) {
            wp_send_json_error(['message' => '提交失败，请稍后再试或电话联系门店。'], 502);
        }
        $body = wp_remote_retrieve_body($resp);
        $json = json_decode($body, true);
        if (empty($json['errcode'])) {
            wp_send_json_success(['message' => '已提交，我们会尽快联系你。']);
        }
        wp_send_json_error(['message' => '提交失败，请稍后再试或电话联系门店。'], 502);
    }

    // 无 webhook 时也按成功处理（可在此扩展邮件通知）
    wp_send_json_success(['message' => '已提交，我们会尽快联系你。']);
}
add_action('wp_ajax_nopriv_yimai_booking', 'yimai_handle_booking');
add_action('wp_ajax_yimai_booking', 'yimai_handle_booking');


/* -------------------------------------------------------------------------
 * 网站内容后台路由（/admin）
 * 移植自旧站 yimaiyoga.com admin，配置存 wp_options（yimai_site_config）
 * ---------------------------------------------------------------------- */
function yimai_admin_route(): void
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = rtrim((string) $path, '/') ?: '/';

    if ($path === '/admin' || str_starts_with($path, '/admin/')) {
        require YIMAI_THEME_DIR . '/admin/index.php';
        exit;
    }
}
add_action('template_redirect', 'yimai_admin_route', 1);
add_action('init', 'yimai_admin_route', 1);
