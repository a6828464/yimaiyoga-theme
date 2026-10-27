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
 *
 * 上传前会先压缩副本（宽度上限 1600 / 质量 82 / 优先 WebP），
 * 只为图床流量瘦身——本地原图始终是 source of truth，压缩失败就降级传原图。
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

    $upload = yimai_imgbed_prepare_upload($absolute_path, $mime, $filename);
    try {
        $payload = file_get_contents($upload['path']);
        if ($payload === false) {
            return null;
        }
        $boundary = 'yimai' . bin2hex(random_bytes(12));
        $body = "--{$boundary}\r\n"
            . 'Content-Disposition: form-data; name="file"; filename="' . addslashes($upload['name']) . "\"\r\n"
            . 'Content-Type: ' . ($upload['mime'] !== '' ? $upload['mime'] : 'application/octet-stream') . "\r\n\r\n"
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
    } finally {
        // 临时压缩副本用完即删（无论上传成功、失败还是抛异常）
        if ($upload['cleanup'] && is_file($upload['path'])) {
            @unlink($upload['path']);
        }
    }
}

/**
 * 生成上传副本：宽度上限 1600、质量 82，优先 WebP（ImageMagick → GD → 原图）。
 *
 * @return array{path:string,mime:string,name:string,cleanup:bool}
 */
function yimai_imgbed_prepare_upload(string $absolute_path, string $mime, string $filename): array
{
    $original = ['path' => $absolute_path, 'mime' => $mime, 'name' => $filename, 'cleanup' => false];
    // 只处理位图照片：GIF（可能是动图）与 SVG（矢量）重编码只会掉质量
    if (!in_array(strtolower(trim($mime)), ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true)) {
        return $original;
    }
    $info = function_exists('getimagesize') ? @getimagesize($absolute_path) : false;
    if (!is_array($info) || empty($info[0])) {
        error_log('yimai imgbed: 读取图片尺寸失败，降级原图上传 → ' . $filename);
        return $original;
    }

    $maxWidth = 1600;
    $quality = 82;
    $sourceWidth = (int) $info[0];
    $targetWidth = $sourceWidth > $maxWidth ? $maxWidth : $sourceWidth;

    foreach ([['webp', 'image/webp', 'webp'], ['jpeg', 'image/jpeg', 'jpg']] as [$format, $formatMime, $ext]) {
        $temp = yimai_imgbed_temp_path($ext);
        if ($temp === '') {
            continue;
        }
        $written = false;
        try {
            $written = yimai_imgbed_render($absolute_path, $temp, $format, $targetWidth, $quality);
        } catch (Throwable $e) {
            // 压缩链路任何异常都不许冒泡：上传不能被它阻断
            error_log('yimai imgbed: ' . $format . ' 生成异常 → ' . $e->getMessage());
            $written = false;
        }
        clearstatcache(true, $temp);
        if ($written && is_file($temp) && (int) @filesize($temp) > 0) {
            $base = (string) preg_replace('/\.[A-Za-z0-9]{1,5}$/', '', (string) basename($filename));
            return [
                'path' => $temp,
                'mime' => $formatMime,
                'name' => ($base !== '' ? $base : 'image') . '.' . $ext,
                'cleanup' => true,
            ];
        }
        if (is_file($temp)) {
            @unlink($temp);
        }
    }

    // ImageMagick 与 GD 都不可用（或都失败）：按需求降级为原图上传，并留痕
    error_log('yimai imgbed: ImageMagick 与 GD 均不可用，降级原图上传 → ' . $filename);
    return $original;
}

/** 临时文件路径（不预先创建文件，由 Imagick/GD 写入） */
function yimai_imgbed_temp_path(string $ext): string
{
    $dir = function_exists('get_temp_dir') ? (string) get_temp_dir() : (string) sys_get_temp_dir();
    $dir = rtrim($dir, '/\\');
    if ($dir === '' || !is_dir($dir) || !is_writable($dir)) {
        return '';
    }
    try {
        $token = bin2hex(random_bytes(8));
    } catch (Throwable $e) {
        $token = (string) mt_rand();
    }
    return $dir . '/yimai-' . $token . '.' . $ext;
}

/** 缩放 + 重编码：ImageMagick 优先，GD 兜底；两者都不可用返回 false。 */
function yimai_imgbed_render(string $source, string $target, string $format, int $maxWidth, int $quality): bool
{
    if (class_exists('Imagick')) {
        try {
            $image = new Imagick($source);
            $width = (int) $image->getImageWidth();
            $height = (int) $image->getImageHeight();
            if ($width > $maxWidth && $width > 0 && $height > 0) {
                $image->resizeImage($maxWidth, (int) max(1, round($height * $maxWidth / $width)), Imagick::FILTER_LANCZOS, 1);
            }
            $image->setImageFormat($format === 'webp' ? 'WEBP' : 'JPEG');
            $image->setImageCompressionQuality($quality);
            if ($format === 'webp') {
                $image->setOption('webp:method', '6');
            }
            $image->stripImage();
            $ok = (bool) $image->writeImage($target);
            $image->clear();
            $image->destroy();
            clearstatcache(true, $target);
            if ($ok && is_file($target) && (int) @filesize($target) > 0) {
                return true;
            }
        } catch (Throwable $e) {
            error_log('yimai imgbed: ImageMagick 压缩失败 → ' . $e->getMessage());
        }
    }
    return yimai_imgbed_render_gd($source, $target, $format, $maxWidth, $quality);
}

/** GD 兜底实现（无 imagick 扩展时） */
function yimai_imgbed_render_gd(string $source, string $target, string $format, int $maxWidth, int $quality): bool
{
    if ($format === 'webp' && !function_exists('imagewebp')) {
        return false; // 该 GD 未编译 WebP 支持
    }
    if ($format === 'jpeg' && !function_exists('imagejpeg')) {
        return false;
    }
    if (!function_exists('imagecreatetruecolor') || !function_exists('getimagesize')) {
        return false;
    }
    $info = @getimagesize($source);
    if (!is_array($info) || empty($info[0]) || empty($info[1])) {
        return false;
    }
    $type = (int) ($info[2] ?? 0);
    $image = null;
    if ($type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
        $image = @imagecreatefromjpeg($source);
    } elseif ($type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
        $image = @imagecreatefrompng($source);
    } elseif ($type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
        $image = @imagecreatefromwebp($source);
    }
    if (!$image) {
        return false;
    }
    $width = (int) imagesx($image);
    $height = (int) imagesy($image);
    if ($width <= 0 || $height <= 0) {
        return false;
    }
    if ($width > $maxWidth) {
        $newWidth = $maxWidth;
        $newHeight = (int) max(1, round($height * $maxWidth / $width));
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        if (!$resized) {
            return false;
        }
        // 保留 PNG / WebP 的透明通道
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, imagecolorallocatealpha($resized, 0, 0, 0, 127));
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        // PHP 8 起 GD 资源由 GC 回收，imagedestroy() 在 8.5 已废弃，故不再调用
        $image = $resized;
    }
    $ok = $format === 'webp' ? imagewebp($image, $target, $quality) : imagejpeg($image, $target, $quality);
    clearstatcache(true, $target);
    return (bool) $ok && is_file($target) && (int) @filesize($target) > 0;
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
