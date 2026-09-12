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

define('YIMAI_VERSION', '1.5.0');
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

/**
 * 全站主题预设（后台「全站主题」可一键切换）。
 * 每套完整定义 11 个前台颜色变量：pearl 页面底色 / linen 区块底色 / oat 描边 /
 * clay 强调色 / moss 辅助色 / forest 深色块与标题 / ink 正文 / sage rose stone smoke 点缀。
 */
function yimai_site_theme_presets(): array
{
    return [
        'ebony-ivory' => [
            'name' => '黑檀米白',
            'desc' => '默认 · 现行配色',
            'sw' => ['#F9F6EE', '#EEEAE2', '#8E7660', '#11110F'],
            'colors' => [],
        ],
        'celadon' => [
            'name' => '青瓷苔绿',
            'desc' => '清淡东方 · 修复疗愈感',
            'sw' => ['#F6F9F4', '#E9F0E6', '#4C7A62', '#1A291F'],
            'colors' => [
                'pearl' => '#F6F9F4', 'linen' => '#E9F0E6', 'oat' => '#C7D5C2',
                'clay' => '#4C7A62', 'moss' => '#5E7A64', 'forest' => '#1A291F',
                'ink' => '#1A231C', 'sage' => '#9DB4A0', 'rose' => '#BFA398',
                'stone' => '#AEBFB0', 'smoke' => '#72837A',
            ],
        ],
        'terracotta' => [
            'name' => '陶土赤橘',
            'desc' => '赤陶暖调 · 活力温暖',
            'sw' => ['#FBF5EE', '#F4E8DA', '#B05A35', '#2C1F17'],
            'colors' => [
                'pearl' => '#FBF5EE', 'linen' => '#F4E8DA', 'oat' => '#E1CCB6',
                'clay' => '#B05A35', 'moss' => '#8A6A4F', 'forest' => '#2C1F17',
                'ink' => '#251A12', 'sage' => '#BCA88F', 'rose' => '#C98D6B',
                'stone' => '#CBB49B', 'smoke' => '#8C7761',
            ],
        ],
        'dusk' => [
            'name' => '黛蓝雾霭',
            'desc' => '沉静蓝灰 · 呼吸感',
            'sw' => ['#F5F7FA', '#E8EDF4', '#4A6591', '#1C2534'],
            'colors' => [
                'pearl' => '#F5F7FA', 'linen' => '#E8EDF4', 'oat' => '#C5CFDC',
                'clay' => '#4A6591', 'moss' => '#5D6E84', 'forest' => '#1C2534',
                'ink' => '#1A2130', 'sage' => '#A3AFC1', 'rose' => '#A79FB5',
                'stone' => '#B2BCCA', 'smoke' => '#76819A',
            ],
        ],
        'blush' => [
            'name' => '玫瑰暖沙',
            'desc' => '柔和玫瑰 · 温柔气质',
            'sw' => ['#FBF4F1', '#F4E6E0', '#A9605A', '#2D1E1A'],
            'colors' => [
                'pearl' => '#FBF4F1', 'linen' => '#F4E6E0', 'oat' => '#E3CBC2',
                'clay' => '#A9605A', 'moss' => '#7D6157', 'forest' => '#2D1E1A',
                'ink' => '#271B17', 'sage' => '#C0A89E', 'rose' => '#C98A82',
                'stone' => '#CCB3AB', 'smoke' => '#8C736A',
            ],
        ],
    ];
}

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
    $presets = yimai_site_theme_presets();
    if (isset($presets[$id]['colors']) && $presets[$id]['colors'] !== []) {
        return array_merge($themes, $presets[$id]['colors']);
    }
    // 主题在转换时已固定为 ebony-ivory（黑檀米白），可在后台「全站主题」切换配色。
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
        // 后台显式选择的图床 / 外链地址，直接使用（加载失败由 app.js 按 footer 兜底映射换回本地）
        return $path;
    }
    return yimai_local_image_url($path);
}

/* -------------------------------------------------------------------------
 * 活动公告（后台「活动公告」面板维护；首页弹窗 + 预约页活动条）
 * ---------------------------------------------------------------------- */

/**
 * 当前生效的活动：取列表中最后一条「启用中」且在起止日期内的活动。
 * 多条同时启用时，靠后的（后添加的）优先展示；没有则返回空数组。
 */
function yimai_active_announcement(): array
{
    $ann = yimai_config()['announcements'] ?? [];
    $items = is_array($ann['items'] ?? null) ? $ann['items'] : [];
    $now = current_time('timestamp');
    $picked = [];
    foreach ($items as $item) {
        if (!is_array($item) || empty($item['active'])) {
            continue;
        }
        if (trim((string) ($item['title'] ?? '')) === '' && trim((string) ($item['content'] ?? '')) === '') {
            continue;
        }
        $start = trim((string) ($item['start'] ?? ''));
        $end = trim((string) ($item['end'] ?? ''));
        if ($start !== '' && $now < (strtotime($start . ' 00:00:00') ?: 0)) {
            continue;
        }
        if ($end !== '' && $now > (strtotime($end . ' 23:59:59') ?: PHP_INT_MAX)) {
            continue;
        }
        $picked = $item;
    }
    return $picked;
}

/** 公告跳转链接：留空返回空；完整网址原样；否则按站点内路径处理 */
function yimai_notice_link(array $item): string
{
    $link = trim((string) ($item['link'] ?? ''));
    if ($link === '') {
        return '';
    }
    if (str_starts_with($link, 'http://') || str_starts_with($link, 'https://')) {
        return $link;
    }
    return home_url('/' . ltrim($link, '/'));
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
