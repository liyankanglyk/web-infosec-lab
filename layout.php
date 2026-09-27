<?php
/**
 * 布局与图标渲染
 * 纯展示层，不涉及业务逻辑与安全策略
 */

// =========================================================================
// 图标 SVG symbol 集（一次注入，全页 <use>）
// =========================================================================
function iconSprite()
{
    static $done = false;
    if ($done) return;
    $done = true;
    ?>
    <svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">
        <symbol id="ic-shield" viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6l-7-3Z"/><path d="M9.5 12.5 11 14l4-4"/></symbol>
        <symbol id="ic-grid" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></symbol>
        <symbol id="ic-folder" viewBox="0 0 24 24"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7Z"/></symbol>
        <symbol id="ic-users" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></symbol>
        <symbol id="ic-list" viewBox="0 0 24 24"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1"/><circle cx="4" cy="12" r="1"/><circle cx="4" cy="18" r="1"/></symbol>
        <symbol id="ic-database" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v7c0 1.7 4 3 9 3s9-1.3 9-3V5"/><path d="M3 12v7c0 1.7 4 3 9 3s9-1.3 9-3v-7"/></symbol>
        <symbol id="ic-help" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.7.3-1 .9-1 1.7v.5"/><circle cx="12" cy="17" r=".6" fill="currentColor" stroke="none"/></symbol>
        <symbol id="ic-settings" viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.03 1.56V21a2 2 0 1 1-4 0v-.09a1.7 1.7 0 0 0-1.11-1.56 1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.7 1.7 0 0 0 .34-1.87 1.7 1.7 0 0 0-1.56-1.03H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.65 8.47a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.7 1.7 0 0 0 1.87.34h.09A1.7 1.7 0 0 0 10.15 2.6V3a2 2 0 1 1 4 0v.09c0 .72.43 1.36 1.09 1.63.66.28 1.42.15 1.94-.35l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.7 1.7 0 0 0-.34 1.87v.09c.28.66.9 1.09 1.63 1.09H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51.79Z"/></symbol>
        <symbol id="ic-plus" viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></symbol>
        <symbol id="ic-edit" viewBox="0 0 24 24"><path d="M15.5 4.5 19 8l-9.5 9.5-4 .5.5-4L15.5 4.5Z"/><path d="M13 7l4 4"/></symbol>
        <symbol id="ic-trash" viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M10 11v7"/><path d="M14 11v7"/><path d="M6 7l1 12a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-12"/><path d="M9 7V4h6v3"/></symbol>
        <symbol id="ic-back" viewBox="0 0 24 24"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></symbol>
        <symbol id="ic-logout" viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></symbol>
        <symbol id="ic-download" viewBox="0 0 24 24"><path d="M12 3v12"/><polyline points="7 10 12 15 17 10"/><path d="M4 20h16"/></symbol>
        <symbol id="ic-restore" viewBox="0 0 24 24"><polyline points="3 4 3 10 9 10"/><path d="M4.5 16a8 8 0 1 0 2-7.9L3 10"/></symbol>
        <symbol id="ic-alert" viewBox="0 0 24 24"><path d="M12 4 3 20h18L12 4Z"/><line x1="12" y1="10" x2="12" y2="14.5"/><circle cx="12" cy="17.2" r=".6" fill="currentColor" stroke="none"/></symbol>
        <symbol id="ic-info" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><line x1="12" y1="11" x2="12" y2="16.5"/><circle cx="12" cy="8" r=".6" fill="currentColor" stroke="none"/></symbol>
        <symbol id="ic-check" viewBox="0 0 24 24"><polyline points="5 12 10 17 19 7"/></symbol>
        <symbol id="ic-key" viewBox="0 0 24 24"><circle cx="7.5" cy="15.5" r="3.5"/><path d="M10 13 20 3"/><path d="M16 7l2 2"/><path d="M14 9l2 2"/></symbol>
        <symbol id="ic-monitor" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="12" rx="2"/><line x1="8" y1="20" x2="16" y2="20"/><line x1="12" y1="16" x2="12" y2="20"/></symbol>
        <symbol id="ic-server" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="6" rx="1"/><rect x="3" y="14" width="18" height="6" rx="1"/><line x1="7" y1="7" x2="7.01" y2="7"/><line x1="7" y1="17" x2="7.01" y2="17"/></symbol>
        <symbol id="ic-inbox" viewBox="0 0 24 24"><path d="M3 12h5l2 3h4l2-3h5"/><path d="M3 12 5 5a2 2 0 0 1 2-1h10a2 2 0 0 1 2 1l2 7v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-7Z"/></symbol>
    </svg>
    <?php
}

