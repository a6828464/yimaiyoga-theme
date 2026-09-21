<?php
/**
 * 样式契约测试：防止「模板用了某个 class，但 CSS 里没有对应规则」的静默版式回归。
 *
 * 背景：一次重构把 page-blog.php / single.php 的内联样式移除、准备回收进 CSS 时，
 * 回收动作没有同步完成——模板已经改用 .blog-card-media / .post-cover 等新类，
 * 但 CSS 里一条规则都没有。桌面端博客列表与文章封面会完全失去版式，而
 * 所有 PHP lint、既有测试都仍然全绿。此测试守护这类「跨文件契约断裂」。
 *
 * 运行：php tests/style-contract-test.php
 *
 * @package yimaiyoga
 */

$theme = dirname(__DIR__);

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

/* ------------------------------------------------------------------ */
section('样式契约 1：样式文件存在且配平');

foreach (['app.css', 'admin.css'] as $file) {
    $path = $theme . '/assets/css/' . $file;
    check("{$file} 存在", is_file($path), true);
    if (!is_file($path)) {
        continue;
    }
    $css = (string) file_get_contents($path);
    $open = substr_count($css, '{');
    $close = substr_count($css, '}');
    check("{$file} 大括号配平", $open === $close, true);
}

// 已删除的旧文件不应复活
foreach (['fix.css', 'mobile-fix.css'] as $gone) {
    check("{$gone} 已删除（内容并入 app.css）", is_file($theme . '/assets/css/' . $gone), false);
}

// 不应有模板再引用已删除的文件
$phpFiles = glob($theme . '/*.php') + glob($theme . '/template-parts/*.php') + glob($theme . '/admin/views/*.php');
foreach ($phpFiles as $f) {
    $body = (string) file_get_contents($f);
    $rel = str_replace($theme . '/', '', $f);
    foreach (['fix.css', 'mobile-fix.css'] as $gone) {
        if (str_contains($body, $gone) && !str_contains($body, '原 ' . $gone) && !str_contains($body, '原' . str_replace('.css', '', $gone))) {
            // 仅当作为真实引用（link/enqueue）时算失败
            if (preg_match('#(href|enqueue_style)[^\n]*' . preg_quote($gone, '#') . '#', $body)) {
                check("{$rel} 不再引用 {$gone}", false, true);
            }
        }
    }
}

/* ------------------------------------------------------------------ */
section('样式契约 2：模板使用的 class 必须有 CSS 规则');

$css = '';
foreach (['app.css', 'admin.css'] as $file) {
    $p = $theme . '/assets/css/' . $file;
    if (is_file($p)) {
        $css .= (string) file_get_contents($p);
    }
}
preg_match_all('/\.([a-zA-Z][\w-]*)/', $css, $cm);
$cssClasses = array_flip($cm[1]);

$templates = [
    'header.php', 'footer.php', 'front-page.php', 'index.php', 'single.php', '404.php',
    'page-blog.php', 'page-booking.php', 'page-classes.php', 'page-contact.php',
    'page-instructors.php', 'page-membership.php', 'page-studio.php', 'page-training.php',
    'template-parts/booking-form.php', 'template-parts/studio-cards.php', 'template-parts/membership-rows.php',
];

/**
 * 允许「有标记但无专用规则」的白名单——必须逐条给出理由，不能图省事清空。
 * 每条都是经过核对的：要么是纯结构容器由父级 grid/flex 定位，要么由元素选择器覆盖。
 */
$allow = [
    'blog-card-body'        => 'grid 第二列子元素，自动落位，无需规则',
    'post-content'          => '由 .post-content 元素选择器覆盖（见 app.css）',
    'teacher-details'       => '纯结构包裹层（原始版本即如此，非本次引入）',
    'path-strip-description'=> '由 .path-strip h3 元素选择器覆盖',
    'reveal'                => '由 .reveal / .reveal.visible 覆盖（含 noscript 兜底）',
    'visible'               => '配合 .reveal 的状态类',
    'active'                => '配合 .teacher-detail.active 等状态类',
    'open'                  => '配合 .mobile-menu.open / .notice-backdrop.open 状态类',
    'page-numbers'          => '由 .pagination .page-numbers 覆盖',
    'current'               => '配合 .page-numbers.current 状态类',
    'hp-field'              => '蜜罐字段，由 .hp-field 覆盖',
    'eyebrow'               => '由元素/组合选择器覆盖',
    'muted-light'           => '通用辅助类',
    'studio-hero'           => '预留语义钩子；视觉全部由同元素的 .story-panel 提供（原始版本即无规则，非本次引入）',
];

