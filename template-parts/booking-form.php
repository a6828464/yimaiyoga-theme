<?php
/**
 * 预约表单模板片段：对应原站 views/partials/booking-form.php
 * 前端 app.js 会拦截提交并 POST 到 admin-ajax.php（见 functions.php yimai_handle_booking）。
 *
 * @package yimaiyoga
 */
?>
<form class="booking-form" data-booking-form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
  <input type="hidden" name="action" value="yimai_booking">
  <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('yimai_booking')); ?>">
  <input type="text" name="website" class="hp-field" tabindex="-1" autocomplete="off">
  <div class="form-grid two">
    <input name="name" required maxlength="30" placeholder="姓名">
    <input name="phone" required maxlength="20" placeholder="手机">
  </div>
  <div class="form-grid two">
    <select name="studio" required>
      <option value="">选择门店</option>
      <option>东部店</option>
      <option>绿地店</option>
    </select>
    <select name="interest" required>
      <option value="">体验方向</option>
      <option>精品团课</option>
      <option>私教小班</option>
      <option>定制私教</option>
      <option>体态评估</option>
      <option>RYT200教培</option>
    </select>
  </div>
  <textarea name="message" maxlength="500" placeholder="你希望改善的状态：体态、肩颈、塑形、压力、睡眠..."></textarea>
  <button class="button primary" type="submit">提交预约意向</button>
  <p class="form-message" data-form-message></p>
</form>
