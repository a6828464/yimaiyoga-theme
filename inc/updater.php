<?php
/**
 * 主题在线更新（对齐「一麦GSO 参考手册」多通道设计）
 *
 * 发布渠道：Gitee（主仓库）+ GitHub（发布镜像，经自建反代 github.shunan.fun 推送），
 * 三条下载通道全部使用 Release 静态资产（同一份 zip），内容哈希跨通道可比对。
 *
 * 通道优先级（国内服务器实测：Gitee 秒级、GitHub 直连挂起、Cloudflare 反代可达）：
 * 1. gitee         —— Gitee Release 资产（主源，稳定）
 * 2. github        —— GitHub Release 资产（海外/网络好时走这条）
 * 3. github_mirror —— 自建反代 Release 资产（GitHub 被墙但 Cloudflare 可达时救场）
 *
 * 核心机制（缺一不可）：
 * - 通道记忆：上次成功的通道提升到最前（transient 1 天），检查与下载同源
 * - 时间戳破缓存：Release 同名资产重建后 CDN 可能仍吐旧文件
 * - 逐通道错误透出：失败信息形如「gitee：HTTP 502；github：cURL error 28」
 * - 检查成功缓存 1 小时；全通道失败时回放上次成功检查（标 stale）
 *
 * 环境约定（2026-09 探测测试服务器得出）：
 * - PHP 8.5，未装 zip 扩展 → 优先 PclZip（WordPress 内置），有 ZipArchive 则用之
 * - proc_open 不可依赖 → 纯 PHP 实现，不落 shell
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) {
    exit;
}

const YIMAI_UPDATE_GITEE_REPO = 'meng-taoo/yimaiyoga-theme';
const YIMAI_UPDATE_GITHUB_REPO = 'a6828464/yimaiyoga-theme';
// GitHub 自建反代域名（Cloudflare 反代 github.com；302 → proxy/release-assets…，
// 下载请求必须跟随重定向）。2026-10 实测：本机与国内服务器均可达。
const YIMAI_UPDATE_GITHUB_MIRROR = 'github.shunan.fun';

/**
 * 更新来源（三条通道，清单与包都指向 Release 静态资产）。
 *
 * 优先级链条（与 README「发布与更新」章节一致）：
 * - gitee：国内服务器访问稳定、秒级 —— 主源
 * - github：海外服务器/网络好时走这条
 * - github_mirror：GitHub 被墙但 Cloudflare 可达时救场（自建反代）
 *
 * v1.9.0 起包不再走 codeload 分支归档：三条通道下载的是同一份静态 zip，
 * 内容哈希跨通道可比对（codeload 实时归档字节不固定，无法预发布哈希；
 * 且反代归档需 Origin Rule 放行 codeload，静态资产无需额外放行）。
 */
function yimai_update_sources(): array
{
    $gh = 'https://github.com/' . YIMAI_UPDATE_GITHUB_REPO;
    $mirror = 'https://' . YIMAI_UPDATE_GITHUB_MIRROR . '/' . YIMAI_UPDATE_GITHUB_REPO;
    return [
        'gitee' => [
            'manifest' => 'https://gitee.com/' . YIMAI_UPDATE_GITEE_REPO . '/releases/download/auto-latest/yimaiyoga-theme-manifest.json',
            'package' => 'https://gitee.com/' . YIMAI_UPDATE_GITEE_REPO . '/releases/download/auto-latest/yimaiyoga-theme-latest.zip',
        ],
        'github' => [
            'manifest' => $gh . '/releases/download/auto-latest/yimaiyoga-theme-manifest.json',
            'package' => $gh . '/releases/download/auto-latest/yimaiyoga-theme-latest.zip',
        ],
        'github_mirror' => [
            'manifest' => $mirror . '/releases/download/auto-latest/yimaiyoga-theme-manifest.json',
            'package' => $mirror . '/releases/download/auto-latest/yimaiyoga-theme-latest.zip',
        ],
    ];
}