function icon($name, $class = 'ic')
{
    return '<svg class="' . htmlspecialchars($class) . '" aria-hidden="true"><use href="#ic-' . htmlspecialchars($name) . '"/></svg>';
}

// =========================================================================
// 资源 URL
// =========================================================================
function assetUrl($rel)
{
    // 复用 index.php 的基地址约定，避免与 buildUrl() 的目录解析出现两套规则
    return getBaseUrl() . '/' . ltrim($rel, '/');
}

// =========================================================================
// 侧栏导航（按角色过滤，与快速操作区一致）
// =========================================================================
function sidebarNav($active = '')
{
    $role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

    // 入口定义: [label, url, icon, 角色可见集合(null = 所有已登录用户)]
    $groups = [
        '概览' => [
            'dashboard' => ['工作台', '/dashboard', 'grid', null],
        ],
        '业务' => [
            'project' => ['项目管理', '/project/list', 'folder', ['super_admin', 'operator']],
            'user'    => ['用户管理', '/user/list',    'users',  ['super_admin', 'admin']],
        ],
        '治理' => [
            'audit'   => ['审计日志', '/audit/list',   'list',     ['super_admin', 'auditor']],
            'backup'  => ['备份管理', '/backup/list',  'database', ['super_admin', 'admin']],
        ],
        '支持' => [
            'help'     => ['帮助文档', '/help/faq',        'help',     null],
            'settings' => ['系统设置', '/help/settings',    'settings', null],
        ],
    ];
    ?>
    <nav class="side-nav">
        <?php foreach ($groups as $groupTitle => $entries): ?>
            <?php
            $visible = [];
            foreach ($entries as $key => $e) {
                if ($e[3] === null || in_array($role, $e[3])) {
                    $visible[$key] = $e;
                }
            }
            if (!$visible) continue;
            ?>
            <div class="nav-group">
                <div class="nav-title"><?= $groupTitle ?></div>
                <?php foreach ($visible as $key => $e): ?>
                    <a class="nav-item<?= $active === $key ? ' is-active' : '' ?>" href="<?= buildUrl($e[1]) ?>">
                        <?= icon($e[2]) ?><span class="lbl"><?= $e[0] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>
    <?php
}

// =========================================================================
// 顶栏
// =========================================================================
function topbar($crumbs = [])
{
    $mode = isset($_SESSION['mode']) ? $_SESSION['mode'] : 'single';
    $modeMap = ['single' => '单租户', 'org' => '组织实例', 'platform' => '平台管理'];
    $modeLabel = isset($modeMap[$mode]) ? $modeMap[$mode] : $mode;
    ?>
    <div class="app-top">
        <div class="crumbs">
            <span>Web信息安全性实验系统</span>
            <?php foreach ($crumbs as $c): ?>
                <?php
                // 允许两种写法：['label'=>..(, 'url'=>..)] 或 ['label'=>['label'=>..,'url'=>..]]
                if (is_array($c) && isset($c['label']) && is_array($c['label'])) {
                    $c = $c['label'];
                }
                ?>
                <span class="sep">/</span>
                <?php if (is_array($c) && isset($c['url'])): ?>
                    <a href="<?= htmlspecialchars($c['url']) ?>"><?= htmlspecialchars(isset($c['label']) ? $c['label'] : '') ?></a>
                <?php else: ?>
                    <b><?= htmlspecialchars(is_array($c) ? (isset($c['label']) ? $c['label'] : '') : $c) ?></b>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div class="top-spacer"></div>
        <span class="top-mode"><span class="dot"></span><?= htmlspecialchars($modeLabel) ?></span>
    </div>
    <?php
}

// =========================================================================
// 侧栏品牌 + 底部用户
// =========================================================================
function sidebarFooterUser($name, $role, $logoutUrl)
{
    $initial = mb_substr($name, 0, 1, 'UTF-8');
    ?>
    <div class="side-foot">
        <div class="user-chip">
            <div class="avatar"><?= htmlspecialchars($initial ?: '?') ?></div>
            <div class="user-meta">
                <div class="u1" title="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name ?: '未登录') ?></div>
                <div class="u2" title="<?= htmlspecialchars($role) ?>"><?= htmlspecialchars($role ?: '无角色') ?></div>
            </div>
            <button class="logout-btn" title="登出" onclick="location.href='<?= htmlspecialchars($logoutUrl) ?>'"><?= icon('logout') ?></button>
        </div>
    </div>
    <?php
}

// =========================================================================
// 页面外壳
// =========================================================================
function pageHead($title)
{
    ?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title) ?> · Web信息安全性实验系统</title>
    <link rel="stylesheet" href="<?= assetUrl('assets/css/app.css') ?>">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6l-7-3Z' fill='%23235aa8'/%3E%3C/svg%3E">
