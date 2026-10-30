<?php
/**
 * 一麦瑜伽官网 WordPress 主题
 * 由 yimaiyoga.com PHP MVC 官网转换而来。
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) {
    exit;
}

define('YIMAI_VERSION', '1.10.2');
define('YIMAI_THEME_DIR', get_template_directory());
define('YIMAI_THEME_URI', get_template_directory_uri());

require_once YIMAI_THEME_DIR . '/inc/site-data.php';
require_once YIMAI_THEME_DIR . '/inc/imgbed.php';
// 门店详情页（一店一页 /studio/east · /studio/greenland）的数据层：
// 注册表、H1、FAQ、到店路线，前台模板与 Schema 共用同一份。
require_once YIMAI_THEME_DIR . '/inc/studio-pages.php';
// 两店周课表（xlsx 一次性转成的静态数据 + 归一化/过滤口径）：运行时不读 xlsx。
require_once YIMAI_THEME_DIR . '/inc/studio-timetables.php';

/* -------------------------------------------------------------------------
 * 主题支持
 * ---------------------------------------------------------------------- */
function yimai_setup(): void
{
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('automatic-feed-links');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
    add_theme_support('custom-logo', ['height' => 64, 'width' => 220, 'flex-height' => true, 'flex-width' => true]);
    add_theme_support('align-wide');

    register_nav_menus([
        'primary' => '主导航',
        'footer'  => '页脚导航',
    ]);
}
add_action('after_setup_theme', 'yimai_setup');

/* -------------------------------------------------------------------------
 * 样式与脚本
 * ---------------------------------------------------------------------- */
function yimai_enqueue(): void
{
    // 前台样式已合并为单一 app.css（原 app/fix/mobile-fix 三层合并，
    // 消除了 fix 与 mobile-fix 之间无依赖声明导致的顺序不确定问题）。
    wp_enqueue_style('yimai-app', YIMAI_THEME_URI . '/assets/css/app.css', [], YIMAI_VERSION);

    // 文章正文排版（标题层级 / 表格边框 / 代码块 / 引用 / 图片圆角等）
    // 仅在单篇与页面加载（首页是静态首屏 + 卡片网格，不加载，省一次请求）。
    if (is_singular() && !is_front_page()) {
        wp_enqueue_style(
            'yimai-article',
            YIMAI_THEME_URI . '/assets/css/article.css',
            ['yimai-app'],
            YIMAI_VERSION
        );
    }

    wp_enqueue_script('yimai-app', YIMAI_THEME_URI . '/assets/js/app.js', [], YIMAI_VERSION, true);

    wp_localize_script('yimai-app', 'yimaiAjax', [
        'url'   => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('yimai_booking'),
    ]);
}
add_action('wp_enqueue_scripts', 'yimai_enqueue');

/* -------------------------------------------------------------------------
 * 辅助函数（对应原站 app/helpers.php 与 app/themes.php）
 * ---------------------------------------------------------------------- */
/**
 * 站点配置（默认结构 + DB 覆盖）。带静态缓存：一次请求内只解析一次，
 * 避免每个调用点都重复 json_decode + 深合并（实测单次约 0.1ms，前台 6-10 次调用）。
 *
 * @param bool $invalidate 传 true 丢弃缓存重新读取（保存配置后使用）
 */
function yimai_config(bool $invalidate = false): array
{
    static $cache = null;
    if ($invalidate) {
        $cache = null;
    }
    if ($cache === null) {
        $cache = yimai_site_data();
    }
    return $cache;
}

/**
 * 服务器本地密钥（inc/local-secrets.php，git 忽略，不随更新包分发）。
 * 返回形如 ['wecom_webhook' => 'https://...'] 的数组。
 */
function yimai_site_secrets(): array
{
    static $secrets = null;
    if ($secrets === null) {
        $file = YIMAI_THEME_DIR . '/inc/local-secrets.php';
        $secrets = is_file($file) ? (array) require $file : [];
    }
    return $secrets;
}

/**
 * 企业微信 Webhook 解析顺序：后台配置（wp_options）→ 服务器本地密钥文件 → 空。
 */
function yimai_wecom_webhook(): string
{
    $configured = trim((string) (yimai_config()['site']['wecomWebhook'] ?? ''));
    if ($configured !== '') {
        return $configured;
    }
    return trim((string) (yimai_site_secrets()['wecom_webhook'] ?? ''));
}

/* -------------------------------------------------------------------------
 * SEO：标题与描述（用后台配置的 site.title / site.description）
 * ---------------------------------------------------------------------- */
/**
 * 标题接管：document_title_parts 里 title / tagline / site 三个键都要归位。
 * 只改 title 会漏掉 WP 自动追加的 tagline（首页）与 site（子页面），
 * 结果是「主标题 | 副标题 | 站点名」或「页面名 | 品牌 | 品牌」（品牌重复两次）。
 */
