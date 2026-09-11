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
<style>
  body{background:var(--color-linen)}
  .admin-login{min-height:100vh;display:grid;place-items:center;padding:2rem}
  .admin-login form{width:min(440px,100%);background:var(--color-pearl);padding:3rem;box-shadow:0 18px 55px rgb(var(--color-forest-rgb)/.08)}
  .admin-login h1{font-family:Georgia,serif;font-size:2.8rem;color:var(--color-forest)}
  .admin-login input{width:100%;border:0;border-bottom:1px solid rgb(var(--color-forest-rgb)/.16);background:transparent;padding:1rem .2rem;outline:none;margin-top:.5rem}
  .admin-login .button{margin-top:1.5rem}
  .admin-error{color:var(--color-clay);text-align:center;margin-top:1rem}
</style>
</head>
<body class="admin-body">
<main class="admin-login">
  <form method="post" action="/admin/login">
    <?php echo csrf_field(); ?>
    <p class="eyebrow">Admin</p>
    <h1>一麦后台登录</h1>
    <input name="username" required placeholder="账号" autocomplete="username">
    <input name="password" type="password" required placeholder="密码" autocomplete="current-password">
    <button class="button primary" type="submit">登录</button>
    <?php if ($error): ?><p class="admin-error"><?php echo h($error); ?></p><?php endif; ?>
  </form>
</main>
</body>
</html>
