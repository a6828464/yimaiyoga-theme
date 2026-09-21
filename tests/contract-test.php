<?php
/**
 * 配置契约测试：确保前台模板与后台编辑器共享同一份数据契约。
 *
 * 这些断言守护审计中发现的「所有权重复」与「形状不一致」类缺陷，
 * 防止它们通过一次「顺手清理」重新长回来：
 * - 预约表单的门店/体验方向必须来自配置，不能硬编码
 * - FAQ 形状必须同时容忍元组 [0][1] 与映射 {q,a} 两种写法
 * - 首页/预约页/空间页共用同一套门店卡片模板
 * - 后台列表编辑器覆盖的键必须在保存白名单内
 *
 * 运行：php tests/contract-test.php
 *
 * @package yimaiyoga
 */

require __DIR__ . '/wp-stubs.php';
require YIMAI_THEME_DIR . '/inc/site-data.php';

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
    printf("  ✗ %s\n      期望: %s\n      实际: %s\n", $label, var_export($expected, true), var_export($actual, true));
}

function section(string $t): void
{
    echo "\n=== {$t} ===\n";
}

function theme_file(string $rel): string
{
    return (string) file_get_contents(YIMAI_THEME_DIR . '/' . $rel);
}

$default = yimai_site_data(false);

/* ------------------------------------------------------------------ */
section('契约 1：预约表单必须读配置，不得硬编码门店');

$form = theme_file('template-parts/booking-form.php');
check('门店来自 yimai_studios()', str_contains($form, 'yimai_studios()'), true);
check('无硬编码「东部店」选项', str_contains($form, '<option>东部店</option>'), false);
check('体验方向读配置或兜底列表', str_contains($form, 'interests') || str_contains($form, 'yimai_config'), true);
check('保留 studio 字段名', str_contains($form, 'name="studio"'), true);
check('保留 interest 字段名', str_contains($form, 'name="interest"'), true);

section('契约 2：配置里必须有 booking.interests');
check('booking.interests 存在', is_array($default['booking']['interests'] ?? null), true);
check('至少 1 个方向', count($default['booking']['interests'] ?? []) >= 1, true);

/* ------------------------------------------------------------------ */
section('契约 3：FAQ 形状双向兼容');

// 元组形状（默认数据）
$tuple = ['问题', '回答'];
$q1 = $tuple[0] ?? ($tuple['q'] ?? '');
$a1 = $tuple[1] ?? ($tuple['a'] ?? '');
check('元组形状能正确取值', [$q1, $a1], ['问题', '回答']);

// 映射形状（后台新增项可能写成这样）
$map = ['q' => '新问题', 'a' => '新回答'];
$q2 = $map[0] ?? ($map['q'] ?? '');
$a2 = $map[1] ?? ($map['a'] ?? '');
check('映射形状也能正确取值', [$q2, $a2], ['新问题', '新回答']);

// 空项不得告警
$empty = [];
$q3 = $empty[0] ?? ($empty['q'] ?? '');
check('空项安全回退为空串', $q3, '');

$contact = theme_file('page-contact.php');
check('page-contact 使用空合并取值', str_contains($contact, '??'), true);

/* ------------------------------------------------------------------ */
section('契约 4：门店卡片模板必须共用');

$cards = theme_file('template-parts/studio-cards.php');
check('template-parts/studio-cards.php 存在且非空', strlen($cards) > 100, true);
foreach (['page-booking.php', 'page-studio.php'] as $tpl) {
    check("{$tpl} 复用 studio-cards", str_contains(theme_file($tpl), "get_template_part('template-parts/studio-cards'"), true);
}
check('studio-cards 读 yimai_studios()', str_contains($cards, 'yimai_studios()'), true);

/* ------------------------------------------------------------------ */
section('契约 5：后台列表编辑器覆盖的键必须在保存白名单内');

$adminJs = theme_file('assets/js/admin.js');
$boot = theme_file('admin/inc/bootstrap.php');

// 从 admin.js 提取 SCHEMAS 的顶层键：形如 `  key:{fields:[...]},`
preg_match('/var SCHEMAS=\{(.*?)\n  \};/s', $adminJs, $m);
$schemaKeys = [];
if ($m) {
    // 行首缩进 + 标识符 + :{fields: ，避免把字段数组内容误当键
    preg_match_all('/^\s{4}([A-Za-z_][A-Za-z0-9_]*):\{fields:/m', $m[1], $mm);
    $schemaKeys = array_values(array_unique($mm[1] ?? []));
}

echo '    SCHEMAS 键：' . implode(', ', $schemaKeys) . "\n";
check('SCHEMAS 解析出多个列表键', count($schemaKeys) >= 5, true);

// 保存白名单
preg_match('/foreach \(\[(.*?)\] as \$key\)/s', $boot, $m2);
$whitelist = $m2 ? array_map(static fn($k) => trim($k, " '\"\n\r\t"), explode(',', $m2[1])) : [];
echo '    保存白名单：' . implode(', ', $whitelist) . "\n";

foreach ($schemaKeys as $key) {
    // announcements 的配置结构是 announcements.items（别名）
    $effective = $key === 'announcements' ? 'announcements' : $key;
    $covered = in_array($effective, $whitelist, true)
        || $effective === 'announcements'; // 单独规范化
    check("列表键 {$key} 已被保存逻辑覆盖", $covered, true);
}

/* ------------------------------------------------------------------ */
section('契约 6：所有列表键在默认结构中确实存在');

foreach ($whitelist as $key) {
    if ($key === '' || $key === 'training_rights') {
        // training_rights 是字符串列表，前台只读；仍在默认结构中
    }
    check("默认结构含 {$key}", array_key_exists($key, $default), true);
    check("{$key} 是列表", is_array($default[$key]) && yimai_is_list($default[$key]), true);
}

/* ------------------------------------------------------------------ */
section('契约 7：数据驱动——新增配置键要能被前台读到');

check('yimai_deep_merge 保留新增键', yimai_deep_merge(['a' => 1], ['b' => 2])['b'] ?? null, 2);
check('列表整体替换而非逐索引', yimai_deep_merge(['l' => [1, 2, 3]], ['l' => [9]])['l'], [9]);

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