function yimai_document_title_parts(array $parts): array
{
    $config = yimai_config();
    $siteTitle = trim((string) ($config['site']['title'] ?? ''));
    if ($siteTitle === '') {
        return $parts;
    }
    // 无论哪类页面，WP 默认追加的两段（tagline / site）都由我们接管
    $parts['tagline'] = '';
    $parts['site'] = '';
    if (is_front_page() || is_home()) {
        // 首页：只用配置主标题（控制在 30 个汉字内），不拼副标题与站点名
        $parts['title'] = $siteTitle;
        return $parts;
    }
    // 子页面：优先用 GSO 生成的 SEO 标题（yimai_gso_seo_title），没有才用页面标题。
    // 拼成「标题 | 品牌」后超过 30 个字就只输出标题：搜索引擎按字符数截断，
    // 超长时品牌名会被切掉一半，反而不如留一个完整的标题。
    $configName = trim((string) ($config['site']['name'] ?? ''));
    $brand = $configName !== '' ? $configName : $siteTitle;
    // 只有真正的一篇内容/页面才有 post meta；归档页取到的 get_the_ID() 是循环里的第一篇文章，不能读
    $postId = is_singular() ? (int) get_the_ID() : 0;
    $aiTitle = $postId > 0 ? trim((string) get_post_meta($postId, 'yimai_gso_seo_title', true)) : '';
    $pageTitle = $aiTitle !== '' ? $aiTitle : trim((string) ($parts['title'] ?? ''));
    if ($pageTitle === '') {
        $parts['title'] = $brand;
        return $parts;
    }
    $combined = $pageTitle . ' | ' . $brand;
    $parts['title'] = mb_strlen($combined) > 30 ? $pageTitle : $combined;
    return $parts;
}
add_filter('document_title_parts', 'yimai_document_title_parts', 20);