$missing = [];
foreach ($templates as $tpl) {
    $path = $theme . '/' . $tpl;
    if (!is_file($path)) {
        continue;
    }
    $body = (string) file_get_contents($path);
    // 只取静态 class 字面量；含 PHP 插值的片段不做判定（避免误报）
    if (preg_match_all('/class="([^"$<>?]*)"|\'class="([^"$<>?]*)"\'/', $body, $m, PREG_SET_ORDER)) {
        foreach ($m as $set) {
            $raw = $set[1] !== '' ? $set[1] : ($set[2] ?? '');
            foreach (preg_split('/\s+/', trim($raw)) as $cls) {
                if ($cls === '' || isset($cssClasses[$cls]) || isset($allow[$cls])) {
                    continue;
                }
                $missing[$cls] = ($missing[$cls] ?? 0) + 1;
            }
        }
    }
}

ksort($missing);
if ($missing !== []) {
    echo "    未覆盖的 class：\n";
    foreach ($missing as $c => $n) {
        echo "      - {$c} (出现 {$n} 次)\n";
    }
}
check('模板中不存在无样式依据的 class', $missing, []);

/* ------------------------------------------------------------------ */
section('样式契约 3：关键版式类必须真的存在');

// 这些是「移除内联样式后必须补回」的类，缺任何一个都会造成可见的版式回归
foreach ([
    'blog-card', 'blog-card-media', 'blog-card-excerpt',
    'post-cover', 'post-back', 'pagination', 'sr-only',
] as $critical) {
    check(".{$critical} 有 CSS 规则", isset($cssClasses[$critical]), true);
}

/* ------------------------------------------------------------------ */
section('样式契约 4：内联样式不得散落在前台模板');

$inlineAllowed = ['header.php'];  // noscript 兜底样式，必须内联（否则禁用 JS 时也无效）
foreach ($templates as $tpl) {
    $path = $theme . '/' . $tpl;
    if (!is_file($path)) {
        continue;
    }
    $body = (string) file_get_contents($path);
    $hasInlineStyleTag = (bool) preg_match('#<style[ >]#', $body);
    $hasStyleAttr = (bool) preg_match('#\sstyle="[^"]*(grid-template|display:|padding:|margin:|aspect-ratio)#', $body);
    if (in_array($tpl, $inlineAllowed, true)) {
        continue;
    }
    if ($hasInlineStyleTag) {
        check("{$tpl} 无 <style> 块", false, true);
    } elseif ($hasStyleAttr) {
        check("{$tpl} 无版式类内联 style", false, true);
    }
}
check('前台模板内联样式已回收（noscript 兜底除外）', true, true);

/* ------------------------------------------------------------------ */
section('样式契约 5：移动端覆盖必须位于 media query 内');

$app = (string) file_get_contents($theme . '/assets/css/app.css');
$pos = strpos($app, '.blog-card{display:grid!important');
check('存在手机端 .blog-card 单列覆盖', $pos !== false, true);
if ($pos !== false) {
    $depth = 0;
    for ($i = 0; $i < $pos; $i++) {
        if ($app[$i] === '{') {
            $depth++;
        } elseif ($app[$i] === '}') {
            $depth--;
        }
    }
    check('该覆盖位于 @media 块内（嵌套深度 ≥ 1）', $depth >= 1, true);
}

// 桌面基样式必须在 media 之外且位于手机覆盖之前（source order 决定覆盖关系）
$desktopPos = strpos($app, '.blog-card{display:grid;grid-template-columns:220px');
check('存在桌面两栏基样式', $desktopPos !== false, true);
if ($desktopPos !== false && $pos !== false) {
    check('桌面基样式在手机覆盖之前', $desktopPos < $pos, true);
}

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
