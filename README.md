# 一麦瑜伽官网 WordPress 主题（yimaiyoga）

由 yimaiyoga.com 原 PHP MVC 官网转换而来的 WordPress 主题。主题为数据驱动：`inc/site-data.php`
提供全部默认文案/图片/配置，后台 `/admin` 保存的覆盖项存于 wp_options（`yimai_site_config`）。

- 配色主题：ebony-ivory（黑檀米白）
- 当前版本：见 `theme.json`（与 `style.css`、`functions.php YIMAI_VERSION` 保持一致，发布脚本会强制校验）
- 样式：前台 `assets/css/app.css`（单一文件）；后台 `assets/css/admin.css`

## 配置合并语义（重要）

配置保存与读取共用 `yimai_deep_merge()`（`inc/site-data.php`），规则是：

- **列表整体替换**（`faqs` / `studios` / `instructors` / `nav_items` / `images.studioImages` / `announcements.items` 等）
- **映射逐键合并**（其余对象）

不要改回 `array_replace_recursive`：它对数字索引是逐下标替换、不缩短，会导致后台删除列表项
不生效（删 1 条变回 8 条、删中间项产生重复），且界面无任何报错。

保存时还会做**类型校验**（`yimai_config_validate()`）：类型不符直接返回 400 并提示字段路径，
不写库。此前类型污染（如关键词被存成字符串）会导致全站 `TypeError` 白屏且后台自身也打不开。

## 后台登录（首次部署必读）

后台账号 `admin`，**没有默认密码**——为避免可被推算的口令，初始密码必须在服务器上设置：

```bash
php wp-content/themes/yimaiyoga/cli/set-admin-password.php
```

- 未设置时登录页会给出上述提示
- 升级自旧版（v1.6.x 及更早）且从未改过密码的站点，升级时会**自动作废**旧版可推算口令并要求重设
- 登录失败限流：15 分钟内 5 次失败锁定 15 分钟；会话 30 分钟无操作失效

## 在线更新（双平台推送）

对齐「一麦工作台」的发布模式：本地迭代 → 推送双平台 → 线上后台一键更新。

```
本地修改代码 → 提交并 git push   # 推送主仓库 Gitee（国内直连，稳定）
        ↓
tools/release.sh                 # 校验工作区干净 + 版本一致 → 补推 GitHub 镜像 → 刷新双平台 Release
        ↓
后台 /admin → 在线更新 → 检查更新 → 立即更新
```

- 主仓库（source of truth）：Gitee `meng-taoo/yimaiyoga-theme`——origin 的 fetch/push 都指向它
- 发布镜像：GitHub `a6828464/yimaiyoga-theme`（remote 名 `github`）——release.sh 发布时自动补推并校验
- 发布前置校验（任一不满足即拒绝发布）：工作区无未提交改动、`theme.json`/`style.css`/`functions.php`/`CHANGELOG.md` 四处版本一致
- 打包用 `git archive HEAD`：保证发布物 == 已推送 commit，且中文文件名带 UTF-8 标志（与 codeload 归档一致）
- 拉取优先级是两条独立链：
  - **清单 manifest**：GitHub Release 资产 → Gitee Release 资产
  - **更新包 package**：GitHub codeload 分支归档 → Gitee Release 资产（codeload 内容实时生成，规避 Release 同名资产的 CDN 旧包问题）
- 服务器端更新实现：`inc/updater.php`，纯 PHP（服务器未装 zip 扩展，走 WordPress 内置 PclZip）
- 更新行为：只覆盖包内文件；**不删除**服务器本地文件，且 `inc/local-secrets.php`、
  `assets/images/uploads/`、`.backups` 等受保护文件**不会被更新包覆盖**
- 更新前自动把当前代码备份到 `wp-content/yimai-backups/pre-*.zip`（保留最近 2 份）。
  备份**不含** `local-secrets.php`，且该目录已移出主题目录并放置目录级访问拦截
- 更新包校验：zip 结构 + 必要条件文件 + 清单 `sha256`（发布脚本生成）+ **防降级**（拒绝安装更低版本）
- 版本清单为仓库根目录 `theme.json`（`version` / `updated` / `notes`）

> 信任边界说明：更新源为上述两个 Git 仓库，能向仓库推送代码者即可影响线上代码。
> 当前已实现结构校验、SHA256 与防降级；若需完全消除该边界，需要为发布流程引入签名密钥。

## 服务器环境备忘（测试服务器 yimaiyoga，2026-09 探测）

- PHP 8.5、无 zip 扩展、`raw.githubusercontent.com` 不可靠（codeload 可达）
- 宝塔面板 API 可读写**文本**文件；二进制文件只能通过在线更新（zip 包）同步
- 通过面板 API 部署的文件记得用面板「SetFileAccess」把属主改为 www:www，否则在线更新无法覆盖

## 密钥管理

企业微信预约 Webhook 读取顺序：后台配置 `site.wecomWebhook` → `inc/local-secrets.php`
（git 忽略）→ 空。**不要把 webhook 地址提交进仓库。**

图床配置同理：`site.imgbedDomain` / `site.imgbedAuthCode` → `inc/local-secrets.php`。

> 预约表单无论是否配置 Webhook 都会先落库留存（后台「预约记录」可查看，保留最近 200 条），
> 因此未配置 Webhook 时预约不会静默丢失。

## 本地同步工具

`tools/pull-theme.py`：从测试服务器重新拉取主题覆盖本地（文本走面板 API、图片走线上 HTTP），
用于兜底找回线上改动。

- 会覆盖本地文件；默认先检查工作区是否干净，不干净则拒绝执行
- 支持 `--dry-run` 预览、`--force` 强制覆盖

## 测试

纯 CLI 回归测试（无需 WordPress，使用 `tests/wp-stubs.php` 提供函数桩）：

```bash
php tests/config-merge-test.php   # 配置合并语义与类型校验（33 项）
php tests/security-test.php       # 安全加固边界（44 项）
```

改动 `inc/site-data.php`、`admin/inc/bootstrap.php`、`inc/updater.php`、`inc/imgbed.php` 后请运行。