function yimai_meta_description(): void
{
    $config = yimai_config();
    $desc = trim((string) ($config['site']['description'] ?? ''));
    // 文章/页面页：优先输出本页自己的摘要（GSO 生成的 yimai_gso_excerpt →
    // 手动摘要 → 正文截断），避免全站搜索结果摘要都是同一句品牌简介。
    if (is_singular()) {
        $post_id = (int) get_the_ID();
        $singular_desc = trim((string) get_post_meta($post_id, 'yimai_gso_excerpt', true));
        // /contact 的页面正文是演示样稿：优先用 copy.contact.description 的中文 NAP 摘要，
        // 不让英文占位文案进入搜索结果摘要（仍让位于 GSO 生成的文章级 SEO 摘要）。
        if ($singular_desc === '' && is_page('contact')) {
            $singular_desc = trim((string) ($config['copy']['contact']['description'] ?? ''));
        }
        if ($singular_desc === '') {
            $post_obj = get_post($post_id);
            if ($post_obj) {
                $singular_desc = trim((string) $post_obj->post_excerpt);
                if ($singular_desc === '') {
                    $singular_desc = mb_substr(trim(preg_replace('/\s+/', ' ', wp_strip_all_tags((string) $post_obj->post_content))), 0, 160);
                }
            }
        }
        if ($singular_desc !== '') {
            $desc = $singular_desc;
        }
    }
    if ($desc !== '') {
        echo "\n<meta name=\"description\" content=\"" . esc_attr($desc) . "\">";
    }
    // canonical：核心 rel_canonical() 对非 singular 页面（首页、归档）不输出
    if (!is_singular()) {
        global $wp;
        $path = isset($wp->request) ? trim((string) $wp->request, '/') : '';
        // 分页页自引用：$wp->request 里已经包含 page/N（例如 blog/page/2），
        // 必须先按正则取出「基路径 + 页码」再拼，否则第 2 页的 canonical 会指向第 1 页——
        // 那样分页内容永远不会自己成为规范地址，等于把第 2 页之后从索引里抹掉。
        if ($path !== '' && preg_match('#^(.+)/page/([0-9]+)$#', $path, $pageMatch)) {
            $url = home_url('/' . $pageMatch[1] . '/page/' . $pageMatch[2] . '/');
        } elseif ($path !== '') {
            $url = home_url('/' . $path . '/');
            // 基路径里不带 page/N，但查询确实是分页（如首页或归档的 ?paged=2）时沿用原逻辑
            if (is_paged()) {
                $paged = (int) get_query_var('paged');
                if ($paged > 1) {
                    $url = home_url('/' . $path . '/page/' . $paged . '/');
                }
            }
        } else {
            $paged = is_paged() ? (int) get_query_var('paged') : 0;
            $url = $paged > 1 ? home_url('/page/' . $paged . '/') : home_url('/');
        }
        echo "\n<link rel=\"canonical\" href=\"" . esc_url($url) . "\">";
    }
    // Open Graph / Twitter / 文章页 description：由「一麦GSO双擎优化」插件统一输出
    // （文章级 og:title/og:description 取每篇文章自己的 SEO 摘要，而非全站同一句简介）。
    // 主题保留：站点 title（上方）、全局 description（上方）、canonical（上方）、
    // 以及下方插件未配置 GEO 实体时的门店 LocalBusiness Schema 兜底。
    $gso_active = defined('YIMAI_GSO_VERSION');
    if (!$gso_active) {
        $site = $config['site'] ?? [];
        $title = trim((string) ($site['title'] ?? ''));
        if (is_singular()) {
            $title = wp_get_document_title();
        }
        $ogImage = '';
        if (is_singular() && has_post_thumbnail()) {
            $ogImage = (string) get_the_post_thumbnail_url(null, 'full');
        }
        if ($ogImage === '') {
            $ogImage = yimai_image_url((string) ($config['images']['homeHero'] ?? ''));
        }
        if ($title !== '') {
            echo "\n<meta property=\"og:type\" content=\"" . (is_singular() ? 'article' : 'website') . "\">";
            echo "\n<meta property=\"og:site_name\" content=\"" . esc_attr((string) ($site['name'] ?? '')) . "\">";
            echo "\n<meta property=\"og:title\" content=\"" . esc_attr($title) . "\">";
            if ($desc !== '') {
                echo "\n<meta property=\"og:description\" content=\"" . esc_attr($desc) . "\">";
            }
            echo "\n<meta property=\"og:url\" content=\"" . esc_url(is_singular() ? (string) get_permalink() : home_url('/')) . "\">";
            if ($ogImage !== '') {
                echo "\n<meta property=\"og:image\" content=\"" . esc_url($ogImage) . "\">";
            }
            echo "\n<meta name=\"twitter:card\" content=\"summary_large_image\">";
            echo "\n<meta name=\"twitter:title\" content=\"" . esc_attr($title) . "\">";
            if ($desc !== '') {
                echo "\n<meta name=\"twitter:description\" content=\"" . esc_attr($desc) . "\">";
            }
            if ($ogImage !== '') {
                echo "\n<meta name=\"twitter:image\" content=\"" . esc_url($ogImage) . "\">";
            }
        }
    }
    // 结构化数据：门店实体。归属见 yimai_theme_owns_local_schema()：
    // 旧插件（< 2.11.0）自己会输出 1 个无坐标的 LocalBusiness，主题必须让位，
    // 否则同页变成 3 个本地商家节点（NAP 冲突）；插件缺失或已是新契约时由主题输出两店。
    // $site 原本只在「插件未激活」的 OG 分支里赋值，插件激活时这里会是未定义变量
    $site = is_array($config['site'] ?? null) ? $config['site'] : [];
    $studios = is_array($config['studios'] ?? null) ? $config['studios'] : [];
    if (yimai_theme_owns_local_schema() && $studios !== [] && (is_front_page() || is_home())) {
        $shops = [];
        foreach ($studios as $studio) {
            if (!is_array($studio) || trim((string) ($studio['name'] ?? '')) === '') {
                continue;
            }
            // 没有任何合法 geo 的门店不输出节点：半截 LocalBusiness 会被 Rich Results 报错，比没有更糟
            $shop = yimai_studio_schema($studio, $site);
            if ($shop !== null) {
                $shops[] = $shop;
            }
        }
        if ($shops !== []) {
            $ld = ['@context' => 'https://schema.org', '@graph' => $shops];
            echo "\n<script type=\"application/ld+json\">"
                . wp_json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
                . '</script>';
        }
    }
    // 门店详情页（一店一页）：只输出「当前这家店」一个实体，@id 指向店内锚点。
    // 首页两店的 @graph 与这里互斥（is_front_page 与门店页不会同时成立），不会重复声明。
    $studioKey = yimai_current_studio_key();
    if (yimai_theme_owns_local_schema() && $studioKey !== '') {
        $studioData = yimai_studio_page_data($studioKey);
        if ($studioData !== []) {
            $shop = yimai_studio_schema($studioData['studio'], $site, (string) $studioData['entry']['slug']);
            if ($shop !== null) {
                $ld = ['@context' => 'https://schema.org'] + $shop;
                echo "\n<script type=\"application/ld+json\">"
                    . wp_json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
                    . '</script>';
            }
        }
    }
}
add_action('wp_head', 'yimai_meta_description', 1);

/**
 * 门店 LocalBusiness 由谁输出：插件未装 → 主题兜底；插件 >= 2.11.0 → 主题接管；旧插件 → 让位。
 * 用版本号而不是 method_exists：后者依赖插件内部方法名，改名就静默失效。
 * 阈值 2.11.0 与插件侧 union 契约同步。守卫存在时四种组合都不会产生重复本地实体：
 *   插件未装        → 主题输出两店；
 *   插件 < 2.11.0   → 主题让位，门店 Schema 由插件输出（1 个 LocalBusiness，旧形态），主题一个都不出；
 *   插件 >= 2.11.0  → 主题接管两店，插件侧 LocalBusiness 由 yimai_gso_emit_local_business 关闭，
 *                     无坐标时降级为 Organization。
 * 升级顺序：先主题、后插件（先升插件会出现「只有 Organization、无 LocalBusiness」的空窗）。
 */
function yimai_theme_owns_local_schema(): bool
{
    if (!defined('YIMAI_GSO_VERSION')) {
        return true;
    }
    return version_compare((string) YIMAI_GSO_VERSION, '2.11.0', '>=');
}