</head>
<body>
<?php
    iconSprite();
}

function appShellOpen($title, $active = '', $crumbs = [])
{
    pageHead($title);
    $name  = isset($_SESSION['username']) ? $_SESSION['username'] : '';
    $roleMap = [
        'super_admin' => '超级管理员',
        'admin' => '普通管理员',
        'operator' => '操作员',
        'auditor' => '审计管理员',
    ];
    $roleRaw = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
    $role = isset($roleMap[$roleRaw]) ? $roleMap[$roleRaw] : $roleRaw;
    ?>
    <div class="app-shell">
        <aside class="app-side">
            <div class="side-brand">
                <div class="brand-mark"><?= icon('shield', 'ic') ?></div>
                <div class="brand-txt">
                    <div class="b1">Web信息安全性实验系统</div>
                    <div class="b2">安全实验室</div>
                </div>
            </div>
            <?php sidebarNav($active); ?>
            <?php sidebarFooterUser($name, $role, buildUrl('/login/logout')); ?>
        </aside>
        <div class="app-main">
            <?php topbar($crumbs); ?>
            <main class="app-body">
    <?php
}

function appShellClose()
{
    ?>
            </main>
        </div>
    </div>
</body>
</html>
    <?php
}

/**
 * 平台外壳（不同于业务侧栏）
 */
function platformShellOpen($title, $active = '', $crumbs = [])
{
    pageHead($title);
    $name = isset($_SESSION['platform_username']) ? $_SESSION['platform_username'] : '';
    ?>
    <div class="app-shell">
        <aside class="app-side">
            <div class="side-brand">
                <div class="brand-mark"><?= icon('server', 'ic') ?></div>
                <div class="brand-txt">
                    <div class="b1">平台管理中心</div>
                    <div class="b2">多租户管理</div>
                </div>
            </div>
            <nav class="side-nav">
                <div class="nav-group">
                    <div class="nav-title">平台</div>
                    <a class="nav-item<?= $active === 'dashboard' ? ' is-active' : '' ?>" href="<?= buildUrl('/dashboard') ?>"><?= icon('grid') ?><span class="lbl">总览</span></a>
                    <a class="nav-item<?= $active === 'create' ? ' is-active' : '' ?>" href="<?= buildUrl('/organizations/create') ?>"><?= icon('plus') ?><span class="lbl">新增组织</span></a>
                    <a class="nav-item" href="<?= buildUrl('/logout') ?>" target="_self"><?= icon('monitor') ?><span class="lbl">业务入口</span></a>
                </div>
            </nav>
            <div class="side-foot">
                <div class="user-chip">
                    <div class="avatar"><?= htmlspecialchars(mb_substr($name, 0, 1, 'UTF-8') ?: 'P') ?></div>
                    <div class="user-meta">
                        <div class="u1"><?= htmlspecialchars($name ?: 'admin') ?></div>
                        <div class="u2">平台管理员</div>
                    </div>
                    <button class="logout-btn" title="退出" onclick="location.href='<?= buildUrl('/logout') ?>'"><?= icon('logout') ?></button>
                </div>
            </div>
        </aside>
        <div class="app-main">
            <?php topbar($crumbs); ?>
            <main class="app-body">
    <?php
}

function platformShellClose() { appShellClose(); }

/**
 * 页面标题栏
 */
function pageHeader($eyebrow, $title, $sub = '', $actions = '')
{
    ?>
    <header class="page-head">
        <div class="ph-main">
            <?php if ($eyebrow): ?><div class="ph-eyebrow"><?= htmlspecialchars($eyebrow) ?></div><?php endif; ?>
            <h1 class="ph-title"><?= htmlspecialchars($title) ?></h1>
            <?php if ($sub): ?><div class="ph-sub"><?= $sub /* HTML 允许（页面自填说明） */ ?></div><?php endif; ?>
        </div>
        <?php if ($actions): ?><div class="ph-actions"><?= $actions /* 允许嵌入按钮 HTML */ ?></div><?php endif; ?>
    </header>
    <?php
}

/**
 * 消息横幅（复用现有 $_SESSION['platform_msg'] / $_SESSION['error'] / $_GET['success'] 等）
 */
function flashMessage($type, $message, $escape = true)
{
    $map = ['success' => 'ok', 'ok' => 'ok', 'error' => 'err', 'warn' => 'warn', 'warning' => 'warn', 'info' => 'info'];
    $k = isset($map[$type]) ? $map[$type] : 'info';
    $iconName = ['ok' => 'check', 'err' => 'alert', 'warn' => 'alert', 'info' => 'info'];
    $ic = isset($iconName[$k]) ? $iconName[$k] : 'info';
    ?>
    <div class="alert alert-<?= $k ?>">
        <?= icon($ic) ?>
        <div class="alert-body"><?= $escape ? htmlspecialchars($message) : $message ?></div>
    </div>
    <?php
}

