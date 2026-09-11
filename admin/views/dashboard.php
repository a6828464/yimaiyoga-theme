<?php
/** @var array $config */
$json = wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
$theme_uri = get_template_directory_uri();
$copySections = ['home' => '首页', 'classes' => '课程', 'instructors' => '师资', 'membership' => '会员', 'booking' => '预约', 'studio' => '空间', 'contact' => '联系', 'training' => '教培'];
?><!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>网站内容后台 · 一麦瑜伽</title>
<link rel="stylesheet" href="<?php echo esc_url($theme_uri . '/assets/css/app.css'); ?>">
<style>
:root{--bd:#e4e0d8;--bg:#f7f5f0;--card:#fff;--ink:#1d241f;--mut:#8a897f;--clay:#a9795f;--line:rgb(29 36 31/.10)}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font-family:"Noto Sans SC","PingFang SC","Microsoft YaHei",Arial,sans-serif;padding-bottom:120px}
.topbar{position:sticky;top:0;z-index:20;background:rgba(247,245,240,.92);backdrop-filter:blur(14px);border-bottom:1px solid var(--line)}
.topbar-inner{max-width:1200px;margin:auto;padding:14px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px}
.topbar h1{font-size:18px;margin:0;letter-spacing:.04em}
.topbar .sub{font-size:12px;color:var(--mut);margin-top:2px}
.topbar a{font-size:13px;color:var(--ink);text-decoration:none;border-bottom:1px solid var(--clay);padding-bottom:2px;margin-left:14px}
.tabs{max-width:1200px;margin:18px auto 0;display:flex;gap:8px;flex-wrap:wrap;padding:0 20px}
.tab{padding:9px 18px;border:1px solid var(--line);border-radius:999px;background:var(--card);cursor:pointer;font-size:13px;color:var(--mut);transition:.15s}
.tab.active{background:var(--ink);color:#fff;border-color:var(--ink)}
.container{max-width:1200px;margin:auto;padding:20px}
.panel{display:none}
.panel.active{display:block}
.card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:20px;margin-bottom:16px}
.card h3{margin:0 0 14px;font-size:15px;letter-spacing:.02em}
.card .hint{font-size:12px;color:var(--mut);margin-top:6px}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:grid;gap:5px;font-size:12px;color:var(--mut)}
input[type=text],input[type=url],input[type=password],select,textarea{width:100%;border:1px solid var(--line);border-radius:8px;padding:9px 11px;font-size:13px;background:#fbfaf7;outline:none;color:var(--ink);font-family:inherit}
textarea{resize:vertical;min-height:64px}
input:focus,textarea:focus{border-color:var(--clay)}
.item{border:1px solid var(--line);border-radius:10px;padding:14px;margin-bottom:12px;background:#fdfcfa;position:relative}
.item-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.item-head b{font-size:13px}
.item-head .del{border:1px solid rgb(169 121 95/.4);color:var(--clay);background:transparent;border-radius:8px;padding:4px 12px;font-size:12px;cursor:pointer}
.item-head .del:hover{background:#faf0ea}
.add-btn{border:1px dashed var(--clay);color:var(--clay);background:transparent;border-radius:10px;padding:9px 16px;font-size:13px;cursor:pointer;width:100%}
.add-btn:hover{background:#faf0ea}
.img-preview{width:100%;height:120px;border-radius:8px;overflow:hidden;background:#eee;display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--mut);margin-bottom:8px;border:1px solid var(--line)}
.img-preview img{width:100%;height:100%;object-fit:cover}
.file-row{display:flex;gap:8px;align-items:center}
.file-row input[type=file]{font-size:12px}
.upload-btn{border:1px solid var(--line);border-radius:8px;padding:7px 12px;font-size:12px;cursor:pointer;background:var(--card);color:var(--ink)}
.savebar{position:fixed;left:0;right:0;bottom:0;background:rgba(255,255,255,.94);backdrop-filter:blur(14px);border-top:1px solid var(--line);padding:12px 20px;display:flex;justify-content:center;align-items:center;gap:14px;z-index:30}
.savebar .btn{border:0;border-radius:999px;padding:12px 34px;font-size:14px;cursor:pointer;font-weight:600}
.savebar .btn.primary{background:var(--ink);color:#fff}
.savebar .btn.secondary{background:transparent;border:1px solid var(--line);color:var(--ink)}
.savebar .msg{font-size:13px;color:var(--clay)}
.tag{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);border-radius:999px;padding:4px 10px;font-size:12px;margin:0 6px 6px 0;background:#fbfaf7}
.tag .x{cursor:pointer;color:var(--clay);font-weight:700}
.json-wrap{font-family:Consolas,monospace;font-size:12px;min-height:300px;background:#1d241f;color:#e8e4da;border-radius:10px;padding:14px;border:0;width:100%}
.avatar{width:64px;height:64px;border-radius:8px;object-fit:cover;border:1px solid var(--line);background:#eee}
.badge{display:inline-block;font-size:10px;background:var(--ink);color:#fff;border-radius:999px;padding:2px 8px;margin-left:6px;vertical-align:middle}
@media(max-width:760px){.grid2{grid-template-columns:1fr}}
</style>
</head>
<body>

<header class="topbar">
  <div class="topbar-inner">
    <div><h1>网站内容后台 <span class="badge">v2 可视化</span></h1><div class="sub">一麦瑜伽 · 保存后前端即时生效</div></div>
    <div>
      <a href="/" target="_blank">查看网站 ↗</a>
      <a href="/admin/logout">退出</a>
    </div>
  </div>
</header>

<nav class="tabs" data-tabs>
  <button class="tab active" data-tab="site">基础与SEO</button>
  <button class="tab" data-tab="images">图片</button>
  <button class="tab" data-tab="copy">页面文案</button>
  <button class="tab" data-tab="studios">门店</button>
  <button class="tab" data-tab="memberships">会员方案</button>
  <button class="tab" data-tab="themes">课程主题</button>
  <button class="tab" data-tab="paths">练习路径</button>
  <button class="tab" data-tab="instructors">师资</button>
  <button class="tab" data-tab="faqs">常见问题</button>
  <button class="tab" data-tab="training">教培方案</button>
  <button class="tab" data-tab="nav">导航</button>
  <button class="tab" data-tab="security">安全</button>
  <button class="tab" data-tab="update">在线更新</button>
  <button class="tab" data-tab="raw">原始JSON</button>
</nav>

<main class="container">
<form data-admin-form method="post" action="/admin/save">
<textarea name="config_json" data-config-json hidden><?php echo h($json); ?></textarea>
<input type="hidden" name="csrf_token" value="<?php echo esc_attr(csrf_token()); ?>">

<!-- ===================== 基础与SEO ===================== -->
<section class="panel active" data-panel="site">
  <div class="card">
    <h3>品牌信息</h3>
    <div class="grid2">
      <label>品牌标识（文字）<input data-path="site.brand" value="<?php echo h($config['site']['brand'] ?? ''); ?>"><span class="hint">未上传 Logo 图片时，导航显示这段文字</span></label>
      <label>品牌 Logo 图片（可选，上传后优先显示）
        <div class="img-preview" data-preview="site.logo"><img src="<?php echo esc_url(yimai_image_url($config['site']['logo'] ?? '')); ?>" onerror="this.parentNode.textContent='未设置 Logo，显示文字品牌'"></div>
        <input data-path="site.logo" value="<?php echo h($config['site']['logo'] ?? ''); ?>">
        <div class="file-row"><input type="file" data-upload-for="site.logo" accept="image/*"><button type="button" class="upload-btn" data-upload-trigger="site.logo">上传 Logo</button></div>
        <span class="hint">建议透明底 PNG</span>
      </label>
      <label>Logo 显示大小（滑块实时预览，数字越小图越小）
        <input type="range" min="4" max="60" step="1" data-path="site.logoHeight" data-range="site.logoHeight" value="<?php echo h($config['site']['logoHeight'] ?? 13); ?>" style="width:100%;margin:6px 0">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <input type="number" min="4" max="60" step="1" data-path="site.logoHeight" data-number="site.logoHeight" value="<?php echo h($config['site']['logoHeight'] ?? 13); ?>" style="width:80px">
          <span style="font-size:11px;color:var(--mut)">px · 建议 8~16</span>
        </div>
        <span class="hint">调滑块立即预览下方 logo 大小，保存后前台生效</span>
      </label>
      <label>网站名称<input data-path="site.name" value="<?php echo h($config['site']['name'] ?? ''); ?>"></label>
      <label>网站网址<input data-path="site.url" value="<?php echo h($config['site']['url'] ?? ''); ?>"></label>
      <label>网站标题（SEO）<input data-path="site.title" value="<?php echo h($config['site']['title'] ?? ''); ?>"></label>
      <label>网站描述（SEO）<textarea data-path="site.description"><?php echo h($config['site']['description'] ?? ''); ?></textarea></label>
      <label>关键词（每行一个）<textarea data-path="site.keywords" data-array-lines><?php echo h(implode("\n", $config['site']['keywords'] ?? [])); ?></textarea></label>
      <label>备案号<input data-path="site.icpNumber" value="<?php echo h($config['site']['icpNumber'] ?? ''); ?>"></label>
      <label>企业微信 Webhook<input data-path="site.wecomWebhook" value="<?php echo h($config['site']['wecomWebhook'] ?? ''); ?>"><span class="hint">预约表单提交后推送消息的企微群</span></label>
      <label>图床地址<input data-path="site.imgbedDomain" value="<?php echo h($config['site']['imgbedDomain'] ?? ''); ?>" placeholder="https://image.shunan.fun"><span class="hint">后台上传的图片自动同步一份到图床，前台优先使用图床链接；留空仅存本地</span></label>
      <label>图床上传密码<input data-path="site.imgbedAuthCode" value="<?php echo h($config['site']['imgbedAuthCode'] ?? ''); ?>" placeholder="图床后台的安全设置 → 用户授权码"><span class="hint">图床「安全设置」里的用户授权码，图床侧修改后需同步更新此处</span></label>
    </div>
  </div>
  <div class="card">
    <h3>品牌 Logo 实时预览</h3>
    <div style="background:var(--color-oat);border:1px dashed var(--line);border-radius:10px;padding:18px;display:flex;align-items:center;justify-content:center">
      <img data-logo-live alt="Logo 实时预览" style="height:<?php echo esc_attr((int) ($config['site']['logoHeight'] ?? 13)); ?>px;width:auto;display:block;object-fit:contain">
    </div>
    <p class="hint">这是导航栏 logo 的实际大小，调上方滑块实时变化</p>
  </div>
  <div class="card">
    <h3>品牌图片</h3>
    <div class="grid2">
      <label>Favicon 图标
        <div class="img-preview" data-preview="site.favicon"><img src="<?php echo esc_url(yimai_image_url($config['site']['favicon'] ?? '')); ?>" onerror="this.parentNode.textContent='无预览'"></div>
        <input data-path="site.favicon" value="<?php echo h($config['site']['favicon'] ?? ''); ?>">
        <div class="file-row"><input type="file" data-upload-for="site.favicon" accept="image/*"><button type="button" class="upload-btn" data-upload-trigger="site.favicon">上传新图</button></div>
      </label>
      <label>公众号二维码
        <div class="img-preview" data-preview="site.wechatQr"><img src="<?php echo esc_url(yimai_image_url($config['site']['wechatQr'] ?? '')); ?>" onerror="this.parentNode.textContent='无预览'"></div>
        <input data-path="site.wechatQr" value="<?php echo h($config['site']['wechatQr'] ?? ''); ?>">
        <div class="file-row"><input type="file" data-upload-for="site.wechatQr" accept="image/*"><button type="button" class="upload-btn" data-upload-trigger="site.wechatQr">上传新图</button></div>
      </label>
    </div>
  </div>
</section>

<!-- ===================== 图片 ===================== -->
<section class="panel" data-panel="images">
  <div class="card">
    <h3>页面大图</h3>
    <div class="grid2">
      <?php foreach (['homeHero' => '首页首屏大图', 'homeStudio' => '首页空间图', 'homeStory' => '首页故事图', 'bookingHero' => '预约页顶部图', 'studioHero' => '空间页主图'] as $key => $label): ?>
      <label><?php echo h($label); ?>
        <div class="img-preview" data-preview="images.<?php echo h($key); ?>"><img src="<?php echo esc_url(yimai_image_url($config['images'][$key] ?? '')); ?>" onerror="this.parentNode.textContent='无预览'"></div>
        <input data-path="images.<?php echo h($key); ?>" value="<?php echo h($config['images'][$key] ?? ''); ?>">
        <div class="file-row"><input type="file" data-upload-for="images.<?php echo h($key); ?>" accept="image/*"><button type="button" class="upload-btn" data-upload-trigger="images.<?php echo h($key); ?>">上传新图</button></div>
      </label>
      <?php endforeach; ?>
    </div>
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

<!-- ===================== 安全 ===================== -->
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
    <div data-update-notes style="margin-top:10px"></div>
    <p style="margin-top:14px;display:flex;gap:10px;align-items:center;flex-wrap:wrap">
      <button type="button" class="upload-btn" data-update-check>检查更新</button>
      <button type="button" class="upload-btn" data-update-run style="border-color:var(--clay);color:var(--clay)">立即更新</button>
      <span data-update-status style="font-size:13px;color:var(--clay)"></span>
    </p>
    <pre data-update-log style="display:none;margin-top:12px;background:#1d241f;color:#e8e4da;border-radius:10px;padding:14px;font-size:12px;font-family:Consolas,monospace;white-space:pre-wrap;max-height:320px;overflow:auto"></pre>
  </div>
</section>

<!-- ===================== 原始 JSON ===================== -->
<section class="panel" data-panel="raw">
  <div class="card">
    <h3>原始配置 JSON <span style="font-weight:400;font-size:12px;color:var(--mut)">（高级用户）</span></h3>
    <p class="hint">可直接编辑全部配置。保存前自动校验，格式错误不会写入。</p>
    <textarea class="json-wrap" data-path="__root__" data-json><?php echo h($json); ?></textarea>
  </div>
</section>

<div class="savebar">
  <button type="button" class="btn secondary" data-reset>重置表单</button>
  <button type="submit" class="btn primary">保存全部更改</button>
  <span class="msg" data-admin-message></span>
</div>
</form>
</main>

<script>
window.CSRF_TOKEN = <?php echo wp_json_encode(csrf_token()); ?>;
window.CONFIG = <?php echo wp_json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.YIMAI_URI = <?php echo wp_json_encode($theme_uri); ?>;
</script>
<script src="<?php echo esc_url($theme_uri . '/assets/js/admin.js?v=2'); ?>"></script>
<script>
/* ---------- 在线更新 ---------- */
(function () {
  var $ = function (sel) { return document.querySelector(sel); };
  var elLocal = $('[data-update-local]'), elRemote = $('[data-update-remote]'),
      elNotes = $('[data-update-notes]'), elStatus = $('[data-update-status]'),
      elLog = $('[data-update-log]'),
      btnCheck = $('[data-update-check]'), btnRun = $('[data-update-run]');

  function renderMeta(meta) {
    if (!meta) return '—';
    var v = 'v' + (meta.version || '?');
    if (meta.updated) v += ' <span style="font-size:12px;color:var(--mut)">(' + meta.updated + ')</span>';
    return v;
  }
  function renderNotes(notes) {
    if (!notes || !notes.length) { elNotes.innerHTML = ''; return; }
    elNotes.innerHTML = '<ul style="margin:0;padding-left:18px;font-size:13px;color:var(--mut);line-height:1.9">'
      + notes.map(function (n) { return '<li>' + n + '</li>'; }).join('') + '</ul>';
  }
  function showLog(lines) {
    elLog.style.display = 'block';
    elLog.textContent = lines.join('\n');
  }

  elLocal.innerHTML = '加载中…';
  btnCheck.addEventListener('click', function () {
    btnCheck.disabled = true;
    elStatus.textContent = '正在检查…';
    fetch('/admin/update-check', { headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN || '' } })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        elLocal.innerHTML = renderMeta(data.local);
        if (!data.ok) { elRemote.textContent = '检查失败'; elStatus.textContent = data.message || '检查失败'; return; }
        elRemote.innerHTML = renderMeta(data.remote) + (data.source ? ' <span style="font-size:12px;color:var(--mut)">via ' + data.source + '</span>' : '');
        renderNotes(data.remote.notes);
        elStatus.textContent = data.up_to_date ? '已是最新版本 ✓' : '发现新版本，可点击「立即更新」';
      })
      .catch(function () { elStatus.textContent = '网络错误，请重试'; })
      .finally(function () { btnCheck.disabled = false; });
  });

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
        showLog(data.log || [data.message || '无输出']);
        elStatus.textContent = data.ok ? '已更新到 v' + (data.version || '') + ' ✓' : (data.message || '更新失败');
        if (data.ok) { elLocal.textContent = 'v' + (data.version || '') + '（本次更新）'; }
      })
      .catch(function () { elStatus.textContent = '网络错误或超时，请重试'; elLog.textContent += '\n（请求异常中断）'; })
      .finally(function () { btnRun.disabled = false; });
  });

  // 进入页面静默拉取一次当前/远端版本
  fetch('/admin/update-check', { headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN || '' } })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      elLocal.innerHTML = renderMeta(data.local);
      if (data.ok) {
        elRemote.innerHTML = renderMeta(data.remote);
        elStatus.textContent = data.up_to_date ? '已是最新版本 ✓' : '发现新版本，可点击「立即更新」';
      } else {
        elRemote.textContent = '远端不可达';
      }
    })
    .catch(function () { elLocal.textContent = '—'; });
})();
</script>
</body>
</html>
