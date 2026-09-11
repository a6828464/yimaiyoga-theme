<?php
/**
 * 主题在线更新（对齐「一麦工作台」双平台推送模式）
 *
 * 发布渠道：GitHub + Gitee 双仓库（本地 git push 同时推送两边），
 * 服务器从固定 URL 拉取 main 分支 zip 包，解压覆盖主题目录。
 *
 * 环境约定（2026-09 探测测试服务器得出）：
 * - PHP 8.5，未装 zip 扩展 → 优先 PclZip（WordPress 内置），有 ZipArchive 则用之
 * - Gitee / codeload.github.com 可达，raw.githubusercontent.com 不可靠 → 清单与包均 Gitee 优先
 * - proc_open 不可依赖 → 纯 PHP 实现，不落 shell
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) {
    exit;
}

const YIMAI_UPDATE_GITEE_REPO = 'meng-taoo/yimaiyoga-theme';
const YIMAI_UPDATE_GITHUB_REPO = 'a6828464/yimaiyoga-theme';

function yimai_update_sources(): array
{
    return [
        'gitee' => [
            'manifest' => 'https://gitee.com/' . YIMAI_UPDATE_GITEE_REPO . '/raw/main/theme.json',
            'package' => 'https://gitee.com/' . YIMAI_UPDATE_GITEE_REPO . '/repository/archive/main.zip',
        ],
        'github' => [
            'manifest' => 'https://raw.githubusercontent.com/' . YIMAI_UPDATE_GITHUB_REPO . '/main/theme.json',
            'package' => 'https://codeload.github.com/' . YIMAI_UPDATE_GITHUB_REPO . '/zip/refs/heads/main',
        ],
    ];
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

/** 依次尝试各发布渠道拉取远端 theme.json */
function yimai_update_remote_meta(?string &$source_used = null): array
{
    foreach (yimai_update_sources() as $source => $urls) {
        $resp = wp_remote_get($urls['manifest'], ['timeout' => 12, 'headers' => ['Accept' => 'application/json']]);
        if (is_wp_error($resp) || wp_remote_retrieve_response_code($resp) !== 200) {
            continue;
        }
        $json = json_decode(wp_remote_retrieve_body($resp), true);
        if (is_array($json) && !empty($json['version'])) {
            $source_used = $source;
            return $json;
        }
    }
    return [];
}

/** 后台「检查更新」入口 */
function yimai_updater_check(): array
{
    $local = yimai_theme_meta();
    $remote = yimai_update_remote_meta($source);
    if (empty($remote)) {
        return [
            'ok' => false,
            'message' => '无法获取远端版本信息（Gitee / GitHub 均不可达），请稍后重试。',
            'local' => $local,
        ];
    }
    return [
        'ok' => true,
        'local' => $local,
        'remote' => $remote,
        'source' => $source,
        'up_to_date' => version_compare((string) $remote['version'], (string) $local['version'], '<='),
    ];
}

/** 确保目录存在，失败抛异常 */
function yimai_update_ensure_dir(string $dir): void
{
    if (!is_dir($dir) && !wp_mkdir_p($dir)) {
        throw new RuntimeException('目录创建失败：' . $dir);
    }
}

/** 下载更新包到临时文件，Gitee 优先、GitHub 兜底，返回 [文件, 来源] */
function yimai_update_download_package(string $dest): array
{
    foreach (yimai_update_sources() as $source => $urls) {
        $resp = wp_remote_get($urls['package'], [
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
    throw new RuntimeException('更新包下载失败（Gitee 与 GitHub 均不可达），请检查服务器外网后重试。');
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
    // 定位包含 theme.json 的包根目录（GitHub 为 <repo>-main，Gitee 为 <repo> 或带 hash 后缀）
    $root = '';
    foreach ((array) scandir($dest) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (is_file($dest . '/' . $entry . '/theme.json')) {
            $root = $dest . '/' . $entry;
            break;
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

/** 更新前备份当前主题代码（跳过图片目录与历史备份），保留最近 2 份 */
function yimai_update_backup(string &$log): string
{
    $theme = get_template_directory();
    $backupDir = $theme . '/.backups';
    yimai_update_ensure_dir($backupDir);
    $dest = $backupDir . '/pre-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.zip';

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($theme, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $list = [];
    foreach ($files as $file) {
        $path = str_replace('\\', '/', (string) $file);
        if (str_contains($path, '/assets/images/uploads/')
            || str_contains($path, '/.backups/')
            || basename($path) === '.update-state.json') {
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
    $log[] = '已备份当前代码 → .backups/' . basename($dest) . '（' . round(filesize($dest) / 1024) . ' KB）';

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

/** 递归覆盖：包内文件 → 主题目录。不删除服务器上的多余文件，路径含 .. 直接拒绝 */
function yimai_update_apply(string $root, string &$log): int
{
    $theme = get_template_directory();
    $count = 0;
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
        $target = $theme . '/' . $rel;
        yimai_update_ensure_dir(dirname($target));
        if (!@copy($src, $target)) {
            throw new RuntimeException('文件写入失败（检查目录权限）：' . $rel);
        }
        @touch($target, (int) $file->getMTime());
        $count++;
    }
    $log[] = "已应用 {$count} 个文件到主题目录（保留服务器本地文件：local-secrets、上传图片、备份）";
    return $count;
}

/** 更新后一次性数据迁移（幂等） */
function yimai_update_migrate(string &$log): void
{
    $raw = get_option('yimai_site_config', '');
    $config = $raw ? json_decode((string) $raw, true) : [];
    if (is_array($config) && (($config['site']['favicon'] ?? '') === '/uploads/1784264609-33d2bec13509d686.png')) {
        $config['site']['favicon'] = '/favicon.png';
        update_option('yimai_site_config', wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT), false);
        $log[] = '已修复后台配置中失效的 favicon 路径';
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

        if (!yimai_is_admin()) {
            throw new RuntimeException('未登录');
        }

        [$zipfile, $source] = yimai_update_download_package(get_temp_dir() . 'yimai-theme-' . bin2hex(random_bytes(4)) . '.zip');
        $log[] = '更新包下载完成（来源：' . $source . '，' . round(filesize($zipfile) / 1024) . ' KB）';

        $work = get_temp_dir() . 'yimai-theme-extract-' . bin2hex(random_bytes(4));
        $root = yimai_update_extract($zipfile, $work);
        $remote = json_decode((string) file_get_contents($root . '/theme.json'), true) ?: [];
        $log[] = '包内版本：v' . ($remote['version'] ?? '?') . '（当前 v' . yimai_theme_meta()['version'] . '）';

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
