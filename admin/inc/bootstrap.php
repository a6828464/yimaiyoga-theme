<?php
/**
 * 后台辅助函数（移植自旧站 app/*.php，配置存 WP 数据库）
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) { exit; }

define('YIMAI_ADMIN_VIEWS', __DIR__ . '/../views');

/** 未初始化密码的占位哈希：与任何输入都不匹配（password_verify 对非法哈希返回 false） */
const YIMAI_PASSWORD_PLACEHOLDER = '*uninitialized*';

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

/**
 * 后台路径前缀：从站点实际安装位置推导，支持 WordPress 装在子目录的情况。
 * （旧实现硬编码 '/admin'，子目录安装时登录会跳到站点根而非 WP 目录。）
 */
function yimai_admin_path(string $sub = ''): string
{
    $base = rtrim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
    $path = $base . '/admin';
    if ($sub !== '') {
        $path .= '/' . ltrim($sub, '/');
    }
    return $path === '' ? '/admin' : $path;
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
    // 纯默认结构（不含数据库旧值）：保存时以此为准，列表项才能真正删掉
    return yimai_site_data(false);
}

/**
 * 读取配置（默认结构 + DB 覆盖）。
 *
 * @param bool $invalidate 传 true 强制丢弃静态缓存（save_config 之后调用）
 */
function load_config(bool $invalidate = false): array
{
    static $cache = null;
    if ($invalidate) {
        $cache = null;
    }
    if ($cache !== null) {
        return $cache;
    }
    $raw = get_option(yimai_config_option_name(), '');
    $config = $raw ? json_decode((string) $raw, true) : [];
    if (!is_array($config)) {
        $config = [];
    }
    $cache = yimai_deep_merge(default_config(), $config);
    return $cache;
}

function save_config(array $config): void
{
    // 1) 类型校验 + 规范化：类型不符直接抛 Yimai_Config_Type_Error，
    //    由调用方转成 400，绝不把坏类型写进 DB（否则前台 implode/取下标会 Fatal 白屏）。
    $config = yimai_config_validate($config, default_config(), 'config');

    // 2) 列表键整体替换、映射逐键合并（yimai_deep_merge，见 inc/site-data.php）。
    //    统一到唯一合并语义，避免 array_replace_recursive 把默认列表尾部补回来。
    $config = yimai_deep_merge(default_config(), $config);

    // 3) 规范化数组列表键的顺序（校验已保证是 list，这里仅统一重排索引）
    foreach (['courseThemes', 'classPaths', 'instructors', 'studios', 'memberships',
              'faqs', 'nav_items', 'training_programs', 'training_rights'] as $key) {
        $config[$key] = array_values($config[$key] ?? []);
    }
    $config['images']['studioImages'] = array_values($config['images']['studioImages'] ?? []);
    $config['announcements']['items'] = array_values(
        array_filter(is_array($config['announcements']['items'] ?? null) ? $config['announcements']['items'] : [],
            'is_array')
    );

    // 4) 备份：保留最近 20 份。落盘前脱敏，备份里不留明文密钥。
    $backups = get_option('yimai_site_config_backups', []);
    $backups = is_array($backups) ? $backups : [];
    $backups[] = ['time' => current_time('mysql'), 'data' => yimai_config_redact($config)];
    if (count($backups) > 20) {
        $backups = array_slice($backups, -20);
    }
    update_option('yimai_site_config_backups', $backups, false);
    update_option(yimai_config_option_name(), wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), false);

    // 5) 同请求内让 load_config() 的静态缓存失效，避免保存后仍读到旧值
    load_config(true);
}

/** 备份脱敏：把密钥类字段替换为占位符（备份只用于回看文案，不需要真实密钥）。 */
function yimai_config_redact(array $config): array
{
    $secretKeys = ['wecomWebhook', 'imgbedAuthCode'];
    array_walk_recursive($config, function (&$value, $key) use ($secretKeys) {
        if (in_array($key, $secretKeys, true) && is_string($value) && $value !== '') {
            $value = '[已脱敏]';
        }
    });
    return $config;
}

/* ---------- 认证（账号 admin，密码哈希存 wp_options） ---------- */
function admin_username(): string
{
    return 'admin';
}