/**
 * 门店 GEO / 邮编 / 营业时间的服务端校验规则在 inc/site-data.php 的
 * yimai_studio_geo_sanitize()：后台保存与前台输出共用同一份，避免两边规则漂移。
 * 非法值不是拒绝保存，而是清空该字段（与 admin.js 的「标红置空」一致）。
 */
function yimai_studio_geo_clean(array $studio): array
{
    return yimai_studio_geo_sanitize($studio);
}

/**
 * 单家门店 → HealthAndBeautyBusiness 节点。
 *
 * 两种用法，行为必须分开：
 * - 首页两店（$slug 为空）：没有合法 geo 时返回 null，调用方跳过该店
 *   —— 半截 LocalBusiness 会被 Rich Results 报错，比没有更糟；
 * - 门店详情页（$slug 非空）：节点一定输出，geo 为空时省略 geo 字段
 *   —— 那一页本身就是这家店的官方落地页，没有坐标也要有 @id / address / telephone，
 *   少了坐标只损失地图增强，不会让整家店从结构化数据里消失。
 */
function yimai_studio_schema(array $studio, array $site, string $slug = ''): ?array
{
    // 输出侧再校验一次：绕过后台 JS 直接写库的脏值（lat=200 / postalCode=abc）不会进 JSON-LD
    $studio = yimai_studio_geo_clean($studio);
    // 旧 DB 记录缺 geo/邮编等字段时，按店名回落默认结构里同一家店的占位值
    $fallback = yimai_studio_geo_clean(yimai_studio_defaults()[trim((string) ($studio['name'] ?? ''))] ?? []);
    $lat = trim((string) ($studio['latitude'] ?? ''));
    $lng = trim((string) ($studio['longitude'] ?? ''));
    if ($lat === '' || $lng === '') {
        $lat = trim((string) ($fallback['latitude'] ?? ''));
        $lng = trim((string) ($fallback['longitude'] ?? ''));
    }
    $hasGeo = is_numeric($lat) && is_numeric($lng);
    if ($slug === '' && !$hasGeo) {
        return null;
    }
    $shop = [
        '@type' => 'HealthAndBeautyBusiness',
        'name' => (string) ($site['name'] ?? '') . ' · ' . $studio['name'],
        'telephone' => (string) ($studio['phone'] ?? ''),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => (string) ($studio['address'] ?? ''),
            'addressRegion' => '浙江省',
            'addressLocality' => '宁波市',
            'addressCountry' => 'CN',
        ],
    ];
    if ($slug !== '') {
        // 门店页独立实体：@id 指到店内锚点，url 指到门店页，引用方可以稳定指向同一家店
        $shop['@id'] = home_url('/studio/' . $slug . '#store');
        $shop['url'] = home_url('/studio/' . $slug . '/');
    }
    if ($hasGeo) {
        $shop['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => (float) $lat,
            'longitude' => (float) $lng,
        ];
    }
    $postalCode = trim((string) ($studio['postalCode'] ?? ($fallback['postalCode'] ?? '')));
    if ($postalCode !== '') {
        $shop['address']['postalCode'] = $postalCode;
    }
    // 营业时间为空就整段省略：合法的本地商家允许没有营业时间，但不允许写错的时间
    $hours = yimai_opening_hours_spec($studio['openingHours'] ?? []);
    if ($hours !== []) {
        $shop['openingHoursSpecification'] = $hours;
    }
    return $shop;
}

/** 默认结构里同名门店的索引（旧 DB 缺 geo 等字段时的回落来源） */
function yimai_studio_defaults(): array
{
    static $defaults = null;
    if ($defaults !== null) {
        return $defaults;
    }
    $defaults = [];
    foreach ((array) (yimai_site_data(false)['studios'] ?? []) as $defaultStudio) {
        if (is_array($defaultStudio) && isset($defaultStudio['name'])) {
            $defaults[(string) $defaultStudio['name']] = $defaultStudio;
        }
    }
    return $defaults;
}

/**
 * 只有主题确实持有门店 Schema 时才关掉插件那份 LocalBusiness；
 * 旧插件根本不认识这个 filter，注册了也是假生效，不如不注册。
 */
if (yimai_theme_owns_local_schema()) {
    add_filter('yimai_gso_emit_local_business', '__return_false');
}

/**
 * FAQ 列表 → FAQPage 的 mainEntity 条目。
 * 元组 ['问题','回答'] 与映射 {q,a} 两种形状都兼容（后台「新增」写的是后者）。
 */
function yimai_faq_items(array $faqs): array
{
    $items = [];
    foreach ($faqs as $faq) {
        $faq = is_array($faq) ? $faq : [];
        $question = trim((string) ($faq[0] ?? ($faq['q'] ?? '')));
        $answer = trim((string) ($faq[1] ?? ($faq['a'] ?? '')));
        if ($question === '' || $answer === '') {
            continue;
        }
        $items[] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
        ];
    }
    return $items;
}

