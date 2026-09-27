<?php
/**
 * 平台管理控制器
 * 管理所有检测组织（多租户），包括组织的创建、编辑、删除、初始化等
 */
namespace Controllers;

class PlatformController
{
    // ============================================================
    // 首页仪表板
    // ============================================================
    public function dashboard()
    {
        global $DB;
        $this->requirePlatformLogin();

        $orgs = $DB->queryAll("SELECT * FROM organizations ORDER BY FIELD(status, 'active','inactive'), created_at DESC");
        $activeCount = $DB->query("SELECT COUNT(*) as cnt FROM organizations WHERE status = 'active'");
        $inactiveCount = $DB->query("SELECT COUNT(*) as cnt FROM organizations WHERE status = 'inactive'");

        $activeNum = $activeCount ? $activeCount['cnt'] : 0;
        $inactiveNum = $inactiveCount ? $inactiveCount['cnt'] : 0;

        ?>
        <?php
        platformShellOpen('平台总览', 'dashboard', [['label' => '平台'], ['label' => '总览']]);
        ?>

                <?php if (isset($_SESSION['platform_msg'])): ?>
                    <?php flashMessage(
                        isset($_SESSION['platform_msg_type']) ? $_SESSION['platform_msg_type'] : 'success',
                        $_SESSION['platform_msg'],
                        true
                    ); ?>
                    <?php unset($_SESSION['platform_msg'], $_SESSION['platform_msg_type']); ?>
                <?php endif; ?>

                <div class="stats">
                    <div class="stat s-ok">
                        <div class="s-lbl">活跃组织</div>
                        <div class="s-val"><?php echo $activeNum; ?></div>
                        <div class="s-meta">可登录实例</div>
                    </div>
                    <div class="stat s-warn">
                        <div class="s-lbl">已停用</div>
                        <div class="s-val"><?php echo $inactiveNum; ?></div>
                        <div class="s-meta">MySQL 用户已锁</div>
                    </div>
                    <div class="stat s-brand">
                        <div class="s-lbl">组总数</div>
                        <div class="s-val"><?php echo count($orgs); ?></div>
                        <div class="s-meta">包含停用</div>
                    </div>
                </div>

                <section class="panel">
                    <div class="panel-head">
                        <div class="panel-title">组织列表</div>
                        <div class="spacer"></div>
                        <a href="<?php echo buildUrl('/organizations/create'); ?>" class="btn btn-sm btn-primary"><?= icon('plus', 'ic ic-sm') ?>新增组织</a>
                    </div>
                    <div class="panel-body panel-body-flush">
                        <?php if (empty($orgs)): ?>
                            <div class="empty-state">
                                <div class="es-icon"><?= icon('server', 'ic ic-lg') ?></div>
                                <div class="es-title">暂无组织</div>
                                <div class="es-sub">点击上方“新增组织”开始创建</div>
                            </div>
                        <?php else: ?>
                        <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="width:140px">代号</th>
                                    <th>组织名称</th>
                                    <th style="width:200px">数据库</th>
                                    <th style="width:110px">状态</th>
                                    <th style="width:170px">创建时间</th>
                                    <th style="width:260px" class="c-actions">操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orgs as $org): ?>
                                <tr>
                                    <td><span class="code"><?php echo htmlspecialchars($org['org_code']); ?></span></td>
                                    <td class="c-primary"><?php echo htmlspecialchars($org['org_name']); ?></td>
                                    <td><span class="c-mono"><?php echo htmlspecialchars($org['db_name']); ?></span></td>
                                    <td><?php echo statusBadge($org['status'], ['active' => '活跃', 'inactive' => '停用']); ?></td>
                                    <td class="c-muted mono"><?php echo htmlspecialchars($org['created_at']); ?></td>
                                    <td class="c-actions">
                                        <a href="<?php echo buildUrl('/organizations/view/' . $org['id']); ?>" class="btn btn-sm btn-quiet">详情</a>
                                        <a href="<?php echo buildUrl('/organizations/edit/' . $org['id']); ?>" class="btn btn-sm btn-quiet"><?= icon('edit', 'ic ic-sm') ?>编辑</a>
                                        <?php if ($org['status'] == 'active'): ?>
                                        <a href="<?php echo buildUrl('/organizations/toggle/' . $org['id']); ?>" class="btn btn-sm btn-warn" onclick="return confirm('确定停用该组织？停用后组织和数据库直连都将被禁止')">停用</a>
                                        <?php else: ?>
                                        <a href="<?php echo buildUrl('/organizations/toggle/' . $org['id']); ?>" class="btn btn-sm btn-quiet">启用</a>
                                        <?php endif; ?>
                                        <a href="<?php echo buildUrl('/organizations/delete/' . $org['id']); ?>" class="btn btn-sm btn-danger-quiet" onclick="return confirm('⚠ 确定删除该组织？将永久删除数据库、MySQL用户及所有数据，不可恢复！')"><?= icon('trash', 'ic ic-sm') ?>删除</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </section>

