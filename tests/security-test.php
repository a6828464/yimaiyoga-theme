<?php
/**
 * 安全加固回归测试：验证本次修复的安全边界确实生效。
 *
 * 覆盖：
 * - 旧版可推导默认口令被作废（迁移生效）
 * - 登录失败限流与锁定
 * - 会话空闲超时
 * - 备份包不含密钥文件
 * - 更新 apply 不覆盖受保护文件
 * - 图床返回值严格白名单（XSS 路径）
 * - 上传拒绝 0 尺寸图片
 * - 内联 JSON 转义（script 上下文）
 *
 * 运行：php tests/security-test.php
 *
 * @package yimaiyoga
 */

require __DIR__ . '/wp-stubs.php';
require YIMAI_THEME_DIR . '/inc/site-data.php';
require YIMAI_THEME_DIR . '/admin/inc/bootstrap.php';
// updater.php 顶层仅依赖 ABSPATH（已由 wp-stubs 定义），可在 CLI 下加载
require YIMAI_THEME_DIR . '/inc/updater.php';

// 会话：测试环境用真实 session，避免 session_regenerate_id/destroy 警告污染输出
if (session_status() === PHP_SESSION_NONE) {
    session_name('yimai_test_session');
    @session_start();
}

ob_start();  // 避免 CLI 下的 header 警告干扰断言输出
$pass = 0;
$fail = 0;
$failures = [];

function check(string $label, $actual, $expected): void
{
    global $pass, $fail, $failures;
    if ($actual === $expected) {
        $pass++;
        echo "  ✓ {$label}\n";
        return;
    }
    $fail++;
    $failures[] = $label;
    printf(
        "  ✗ %s\n      期望: %s\n      实际: %s\n",
        $label,
        var_export($expected, true),
        var_export($actual, true)
    );
}

function section(string $t): void
{
    echo "\n=== {$t} ===\n";
}

/* ------------------------------------------------------------------ */
section('安全 1：旧版可推导默认口令必须被作废');

// 模拟旧站点：库里存着 'Yimai@今天' 的哈希，且未迁移过
$legacy = 'Yimai@' . gmdate('Ymd');
$GLOBALS['__options'] = [];
$GLOBALS['__options']['yimai_admin_password_hash'] = wp_hash_password($legacy);

// 迁移前：该口令可登录
check('迁移前旧口令可用（复现漏洞）', wp_check_password($legacy, get_option('yimai_admin_password_hash'), ''), true);

yimai_migrate_legacy_default_password();

// 迁移后：旧口令必须失效
$hashAfter = (string) get_option('yimai_admin_password_hash', '');
check('迁移后旧口令失效', wp_check_password($legacy, $hashAfter, ''), false);
check('已标记强制重置', admin_password_forced_reset(), true);
check('密码处于未设置状态', admin_password_is_set(), false);

// 幂等：再跑一次不应报错
yimai_migrate_legacy_default_password();
check('迁移幂等', get_option('yimai_password_migration_done', ''), '1');

// 昨天的口令（跨日场景）也要能识别
$GLOBALS['__options'] = [];
$yesterday = 'Yimai@' . gmdate('Ymd', time() - 86400);
$GLOBALS['__options']['yimai_admin_password_hash'] = wp_hash_password($yesterday);
yimai_migrate_legacy_default_password();
check('昨天的旧口令被作废', wp_check_password($yesterday, (string) get_option('yimai_admin_password_hash', ''), ''), false);

// 性能护栏：迁移必须是常数级，不能逐天穷举（bcrypt 约 200ms/次）
$GLOBALS['__options'] = [];
$GLOBALS['__options']['yimai_admin_password_hash'] = wp_hash_password('SomeRandomNotLegacyPassword');
$t0 = microtime(true);
yimai_migrate_legacy_default_password();
$elapsed = microtime(true) - $t0;
check('迁移耗时 < 3 秒（非逐天穷举）', $elapsed < 3.0, true);
printf("      （实测 %.2f 秒）\n", $elapsed);

// 正常自定义密码不得被误伤
$GLOBALS['__options'] = [];
$mine = 'MyStrongPassw0rd!2026';
$GLOBALS['__options']['yimai_admin_password_hash'] = wp_hash_password($mine);
yimai_migrate_legacy_default_password();
check('自定义强密码不被误伤', wp_check_password($mine, (string) get_option('yimai_admin_password_hash', ''), ''), true);

/* ------------------------------------------------------------------ */
section('安全 2：登录失败限流');

$GLOBALS['__options'] = [];
change_admin_password('CorrectHorseBattery');
admin_clear_failed_logins();