/**
 * FAQPage 结构化数据：只在真正展示 FAQ 的页面输出
 * —— /contact 的 .faq-panel（全站 FAQ），以及门店详情页（该店 FAQ，与页面同源同顺序）。
 * 其余页面不输出，避免全站每页都声明同一组问答被判为重复标记。
 */
function yimai_faq_schema(): void
{
    $items = [];
    if (is_page('contact')) {
        $items = yimai_faq_items(yimai_faqs());
    } else {
        $studioKey = yimai_current_studio_key();
        if ($studioKey !== '') {
            $studioData = yimai_studio_page_data($studioKey);
            if ($studioData !== []) {
                $items = yimai_faq_items(
                    yimai_studio_page_faqs(
                        $studioData['studio'],
                        $studioData['entry'],
                        yimai_studio_page_transit($studioKey)
                    )
                );
            }
        }
    }
    if ($items === []) {
        return;
    }
    $ld = ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items];
    echo "\n<script type=\"application/ld+json\">"
        . wp_json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
        . '</script>';
}
add_action('wp_head', 'yimai_faq_schema', 2);

/** 404 页标记 noindex，避免被搜索引擎收录 */
function yimai_robots_404(array $robots): array
{
    if (is_404()) {
        $robots['noindex'] = true;
        $robots['follow'] = true;
    }
    return $robots;
}
add_filter('wp_robots', 'yimai_robots_404');

/**
 * 全站主题预设（后台「全站主题」可一键切换）。
 * 每套完整定义 11 个前台颜色变量：pearl 页面底色 / linen 区块底色 / oat 描边 /
 * clay 强调色 / moss 辅助色 / forest 深色块与标题 / ink 正文 / sage rose stone smoke 点缀。
 */
function yimai_site_theme_presets(): array
{
    return [
        'ebony-ivory' => [
            'name' => '黑檀米白',
            'desc' => '默认 · 现行配色',
            'sw' => ['#F9F6EE', '#EEEAE2', '#8E7660', '#11110F'],
            'colors' => [],
        ],
        'celadon' => [
            'name' => '青瓷苔绿',
            'desc' => '清淡东方 · 修复疗愈感',
            'sw' => ['#F6F9F4', '#E9F0E6', '#4C7A62', '#1A291F'],
            'colors' => [
                'pearl' => '#F6F9F4', 'linen' => '#E9F0E6', 'oat' => '#C7D5C2',
                'clay' => '#4C7A62', 'moss' => '#5E7A64', 'forest' => '#1A291F',
                'ink' => '#1A231C', 'sage' => '#9DB4A0', 'rose' => '#BFA398',
                'stone' => '#AEBFB0', 'smoke' => '#72837A',
            ],
        ],
        'terracotta' => [
            'name' => '陶土赤橘',
            'desc' => '赤陶暖调 · 活力温暖',
            'sw' => ['#FBF5EE', '#F4E8DA', '#B05A35', '#2C1F17'],
            'colors' => [
                'pearl' => '#FBF5EE', 'linen' => '#F4E8DA', 'oat' => '#E1CCB6',
                'clay' => '#B05A35', 'moss' => '#8A6A4F', 'forest' => '#2C1F17',
                'ink' => '#251A12', 'sage' => '#BCA88F', 'rose' => '#C98D6B',
                'stone' => '#CBB49B', 'smoke' => '#8C7761',
            ],
        ],
        'dusk' => [
            'name' => '黛蓝雾霭',
            'desc' => '沉静蓝灰 · 呼吸感',
            'sw' => ['#F5F7FA', '#E8EDF4', '#4A6591', '#1C2534'],
            'colors' => [
                'pearl' => '#F5F7FA', 'linen' => '#E8EDF4', 'oat' => '#C5CFDC',
                'clay' => '#4A6591', 'moss' => '#5D6E84', 'forest' => '#1C2534',
                'ink' => '#1A2130', 'sage' => '#A3AFC1', 'rose' => '#A79FB5',
                'stone' => '#B2BCCA', 'smoke' => '#76819A',
            ],
        ],
        'blush' => [
            'name' => '玫瑰暖沙',
            'desc' => '柔和玫瑰 · 温柔气质',
            'sw' => ['#FBF4F1', '#F4E6E0', '#A9605A', '#2D1E1A'],
            'colors' => [
                'pearl' => '#FBF4F1', 'linen' => '#F4E6E0', 'oat' => '#E3CBC2',
                'clay' => '#A9605A', 'moss' => '#7D6157', 'forest' => '#2D1E1A',
                'ink' => '#271B17', 'sage' => '#C0A89E', 'rose' => '#C98A82',
                'stone' => '#CCB3AB', 'smoke' => '#8C736A',
            ],
        ],
    ];
}

