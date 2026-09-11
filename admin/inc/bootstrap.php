<?php
/**
 * 后台辅助函数（移植自旧站 app/*.php，配置存 WP 数据库）
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) { exit; }

define('YIMAI_ADMIN_VIEWS', __DIR__ . '/../views');

/* ---------- 基础 ---------- */
function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect_to(string $path): void
{
    header('Location: ' . $path, true, 302);
    exit;
}

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/* ---------- 配置读写（存 wp_options，键名 yimai_site_config） ---------- */
function yimai_config_option_name(): string
{
    return 'yimai_site_config';
}

function default_config(): array
{
    return yimai_site_data();
}

function load_config(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }
    $raw = get_option(yimai_config_option_name(), '');
    $config = $raw ? json_decode((string) $raw, true) : [];
    if (!is_array($config)) {
        $config = [];
    }
    $cache = array_replace_recursive(default_config(), $config);
    return $cache;
}

function save_config(array $config): void
{
    $config = array_replace_recursive(default_config(), $config);
    // 规范化数组列表
    foreach (['courseThemes', 'classPaths', 'instructors', 'studios', 'memberships', 'faqs', 'nav_items'] as $key) {
        $config[$key] = array_values($config[$key] ?? []);
    }
    $config['images']['studioImages'] = array_values($config['images']['studioImages'] ?? []);
    // 备份：保留最近 20 份
    $backups = get_option('yimai_site_config_backups', []);
    $backups = is_array($backups) ? $backups : [];
    $backups[] = ['time' => current_time('mysql'), 'data' => $config];
    if (count($backups) > 20) {
        $backups = array_slice($backups, -20);
    }
    update_option('yimai_site_config_backups', $backups, false);
    update_option(yimai_config_option_name(), wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), false);
}

/* ---------- 认证（账号 admin，密码哈希存 wp_options） ---------- */
function admin_username(): string
{
    return 'admin';
}

function admin_password_hash(): string
{
    $hash = get_option('yimai_admin_password_hash', '');
    if ($hash !== '') {
        return $hash;
    }
    // 初始密码在安装时生成
    $hash = wp_hash_password('Yimai@' . date('Ymd'));
    update_option('yimai_admin_password_hash', $hash, false);
    return $hash;
}

function admin_login(string $username, string $password): bool
{
    if (!hash_equals(admin_username(), $username)) {
        return false;
    }
    if (!wp_check_password($password, admin_password_hash(), '')) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_login_at'] = time();
    return true;
}

function change_admin_password(string $new): void
{
    update_option('yimai_admin_password_hash', wp_hash_password($new), false);
}

function admin_logout(): void
{
    $_SESSION = [];
    session_destroy();
}

function yimai_is_admin(): bool
{
    return !empty($_SESSION['admin_authenticated']);
}

function require_admin(): void
{
    if (!yimai_is_admin()) {
        redirect_to('/admin/login');
    }
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        json_response(['message' => 'CSRF 校验失败'], 419);
    }
}

/* ---------- 上传（存主题 assets/images/uploads，对应原站 /uploads/） ---------- */
function upload_path(): string
{
    return get_template_directory() . '/assets/images/uploads';
}

function upload_url_base(): string
{
    return get_template_directory_uri() . '/assets/images';
}

function save_uploaded_image(array $file, string $field = ''): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => '请选择图片文件'];
    }
    if (($file['size'] ?? 0) > 8 * 1024 * 1024) {
        return ['ok' => false, 'message' => '图片不能超过 8MB'];
    }
    $tmp = $file['tmp_name'] ?? '';
    $info = @getimagesize($tmp);
    if (!$info) {
        return ['ok' => false, 'message' => '只能上传图片'];
    }
    $mime = $info['mime'] ?? '';
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'message' => '仅支持 JPG、PNG、WebP、GIF'];
    }
    $dir = upload_path();
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }
    $isFavicon = $field === 'site.favicon' || preg_match('/favicon/i', (string) ($file['name'] ?? ''));
    // 需要保留透明/矢量感的字段：logo、favicon、二维码（按字段判断，不依赖文件名）
    $pngFields = ['site.logo', 'site.favicon', 'site.wechatQr'];
    $keepPng = $mime === 'image/png' && (in_array($field, $pngFields, true) || $isFavicon || preg_match('/(qr|code|logo)/i', (string) ($file['name'] ?? '')));
    $ext = $mime === 'image/gif' ? 'gif' : (($keepPng || $isFavicon) ? 'png' : 'jpg');
    $name = time() . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    $target = $dir . '/' . $name;

    if ($mime === 'image/gif' || !function_exists('imagecreatetruecolor')) {
        move_uploaded_file($tmp, $target);
        return ['ok' => true, 'path' => '/uploads/' . $name];
    }

    [$width, $height] = $info;

    // 按用途限制最大尺寸：logo / favicon / 二维码小图，其余页面大图
    $maxByField = [
        'site.logo'     => 600,
        'site.favicon'  => 256,
        'site.wechatQr' => 1000,
        'images.homeHero'   => 1800,
        'images.homeStudio' => 1800,
        'images.homeStory'  => 1800,
        'images.bookingHero'=> 1600,
        'images.studioHero' => 1800,
    ];
    $max = $maxByField[$field] ?? 1800;
    $ratio = min(1, $max / max($width, $height));
    $newWidth = max(1, (int) round($width * $ratio));
    $newHeight = max(1, (int) round($height * $ratio));
    $source = match ($mime) {
        'image/png' => imagecreatefrompng($tmp),
        'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($tmp) : null,
        default => imagecreatefromjpeg($tmp),
    };
    if (!$source) {
        move_uploaded_file($tmp, $target);
        return ['ok' => true, 'path' => '/uploads/' . $name];
    }
    $canvas = imagecreatetruecolor($newWidth, $newHeight);
    // 透明通道：先填充透明色，避免 PNG 透明区域变黑
    imagesavealpha($canvas, true);
    imagealphablending($canvas, false);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    if ($isFavicon) {
        // favicon 统一转 PNG 白色底（浏览器兼容）
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagepng($canvas, $target, 9);
    } elseif ($keepPng) {
        imagepng($canvas, $target, 9);
    } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
        imagewebp($canvas, $target, 84);
    } else {
        imagejpeg($canvas, $target, 86);
    }
    imagedestroy($source);
    imagedestroy($canvas);
    return ['ok' => true, 'path' => '/uploads/' . $name];
}

/* ---------- 渲染 ---------- */
function render_view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $config = load_config();
    $file = YIMAI_ADMIN_VIEWS . '/' . $name . '.php';
    if (!is_file($file)) {
        http_response_code(500);
        echo 'view missing: ' . h($name);
        exit;
    }
    include $file;
}