/** 密码是否已设置（区分「从未设置」与「已设置」） */
function admin_password_is_set(): bool
{
    $hash = (string) get_option('yimai_admin_password_hash', '');
    return $hash !== '' && $hash !== YIMAI_PASSWORD_PLACEHOLDER;
}

/**
 * 一次性迁移：作废旧版「可推导默认口令」。
 *
 * 旧实现用 'Yimai@' . date('Ymd')。已上线站点若从未改过密码，库里存的
 * 就是那个任何人可推算的哈希——只升级代码并不消除该风险。
 *
 * 实现要点（避免自伤）：bcrypt 单次校验实测约 200ms，若逐天穷举 400 个
 * 候选会耗时约 85 秒，等于每次请求都造成一次 DoS。因此这里只校验
 * 前后各一天（覆盖 date() 用服务器本地时区、gmdate() 用 UTC 的跨日差异），
 * 成本约 600ms 且由 option 标记保证只跑一次。
 *
 * 已知局限：若站点是在更早某天首次登录写入旧口令、之后长期未改密，
 * 仅靠本迁移无法识别。因此登录页提示站长：不确定时直接重设密码。
 */
function yimai_migrate_legacy_default_password(): void
{
    if (get_option('yimai_password_migration_done', '') === '1') {
        return;
    }
    $hash = (string) get_option('yimai_admin_password_hash', '');
    // 未设置或已是占位符：无需迁移
    if ($hash === '' || $hash === YIMAI_PASSWORD_PLACEHOLDER) {
        update_option('yimai_password_migration_done', '1', false);
        return;
    }
    $candidates = [];
    foreach ([-1, 0, 1] as $offset) {
        $ts = time() + $offset * 86400;
        $candidates[] = 'Yimai@' . gmdate('Ymd', $ts);
        $candidates[] = 'Yimai@' . date('Ymd', $ts);
    }
    $found = false;
    foreach (array_unique($candidates) as $candidate) {
        if (wp_check_password($candidate, $hash, '')) {
            $found = true;
            break;
        }
    }
    if ($found) {
        // 作废：登录必然失败，必须用 cli/set-admin-password.php 重新设置
        update_option('yimai_admin_password_hash', YIMAI_PASSWORD_PLACEHOLDER, false);
        update_option('yimai_password_forced_reset', '1', false);
    }
    update_option('yimai_password_migration_done', '1', false);
}

/** 是否因安全迁移而强制重置过密码（登录页据此提示） */
function admin_password_forced_reset(): bool
{
    return get_option('yimai_password_forced_reset', '') === '1';
}

/**
 * 首次安装：不生成任何可推导或可被抢先使用的口令。
 *
 * 旧实现用 'Yimai@' . date('Ymd')，任何人读过源码即可推算并抢先登录，
 * 且首次访问就会把该哈希写进 option。现在未初始化时写入一个永不匹配的
 * 占位哈希，登录必然失败，站长必须用 cli/set-admin-password.php
 * 在服务器端设置初始密码（需要服务器访问权限＝真实信任边界）。
 */
function admin_password_hash(): string
{
    $hash = (string) get_option('yimai_admin_password_hash', '');
    if ($hash !== '') {
        return $hash;
    }
    update_option('yimai_admin_password_hash', YIMAI_PASSWORD_PLACEHOLDER, false);
    return YIMAI_PASSWORD_PLACEHOLDER;
}

function admin_login(string $username, string $password): bool
{
    if (!hash_equals(admin_username(), $username)) {
        admin_record_failed_login();
        return false;
    }
    if (admin_is_locked_out()) {
        return false;
    }
    if (!wp_check_password($password, admin_password_hash(), '')) {
        admin_record_failed_login();
        return false;
    }
    admin_clear_failed_logins();
    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    $_SESSION['admin_login_at'] = time();
    $_SESSION['admin_last_seen'] = time();
    return true;
}

/* ---------- 登录限流（简单计数 + 指数退避，无外部依赖） ---------- */
const YIMAI_LOGIN_MAX_ATTEMPTS = 5;
const YIMAI_LOGIN_WINDOW = 900;      // 15 分钟窗口
const YIMAI_LOGIN_LOCKOUT = 900;     // 触发后锁定 15 分钟

function admin_login_attempts(): array
{
    $data = get_option('yimai_admin_login_attempts', []);
    return is_array($data) ? $data : [];
}

