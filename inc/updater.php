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

/**
 * 更新来源。
 *
 * 优先级链条（与 README「发布与更新」章节一致）：
 * - 清单 manifest：GitHub Release 资产 → Gitee Release 资产（先到先得）
 * - 更新包 package：GitHub codeload 分支归档 → Gitee Release 资产
 *
 * 为何包用 codeload 主源：分支归档内容实时生成，绝无 GitHub Release
 * 同名资产被 CDN 缓存吐旧包的问题（2026-09 实测教训，见 CHANGELOG v1.2.3）。
 * 清单两个平台都试，避免单平台不可达时无法检查更新。
 */
function yimai_update_sources(): array
{
    return [
        'github' => [
            'manifest' => 'https://github.com/' . YIMAI_UPDATE_GITHUB_REPO . '/releases/download/auto-latest/yimaiyoga-theme-manifest.json',
            'package' => 'https://codeload.github.com/' . YIMAI_UPDATE_GITHUB_REPO . '/zip/refs/heads/main',
        ],
        'gitee' => [
            'manifest' => 'https://gitee.com/' . YIMAI_UPDATE_GITEE_REPO . '/releases/download/auto-latest/yimaiyoga-theme-manifest.json',
            'package' => 'https://gitee.com/' . YIMAI_UPDATE_GITEE_REPO . '/releases/download/auto-latest/yimaiyoga-theme-latest.zip',
        ],
    ];
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

function yimai_update_check_hash(string $zipfile, array &$log): void
{
    $expected = yimai_update_expected_sha256();
    if ($expected === '') {
        $log[] = '提示：清单未提供 sha256，已跳过哈希校验（建议在发布脚本中生成该字段）';
        return;
    }
    $actual = strtolower((string) hash_file('sha256', $zipfile));
    if (!hash_equals($expected, $actual)) {
        throw new RuntimeException('更新包哈希与清单不一致，已中止（可能下载损坏或被替换）');
    }
    $log[] = '更新包 SHA256 校验通过';
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
        // 加时间戳破缓存：GitHub/Gitee 对同名 Release 资产有 CDN 缓存，重建同名 Release 后可能仍吐旧文件
        $url = $urls['manifest'] . (strpos($urls['manifest'], '?') === false ? '?' : '&') . 't=' . time();
        $resp = wp_remote_get($url, ['timeout' => 12, 'headers' => ['Accept' => 'application/json']]);
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
    $remote = yimai_update_remote_meta($source);
    if (empty($remote)) {
        return [
            'ok' => false,
            'message' => '无法获取远端版本信息（Gitee / GitHub 均不可达），请稍后重试。',
            'local' => $local,
            'changelog' => yimai_local_changelog(),
        ];
    }
    return [
        'ok' => true,
        'local' => $local,
        'remote' => $remote,
        'source' => $source,
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

/** 下载更新包到临时文件，GitHub 优先、Gitee 兜底，返回 [文件, 来源] */
function yimai_update_download_package(string $dest): array
{
    foreach (yimai_update_sources() as $source => $urls) {
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
    // 定位包根（含 theme.json 的目录）：GitHub codeload 包为 <repo>-main 一层目录，
    // 本地发布脚本打的 zip 包根即主题根（无外层目录）
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

        if (!yimai_is_admin()) {
            throw new RuntimeException('未登录');
        }

        [$zipfile, $source] = yimai_update_download_package(get_temp_dir() . 'yimai-theme-' . bin2hex(random_bytes(4)) . '.zip');
        $log[] = '更新包下载完成（来源：' . $source . '，' . round(filesize($zipfile) / 1024) . ' KB）';
        yimai_update_verify_package($zipfile, $log);
        yimai_update_check_hash($zipfile, $log);

        $work = get_temp_dir() . 'yimai-theme-extract-' . bin2hex(random_bytes(4));
        $root = yimai_update_extract($zipfile, $work);
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
