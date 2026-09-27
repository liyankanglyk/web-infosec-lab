# Web信息安全性实验系统

> PHP 7.3+ 单体应用，运行于 Apache + PHP + MySQL（环境中立，不绑定任何集成环境），无框架，手动 MVC + 内联路由。

## Project

- 用途: 检测机构业务管理（用户、项目、审计、备份），作为软件信息安全性实验样品。
- 入口: `index.php`：路由、自动加载、会话、数据库连接、辅助函数、`Database` 类均在此文件；展示层助手在 `layout.php`（由 `index.php` 提前 `require_once`）。
- 堆栈: PHP 7.3+（无 7.4 专属语法）, MySQL 5.7+/8.0 (PDO, utf8mb4), Apache 2.4+；放于 Apache 站点根目录下的 `web-infosec-lab\`（目录名必须 ASCII）。技术栈表见 `README.md`；面向使用方的功能与数据设计见 `doc/系统设计方案.md`；代码级机制见本文末“实现层备忘”。
- 重要: 本项目是**安全性测评实验样品**，代码中预埋了已知缺陷（DEF001–DEF030），编号总览见 `doc/缺陷清单.md`，逐条描述与检测见 `doc/缺陷检测指南.md`。下方 Conventions 中列出的"坏实践"（明文密码、弱权限等）均为刻意预留，**不可擅自修复**。

## 文档分工（一个事实只有一个归属）

| 内容 | 唯一归属 | 其他文档只给链接 |
|------|------|------|
| 项目定位 / 风险边界 / 仓库文件清单 / 文档地图 | `README.md` | 是 |
| 需求全文：概述与引用文档、§3.1 使用需求、§3.2 角色与权限（表 2）、§3.3 六大安全性子特性（条款编号源头） | `doc/测试需求.md`（第三方材料，只读） | 是 |
| 系统怎么设计的、有哪些功能（面向使用方：功能清单、角色权限、数据对象、界面与信息架构、访问形态） | `doc/系统设计方案.md` | 是 |
| 代码级实现事实与机制（入口与全局 `$DB`、配置键生效情况、审计写入短路顺序等） | `AGENTS.md`（本文末节备忘） | 是 |
| 缺陷编号总览（编号、子特性、对应条款、名称） | `doc/缺陷清单.md` | 是 |
| 缺陷逐条描述 / 代码位置 / 复现 / 判定 / 统计口径 | `doc/缺陷检测指南.md` | 是 |
| 安装、账户、多租户运维、故障排查、验收 | `doc/部署与运行指南.md` | 是 |
| 改代码的硬性约束与易错点 | `AGENTS.md`（本文） | 是 |
| 许可与例外 | `LICENSE` | 是 |

约定：
- 新增事实先判断归属文档，**不要向其他文档复制一份**；跨文档只写"见 X"。
- 缺陷位置的唯一来源是代码里的 `// DEF0xx` 注释。控制器头部的“包含缺陷”列表与 `doc/缺陷清单.md` 都由它派生，改完注释需同步生成，不得手写新编号。
- 子特性分布、缺陷计数等统计口径只在《缺陷检测指南.md》§一 维护；其他文档引用而不重列。

## Commands

| 用途 | 命令 |
|------|------|
| 启动服务 | 由本机环境启动 Apache 与 MySQL（无专用启停脚本） |
| 生成配置 | 复制 `config_database.example.php` 为 `config_database.php`，填入本地 MySQL 凭据（后者已被 `.gitignore` 排除） |
| 初始化数据库 | 导入 `sql/业务库建库脚本.sql`（建 `websec_db`，含建表+种子数据），可用命令行或 phpMyAdmin |

无 `composer.json`，无构建/测试/lint 命令。

### 部署目录名必须为 ASCII

