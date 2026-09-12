<?php
/** @var array $config */
$json = wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
$theme_uri = get_template_directory_uri();
$copySections = ['home' => '首页', 'classes' => '课程', 'instructors' => '师资', 'membership' => '会员', 'booking' => '预约', 'studio' => '空间', 'contact' => '联系', 'training' => '教培'];
$imgbedMap = function_exists('yimai_imgbed_map') ? yimai_imgbed_map() : [];
$imgbedDomain = function_exists('yimai_imgbed_config') ? (yimai_imgbed_config()['domain'] ?? '') : '';
$uploadTarget = $config['site']['uploadTarget'] ?? 'imgbed';
$noticeEnabled = $config['announcements']['enabled'] ?? true;
$stripEnabled = $config['announcements']['stripEnabled'] ?? true;
$adminTheme = (string) ($config['site']['adminTheme'] ?? '');
$siteTheme = (string) ($config['site']['theme'] ?? 'ebony-ivory');
/* 全站主题预设（前台网站配色，functions.php yimai_site_theme_presets） */
$siteThemes = function_exists('yimai_site_theme_presets') ? yimai_site_theme_presets() : [];
/* 后台外观主题（只影响后台界面） */
$adminThemes = [
    'paper' => ['name' => '宣纸 · 暖白', 'sw' => ['#f7f5f0', '#ffffff', '#a9795f', '#1d241f']],
    'inknight' => ['name' => '墨夜 · 深色', 'sw' => ['#191a17', '#222320', '#c9926e', '#e9e5da']],
    'celadon' => ['name' => '青瓷 · 雅绿', 'sw' => ['#edf2ed', '#fbfdfb', '#47806a', '#24352c']],
    'terra' => ['name' => '陶土 · 暖橘', 'sw' => ['#f6efe8', '#fffcf9', '#b0623c', '#372820']],
    'dusk' => ['name' => '黛蓝 · 雾蓝', 'sw' => ['#eef0f5', '#fcfdff', '#4c6a9e', '#232a3a']],
];
/* 侧边栏：分组 => [面板 => 标题] */
$navGroups = [
    '站点' => [
        'site' => '基础与 SEO',
        'images' => '图片管理',
        'notice' => '活动公告',
        'appearance' => '全站主题',
    ],
    '内容' => [
        'copy' => '页面文案',
        'studios' => '门店',
        'memberships' => '会员方案',
        'themes' => '课程主题',
        'paths' => '练习路径',
        'instructors' => '师资',
        'faqs' => '常见问题',
        'training' => '教培方案',
        'nav' => '导航菜单',
    ],
    '系统' => [
        'update' => '在线更新',
        'security' => '账号安全',
    ],
];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>网站内容后台 · 一麦瑜伽</title>
<link rel="stylesheet" href="<?php echo esc_url($theme_uri . '/assets/css/app.css'); ?>">
<style>
/* ---------- 主题变量（默认=宣纸；其余外观靠 body[data-admin-theme] 覆盖） ---------- */
body{
  --bg:#f7f5f0;--card:#ffffff;--ink:#1d241f;--mut:#8a897f;--clay:#a9795f;
  --line:rgb(29 36 31/.10);--side:#fbfaf7;--input:#fbfaf7;--item:#fdfcfa;
  --bar:rgba(255,255,255,.94);--hover:rgb(29 36 31/.05);
  --active-bg:#1d241f;--active-fg:#ffffff;--imgbg:#edece8;
}
body[data-admin-theme="inknight"]{
  --bg:#191a17;--card:#222320;--ink:#e9e5da;--mut:#98948a;--clay:#c9926e;
  --line:rgb(233 229 218/.13);--side:#1f201c;--input:#292a25;--item:#26271f;
  --bar:rgba(25,26,23,.92);--hover:rgb(233 229 218/.07);
  --active-bg:#e9e5da;--active-fg:#191a17;--imgbg:#2c2d27;
}
body[data-admin-theme="celadon"]{
  --bg:#edf2ed;--card:#fbfdfb;--ink:#24352c;--mut:#7e9086;--clay:#47806a;
  --line:rgb(36 53 44/.11);--side:#f3f8f3;--input:#f5faf6;--item:#f6fbf7;
  --bar:rgba(251,253,251,.94);--hover:rgb(36 53 44/.05);
  --active-bg:#2f5e48;--active-fg:#ffffff;--imgbg:#e7efe8;
}
body[data-admin-theme="terra"]{
  --bg:#f6efe8;--card:#fffcf9;--ink:#372820;--mut:#99836f;--clay:#b0623c;
  --line:rgb(55 40 32/.10);--side:#faf2ea;--input:#faf4ec;--item:#fdf7f0;
  --bar:rgba(255,252,249,.94);--hover:rgb(55 40 32/.05);
  --active-bg:#a9542f;--active-fg:#ffffff;--imgbg:#f0e4d8;
}
body[data-admin-theme="dusk"]{
  --bg:#eef0f5;--card:#fcfdff;--ink:#232a3a;--mut:#7e879b;--clay:#4c6a9e;
  --line:rgb(35 42 58/.11);--side:#f3f5fa;--input:#f5f7fc;--item:#f6f8fd;
  --bar:rgba(252,253,255,.94);--hover:rgb(35 42 58/.05);
  --active-bg:#2f4468;--active-fg:#ffffff;--imgbg:#e8ebf2;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:"Noto Sans SC","PingFang SC","Microsoft YaHei",Arial,sans-serif;padding-bottom:120px}
/* 手机端顶栏（桌面隐藏） */
.mobile-top{display:none;position:sticky;top:0;z-index:45;align-items:center;gap:10px;background:var(--bar);backdrop-filter:blur(14px);border-bottom:1px solid var(--line);padding:10px 14px;padding-top:calc(10px + env(safe-area-inset-top))}
.menu-btn{width:38px;height:38px;flex:none;border:1px solid var(--line);background:var(--card);border-radius:10px;display:flex;flex-direction:column;gap:4px;align-items:center;justify-content:center;cursor:pointer}
.menu-btn span{width:16px;height:1.5px;background:var(--ink);display:block;border-radius:2px}
.mobile-brand{font-weight:700;font-size:14px;letter-spacing:.04em}
.mobile-brand span{font-weight:400;font-size:11px;color:var(--mut)}
.mobile-ver{margin-left:auto;font-size:11px;color:var(--mut)}
.shell{display:grid;grid-template-columns:236px minmax(0,1fr);min-height:100vh}
/* ---------- 左侧边栏（桌面常驻 / 手机抽屉） ---------- */
.drawer-backdrop{display:none}
.side{position:sticky;top:0;height:100vh;display:flex;flex-direction:column;background:var(--side);border-right:1px solid var(--line)}
.side-head{padding:20px 18px 10px;display:flex;align-items:flex-start;justify-content:space-between;gap:8px}
.side-brand{font-size:16px;font-weight:700;letter-spacing:.05em}
.side-brand span{font-weight:400;font-size:11px;color:var(--mut);margin-left:6px}
.side-ver{font-size:11px;color:var(--mut);margin-top:4px}
.side-close{display:none;width:32px;height:32px;flex:none;border:1px solid var(--line);border-radius:8px;background:var(--card);color:var(--ink);font-size:18px;line-height:1;cursor:pointer;align-items:center;justify-content:center}
.side-nav{flex:1;overflow-y:auto;padding:4px 12px 12px;display:flex;flex-direction:column;gap:2px}
.side-group{font-size:10px;letter-spacing:.2em;color:var(--mut);margin:14px 8px 6px}
.side-nav button{display:flex;align-items:center;border:0;background:transparent;text-align:left;padding:8px 10px;border-radius:8px;font-size:13px;color:var(--ink);cursor:pointer;transition:.15s}
.side-nav button:hover{background:var(--hover)}
.side-nav button.active{background:var(--active-bg);color:var(--active-fg)}
.side-dot{width:7px;height:7px;border-radius:50%;background:#d05a3a;display:inline-block;margin-left:auto}
.side-dot[hidden]{display:none}
.side-foot{border-top:1px solid var(--line);padding:12px 18px;display:flex;gap:16px;font-size:12px}
.side-foot a{color:var(--mut);text-decoration:none}
.side-foot a:hover{color:var(--ink)}
/* ---------- 右侧主区 ---------- */
.main{min-width:0}
.main-top{position:sticky;top:0;z-index:20;background:var(--bar);backdrop-filter:blur(14px);border-bottom:1px solid var(--line);padding:14px 24px;display:flex;align-items:baseline;gap:12px}
.main-top h2{margin:0;font-size:16px;letter-spacing:.04em}
.main-top .sub{font-size:12px;color:var(--mut)}
.container{max-width:1080px;margin:auto;padding:20px 24px}
.panel{display:none}
.panel.active{display:block}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:20px;margin-bottom:16px}
.card h3{margin:0 0 14px;font-size:15px;letter-spacing:.02em}
.card .hint{font-size:12px;color:var(--mut);margin-top:6px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:grid;gap:5px;font-size:12px;color:var(--mut)}
input[type=text],input[type=url],input[type=password],select,textarea{width:100%;border:1px solid var(--line);border-radius:8px;padding:9px 11px;font-size:13px;background:var(--input);outline:none;color:var(--ink);font-family:inherit}
textarea{resize:vertical;min-height:64px}
input:focus,textarea:focus,select:focus{border-color:var(--clay)}
.item{border:1px solid var(--line);border-radius:10px;padding:14px;margin-bottom:12px;background:var(--item);position:relative}
.item-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}
.item-head b{font-size:13px}
.item-head .ops{display:flex;gap:8px}
.item-head .del{border:1px solid var(--line);color:var(--clay);background:transparent;border-radius:8px;padding:4px 12px;font-size:12px;cursor:pointer}
.item-head .del:hover{background:var(--hover)}
.add-btn{border:1px dashed var(--clay);color:var(--clay);background:transparent;border-radius:10px;padding:9px 16px;font-size:13px;cursor:pointer;width:100%}
.add-btn:hover{background:var(--hover)}
.img-preview{width:100%;height:120px;border-radius:8px;overflow:hidden;background:var(--imgbg);display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--mut);margin-bottom:8px;border:1px solid var(--line)}
.img-preview img{width:100%;height:100%;object-fit:cover}
.file-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.file-row input[type=file]{font-size:12px;max-width:100%}
.upload-btn{border:1px solid var(--line);border-radius:8px;padding:7px 12px;font-size:12px;cursor:pointer;background:var(--card);color:var(--ink)}
.goto-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);border-radius:999px;padding:8px 16px;font-size:12px;cursor:pointer;background:var(--card);color:var(--ink);margin:6px 8px 0 0}
.goto-btn:hover{border-color:var(--clay);color:var(--clay)}
/* 本地 / 图床切换 */
.src-toggle{display:flex;align-items:center;margin:2px 0 8px;border:1px solid var(--line);border-radius:8px;overflow:hidden;width:max-content;max-width:100%}
.src-toggle button{border:0;background:transparent;padding:6px 14px;font-size:12px;color:var(--mut);cursor:pointer;white-space:nowrap}
.src-toggle button.active{background:var(--active-bg);color:var(--active-fg)}
.src-hint{font-size:11px;color:var(--mut);padding:0 10px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.switch{display:inline-flex;align-items:center;gap:10px;font-size:13px;color:var(--ink);cursor:pointer;font-weight:600}
.switch input{width:18px;height:18px;accent-color:var(--clay)}
.savebar{position:fixed;left:236px;right:0;bottom:0;background:var(--bar);backdrop-filter:blur(14px);border-top:1px solid var(--line);padding:12px 20px;padding-bottom:calc(12px + env(safe-area-inset-bottom));display:flex;justify-content:center;align-items:center;gap:14px;z-index:30}
.savebar .btn{border:0;border-radius:999px;padding:12px 34px;font-size:14px;cursor:pointer;font-weight:600}
.savebar .btn.primary{background:var(--active-bg);color:var(--active-fg)}
.savebar .btn.secondary{background:transparent;border:1px solid var(--line);color:var(--ink)}
.savebar .msg{font-size:13px;color:var(--clay)}
.tag{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);border-radius:999px;padding:4px 10px;font-size:12px;margin:0 6px 6px 0;background:var(--input)}
.tag .x{cursor:pointer;color:var(--clay);font-weight:700}
/* 更新日志 */
.log-entry{border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:10px;background:var(--item)}
.log-entry.new{border-color:var(--clay)}
.log-head{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.log-head b{font-size:13px}
.log-date{font-size:11px;color:var(--mut)}
.log-badge{font-style:normal;font-size:10px;background:var(--clay);color:#fff;border-radius:999px;padding:2px 8px}
.log-entry ul{margin:0;padding-left:18px;font-size:12px;color:var(--mut);line-height:1.9}
/* 全站主题 */
.site-theme-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px}
.theme-card{display:grid;gap:5px;justify-items:start;border:1px solid var(--line);background:var(--item);border-radius:12px;padding:14px;cursor:pointer;text-align:left}
.theme-card.active{border-color:var(--clay);box-shadow:0 0 0 1px var(--clay) inset}
.theme-card b{font-size:13px;color:var(--ink)}
.theme-card span{font-size:11px;color:var(--mut)}
.theme-swatch{display:flex;gap:5px;margin-bottom:2px}
.theme-swatch i{width:18px;height:18px;border-radius:50%;border:1px solid var(--line)}
.admin-theme-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:8px}
.admin-theme-grid .theme-card{padding:10px 12px}
.admin-theme-grid .theme-swatch i{width:13px;height:13px}
/* 图片库 */
.lib-backdrop{position:fixed;inset:0;z-index:80;background:rgb(0 0 0/.45);display:flex;align-items:center;justify-content:center;padding:18px;opacity:0;visibility:hidden;pointer-events:none;transition:opacity .25s ease,visibility .25s ease}
.lib-backdrop.open{opacity:1;visibility:visible;pointer-events:auto}
.lib-panel{background:var(--card);border:1px solid var(--line);border-radius:14px;width:min(820px,100%);height:min(620px,84vh);display:flex;flex-direction:column;overflow:hidden;transform:translateY(12px);transition:transform .25s ease}
.lib-backdrop.open .lib-panel{transform:none}
.lib-head{display:flex;align-items:center;gap:10px;padding:12px 14px;border-bottom:1px solid var(--line)}
.lib-head b{font-size:14px;flex:none}
.lib-count{font-size:11px;color:var(--mut);flex:none}
.lib-search{flex:1;min-width:80px;border:1px solid var(--line);border-radius:8px;padding:7px 10px;font-size:12.5px;background:var(--input);outline:none;color:var(--ink)}
.lib-search:focus{border-color:var(--clay)}
.lib-close{width:30px;height:30px;flex:none;border:1px solid var(--line);border-radius:8px;background:var(--card);color:var(--ink);font-size:17px;line-height:1;cursor:pointer}
.lib-grid{flex:1;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill,minmax(128px,1fr));gap:10px;padding:14px;align-content:start}
.lib-item{position:relative;display:grid;gap:5px;border:1px solid var(--line);border-radius:10px;background:var(--item);padding:8px;cursor:pointer;text-align:left}
.lib-item:hover{border-color:var(--clay)}
.lib-thumb{display:block;height:76px;border-radius:6px;overflow:hidden;background:var(--imgbg)}
.lib-thumb img{width:100%;height:100%;object-fit:cover;display:block}
.lib-name{font-size:10.5px;color:var(--mut);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.lib-badge{position:absolute;top:12px;right:12px;font-style:normal;font-size:9.5px;background:var(--clay);color:#fff;border-radius:999px;padding:2px 7px}
/* ---------- 手机端：顶栏 + 左侧抽屉 ---------- */
@media(max-width:880px){
  body{padding-bottom:96px}
  .mobile-top{display:flex}
  .shell{display:block}
  .drawer-backdrop{display:block;position:fixed;inset:0;z-index:55;background:rgb(0 0 0/.38);opacity:0;visibility:hidden;transition:opacity .25s ease,visibility .25s ease}
  .drawer-backdrop.open{opacity:1;visibility:visible}
  .side{position:fixed;top:0;left:0;bottom:0;height:auto;width:min(78vw,300px);transform:translateX(-103%);transition:transform .28s ease;z-index:60;border-right:1px solid var(--line)}
  .side.open{transform:none;box-shadow:12px 0 40px rgb(0 0 0/.18)}
  .side-head{padding:14px 14px 8px;align-items:center}
  .side-close{display:flex}
  .side-nav{padding:4px 12px 12px}
  .side-foot{padding:12px 16px;padding-bottom:calc(12px + env(safe-area-inset-bottom))}
  .main-top{padding:12px 16px}
  .main-top .sub{display:none}
  .container{padding:14px 14px 20px}
  .card{padding:16px;border-radius:12px}
  .grid2{grid-template-columns:1fr}
  .item{padding:12px}
  .img-preview{height:110px}
  .savebar{left:0;padding:10px 14px;padding-bottom:calc(10px + env(safe-area-inset-bottom));gap:10px}
  .savebar .btn{flex:1;padding:12px 10px;font-size:13.5px}
  .savebar .btn.primary{max-width:60%}
  .savebar .msg{font-size:12px}
}
</style>
</head>
<body<?php echo $adminTheme !== '' ? ' data-admin-theme="' . esc_attr($adminTheme) . '"' : ''; ?>>
<script>(function(){try{var t=localStorage.getItem('yimai_admin_theme');if(t){document.body.setAttribute('data-admin-theme',t)}}catch(e){}})();</script>

<div class="mobile-top">
  <button class="menu-btn" type="button" data-drawer-open aria-label="打开菜单"><span></span><span></span><span></span></button>
  <div class="mobile-brand">一麦瑜伽 <span>内容后台</span></div>
  <div class="mobile-ver">v<?php echo h(YIMAI_VERSION); ?></div>
</div>
<div class="drawer-backdrop" data-drawer-close></div>

<div class="shell">

<aside class="side" data-drawer>
  <div class="side-head">
    <div>
      <div class="side-brand">一麦瑜伽<span>内容后台</span></div>
      <div class="side-ver">主题版本 v<?php echo h(YIMAI_VERSION); ?> · 保存后前台即时生效</div>
    </div>
    <button class="side-close" type="button" data-drawer-close aria-label="关闭菜单">×</button>
  </div>
  <nav class="side-nav">
    <?php foreach ($navGroups as $group => $items): ?>
      <div class="side-group"><?php echo h($group); ?></div>
      <?php foreach ($items as $panel => $title): ?>
        <button type="button" data-tab="<?php echo h($panel); ?>" data-title="<?php echo h($title); ?>" class="<?php echo $panel === 'site' ? 'active' : ''; ?>"><?php echo h($title); ?><?php if ($panel === 'update'): ?><em class="side-dot" data-update-dot hidden></em><?php endif; ?></button>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="side-foot">
    <a href="/" target="_blank">查看网站 ↗</a>
    <a href="/admin/logout">退出登录</a>
  </div>
</aside>

<div class="main">
<header class="main-top">
  <h2 data-main-title>基础与 SEO</h2>
  <span class="sub">一麦瑜伽官网 · 保存后前台即时生效</span>
</header>

<main class="container">
<form data-admin-form method="post" action="/admin/save">
<textarea name="config_json" data-config-json hidden><?php echo h($json); ?></textarea>
<input type="hidden" name="csrf_token" value="<?php echo esc_attr(csrf_token()); ?>">

<!-- ===================== 基础与SEO ===================== -->
<section class="panel active" data-panel="site">
  <div class="card">
    <h3>品牌信息</h3>
    <p class="hint" style="margin:-6px 0 12px">品牌 Logo / Favicon / 公众号二维码等图片请到左侧「图片管理」上传维护。</p>
    <div class="grid2">
      <label>品牌标识（文字）<input data-path="site.brand" value="<?php echo h($config['site']['brand'] ?? ''); ?>"><span class="hint">未上传 Logo 图片时，导航显示这段文字</span></label>
      <label>网站名称<input data-path="site.name" value="<?php echo h($config['site']['name'] ?? ''); ?>"></label>
      <label>网站网址<input data-path="site.url" value="<?php echo h($config['site']['url'] ?? ''); ?>"></label>
      <label>网站标题（SEO）<input data-path="site.title" value="<?php echo h($config['site']['title'] ?? ''); ?>"></label>
      <label>网站描述（SEO）<textarea data-path="site.description"><?php echo h($config['site']['description'] ?? ''); ?></textarea></label>
      <label>关键词（每行一个）<textarea data-path="site.keywords" data-array-lines><?php echo h(implode("\n", $config['site']['keywords'] ?? [])); ?></textarea></label>
      <label>备案号<input data-path="site.icpNumber" value="<?php echo h($config['site']['icpNumber'] ?? ''); ?>"></label>
      <label>企业微信 Webhook<input data-path="site.wecomWebhook" value="<?php echo h($config['site']['wecomWebhook'] ?? ''); ?>"><span class="hint">预约表单提交后推送消息的企微群</span></label>
      <label>图床地址<input data-path="site.imgbedDomain" value="<?php echo h($config['site']['imgbedDomain'] ?? ''); ?>" placeholder="https://image.shunan.fun"><span class="hint">配置后图片可一键切到「图床加速」；留空仅存本地</span></label>
      <label>图床上传密码<input data-path="site.imgbedAuthCode" value="<?php echo h($config['site']['imgbedAuthCode'] ?? ''); ?>" placeholder="图床后台的安全设置 → 用户授权码"><span class="hint">图床「安全设置」里的用户授权码，图床侧修改后需同步更新此处</span></label>
    </div>
  </div>
  <div class="card">
    <h3>品牌 Logo 实时预览</h3>
    <div style="border:1px dashed var(--line);border-radius:10px;padding:18px;display:flex;align-items:center;justify-content:center">
      <img data-logo-live alt="Logo 实时预览" style="height:<?php echo esc_attr((int) ($config['site']['logoHeight'] ?? 13)); ?>px;width:auto;display:block;object-fit:contain">
    </div>
    <p class="hint">这是导航栏 logo 的实际大小，调下方滑块实时变化；上传或更换 Logo 图片请到「图片管理」</p>
    <div style="margin-top:10px">
      <label>Logo 显示大小（滑块实时预览，数字越小图越小）
        <input type="range" min="4" max="60" step="1" data-path="site.logoHeight" data-range="site.logoHeight" value="<?php echo h($config['site']['logoHeight'] ?? 13); ?>" style="width:100%;margin:6px 0">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <input type="number" min="4" max="60" step="1" data-path="site.logoHeight" data-number="site.logoHeight" value="<?php echo h($config['site']['logoHeight'] ?? 13); ?>" style="width:80px">
          <span style="font-size:11px;color:var(--mut)">px · 建议 8~16</span>
        </div>
      </label>
    </div>
  </div>
</section>

<!-- ===================== 图片管理 ===================== -->
<section class="panel" data-panel="images">
  <div class="card">
    <h3>上传设置</h3>
    <div class="grid2">
      <label>上传后默认使用
        <select data-path="site.uploadTarget">
          <option value="imgbed" <?php echo $uploadTarget === 'imgbed' ? 'selected' : ''; ?>>图床地址（推荐，加载更快）</option>
          <option value="local" <?php echo $uploadTarget !== 'imgbed' ? 'selected' : ''; ?>>本地地址（仅存服务器本地）</option>
        </select>
        <span class="hint">每次上传后仍可用图片下方的「本地存储 / 图床加速」按钮单独切换</span>
      </label>
    </div>
  </div>
  <div class="card">
    <h3>品牌图片 <span style="font-weight:400;font-size:12px;color:var(--mut)">（导航 / 浏览器图标 / 页脚）</span></h3>
    <div class="grid2">
      <?php admin_image_field('site.logo', '品牌 Logo', $config['site']['logo'] ?? '', '建议透明底 PNG；显示大小在「基础与SEO」调节'); ?>
      <?php admin_image_field('site.favicon', 'Favicon 图标', $config['site']['favicon'] ?? '', '浏览器标签页小图标，建议正方形透明 PNG'); ?>
      <?php admin_image_field('site.wechatQr', '公众号二维码', $config['site']['wechatQr'] ?? '', '显示在网站页脚'); ?>
    </div>
  </div>
  <div class="card">
    <h3>首页</h3>
    <div class="grid2">
      <?php foreach (['homeHero' => '首屏大图', 'homeStudio' => '空间氛围图', 'homeStory' => '空间故事图'] as $key => $label): ?>
        <?php admin_image_field('images.' . $key, $label, $config['images'][$key] ?? ''); ?>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <h3>预约页</h3>
    <div class="grid2">
      <?php admin_image_field('images.bookingHero', '预约页顶部图', $config['images']['bookingHero'] ?? ''); ?>
    </div>
  </div>
  <div class="card">
    <h3>空间页 / 门店 <span style="font-weight:400;font-size:12px;color:var(--mut)">（预约页的空间光影区与门店卡片共用）</span></h3>
    <div class="grid2">
      <?php admin_image_field('images.studioHero', '空间光影主图', $config['images']['studioHero'] ?? '', '空间页大图 + 预约页光影区；门店图不足时也作为兜底'); ?>
    </div>
    <p style="margin:14px 0 8px;font-size:12px;color:var(--mut)">门店空间图（按顺序对应「门店」列表里的门店，显示在空间页与预约页的门店卡片）</p>
    <div data-studio-images></div>
    <button type="button" class="add-btn" data-add-studio-img>+ 添加门店空间图</button>
  </div>
  <div class="card">
    <h3>更多图片</h3>
    <p class="hint" style="margin-top:0">课程主题配图与老师照片在各自的列表页里管理（每一项内可直接上传 / 切换图床）：</p>
    <button type="button" class="goto-btn" data-goto-tab="themes">去课程主题 →</button>
    <button type="button" class="goto-btn" data-goto-tab="instructors">去师资管理 →</button>
  </div>
</section>

<!-- ===================== 活动公告 ===================== -->
<section class="panel" data-panel="notice">
  <div class="card">
    <h3>展示位置</h3>
    <label class="switch"><input type="checkbox" data-path="announcements.enabled" <?php echo $noticeEnabled ? 'checked' : ''; ?>> 首页弹窗</label>
    <p class="hint">开启后，访客<b>每次进入首页都会自动弹窗展示一次</b>当前活动；关闭弹窗后本次停留不再打扰，重新进入首页会再次弹出。</p>
    <label class="switch" style="margin-top:12px"><input type="checkbox" data-path="announcements.stripEnabled" <?php echo $stripEnabled ? 'checked' : ''; ?>> 预约页顶部活动条</label>
    <p class="hint">预约页最上方显示一条活动横幅，点击查看活动详情；没有启用中的活动时自动隐藏。</p>
  </div>
  <div class="card">
    <h3>活动列表</h3>
    <p class="hint">多条同时启用时展示<b>最下面一条</b>；填写开始 / 结束日期可定时上下线，到期自动隐藏，无需手动关闭。</p>
    <div data-editor-list="announcements"></div>
    <button type="button" class="add-btn" data-add-item="announcements">+ 添加活动</button>
  </div>
</section>

<!-- ===================== 全站主题 ===================== -->
<section class="panel" data-panel="appearance">
  <div class="card">
    <h3>网站配色</h3>
    <p class="hint" style="margin-top:0">一键切换整个官网的配色方案（页面底色、强调色、文字色整体联动），保存后前台即时生效。</p>
    <div class="site-theme-grid">
      <?php foreach ($siteThemes as $id => $t): ?>
      <button type="button" class="theme-card<?php echo $siteTheme === $id ? ' active' : ''; ?>" data-site-theme-set="<?php echo h($id); ?>">
        <span class="theme-swatch"><?php foreach ($t['sw'] as $c): ?><i style="background:<?php echo h($c); ?>"></i><?php endforeach; ?></span>
        <b><?php echo h($t['name']); ?></b>
        <span><?php echo h($t['desc']); ?></span>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="card">
    <h3>后台界面配色 <span style="font-weight:400;font-size:12px;color:var(--mut)">（只影响这个后台，不影响网站）</span></h3>
    <div class="admin-theme-grid">
      <?php foreach ($adminThemes as $id => $t): ?>
      <button type="button" class="theme-card" data-admin-theme-set="<?php echo h($id); ?>">
        <span class="theme-swatch"><?php foreach ($t['sw'] as $c): ?><i style="background:<?php echo h($c); ?>"></i><?php endforeach; ?></span>
        <b><?php echo h($t['name']); ?></b>
      </button>
      <?php endforeach; ?>
    </div>
    <p class="hint">点击即时切换，当前浏览器记住选择；点「保存全部更改」后所有设备都使用这款。</p>
  </div>
</section>

<!-- ===================== 页面文案 ===================== -->
<section class="panel" data-panel="copy">
  <?php foreach ($copySections as $section => $label): ?>
  <div class="card">
    <h3><?php echo h($label); ?></h3>
    <div class="grid2">
      <?php foreach (($config['copy'][$section] ?? []) as $key => $value): ?>
      <label><?php echo h($key); ?><textarea data-path="copy.<?php echo h($section); ?>.<?php echo h($key); ?>"><?php echo h($value); ?></textarea></label>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</section>

<!-- ===================== 门店（可视化列表） ===================== -->
<section class="panel" data-panel="studios">
  <div class="card">
    <h3>门店列表</h3>
    <p class="hint">顺序即前台显示顺序；门店空间图在「图片管理 → 空间页 / 门店」里按顺序对应。</p>
    <div data-editor-list="studios"></div>
    <button type="button" class="add-btn" data-add-item="studios">+ 添加门店</button>
  </div>
</section>

<!-- ===================== 会员方案 ===================== -->
<section class="panel" data-panel="memberships">
  <div class="card">
    <h3>会员方案</h3>
    <div data-editor-list="memberships"></div>
    <button type="button" class="add-btn" data-add-item="memberships">+ 添加方案</button>
  </div>
</section>

<!-- ===================== 课程主题 ===================== -->
<section class="panel" data-panel="themes">
  <div class="card">
    <h3>课程主题</h3>
    <div data-editor-list="courseThemes"></div>
    <button type="button" class="add-btn" data-add-item="courseThemes">+ 添加课程主题</button>
  </div>
</section>

<!-- ===================== 练习路径 ===================== -->
<section class="panel" data-panel="paths">
  <div class="card">
    <h3>练习路径</h3>
    <div data-editor-list="classPaths"></div>
    <button type="button" class="add-btn" data-add-item="classPaths">+ 添加练习路径</button>
  </div>
</section>

<!-- ===================== 师资 ===================== -->
<section class="panel" data-panel="instructors">
  <div class="card">
    <h3>师资团队</h3>
    <div data-editor-list="instructors"></div>
    <button type="button" class="add-btn" data-add-item="instructors">+ 添加老师</button>
  </div>
</section>

<!-- ===================== 常见问题 ===================== -->
<section class="panel" data-panel="faqs">
  <div class="card">
    <h3>常见问题（FAQ）</h3>
    <div data-editor-list="faqs"></div>
    <button type="button" class="add-btn" data-add-item="faqs">+ 添加问题</button>
  </div>
</section>

<!-- ===================== 教培方案 ===================== -->
<section class="panel" data-panel="training">
  <div class="card">
    <h3>教培方案</h3>
    <div data-editor-list="training_programs"></div>
    <button type="button" class="add-btn" data-add-item="training_programs">+ 添加教培方案</button>
  </div>
</section>

<!-- ===================== 导航 ===================== -->
<section class="panel" data-panel="nav">
  <div class="card">
    <h3>导航菜单</h3>
    <p class="hint">编辑文案与链接；顺序即显示顺序。</p>
    <div data-editor-list="nav_items"></div>
    <button type="button" class="add-btn" data-add-item="nav_items">+ 添加导航项</button>
  </div>
</section>

<!-- ===================== 账号安全 ===================== -->
<section class="panel" data-panel="security">
  <div class="card">
    <h3>修改登录密码</h3>
    <div class="grid2">
      <label>原密码<input type="password" id="old_password" autocomplete="current-password"></label>
      <label>新密码（至少 8 位）<input type="password" id="new_password" autocomplete="new-password"></label>
    </div>
    <p style="margin-top:12px"><button type="button" class="btn secondary" data-change-password style="border:1px solid var(--line);border-radius:999px;padding:10px 26px;cursor:pointer">修改密码</button> <span data-password-msg style="font-size:13px;color:var(--clay)"></span></p>
  </div>
</section>

<!-- ===================== 在线更新 ===================== -->
<section class="panel" data-panel="update">
  <div class="card">
    <h3>主题在线更新 <span style="font-weight:400;font-size:12px;color:var(--mut)">（GitHub + Gitee 双平台）</span></h3>
    <p class="hint">本地迭代代码 → git push 推送双平台 → 在此点击「检查更新 / 立即更新」。更新只覆盖代码文件，保留服务器本地的配置、上传图片与密钥文件；更新前自动备份到主题 .backups 目录（保留最近 2 份）。</p>
    <div class="grid2" style="margin-top:12px">
      <label>当前版本<div style="font-size:15px;color:var(--ink)" data-update-local>—</div></label>
      <label>远端最新版本<div style="font-size:15px;color:var(--ink)" data-update-remote>未检查</div></label>
    </div>
    <p style="margin-top:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <button type="button" class="upload-btn" data-update-check>检查更新</button>
      <button type="button" class="upload-btn" data-update-run style="border-color:var(--clay);color:var(--clay)">立即更新</button>
      <span data-update-status style="font-size:13px;color:var(--clay)"></span>
    </p>
    <pre data-update-log style="display:none;margin-top:12px;background:#1d241f;color:#e8e4da;border-radius:10px;padding:14px;font-size:12px;font-family:Consolas,monospace;white-space:pre-wrap;max-height:320px;overflow:auto"></pre>
  </div>
  <div class="card">
    <h3>更新日志 <span style="font-weight:400;font-size:12px;color:var(--mut)">（新版本会标出「新」）</span></h3>
    <div data-update-changelog><p class="hint">正在加载更新日志…</p></div>
  </div>
</section>

<div class="savebar">
  <button type="button" class="btn secondary" data-reset>重置表单</button>
  <button type="submit" class="btn primary">保存全部更改</button>
  <span class="msg" data-admin-message></span>
</div>
</form>
</main>
</div>
</div>

<div class="lib-backdrop" data-library>
  <div class="lib-panel" role="dialog" aria-modal="true" aria-label="选择图片">
    <div class="lib-head">
      <b>图片库</b>
      <span class="lib-count" data-library-status></span>
      <input class="lib-search" data-library-search type="text" placeholder="搜索文件名…">
      <button class="lib-close" type="button" data-library-close aria-label="关闭图片库">×</button>
    </div>
    <div class="lib-grid" data-library-grid><p class="hint" style="padding:20px">正在加载…</p></div>
  </div>
</div>

<script>
window.CSRF_TOKEN = <?php echo wp_json_encode(csrf_token()); ?>;
window.CONFIG = <?php echo wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.YIMAI_URI = <?php echo wp_json_encode($theme_uri); ?>;
window.YIMAI_IMGBED = {
  domain: <?php echo wp_json_encode($imgbedDomain); ?>,
  map: <?php echo wp_json_encode($imgbedMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>
};
</script>
<script src="<?php echo esc_url($theme_uri . '/assets/js/admin.js?v=7'); ?>"></script>
<script>
/* ---------- 在线更新 ---------- */
(function () {
  var $ = function (sel) { return document.querySelector(sel); };
  var elLocal = $('[data-update-local]'), elRemote = $('[data-update-remote]'),
      elStatus = $('[data-update-status]'), elLog = $('[data-update-log]'),
      elChangelog = $('[data-update-changelog]'), elDot = $('[data-update-dot]'),
      btnCheck = $('[data-update-check]'), btnRun = $('[data-update-run]');
  var localVersion = <?php echo wp_json_encode(YIMAI_VERSION); ?>;

  function esc(s) {
    return String(s == null ? '' : s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }
  function vcmp(a, b) {
    var pa = String(a).split('.'), pb = String(b).split('.');
    for (var i = 0; i < Math.max(pa.length, pb.length); i++) {
      var x = parseInt(pa[i], 10) || 0, y = parseInt(pb[i], 10) || 0;
      if (x !== y) return x - y;
    }
    return 0;
  }
  function renderMeta(meta) {
    if (!meta) return '—';
    var v = 'v' + (meta.version || '?');
    if (meta.updated) v += ' <span style="font-size:12px;color:var(--mut)">(' + esc(meta.updated) + ')</span>';
    return v;
  }
  function renderChangelog(entries) {
    if (!elChangelog) return;
    if (!entries || !entries.length) {
      elChangelog.innerHTML = '<p class="hint">暂无更新日志</p>';
      return;
    }
    elChangelog.innerHTML = entries.map(function (en) {
      var isNew = en.version && vcmp(en.version, localVersion) > 0;
      var notes = (en.notes || []).map(function (n) { return '<li>' + esc(n) + '</li>'; }).join('');
      if (!notes) notes = '<li>—</li>';
      return '<div class="log-entry' + (isNew ? ' new' : '') + '">'
        + '<div class="log-head"><b>v' + esc(en.version) + '</b>'
        + (en.date ? '<span class="log-date">' + esc(en.date) + '</span>' : '')
        + (isNew ? '<i class="log-badge">新</i>' : '')
        + '</div><ul>' + notes + '</ul></div>';
    }).join('');
  }
  function applyCheck(data) {
    elLocal.innerHTML = renderMeta(data.local);
    if (!data.ok) {
      elRemote.textContent = '检查失败';
      elStatus.textContent = data.message || '检查失败';
      renderChangelog(data.changelog);
      if (elDot) elDot.hidden = true;
      return;
    }
    elRemote.innerHTML = renderMeta(data.remote) + (data.source ? ' <span style="font-size:12px;color:var(--mut)">via ' + esc(data.source) + '</span>' : '');
    elStatus.textContent = data.up_to_date ? '已是最新版本 ✓' : '发现新版本，可点击「立即更新」';
    if (elDot) elDot.hidden = !!data.up_to_date;
    var entries = (data.remote && data.remote.changelog && data.remote.changelog.length)
      ? data.remote.changelog : (data.changelog || []);
    renderChangelog(entries);
  }
  function check(silent) {
    if (!silent) { btnCheck.disabled = true; elStatus.textContent = '正在检查…'; }
    return fetch('/admin/update-check', { headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN || '' } })
      .then(function (r) { return r.json(); })
      .then(function (data) { applyCheck(data); })
      .catch(function () { if (!silent) elStatus.textContent = '网络错误，请重试'; })
      .finally(function () { btnCheck.disabled = false; });
  }

  btnCheck.addEventListener('click', function () { check(false); });

  btnRun.addEventListener('click', function () {
    if (!confirm('立即从远程仓库拉取最新版本并覆盖主题代码？\n（更新前会自动备份，服务器本地配置与图片不受影响）')) return;
    btnRun.disabled = true;
    elStatus.textContent = '正在下载更新包并应用，请勿关闭页面…';
    elLog.style.display = 'block';
    elLog.textContent = '更新中…';
    var fd = new FormData();
    fd.append('csrf_token', window.CSRF_TOKEN || '');
    fetch('/admin/update-run', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        elLog.style.display = 'block';
        elLog.textContent = (data.log || [data.message || '无输出']).join('\n');
        elStatus.textContent = data.ok ? '已更新到 v' + (data.version || '') + ' ✓' : (data.message || '更新失败');
        if (data.ok) {
          elLocal.textContent = 'v' + (data.version || '') + '（本次更新）';
          setTimeout(function () { check(true); }, 1200);
        }
      })
      .catch(function () { elStatus.textContent = '网络错误或超时，请重试'; elLog.textContent += '\n（请求异常中断）'; })
      .finally(function () { btnRun.disabled = false; });
  });

  check(true);
})();
</script>
</body>
</html>