function yimai_theme_vars(): array
{
    $config = yimai_config();
    $themes = [
        'linen' => '#EEEAE2', 'oat' => '#CFC7B8', 'clay' => '#8E7660',
        'moss' => '#6A6B61', 'forest' => '#11110F', 'sage' => '#A9A495',
        'rose' => '#B69B8B', 'ink' => '#151511', 'pearl' => '#F9F6EE',
        'stone' => '#BAB1A2', 'smoke' => '#77736A',
    ];
    $id = $config['site']['theme'] ?? 'ebony-ivory';
    if ($id === 'custom' && !empty($config['site']['customTheme'])) {
        return array_merge($themes, $config['site']['customTheme']);
    }
    $presets = yimai_site_theme_presets();
    if (isset($presets[$id]['colors']) && $presets[$id]['colors'] !== []) {
        return array_merge($themes, $presets[$id]['colors']);
    }
    // 主题在转换时已固定为 ebony-ivory（黑檀米白），可在后台「全站主题」切换配色。
    return $themes;
}

function yimai_hex_to_rgb(string $hex): string
{
    $value = ltrim($hex, '#');
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $value)) {
        return '21 21 17';
    }
    return hexdec(substr($value, 0, 2)) . ' ' . hexdec(substr($value, 2, 2)) . ' ' . hexdec(substr($value, 4, 2));
}

function yimai_image_url(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return YIMAI_THEME_URI . '/assets/css/placeholder.svg';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        // 后台显式选择的图床 / 外链地址，直接使用（加载失败由 app.js 按 footer 兜底映射换回本地）
        return $path;
    }
    return yimai_local_image_url($path);
}

/* -------------------------------------------------------------------------
 * 首页「最新文章」（预约区块之上）：卡片数据
 * ---------------------------------------------------------------------- */

/**
 * 卡片摘要：GSO 生成的 SEO 摘要 → 手写摘要 → 正文，按「字符」截断。
 * 不用 wp_trim_words：它按空格数词，中文整段没有空格会被当成 1 个词，截不住长度。
 */
function yimai_post_card_excerpt(int $postId, int $limit = 46): string
{
    $text = trim((string) get_post_meta($postId, 'yimai_gso_excerpt', true));
    if ($text === '') {
        $text = trim((string) get_the_excerpt($postId));
    }
    if ($text === '') {
        $post = get_post($postId);
        $text = $post instanceof WP_Post ? (string) $post->post_content : '';
    }
    $text = trim((string) preg_replace('/\s+/u', ' ', wp_strip_all_tags($text)));
    if ($text === '') {
        return '';
    }
    return mb_strlen($text) > $limit ? mb_substr($text, 0, $limit) . '…' : $text;
}

/**
 * 卡片图片：特色图 → 正文第一张图 → 主题占位图（占位图的 viewBox 就是 1200×900）。
 * 正文图只有在能解析回附件时才拿得到真实宽高；拿不到就统一退回占位图——
 * 宁可一组卡片都用占位，也不让某一张用没声明的尺寸把卡片高度带歪。
 *
 * @return array{url:string,width:int,height:int}
 */
function yimai_post_card_image(int $postId): array
{
    $placeholder = ['url' => yimai_image_url(''), 'width' => 1200, 'height' => 900];

    $thumbId = (int) get_post_thumbnail_id($postId);
    if ($thumbId > 0) {
        $size = wp_get_attachment_image_src($thumbId, 'medium_large');
        if (is_array($size) && !empty($size[0])) {
            return ['url' => (string) $size[0], 'width' => (int) ($size[1] ?? 0), 'height' => (int) ($size[2] ?? 0)];
        }
    }

    $post = get_post($postId);
    $content = $post instanceof WP_Post ? (string) $post->post_content : '';
    if ($content !== '' && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
        $attachmentId = function_exists('attachment_url_to_postid') ? (int) attachment_url_to_postid((string) $matches[1]) : 0;
        if ($attachmentId > 0) {
            $size = wp_get_attachment_image_src($attachmentId, 'medium_large');
            if (is_array($size) && !empty($size[0])) {
                return ['url' => (string) $size[0], 'width' => (int) ($size[1] ?? 0), 'height' => (int) ($size[2] ?? 0)];
            }
        }
    }
    return $placeholder;
}

/**
 * 首页最新文章（默认 3 篇）：返回渲染所需的扁平字段，模板里不再碰 WP 循环。
 * 置顶文章不插队（ignore_sticky_posts），首页要的是「最近发布」而不是编辑排序。
 *
 * @return array<int, array{id:int,title:string,url:string,excerpt:string,date:string,date_iso:string,image:string,image_width:int,image_height:int,alt:string}>
 */
function yimai_latest_posts(int $limit = 3): array
{
    $items = [];
    if (!class_exists('WP_Query')) {
        return $items;
    }
    $query = new WP_Query([
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => max(1, $limit),
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true, // 首页不分页，省掉一次 COUNT(*)
    ]);
    if (!$query->have_posts()) {
        return $items;
    }
    while ($query->have_posts()) {
        $query->the_post();
        $postId = (int) get_the_ID();
        if ($postId <= 0) {
            continue;
        }
        $title = trim(wp_strip_all_tags((string) get_the_title($postId)));
        $image = yimai_post_card_image($postId);
        $items[] = [
            'id'           => $postId,
            'title'        => $title,
            'url'          => (string) get_permalink($postId),
            'excerpt'      => yimai_post_card_excerpt($postId),
            'date'         => (string) get_the_date('Y年n月j日', $postId),
            'date_iso'     => (string) get_the_date('c', $postId),
            'image'        => $image['url'],
            'image_width'  => $image['width'],
            'image_height' => $image['height'],
            'alt'          => $title,
        ];
    }
    wp_reset_postdata();
    return $items;
}