function admin_is_locked_out(): bool
{
    $data = admin_login_attempts();
    $count = (int) ($data['count'] ?? 0);
    $last = (int) ($data['last'] ?? 0);
    if ($count < YIMAI_LOGIN_MAX_ATTEMPTS) {
        return false;
    }
    return (time() - $last) < YIMAI_LOGIN_LOCKOUT;
}

/** 锁定剩余秒数 */
function admin_lockout_remaining(): int
{
    $data = admin_login_attempts();
    $last = (int) ($data['last'] ?? 0);
    return max(0, YIMAI_LOGIN_LOCKOUT - (time() - $last));
}

function admin_record_failed_login(): void
{
    $data = admin_login_attempts();
    $last = (int) ($data['last'] ?? 0);
    $count = (int) ($data['count'] ?? 0);
    // 超出窗口则重新计数
    if ((time() - $last) > YIMAI_LOGIN_WINDOW) {
        $count = 0;
    }
    update_option('yimai_admin_login_attempts', ['count' => $count + 1, 'last' => time()], false);
}

function admin_clear_failed_logins(): void
{
    update_option('yimai_admin_login_attempts', ['count' => 0, 'last' => 0], false);
}

function change_admin_password(string $new): void
{
    update_option('yimai_admin_password_hash', wp_hash_password($new), false);
}