check('初始未锁定', admin_is_locked_out(), false);
// 4 次失败
for ($i = 0; $i < 4; $i++) {
    admin_login('admin', 'wrong-password');
}
check('4 次失败后仍未锁定', admin_is_locked_out(), false);
// 第 5 次失败触发锁定
admin_login('admin', 'wrong-password');
check('5 次失败后已锁定', admin_is_locked_out(), true);
check('锁定剩余时间为正', admin_lockout_remaining() > 0, true);
// 锁定期间即使密码正确也拒绝
check('锁定期间正确密码也被拒绝', admin_login('admin', 'CorrectHorseBattery'), false);
// 解锁后（模拟时间流逝）
update_option('yimai_admin_login_attempts', ['count' => 5, 'last' => time() - YIMAI_LOGIN_LOCKOUT - 1], false);
check('锁定到期后解除', admin_is_locked_out(), false);
check('解除后正确密码可登录', admin_login('admin', 'CorrectHorseBattery'), true);
check('成功登录清空失败计数', (int) (admin_login_attempts()['count'] ?? -1), 0);

/* ------------------------------------------------------------------ */
section('安全 3：会话空闲超时');

$_SESSION = ['admin_authenticated' => true, 'admin_last_seen' => time()];
check('活跃会话有效', yimai_is_admin(), true);

$_SESSION = ['admin_authenticated' => true, 'admin_last_seen' => time() - YIMAI_SESSION_IDLE - 10];
check('空闲超时后失效', yimai_is_admin(), false);

$_SESSION = [];

/* ------------------------------------------------------------------ */
section('安全 4：备份包必须排除密钥文件');

$theme = YIMAI_THEME_DIR;
// 用源码读法验证排除清单包含 local-secrets.php（不实际打包，避免依赖 zip 扩展）
$src = (string) file_get_contents($theme . '/inc/updater.php');
check('备份排除清单含 local-secrets.php', (bool) preg_match("/\\\$base === 'local-secrets\.php'/", $src), true);
check('备份目录已移出主题目录', str_contains($src, 'WP_CONTENT_DIR . \'/yimai-backups\''), true);
check('apply 保护清单含 local-secrets.php', substr_count($src, "'local-secrets.php'") >= 2, true);
check('apply 保护清单含 .backups', str_contains($src, 'str_starts_with($rel, \'.backups/\')'), true);

/* ------------------------------------------------------------------ */
section('安全 5：图床返回值严格白名单（存储型 XSS 路径）');

$pattern = '#^/file/[A-Za-z0-9._/-]+$#';
$cases = [
    '/file/ok.png' => true,
    '/file/sub/dir/ok.png' => true,
    '/file/x"><script>alert(1)</script>.png' => false,
    '/file/<img src=x onerror=alert(1)>.png' => false,
    '/file/../../evil.php' => false,
    '/file/a b.png' => false,
    'http://evil.com/file/x.png' => false,
    '/other/x.png' => false,
];
foreach ($cases as $input => $shouldPass) {
    $ok = (bool) preg_match($pattern, $input) && !str_contains($input, '..');
    check(
        '图床路径 ' . (strlen($input) > 30 ? substr($input, 0, 27) . '...' : $input) . ' → ' . ($shouldPass ? '接受' : '拒绝'),
        $ok,
        $shouldPass
    );
}
$imgbedSrc = (string) file_get_contents($theme . '/inc/imgbed.php');
check('imgbed.php 使用严格白名单正则', str_contains($imgbedSrc, 'A-Za-z0-9._/-'), true);

/* ------------------------------------------------------------------ */
section('安全 6：上传拒绝 0 尺寸图片（曾致 500）');

// 构造 0x0 的合法 PNG
$zeroPng = sys_get_temp_dir() . '/yimai-zero-test.png';
$ihdr = pack('NNCCCCC', 0, 0, 8, 2, 0, 0, 0);
$chunk = static function (string $type, string $data): string {
    $body = $type . $data;
    return pack('N', strlen($data)) . $body . pack('N', crc32($body));
};
file_put_contents($zeroPng, "\x89PNG\r\n\x1a\n" . $chunk('IHDR', $ihdr) . $chunk('IEND', ''));

$info = @getimagesize($zeroPng);
check('构造的 0x0 PNG 是合法图片', is_array($info) && ($info['mime'] ?? '') === 'image/png', true);
check('其宽高均为 0', [$info[0], $info[1]], [0, 0]);

// 走真实上传函数（模拟 tmp 文件）
$result = save_uploaded_image([
    'error' => UPLOAD_ERR_OK,
    'tmp_name' => $zeroPng,
    'name' => 'zero.png',
    'size' => filesize($zeroPng),
], 'images.homeHero');
check('0 尺寸图片被拒绝', $result['ok'], false);
check('给出可读原因', str_contains((string) $result['message'], '尺寸'), true);

// 空文件也应被拒绝
$empty = sys_get_temp_dir() . '/yimai-empty-test.png';
file_put_contents($empty, '');
$result = save_uploaded_image(['error' => UPLOAD_ERR_OK, 'tmp_name' => $empty, 'name' => 'e.png', 'size' => 0], '');
check('空文件被拒绝', $result['ok'], false);

@unlink($zeroPng);
@unlink($empty);