/* -------------------------------------------------------------------------
 * 活动公告（后台「活动公告」面板维护；首页弹窗 + 预约页活动条）
 * ---------------------------------------------------------------------- */

/**
 * 当前生效的活动：取列表中最后一条「启用中」且在起止日期内的活动。
 * 多条同时启用时，靠后的（后添加的）优先展示；没有则返回空数组。
 */
function yimai_active_announcement(): array
{
    $ann = yimai_config()['announcements'] ?? [];
    $items = is_array($ann['items'] ?? null) ? $ann['items'] : [];
    $now = current_time('timestamp');
    $picked = [];
    foreach ($items as $item) {
        if (!is_array($item) || empty($item['active'])) {
            continue;
        }
        if (trim((string) ($item['title'] ?? '')) === '' && trim((string) ($item['content'] ?? '')) === '') {
            continue;
        }
        $start = trim((string) ($item['start'] ?? ''));
        $end = trim((string) ($item['end'] ?? ''));
        if ($start !== '' && $now < (strtotime($start . ' 00:00:00') ?: 0)) {
            continue;
        }
        if ($end !== '' && $now > (strtotime($end . ' 23:59:59') ?: PHP_INT_MAX)) {
            continue;
        }
        $picked = $item;
    }
    return $picked;
}

/** 公告跳转链接：留空返回空；完整网址原样；否则按站点内路径处理 */
function yimai_notice_link(array $item): string
{
    $link = trim((string) ($item['link'] ?? ''));
    if ($link === '') {
        return '';
    }
    if (str_starts_with($link, 'http://') || str_starts_with($link, 'https://')) {
        return $link;
    }
    return home_url('/' . ltrim($link, '/'));
}

function yimai_nav_items(): array
{
    $config = yimai_config();
    return $config['nav_items'] ?? [];
}

function yimai_studios(): array
{
    $config = yimai_config();
    return $config['studios'] ?? [];
}

function yimai_memberships(): array
{
    $config = yimai_config();
    return $config['memberships'] ?? [];
}

function yimai_faqs(): array
{
    $config = yimai_config();
    return $config['faqs'] ?? [];
}

/**
 * 门店电话 → tel: 拨号号码（模板里写成 href="tel:+86<?php echo … ?>"）。
 * 去除非数字字符；座机去掉国内长途前缀 0；号码不可用时返回空串（模板据此不渲染链接）。
 */
function yimai_tel_number(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (!is_string($digits) || $digits === '') {
        return '';
    }
    // 已经是国际格式（86574… / 8613…）时先剥掉 86，避免拼出 +8686…
    if (str_starts_with($digits, '86') && strlen($digits) > 11) {
        $digits = substr($digits, 2);
    }
    if (str_starts_with($digits, '0')) {
        $digits = substr($digits, 1);
    }
    return $digits;
}

/**
 * 营业时间（如 'Mo-Su 09:00-21:30'，可传数组）→ schema.org openingHoursSpecification。
 * 只解析认得的写法，认不出就跳过：Schema 里宁缺勿错。
 */
function yimai_opening_hours_spec(mixed $hours): array
{
    $days = [
        'mo' => 'Monday', 'tu' => 'Tuesday', 'we' => 'Wednesday', 'th' => 'Thursday',
        'fr' => 'Friday', 'sa' => 'Saturday', 'su' => 'Sunday',
    ];
    $order = array_values($days);
    if (is_string($hours)) {
        $hours = [$hours];
    }
    if (!is_array($hours)) {
        return [];
    }
    $specs = [];
    foreach ($hours as $line) {
        if (!is_string($line) || trim($line) === '') {
            continue;
        }
        // 一行可写多段（逗号分隔）；支持 'Mo-Su 09:00-21:30' 与单日 'Mo 10:00-22:00'
        foreach (explode(',', $line) as $chunk) {
            if (!preg_match('/^\s*([A-Za-z]{2})(?:\s*-\s*([A-Za-z]{2}))?\s+([01]?\d|2[0-3]):([0-5]\d)\s*-\s*([01]?\d|2[0-3]):([0-5]\d)\s*$/', $chunk, $m)) {
                continue;
            }
            $from = $days[strtolower($m[1])] ?? '';
            $to = $m[2] !== '' ? ($days[strtolower($m[2])] ?? '') : $from;
            if ($from === '' || $to === '') {
                continue;
            }
            $start = array_search($from, $order, true);
            $end = array_search($to, $order, true);
            if (!is_int($start) || !is_int($end) || $end < $start) {
                continue;
            }
            $specs[] = [
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => array_slice($order, $start, $end - $start + 1),
                'opens' => $m[3] . ':' . $m[4],
                'closes' => $m[5] . ':' . $m[6],
            ];
        }
    }
    return $specs;
}