项目目录名固定为 `web-infosec-lab`，不得改为中文（如“Web信息安全性实验”）。原因：`index.php` 的路由前缀剥离是对 `REQUEST_URI` 与 `SCRIPT_NAME` 做裸字符串 `strpos(...) === 0` 比较，而 Apache 递交的 `REQUEST_URI` 是百分号编码的，中文目录名会使两者一个编码一个不编码，前缀剥不掉，直接导致路由错乱。非 ASCII 路径还会影响 `BackupController` 中 `escapeshellarg` + cmd 代码页下的 `mysqldump < 路径` 行为。产品名称只在界面与文档中使用中文。

### 库名与脚本文件名命名族

| 对象 | 名称 |
|------|------|
| 单租户业务库 | `websec_db` |
| 多租户平台库 | `websec_platform` |
| 组织独立库 | `websec_org_{组织代号}`（`PlatformController::createOrganization()` 拼接） |
| 组织 MySQL 用户 | `websec_{组织代号}_user` |
| 业务库脚本 | `sql/业务库建库脚本.sql` |
| 平台库脚本 | `sql/平台库建库脚本.sql` |

- 中文脚本文件名是安全的：SQL 文件只经 PHP 文件系统 API（`businessSchemaFile()` + `file_get_contents()`）或命令行导入，**不出现在 URL 与路由中**，与上方“目录名必须 ASCII”的约束不冲突（已实测 PHP 7.3 + Apache 可正常读取）。
- `businessSchemaFile()`（`index.php`）的候选列表**同时保留早期 ASCII 名** `sql/database_schema.sql` / `database_schema.sql`，以便旧副本升级后“初始化系统”仍能定位脚本；新增调用不要自己拼路径。
- 多租户建库依赖字面量替换：`PlatformController` 对脚本全文做 `str_replace('websec_db', $orgDbName, $sql)` 后用正则剔掉 `CREATE DATABASE` / `USE` 行。因此业务库脚本内的库名必须始终写作 `websec_db`（含顶部注释），否则组织建库会静默建到错误的库。

### Windows 启停脚本：当前不随仓库分发

`启动实验环境.bat` / `停止实验环境.bat` 已从仓库移除（启动服务改由本机环境完成，见上表）。若以后决定加回任何 `.bat`，必须遵守以下约束（历史上踩过坑）：

- 编码：**必须以 GBK(cp936) + CRLF 保存**，且**第 1 行必须是 `@echo off`，第 2 行必须是 `chcp 936 >nul`**，两行均需纯 ASCII。
- `cmd.exe` 用 **OEM 代码页**（中文 Windows 为 cp936）解析批处理，不是 UTF-8。用 UTF-8 保存会导致中文字节被按 GBK 双字节配对误吞后续 ASCII 字符（如把 `REM` 行的内容变成可执行 token，报“不是内部或外部命令”）。
- CR 不能被吃：`0x0D` 不是 GBK 尾字节，所以 CRLF 安全；但 `✓` `✗` 等**不在 cp936 中的字符不能写进 .bat**，统一用 `[OK]` / `[WARN]` / `[FAIL]`。
- `@echo off` 前不能有空调行，否则 echo 未关闭、下一行被当命令执行。
- 修改时要用二进制模式写，例：`open(f,'wb').write(text.encode('cp936'))`，换行统一 `\r\n`。
- 脚本**不得硬编码任何集成环境的绝对路径**：Apache / MySQL 可执行文件与访问地址一律走顶部变量（`APACHE_HTTPD` / `MYSQLD` / `PHP_EXE` / `APP_URL`），留空即跳过该步。
- 若加回脚本，需同时确认 `.gitattributes` 中的 `*.bat -text` 保住了 CRLF（仓库曾因 `core.autocrlf` 把 CR 规范化掉，导致 cmd 误吞中文行）。

## Architecture

