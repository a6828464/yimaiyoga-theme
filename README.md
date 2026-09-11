# 一麦瑜伽官网 WordPress 主题（yimaiyoga）

由 yimaiyoga.com 原 PHP MVC 官网转换而来的 WordPress 主题。主题为数据驱动：`inc/site-data.php`
提供全部默认文案/图片/配置，后台 `/admin` 保存的覆盖项存于 wp_options（`yimai_site_config`）。

- 配色主题：ebony-ivory（黑檀米白）
- 当前版本：见 `theme.json`（与 `style.css`、`functions.php YIMAI_VERSION` 三处保持一致）

## 在线更新（双平台推送）

对齐「一麦工作台」的发布模式：本地迭代 → 推送双平台 → 线上后台一键更新。

```
本地修改代码 → git push origin main   # 同时推送 GitHub + Gitee
        ↓
后台 /admin → 在线更新 → 检查更新 → 立即更新
```

- 远程仓库：GitHub `a6828464/yimaiyoga-theme`（主）+ Gitee `meng-taoo/yimaiyoga-theme`（备）
- 服务器端拉取顺序：Gitee 优先（国内连通性好），GitHub（codeload）兜底
- 实现：`inc/updater.php`，纯 PHP（服务器未装 zip 扩展，走 WordPress 内置 PclZip）
- 更新行为：只覆盖包内文件；**不删除**服务器本地文件（`inc/local-secrets.php`、
  `assets/images/uploads/` 后台上传图、`.backups/` 备份均不受影响）
- 每次更新前自动把当前代码备份到主题 `.backups/pre-*.zip`，保留最近 2 份
- 版本清单为仓库根目录 `theme.json`（`version` / `updated` / `notes`），发版时三处版本号一起改

## 服务器环境备忘（测试服务器 yimaiyoga，2026-09 探测）

- PHP 8.5、无 zip 扩展、`raw.githubusercontent.com` 不可靠（codeload 可达）
- 宝塔面板 API 可读写**文本**文件；二进制文件只能通过在线更新（zip 包）同步
- 通过面板 API 部署的文件记得用面板「SetFileAccess」把属主改为 www:www，否则在线更新无法覆盖

## 密钥管理

企业微信预约 Webhook 读取顺序：后台配置 `site.wecomWebhook` → `inc/local-secrets.php`
（git 忽略）→ 空。**不要把 webhook 地址提交进仓库。**

## 本地同步工具

工作区 `../tools/pull-theme.py`：从测试服务器重新拉取主题覆盖本地（文本走面板 API、
图片走线上 HTTP），用于兜底找回线上改动。注意：会覆盖本地未推送的修改，慎用。