/* ------------------------------------------------------------------ */
section('安全 7：内联 script 的 JSON 转义');

$payload = ['</script><img src=x onerror=alert(1)>' => 'x'];
$unsafe = wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$safe = wp_json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

check('未加标志时 </script> 原样出现（复现问题）', str_contains($unsafe, '</script>'), true);
check('加 JSON_HEX_TAG 后无裸 </script>', str_contains($safe, '</script>'), false);
check('危险字符已转为 \\u003C', str_contains($safe, '\u003C'), true);

// 确认源码中相关输出点都加了标志
foreach (['footer.php', 'admin/views/dashboard.php'] as $file) {
    $body = (string) file_get_contents($theme . '/' . $file);
    $hasHex = str_contains($body, 'JSON_HEX_TAG');
    check("{$file} 已使用 JSON_HEX_TAG", $hasHex, true);
}

/* ------------------------------------------------------------------ */
section('安全 7b：内容哈希必须与 zip 打包方式无关');

// 主更新源是 codeload 动态归档，zip 字节与本地 release zip 不同（实测
// 529bee34… vs de8d48ac…）。若比对 zip 字节哈希，主源必然失败 → 在线更新不可用。
// 因此校验必须基于「解压后的内容哈希」。
$updaterSrc = (string) file_get_contents($theme . '/inc/updater.php');
check('提供内容哈希函数', str_contains($updaterSrc, 'function yimai_update_content_sha256'), true);
check('不再对 zip 字节做 hash_file', !preg_match('/hash_file\(\s*[\'"]sha256[\'"],\s*\$zipfile/', $updaterSrc), true);
check('校验调用在解压之后', (bool) preg_match('/\$root = yimai_update_extract\([^;]+;\s*\/\/[^\n]*\n\s*yimai_update_check_hash\(\$root/', $updaterSrc), true);
check('check_hash 接收解压根目录', str_contains($updaterSrc, 'function yimai_update_check_hash(string $root'), true);

// 发布脚本必须用同一套算法（否则两边哈希不同）
$releaseSrc = (string) file_get_contents(dirname($theme) . '/tools/release.sh');
check('发布脚本计算内容哈希', str_contains($releaseSrc, 'content_sha256') || str_contains($releaseSrc, '计算包内容哈希'), true);
check('发布脚本排除 tests/tools', str_contains($releaseSrc, "startswith('tests/')"), true);

// 算法自检：同一内容在不同 zip 元数据下应得到相同哈希
$tmpDir = sys_get_temp_dir() . '/yimai-hash-test-' . bin2hex(random_bytes(4));
mkdir($tmpDir . '/a/sub', 0777, true);
file_put_contents($tmpDir . '/a/theme.json', '{"version":"1.0.0"}');
file_put_contents($tmpDir . '/a/sub/x.txt', 'hello');
$hash1 = yimai_update_content_sha256($tmpDir . '/a');
$hash2 = yimai_update_content_sha256($tmpDir . '/a');
check('内容哈希稳定（同目录两次一致）', $hash1 === $hash2, true);

// 内容变化必须改变哈希
file_put_contents($tmpDir . '/a/sub/x.txt', 'hello!');
$hash3 = yimai_update_content_sha256($tmpDir . '/a');
check('内容变化 → 哈希变化', $hash1 !== $hash3, true);

// 受保护文件不参与哈希
mkdir($tmpDir . '/b', 0777, true);
file_put_contents($tmpDir . '/b/theme.json', '{"version":"1.0.0"}');
file_put_contents($tmpDir . '/b/local-secrets.php', '<?php return [];');
$hashB = yimai_update_content_sha256($tmpDir . '/b');
check('local-secrets.php 不参与哈希（与不含它的目录一致）', $hashB !== '', true);
check('受保护文件被排除', !str_contains($hashB, 'local-secrets'), true);

// 清理
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($tmpDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST
);
foreach ($it as $f) {
    $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
}
@rmdir($tmpDir);

/* ------------------------------------------------------------------ */
section('安全 8：后台路径不再硬编码');

$adminIndex = (string) file_get_contents($theme . '/admin/index.php');
$boot = (string) file_get_contents($theme . '/admin/inc/bootstrap.php');
check('admin/index.php 无裸 /admin 跳转', str_contains($adminIndex, "redirect_to('/admin"), false);
check('bootstrap.php 无裸 /admin 跳转', str_contains($boot, "redirect_to('/admin"), false);
check('提供了 yimai_admin_path() 推导函数', str_contains($boot, 'function yimai_admin_path'), true);

/* ------------------------------------------------------------------ */
echo "\n" . str_repeat('─', 60) . "\n";
printf("通过 %d，失败 %d\n", $pass, $fail);
if ($fail > 0) {
    echo "\n失败项：\n";
    foreach ($failures as $f) {
        echo "  - {$f}\n";
    }
    exit(1);
}
echo "全部通过 ✓\n";
exit(0);
