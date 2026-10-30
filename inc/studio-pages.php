<?php
/**
 * 门店详情页（一店一页）数据层：/studio/east（东部店）· /studio/greenland（绿地店）
 *
 * 为什么单独一份文件、而不是全部塞进 inc/site-data.php 的 studios：
 * 后台「门店」列表编辑器按固定字段（name/area/address/phone/latitude/longitude/
 * postalCode/openingHours）重建整个列表项，任何编辑器里没有输入框的键，**保存一次就会
 * 消失**（v1.9.3 已经因为这个坑补过四个字段）。所以「地铁 / 停车 / 到店动线」这类
 * 后台没有框的文案放在这里，而不是塞进 studios。
 *
 * 另一条原则：留空 = 前端整块不渲染。门店页最贵的错误是让人按错的时间、走错的入口
 * 白跑一趟（与 v1.9.3「营业时间不再编造」同一条原则），所以这里只放核对过的内容，
 * 待门店确认的项见 docs/门店待确认数据清单.md。
 *
 * @package yimaiyoga
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 门店页注册表（唯一入口）。
 *
 * - key：内部标识，同时用于日志 / 测试 / 排查时的检索线索（studio-east / studio-greenland）
 * - slug：URL 末段，/studio/<slug>，也用作 LocalBusiness 的 @id 锚点（/studio/east#store）
 * - name：与 inc/site-data.php studios 里的店名一致；按店名定位门店，后台调整门店顺序
 *         不会把两店的地址电话对调（index 只在店名被改过时兜底）
 * - business：该店 H1 里的业务词，取自本店全职导师的 focus（不写品牌没有的项目）
 */
function yimai_studio_page_registry(): array
{
    return [
        'studio-east' => [
            'slug'     => 'east',
            'name'     => '东部店',
            'index'    => 0,
            'business' => '器械普拉提与空中瑜伽',
        ],
        'studio-greenland' => [
            'slug'     => 'greenland',
            'name'     => '绿地店',
            'index'    => 1,
            'business' => '普拉提大器械与体态调整',
        ],
    ];
}

/**
 * 门店页 key → ['studio' => 门店配置, 'index' => 下标, 'entry' => 注册表项]。
 * 未知 key 或门店配置被清空时返回空数组（调用方据此不渲染半截页面）。
 */
function yimai_studio_page_data(string $key): array
{
    $registry = yimai_studio_page_registry();
    if (!isset($registry[$key])) {
        return [];
    }
    $entry = $registry[$key];
    $studios = yimai_studios();

    // 先按店名找：这是唯一稳定的锚点（后台可增删门店、可调顺序）
    foreach ($studios as $index => $studio) {
        if (is_array($studio) && trim((string) ($studio['name'] ?? '')) === $entry['name']) {
            return ['studio' => $studio, 'index' => (int) $index, 'entry' => $entry];
        }
    }
    // 店名被改成「东部店（杉杉国贸）」这类写法时，退回原下标：至少还是同一家店的地址电话
    $fallback = $studios[$entry['index']] ?? null;
    if (is_array($fallback)) {
        return ['studio' => $fallback, 'index' => (int) $entry['index'], 'entry' => $entry];
    }
    return [];
}

/**
 * 当前请求对应的门店页 key（studio-east / studio-greenland），非门店页返回空串。
 *
 * 判定依据是页面 slug：WP 的模板层级已经按 slug 决定了 /studio/east 用 page-east.php，
 * 这里用同一个 slug 反查，保证「页面渲染」与「Schema 输出」看的是同一家店。
 */
function yimai_current_studio_key(): string
{
    if (!is_page()) {
        return '';
    }
    $object = get_queried_object();
    $slug = $object instanceof WP_Post ? (string) $object->post_name : '';
    if ($slug === '') {
        // 兜底：对象没解析出来时用查询变量（WP 已把它裁剪成末段 slug）
        $pagename = get_query_var('pagename');
        $slug = is_string($pagename) ? (string) $pagename : '';
    }
    $slug = trim($slug);
    if ($slug === '') {
        return '';
    }
    foreach (yimai_studio_page_registry() as $key => $entry) {
        if ($entry['slug'] === $slug) {
            return (string) $key;
        }
    }
    return '';
}

/** 门店所在行政区，从地址里取（避免地址与 H1 两处各写一遍）：'宁波市鄞州区…' → '鄞州' */
function yimai_studio_district(array $studio): string
{
    $address = trim((string) ($studio['address'] ?? ''));
    if ($address !== '' && preg_match('/宁波市([\x{4e00}-\x{9fa5}]{2,3})区/u', $address, $matches)) {
        return (string) $matches[1];
    }
    return '';
}

/**
 * 门店页 H1：宁波 + 区 + 业务 + 店名 四要素齐全（同城位置词靠这一行吃）。
 * 例：宁波鄞州 · 器械普拉提与空中瑜伽（东部店）
 * 地址里取不到区时降级为「宁波 · 业务（店名）」，仍然保留城市、业务与店名。
 */
