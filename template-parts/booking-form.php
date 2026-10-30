<?php
/**
 * 预约表单模板片段：对应原站 views/partials/booking-form.php
 * 前端 app.js 会拦截提交并 POST 到 admin-ajax.php（见 functions.php yimai_handle_booking）。
 *
 * 无障碍：每个控件都有真正关联的 <label>（.sr-only 视觉隐藏）；
 * 门店 / 体验方向选项来自 yimai_config()（后台可维护），缺配置时回退到默认列表。
 *
 * @package yimaiyoga
 */

$bookingConfig = yimai_config()['booking'] ?? [];

// 门店：值用门店名，保持提交字段名 studio 与后台处理逻辑不变
$studioOptions = [];
foreach (yimai_studios() as $studio) {
    $studioName = trim((string) ($studio['name'] ?? ''));
    if ($studioName !== '') {
        $studioOptions[] = $studioName;
    }
}
if ($studioOptions === []) {
    $studioOptions = ['东部店', '绿地店'];
}

// 门店页 CTA 带来的 ?studio=<slug>（east / greenland）：命中门店注册表才预选。
// 预选值仍然是「门店名」，提交字段名与后台处理逻辑都不变；也兼容直接传店名的旧链接。
$studioPreset = '';
$studioParam = isset($_GET['studio']) && is_string($_GET['studio'])
    ? mb_substr(trim(sanitize_text_field(wp_unslash($_GET['studio']))), 0, 40)
    : '';
if ($studioParam !== '') {
    foreach (yimai_studio_page_registry() as $studioEntry) {
        if ($studioEntry['slug'] === $studioParam) {
            $studioPreset = (string) $studioEntry['name'];
            break;
        }
    }
    if ($studioPreset === '' && in_array($studioParam, $studioOptions, true)) {
        $studioPreset = $studioParam;
    }
    // 门店已从配置里删掉时不留一个选不中的预选值
    if (!in_array($studioPreset, $studioOptions, true)) {
        $studioPreset = '';
    }
}

// 体验方向：优先读配置 booking.interests，未配置时回退到默认 5 项
$interestOptions = $bookingConfig['interests'] ?? null;
$interestOptions = is_array($interestOptions) ? array_values(array_filter(array_map('strval', $interestOptions), 'strlen')) : [];
if ($interestOptions === []) {
    $interestOptions = ['精品团课', '私教小班', '定制私教', '体态评估', 'RYT200教培'];
}

// 课程卡「约这节课」带来的 ?interest=<课程主题>：只有命中白名单才预选，避免脏参数被当成选项。
// 白名单 = 配置里的 booking.interests + courseThemes 标题（课程卡就是拿标题当参数的）。
$interestAllowed = $interestOptions;
foreach ((array) (yimai_config()['courseThemes'] ?? []) as $courseTheme) {
    $themeTitle = is_array($courseTheme) ? trim((string) ($courseTheme['title'] ?? '')) : '';
    if ($themeTitle !== '') {
        $interestAllowed[] = $themeTitle;
    }
}
$interestPreset = isset($_GET['interest']) && is_string($_GET['interest'])
    ? mb_substr(trim(sanitize_text_field(wp_unslash($_GET['interest']))), 0, 40)
    : '';
if ($interestPreset !== '' && in_array($interestPreset, $interestAllowed, true)) {
    if (!in_array($interestPreset, $interestOptions, true)) {
        array_unshift($interestOptions, $interestPreset); // 课程主题不在下拉里时补一项
    }
} else {
    $interestPreset = ''; // 不命中白名单就忽略，保持默认选中
}
?>
<form class="booking-form" data-booking-form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
  <input type="hidden" name="action" value="yimai_booking">
  <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('yimai_booking')); ?>">
  <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off" aria-hidden="true">
  <div class="form-grid two">
    <label class="sr-only" for="booking-name">姓名</label>
    <input id="booking-name" name="name" required maxlength="30" placeholder="姓名" autocomplete="name">
    <label class="sr-only" for="booking-phone">手机</label>
    <input id="booking-phone" name="phone" type="tel" required maxlength="20" placeholder="手机" autocomplete="tel">
  </div>
  <div class="form-grid two">
    <label class="sr-only" for="booking-studio">选择门店</label>
    <select id="booking-studio" name="studio" required>
      <option value="">选择门店</option>
      <?php foreach ($studioOptions as $studioName): ?>
        <option value="<?php echo esc_attr($studioName); ?>"<?php echo $studioPreset === $studioName ? ' selected' : ''; ?>><?php echo esc_html($studioName); ?></option>
      <?php endforeach; ?>
    </select>
    <label class="sr-only" for="booking-interest">体验方向</label>
    <select id="booking-interest" name="interest" required>
      <option value="">体验方向</option>
      <?php foreach ($interestOptions as $interest): ?>
        <option value="<?php echo esc_attr($interest); ?>"<?php echo $interestPreset === $interest ? ' selected' : ''; ?>><?php echo esc_html($interest); ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <label class="sr-only" for="booking-message">备注</label>
  <textarea id="booking-message" name="message" maxlength="500" placeholder="你希望改善的状态：体态、肩颈、塑形、压力、睡眠..."></textarea>
  <button class="button primary" type="submit">提交预约意向</button>
  <p class="form-message" data-form-message aria-live="polite"></p>
</form>
