<?php
/**
 * 兜底模板：未匹配到专属模板时使用。
 * 页面主要模板：front-page.php / page-classes.php / page-instructors.php /
 * page-training.php / page-membership.php / page-studio.php / page-contact.php / page-booking.php
 *
 * @package yimaiyoga
 */

get_header();
?>
<section class="statement page-block">
  <p class="eyebrow">Yi Mai Yoga</p>
  <h1><?php the_title(); ?></h1>
  <div><?php the_content(); ?></div>
</section>
<?php
get_footer();