/**
 * 通道顺序应用「通道记忆」：上次成功的通道提升到最前。
 *
 * 手册原则：检查更新与下载包必须同源（否则清单哈希与包可能来自不同发布轮，
 * 内容哈希必然对不上）。因此清单成功后记住通道，下载时同一通道优先。
 * gitee 本就是默认首位，记忆 gitee 时无需重排。
 */
function yimai_update_sources_ordered(): array
{
    $sources = yimai_update_sources();
    $last = get_transient('yimai_update_manifest_source');
    if (is_string($last) && $last !== '' && isset($sources[$last]) && $last !== array_key_first($sources)) {
        $sources = [$last => $sources[$last]] + $sources;
    }
    return $sources;
}

/**
 * 包完整性校验（存在 manifest.sha256 时强制校验）。
 *
 * 完整信任边界消除需要给发布流程加签名密钥，属于架构决策；
 * 当前实现提供：manifest 版本一致性 + 包 SHA256（若发布脚本提供了该字段）
 * + zip 结构校验 + 防降级。这能挡住「下载被截断/被替换成明显异常包」，
 * 但**不能**挡住能改仓库的攻击者——该风险已在 AUDIT.md 记录为待决策项。
 */
function yimai_update_expected_sha256(): string
{
    $remote = yimai_update_remote_meta();
    $sha = $remote['sha256'] ?? '';
    return is_string($sha) ? strtolower(trim($sha)) : '';
}

/**
 * 计算解压目录的**内容哈希**：按「相对路径 + 文件内容哈希」聚合，与 zip 字节无关。
 *
 * 为什么不能用 zip 字节哈希：Release 资产经 GitHub/Gitee CDN 分发，不同通道
 * 理论上可能拿到重传的副本；内容哈希只看解压后的文件内容，任何一份合法
 * 副本、任何打包实现（git archive / codeload / macOS zip）都得到同一结果。
 *
 * 排除规则必须与 tools/release.sh 的 content_sha256 保持一致。
 */
function yimai_update_content_sha256(string $root): string
{
    $items = [];
    $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = str_replace('\\', '/', (string) $file);
        $rel = ltrim(substr($path, strlen($normalizedRoot)), '/');
        $base = basename($rel);
        // 与发布脚本一致的排除项：不参与内容哈希
        if ($base === 'local-secrets.php'
            || $base === '.update-state.json'
            || $base === '.DS_Store'
            || str_starts_with($rel, '.git')
            || str_starts_with($rel, '.backups/')
            || str_starts_with($rel, 'tests/')
            || str_starts_with($rel, 'tools/')) {
            continue;
        }
        $items[$rel] = (string) hash_file('sha256', $path);
    }
    ksort($items, SORT_STRING);
    $ctx = hash_init('sha256');
    foreach ($items as $rel => $hash) {
        hash_update($ctx, $rel . "\0" . $hash . "\n");
    }
    return hash_final($ctx);
}

/**
 * 校验解压后的内容哈希是否与清单一致。
 *
 * @param string $root 已解压的主题根目录
 */
function yimai_update_check_hash(string $root, array &$log): void
{
    $expected = yimai_update_expected_sha256();
    if ($expected === '') {
        $log[] = '提示：清单未提供内容哈希，已跳过校验（建议发布脚本生成该字段）';
        return;
    }
    $actual = yimai_update_content_sha256($root);
    if (!hash_equals($expected, $actual)) {
        throw new RuntimeException(
            '更新包内容与清单不一致，已中止。'
            . '可能原因：下载损坏、包被替换，或该版本由旧发布脚本生成（未含内容哈希）。'
        );
    }
    $log[] = '更新包内容哈希校验通过';
}

