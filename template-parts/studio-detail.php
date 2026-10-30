<?php
/**
 * 门店详情页（一店一页）渲染片段
 *
 * 由 page-east.php（/studio/east）与 page-greenland.php（/studio/greenland）通过
 * get_template_part() 的第三参数传入 key 调用。
 *
 * 固定 8 块：地址 / 电话 / 地铁停车与到店路线 / 该店课程与教练 / 营业时间 /
 * FAQ / 地图占位 / 预约 CTA。数据只有两个来源：
 *   1. inc/site-data.php 的 studios（地址、电话、面积、坐标、营业时间，后台可维护）
 *   2. 同文件的 courseThemes / instructors（instructors.role 里写了「东部店/绿地店」）
 * 「营业时间」「地铁/停车/到店动线」没有核对过的值就整块不渲染——门店页最贵的错误
 * 是让人按错的时间白跑一趟。
 *
 * @package yimaiyoga
 */

$yimaiStudioKey = (string) ($args['key'] ?? '');
$yimaiStudioData = yimai_studio_page_data($yimaiStudioKey);
if ($yimaiStudioData === []) {
    // 门店配置被后台清空时（studios 列表删空）不留一个只有页头页脚的空白页
    return;
}

$studio   = $yimaiStudioData['studio'];
$entry    = $yimaiStudioData['entry'];
$config   = yimai_config();
$images   = is_array($config['images'] ?? null) ? $config['images'] : [];
$transit  = yimai_studio_page_transit($yimaiStudioKey);
$courses  = yimai_studio_page_courses();
$teachers = yimai_studio_page_teachers($studio);
$faqs     = yimai_studio_page_faqs($studio, $entry, $transit);
// 周课表（xlsx 一次性转成的静态数据）：过滤掉清洁日/内部训练后为空则整块不渲染
$timetable = yimai_studio_timetable($yimaiStudioKey);
// 周期只出现一次（表头只留周一…周日）：week 来自数据层，日期不重复渲染 7 次
$ttWeek = trim((string) ($timetable['week'] ?? ''));

$studioName  = trim((string) ($studio['name'] ?? ''));
$siteName    = trim((string) ($config['site']['name'] ?? '一麦瑜伽'));
$district    = yimai_studio_district($studio);
$address     = trim((string) ($studio['address'] ?? ''));
$area        = trim((string) ($studio['area'] ?? ''));
$phone       = trim((string) ($studio['phone'] ?? ''));
$telNumber   = yimai_tel_number($phone);
$hoursText   = yimai_opening_hours_text($studio['openingHours'] ?? []);
$h1          = yimai_studio_page_h1($studio, $entry);
// H1 拆成「位置+业务」与「（店名）」两段：店名整段不断行，避免大字号下把（东部店）切成两半
$h1Name      = $studioName !== '' ? '（' . $studioName . '）' : '';
$h1Base      = ($h1Name !== '' && str_ends_with($h1, $h1Name)) ? substr($h1, 0, -strlen($h1Name)) : $h1;
$bookingUrl  = yimai_studio_page_booking_url((string) $entry['slug']);
$studioImage = (string) ($images['studioImages'][$yimaiStudioData['index']] ?? ($images['studioHero'] ?? ''));
$mapQuery    = $address !== '' ? 'https://uri.amap.com/search?keyword=' . rawurlencode($address) : '';
// 小标题：店名 · 城市+区 · 面积，缺项自动跳过（不留「· ·」这种空段）
$eyebrowParts = array_values(array_filter(
    [$studioName, $district !== '' ? '宁波' . $district : '', $area],
    static fn (string $part): bool => $part !== ''
));
$eyebrow = implode(' · ', $eyebrowParts);
?>
<nav class="breadcrumb" aria-label="面包屑">
  <a href="<?php echo esc_url(home_url('/')); ?>">首页</a>
  <span class="breadcrumb-sep" aria-hidden="true">/</span>
  <a href="<?php echo esc_url(home_url('/studio')); ?>">空间与门店</a>
  <span class="breadcrumb-sep" aria-hidden="true">/</span>
  <span class="breadcrumb-current" aria-current="page"><?php echo esc_html(trim(($district !== '' ? '宁波' . $district . ' · ' : '') . $studioName)); ?></span>
