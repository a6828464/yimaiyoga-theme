<?php
/**
 * 图床同步（image.shunan.fun，CloudFlare-ImgBed）
 *
 * 设计：本地文件始终是 source of truth；上传成功后把「本地相对路径 → 图床路径」
 * 写入 wp_options（yimai_imgbed_map）。前台 yimai_image_url() 命中映射时输出图床
 * 链接，footer.php 同时输出 imgbed→本地 的兜底映射，app.js 监听图片 onerror
 * 自动换回本地地址。图床域名与授权码存 inc/local-secrets.php（不进 git）。
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) {
    exit;
}

const YIMAI_IMGBED_MAP_OPTION = 'yimai_imgbed_map';

function yimai_imgbed_config(): array
{
    // 解析顺序与企微 webhook 一致：后台配置（DB）→ local-secrets.php → 空（仅存本地）
    $config = yimai_config()['site'] ?? [];
    $secrets = yimai_site_secrets();
    $domain = rtrim(trim((string) ($config['imgbedDomain'] ?? '')), '/');
    $code = trim((string) ($config['imgbedAuthCode'] ?? ''));
    if ($domain === '') {
        $domain = rtrim(trim((string) ($secrets['imgbed_domain'] ?? '')), '/');
    }
    if ($code === '') {
        $code = trim((string) ($secrets['imgbed_auth_code'] ?? ''));
    }
    if ($domain === '') {
        return [];
    }
    return ['domain' => $domain, 'auth_code' => $code];
}

function yimai_imgbed_map(): array
{
    $map = get_option(YIMAI_IMGBED_MAP_OPTION, []);
    return is_array($map) ? $map : [];
}

/** 把上传接口返回的图床路径（如 /file/xxx.png）与本地相对路径（/uploads/xxx.png）建立映射 */
function yimai_imgbed_remember(string $local_rel, string $imgbed_path): void
{
    $map = yimai_imgbed_map();
    $map[$local_rel] = ltrim($imgbed_path, '/');
    update_option(YIMAI_IMGBED_MAP_OPTION, $map, false);
}

/** 图床路径不存在时的清理（文件被删/换图床后可重传） */
function yimai_imgbed_forget(string $local_rel): void
{
    $map = yimai_imgbed_map();
    if (isset($map[$local_rel])) {
        unset($map[$local_rel]);
        update_option(YIMAI_IMGBED_MAP_OPTION, $map, false);
    }
}

/**
 * 上传一个本地文件到图床，成功返回图床路径（/file/xxx），失败返回 null（不抛异常，不阻塞本地上传）。
 */
function yimai_imgbed_upload(string $absolute_path, string $mime, string $filename): ?string
{
    $config = yimai_imgbed_config();
    if ($config === [] || !is_file($absolute_path)) {
        return null;
    }

    $endpoint = $config['domain'] . '/upload';
    if ($config['auth_code'] !== '') {
        $endpoint .= '?authCode=' . rawurlencode($config['auth_code']);
    }

    $boundary = 'yimai' . bin2hex(random_bytes(12));
    $payload = file_get_contents($absolute_path);
    if ($payload === false) {
        return null;
    }
    $body = "--{$boundary}\r\n"
        . 'Content-Disposition: form-data; name="file"; filename="' . addslashes($filename) . "\"\r\n"
        . 'Content-Type: ' . ($mime !== '' ? $mime : 'application/octet-stream') . "\r\n\r\n"
        . $payload . "\r\n"
        . "--{$boundary}--\r\n";

    $response = wp_remote_post($endpoint, [
        'timeout' => 20,
        'user-agent' => 'YimaiTheme/1.0',
        'headers' => ['Content-Type' => 'multipart/form-data; boundary=' . $boundary],
        'body' => $body,
    ]);
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }

    $json = json_decode(wp_remote_retrieve_body($response), true);
    // 成功响应形如 [{"src":"/file/xxx.png"}]
    $src = $json[0]['src'] ?? ($json['src'] ?? null);
    // 严格白名单：只接受 /file/ 下的常规路径段。
    // 旧实现仅 stripos('/file/')===0，实测 "/file/x\"><script>…" 与
    // "/file/../../evil.php" 都能通过，随后经内联 script 输出构成存储型 XSS。
    if (!is_string($src) || !preg_match('#^/file/[A-Za-z0-9._/-]+$#', $src) || str_contains($src, '..')) {
        return null;
    }
    return $src;
}

/**
 * 前端兜底映射：图床完整 URL → 本地完整 URL。footer.php 输出给 app.js 使用。
 */
function yimai_imgbed_fallback_map(): array
{
    $config = yimai_imgbed_config();
    if ($config === []) {
        return [];
    }
    $map = [];
    foreach (yimai_imgbed_map() as $local_rel => $imgbed_path) {
        $map[$config['domain'] . '/' . $imgbed_path] = yimai_local_image_url($local_rel);
    }
    return $map;
}

/** 本地完整 URL（原 yimai_image_url 的本地解析逻辑） */
function yimai_local_image_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return YIMAI_THEME_URI . '/assets/css/placeholder.svg';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    return YIMAI_THEME_URI . '/assets/images' . $path;
}
