<?php
/**
 * 备份管理控制器
 * 包含缺陷: DEF012, DEF013, DEF021, DEF025
 */

namespace Controllers;

class BackupController
{

    /**
     * 备份列表
     */
    public function index()
    {
        global $DB;

        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['super_admin', 'admin'])) {
            die('403: 无权限访问');
        }

        try {
            $bpage = isset($_GET['bpage']) ? max(1, intval($_GET['bpage'])) : 1;
            $perPage = 10;
            $btotal = $DB->query("SELECT COUNT(*) as count FROM backup_records");
            $btotalPages = max(1, ceil($btotal['count'] / $perPage));
            $boffset = ($bpage - 1) * $perPage;
            $backups = $DB->queryAll("
                SELECT * FROM backup_records 
                ORDER BY created_at DESC 
                LIMIT $perPage OFFSET $boffset
            ");

            $rpage = isset($_GET['rpage']) ? max(1, intval($_GET['rpage'])) : 1;
            $rtotal = $DB->query("SELECT COUNT(*) as count FROM restore_records");
            $rtotalPages = max(1, ceil($rtotal['count'] / $perPage));
            $roffset = ($rpage - 1) * $perPage;
            $restores = $DB->queryAll("
                SELECT * FROM restore_records 
                ORDER BY created_at DESC 
                LIMIT $perPage OFFSET $roffset
            ");
        } catch (Exception $e) {
            die('查询失败: ' . $e->getMessage());
        }

        ?>
        <?php
        appShellOpen('备份管理', 'backup', [['label' => '治理'], ['label' => '备份与恢复']]);
        ?>

        <?php pageHeader('治理', '备份与恢复', '数据库备份台账与恢复记录'); ?>

        <div class="toolbar">
            <div class="tb-group">
                <a class="switch <?= (isset($_SESSION['auto_backup_enabled']) && $_SESSION['auto_backup_enabled']) ? 'is-on' : 'is-off' ?>"
                   href="<?php echo buildUrl('/backup/toggleauto'); ?>">自动备份(每60分钟): <?php echo (isset($_SESSION['auto_backup_enabled']) && $_SESSION['auto_backup_enabled']) ? '已开启' : '已关闭'; ?></a>
            </div>
            <div class="tb-spacer"></div>
            <div class="tb-group">
                <a class="btn btn-quiet btn-sm" href="<?php echo buildUrl('/dashboard'); ?>"><?= icon('back', 'ic ic-sm') ?>返回工作台</a>
                <button class="btn btn-sm btn-primary" onclick="manualBackup()"><?= icon('database', 'ic ic-sm') ?>执行手动备份</button>
            </div>
        </div>

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">备份记录</div>
                <div class="panel-sub">共 <b class="mono"><?php echo $btotal['count']; ?></b> 条</div>
            </div>
            <div class="panel-body panel-body-flush">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:80px">备份 ID</th>
                                <th style="width:190px">备份时间</th>
                                <th style="width:120px">大小</th>
                                <th style="width:120px">类型</th>
                                <th style="width:120px">状态</th>
                                <th style="width:160px" class="c-actions">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($backups) == 0): ?>
                            <tr><td class="empty" colspan="6">暂无备份记录</td></tr>
                        <?php else: ?>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td class="c-num"><?php echo htmlspecialchars($backup['id']); ?></td>
                                    <td class="c-muted mono"><?php echo htmlspecialchars($backup['created_at']); ?></td>
                                    <td class="c-num"><?php echo htmlspecialchars(isset($backup['file_size']) ? $backup['file_size'] : '未知'); ?> <span class="c-muted">KB</span></td>
                                    <td><?php echo statusBadge($backup['backup_type']); ?></td>
                                    <td><?php echo statusBadge($backup['status']); ?></td>
                                    <td class="c-actions">
                                        <button class="btn btn-sm btn-info" onclick="restoreBackup(<?php echo $backup['id']; ?>)"><?= icon('restore', 'ic ic-sm') ?>恢复</button>
                                        <button class="btn btn-sm btn-quiet" onclick="downloadBackup(<?php echo $backup['id']; ?>)"><?= icon('download', 'ic ic-sm') ?>下载</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($bpage, $btotalPages, 'bpage'); ?>
            </div>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">恢复记录</div>
                <div class="panel-sub">共 <b class="mono"><?php echo $rtotal['count']; ?></b> 条</div>
            </div>
            <div class="panel-body panel-body-flush">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:80px">恢复 ID</th>
                                <th style="width:190px">恢复时间</th>
                                <th style="width:100px">备份 ID</th>
                                <th style="width:100px">操作者</th>
                                <th style="width:120px">状态</th>
                                <th>说明</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (count($restores) == 0): ?>
                            <tr><td class="empty" colspan="6">暂无恢复记录</td></tr>
                        <?php else: ?>
                            <?php foreach ($restores as $restore): ?>
                                <tr>
                                    <td class="c-num"><?php echo htmlspecialchars($restore['id']); ?></td>
                                    <td class="c-muted mono"><?php echo htmlspecialchars($restore['created_at']); ?></td>
                                    <td class="c-num"><?php echo htmlspecialchars($restore['backup_id']); ?></td>
                                    <td class="c-num"><?php echo htmlspecialchars($restore['restored_by']); ?></td>
                                    <td><?php echo statusBadge($restore['restore_status']); ?></td>
                                    <td class="c-muted truncate">
                                        <?php
                                        echo htmlspecialchars(isset($restore['error_message']) ? $restore['error_message'] : '');
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($rpage, $rtotalPages, 'rpage'); ?>
            </div>
        </section>

        <script>
            function manualBackup() {
                if (confirm('执行手动备份？此操作可能需要几分钟...')) {
                    location.href = '<?php echo buildUrl('/backup/create'); ?>';
                }
            }

            function restoreBackup(id) {
                if (confirm('确定要恢复此备份吗？')) {
                    location.href = '<?php echo buildUrl('/backup/restore/'); ?>' + id;
                }
            }

            function downloadBackup(id) {
                location.href = '<?php echo buildUrl('/backup/download/'); ?>' + id;
            }
        </script>

        <?php
        appShellClose();
    }

    /**
     * 执行手动备份
     * 缺陷位置: DEF021
     */
    public function create()
    {
        global $DB;

        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['super_admin', 'admin'])) {
            die('403: 无权限访问');
        }

        try {
            $backup_file = BASE_PATH . '/backups/backup_' . date('YmdHis') . '.sql';

            // DEF012: 备份失败被静默处理
            // 正常应该检查执行结果
            $dbConfig = require BASE_PATH . '/config_database.php';
            $db = $dbConfig['database'];
            $passArg = !empty($db['password']) ? ' -p' . escapeshellarg($db['password']) : '';

            $mysqldump = 'mysqldump';
            $envRoot = dirname(dirname(BASE_PATH));
            if (file_exists($envRoot . '\\mysql\\bin\\mysqldump.exe')) {
                $mysqldump = $envRoot . '\\mysql\\bin\\mysqldump';
            }

            $command = sprintf(
                "%s -h %s -P %s -u %s%s %s 2>&1",
                $mysqldump,
                escapeshellarg($db['host']),
                escapeshellarg($db['port']),
                escapeshellarg($db['user']),
                $passArg,
                escapeshellarg($db['database'])
            );

            $output = '';
            if (function_exists('exec')) {
                $lines = [];
                @exec($command, $lines);
                $output = implode("\n", $lines);
            } elseif (function_exists('shell_exec')) {
                $result = @shell_exec($command);
                $output = $result !== null ? $result : '';
            }
            file_put_contents($backup_file, $output);

            // DEF021: 无自动备份机制，也不记录备份事件
            $DB->insert('backup_records', [
                'backup_name' => '手动备份 ' . date('Y-m-d H:i:s'),
                'file_path' => $backup_file,
                'file_size' => file_exists($backup_file) ? filesize($backup_file) : 0,
                'backup_type' => 'manual',
                'backup_time' => date('Y-m-d H:i:s'),
                'status' => 'completed',
                'created_by' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            auditLog('BACKUP', 'backup', $DB->getLastInsertId(), '手动备份数据库');

            redirect('/backup/list?success=备份已完成');
        } catch (Exception $e) {
            die('备份失败: ' . $e->getMessage());
        }
    }

    /**
     * 恢复备份
     * 缺陷位置: DEF013, DEF025
     */
    public function restore($id = null)
    {
        global $DB;

        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['super_admin', 'admin'])) {
            die('403: 无权限访问');
        }

        $id = intval(isset($id) ? $id : 0);

        if ($id <= 0) {
            die('备份ID无效');
        }

        try {
            $backup = $DB->query("SELECT * FROM backup_records WHERE id = ?", [$id]);

            if (!$backup || !file_exists($backup['file_path'])) {
                die('备份文件不存在');
            }

            // DEF013: 恢复时丢失最后10条记录
            // 执行恢复但不删除最后10条插入的记录
            $dbConfig = require BASE_PATH . '/config_database.php';
            $db = $dbConfig['database'];
            $passArg = !empty($db['password']) ? ' -p' . escapeshellarg($db['password']) : '';

            $mysql = 'mysql';
            $envRoot = dirname(dirname(BASE_PATH));
            if (file_exists($envRoot . '\\mysql\\bin\\mysql.exe')) {
                $mysql = $envRoot . '\\mysql\\bin\\mysql';
            }

            $command = sprintf(
                "%s -h %s -P %s -u %s%s %s < %s 2>&1",
                $mysql,
                escapeshellarg($db['host']),
                escapeshellarg($db['port']),
                escapeshellarg($db['user']),
                $passArg,
                escapeshellarg($db['database']),
                escapeshellarg($backup['file_path'])
            );

            // 恢复前保存当前 backup_records，防止被备份文件覆盖
            $savedBackups = $DB->queryAll("SELECT * FROM backup_records");

            $error = '';
            if (function_exists('exec')) {
                $lines = [];
                @exec($command, $lines);
                $error = implode("\n", $lines);
            } elseif (function_exists('shell_exec')) {
                $error = @shell_exec($command);
            }

            // DEF025: 恢复后不进行验证
            // 正常应该检查恢复是否成功

            // 恢复后还原 backup_records 表，防止被备份文件覆盖
            if (!empty($savedBackups)) {
                $DB->execute("SET FOREIGN_KEY_CHECKS = 0");
                $DB->execute("TRUNCATE TABLE backup_records");
                foreach ($savedBackups as $b) {
                    $DB->insert('backup_records', $b);
                }
                $DB->execute("SET FOREIGN_KEY_CHECKS = 1");
            }

            // 删除最后10条检测项目记录（模拟DEF013）
            $recent = $DB->queryAll("
                SELECT id FROM detection_projects 
                ORDER BY id DESC 
                LIMIT 10
            ");

            foreach ($recent as $project) {
                $DB->delete('detection_projects', ['id' => $project['id']]);
            }

            $DB->insert('restore_records', [
                'backup_id' => $id,
                'restore_time' => date('Y-m-d H:i:s'),
                'restored_by' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1,
                'restore_status' => 'success',
                'affected_records' => 10,  // DEF013: 丢失记录数
                'error_message' => '恢复完成',
                'created_at' => date('Y-m-d H:i:s')
            ]);

            auditLog('RESTORE', 'backup', $id, '恢复备份');

            redirect('/backup/list?success=备份已恢复');
        } catch (Exception $e) {
            die('恢复失败: ' . $e->getMessage());
        }
    }

    /**
     * 切换自动备份状态
     * 缺陷: 状态可开启，但实际不会执行自动备份
     */
    public function toggleauto()
    {
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['super_admin', 'admin'])) {
            die('403: 无权限访问');
        }
        $_SESSION['auto_backup_enabled'] = isset($_SESSION['auto_backup_enabled']) ? !$_SESSION['auto_backup_enabled'] : true;
        redirect('/backup/list');
    }

    /**
     * 下载备份文件
     */
    public function download($id = null)
    {
        global $DB;

        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['super_admin', 'admin'])) {
            die('403: 无权限访问');
        }

        $id = intval(isset($id) ? $id : 0);

        if ($id <= 0) {
            die('备份ID无效');
        }

        try {
            $backup = $DB->query("SELECT * FROM backup_records WHERE id = ?", [$id]);

            if (!$backup || !file_exists($backup['file_path'])) {
                die('备份文件不存在');
            }

            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($backup['file_path']) . '"');
            header('Content-Length: ' . filesize($backup['file_path']));

            readfile($backup['file_path']);
            exit;
        } catch (Exception $e) {
            die('下载失败: ' . $e->getMessage());
        }
    }
}

?>