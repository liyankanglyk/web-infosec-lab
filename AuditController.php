<?php
/**
 * 审计日志控制器
 * 包含缺陷: DEF016, DEF017, DEF018, DEF019, DEF020, DEF021, DEF023
 */

namespace Controllers;

class AuditController
{

    /**
     * 审计日志列表
     * 缺陷位置: DEF016-019
     */
    public function index()
    {
        global $DB;

        // DEF016, DEF017: 删除操作可能不纳入审计
        // DEF018: 客户端IP通常为空
        // DEF019: 时间仅精确到日期

        try {
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = 10;
            $total = $DB->query("SELECT COUNT(*) as count FROM audit_logs");
            $totalPages = max(1, ceil($total['count'] / $perPage));
            $offset = ($page - 1) * $perPage;
            $logs = $DB->queryAll("
                SELECT * FROM audit_logs 
                ORDER BY id DESC 
                LIMIT $perPage OFFSET $offset
            ");
        } catch (Exception $e) {
            die('查询失败: ' . $e->getMessage());
        }

        ?>
        <?php
        appShellOpen('审计日志', 'audit', [['label' => '治理'], ['label' => '审计日志']]);
        $isSuper = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['super_admin', 'auditor']);
        ?>

        <?php pageHeader('治理', '审计日志', '操作行为记录与备份台账'); ?>

        <div class="toolbar">
            <div class="tb-group">
                <span class="tb-meta">共 <b><?php echo $total['count']; ?></b> 条记录</span>
            </div>
            <div class="tb-spacer"></div>
            <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['super_admin', 'auditor'])): ?>
            <div class="tb-group">
                <a class="switch <?= (isset($_SESSION['audit_disabled']) && $_SESSION['audit_disabled']) ? 'is-off' : 'is-on' ?>"
                   href="<?php echo buildUrl('/audit/toggleaudit'); ?>">审计日志: <?php echo (isset($_SESSION['audit_disabled']) && $_SESSION['audit_disabled']) ? '已关闭' : '已开启'; ?></a>
                <a class="switch <?= (isset($_SESSION['audit_backup_enabled']) && $_SESSION['audit_backup_enabled']) ? 'is-info' : 'is-off' ?>"
                   href="<?php echo buildUrl('/audit/toggleauditbackup'); ?>">审计日志备份(每小时): <?php echo (isset($_SESSION['audit_backup_enabled']) && $_SESSION['audit_backup_enabled']) ? '已开启' : '已关闭'; ?></a>
            </div>
            <?php endif; ?>
            <div class="tb-sep"></div>
            <a class="btn btn-quiet btn-sm" href="<?php echo buildUrl('/dashboard'); ?>"><?= icon('back', 'ic ic-sm') ?>返回工作台</a>
        </div>

        <?php if ($isSuper): ?><form id="batch-form" method="POST" action="<?php echo buildUrl('/audit/batchdelete'); ?>" style="margin:0">
        <div class="toolbar" style="margin-bottom:10px">
            <div class="tb-group">
                <button type="button" class="btn btn-sm btn-danger-quiet" onclick="batchDelete()"><?= icon('trash', 'ic ic-sm') ?>批量删除</button>
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteAllLogs()"><?= icon('alert', 'ic ic-sm') ?>删除所有日志</button>
            </div>
        </div>
        <?php endif; ?>

        <section class="panel">
            <div class="panel-body panel-body-flush">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <?php if ($isSuper): ?><th class="c-check"><input type="checkbox" id="select-all" onclick="toggleAll(this)"></th><?php endif; ?>
                                <th style="width:110px">时间</th>
                                <th style="width:120px">用户</th>
                                <th style="width:110px">操作类型</th>
                                <th style="width:110px">对象类型</th>
                                <th>操作描述</th>
                                <th style="width:130px">客户端 IP</th>
                                <th style="width:90px">结果</th>
                                <?php if ($isSuper): ?><th style="width:80px" class="c-actions">操作</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($logs) == 0): ?>
                            <tr>
                                <td class="empty" colspan="<?php echo $isSuper ? '9' : '7'; ?>">
                                    暂无日志记录
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <?php if ($isSuper): ?><td class="c-check"><input type="checkbox" name="ids[]" value="<?php echo $log['id']; ?>"></td><?php endif; ?>
                                    <td class="c-muted mono"><?php echo htmlspecialchars($log['operation_time']); ?></td>
                                    <td class="c-mono"><?php echo htmlspecialchars(isset($log['username']) ? $log['username'] : 'unknown'); ?></td>
                                    <td><?php echo auditOpBadge($log['operation_type']); ?></td>
                                    <td><?php echo auditObjBadge(isset($log['object_type']) ? $log['object_type'] : ''); ?></td>
                                    <td class="truncate"><?php echo htmlspecialchars(substr(isset($log['operation_desc']) ? $log['operation_desc'] : '', 0, 50)); ?></td>
                                    <td>
                                        <span class="c-muted mono"><?php echo empty($log['client_ip']) ? '(空)' : htmlspecialchars($log['client_ip']); ?></span>
                                    </td>
                                    <td><?php echo statusBadge($log['result']); ?></td>
                                    <?php if ($isSuper): ?><td class="c-actions"><button class="btn btn-sm btn-danger-quiet" onclick="if(confirm('确定删除该日志吗？')) location.href='<?php echo buildUrl('/audit/delete/' . $log['id']); ?>'"><?= icon('trash', 'ic ic-sm') ?>删除</button></td><?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
        <?php if ($isSuper): ?></form><?php endif; ?>

        <?php renderPagination($page, $totalPages); ?>

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">审计备份记录</div>
                <div class="panel-sub">自动任务未启用</div>
            </div>
            <div class="panel-body panel-body-flush">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:80px">备份 ID</th>
                                <th style="width:180px">备份时间</th>
                                <th style="width:120px">大小</th>
                                <th style="width:120px">类型</th>
                                <th style="width:120px">状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="empty" colspan="5">暂无备份记录</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <script>
            function deleteAllLogs() {
                if (confirm('确定要删除所有审计日志吗？此操作不可撤销！')) {
                    location.href = '/audit/deleteall';
                }
            }

            function toggleAll(source) {
                var checkboxes = document.querySelectorAll('input[name="ids[]"]');
                for (var i = 0; i < checkboxes.length; i++) {
                    checkboxes[i].checked = source.checked;
                }
            }

            function batchDelete() {
                var checked = document.querySelectorAll('input[name="ids[]"]:checked');
                if (checked.length == 0) {
                    alert('请选择要删除的日志');
                    return;
                }
                if (confirm('确定删除选中的 ' + checked.length + ' 条日志吗？')) {
                    document.getElementById('batch-form').submit();
                }
            }
        </script>

        <?php
        appShellClose();
    }

    /**
     * 删除所有日志
     * 缺陷位置: DEF020
     */
    public function deleteall()
    {
        global $DB;

        // DEF020: 超级管理员可以删除审计日志（应该禁止）

        if (!in_array($_SESSION['user_role'], ['super_admin', 'auditor'])) {
            die('403: 仅超级管理员可操作');
        }

        try {
            $DB->execute("TRUNCATE TABLE audit_logs");
            auditLog('DELETE', 'audit_log', 0, '清除所有审计日志');
            redirect('/audit/list?success=所有日志已删除');
        } catch (Exception $e) {
            die('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除特定日志
     * 缺陷位置: DEF020
     */
    public function delete($id = null)
    {
        global $DB;

        if (!in_array($_SESSION['user_role'], ['super_admin', 'auditor'])) {
            die('403: 仅超级管理员可操作');
        }

        $id = intval(isset($id) ? $id : 0);

        if ($id <= 0) {
            die('日志ID无效');
        }

        try {
            $DB->delete('audit_logs', ['id' => $id]);
            auditLog('DELETE', 'audit_log', $id, '删除审计日志');
            redirect('/audit/list?success=日志已删除');
        } catch (Exception $e) {
            die('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 导出日志
     */
    public function export()
    {
        global $DB;

        try {
            $logs = $DB->queryAll("
                SELECT * FROM audit_logs 
                ORDER BY id DESC 
                LIMIT 1000
            ");
        } catch (Exception $e) {
            die('查询失败: ' . $e->getMessage());
        }

        // 生成CSV文件
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="audit_logs_' . date('YmdHis') . '.csv"');

        // 写入BOM以支持Excel中文显示
        echo "\xEF\xBB\xBF";

        // 写入标题
        echo "时间,用户,操作类型,对象类型,操作描述,客户端IP,结果\n";

        // 写入数据
        foreach ($logs as $log) {
            $row = [
                $log['operation_time'],
                isset($log['username']) ? $log['username'] : 'unknown',
                $log['operation_type'],
                isset($log['object_type']) ? $log['object_type'] : '-',
                isset($log['operation_desc']) ? $log['operation_desc'] : '',
                isset($log['client_ip']) ? $log['client_ip'] : '',
                $log['result']
            ];

            echo implode(',', array_map(function ($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row)) . "\n";
        }

        exit;
    }

    /**
     * 批量删除日志
     */
    public function batchdelete()
    {
        global $DB;

        if (!in_array($_SESSION['user_role'], ['super_admin', 'auditor'])) {
            die('403: 仅超级管理员可操作');
        }

        $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
        if (empty($ids)) {
            redirect('/audit/list?error=未选择任何日志');
        }

        try {
            foreach ($ids as $id) {
                $DB->delete('audit_logs', ['id' => intval($id)]);
            }
            auditLog('DELETE', 'audit_log', 0, '批量删除审计日志: ' . count($ids) . ' 条');
            redirect('/audit/list?success=已删除 ' . count($ids) . ' 条日志');
        } catch (Exception $e) {
            die('批量删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 切换审计日志开启/关闭
     * 缺陷位置: DEF023 - 审计进程无保护，auditor（非最高权限）亦可关闭全局审计，无二次确认、无关闭行为留痕
     */
    public function toggleaudit()
    {
        global $DB;
        if (!in_array($_SESSION['user_role'], ['super_admin', 'auditor'])) {
            die('403: 仅超级管理员可操作');
        }
        $disabled = isset($_SESSION['audit_disabled']) ? !$_SESSION['audit_disabled'] : true;
        $_SESSION['audit_disabled'] = $disabled;
        $DB->update('audit_config', ['config_value' => $disabled ? '0' : '1'], ['config_key' => 'enable_audit']);
        redirect('/audit/list');
    }

    /**
     * 切换审计日志备份状态
     * DEF021: 状态可开启，但实际不会执行备份（仅翻转会话标志）
     */
    public function toggleauditbackup()
    {
        if (!in_array($_SESSION['user_role'], ['super_admin', 'auditor'])) {
            die('403: 仅超级管理员可操作');
        }
        $_SESSION['audit_backup_enabled'] = isset($_SESSION['audit_backup_enabled']) ? !$_SESSION['audit_backup_enabled'] : true;
        redirect('/audit/list');
    }
}

?>