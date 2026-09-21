<?php
/**
 * 师资页模板：对应原站 views/pages/instructors.php
 *
 * 师资 Tab 遵循 WAI-ARIA tabs 模式：tablist / tab / tabpanel + aria-selected /
 * aria-controls / aria-labelledby + roving tabindex，键盘方向键由 app.js 处理。
 *
 * @package yimaiyoga
 */

get_header();

$config   = yimai_config();
$copy     = $config['copy']['instructors'] ?? [];
$teachers = $config['instructors'] ?? [];
?>
<section class="page-hero reveal">
  <div>
    <p class="eyebrow"><?php echo esc_html($copy['eyebrow'] ?? ''); ?></p>
    <h1><?php echo nl2br(esc_html($copy['title'] ?? '')); ?></h1>
  </div>
  <?php if (!empty($copy['sideDescription'])): ?>
    <aside>
      <p class="eyebrow"><?php echo esc_html($copy['sideEyebrow'] ?? ''); ?></p>
      <p><?php echo esc_html($copy['sideDescription']); ?></p>
    </aside>
  <?php endif; ?>
</section>
<section class="instructor-layout page-block" data-instructors>
  <div class="teacher-tabs" role="tablist" aria-label="师资列表">
    <?php foreach ($teachers as $index => $teacher): ?>
      <?php
      $tabIndex   = (int) $index;
      $isActive   = ($index === 0);
      $tabId      = 'teacher-tab-' . $tabIndex;
      $panelId    = 'teacher-panel-' . $tabIndex;
      $tabClass   = $isActive ? 'active' : '';
      $tabState   = $isActive ? 'true' : 'false';
      $tabStop    = $isActive ? '0' : '-1';
      ?>
      <button type="button" id="<?php echo esc_attr($tabId); ?>" role="tab" class="<?php echo esc_attr($tabClass); ?>" aria-selected="<?php echo esc_attr($tabState); ?>" aria-controls="<?php echo esc_attr($panelId); ?>" tabindex="<?php echo esc_attr($tabStop); ?>" data-teacher-tab="<?php echo esc_attr((string) $tabIndex); ?>"><span><?php echo esc_html($teacher['name'] ?? ''); ?></span><em><?php echo esc_html($teacher['years'] ?? ''); ?></em></button>
    <?php endforeach; ?>
  </div>
  <div class="teacher-details">
    <?php foreach ($teachers as $index => $teacher): ?>
      <?php
      $panelIndex = (int) $index;
      $isActive   = ($index === 0);
      $tabId      = 'teacher-tab-' . $panelIndex;
      $panelId    = 'teacher-panel-' . $panelIndex;
      $panelClass = trim('teacher-detail ' . ($isActive ? 'active' : ''));
      ?>
      <article id="<?php echo esc_attr($panelId); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr($tabId); ?>" class="<?php echo esc_attr($panelClass); ?>" data-teacher-panel="<?php echo esc_attr((string) $panelIndex); ?>"<?php echo $isActive ? '' : ' hidden'; ?>>
        <img src="<?php echo esc_url(yimai_image_url($teacher['image'] ?? '')); ?>" alt="<?php echo esc_attr($teacher['name'] ?? ''); ?>老师" loading="lazy">
        <div>
          <p class="eyebrow">Teacher Profile</p>
          <h2><?php echo esc_html($teacher['name'] ?? ''); ?></h2>
          <p class="lead"><?php echo esc_html($teacher['focus'] ?? ''); ?></p>
          <p><?php echo esc_html($teacher['summary'] ?? ''); ?></p>
          <div class="detail-columns">
            <section><h3>资质与学习</h3><?php foreach ($teacher['credentials'] ?? [] as $item): ?><p><?php echo esc_html($item); ?></p><?php endforeach; ?></section>
            <section><h3>擅长方向</h3><div class="tags"><?php foreach ($teacher['specialties'] ?? [] as $item): ?><span><?php echo esc_html($item); ?></span><?php endforeach; ?></div></section>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php
get_footer();