function yimai_theme_meta(): array
{
    static $meta = null;
    if ($meta === null) {
        $meta = ['version' => YIMAI_VERSION, 'updated' => '', 'notes' => []];
        $file = get_template_directory() . '/theme.json';
        if (is_file($file)) {
            $json = json_decode((string) file_get_contents($file), true);
            if (is_array($json)) {
                $meta = array_merge($meta, $json);
            }
        }
    }
    return $meta;
}

function yimai_update_state(): array
{
    $file = get_template_directory() . '/.update-state.json';
    if (is_file($file)) {
        $json = json_decode((string) file_get_contents($file), true);
        if (is_array($json)) {
            return $json;
        }
    }
    return [];
}

/**
 * 更新检查（通道记忆 + 成功缓存 1 小时 + 失败兜底展示）。
 *
 * 手册 §3.2-④：成功结果缓存，避免每次进后台都打三通道；
 * 全通道失败时回放上次成功检查（标 stale），用户至少知道「远端理论上是什么版本」。
 */
function yimai_update_remote_meta(?string &$source_used = null, ?array &$failLog = null): array
{
    // 失败兜底：回放上次成功检查的清单（不重试网络），UI 标注「缓存」
    $fallBack = static function () use (&$source_used): array {
        $cached = get_transient('yimai_update_manifest_cache');
        if (is_array($cached) && !empty($cached['json']['version'])) {
            $source_used = $cached['source'] ?? '';
            return $cached['json'] + ['_stale' => true];
        }
        return [];
    };

    foreach (yimai_update_sources_ordered() as $source => $urls) {
        // 加时间戳破缓存：GitHub/Gitee 对同名 Release 资产有 CDN 缓存，重建同名 Release 后可能仍吐旧文件
        $url = $urls['manifest'] . (strpos($urls['manifest'], '?') === false ? '?' : '&') . 't=' . time();
        $resp = wp_remote_get($url, ['timeout' => 12, 'headers' => ['Accept' => 'application/json']]);
        if (is_wp_error($resp)) {
            $failLog[] = $source . ': ' . $resp->get_error_message();
            continue;
        }
        $code = wp_remote_retrieve_response_code($resp);
        if ($code !== 200) {
            $failLog[] = $source . ': HTTP ' . $code;
            continue;
        }
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if (is_array($json) && !empty($json['version'])) {
            $source_used = $source;
            // 通道记忆（1 天）+ 检查结果缓存（1 小时），下载与检查同源
            set_transient('yimai_update_manifest_source', $source, DAY_IN_SECONDS);
            set_transient('yimai_update_manifest_cache', ['source' => $source, 'json' => $json], HOUR_IN_SECONDS);
            return $json;
        }
        $failLog[] = $source . ': 响应不是有效清单 JSON';
    }
    return $fallBack();
}

/** 解析 CHANGELOG.md 为 [{version,date,notes}]（发布脚本对清单用同一套规则） */
function yimai_parse_changelog(string $markdown): array
{
    $entries = [];
    $current = null;
    foreach (preg_split('/\r?\n/', $markdown) as $line) {
        $line = rtrim($line);
        if (preg_match('/^##\s+v?([0-9][0-9A-Za-z.\-]*)\s*[·\- ]*\s*(\d{4}-\d{2}-\d{2})?\s*$/', $line, $m)) {
            if ($current !== null) {
                $entries[] = $current;
            }
            $current = ['version' => $m[1], 'date' => $m[2] ?? '', 'notes' => []];
        } elseif ($current !== null && preg_match('/^[-*]\s+(.+)$/', $line, $m)) {
            $current['notes'][] = $m[1];
        }
    }
    if ($current !== null) {
        $entries[] = $current;
    }
    return $entries;
}

function yimai_local_changelog(): array
{
    $file = get_template_directory() . '/CHANGELOG.md';
    return is_file($file) ? yimai_parse_changelog((string) file_get_contents($file)) : [];
}