function admin_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $params['path'],
            'domain'   => $params['domain'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

/** 会话空闲超时（8 小时 cookie 生命期内，30 分钟无操作即失效） */
const YIMAI_SESSION_IDLE = 1800;

function admin_session_expired(): bool
{
    $last = (int) ($_SESSION['admin_last_seen'] ?? 0);
    return $last > 0 && (time() - $last) > YIMAI_SESSION_IDLE;
}

function admin_touch_session(): void
{
    $_SESSION['admin_last_seen'] = time();
}

function yimai_is_admin(): bool
{
    if (empty($_SESSION['admin_authenticated'])) {
        return false;
    }
    if (admin_session_expired()) {
        admin_logout();
        return false;
    }
    admin_touch_session();
    return true;
}

function require_admin(): void
{
    if (!yimai_is_admin()) {
        redirect_to(yimai_admin_path('login'));
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

/**
 * CSRF 校验。
 *
 * @param bool $as_page 表单直访场景（登录页）：失败时回跳并闪错，而不是吐裸 JSON
 */
function verify_csrf(bool $as_page = false): void
{
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        if ($as_page) {
            $_SESSION['flash_error'] = '页面已过期，请重新提交';
            redirect_to(yimai_admin_path('login'));
        }
        json_response(['message' => 'CSRF 校验失败'], 419);
    }
}


/**
 * 上传出口：确保本地文件已落盘后，尽力同步图床并写入映射，最后返回统一结构。
 * 图床失败完全静默——本地文件始终可用。imgbed 为完整图床 URL（未同步时 null）。
 */
function yimai_finish_upload(string $target, string $mime, string $name): array
{
    $imgbedFull = null;
    if (is_file($target)) {
        try {
            $imgbedPath = yimai_imgbed_upload($target, $mime, $name);
            if ($imgbedPath !== null) {
                yimai_imgbed_remember('/uploads/' . $name, $imgbedPath);
                $imgbedConfig = yimai_imgbed_config();
                $imgbedFull = $imgbedConfig['domain'] . '/' . ltrim($imgbedPath, '/');
            }
        } catch (Throwable $e) {
            // 图床同步失败不影响上传结果
        }
    }
    return ['ok' => true, 'path' => '/uploads/' . $name, 'imgbed' => $imgbedFull];
}

/**
 * 把一张已存在的本地图片同步到图床（后台「图床」切换按钮触发）。
 * 已有映射直接返回；未同步则现场上传并记住映射。
 */
function yimai_imgbed_sync_existing(string $rel): array
{
    $rel = '/' . ltrim(trim($rel), '/');
    if ($rel === '/' || !str_starts_with($rel, '/uploads/') || str_contains($rel, '..')) {
        return ['ok' => false, 'message' => '路径不合法'];
    }
    $config = yimai_imgbed_config();
    if ($config === []) {
        return ['ok' => false, 'message' => '尚未配置图床：请先在「基础与SEO」填写图床地址'];
    }
    $map = yimai_imgbed_map();
    $imgbedPath = $map[$rel] ?? null;
    if ($imgbedPath === null) {
        $abs = upload_path() . '/' . basename($rel);
        if (!is_file($abs)) {
            return ['ok' => false, 'message' => '本地文件不存在：' . $rel];
        }
        $info = @getimagesize($abs);
        $mime = is_array($info) ? ($info['mime'] ?? '') : '';
        $imgbedPath = yimai_imgbed_upload($abs, $mime, basename($rel));
        if ($imgbedPath === null) {
            return ['ok' => false, 'message' => '同步图床失败，请稍后重试'];
        }
        yimai_imgbed_remember($rel, $imgbedPath);
    }
    return ['ok' => true, 'imgbed' => $config['domain'] . '/' . ltrim((string) $imgbedPath, '/')];
}

/* ---------- 上传（存主题 assets/images/uploads，对应原站 /uploads/） ---------- */
function upload_path(): string
{
    return get_template_directory() . '/assets/images/uploads';
}

function save_uploaded_image(array $file, string $field = ''): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => '请选择图片文件'];
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_file($tmp)) {
        return ['ok' => false, 'message' => '上传文件无效'];
    }
    // 以服务器上真实落盘大小为准（$file['size'] 来自客户端 multipart 头，可伪造/可为负）
    $size = (int) @filesize($tmp);
    if ($size <= 0) {
        return ['ok' => false, 'message' => '上传文件为空'];
    }
    if ($size > 8 * 1024 * 1024) {
        return ['ok' => false, 'message' => '图片不能超过 8MB'];
    }
    $info = @getimagesize($tmp);
    if (!$info) {
        return ['ok' => false, 'message' => '只能上传图片'];
    }
    $mime = (string) ($info['mime'] ?? '');
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'message' => '仅支持 JPG、PNG、WebP、GIF'];
    }
    $width = (int) ($info[0] ?? 0);
    $height = (int) ($info[1] ?? 0);
    // 宽或高为 0 的畸形图（如 IHDR 声明 0x0 的合法 PNG）会通过 MIME 白名单，
    // 随后 max($width,$height) 为 0 触发 DivisionByZeroError 致 500。先拒绝。
    if ($width < 1 || $height < 1) {
        return ['ok' => false, 'message' => '图片尺寸无效'];
    }
    $dir = upload_path();
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
    }

    // 需要保留透明/矢量感的字段：logo、favicon、二维码。
    // 判定**只看字段**，不看客户端文件名——否则 image/jpeg 命名为 favicon.jpg
    // 会被存成 .png（内容与后缀不一致，且误导图床/CDN 的二次处理）。
    $pngFields = ['site.logo', 'site.favicon', 'site.wechatQr'];
    $keepPng = $mime === 'image/png' && in_array($field, $pngFields, true);

    // 原样落盘的两条路径：GIF（保留动画）与无 GD 环境
    if ($mime === 'image/gif' || !function_exists('imagecreatetruecolor')) {
        $name = yimai_upload_filename($allowed[$mime]);
        if (!move_uploaded_file($tmp, $dir . '/' . $name)) {
            return ['ok' => false, 'message' => '写入文件失败，请检查目录权限'];
        }
        return yimai_finish_upload($dir . '/' . $name, $mime, $name);
    }

    // 输出格式必须在建文件名前定下来：扩展名要与实际写出的字节一致
    if ($keepPng) {
        $outExt = 'png';
    } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
        $outExt = 'webp';
    } else {
        $outExt = 'jpg';   // jpeg；非透明用途的 PNG；无 webp 支持时的 WebP
    }
    $name = yimai_upload_filename($outExt);
    $target = $dir . '/' . $name;

    $source = match ($mime) {
        'image/png' => imagecreatefrompng($tmp),
        'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($tmp) : null,
        default => imagecreatefromjpeg($tmp),
    };
    if (!$source) {
        // GD 读不出来：退回原样落盘（扩展名改为原始 MIME，保持一致）
        $rawName = yimai_upload_filename($allowed[$mime]);
        if (!move_uploaded_file($tmp, $dir . '/' . $rawName)) {
            return ['ok' => false, 'message' => '写入文件失败，请检查目录权限'];
        }
        return yimai_finish_upload($dir . '/' . $rawName, $mime, $rawName);
    }

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
    $canvas = imagecreatetruecolor($newWidth, $newHeight);
    // 透明通道：先填充透明色，避免 PNG 透明区域变黑
    imagesavealpha($canvas, true);
    imagealphablending($canvas, false);
    $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
    imagefilledrectangle($canvas, 0, 0, $newWidth, $newHeight, $transparent);
    imagealphablending($canvas, true);
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // 写出的格式与 $outExt 严格对应
    $written = match ($outExt) {
        // PNG 保留透明通道（favicon 不再铺白底，浏览器标签页显示透明图标）
        'png'  => imagepng($canvas, $target, 9),
        'webp' => imagewebp($canvas, $target, 84),
        default => imagejpeg($canvas, $target, 86),
    };
    imagedestroy($source);
    imagedestroy($canvas);
    if (!$written || !is_file($target)) {
        return ['ok' => false, 'message' => '图片写入失败，请检查目录权限'];
    }
    return yimai_finish_upload($target, $mime, $name);
}

