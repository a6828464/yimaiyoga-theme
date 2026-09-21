<?php
/**
 * WordPress 函数桩：让 inc/site-data.php 与 admin/inc/bootstrap.php
 * 能在 CLI 下被真实调用（回归测试用，不参与线上运行）。
 *
 * @package yimaiyoga
 */

define('ABSPATH', __DIR__ . '/');
define('YIMAI_THEME_DIR', dirname(__DIR__));
define('YIMAI_THEME_URI', 'https://example.test/wp-content/themes/yimaiyoga');

// ---- wp_options 内存实现 ----
$GLOBALS['__options'] = [];
function get_option(string $name, $default = false)
{
    return array_key_exists($name, $GLOBALS['__options']) ? $GLOBALS['__options'][$name] : $default;
}
function update_option(string $name, $value, $autoload = null): bool
{
    $GLOBALS['__options'][$name] = $value;
    return true;
}
function delete_option(string $name): bool
{
    unset($GLOBALS['__options'][$name]);
    return true;
}

// ---- 杂项 ----
function current_time(string $type = 'mysql', bool $gmt = false)
{
    return $type === 'timestamp' ? time() : date('Y-m-d H:i:s');
}
function wp_json_encode($data, int $flags = 0, int $depth = 512)
{
    return json_encode($data, $flags, $depth);
}
function wp_hash_password(string $password): string
{
    return password_hash($password, PASSWORD_BCRYPT);
}
function wp_check_password(string $password, string $hash, $user_id = ''): bool
{
    return $hash !== '' && password_verify($password, $hash);
}
function wp_generate_password(int $length = 12, bool $special = true, bool $extra = false): string
{
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    if ($special) {
        $chars .= '!@#$%^&*()-_[]{}<>~`+=,.;:/?|';
    }
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $out;
}
function wp_mkdir_p(string $dir): bool
{
    return is_dir($dir) || mkdir($dir, 0755, true);
}
function wp_delete_file(string $file): bool
{
    return @unlink($file);
}
function wp_unslash($value)
{
    return is_string($value) ? stripslashes($value) : $value;
}
function sanitize_text_field($str): string
{
    return trim(strip_tags((string) $str));
}
function sanitize_textarea_field($str): string
{
    return trim(strip_tags((string) $str));
}
function get_template_directory(): string
{
    return YIMAI_THEME_DIR;
}
function get_template_directory_uri(): string
{
    return YIMAI_THEME_URI;
}
function get_temp_dir(): string
{
    return sys_get_temp_dir() . '/';
}
function home_url(string $path = ''): string
{
    return 'https://example.test' . $path;
}
function admin_url(string $path = ''): string
{
    return 'https://example.test/wp-admin/' . $path;
}
function esc_url($url): string
{
    return htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8');
}
function esc_attr($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}
function esc_html($text): string
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}
function is_ssl(): bool
{
    return false;
}
function wp_remote_post(string $url, array $args = [])
{
    return new WP_Error('stub', 'no network in tests');
}
function wp_remote_get(string $url, array $args = [])
{
    return new WP_Error('stub', 'no network in tests');
}
function is_wp_error($thing): bool
{
    return $thing instanceof WP_Error;
}
function wp_remote_retrieve_body($response): string
{
    return '';
}
function wp_remote_retrieve_response_code($response): int
{
    return 0;
}
class WP_Error
{
    public function __construct(private string $code = '', private string $message = '')
    {
    }
    public function get_error_message(): string
    {
        return $this->message;
    }
}