function yimai_studio_page_h1(array $studio, array $entry): string
{
    $name = trim((string) ($studio['name'] ?? ($entry['name'] ?? '')));
    $district = yimai_studio_district($studio);
    $business = trim((string) ($entry['business'] ?? ''));
    $line = '宁波' . $district . ' · ' . $business;
    if ($name !== '') {
        $line .= '（' . $name . '）';
    }
    return $line;
}

/** 门店页预约地址：带上 ?studio=<slug>，门店页来的预约在后台记录里可回溯是哪家店带来的 */
function yimai_studio_page_booking_url(string $slug): string
{
    $url = home_url('/booking');
    return $slug === '' ? $url : $url . '?studio=' . rawurlencode($slug);
}

/**
 * 门店名 → 门店页地址（/studio/east、/studio/greenland）。
 * 页面还没建时返回空串：内置链接不能指向 404（建页步骤见 docs/门店待确认数据清单.md）。
 * 用于 /studio 与 /booking 的门店卡片，让两个门店页有站内入口，而不是孤立页。
 */
function yimai_studio_page_url_by_name(string $name): string
{
    $name = trim($name);
    if ($name === '') {
        return '';
    }
    foreach (yimai_studio_page_registry() as $entry) {
        if ($entry['name'] !== $name) {
            continue;
        }
        $exists = get_page_by_path('studio/' . $entry['slug'], OBJECT, 'page');
        return $exists instanceof WP_Post ? home_url('/studio/' . $entry['slug']) : '';
    }
    return '';
}

/**
 * 「地铁 / 停车 / 到店动线」三项：后台没有输入框，因此放在代码里。
 * 留空 = 该行不渲染，三项都空时整块「到店信息」不出现。
 * 【需 YG 提供】按门店核对后填这里，见 docs/门店待确认数据清单.md。
 */
function yimai_studio_page_transit(string $key): array
{
    $data = [
        'studio-east' => [
            'metro'   => '', // 需 YG 提供：最近地铁站 + 出口 + 步行分钟数
            'parking' => '', // 需 YG 提供：商场停车场入口 / 免费时长 / 计费方式
            'route'   => '', // 需 YG 提供：楼宇入口、电梯位置、是否需要门禁
        ],
        'studio-greenland' => [
            'metro'   => '', // 需 YG 提供
            'parking' => '', // 需 YG 提供
            'route'   => '', // 需 YG 提供
        ],
    ];
    $labels = ['metro' => '地铁', 'parking' => '停车', 'route' => '到店动线'];
    $rows = [];
    foreach ($labels as $field => $label) {
        $value = trim((string) ($data[$key][$field] ?? ''));
        if ($value !== '') {
            $rows[] = ['label' => $label, 'value' => $value];
        }
    }
    return $rows;
}

/**
 * 该店展示的课程主题（默认 3 个，正好填满一行三列）。
 * 取法是「按关键词优先级各取一个」，而不是按配置顺序截前三条：门店页的 H1 与位置词
 * 围绕器械普拉提 / 空中瑜伽，卡片必须对得上，否则会出现「标题写空中瑜伽、卡片里没有」的错位。
 * 命中不足 3 个时按原顺序补足；数据全部来自 inc/site-data.php 的 courseThemes（两店共用一套主题）。
 */
function yimai_studio_page_courses(int $limit = 3): array
{
    $themes = (array) (yimai_config()['courseThemes'] ?? []);
    $keywords = ['普拉提', '空中', '理疗', '体态'];
    $picked = [];
    foreach ($keywords as $keyword) {
        if (count($picked) >= $limit) {
            break;
        }
        foreach ($themes as $theme) {
            if (!is_array($theme) || in_array($theme, $picked, true)) {
                continue;
            }
            $haystack = (string) ($theme['title'] ?? '') . ' ' . (string) ($theme['type'] ?? '');
            if (str_contains($haystack, $keyword)) {
                $picked[] = $theme;
                break;
            }
        }
    }
    // 主题被后台删到不足时按原顺序补足，避免「该店课程」只剩一张卡
    foreach ($themes as $theme) {
        if (count($picked) >= min(3, $limit)) {
            break;
        }
        if (is_array($theme) && !in_array($theme, $picked, true)) {
            $picked[] = $theme;
        }
    }
    return array_slice($picked, 0, $limit);
}

/**
 * 该店教练：instructors 的 role 字段本身就写了「东部店全职导师 / 绿地店全职导师」，
 * 按店名匹配即可，不需要另建一份门店-师资关系表。
 * 店名匹配不到时返回空数组（模板只出「课程」不出师资，不硬凑）。
 */