```
index.php                ：入口：常量定义、会话、DB连接、自动加载、路由、辅助函数、Database类
layout.php               ：展示层：设计令牌引用、侧栏/顶栏外壳、图标 sprite、徽章/分页助手、assetUrl()
assets/css/app.css       ：全局设计系统（唯一样式源，控制器不再内联 <style>）
config_database.example.php：配置模板（返回 `$config['platform']` 与 `$config['database']`；需复制为 config_database.php 才能生效）
config_database.php      ：由模板复制生成，含真实凭据（已在 .gitignore 中，不随仓库分发）
sql/业务库建库脚本.sql：完整 DDL + 角色/权限/用户种子数据（代码侧统一由 index.php 的 businessSchemaFile() 定位）
sql/平台库建库脚本.sql：平台库 DDL（组织/平台管理员/平台审计）

{Name}Controller.php     ：控制器（根目录，namespace Controllers\）
LoginController        ：登录/登出（公开页面）
DashboardController    ：仪表板首页
UserController         ：用户 CRUD
ProjectController      ：检测项目 CRUD
AuditController        ：审计日志查看
BackupController       ：数据库备份/恢复
HelpController         ：帮助页面
PlatformController     ：多租户平台管理（组织创建/初始化/停用，平台库 websec_platform 存在时生效）

backups/                 ：备份文件存储
logs/                    ：日志文件（按日期: YYYY-MM-DD.log）
doc/                     ：项目文档（测试需求、系统设计方案、缺陷清单、缺陷检测指南、部署与运行指南）
```

路由规则：`/{controller}/{action}/{args...}` 对应 `Controllers\{Controller}Controller::{action}()`，默认 `dashboard/index`。`/list` 自动映射为 `index` action。登录检查通过 `isPublicPage()` 白名单（仅 Login），权限检查按 `$_SESSION['user_role']`（`super_admin` 或 `admin`）。

## 刻意预留缺陷

本项目是故意保留安全缺陷的样例代码。所有不符合安全或编码规范的实现几乎都是刻意预埋的，不要当成普通项目的遗留缺陷去清理。

除非用户明确要求修复某个具体缺陷，否则：
- 不要"顺手"修复任何安全问题（SQL 注入、XSS、明文密码等）
- 不要重构"不规范"的代码（全局变量、内联 HTML、缺少验证等）
- 不要补全缺失的功能（密码过期强制改密、登录锁定等）
- 可以新增功能、修改与缺陷无关的逻辑（含纯视觉层改版）、回答关于缺陷的问题

> **改版前端的边界**：只改样式与 DOM 结构，不改任何 `$_GET/$_POST/$_SESSION/Cookie` 读写、表单 action、跳转 URL、转义调用、权限分支或审计分支。尤其注意保留：控制器中硬编码的 `location.href = '/project/delete/' + id` 与 `'/audit/deleteall'`（未走 `buildUrl()`）、`confirm()` 内插入的用户名（XSS 面）、`getOrgUrl()` 未转义输出、以及 `orgView` 明文展示数据库密码。

缺陷逐条描述、代码位置与复现方式见 `doc/缺陷检测指南.md`（DEF001–DEF030），编号与条款对照见 `doc/缺陷清单.md`。代码中以 `// DEF0xx` 注释直接标注，可 grep 定位；文档与注释编号一一对应。

## 实现层备忘（代码级事实，面向维护者；使用层面描述在设计方案）