</nav>

<section class="page-hero reveal">
  <div>
    <p class="eyebrow"><?php echo esc_html($eyebrow); ?></p>
    <h1 class="store-h1"><?php echo esc_html($h1Base); ?><?php if ($h1Name !== '' && $h1Base !== $h1): ?><span class="store-h1-name"><?php echo esc_html($h1Name); ?></span><?php endif; ?></h1>
    <?php if ($address !== ''): ?>
      <p class="lead"><?php echo esc_html($address); ?></p>
    <?php endif; ?>
  </div>
  <aside>
    <p class="eyebrow">Address / Phone</p>
    <?php if ($phone !== ''): ?>
      <p>
        <?php if ($telNumber !== ''): ?>
          <a class="tel-link" href="tel:+86<?php echo esc_attr($telNumber); ?>"><?php echo esc_html($phone); ?></a>
        <?php else: ?>
          <?php echo esc_html($phone); ?>
        <?php endif; ?>
      </p>
    <?php endif; ?>
    <?php if ($hoursText !== ''): ?>
      <p><?php echo esc_html($hoursText); ?></p>
    <?php endif; ?>
    <p><a class="button primary" href="<?php echo esc_url($bookingUrl); ?>">预约到店体验</a></p>
  </aside>
</section>

<?php if ($studioImage !== '' && $studioImage !== ($images['studioHero'] ?? '')): ?>
  <div class="post-cover">
    <img src="<?php echo esc_url(yimai_image_url($studioImage)); ?>" alt="<?php echo esc_attr($siteName . ' · ' . $studioName . '门店实拍'); ?>" loading="lazy">
  </div>
<?php endif; ?>

<section class="contact-grid page-block">
  <div class="store-facts">
    <?php if ($address !== ''): ?>
      <article class="reveal">
        <h2>地址</h2>
        <p><?php echo esc_html($address); ?></p>
      </article>
    <?php endif; ?>

    <?php if ($phone !== ''): ?>
      <article class="reveal">
        <h2>电话</h2>
        <p>
          <?php if ($telNumber !== ''): ?>
            <a class="tel-link" href="tel:+86<?php echo esc_attr($telNumber); ?>"><?php echo esc_html($phone); ?></a>
          <?php else: ?>
            <?php echo esc_html($phone); ?>
          <?php endif; ?>
        </p>
        <p>课程咨询、到店路线与体验预约都可以直接打这个电话。</p>
      </article>
    <?php endif; ?>

    <?php if ($transit !== []): ?>
      <article class="reveal">
        <h2>地铁 / 停车 / 到店路线</h2>
        <div class="detail-columns">
          <?php foreach ($transit as $row): ?>
            <section>
              <h3><?php echo esc_html((string) ($row['label'] ?? '')); ?></h3>
              <p><?php echo esc_html((string) ($row['value'] ?? '')); ?></p>
            </section>
          <?php endforeach; ?>
        </div>
      </article>
    <?php endif; ?>

    <?php if ($hoursText !== ''): ?>
      <article class="reveal">
        <h2>营业时间</h2>
        <p><?php echo esc_html($hoursText); ?></p>
      </article>
    <?php endif; ?>
  </div>

  <?php // 地图占位：坐标与门牌未确认前不放嵌入 iframe（假坐标比没有更糟），先用地址检索链接占位 ?>
  <div class="store-map reveal">
    <p class="eyebrow">Map · 到店路线</p>
    <h2><?php echo esc_html($studioName . '在哪里'); ?></h2>
    <?php if ($address !== ''): ?>
      <p><?php echo esc_html($address); ?></p>
    <?php endif; ?>
    <?php if ($mapQuery !== ''): ?>
      <p><a class="text-link" href="<?php echo esc_url($mapQuery); ?>" target="_blank" rel="nofollow noopener noreferrer">在地图里搜索这个地址 →</a></p>
    <?php endif; ?>
  </div>
</section>

