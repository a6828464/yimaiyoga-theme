<?php
/**
 * 配置数据层回归测试（纯 CLI，无 WordPress）。
 *
 * 覆盖审计发现的核心缺陷：
 * - 列表删除 / 清空必须生效（曾经被 array_replace_recursive 补回默认值）
 * - 删中间项不得产生重复项
 * - 类型污染必须被拦截（曾经一次误存导致全站 TypeError 白屏）
 *
 * 运行：php tests/config-merge-test.php
 *
 * @package yimaiyoga
 */

require __DIR__ . '/wp-stubs.php';
require YIMAI_THEME_DIR . '/inc/site-data.php';
require YIMAI_THEME_DIR . '/admin/inc/bootstrap.php';

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
        json_encode($expected, JSON_UNESCAPED_UNICODE),
        json_encode($actual, JSON_UNESCAPED_UNICODE)
    );
}

function section(string $title): void
{
    echo "\n=== {$title} ===\n";
}

$default = yimai_site_data(false);

/* ------------------------------------------------------------------ */
section('基线：默认列表长度');
$expectedCounts = [
    'faqs' => 8, 'instructors' => 6, 'courseThemes' => 12, 'classPaths' => 3,
    'studios' => 2, 'nav_items' => 7, 'memberships' => 4, 'training_programs' => 3,
];
foreach ($expectedCounts as $key => $n) {
    check("{$key} 默认 {$n} 条", count($default[$key]), $n);
}

/* ------------------------------------------------------------------ */
section('缺陷 1：删除列表项必须生效');
// 用户删掉第 1 条门店后提交 1 条
$saved = ['studios' => [$default['studios'][1]]];
save_config($saved);
$got = yimai_site_data(true)['studios'];
check('提交 1 条门店 → 落库 1 条', count($got), 1);
check('落库的是保留下来的「绿地店」', $got[0]['name'], '绿地店');

// 用户清空 FAQ
save_config(['faqs' => []]);
check('清空 FAQ → 落库 0 条', count(yimai_site_data(true)['faqs']), 0);

// 用户只留 3 条 FAQ
save_config(['faqs' => array_slice($default['faqs'], 0, 3)]);
check('提交 3 条 FAQ → 落库 3 条', count(yimai_site_data(true)['faqs']), 3);

// 用户清空门店空间图
save_config(['images' => ['studioImages' => []]]);
check('清空门店空间图 → 落库 0 张', count(yimai_site_data(true)['images']['studioImages']), 0);

/* ------------------------------------------------------------------ */
section('缺陷 2：删中间项不得产生重复项');
$three = $default['images']['studioImages'];
check('前置：默认 3 张空间图', count($three), 3);
// 删掉 index 1，保留 [图0, 图2]
save_config(['images' => ['studioImages' => [$three[0], $three[2]]]]);
$got = yimai_site_data(true)['images']['studioImages'];
check('删中间项 → 落库 2 张（非 3 张）', count($got), 2);
check('落库第 1 张 = 原图0', $got[0] ?? null, $three[0]);
check('落库第 2 张 = 原图2', $got[1] ?? null, $three[2]);
check('末项未被复制', $got[0] !== $got[1], true);

// FAQ 删中间项
$faqs = $default['faqs'];
$userFaqs = $faqs;
array_splice($userFaqs, 2, 1); // 删第 3 条
save_config(['faqs' => $userFaqs]);
$got = yimai_site_data(true)['faqs'];
check('FAQ 删第 3 条 → 落库 7 条', count($got), 7);
check('被删的那条确实不存在', in_array($faqs[2], $got, true), false);

/* ------------------------------------------------------------------ */
section('缺陷 3：类型污染必须被拦截（曾致全站白屏）');
$threw = false;
try {
    save_config(['site' => ['keywords' => '宁波瑜伽']]); // 字符串冒充列表
} catch (Yimai_Config_Type_Error $e) {
    $threw = true;
}
check('keywords 传字符串 → 抛类型错误', $threw, true);

// 确认坏数据没被写进 DB
$rawAfter = json_decode((string) get_option('yimai_site_config', ''), true);
check('坏数据未被写入 DB', is_array($rawAfter['site']['keywords'] ?? null), true);

$threw = false;
try {
    save_config(['images' => 'oops']); // 字符串冒充映射
} catch (Yimai_Config_Type_Error $e) {
    $threw = true;
}
check('images 传字符串 → 抛类型错误', $threw, true);

$threw = false;
try {
    save_config(['announcements' => 'oops']);
} catch (Yimai_Config_Type_Error $e) {
    $threw = true;
}
check('announcements 传字符串 → 抛类型错误', $threw, true);

/* ------------------------------------------------------------------ */
section('缺陷 4：合法输入必须照常通过（不能误伤）');
// 正常文本字段
save_config(['site' => ['name' => '一麦瑜伽·普拉提', 'icpNumber' => '浙ICP备00000000号']]);
$site = yimai_site_data(true)['site'];
check('文本字段正常保存', $site['icpNumber'], '浙ICP备00000000号');

// keywords 传数组（正常路径）
save_config(['site' => ['keywords' => ['宁波瑜伽', '宁波普拉提']]]);
check('keywords 传数组正常保存', yimai_site_data(true)['site']['keywords'], ['宁波瑜伽', '宁波普拉提']);

// 整数转换
save_config(['site' => ['logoHeight' => '20']]);
check('logoHeight 字符串数字 → int', yimai_site_data(true)['site']['logoHeight'], 20);

// 布尔
save_config(['announcements' => ['enabled' => false]]);
check('announcements.enabled false 生效', yimai_site_data(true)['announcements']['enabled'], false);

// 新增列表项（FAQ 形状由后台负责，这里验证长度自由）
$newFaqs = $default['faqs'];
$newFaqs[] = ['新问题', '新回答'];
save_config(['faqs' => $newFaqs]);
check('新增 FAQ → 落库 9 条', count(yimai_site_data(true)['faqs']), 9);

/* ------------------------------------------------------------------ */
section('缺陷 5：未知键不得被丢弃（前向兼容）');
save_config(['site' => ['futureField' => 'keep-me']]);
check('未知键被保留', yimai_site_data(true)['site']['futureField'] ?? null, 'keep-me');

/* ------------------------------------------------------------------ */
section('缺陷 6：列表形状校验');
$threw = false;
try {
    save_config(['studios' => ['a' => 'sparse', 'b' => 'keys']]); // 非连续键
} catch (Yimai_Config_Type_Error $e) {
    $threw = true;
}
check('非连续键列表 → 抛类型错误', $threw, true);

/* ------------------------------------------------------------------ */
section('缺陷 7：备份选项必须继续工作且不含明文密钥');
$GLOBALS['__options'] = [];
$GLOBALS['__options']['yimai_site_config'] = wp_json_encode([
    'site' => ['wecomWebhook' => 'https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=SECRET'],
]);
save_config(['site' => ['name' => '测试']]);
$backups = get_option('yimai_site_config_backups', []);
check('备份已写入', is_array($backups) && count($backups) >= 1, true);
$dump = wp_json_encode($backups, JSON_UNESCAPED_UNICODE);
check('备份中不含 webhook 明文密钥', str_contains($dump, 'SECRET'), false);

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
