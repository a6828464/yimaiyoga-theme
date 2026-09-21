<?php
/** @var array $config */
$error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);
$theme_uri = get_template_directory_uri();
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>登录 · 一麦官网后台</title>
<link rel="stylesheet" href="<?php echo esc_url($theme_uri . '/assets/css/app.css'); ?>">
<link rel="stylesheet" href="<?php echo esc_url($theme_uri . '/assets/css/admin.css'); ?>">
<style>
  body{background:var(--color-linen)}
  .admin-login{min-height:100vh;display:grid;place-items:center;padding:2rem}
  .admin-login form{width:min(440px,100%);background:var(--color-pearl);padding:3rem;box-shadow:0 18px 55px rgb(var(--color-forest-rgb)/.08)}
  .admin-login h1{font-family:Georgia,serif;font-size:clamp(1.9rem,6.5vw,2.8rem);color:var(--color-forest);white-space:nowrap}
  .admin-login input{width:100%;border:0;border-bottom:1px solid rgb(var(--color-forest-rgb)/.16);background:transparent;padding:1rem .2rem;outline:none;margin-top:.5rem;font-size:16px}
  .admin-login .button{margin-top:1.5rem}
  .admin-error{color:var(--color-clay);text-align:center;margin-top:1rem}
  @media(max-width:480px){.admin-login{padding:1.25rem}.admin-login form{padding:2.25rem 1.5rem}}
</style>
</head>
<body class="admin-body">
<main class="admin-login">
  <form method="post" action="<?php echo esc_url(yimai_admin_path('login')); ?>">
    <?php echo csrf_field(); ?>
    <p class="eyebrow">Admin</p>
    <h1>一麦后台登录</h1>
    <input name="username" required placeholder="账号" autocomplete="username">
    <input name="password" type="password" required placeholder="密码" autocomplete="current-password">
    <button class="button primary" type="submit">登录</button>
    <?php if ($error): ?><p class="admin-error"><?php echo h($error); ?></p><?php endif; ?>
    <?php if (!admin_password_is_set()): ?>
      <p class="admin-error" style="text-align:left;line-height:1.8">
        <?php if (admin_password_forced_reset()): ?>
          <strong>安全升级：原后台密码已被作废。</strong><br>
          旧版本使用可被推算的默认口令，本次升级已将其失效。
        <?php else: ?>
          <strong>后台密码尚未设置。</strong><br>
        <?php endif; ?>
        为避免可被推算的默认口令，请在<strong>服务器</strong>上运行以下命令设置：
        <code style="display:block;margin-top:.5rem;word-break:break-all">php <?php echo h(get_template_directory()); ?>/cli/set-admin-password.php</code>
        <span style="display:block;margin-top:.5rem;font-size:12px;color:var(--mut)">
          提示：若后台密码是早期版本设置且从未修改过，建议直接重设一次以免沿用旧口令。
        </span>
      </p>
    <?php endif; ?>
  </form>
</main>
</body>
</html>