/** 后台「检查更新」入口 */
function yimai_updater_check(): array
{
    $local = yimai_theme_meta();
    $failLog = [];
    $source = null;
    $remote = yimai_update_remote_meta($source, $failLog);
    $stale = (bool) ($remote['_stale'] ?? false);
    unset($remote['_stale']);
    if (empty($remote)) {
        return [
            'ok' => false,
            'message' => '无法获取远端版本信息：' . implode('；', $failLog),
            'local' => $local,
            'changelog' => yimai_local_changelog(),
        ];
    }
    return [
        'ok' => true,
        'local' => $local,
        'remote' => $remote,
        'source' => $source,
        'stale' => $stale,
        'up_to_date' => version_compare((string) $remote['version'], (string) $local['version'], '<='),
        'changelog' => yimai_local_changelog(),
    ];
}

/** 确保目录存在，失败抛异常 */
function yimai_update_ensure_dir(string $dir): void
{
    if (!is_dir($dir) && !wp_mkdir_p($dir)) {
        throw new RuntimeException('目录创建失败：' . $dir);
    }
}

/**
 * 备份目录：移到 WordPress 上传目录之外、webroot 之内不可预测的私有路径。
 *
 * 旧实现放在主题目录 `.backups/`，该目录在 webroot 内且项目无 .htaccess，
 * 一旦服务器未拦截点目录，任何访客都能下载到备份包（历史版本含 local-secrets）。
 * 现在放到 wp-content 下的独立目录，并写入 index.php 与 .htaccess 双重拦截。
 */