        <?php
        platformShellClose();
    }

    // ============================================================
    // 组织列表 / 子操作路由
    // ============================================================
    public function organizations()
    {
        $args = func_get_args();
        $subAction = isset($args[0]) ? $args[0] : 'list';

        switch ($subAction) {
            case 'create': $this->orgCreate(); break;
            case 'edit':   $this->orgEdit(isset($args[1]) ? $args[1] : null); break;
            case 'view':   $this->orgView(isset($args[1]) ? $args[1] : null); break;
            case 'delete': $this->orgDelete(isset($args[1]) ? $args[1] : null); break;
            case 'toggle': $this->orgToggle(isset($args[1]) ? $args[1] : null); break;
            case 'init':   $this->orgInit(isset($args[1]) ? $args[1] : null); break;
            default:       $this->dashboard(); break;
        }
    }

    // ============================================================
    // 创建组织
    // ============================================================
    private function orgCreate()
    {
        global $DB;
        $this->requirePlatformLogin();

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orgCode = trim(isset($_POST['org_code']) ? $_POST['org_code'] : '');
            $orgName = trim(isset($_POST['org_name']) ? $_POST['org_name'] : '');
            $description = trim(isset($_POST['description']) ? $_POST['description'] : '');

            // 验证
            if (empty($orgCode) || empty($orgName)) {
                $error = '组织代号和名称不能为空';
            } elseif (!preg_match('/^[a-z0-9_-]+$/i', $orgCode)) {
                $error = '组织代号只能包含字母、数字、下划线和连字符';
            } elseif (strtolower($orgCode) === 'platform') {
                $error = 'platform 是系统保留字，不能使用';
            } else {
                // 检查是否已存在
                $exist = $DB->query("SELECT id FROM organizations WHERE org_code = ?", [$orgCode]);
                if ($exist) {
                    $error = '组织代号已存在';
                } else {
                    $result = $this->createOrganization($orgCode, $orgName, $description);
                    if ($result['success']) {
                        $this->auditPlatformLog('create_org', 'organization', $result['org_id'], "创建组织: {$orgCode} - {$orgName}");
                        $_SESSION['platform_msg'] = "组织创建成功！<br>数据库: {$result['db_name']}<br>MySQL用户: {$result['db_user']}<br>密码: {$result['db_password']}";
                        $_SESSION['platform_msg_type'] = 'success';
                        redirect('/dashboard');
                        return;
                    } else {
                        $error = $result['error'];
                    }
                }
            }
        }

        ?>
        <?php
        platformShellOpen('新增组织', 'create', [['label' => ['url' => buildUrl('/dashboard'), 'label' => '平台总览']], ['label' => '新增组织']]);
        ?>
            <div class="panel" style="max-width:640px">
                <div class="panel-head">
                    <div class="panel-title">新增组织</div>
                    <div class="spacer"></div>
                    <a href="<?= buildUrl('/dashboard') ?>" class="btn btn-sm btn-quiet"><?= icon('back', 'ic ic-sm') ?>返回</a>
                </div>
                <div class="panel-body">
                <div class="alert alert-info">
                    <?= icon('info') ?>
                    <div class="alert-body">
                        <strong>创建组织将自动执行：</strong><br>
                        ① 创建独立数据库 <span class="code">websec_org_{代号}</span><br>
                        ② 创建独立 MySQL 用户并授权<br>
                        ③ 导入完整系统表结构和初始数据<br>
                        ④ 生成随机密码，请妥善保存
                    </div>
                </div>
                <?php if ($error): ?>
                    <?php flashMessage('error', $error); ?>
                <?php endif; ?>
                <form method="POST" class="form">
                    <div class="form-grid">
                        <div class="field wide">
                            <label class="lbl">组织代号 <span class="req">*</span></label>
                            <input class="input mono" type="text" name="org_code" required placeholder="例如：bj_lab、sh_detection" pattern="[a-zA-Z0-9_-]+">
                            <div class="hint">用于 URL 路径和数据库名，创建后不可修改</div>
                        </div>
                        <div class="field wide">
                            <label class="lbl">组织全称 <span class="req">*</span></label>
                            <input class="input" type="text" name="org_name" required placeholder="例如：北京XX检测实验室">
                        </div>
                        <div class="field wide">
                            <label class="lbl">备注</label>
                            <textarea class="textarea" name="description" rows="3" placeholder="可选备注信息"></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?= icon('check', 'ic ic-sm') ?>创建组织</button>
                        <a href="<?= buildUrl('/dashboard') ?>" class="btn btn-quiet">取消</a>
                    </div>
                </form>
                </div>
            </div>
        <?php
        platformShellClose();
    }

    // ============================================================
    // 查看组织详情
    // ============================================================
    private function orgView($id)
    {
        global $DB;
        $this->requirePlatformLogin();

        $org = $DB->query("SELECT * FROM organizations WHERE id = ?", [$id]);
        if (!$org) {
            die('组织不存在');
        }

        ?>
        <?php
        platformShellOpen('组织详情', '', [['label' => ['url' => buildUrl('/dashboard'), 'label' => '平台总览']], ['label' => $org['org_code']]]);
        ?>
            <div class="panel" style="max-width:760px">
                <div class="panel-head">
                    <div class="panel-title">组织详情 · <span class="code"><?php echo htmlspecialchars($org['org_code']); ?></span></div>
                    <div class="spacer"></div>
                    <a href="<?= buildUrl('/dashboard') ?>" class="btn btn-sm btn-quiet"><?= icon('back', 'ic ic-sm') ?>返回</a>
                </div>
                <div class="panel-body">
                    <dl class="dl">
                        <dt>代号</dt>
                        <dd><span class="code"><?php echo htmlspecialchars($org['org_code']); ?></span></dd>
                        <dt>全称</dt>
                        <dd><?php echo htmlspecialchars($org['org_name']); ?></dd>
                        <dt>状态</dt>
                        <dd><?php echo statusBadge($org['status'], ['active' => '活跃', 'inactive' => '停用']); ?></dd>
                        <dt>备注</dt>
                        <dd><?php echo htmlspecialchars(isset($org['description']) ? $org['description'] : '-'); ?></dd>
                        <dt>创建时间</dt>
                        <dd class="mono"><?php echo htmlspecialchars($org['created_at']); ?></dd>
                        <dt>更新时间</dt>
                        <dd class="mono"><?php echo htmlspecialchars($org['updated_at']); ?></dd>
                    </dl>

                    <div class="alert alert-info" style="margin-top:20px">
                        <?= icon('key') ?>
                        <div class="alert-body">
                            <div class="strong" style="margin-bottom:6px">数据库连接信息 · 交付给组织技术负责人</div>
                            <div class="kv"><span class="k">主机</span><span class="v"><span class="code"><?php echo htmlspecialchars($org['db_host']); ?></span></span></div>
                            <div class="kv"><span class="k">端口</span><span class="v"><span class="code"><?php echo htmlspecialchars($org['db_port']); ?></span></span></div>
                            <div class="kv"><span class="k">数据库</span><span class="v"><span class="code"><?php echo htmlspecialchars($org['db_name']); ?></span></span></div>
                            <div class="kv"><span class="k">用户</span><span class="v"><span class="code"><?php echo htmlspecialchars($org['db_user']); ?></span></span></div>
                            <div class="kv"><span class="k">密码</span><span class="v"><span class="code code-err"><?php echo htmlspecialchars($org['db_password']); ?></span></span></div>
                            <div class="kv"><span class="k">访问 URL</span><span class="v"><span class="code"><?php echo $this->getOrgUrl($org['org_code']); ?></span></span></div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="<?= buildUrl('/organizations/edit/' . $org['id']) ?>" class="btn btn-primary"><?= icon('edit', 'ic ic-sm') ?>编辑</a>
                        <a href="<?= buildUrl('/organizations/init/' . $org['id']) ?>" class="btn btn-warn" onclick="return confirm('重新初始化将清空该组织所有数据，确定继续？')"><?= icon('restore', 'ic ic-sm') ?>重新初始化</a>
                    </div>
                </div>
            </div>
        <?php
        platformShellClose();
    }

    // ============================================================
    // 编辑组织
    // ============================================================
    private function orgEdit($id)
    {
        global $DB;
        $this->requirePlatformLogin();

        $org = $DB->query("SELECT * FROM organizations WHERE id = ?", [$id]);
        if (!$org) {
            die('组织不存在');
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $orgName = trim(isset($_POST['org_name']) ? $_POST['org_name'] : '');
            $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
            $status = isset($_POST['status']) ? $_POST['status'] : $org['status'];

            if (empty($orgName)) {
                $error = '组织名称不能为空';
            } else {
                $DB->update('organizations', [
                    'org_name' => $orgName,
                    'description' => $description,
                    'status' => $status,
                ], ['id' => $id]);

                $this->auditPlatformLog('edit_org', 'organization', $id, "编辑组织: {$org['org_code']}");
                $_SESSION['platform_msg'] = '组织信息已更新';
                $_SESSION['platform_msg_type'] = 'success';
                redirect('/dashboard');
                return;
            }
        }

        ?>
        <?php
        platformShellOpen('编辑组织', '', [['label' => ['url' => buildUrl('/dashboard'), 'label' => '平台总览']], ['label' => '编辑 ' . $org['org_code']]]);
        ?>
            <div class="panel" style="max-width:640px">
                <div class="panel-head">
                    <div class="panel-title">编辑组织 · <span class="code"><?php echo htmlspecialchars($org['org_code']); ?></span></div>
                    <div class="spacer"></div>
                    <a href="<?= buildUrl('/dashboard') ?>" class="btn btn-sm btn-quiet"><?= icon('back', 'ic ic-sm') ?>返回</a>
                </div>
                <div class="panel-body">
                <?php if ($error): ?>
                    <?php flashMessage('error', $error); ?>
                <?php endif; ?>
                <form method="POST" class="form">
                    <div class="form-grid">
                        <div class="field wide">
                            <label class="lbl">组织代号</label>
                            <input class="input mono" type="text" value="<?php echo htmlspecialchars($org['org_code']); ?>" disabled>
                            <div class="hint">代号创建后不可修改</div>
                        </div>
                        <div class="field wide">
                            <label class="lbl">组织全称 <span class="req">*</span></label>
                            <input class="input" type="text" name="org_name" required value="<?php echo htmlspecialchars($org['org_name']); ?>">
                        </div>
                        <div class="field wide">
                            <label class="lbl">状态</label>
                            <select class="select" name="status">
                                <option value="active" <?php echo $org['status'] == 'active' ? 'selected' : ''; ?>>活跃</option>
                                <option value="inactive" <?php echo $org['status'] == 'inactive' ? 'selected' : ''; ?>>停用</option>
                            </select>
                        </div>
                        <div class="field wide">
                            <label class="lbl">备注</label>
                            <textarea class="textarea" name="description" rows="3"><?php echo htmlspecialchars(isset($org['description']) ? $org['description'] : ''); ?></textarea>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?= icon('check', 'ic ic-sm') ?>保存</button>
                        <a href="<?= buildUrl('/dashboard') ?>" class="btn btn-quiet">取消</a>
                    </div>
                </form>
                </div>
            </div>
        <?php
        platformShellClose();
    }

    // ============================================================
    // 软删除组织
    // ============================================================
    // 停用/启用组织（同时锁定/解锁MySQL用户）
    // ============================================================
    private function orgToggle($id)
    {
        global $DB;
        $this->requirePlatformLogin();

        $org = $DB->query("SELECT * FROM organizations WHERE id = ?", [$id]);
        if ($org) {
            $newStatus = ($org['status'] == 'active') ? 'inactive' : 'active';

            try {
                if ($newStatus == 'inactive') {
                    // 停用：撤销MySQL用户权限（禁止数据库直连）
                    $DB->execute("REVOKE ALL PRIVILEGES ON `{$org['db_name']}`.* FROM '{$org['db_user']}'@'%'");
                    $DB->execute("REVOKE ALL PRIVILEGES ON `{$org['db_name']}`.* FROM '{$org['db_user']}'@'localhost'");
                    $DB->execute("FLUSH PRIVILEGES");
                } else {
                    // 启用：恢复MySQL用户权限
                    $DB->execute("GRANT ALL PRIVILEGES ON `{$org['db_name']}`.* TO '{$org['db_user']}'@'%'");
                    $DB->execute("GRANT ALL PRIVILEGES ON `{$org['db_name']}`.* TO '{$org['db_user']}'@'localhost'");
                    $DB->execute("FLUSH PRIVILEGES");
                }
            } catch (\Exception $e) {
                // MySQL用户操作失败不阻断状态切换
            }

            $DB->update('organizations', ['status' => $newStatus], ['id' => $id]);
            $this->auditPlatformLog('toggle_org', 'organization', $id, "切换组织状态: {$org['org_code']} → {$newStatus}");
            $_SESSION['platform_msg'] = "组织 {$org['org_code']} 已" . ($newStatus == 'active' ? '启用（MySQL用户已恢复）' : '停用（MySQL用户已锁定）');
            $_SESSION['platform_msg_type'] = 'success';
        }
        redirect('/dashboard');
    }

    // ============================================================
    // 删除组织（直接彻底删除：DROP DATABASE + DROP USER + 删记录）
    // ============================================================
    private function orgDelete($id)
    {
        global $DB;
        $this->requirePlatformLogin();

        $org = $DB->query("SELECT * FROM organizations WHERE id = ?", [$id]);
        if (!$org) {
            die('组织不存在');
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
            $result = $this->dropOrganizationDatabase($org);
            if ($result['success']) {
                $DB->delete('organizations', ['id' => $id]);
                $this->auditPlatformLog('delete_org', 'organization', $id, "彻底删除组织: {$org['org_code']}");
                $_SESSION['platform_msg'] = "组织 {$org['org_code']} 已彻底删除。";
                $_SESSION['platform_msg_type'] = 'success';
                redirect('/dashboard');
                return;
            } else {
                $error = $result['error'];
            }
        }

        ?>
        <?php
        platformShellOpen('删除组织', '', [['label' => ['url' => buildUrl('/dashboard'), 'label' => '平台总览']], ['label' => '删除 ' . $org['org_code']]]);
        ?>
            <div class="panel" style="max-width:640px;border-color:var(--err-line)">
                <div class="panel-head" style="background:var(--err-50);border-bottom-color:var(--err-line)">
                    <div class="panel-title" style="color:var(--err-500)">
                        <span style="display:inline-flex;align-items:center;gap:8px"><?= icon('alert', 'ic ic-sm') ?>删除组织 · <?php echo htmlspecialchars($org['org_code']); ?></span>
                    </div>
                    <div class="spacer"></div>
                    <a href="<?= buildUrl('/dashboard') ?>" class="btn btn-sm btn-quiet"><?= icon('back', 'ic ic-sm') ?>取消</a>
                </div>
                <div class="panel-body">
                <div class="alert alert-err">
                    <?= icon('alert') ?>
                    <div class="alert-body">
                        <strong>此操作将永久删除：</strong><br>
                        ① 数据库 <span class="code code-err"><?php echo htmlspecialchars($org['db_name']); ?></span> 及其中所有数据<br>
                        ② MySQL 用户 <span class="code code-err"><?php echo htmlspecialchars($org['db_user']); ?></span><br>
                        ③ 组织记录<br><br>
                        <strong>此操作不可恢复！</strong>
                    </div>
                </div>
                <div class="alert alert-warn">
                    <?= icon('info') ?>
                    <div class="alert-body">
                        <div class="strong" style="margin-bottom:6px">将执行</div>
                        <div><span class="code">DROP DATABASE IF EXISTS <?php echo htmlspecialchars($org['db_name']); ?>;</span></div>
                        <div style="margin-top:4px"><span class="code">DROP USER IF EXISTS '<?php echo htmlspecialchars($org['db_user']); ?>'@'%';</span></div>
                        <div style="margin-top:4px"><span class="code">DROP USER IF EXISTS '<?php echo htmlspecialchars($org['db_user']); ?>'@'localhost';</span></div>
                    </div>
                </div>
                <?php if ($error): ?>
                    <?php flashMessage('error', $error); ?>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <div class="form-actions" style="border-top:0;padding-top:0;margin-top:0">
                        <button type="submit" class="btn btn-danger"><?= icon('trash', 'ic ic-sm') ?>确认删除</button>
                        <a href="<?= buildUrl('/dashboard') ?>" class="btn btn-quiet">取消</a>
                    </div>
                </form>
                </div>
            </div>
        <?php
        platformShellClose();
    }

    // ============================================================
    // 重新初始化组织（清空所有数据，重新导入DDL）
    // ============================================================
    private function orgInit($id)
    {
        global $DB;
        $this->requirePlatformLogin();

        $org = $DB->query("SELECT * FROM organizations WHERE id = ?", [$id]);
        if (!$org) {
            die('组织不存在');
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm'])) {
            $result = $this->reinitializeOrganization($org);
            if ($result['success']) {
                $this->auditPlatformLog('init_org', 'organization', $id, "重新初始化组织: {$org['org_code']}");
                $_SESSION['platform_msg'] = "组织 {$org['org_code']} 已重新初始化，所有数据已清空。";
                $_SESSION['platform_msg_type'] = 'success';
                redirect('/dashboard');
                return;
            } else {
                $error = $result['error'];
            }
        }

        ?>
        <?php
        platformShellOpen('重新初始化', '', [['label' => ['url' => buildUrl('/dashboard'), 'label' => '平台总览']], ['label' => '重新初始化 ' . $org['org_code']]]);
        ?>
            <div class="panel" style="max-width:640px;border-color:var(--warn-line)">
                <div class="panel-head" style="background:var(--warn-50);border-bottom-color:var(--warn-line)">
                    <div class="panel-title" style="color:var(--warn-500)">
                        <span style="display:inline-flex;align-items:center;gap:8px"><?= icon('restore', 'ic ic-sm') ?>重新初始化组织 · <?php echo htmlspecialchars($org['org_code']); ?></span>
                    </div>
                    <div class="spacer"></div>
                    <a href="<?= buildUrl('/dashboard') ?>" class="btn btn-sm btn-quiet"><?= icon('back', 'ic ic-sm') ?>取消</a>
                </div>
                <div class="panel-body">
                <div class="alert alert-warn">
                    <?= icon('alert') ?>
                    <div class="alert-body">
                        <strong>警告：此操作将：</strong><br>
                        ① 删除数据库 <span class="code"><?php echo htmlspecialchars($org['db_name']); ?></span> 中的所有表和数据<br>
                        ② 重新导入初始表结构和种子数据（含 4990 条审计日志）<br>
                        ③ MySQL 用户不变<br><br>
                        <strong>此操作不可恢复！</strong>
                    </div>
                </div>
                <?php if ($error): ?>
                    <?php flashMessage('error', $error); ?>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="confirm" value="1">
                    <div class="form-actions" style="border-top:0;padding-top:0;margin-top:0">
                        <button type="submit" class="btn btn-warn"><?= icon('restore', 'ic ic-sm') ?>确认重新初始化</button>
                        <a href="<?= buildUrl('/dashboard') ?>" class="btn btn-quiet">取消</a>
                    </div>
                </form>
                </div>
            </div>
        <?php
        platformShellClose();
    }

    // ============================================================
    // 平台登录
    // ============================================================
    public function login()
    {
        global $DB;

        // 检查是否需要首次初始化
        $firstRun = false;
        try {
            $adminUser = $DB->query("SELECT * FROM platform_users WHERE username = 'admin'");
            if ($adminUser && $adminUser['password_hash'] === '__FIRST_RUN__') {
                $firstRun = true;
            }
        } catch (Exception $e) {
            die('平台数据库未初始化，请先导入 sql/平台库建库脚本.sql');
        }

        if ($firstRun && !isset($_POST['setup_password'])) {
            $this->showFirstRunSetup();
            return;
        }

        // 处理首次密码设置
        if ($firstRun && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_password'])) {
            $pw = isset($_POST['setup_password']) ? $_POST['setup_password'] : '';
            $pw2 = isset($_POST['setup_password2']) ? $_POST['setup_password2'] : '';
            if (empty($pw) || strlen($pw) < 6) {
                $error = '密码长度至少6位';
                $this->showFirstRunSetup($error);
                return;
            }
            if ($pw !== $pw2) {
                $error = '两次密码不一致';
                $this->showFirstRunSetup($error);
                return;
            }
            $DB->update('platform_users', ['password_hash' => password_hash($pw, PASSWORD_DEFAULT)], ['username' => 'admin']);
            $_SESSION['platform_msg'] = '平台管理员密码已设置，请登录';
            $_SESSION['platform_msg_type'] = 'success';
            redirect('/login');
            return;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
            $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
            $password = isset($_POST['password']) ? $_POST['password'] : '';

            if (empty($username) || empty($password)) {
                $error = '请输入用户名和密码';
            } else {
                $user = $DB->query("SELECT * FROM platform_users WHERE username = ? AND status = 'active'", [$username]);
                if ($user && password_verify($password, $user['password_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['mode'] = 'platform';
                    $_SESSION['platform_user_id'] = $user['id'];
                    $_SESSION['platform_username'] = $user['username'];
                    $DB->update('platform_users', ['last_login' => date('Y-m-d H:i:s')], ['id' => $user['id']]);
                    redirect('/dashboard');
                    return;
                } else {
                    $error = '用户名或密码错误';
                }
            }
        }

        ?>
        <?php pageHead('平台管理·登录'); ?>
        <div class="auth">
            <div class="auth-top">
                <div class="brand-mark"><?= icon('server') ?></div>
                <span>平台管理中心</span>
                <span class="sub">平台管理</span>
            </div>
            <div class="auth-body">
                <div class="auth-card">
                    <div class="h-eyebrow">管理入口</div>
                    <h1>平台管理员登录</h1>
                    <p class="lede">管理所有检测组织实例与数据库交付信息。</p>
                    <?php if (isset($_SESSION['platform_msg'])): ?>
                        <?php flashMessage(isset($_SESSION['platform_msg_type']) ? $_SESSION['platform_msg_type'] : 'success', $_SESSION['platform_msg']); ?>
                        <?php unset($_SESSION['platform_msg'], $_SESSION['platform_msg_type']); ?>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <?php flashMessage('error', $error); ?>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="field">
                            <label class="lbl">用户名</label>
                            <input class="input" type="text" name="username" required autofocus autocomplete="username">
                        </div>
                        <div class="field">
                            <label class="lbl">密码</label>
                            <input class="input" type="password" name="password" required autocomplete="current-password">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">登录</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="auth-foot">Web信息安全性实验系统 · 平台管理中心</div>
        </div>
        </body>
        </html>
        <?php
    }

    private function showFirstRunSetup($error = '')
    {
        ?>
        <?php pageHead('首次初始化'); ?>
        <div class="auth">
            <div class="auth-top">
                <div class="brand-mark"><?= icon('server') ?></div>
                <span>平台管理中心</span>
                <span class="sub">首次运行</span>
            </div>
            <div class="auth-body">
                <div class="auth-card wide">
                    <div class="h-eyebrow">首次初始化</div>
                    <h1>设置平台管理员密码</h1>
                    <p class="lede">平台管理系统首次运行，完成此步骤后进入登录页。</p>
                    <div class="alert alert-info">
                        <?= icon('info') ?>
                        <div class="alert-body">这是平台管理系统的首次运行。请为 <strong>admin</strong> 账户设置密码。此账户管理所有检测组织。</div>
                    </div>
                    <?php if ($error): ?>
                        <?php flashMessage('error', $error); ?>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="field">
                            <label class="lbl">用户名</label>
                            <input class="input" type="text" value="admin" disabled>
                        </div>
                        <div class="field">
                            <label class="lbl">新密码 <span class="req">*</span></label>
                            <input class="input" type="password" name="setup_password" required minlength="6" autofocus autocomplete="new-password">
                        </div>
                        <div class="field">
                            <label class="lbl">确认密码 <span class="req">*</span></label>
                            <input class="input" type="password" name="setup_password2" required minlength="6" autocomplete="new-password">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">设置密码并继续</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="auth-foot">Web信息安全性实验系统 · 平台管理中心</div>
        </div>
        </body>
        </html>
        <?php
    }

    // ============================================================
    // 平台登出
    // ============================================================
    public function logout()
    {
        unset($_SESSION['platform_user_id'], $_SESSION['platform_username'], $_SESSION['mode']);
        $_SESSION['platform_msg'] = '已退出平台管理';
        $_SESSION['platform_msg_type'] = 'success';
        redirect('/login');
    }

    // ============================================================
    // 辅助方法
    // ============================================================

    private function requirePlatformLogin()
    {
        if (!isset($_SESSION['platform_user_id']) || empty($_SESSION['platform_user_id'])) {
            redirect('/login');
        }
    }

    private function auditPlatformLog($operationType, $objectType, $objectId, $desc)
    {
        global $DB;
        try {
            $DB->insert('platform_audit_logs', [
                'platform_user_id' => isset($_SESSION['platform_user_id']) ? $_SESSION['platform_user_id'] : null,
                'username' => isset($_SESSION['platform_username']) ? $_SESSION['platform_username'] : 'unknown',
                'operation_type' => $operationType,
                'object_type' => $objectType,
                'object_id' => $objectId,
                'operation_desc' => $desc,
                'client_ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
            ]);
        } catch (Exception $e) {
            // 审计失败不阻止操作
        }
    }

    private function getOrgUrl($orgCode)
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script = dirname($_SERVER['SCRIPT_NAME']);
        $script = rtrim($script, '/');
        return "{$scheme}://{$host}{$script}/index.php/{$orgCode}/";
    }

    /**
     * 创建组织：建库 + 建MySQL用户 + 导入DDL
     */
    private function createOrganization($orgCode, $orgName, $description)
    {
        global $DB;

        $dbName = 'websec_org_' . strtolower($orgCode);
        $dbUser = 'websec_' . strtolower($orgCode) . '_user';
        $dbPassword = $this->generatePassword(16);
        $dbHost = 'localhost';
        $dbPort = 3306;

        // fail-fast：先定位结构脚本，再建库建用户
        $schemaFile = businessSchemaFile();
        if ($schemaFile === null) {
            return ['success' => false, 'error' => 'sql/业务库建库脚本.sql 文件不存在'];
        }

        try {
            // ① 创建数据库
            $DB->execute("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // ② 创建MySQL用户并授权
            $DB->execute("CREATE USER IF NOT EXISTS '{$dbUser}'@'%' IDENTIFIED BY '{$dbPassword}'");
            $DB->execute("CREATE USER IF NOT EXISTS '{$dbUser}'@'localhost' IDENTIFIED BY '{$dbPassword}'");
            $DB->execute("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'%'");
            $DB->execute("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$dbUser}'@'localhost'");
            $DB->execute("FLUSH PRIVILEGES");

            // ③ 导入DDL
            $schemaSql = file_get_contents($schemaFile);
            // 替换数据库名
            $schemaSql = str_replace('websec_db', $dbName, $schemaSql);
            // 去掉 CREATE DATABASE 和 USE 行（我们已经创建了）
            $schemaSql = preg_replace('/^CREATE DATABASE.*?;$/im', '', $schemaSql);
            $schemaSql = preg_replace('/^USE .*?;$/im', '', $schemaSql);

            // 使用新数据库连接执行DDL
            $orgPdo = new \PDO(
                "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4",
                $dbUser,
                $dbPassword,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            // 拆分为多条SQL执行
            $statements = $this->splitSql($schemaSql);
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (!empty($stmt)) {
                    $orgPdo->exec($stmt);
                }
            }

            // 生成4990条审计日志（预留测试数据）
            $ops = [
                ['LOGIN', 'user', 'superadmin', 'super_admin', '用户登录'],
                ['LOGOUT', 'user', 'superadmin', 'super_admin', '用户登出'],
                ['LOGIN', 'user', 'admin', 'admin', '用户登录'],
                ['LOGOUT', 'user', 'admin', 'admin', '用户登出'],
                ['LOGIN', 'user', 'operator', 'operator', '用户登录'],
                ['LOGOUT', 'user', 'operator', 'operator', '用户登出'],
                ['CREATE', 'user', 'admin', 'admin', '创建用户'],
                ['UPDATE', 'user', 'admin', 'admin', '更新用户'],
                ['DELETE', 'user', 'superadmin', 'super_admin', '删除用户'],
                ['CREATE', 'project', 'admin', 'admin', '创建项目'],
                ['UPDATE', 'project', 'admin', 'admin', '更新项目'],
                ['DELETE', 'project', 'superadmin', 'super_admin', '删除项目'],
                ['DELETE', 'audit_log', 'admin', 'admin', '批量删除审计日志'],
                ['BACKUP', 'backup', 'superadmin', 'super_admin', '手动备份数据库'],
                ['RESTORE', 'backup', 'superadmin', 'super_admin', '恢复备份'],
            ];
            $usernames = ['admin', 'superadmin', 'operator', 'auditor'];
            $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
            $objNames = ['测试用户A', '测试用户B', '检测员', '管理员', '张三', '李四'];

            $logSql = "INSERT INTO audit_logs (operation_time, user_id, username, user_role, operation_type, object_type, object_id, operation_desc, old_value, new_value, client_ip, user_agent, result, created_at) VALUES ";
            $values = [];
            for ($i = 0; $i < 4990; $i++) {
                $tpl = $ops[\array_rand($ops)];
                $day = \rand(1, 10);
                $date = '2026-07-' . \sprintf('%02d', $day);
                $hour = \rand(0, 23);
                $min = \rand(0, 59);
                $sec = \rand(0, 59);
                $ts = \sprintf('%s %02d:%02d:%02d', $date, $hour, $min, $sec);
                $objId = \rand(1, 20);
                $desc = $tpl[4];
                if (\in_array($tpl[0], ['CREATE', 'UPDATE', 'DELETE']) && $tpl[1] == 'user') {
                    $desc .= ': ' . $usernames[\array_rand($usernames)];
                } elseif ($tpl[0] == 'DELETE' && $tpl[1] == 'audit_log') {
                    $desc .= ': ' . \rand(1, 50) . ' 条';
                } elseif (\in_array($tpl[0], ['CREATE', 'UPDATE', 'DELETE']) && $tpl[1] == 'project') {
                    $desc .= ': ' . $objNames[\array_rand($objNames)];
                }
                $values[] = "('$date', 1, '{$tpl[2]}', '{$tpl[3]}', '{$tpl[0]}', '{$tpl[1]}', $objId, '$desc', NULL, NULL, '', '$ua', 'success', '$ts')";
                if (\count($values) >= 100) {
                    $orgPdo->exec($logSql . \implode(',', $values));
                    $values = [];
                }
            }
            if (!empty($values)) {
                $orgPdo->exec($logSql . \implode(',', $values));
            }

            // ④ 写入organizations表
            $DB->insert('organizations', [
                'org_code' => $orgCode,
                'org_name' => $orgName,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_password' => $dbPassword,
                'db_host' => $dbHost,
                'db_port' => $dbPort,
                'description' => $description,
            ]);

            $orgId = $DB->getLastInsertId();

            return [
                'success' => true,
                'org_id' => $orgId,
                'db_name' => $dbName,
                'db_user' => $dbUser,
                'db_password' => $dbPassword,
            ];
        } catch (\Exception $e) {
            \logError($e->getMessage());
            // 回滚：尝试删除已创建的资源
            try { $DB->execute("DROP DATABASE IF EXISTS `{$dbName}`"); } catch (\Exception $ex) {}
            try { $DB->execute("DROP USER IF EXISTS '{$dbUser}'@'%'"); } catch (\Exception $ex) {}
            try { $DB->execute("DROP USER IF EXISTS '{$dbUser}'@'localhost'"); } catch (\Exception $ex) {}
            return ['success' => false, 'error' => '数据库操作失败，请查看日志'];
        }
    }

    /**
     * 重新初始化组织数据库
     */
    private function reinitializeOrganization($org)
    {
        global $DB;

        // fail-fast：先确认结构脚本存在，再重建组织库
        $schemaFile = businessSchemaFile();
        if ($schemaFile === null) {
            return ['success' => false, 'error' => 'sql/业务库建库脚本.sql 文件不存在'];
        }

        try {
            // 删除旧数据库
            $DB->execute("DROP DATABASE IF EXISTS `{$org['db_name']}`");
            // 重新创建
            $DB->execute("CREATE DATABASE `{$org['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // 重新导入DDL
            $schemaSql = file_get_contents($schemaFile);
            $schemaSql = str_replace('websec_db', $org['db_name'], $schemaSql);
            $schemaSql = preg_replace('/^CREATE DATABASE.*?;$/im', '', $schemaSql);
            $schemaSql = preg_replace('/^USE .*?;$/im', '', $schemaSql);

            $orgPdo = new \PDO(
                "mysql:host={$org['db_host']};port={$org['db_port']};dbname={$org['db_name']};charset=utf8mb4",
                $org['db_user'],
                $org['db_password'],
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );

            $statements = $this->splitSql($schemaSql);
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (!empty($stmt)) {
                    $orgPdo->exec($stmt);
                }
            }

            // 生成4990条审计日志（与 HelpController::init 一致）
            $ops = [
                ['LOGIN', 'user', 'superadmin', 'super_admin', '用户登录'],
                ['LOGOUT', 'user', 'superadmin', 'super_admin', '用户登出'],
                ['LOGIN', 'user', 'admin', 'admin', '用户登录'],
                ['LOGOUT', 'user', 'admin', 'admin', '用户登出'],
                ['LOGIN', 'user', 'operator', 'operator', '用户登录'],
                ['LOGOUT', 'user', 'operator', 'operator', '用户登出'],
                ['CREATE', 'user', 'admin', 'admin', '创建用户'],
                ['UPDATE', 'user', 'admin', 'admin', '更新用户'],
                ['DELETE', 'user', 'superadmin', 'super_admin', '删除用户'],
                ['CREATE', 'project', 'admin', 'admin', '创建项目'],
                ['UPDATE', 'project', 'admin', 'admin', '更新项目'],
                ['DELETE', 'project', 'superadmin', 'super_admin', '删除项目'],
                ['DELETE', 'audit_log', 'admin', 'admin', '批量删除审计日志'],
                ['BACKUP', 'backup', 'superadmin', 'super_admin', '手动备份数据库'],
                ['RESTORE', 'backup', 'superadmin', 'super_admin', '恢复备份'],
            ];
            $usernames = ['admin', 'superadmin', 'operator', 'auditor'];
            $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
            $objNames = ['测试用户A', '测试用户B', '检测员', '管理员', '张三', '李四'];

            $sql = "INSERT INTO audit_logs (operation_time, user_id, username, user_role, operation_type, object_type, object_id, operation_desc, old_value, new_value, client_ip, user_agent, result, created_at) VALUES ";
            $values = [];
            for ($i = 0; $i < 4990; $i++) {
                $tpl = $ops[\array_rand($ops)];
                $day = \rand(1, 10);
                $date = '2026-07-' . \sprintf('%02d', $day);
                $hour = \rand(0, 23);
                $min = \rand(0, 59);
                $sec = \rand(0, 59);
                $ts = \sprintf('%s %02d:%02d:%02d', $date, $hour, $min, $sec);
                $objId = \rand(1, 20);
                $desc = $tpl[4];
                if (\in_array($tpl[0], ['CREATE', 'UPDATE', 'DELETE']) && $tpl[1] == 'user') {
                    $desc .= ': ' . $usernames[\array_rand($usernames)];
                } elseif ($tpl[0] == 'DELETE' && $tpl[1] == 'audit_log') {
                    $desc .= ': ' . \rand(1, 50) . ' 条';
                } elseif (\in_array($tpl[0], ['CREATE', 'UPDATE', 'DELETE']) && $tpl[1] == 'project') {
                    $desc .= ': ' . $objNames[\array_rand($objNames)];
                }
                $values[] = "('$date', 1, '{$tpl[2]}', '{$tpl[3]}', '{$tpl[0]}', '{$tpl[1]}', $objId, '$desc', NULL, NULL, '', '$ua', 'success', '$ts')";
                if (\count($values) >= 100) {
                    $orgPdo->exec($sql . \implode(',', $values));
                    $values = [];
                }
            }
            if (!empty($values)) {
                $orgPdo->exec($sql . \implode(',', $values));
            }

            return ['success' => true];
        } catch (\Exception $e) {
            \logError($e->getMessage());
            return ['success' => false, 'error' => '数据库操作失败，请查看日志'];
        }
    }

    /**
     * 彻底删除组织数据库和MySQL用户
     */
    private function dropOrganizationDatabase($org)
    {
        global $DB;

        try {
            $DB->execute("DROP DATABASE IF EXISTS `{$org['db_name']}`");
            $DB->execute("DROP USER IF EXISTS '{$org['db_user']}'@'%'");
            $DB->execute("DROP USER IF EXISTS '{$org['db_user']}'@'localhost'");
            return ['success' => true];
        } catch (\Exception $e) {
            \logError($e->getMessage());
            return ['success' => false, 'error' => '数据库操作失败，请查看日志'];
        }
    }

    /**
     * 生成随机密码
     */
    private function generatePassword($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*_-';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[\mt_rand(0, strlen($chars) - 1)];
        }
        return $password;
    }

    /**
     * 拆分SQL语句（按分号分隔，忽略注释）
     */
    private function splitSql($sql)
    {
        // 移除注释
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        $statements = [];
        $current = '';
        $lines = explode("\n", $sql);

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) continue;

            $current .= $line . "\n";

            if (substr($trimmed, -1) === ';') {
                $statements[] = $current;
                $current = '';
            }
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }
}
