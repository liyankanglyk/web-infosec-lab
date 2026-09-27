# Web信息安全性实验系统

![License: CC BY-NC 4.0](https://img.shields.io/badge/License-CC--BY--NC--4.0-lightgrey.svg)
![PHP 7.3+](https://img.shields.io/badge/PHP-7.3%2B-blue.svg)
![MySQL 5.7 or 8.0](https://img.shields.io/badge/MySQL-5.7%20%7C%208.0-orange.svg)
![Apache 2.4+](https://img.shields.io/badge/Apache-2.4%2B-red.svg)
![30 planted deficiencies](https://img.shields.io/badge/Deficiencies-30%20(DEF001--DEF030)-yellow.svg)

用于安全检测与教学练习的 PHP 单体业务系统。系统在可正常运行的业务功能中，按 GB/T 25000.51-2016 信息安全性的六个子特性预埋了 30 个缺陷（DEF001–DEF030）。

它与 DVWA、Pikachu 同属练习靶场，区别在于它是一个完整的业务系统：有用户与角色权限体系、检测业务流程、审计日志、数据库备份与恢复。按《测试需求.md》§1.2 采用单租户部署形态，另有平台端提供的多租户能力。

版本：V1.3　维护者：小花（<li-yan-kang@qq.com>）

## 安全提示

仓库内包含可被真实利用的安全缺陷，包括 SQL 注入、XSS、口令明文存储和越权访问。请勿部署到生产环境或任何可从公网访问的服务器，仅在隔离的实验环境中运行。

系统不含破坏性逻辑：初始化和删除类操作都需登录，且限超级管理员；数据均为虚构种子数据。

## 用途

练习者可以在本系统上完成一次完整的信息安全性检测：用黑盒手段发现问题，在代码中定位对应实现，核对数据库中的实际存储，最后出具报告。参考答案随仓库公开，建议先自行检出再对照。

## 功能

业务功能：

- 用户管理：新增、编辑、停用、分页查询、角色分配
- 权限体系：4 个角色（`super_admin`、`admin`、`operator`、`auditor`），15 项权限，含角色权限关联表
- 检测项目管理与检测结果数据
- 审计日志：按时间、用户、对象类型查询，支持删除与导出
- 数据库备份与恢复，含备份记录和恢复记录
- 帮助中心与系统初始化（重建业务库并生成演示审计数据）
- 平台端：组织登记、组织库初始化、停用与下线、平台审计

靶场特性：

- 30 个缺陷均可复现、可检测，风险等级为中低，不含远程代码执行等高危项
- 每个缺陷在代码与建库脚本中有 `// DEF0xx` 或 `-- DEF0xx` 注释，可用 grep 定位
- 文档提供逐条的标准依据、代码位置、检测方法和预期结果

## 技术栈

| 项目 | 选型 |
|---|---|
| 服务端语言 | PHP 7.3 及以上，不使用 7.4 专属语法，需 `pdo_mysql` 扩展 |
| 数据库 | MySQL 5.7 或 8.0，PDO 访问，`utf8mb4` |
| Web 服务器 | Apache 2.4 及以上；Nginx 加 PHP-CGI 亦可，需自行配置入口 |
| 前端 | HTML、CSS 与原生 JavaScript，控制器内联输出页面片段，样式集中在 `assets/css/app.css` |
| 依赖 | 无 Composer，无构建步骤，无模板引擎 |

## 环境要求

- PHP 7.3 及以上，启用 `pdo_mysql`
- MySQL 5.7 或 8.0，账号需具备 `CREATE DATABASE` 权限
- Apache 2.4 及以上，或等价的 PHP 运行环境
- 可选：phpMyAdmin，用于图形化导入建库脚本

任一 Apache、PHP、MySQL 的组合均可，系统不依赖特定的集成环境。

## 快速开始

第一步，放置代码。将项目复制到 Apache 站点根目录（常见目录名为 `htdocs/`、`www/` 或 `html/`）。目录名必须保持 ASCII 且为 `web-infosec-lab`，不得改为中文，原因见 AGENTS.md。

第二步，初始化数据库。

```bash
mysql -u root -p < sql/业务库建库脚本.sql     # 业务库 websec_db，必选
mysql -u root -p < sql/平台库建库脚本.sql     # 平台库 websec_platform，仅多租户需要
```

也可用 phpMyAdmin 的导入功能执行同样的脚本。

第三步，配置数据库连接。复制 `config_database.example.php` 为 `config_database.php`，填入本机的 `host`、`port`、`user`、`password`：

```php
'database' => [
    'host' => 'localhost',
    'port' => 3306,
    'database' => 'websec_db',
    'user' => 'root',
    'password' => '你的 MySQL 口令',
    'charset' => 'utf8mb4',
],
```

第四步，启动 Apache 与 MySQL，访问 `http://<站点>/web-infosec-lab/`。若用虚拟主机把站点根直接指向本项目，则访问 `http://<主机名>/`。

第五步，登录。

| 入口 | 地址 | 账户 |
|---|---|---|
| 单租户 | `http://<站点>/web-infosec-lab/index.php/login` | `superadmin` / `123456` |
| 平台端 | `http://<站点>/web-infosec-lab/index.php/platform/login` | `admin` 与首次访问时设置的口令 |
| 组织端 | `http://<站点>/web-infosec-lab/index.php/{org_code}/login` | 平台端登记组织时生成 |

默认账户与默认口令本身就是缺陷 DEF001、DEF002、DEF005 的载体，请不要修改或加固。完整部署流程见 [doc/部署与运行指南.md](doc/部署与运行指南.md)。

## 部署形态

- 单租户：不导入平台库即可使用，业务数据在 `websec_db`。《测试需求.md》§1.2 称此形态为“单机构部署形态”，也是测评时的默认形态。
- 多租户：导入 `sql/平台库建库脚本.sql` 创建 `websec_platform` 后启用，每个组织使用独立库 `websec_org_{org_code}`。需求里的“机构”在界面与代码中写作“组织”（org）。

## 目录结构

```text
web-infosec-lab/
├── index.php                      入口：常量、会话、数据库、自动加载、路由、辅助函数、Database 类
├── layout.php                     展示层：页面外壳、图标、徽章与分页
├── LoginController.php            登录与登出
├── DashboardController.php        首页（仪表板）
├── UserController.php             用户管理
├── ProjectController.php          检测项目管理
├── AuditController.php            审计日志
├── BackupController.php           数据库备份与恢复
├── HelpController.php             帮助中心与系统初始化
├── PlatformController.php         平台端（多租户）
├── assets/css/app.css             唯一样式源
├── sql/
│   ├── 业务库建库脚本.sql           业务库表结构与种子数据
│   └── 平台库建库脚本.sql           平台库表结构
├── doc/                           测试需求、系统设计方案、缺陷清单、缺陷检测指南、部署与运行指南
├── backups/                       备份文件，运行时产物
├── logs/                          应用日志，运行时产物
├── config_database.example.php    配置模板
├── .gitignore                     忽略本地配置与运行时产物
├── .gitattributes                 换行与二进制文件属性
├── .htaccess / nginx.htaccess     入口与路由配置示例
├── AGENTS.md                      编码约定与禁区
├── LICENSE                        许可条款
└── README.md
```

## 内置缺陷

共 30 个，编号连续，按 GB/T 25000.51-2016 信息安全性的六个子特性分布：保密性 9 个，完整性 4 个，抗抵赖性 2 个，可核查性 11 个，真实性 2 个，依从性 2 个。

- 编号与子特性、需求条款的对应关系：[doc/缺陷清单.md](doc/缺陷清单.md)
- 逐条的代码位置、检测方法与预期结果：[doc/缺陷检测指南.md](doc/缺陷检测指南.md)

按代码单元定位缺陷：

```bash
grep -rn "DEF0" *.php sql/
```

## 建议的使用流程

1. 完整使用一遍系统，确认哪些行为是正常的：首页、用户管理、项目管理、审计日志、备份与恢复、帮助中心。
2. 黑盒练习：抓包分析登录流程与 Cookie；对查询参数、搜索框和备注字段尝试注入与脚本；测试越权入口、批量删除与备份恢复。
3. 白盒核对：用上面的 grep 找到注释，逐条比对声明的缺陷与实际行为是否一致。
4. 数据库核查：确认口令是否明文、关键操作是否留痕、审计相关字段是否真的被使用。SQL 见检测指南的数据库查询法。
5. 撰写报告：现象、复现步骤、代码位置、风险等级、整改建议。整改建议写入报告即可，不要改动代码。

## 文档

| 文档 | 内容 |
|---|---|
| [doc/测试需求.md](doc/测试需求.md) | 需求规格说明书：概述、引用文档、§3.1 使用需求、§3.2 角色与权限、§3.3 安全性需求。条款编号以此为准 |
| [doc/系统设计方案.md](doc/系统设计方案.md) | 系统设计与功能说明，面向使用方与测评人员 |
| [doc/缺陷清单.md](doc/缺陷清单.md) | 30 个缺陷的编号、子特性、对应需求条款与名称 |
| [doc/缺陷检测指南.md](doc/缺陷检测指南.md) | 逐条缺陷的描述、代码位置、检测方法、预期结果与判定标准 |
| [doc/部署与运行指南.md](doc/部署与运行指南.md) | 安装、账户、多租户运维、备份恢复、故障排查、验收清单 |
| [AGENTS.md](AGENTS.md) | 编码约定、禁区与易错点，面向维护者 |
| [LICENSE](LICENSE) | 许可条款与第三方材料例外 |

同一事实只写在归属文档中，其他文档给出链接。若两处描述不一致，以归属文档为准，欢迎通过 issue 指出。

## 常见问题

| 现象 | 原因 |
|---|---|
| `Access denied for user 'root'@'localhost' (using password: YES)` | `config_database.php` 中的账号或口令与本机 MySQL 不符 |
| 页面 404 或路由错乱 | 项目目录名不是 ASCII 的 `web-infosec-lab` |
| 登录后跳回登录页 | PHP 会话目录不可写，检查 `session.save_path` 权限 |

完整的排查步骤、运维操作与部署验收清单见 [doc/部署与运行指南.md](doc/部署与运行指南.md)。

## 贡献

1. 不要修复预埋缺陷，包括参数化 SQL、密码哈希、权限校验、审计补全一类的"顺手优化"。发现缺陷被改动，请提 issue，不要提交修复。
2. 可以贡献：文档勘误、安装与部署方式改进、不影响缺陷语义的兼容性修复（例如更高 PHP 版本下的告警）、与 30 个检测点无关的演示数据。
3. 提交前自测：`php -l` 通过；`/login` 登录后可访问首页、用户、项目、审计、备份；对照检测指南确认 30 个缺陷仍可复现。
4. 改动代码中的 `DEF0xx` 注释后，需同步控制器头部的"包含缺陷"列表与 [doc/缺陷清单.md](doc/缺陷清单.md)。代码注释是唯一来源。

## 许可证

源代码与自研文档以 CC BY-NC 4.0（署名—非商业性使用 4.0 国际公有）授权，`SPDX-License-Identifier: CC-BY-NC-4.0`。可以自由复制、分发和改编，但须保留署名且不得用于商业目的。

例外：`doc/测试需求.md` 是第三方需求规格说明书，权利归属原出具单位，不由本许可证再授权。

完整条款见 [LICENSE](LICENSE)。

## 免责声明

本系统按原样提供，包含故意保留的真实可利用安全缺陷。作者不对因安装、运行、传播本仓库代码而产生的损失或法律责任承担责任。禁止将本系统部署于任何生产环境或可被公网访问的环境。

## 致谢

- 同类公开靶场：[DVWA](https://github.com/digininja/DVWA)、[Pikachu](https://github.com/zhuifeng3051/pikachu)
- 缺陷分类依据：GB/T 25000.51-2016《系统与软件工程 系统与软件质量要求和评价（SQuaRE）第 51 部分：就绪可用软件产品（RUSP）的质量要求和测试细则》