/** 生成上传文件名：时间戳 + 随机串 + 扩展名（不含任何客户端输入） */
function yimai_upload_filename(string $ext): string
{
    return time() . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
}

/**
 * 图片库：列出本地已上传的全部图片（后台「图片库」弹窗数据源）。
 * 已同步图床的图片带 imgbed 完整地址（缩略图走图床 CDN，选择时可写图床地址）。
 */
function yimai_image_library(): array
{
    $dir = upload_path();
    $map = function_exists('yimai_imgbed_map') ? yimai_imgbed_map() : [];
    $imgbedConfig = function_exists('yimai_imgbed_config') ? yimai_imgbed_config() : [];
    $items = [];
    foreach ((array) @scandir($dir) as $file) {
        if ($file === '.' || $file === '..' || str_starts_with($file, '.')) {
            continue;
        }
        $abs = $dir . '/' . $file;
        if (!is_file($abs)) {
            continue;
        }
        $info = @getimagesize($abs);
        if (!$info) {
            continue;
        }
        $rel = '/uploads/' . $file;
        $item = [
            'path' => $rel,
            'imgbed' => null,
            'width' => (int) ($info[0] ?? 0),
            'height' => (int) ($info[1] ?? 0),
            'time' => (int) @filemtime($abs),
        ];
        if (isset($map[$rel]) && $imgbedConfig !== []) {
            $item['imgbed'] = $imgbedConfig['domain'] . '/' . ltrim((string) $map[$rel], '/');
        }
        $items[] = $item;
    }
    usort($items, function ($a, $b) {
        return $b['time'] <=> $a['time'];
    });
    return ['ok' => true, 'items' => array_slice($items, 0, 500)];
}

/* ---------- 渲染 ---------- */
/**
 * 后台图片字段：预览 + 地址输入 + 「本地 / 图床」切换 + 上传按钮。
 * 值为 http 开头时视为图床/外链（图床侧高亮），否则为本地相对路径。
 */
function admin_image_field(string $path, string $label, string $value, string $hint = ''): void
{
    $value = trim((string) $value);
    $isRemote = preg_match('/^https?:\/\//i', $value) === 1;
    ?>
    <label><?php echo h($label); ?>
      <div class="img-preview" data-preview="<?php echo h($path); ?>"><img src="<?php echo esc_url(yimai_image_url($value)); ?>" onerror="this.parentNode.textContent='无预览'"></div>
      <input data-path="<?php echo h($path); ?>" value="<?php echo h($value); ?>" placeholder="/uploads/… 或 https://…">
      <div class="src-toggle" data-src-toggle="<?php echo h($path); ?>">
        <button type="button" data-src-btn="local" <?php if (!$isRemote) echo 'class="active"'; ?>>本地存储</button>
        <button type="button" data-src-btn="imgbed" <?php if ($isRemote) echo 'class="active"'; ?>>图床加速</button>
        <span class="src-hint" data-src-hint="<?php echo h($path); ?>"></span>
      </div>
      <div class="file-row"><input type="file" data-upload-for="<?php echo h($path); ?>" accept="image/*"><button type="button" class="upload-btn" data-upload-trigger="<?php echo h($path); ?>">上传新图</button><button type="button" class="upload-btn" data-library-for="<?php echo h($path); ?>">图片库</button></div>
      <?php if ($hint !== ''): ?><span class="hint"><?php echo h($hint); ?></span><?php endif; ?>
    </label>
    <?php
}

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