<?php // 该店课程：三张卡正好一行（.card-grid.three），卡片主题与 H1 的业务词对齐 ?>
<section class="split-section">
  <div class="section-intro reveal">
    <p class="eyebrow">Courses · 该店课程</p>
    <h2>在<?php echo esc_html($studioName); ?>练什么。</h2>
    <p>器械普拉提、空中瑜伽与体态理疗等主题是两店共用的课程体系，具体班次按门店当周课表安排；第一次到店会先做体态评估，再决定从哪个主题开始。</p>
  </div>
  <div class="card-grid three">
    <?php foreach ($courses as $course): ?>
      <article class="course-card reveal">
        <div class="image-frame"><img src="<?php echo esc_url(yimai_image_url((string) ($course['image'] ?? ''))); ?>" alt="<?php echo esc_attr((string) ($course['title'] ?? '')); ?>" loading="lazy"></div>
        <p class="eyebrow"><?php echo esc_html((string) ($course['type'] ?? '')); ?></p>
        <h3><?php echo esc_html((string) ($course['title'] ?? '')); ?></h3>
        <p><?php echo esc_html((string) ($course['effect'] ?? '')); ?></p>
        <small><?php echo esc_html((string) ($course['suited'] ?? '')); ?></small>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<?php // 该店教练：instructors.role 里写了「东部店/绿地店」，两店名单不同，所以单独成块 ?>
<?php if ($teachers !== []): ?>
<section class="split-section teachers-section">
  <div class="section-intro reveal">
    <p class="eyebrow">Teachers · 该店师资</p>
    <h2><?php echo esc_html($studioName); ?>的老师。</h2>
    <p>下文是常驻本店的老师，擅长的方向写在名字下面；跨店带课或临时替课以门店当周课表为准。</p>
  </div>
  <div class="card-grid two">
    <?php foreach ($teachers as $teacher): ?>
      <a class="teacher-card reveal" href="<?php echo esc_url(home_url('/instructors')); ?>">
        <img src="<?php echo esc_url(yimai_image_url((string) ($teacher['image'] ?? ''))); ?>" alt="<?php echo esc_attr((string) ($teacher['name'] ?? '') . '老师'); ?>" loading="lazy">
        <div><h3><?php echo esc_html((string) ($teacher['name'] ?? '')); ?></h3><span><?php echo esc_html((string) ($teacher['years'] ?? '')); ?></span></div>
        <p><?php echo esc_html(implode(' · ', array_filter([(string) ($teacher['role'] ?? ''), (string) ($teacher['focus'] ?? '')], static fn (string $part): bool => $part !== ''))); ?></p>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<?php // 周课表：行=时间段，列=周一到周日；桌面整表，<=700px 横向滚动 + 首列吸附 ?>
