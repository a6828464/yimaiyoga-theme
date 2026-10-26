<?php
/**
 * 后台保存链路回归测试（wp-admin 模式）。
 *
 * 背景：v1.8.0 把内容后台搬进 wp-admin 后，「保存全部更改」在生产上永远失败——
 * 表单隐藏域放的是独立后台的会话令牌（csrf_token()，64 位十六进制），而接口用
 * wp_verify_nonce('yimai_admin_api') 校验（10 位哈希）。两者永不相等，保存必 403，
 * 且 WordPress 用 wp_die('-1') 结束请求，前端拿不到 message，只能显示「保存失败」。
 *
 * 覆盖：
 * - 表单隐藏域令牌与接口校验令牌同源（$yimaiCsrf 在 <form> 之前算好）
 * - WP nonce 能保存成功，且「关闭首页弹窗」真正落库（announcements.enabled=false）
 * - 会话令牌 / 空令牌被拒，并返回可读原因而不是裸 -1
 * - 活动列表项字段路径走 announcements.items 别名（否则改不动、也关不掉）
 *
 * 运行：php tests/admin-save-auth-test.php
 *
 * @package yimaiyoga
 */

require __DIR__ . '/wp-stubs.php';

/* ------------------------------------------------------------------ */
/* 渲染模式：真的把 dashboard 视图渲染出来，比对两个令牌               */
/* ------------------------------------------------------------------ */
if (in_array($argv[1] ?? '', ['--render-wpadmin', '--render-standalone'], true)) {
    $mode = $argv[1] === '--render-wpadmin' ? 'wpadmin' : 'standalone';
    // 走真实主题装载链（函数、常量、后台 bootstrap 一次到位），只把提示类告警降噪，
    // 致命错误单独捕获成 JSON，避免断言在空输出上「假通过」。
    error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);
    register_shutdown_function(static function (): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            fwrite(STDOUT, json_encode(['__fatal' => $error['message']], JSON_UNESCAPED_UNICODE));
        }
    });
    require YIMAI_THEME_DIR . '/functions.php';

    if ($mode === 'wpadmin') {
        function get_current_screen()
        {
            return new stdClass(); // 非 null → 视图走 wp-admin 分支
        }
    } else {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('yimai_render_test');
            @session_start();
        }
        function get_current_screen()
        {
            return null;
        }
    }
    function wp_logout_url(string $redirect = ''): string
    {
        return 'https://example.test/wp-login.php?action=logout';
    }

    $config = load_config();
    ob_start();
    include YIMAI_THEME_DIR . '/admin/views/dashboard.php';
    $html = (string) ob_get_clean();
    preg_match('/name="csrf_token" value="([^"]*)"/', $html, $hidden);
    preg_match('/window\.CSRF_TOKEN = "([^"]*)"/', $html, $token);
    echo json_encode([
        'hidden' => $hidden[1] ?? null,
        'window' => $token[1] ?? null,
        'expect' => $mode === 'wpadmin'
            ? wp_create_nonce('yimai_admin_api')
            : (string) ($_SESSION['csrf_token'] ?? ''),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/* ------------------------------------------------------------------ */
/* 子进程模式：在真实 include 链里跑一次 admin-ajax 请求              */
/* ------------------------------------------------------------------ */
if (($argv[1] ?? '') === '--child') {
    require YIMAI_THEME_DIR . '/inc/site-data.php';
    require YIMAI_THEME_DIR . '/inc/wp-admin-integration.php';

    $case = (string) ($argv[2] ?? 'nonce');
    $token = match ($case) {
        'nonce'   => wp_create_nonce('yimai_admin_api'),
        'session' => str_repeat('a', 64), // 独立后台 csrf_token() 的形状
        default   => '',
    };

    register_shutdown_function(static function (): void {
        fwrite(STDOUT, "\n@@OPTION@@" . (string) get_option('yimai_site_config', ''));
    });

    $_REQUEST['op'] = 'save';
    if ($token !== '') {
        $_REQUEST['csrf_token'] = $token;
    }
    $_POST['config_json'] = json_encode([
        'announcements' => [
            'enabled' => false,
            'stripEnabled' => true,
            'items' => [[
                'id' => 'act-1',
                'title' => '国庆活动',
                'content' => '限时体验课',
                'image' => '',
                'link' => '/booking',
                'linkText' => '查看详情',
                'start' => '',
                'end' => '',
                'active' => true,
            ]],
        ],
        'site' => ['brand' => '一麦瑜伽'],
    ], JSON_UNESCAPED_UNICODE);

    do_action_stub('wp_ajax_yimai_admin_api');
    exit;
}

/* ------------------------------------------------------------------ */
/* 主进程：编排 + 静态不变式                                          */
/* ------------------------------------------------------------------ */
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

function section(string $title): void
{
    echo "\n=== {$title} ===\n";
}

/** 跑一个子进程用例，返回 [解析后的响应对, 落库的 option 原文] */
function run_case(string $case): array
{
    $cmd = sprintf(
        '%s %s --child %s 2>&1',
        escapeshellarg(PHP_BINARY),
        escapeshellarg(__FILE__),
        escapeshellarg($case)
    );
    $out = (string) shell_exec($cmd);
    $parts = explode('@@OPTION@@', $out, 2);
    $body = trim($parts[0]);
    $option = trim($parts[1] ?? '');
    $decoded = json_decode($body, true);
    return [is_array($decoded) ? $decoded : ['__raw' => $body], $option];
}

section('接口认证：令牌来源必须一致');

[$ok, $doneOption] = run_case('nonce');
check('WP nonce 保存成功', $ok['message'] ?? null, '已保存');
$saved = json_decode($doneOption, true);
check('落库 announcements.enabled = false（弹窗已关闭）', $saved['announcements']['enabled'] ?? null, false);
check('落库活动标题', $saved['announcements']['items'][0]['title'] ?? null, '国庆活动');
check('落库活动仍在列表中', count($saved['announcements']['items'] ?? []), 1);

[$expired, $expiredOption] = run_case('session');
check('会话令牌被拒', $expired['message'] ?? null, '页面令牌已过期（通常超过 24 小时未刷新），请按 Ctrl+F5 刷新本页后重新保存');
check('被拒请求未写库', $expiredOption, '');

[$empty, $emptyOption] = run_case('empty');
check('空令牌被拒', $empty['message'] ?? null, '页面令牌已过期（通常超过 24 小时未刷新），请按 Ctrl+F5 刷新本页后重新保存');
check('空令牌未写库', $emptyOption, '');

section('渲染不变式：两个入口的隐藏域与脚本令牌必须一致');

function run_render(string $flag): array
{
    $cmd = sprintf(
        '%s %s %s 2>&1',
        escapeshellarg(PHP_BINARY),
        escapeshellarg(__FILE__),
        escapeshellarg($flag)
    );
    $out = trim((string) shell_exec($cmd));
    $decoded = json_decode($out, true);
    return is_array($decoded) ? $decoded : ['__raw' => $out];
}

$wpadminRender = run_render('--render-wpadmin');
check('wp-admin 渲染：子进程无致命错误', isset($wpadminRender['expect']) ? 'ok' : ($wpadminRender['__fatal'] ?? $wpadminRender['__raw'] ?? 'no-output'), 'ok');
check('wp-admin 渲染：隐藏域令牌 = WP nonce', ($wpadminRender['hidden'] ?? null), $wpadminRender['expect'] ?? null);
check('wp-admin 渲染：脚本令牌 = WP nonce', ($wpadminRender['window'] ?? null), $wpadminRender['expect'] ?? null);
check('wp-admin 渲染：隐藏域不再是 64 位会话令牌', strlen((string) ($wpadminRender['hidden'] ?? '')) === 64, false);

$standaloneRender = run_render('--render-standalone');
check('独立 /admin 渲染：子进程无致命错误', isset($standaloneRender['expect']) ? 'ok' : ($standaloneRender['__fatal'] ?? $standaloneRender['__raw'] ?? 'no-output'), 'ok');
check('独立 /admin 渲染：隐藏域令牌 = 会话令牌', ($standaloneRender['hidden'] ?? null), $standaloneRender['expect'] ?? null);
check('独立 /admin 渲染：脚本令牌 = 会话令牌', ($standaloneRender['window'] ?? null), $standaloneRender['expect'] ?? null);

section('视图不变式：表单令牌先于表单生成，且与脚本令牌同源');

$view = (string) file_get_contents(YIMAI_THEME_DIR . '/admin/views/dashboard.php');
$formPos = strpos($view, '<form data-admin-form');
$tokenAssignPos = strpos($view, '$yimaiCsrf = wp_create_nonce(');
check('视图能找到表单', is_int($formPos), true);
check('$yimaiCsrf 在 <form> 之前赋值', is_int($tokenAssignPos) && $tokenAssignPos < $formPos, true);
check(
    '表单隐藏域使用 $yimaiCsrf',
    str_contains($view, 'name="csrf_token" value="<?php echo esc_attr($yimaiCsrf); ?>"'),
    true
);
check(
    '表单隐藏域不再使用 csrf_token()',
    str_contains($view, 'name="csrf_token" value="<?php echo esc_attr(csrf_token()); ?>"'),
    false
);
check(
    'window.CSRF_TOKEN 与表单同源',
    str_contains($view, 'window.CSRF_TOKEN = <?php echo wp_json_encode($yimaiCsrf'),
    true
);

section('活动列表：字段路径必须指向 announcements.items');

$js = (string) file_get_contents(YIMAI_THEME_DIR . '/assets/js/admin.js');
check('列表项字段路径使用别名 base', str_contains($js, 'var base=LIST_ALIASES[key]||key;'), true);
check("列表项字段路径不再直拼 key（announcements.0.*）", str_contains($js, "var path=key+'.'+idx+'.'+f[0];"), false);
check('别名映射存在', str_contains($js, "var LIST_ALIASES={announcements:'announcements.items'};"), true);

echo "\n----------------------------------------\n";
printf("通过 %d / 失败 %d\n", $pass, $fail);
if ($fail > 0) {
    echo '失败项：' . implode('、', $failures) . "\n";
    exit(1);
}