/* -------------------------------------------------------------------------
 * 预约表单 AJAX 处理（对应原站 actions/booking-submit.php）
 * 前端 app.js 会把表单提交到 admin-ajax.php。
 * ---------------------------------------------------------------------- */
function yimai_handle_booking(): void
{
    check_ajax_referer('yimai_booking', 'nonce');

    if (!empty($_POST['website'])) {
        wp_send_json_success(['message' => 'ok']);
    }

    $name    = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $phone   = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
    $studio  = sanitize_text_field(wp_unslash($_POST['studio'] ?? ''));
    $interest = sanitize_text_field(wp_unslash($_POST['interest'] ?? ''));
    $message = sanitize_textarea_field(wp_unslash($_POST['message'] ?? ''));

    if ($name === '' || $phone === '') {
        wp_send_json_error(['message' => '姓名和手机不能为空'], 400);
    }
    if (!preg_match('/^1[3-9]\d{9}$|^0\d{2,3}-?\d{7,8}$/', $phone)) {
        wp_send_json_error(['message' => '请填写正确的手机号或门店电话'], 400);
    }
    if (mb_strlen($message) > 500) {
        wp_send_json_error(['message' => '备注不能超过 500 字'], 400);
    }

    $webhook = yimai_wecom_webhook();

    // 无论是否有 webhook，都先落库留存：否则未配置 webhook 时，
    // 访客看到「已提交」但数据被直接丢弃，预约意向无声流失。
    $entry = [
        'time'     => current_time('mysql'),
        'name'     => $name,
        'phone'    => $phone,
        'studio'   => $studio,
        'interest' => $interest,
        'message'  => $message,
        'ip'       => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
        'notified' => false,
    ];
    yimai_booking_store($entry);

    if ($webhook !== '') {
        $payload = [
            'name' => $name,
            'phone' => $phone,
            'studio' => $studio,
            'interest' => $interest,
            'message' => $message,
        ];
        $content = "一麦官网预约意向\n姓名：{$payload['name']}\n手机：{$payload['phone']}\n门店：{$payload['studio']}\n方向：{$payload['interest']}\n备注：{$payload['message']}";
        $resp = wp_remote_post($webhook, [
            'timeout' => 15,
            'body' => wp_json_encode(['msgtype' => 'text', 'text' => ['content' => $content]]),
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
        ]);
        if (is_wp_error($resp)) {
            wp_send_json_error(['message' => '提交失败，请稍后再试或电话联系门店。'], 502);
        }
        $body = wp_remote_retrieve_body($resp);
        $json = json_decode($body, true);
        if (empty($json['errcode'])) {
            wp_send_json_success(['message' => '已提交，我们会尽快联系你。']);
        }
        wp_send_json_error(['message' => '提交失败，请稍后再试或电话联系门店。'], 502);
    }

    // 未配置 webhook：预约已落库（后台「预约记录」可查看），不丢数据
    wp_send_json_success(['message' => '已提交，我们会尽快联系你。']);
}

/** 预约记录最多保留条数 */
const YIMAI_BOOKING_MAX = 200;

/** 写入一条预约记录（wp_options，无需自建表；保留最近 200 条） */
function yimai_booking_store(array $entry): void
{
    $list = get_option('yimai_booking_entries', []);
    $list = is_array($list) ? $list : [];
    array_unshift($list, $entry);
    if (count($list) > YIMAI_BOOKING_MAX) {
        $list = array_slice($list, 0, YIMAI_BOOKING_MAX);
    }
    update_option('yimai_booking_entries', $list, false);
}

/** 读取预约记录（后台展示用，最新在前） */
function yimai_booking_entries(): array
{
    $list = get_option('yimai_booking_entries', []);
    return is_array($list) ? $list : [];
}
add_action('wp_ajax_nopriv_yimai_booking', 'yimai_handle_booking');
add_action('wp_ajax_yimai_booking', 'yimai_handle_booking');


/* -------------------------------------------------------------------------
 * 网站内容后台路由（/admin）
 * 移植自旧站 yimaiyoga.com admin，配置存 wp_options（yimai_site_config）
 * ---------------------------------------------------------------------- */
function yimai_admin_route(): void
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $path = rtrim((string) $path, '/') ?: '/';

    if ($path === '/admin' || str_starts_with($path, '/admin/')) {
        require YIMAI_THEME_DIR . '/admin/index.php';
        exit;
    }
}
add_action('template_redirect', 'yimai_admin_route', 1);
add_action('init', 'yimai_admin_route', 1);

/* -------------------------------------------------------------------------
 * WP 后台集成：内容后台同时挂到 /wp-admin → 「一麦官网」菜单
 * （admin-ajax 接口用 manage_options + nonce；独立 /admin 入口保留作兜底）
 * ---------------------------------------------------------------------- */
require YIMAI_THEME_DIR . '/inc/wp-admin-integration.php';