function yimai_studio_page_teachers(array $studio, int $limit = 3): array
{
    $storeName = trim((string) ($studio['name'] ?? ''));
    if ($storeName === '') {
        return [];
    }
    $matched = [];
    foreach ((array) (yimai_config()['instructors'] ?? []) as $teacher) {
        if (!is_array($teacher)) {
            continue;
        }
        $role = trim((string) ($teacher['role'] ?? ''));
        if ($role !== '' && str_contains($role, $storeName)) {
            $matched[] = $teacher;
        }
    }
    return array_slice($matched, 0, $limit);
}

/**
 * 营业时间 → 人可读文本：'Mo-Su 09:00-21:30' → '周一至周日 09:00-21:30'。
 * 认不出的写法返回空串（宁可这块不渲染，也不把机器格式直接印给客户看）。
 */
function yimai_opening_hours_text(mixed $hours): string
{
    $days = [
        'mo' => '周一', 'tu' => '周二', 'we' => '周三', 'th' => '周四',
        'fr' => '周五', 'sa' => '周六', 'su' => '周日',
    ];
    if (is_string($hours)) {
        $hours = [$hours];
    }
    if (!is_array($hours)) {
        return '';
    }
    $lines = [];
    foreach ($hours as $line) {
        if (!is_string($line) || trim($line) === '') {
            continue;
        }
        foreach (explode(',', $line) as $chunk) {
            if (!preg_match('/^\s*([A-Za-z]{2})(?:\s*-\s*([A-Za-z]{2}))?\s+(([01]?\d|2[0-3]):[0-5]\d)\s*-\s*(([01]?\d|2[0-3]):[0-5]\d)\s*$/', $chunk, $m)) {
                continue;
            }
            $from = $days[strtolower($m[1])] ?? '';
            $to = $m[2] !== '' ? ($days[strtolower($m[2])] ?? '') : $from;
            if ($from === '' || $to === '') {
                continue;
            }
            $label = $from === $to ? $from : $from . '至' . $to;
            $lines[] = $label . ' ' . $m[3] . '-' . $m[5];
        }
    }
    return implode('，', $lines);
}

/**
 * 门店页 FAQ（3–5 条）：只用门店真实数据拼装，缺数据的问答不出现。
 * 顺序按「到店前真正会问的先后」排：怎么预约 → 怎么走 → 零基础能不能练 → 几点开门 → 怎么确认班次。
 * 与前台展示同源（模板与 FAQPage JSON-LD 都调本函数），不会出现「页面上没写、Schema 里有」的标记。
 */
function yimai_studio_page_faqs(array $studio, array $entry, array $transit = []): array
{
    $name = trim((string) ($studio['name'] ?? ($entry['name'] ?? '')));
    $phone = trim((string) ($studio['phone'] ?? ''));
    $address = trim((string) ($studio['address'] ?? ''));
    $faqs = [];

    // 1) 预约方式：门店名与电话都是配置里的真实字段
    $answer = '在本页点「预约到店体验」，或在预约页把门店选为「' . $name . '」提交意向';
    $answer .= $phone !== '' ? '；也可以直接打 ' . $phone . ' 和门店确认时间。' : '。';
    $faqs[] = ['q' => '第一次去' . $name . '，怎么预约？', 'a' => $answer];

    // 2) 到店路线：地址必有；地铁/停车/动线有核对过的数据才写进去
    $routeAnswer = $address !== '' ? $address . '。' : '';
    foreach ($transit as $row) {
        $label = (string) ($row['label'] ?? '');
        $value = (string) ($row['value'] ?? '');
        if ($label !== '' && $value !== '') {
            $routeAnswer .= $label . '：' . $value . '。';
        }
    }
    $routeAnswer .= $phone !== '' ? '入口、电梯与停车如需指引，可致电 ' . $phone . '。' : '';
    $faqs[] = ['q' => $name . '怎么走？', 'a' => $routeAnswer];

    // 3) 零基础：与「新人体验 = 体态评估 + 体验课程 + 练习路径建议」的会员方案同源
    $faqs[] = [
        'q'  => '零基础可以直接上器械普拉提或空中瑜伽吗？',
        'a'  => '可以。精品团课与私教小班都从基础开始，老师会按你的身体状态调整动作难度；第一次到店会先做体态评估，再决定从哪个主题开始。',
    ];

    // 4) 营业时间：只有门店确认过（openingHours 非空）才出这一问
    $hoursText = yimai_opening_hours_text($studio['openingHours'] ?? []);
    if ($hoursText !== '') {
        $faqs[] = ['q' => $name . '的营业时间是几点到几点？', 'a' => $hoursText . '。节假日如有调整，会提前在门店公告。'];
    }

    // 5) 班次确认：与全站 FAQ「团课建议提前一天预约」一致
    $bookingAnswer = '团课建议提前一天预约，私教与老师约好固定时间即可；当周课表可以';
    $bookingAnswer .= $phone !== '' ? '致电 ' . $phone . ' 确认。' : '在预约时说明想练的主题。';
    $faqs[] = ['q' => '当天有哪些课？需要提前多久预约？', 'a' => $bookingAnswer];

    return $faqs;
}