function yimai_update_backup_dir(): string
{
    $base = defined('WP_CONTENT_DIR')
        ? WP_CONTENT_DIR . '/yimai-backups'
        : get_template_directory() . '/.backups';
    if (!is_dir($base)) {
        wp_mkdir_p($base);
        // 目录级拦截：即使被直接访问也不列表、不执行
        @file_put_contents($base . '/index.php', "<?php\n// Silence is golden.\n");
        @file_put_contents($base . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n");
    }
    return $base;
}

/**
 * 下载后的包完整性校验：必须是可读 zip、体积合理、且包含主题必需文件。
 * 这是「不做签名」前提下的最低防线：拒绝明显异常/被截断的包。
 * 注：三条通道均为静态 Release 资产，内容哈希校验（解压后）恒定适用。
 */
function yimai_update_verify_package(string $zipfile, array &$log): void
{
    if (!is_file($zipfile) || filesize($zipfile) < 1024) {
        throw new RuntimeException('更新包异常（文件缺失或过小）');
    }
    // zip 魔数校验：PK\x03\x04 / PK\x05\x06（空档）/ PK\x07\x08
    $fh = fopen($zipfile, 'rb');
    $magic = $fh ? (string) fread($fh, 4) : '';
    if ($fh) {
        fclose($fh);
    }
    if (!in_array($magic, ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"], true)) {
        throw new RuntimeException('更新包不是有效的 zip 文件');
    }
    $log[] = '更新包校验通过（' . round(filesize($zipfile) / 1024) . ' KB）';
}

/**
 * 下载更新包到临时文件，按通道顺序（含通道记忆）逐个尝试，返回 [文件, 来源]。
 *
 * 三条通道下载的都是同一份静态 Release zip：codeload 实时归档字节不固定、
 * 无法预发布哈希；静态资产哈希跨通道可比对（见 yimai_update_check_hash）。
 */
function yimai_update_download_package(string $dest): array
{
    foreach (yimai_update_sources_ordered() as $source => $urls) {
        // 时间戳破 CDN 缓存（同名 Release 资产重建后 GitHub 边缘节点可能仍吐旧包）
        $url = $urls['package'] . (strpos($urls['package'], '?') === false ? '?' : '&') . 't=' . time();
        $resp = wp_remote_get($url, [
            'timeout' => 240,
            'user-agent' => 'YimaiTheme-Updater/1.0',
        ]);
        if (is_wp_error($resp)) {
            continue;
        }
        $code = wp_remote_retrieve_response_code($resp);
        $body = wp_remote_retrieve_body($resp);
        if ($code !== 200 || strlen($body) < 1024) {
            continue;
        }
        file_put_contents($dest, $body);
        return [$dest, $source];
    }
    throw new RuntimeException('更新包下载失败（Gitee / GitHub / GitHub 反代均不可达），请检查服务器外网后重试，或使用「上传更新包更新」。');
}

/** 解压 zip（ZipArchive 或 PclZip），返回解压目标目录 */
function yimai_update_extract(string $zipfile, string $dest): string
{
    yimai_update_ensure_dir($dest);
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        if ($zip->open($zipfile) !== true) {
            throw new RuntimeException('无法打开更新包');
        }
        $zip->extractTo($dest);
        $zip->close();
    } else {
        require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
        $zip = new PclZip($zipfile);
        if (($zip->extract(PCLZIP_OPT_PATH, $dest)) === 0) {
            throw new RuntimeException('解压失败：' . $zip->errorInfo(true));
        }
    }
    // 定位包根（含 theme.json 的目录）：兼容外层多一层 <repo>-main/ 目录的归档
    //（历史 codeload 包；当前 Release zip 包根即主题根）
    $root = '';
    if (is_file($dest . '/theme.json')) {
        $root = $dest;
    } else {
        foreach ((array) scandir($dest) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (is_file($dest . '/' . $entry . '/theme.json')) {
                $root = $dest . '/' . $entry;
                break;
            }
        }
    }
    if ($root === '') {
        throw new RuntimeException('更新包结构异常：未找到 theme.json');
    }
    foreach (['style.css', 'functions.php'] as $must) {
        if (!is_file($root . '/' . $must)) {
            throw new RuntimeException('更新包结构异常：缺少 ' . $must);
        }
    }
    return $root;
}

/** 更新前备份当前主题代码（跳过图片目录、历史备份与本地密钥），保留最近 2 份 */
function yimai_update_backup(array &$log): string
{
    $theme = get_template_directory();
    $backupDir = yimai_update_backup_dir();
    yimai_update_ensure_dir($backupDir);
    $dest = $backupDir . '/pre-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.zip';

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($theme, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $list = [];
    foreach ($files as $file) {
        $path = str_replace('\\', '/', (string) $file);
        $base = basename($path);
        // 排除：上传图片、历史备份、更新状态、以及所有本地密钥文件。
        // local-secrets.php 含企微 Webhook 与图床授权码，绝不能进备份包。
        if (str_contains($path, '/assets/images/uploads/')
            || str_contains($path, '/.backups/')
            || $base === '.update-state.json'
            || $base === 'local-secrets.php'
            || $base === '.DS_Store') {
            continue;
        }
        $list[] = $path;
    }
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        $zip->open($dest, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($list as $path) {
            $zip->addFile($path, ltrim(substr($path, strlen($theme)), '/'));
        }
        $zip->close();
    } else {
        require_once ABSPATH . 'wp-admin/includes/class-pclzip.php';
        $zip = new PclZip($dest);
        if (($zip->create($list, PCLZIP_OPT_REMOVE_PATH, $theme)) === 0) {
            throw new RuntimeException('备份创建失败：' . $zip->errorInfo(true));
        }
    }
    $log[] = '已备份当前代码 → ' . basename($dest) . '（' . round(filesize($dest) / 1024) . ' KB，存于 wp-content/yimai-backups）';

    $keep = glob($backupDir . '/pre-*.zip') ?: [];
    if (count($keep) > 2) {
        sort($keep);
        foreach (array_slice($keep, 0, count($keep) - 2) as $old) {
            @wp_delete_file($old);
        }
        $log[] = '清理历史备份，保留最近 2 份';
    }
    return $dest;
}

/**
 * 递归覆盖：包内文件 → 主题目录。不删除服务器上的多余文件。
 * 安全边界：拒绝异常路径，且**永不覆盖本地密钥与备份目录**（即使包内出现）。
 */
function yimai_update_apply(string $root, array &$log): int
{
    $theme = get_template_directory();
    $count = 0;
    $skipped = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        $src = (string) $file;
        $rel = ltrim(substr(str_replace('\\', '/', $src), strlen(str_replace('\\', '/', $root))), '/');
        if ($rel === '' || str_contains($rel, '..')) {
            throw new RuntimeException('更新包含异常路径，已中止：' . $rel);
        }
        // 保护名单：本地密钥、备份与状态文件绝不接受远端覆盖
        $base = basename($rel);
        if ($base === 'local-secrets.php'
            || str_starts_with($rel, '.backups/')
            || str_contains($rel, '/.backups/')
            || $base === '.update-state.json'
            || $base === '.htaccess'
            || $base === '.DS_Store') {
            $skipped[] = $rel;
            continue;
        }
        $target = $theme . '/' . $rel;
        yimai_update_ensure_dir(dirname($target));
        if (!@copy($src, $target)) {
            throw new RuntimeException('文件写入失败（检查目录权限）：' . $rel);
        }
        @touch($target, (int) $file->getMTime());
        $count++;
    }
    $log[] = "已应用 {$count} 个文件到主题目录（保留服务器本地文件：local-secrets、上传图片、备份）";
    if ($skipped !== []) {
        $log[] = '已跳过 ' . count($skipped) . ' 个受保护文件：' . implode('、', array_slice($skipped, 0, 5));
    }
    return $count;
}

/** 更新后一次性数据迁移（幂等） */
function yimai_update_migrate(array &$log): void
{
    $raw = get_option('yimai_site_config', '');
    $config = $raw ? json_decode((string) $raw, true) : [];
    if (is_array($config) && (($config['site']['favicon'] ?? '') === '/uploads/1784264609-33d2bec13509d686.png')) {
        $config['site']['favicon'] = '/favicon.png';
        update_option('yimai_site_config', wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), false);
        $log[] = '已修复后台配置中失效的 favicon 路径';
    }

    // 规范化图床映射：旧版本可能写入带前导斜杠的路径（拼出 //file/ 双斜杠 URL）
    $mapRaw = function_exists('yimai_imgbed_map') ? yimai_imgbed_map() : [];
    if ($mapRaw !== []) {
        $clean = [];
        foreach ($mapRaw as $k => $v) {
            $clean[(string) $k] = ltrim((string) $v, '/');
        }
        if ($clean !== $mapRaw) {
            update_option('yimai_imgbed_map', $clean, false);
            $log[] = '已规范化图床映射中的路径斜杠';
        }
    }

    // v1.3.0 起图片地址「所见即所得」：把配置中已同步图床的本地路径改写为图床 URL，
    // 与旧版「自动优先图床」的前台表现保持一致；未同步的图片保持本地路径不动。
    $map = function_exists('yimai_imgbed_map') ? yimai_imgbed_map() : [];
    $imgbedConfig = function_exists('yimai_imgbed_config') ? yimai_imgbed_config() : [];
    if ($map !== [] && $imgbedConfig !== [] && is_array($config)) {
        $changed = false;
        array_walk_recursive($config, function (&$value) use ($map, $imgbedConfig, &$changed) {
            if (is_string($value) && isset($map[$value])) {
                $value = $imgbedConfig['domain'] . '/' . $map[$value];
                $changed = true;
            }
        });
        if ($changed) {
            update_option('yimai_site_config', wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), false);
            $log[] = '已把后台配置中已同步图床的图片改写为图床地址（本地文件仍保留，可随时在后台切回）';
        }
    }
}