/**
 * 分页组件（复用 renderPagination 的 URL 规则，仅换视觉）
 */
function renderPagination($page, $totalPages, $param = 'page')
{
    if ($totalPages <= 1) return;
    echo '<div class="pagination">';
    if ($page > 1) {
        echo '<a href="?' . $param . '=' . ($page - 1) . '">上一页</a>';
    }
    $range = 2;
    $last = 0;
    for ($i = 1; $i <= $totalPages; $i++) {
        $visible = ($i == 1 || $i == $totalPages || ($i >= $page - $range && $i <= $page + $range));
        if ($visible) {
            if ($last && $i - $last > 1) echo '<span>…</span>';
            if ($i == $page) {
                echo '<span class="current">' . $i . '</span>';
            } else {
                echo '<a href="?' . $param . '=' . $i . '">' . $i . '</a>';
            }
            $last = $i;
        }
    }
    if ($page < $totalPages) {
        echo '<a href="?' . $param . '=' . ($page + 1) . '">下一页</a>';
    }
    echo '</div>';
}

/**
 * 角色 → badge class 映射
 */
function roleBadgeClass($role)
{
    $map = [
        'super_admin' => 'brand',
        'admin'       => 'admin',
        'operator'    => 'op',
        'auditor'     => 'auditor',
        ''            => 'none',
    ];
    return isset($map[$role]) ? $map[$role] : 'none';
}

function roleBadgeLabel($role)
{
    $map = [
        'super_admin' => '超级管理员',
        'admin'       => '普通管理员',
        'operator'    => '操作员',
        'auditor'     => '审计管理员',
    ];
    return isset($map[$role]) ? $map[$role] : '未分配';
}

/**
 * 枚举值 → 中文标签展示；原始入库值保留在 title 里供核查
 */
function enumBadge($value, $map, $defaultClass = 'neutral')
{
    $key = strtolower((string) $value);
    if (isset($map[$key])) {
        list($cls, $label) = $map[$key];
    } else {
        $cls   = $defaultClass;
        $label = ($value === '' || $value === null) ? '-' : $value;
    }
    $raw  = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $text = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    // 原始值与中文标签一致时不重复提示
    $tip = ($raw !== '' && strcasecmp($raw, $label) !== 0)
         ? ' title="原始值: ' . $raw . '"' : '';
    return '<span class="badge badge-' . $cls . '"' . $tip . '>' . $text . '</span>';
}

/**
 * 状态 → 中文徽章（用户/项目/组织/备份/恢复 通用）
 */
function statusBadge($status, $labels = [])
{
    $map = [
        'active'    => ['ok',      '激活'],
        'inactive'  => ['neutral', '禁用'],
        'locked'    => ['err',     '锁定'],
        'deleted'   => ['err',     '已删除'],
        'draft'     => ['neutral', '草稿'],
        'submitted' => ['info',    '已提交'],
        'reviewed'  => ['brand',   '已审核'],
        'archived'  => ['warn',    '已存档'],
        'completed' => ['ok',      '完成'],
        'failed'    => ['err',     '失败'],
        'pending'   => ['warn',    '处理中'],
        'success'   => ['ok',      '成功'],
        'failure'   => ['err',     '失败'],
        'manual'    => ['neutral', '手动'],
        'auto'      => ['info',    '自动'],
    ];
    // 同一枚举在不同页面的固有叫法（如组织的 active = 活跃）可覆盖
    foreach ($labels as $k => $v) {
        $map[strtolower($k)] = isset($map[$k]) ? [$map[strtolower($k)][0], $v] : ['neutral', $v];
    }
    return enumBadge($status, $map);
}

/**
 * 审计操作类型 → 中文徽章
 */
function auditOpBadge($type)
{
    $map = [
        'login'    => ['info',  '登录'],
        'logout'   => ['neutral','登出'],
        'create'   => ['ok',    '新增'],
        'update'   => ['brand', '修改'],
        'delete'   => ['err',   '删除'],
        'backup'   => ['info',  '备份'],
        'restore'  => ['warn',  '恢复'],
        'query'    => ['neutral','查询'],
    ];
    return enumBadge($type, $map);
}

/**
 * 审计对象类型 → 中文徽章
 */
function auditObjBadge($type)
{
    $map = [
        'user'         => ['neutral', '用户'],
        'project'      => ['neutral', '项目'],
        'audit_log'    => ['neutral', '审计日志'],
        'backup'       => ['neutral', '备份'],
        'organization' => ['neutral', '组织'],
    ];
    return enumBadge($type, $map, 'neutral');
}