<?php if ($timetable['rows'] !== [] && $timetable['days'] !== []): ?>
<section class="page-block tt-section reveal">
  <div class="tt-head">
    <div>
      <p class="eyebrow">Timetable · 周课表</p>
      <h2><?php echo esc_html($studioName . '周课表'); ?></h2>
    </div>
    <p class="tt-note"><?php echo esc_html(($ttWeek !== '' ? '课表适用于 ' . $ttWeek . '，' : '') . '实际排课以门店当周公告为准；每格为课程与授课老师，空格为无课。'); ?></p>
  </div>
  <?php // 手机上表格只在容器内横向滚动：给一句看得见的提示，别让人以为课表被裁掉了 ?>
  <p class="tt-hint">左右滑动查看整周 →</p>
  <div class="tt-scroll" role="region" tabindex="0" aria-label="<?php echo esc_attr($studioName . '周课表，可横向滚动'); ?>">
    <table class="tt-table">
      <caption class="sr-only"><?php echo esc_html($studioName . '课程安排' . ($ttWeek !== '' ? '（适用于 ' . $ttWeek . '）' : '') . '：每行是一个开始时间，每列是周一到周日'); ?></caption>
      <thead>
        <tr>
          <th scope="col" class="tt-time">时间</th>
          <?php foreach ($timetable['days'] as $dayLabel): ?>
            <th scope="col"><?php echo esc_html($dayLabel); ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($timetable['rows'] as $ttRow): ?>
          <?php
          /*
           * 行首的时间：源表里同一行的时段是统一的（都是 60 分钟），所以把「11:15–12:15」
           * 放在行首，格子里不再重复时间，只留课程 / 老师 / 教室+难度三层。
           */
          $ttStart = (string) ($ttRow['start'] ?? '');
          $ttRange = '';
          foreach ($ttRow['slots'] as $ttSlot) {
              if (is_array($ttSlot) && (string) ($ttSlot['time'] ?? '') !== '') {
                  $ttRange = (string) $ttSlot['time'];
                  break;
              }
          }
          $ttEnd = str_contains($ttRange, '~') ? substr($ttRange, (int) strpos($ttRange, '~') + 1) : '';
          ?>
          <tr>
            <th scope="row" class="tt-time">
              <span class="tt-start"><?php echo esc_html($ttStart); ?></span>
              <?php if ($ttEnd !== ''): ?><span class="tt-range">–<?php echo esc_html($ttEnd); ?></span><?php endif; ?>
            </th>
            <?php foreach ($timetable['days'] as $dayIndex => $dayLabel): ?>
              <?php $ttSlot = $ttRow['slots'][$dayIndex] ?? null; ?>
              <?php if (!is_array($ttSlot)): ?>
                <td class="tt-empty" aria-label="无课">—</td>
              <?php else: ?>
                <td class="tt-cell">
                  <?php
                  /*
                   * 课程名拆两段：主干 + 后缀（如「站立平衡」＋「-根基与控制」）。
                   * 桌面显示完整名；<=700px 只显示主干（后缀藏在 .tt-name-extra），
                   * 全名同时挂在 .tt-course 的 title 与 aria-label 上，长按/读屏都还能拿到。
                   */
                  $ttCourseFull = (string) $ttSlot['course'];
                  $ttCourseTrunk = (string) ($ttSlot['trunk'] ?? $ttCourseFull);
                  $ttCourseExtra = (string) ($ttSlot['extra'] ?? '');
                  ?>
                  <span class="tt-course" title="<?php echo esc_attr($ttCourseFull); ?>" aria-label="<?php echo esc_attr($ttCourseFull); ?>"><span class="tt-dot <?php echo esc_attr((string) ($ttSlot['dot'] ?? 'tt-dot--f')); ?>" aria-hidden="true"></span><span class="tt-name"><?php echo esc_html($ttCourseTrunk); ?><?php if ($ttCourseExtra !== ''): ?><span class="tt-name-extra"><?php echo esc_html($ttCourseExtra); ?></span><?php endif; ?></span><?php if (($ttSlot['tag'] ?? '') !== ''): ?><span class="tt-tag"><?php echo esc_html((string) $ttSlot['tag']); ?></span><?php endif; ?></span>
                  <?php if (($ttSlot['teacher'] ?? '') !== ''): ?>
                    <span class="tt-teacher"><?php echo esc_html((string) $ttSlot['teacher']); ?></span>
                  <?php endif; ?>
                  <?php
                  $ttRating = (int) ($ttSlot['rating'] ?? 0);
                  $ttMeta = trim((string) ($ttSlot['room'] ?? '') . ($ttRating > 0 ? ' · ' . str_repeat('★', $ttRating) : ''));
                  ?>
                  <?php if ($ttMeta !== ''): ?>
                    <span class="tt-meta" title="<?php echo esc_attr('难度 ' . $ttRating . '/5'); ?>"><?php echo esc_html($ttMeta); ?></span>
                  <?php endif; ?>
                </td>
              <?php endif; ?>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<section class="page-block">
  <div class="faq-panel reveal">
    <p class="eyebrow muted-light">FAQ · 到店前常见问题</p>
    <h2><?php echo esc_html('到' . $studioName . '之前，你可能会问'); ?></h2>
    <?php foreach ($faqs as $faq): ?>
      <details>
        <summary><?php echo esc_html((string) ($faq['q'] ?? '')); ?></summary>
        <p><?php echo esc_html((string) ($faq['a'] ?? '')); ?></p>
      </details>
    <?php endforeach; ?>
  </div>
</section>

<section class="cta-block reveal">
  <p class="eyebrow muted-light">Booking · <?php echo esc_html($studioName); ?></p>
  <h2>先在<?php echo esc_html($studioName); ?>约一次到店体验。</h2>
  <p>留下手机号，门店会按你的时间安排体验课；也可以先打电话问清班次再决定。</p>
  <p><a class="text-link light" href="<?php echo esc_url($bookingUrl); ?>">预约到店体验</a></p>
</section>