/** 后台「立即更新」入口 */
function yimai_updater_run(): array
{
    $log = [];
    $start = microtime(true);
    try {
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();

        [$zipfile, $source] = yimai_update_download_package(get_temp_dir() . 'yimai-theme-' . bin2hex(random_bytes(4)) . '.zip');
        $log[] = '更新包下载完成（来源：' . $source . '，' . round(filesize($zipfile) / 1024) . ' KB）';
        return yimai_updater_apply_zip($zipfile, $source, $log, $start, true);
    } catch (Throwable $error) {
        $log[] = '更新失败：' . $error->getMessage();
        return ['ok' => false, 'message' => $error->getMessage(), 'log' => $log];
    }
}

/**
 * 后台「上传 zip 更新」入口（对齐微光短剧 App 的「本地安装包」模式）：
 * 服务器外网拉不动 GitHub/Gitee 时，本地 release.sh 打的包直接上传应用。
 * 与在线更新共用同一套校验/备份/覆盖/迁移管线；唯一区别是包来自上传
 * 且跳过内容哈希校验（哈希在远端 manifest 里，外网不通时本来也取不到，
 * 上传者身份 + manage_options/nonce 就是这条通道的信任边界）。
 */
function yimai_updater_run_upload(array $file): array
{
    $log = [];
    $start = microtime(true);
    try {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('请选择更新包（yimaiyoga-theme-latest.zip）');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('上传文件无效');
        }
        $zipfile = get_temp_dir() . 'yimai-theme-upload-' . bin2hex(random_bytes(4)) . '.zip';
        if (!@move_uploaded_file($tmp, $zipfile)) {
            throw new RuntimeException('上传文件落盘失败（检查临时目录权限）');
        }
        $log[] = '更新包上传完成（' . round(filesize($zipfile) / 1024) . ' KB）';
        return yimai_updater_apply_zip($zipfile, '本地上传', $log, $start, false);
    } catch (Throwable $error) {
        $log[] = '更新失败：' . $error->getMessage();
        return ['ok' => false, 'message' => $error->getMessage(), 'log' => $log];
    }
}