| 主题 | 事实 |
|------|------|
| 数据访问 | `index.php` 内的 `Database` 封装（`query`/`queryAll`/`execute`/`insert`/`update`/`delete`/`getLastInsertId`），PDO 预处理；控制器以 `global $DB` 取用，无服务层/ORM |
| 双库绑定 | 入口先探平台库：命中平台前缀或组织代号时分别绑定 `websec_platform` / `websec_org_{code}`；否则绑 `websec_db`（平台库存在但业务库缺失时亦回退至此） |
| 权限判定 | `hasPermission()` 不查 `role_permissions`：平台会话直接放行；其余按 `in_array($_SESSION['user_role'], [4 个角色])` 判定，即已登录用户全部通过（DEF003） |
| 审计写入 | `auditLog()` 依次判断：未登录、`$_SESSION['audit_disabled']`、`COUNT(*) ≥ system_config.max_audit_logs`，任一命中即不写入；`operation_time` 用应用服务器 `date('Y-m-d')`，`client_ip` 写空串 |
| 配置键生效情况 | 12 个配置列中只有 `system_config.max_audit_logs` 与 `audit_config.enable_audit` 被代码读取；`session_timeout`、`password_expiry_days`、`backup_schedule`、`max_login_attempts`、`lockout_duration` 与 `audit_config` 其余 4 项为死配置，改它们不改变行为 |
| 未接入的表 | `detection_results` 仅出现在初始化的表名清单中，无读写界面；`login_logs` 从不写入（登录只更新 `users.last_login`）；`roles.permissions` JSON 列不参与逻辑 |
| 会话与 Cookie | 会话写 `user_id`/`username`/`user_role`（机构模式另写 `org_code`/`org_name`；平台写 `platform_user_id`）；Cookie 冗余写 `username`/`user_role`/`user_id`（机构模式加 `org_id`/`org_name`），身份判定仍以 `$_SESSION` 为准 |
| 列表与导出 | 四类列表页每页 10 条；审计 CSV 导出上限 1000 行 |
| 备份实现 | `exec`/`shell_exec` 调 `mysqldump`，参数均经 `escapeshellarg`；可执行文件先探 `dirname(BASE_PATH)` 上一级的 `mysql\bin\mysqldump.exe`，探不到则退 PATH；失败不阻断记录 |
| 错误处理 | 控制器内 `die('500: …')` 不设置 HTTP 状态码。`index.php` 位于全局命名空间，其 `catch (Exception)` 可捕获 `PDOException`；控制器文件处于 `namespace Controllers;` 下，同名写法解析为 `Controllers\Exception`，捕获不到，需写成 `\Exception` 或 `\Throwable` |
| URL 构建 | 统一走 `getBaseUrl()` + `buildUrl()` / `url()`；`assetUrl()` 用 CSS 文件 mtime 做版本号，取不到时退回静态版本号 |

---

## Conventions

- 命名空间: `Controllers\`，文件名 `{Name}Controller.php`，类名 `{Name}Controller`。
- 全局依赖: 控制器通过 `global $DB` 获取数据库实例，辅助函数（`isLoggedIn()`, `getCurrentUser()`, `redirect()`, `auditLog()` 等）均为全局函数，定义在 `index.php`。
- 模板: 无模板引擎。控制器方法用 `?>` 内联输出**页面片段**，外层由 `appShellOpen($标题, $导航项, $面包屑)` … `appShellClose()` 包裹；平台端用 `platformShellOpen/Close`；登录/首次初始化等公开页用 `pageHead()` + `.auth` 布局。
- 样式: 一律走 `assets/css/app.css` 的设计令牌与组件类（`.panel` `.table` `.btn` `.badge` `.stat` `.field` `.alert` `.toolbar` `.pagination`），**不要在控制器里写 `<style>` 块**。图标用 `icon('name')` 输出的 SVG sprite，不用 emoji。
- 状态/角色渲染: 用 `statusBadge()` / `roleBadgeClass()` / `roleBadgeLabel()` 保证全站一致；需展示数据库原始枚举值时用 `badge-neutral bare`。
- 数据库: 使用 `$DB->query()`, `$DB->queryAll()`, `$DB->execute()`, `$DB->insert()`, `$DB->update()`, `$DB->delete()` 方法，参数化查询（PDO prepared statements）。
- 审计: 通过 `auditLog()` 全局函数写入 `audit_logs` 表。
- 日志: 通过 `logError()` 写入 `logs/YYYY-MM-DD.log`。
- 密码: 明文存储（DEF005），无哈希处理，刻意为之，勿改。

