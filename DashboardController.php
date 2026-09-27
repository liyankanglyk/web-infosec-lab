<?php
/**
 * 仪表板控制器
 */

namespace Controllers;

class DashboardController
{

    /**
     * 首页
     */
    public function index()
    {
        global $DB;

        if (!isLoggedIn()) {
            redirect('/login');
            return;
        }

        $user = getCurrentUser();

        // 身份回查：用户行已不存在时 getCurrentUser() 返回 false，视为未登录
        if (!$user) {
            redirect('/login');
            return;
        }

        try {
            // 获取统计信息
            $stats = $DB->query("SELECT 
                (SELECT COUNT(*) FROM users WHERE status = 'active') as active_users,
                (SELECT COUNT(*) FROM detection_projects) as total_projects,
                (SELECT COUNT(*) FROM audit_logs) as total_logs,
                (SELECT COUNT(*) FROM backup_records WHERE status = 'completed') as total_backups
            ");

            $recent_projects = $DB->queryAll("
                SELECT id, project_name, status, created_at 
                FROM detection_projects 
                ORDER BY created_at DESC 
                LIMIT 5
            ");

            $recent_logs = $DB->queryAll("
                SELECT operation_time, username, operation_type, operation_desc 
                FROM audit_logs 
                ORDER BY created_at DESC 
                LIMIT 10
            ");

        } catch (\Exception $e) {
            $stats = [];
            $recent_projects = [];
            $recent_logs = [];
        }

        ?>
        <?php
        appShellOpen('工作台', 'dashboard', [['label' => '工作台']]);
        ?>

        <?php $role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : ''; ?>

        <?php pageHeader('总览', '欢迎回来', '当前登录：' . htmlspecialchars(isset($user['real_name']) ? $user['real_name'] : $user['username'])); ?>

        <div class="stats">
            <div class="stat s-brand">
                <div class="s-lbl">活跃用户</div>
                <div class="s-val"><?php echo isset($stats['active_users']) ? $stats['active_users'] : 0; ?></div>
                <div class="s-meta">仅统计启用账号</div>
            </div>
            <div class="stat s-ok">
                <div class="s-lbl">检测项目</div>
                <div class="s-val"><?php echo isset($stats['total_projects']) ? $stats['total_projects'] : 0; ?></div>
                <div class="s-meta">累计创建</div>
            </div>
            <div class="stat s-warn">
                <div class="s-lbl">审计日志</div>
                <div class="s-val"><?php echo isset($stats['total_logs']) ? $stats['total_logs'] : 0; ?></div>
                <div class="s-meta">全量可查条数</div>
            </div>
            <div class="stat s-info">
                <div class="s-lbl">备份记录</div>
                <div class="s-val"><?php echo isset($stats['total_backups']) ? $stats['total_backups'] : 0; ?></div>
                <div class="s-meta">已完成</div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: minmax(0, 2fr) minmax(0, 1fr); gap: 14px; align-items: start;">
            <div class="stack">
                <section class="panel">
                    <div class="panel-head">
                        <div class="panel-title">最近项目</div>
                        <div class="spacer"></div>
                        <?php if (in_array($role, ['super_admin', 'operator'])): ?>
                            <a class="btn btn-sm btn-quiet" href="<?php echo buildUrl('/project/list'); ?>"><?= icon('folder', 'ic ic-sm') ?>进入项目管理</a>
                        <?php endif; ?>
                    </div>
                    <div class="panel-body panel-body-flush">
                        <?php if (count($recent_projects) > 0): ?>
                            <div class="table-wrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>项目名称</th>
                                            <th style="width:110px">状态</th>
                                            <th style="width:120px">创建时间</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($recent_projects as $project): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo buildUrl('/project/edit/' . $project['id']); ?>" class="strong">
                                                    <?php echo htmlspecialchars($project['project_name']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo statusBadge($project['status']); ?></td>
                                            <td class="c-muted mono"><?php echo htmlspecialchars(substr($project['created_at'], 0, 10)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="es-icon"><?= icon('inbox', 'ic ic-lg') ?></div>
                                <div class="es-title">暂无项目</div>
                                <?php if (in_array($role, ['super_admin', 'operator'])): ?>
                                    <a class="btn btn-sm btn-primary" href="<?php echo buildUrl('/project/create'); ?>"><?= icon('plus', 'ic ic-sm') ?>创建新项目</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head">
                        <div class="panel-title">最近操作日志</div>
                        <div class="spacer"></div>
                        <?php if (in_array($role, ['super_admin', 'auditor'])): ?>
                            <a class="btn btn-sm btn-quiet" href="<?php echo buildUrl('/audit/list'); ?>"><?= icon('list', 'ic ic-sm') ?>查看全部</a>
                        <?php endif; ?>
                    </div>
                    <div class="panel-body panel-body-flush">
                        <?php if (count($recent_logs) > 0): ?>
                            <div class="table-wrap">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th style="width:120px">时间</th>
                                            <th style="width:140px">用户</th>
                                            <th>操作</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($recent_logs as $log): ?>
                                        <tr>
                                            <td class="c-muted mono"><?php echo htmlspecialchars(substr($log['operation_time'], 0, 10)); ?></td>
                                            <td class="c-mono"><?php echo htmlspecialchars($log['username']); ?></td>
                                            <td class="truncate"><?php echo htmlspecialchars(substr($log['operation_type'], 0, 20)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="es-icon"><?= icon('inbox', 'ic ic-lg') ?></div>
                                <div class="es-title">暂无操作日志</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </div>

            <div class="stack">
                <section class="panel">
                    <div class="panel-head"><div class="panel-title">快捷入口</div></div>
                    <div class="panel-body" style="display:grid;gap:6px;">
                        <?php if (in_array($role, ['super_admin', 'operator'])): ?>
                            <a class="btn btn-quiet" style="justify-content:flex-start" href="<?php echo buildUrl('/project/list'); ?>"><?= icon('folder', 'ic ic-sm') ?>项目管理</a>
                        <?php endif; ?>
                        <?php if (in_array($role, ['super_admin', 'admin'])): ?>
                            <a class="btn btn-quiet" style="justify-content:flex-start" href="<?php echo buildUrl('/user/list'); ?>"><?= icon('users', 'ic ic-sm') ?>用户管理</a>
                        <?php endif; ?>
                        <?php if (in_array($role, ['super_admin', 'auditor'])): ?>
                            <a class="btn btn-quiet" style="justify-content:flex-start" href="<?php echo buildUrl('/audit/list'); ?>"><?= icon('list', 'ic ic-sm') ?>审计日志</a>
                        <?php endif; ?>
                        <?php if (in_array($role, ['super_admin', 'admin'])): ?>
                            <a class="btn btn-quiet" style="justify-content:flex-start" href="<?php echo buildUrl('/backup/list'); ?>"><?= icon('database', 'ic ic-sm') ?>备份管理</a>
                        <?php endif; ?>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head"><div class="panel-title">当前会话</div></div>
                    <div class="panel-body">
                        <div class="kv"><span class="k">姓名</span><span class="v"><?php echo htmlspecialchars(!empty($user['real_name']) ? $user['real_name'] : $user['username']); ?></span></div>
                        <div class="kv"><span class="k">用户名</span><span class="v mono"><?php echo htmlspecialchars($user['username']); ?></span></div>
                        <?php
                        // 角色显示名查库；查不到时回落到会话中的角色快照
                        $roleName = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
                        try {
                            $roleRow = $DB->query("SELECT display_name FROM roles WHERE role_name = ?", [$roleName]);
                        } catch (\Exception $e) {
                            $roleRow = false;
                        }
                        ?>
                        <div class="kv"><span class="k">角色</span><span class="v"><?php echo ($roleRow && !empty($roleRow['display_name'])) ? htmlspecialchars($roleRow['display_name']) : ($roleName !== '' ? htmlspecialchars($roleName) : '未分配'); ?></span></div>
                        <div class="kv"><span class="k">登录 IP</span><span class="v mono"><?php echo htmlspecialchars(!empty($user['last_login_ip']) ? $user['last_login_ip'] : '-'); ?></span></div>
                    </div>
                </section>

                <section class="panel">
                    <div class="panel-head"><div class="panel-title">帮助与文档</div></div>
                    <div class="panel-body" style="display:grid;gap:6px;">
                        <a class="btn btn-ghost" style="justify-content:flex-start" href="<?php echo buildUrl('/help/faq'); ?>"><?= icon('help', 'ic ic-sm') ?>常见问题</a>
                        <a class="btn btn-ghost" style="justify-content:flex-start" href="<?php echo buildUrl('/help/manual'); ?>"><?= icon('list', 'ic ic-sm') ?>用户手册</a>
                        <a class="btn btn-ghost" style="justify-content:flex-start" href="<?php echo buildUrl('/help/security'); ?>"><?= icon('shield', 'ic ic-sm') ?>安全指南</a>
                        <a class="btn btn-ghost" style="justify-content:flex-start" href="<?php echo buildUrl('/help/settings'); ?>"><?= icon('settings', 'ic ic-sm') ?>系统设置</a>
                    </div>
                </section>
            </div>
        </div>

        <?php
        appShellClose();
    }
}

?>