/** 在线/上传两条更新通道共用的应用管线 */
function yimai_updater_apply_zip(string $zipfile, string $source, array $log, float $start, bool $checkHash): array
{
    try {
        yimai_update_verify_package($zipfile, $log);

        $work = get_temp_dir() . 'yimai-theme-extract-' . bin2hex(random_bytes(4));
        $root = yimai_update_extract($zipfile, $work);
        if ($checkHash) {
            // 内容哈希校验在解压之后：比对的是文件内容，与 zip 打包方式无关
            yimai_update_check_hash($root, $log);
        }
        $remote = json_decode((string) file_get_contents($root . '/theme.json'), true) ?: [];
        $localVersion = (string) yimai_theme_meta()['version'];
        $remoteVersion = (string) ($remote['version'] ?? '');
        $log[] = '包内版本：v' . ($remoteVersion !== '' ? $remoteVersion : '?') . '（当前 v' . $localVersion . '）';

        // 防降级：拒绝把线上回退到更旧的版本（除非显式强制）
        if ($remoteVersion !== '' && version_compare($remoteVersion, $localVersion, '<')) {
            throw new RuntimeException(
                '拒绝降级：包内版本 v' . $remoteVersion . ' 低于当前 v' . $localVersion . '。'
                . '如确需回退，请从 .backups 手动恢复或走发布流程重新发版。'
            );
        }

        yimai_update_backup($log);
        yimai_update_apply($root, $log);
        yimai_update_migrate($log);

        $state = ['version' => $remote['version'] ?? '', 'time' => current_time('mysql'), 'source' => $source];
        file_put_contents(get_template_directory() . '/.update-state.json', wp_json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 清理临时文件
        @unlink($zipfile);
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($work, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($work);

        $log[] = sprintf('更新完成，耗时 %.1f 秒。', microtime(true) - $start);
        return ['ok' => true, 'version' => $state['version'], 'log' => $log];
    } catch (Throwable $error) {
        $log[] = '更新失败：' . $error->getMessage();
        return ['ok' => false, 'message' => $error->getMessage(), 'log' => $log];
    }
}
