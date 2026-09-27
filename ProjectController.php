<?php
/**
 * 数据管理控制器
 * 包含缺陷: DEF004, DEF010, DEF011, DEF014, DEF016, DEF020
 */

namespace Controllers;

class ProjectController
{

    /**
     * 显示项目列表
     */
    public function index()
    {
        global $DB;

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $perPage = 10;
        $total = $DB->query("SELECT COUNT(*) as count FROM detection_projects");
        $totalPages = max(1, ceil($total['count'] / $perPage));
        $offset = ($page - 1) * $perPage;
        $projects = $DB->queryAll("SELECT * FROM detection_projects ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");

        ?>
        <?php
        appShellOpen('检测项目', 'project', [['label' => '业务'], ['label' => '项目管理']]);
        ?>

        <?php pageHeader('业务', '检测项目', '项目登记与状态流转'); ?>

        <div class="toolbar">
            <div class="tb-group">
                <span class="tb-meta">共 <b><?php echo $total['count']; ?></b> 个项目</span>
            </div>
            <div class="tb-spacer"></div>
            <div class="tb-group">
                <a class="btn btn-quiet btn-sm" href="<?php echo buildUrl('/dashboard'); ?>"><?= icon('back', 'ic ic-sm') ?>返回工作台</a>
                <a class="btn btn-sm btn-primary" href="<?php echo buildUrl('/project/create'); ?>"><?= icon('plus', 'ic ic-sm') ?>新建项目</a>
            </div>
        </div>

        <section class="panel">
            <div class="panel-body panel-body-flush">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:72px">项目ID</th>
                                <th>项目名称</th>
                                <th style="width:160px">项目代码</th>
                                <th style="width:100px">状态</th>
                                <th style="width:100px">创建人</th>
                                <th style="width:170px">创建时间</th>
                                <th style="width:140px" class="c-actions">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($projects as $project): ?>
                            <tr>
                                <td class="c-num"><?php echo htmlspecialchars($project['id']); ?></td>
                                <td class="c-primary"><?php echo htmlspecialchars($project['project_name']); ?></td>
                                <td class="c-mono"><?php echo htmlspecialchars($project['project_code']); ?></td>
                                <td><?php echo statusBadge($project['status']); ?></td>
                                <td class="c-num"><?php echo htmlspecialchars($project['created_by']); ?></td>
                                <td class="c-muted mono"><?php echo htmlspecialchars($project['created_at']); ?></td>
                                <td class="c-actions">
                                    <a class="btn btn-sm btn-quiet" href="<?php echo buildUrl('/project/edit/' . $project['id']); ?>"><?= icon('edit', 'ic ic-sm') ?>编辑</a>
                                    <button class="btn btn-sm btn-danger-quiet" onclick="deleteProject(<?php echo $project['id']; ?>)"><?= icon('trash', 'ic ic-sm') ?>删除</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($page, $totalPages); ?>
            </div>
        </section>

        <script>
            function deleteProject(id) {
                if (confirm('确定删除该项目吗？')) {
                    // DEF020: 删除操作可能不纳入审计（通过权限检查缺陷）
                    location.href = '/project/delete/' + id;
                }
            }
        </script>

        <?php
        appShellClose();
    }

    /**
     * 显示创建项目页面
     */
    public function create()
    {
        ?>
        <?php
        appShellOpen('新建项目', 'project', [['label' => '业务'], ['label' => ['url' => buildUrl('/project/list'), 'label' => '项目管理']], ['label' => '新建']]);
        ?>

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">新建检测项目</div>
                <div class="spacer"></div>
                <a class="btn btn-sm btn-quiet" href="<?php echo buildUrl('/project/list'); ?>"><?= icon('back', 'ic ic-sm') ?>返回列表</a>
            </div>
            <div class="panel-body">
                <form method="POST" action="<?php echo buildUrl('/project/save'); ?>" class="form" style="max-width:560px">
                    <div class="form-grid">
                        <div class="field">
                            <label class="lbl" for="project_name">项目名称<span class="req">*</span></label>
                            <input class="input" type="text" id="project_name" name="project_name" required>
                        </div>

                        <div class="field">
                            <label class="lbl" for="project_code">项目代码<span class="req">*</span></label>
                            <input class="input mono" type="text" id="project_code" name="project_code" required>
                        </div>

                        <div class="field wide">
                            <label class="lbl" for="description">描述</label>
                            <textarea class="textarea" id="description" name="description" rows="5"></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?= icon('check', 'ic ic-sm') ?>保存</button>
                        <button type="button" class="btn btn-quiet" onclick="history.back()">取消</button>
                    </div>
                </form>
            </div>
        </section>

        <?php
        appShellClose();
    }

    /**
     * 保存项目
     * 缺陷位置: DEF010, DEF011, DEF014
     */
    public function save()
    {
        global $DB;

        $project_name = isset($_POST['project_name']) ? $_POST['project_name'] : '';
        $project_code = isset($_POST['project_code']) ? $_POST['project_code'] : '';
        $description = isset($_POST['description']) ? $_POST['description'] : '';

        if (empty($project_name) || empty($project_code)) {
            die('项目名称和代码不能为空');
        }

        // DEF010: 传输数据无校验机制
        // 即使抓包篡改数据，系统仍会接受

        $user_id = $_SESSION['user_id'];

        try {
            $DB->insert('detection_projects', [
                'project_name' => $project_name,
                'project_code' => $project_code,
                'manager_id' => $user_id,
                'description' => $description,
                'status' => 'draft',
                'created_by' => $user_id,
                'created_at' => date('Y-m-d H:i:s'),
                // DEF011: data_integrity_check 字段设为空，不进行校验
                'data_integrity_check' => NULL
            ]);

            // DEF014: 删除操作无抗抵赖签章
            // 保存时也无签章机制

            auditLog('CREATE', 'project', $DB->getLastInsertId(), '创建项目: ' . $project_name);

            redirect('/dashboard?success=项目创建成功');
        } catch (Exception $e) {
            die('保存失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除项目
     * 缺陷位置: DEF016, DEF020
     */
    public function delete($id = null)
    {
        global $DB;

        $id = intval(isset($id) ? $id : (isset($_GET['id']) ? $_GET['id'] : 0));

        if ($id <= 0) {
            die('项目ID无效');
        }

        try {
            $DB->delete('detection_projects', ['id' => $id]);

            // DEF016: 操作员删除操作不纳入审计范围
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'operator') {
                auditLog('DELETE', 'project', $id, '删除项目');
            }

            redirect('/project/list?success=项目已删除');
        } catch (Exception $e) {
            die('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 编辑项目
     * 缺陷位置: DEF004 - 演示权限越权
     */
    public function edit($id = null)
    {
        global $DB;

        $id = intval(isset($id) ? $id : 0);

        if ($id <= 0) {
            die('项目ID无效');
        }

        $project = $DB->query("SELECT * FROM detection_projects WHERE id = ?", [$id]);

        if (!$project) {
            die('项目不存在');
        }

        // DEF004: 权限检查可能有缺陷 - 此处应该检查用户是否是项目创建者或管理员
        // 但实际上由于权限管理的缺陷，可能任何用户都能编辑

        ?>
        <?php
        appShellOpen('编辑项目', 'project', [['label' => '业务'], ['label' => ['url' => buildUrl('/project/list'), 'label' => '项目管理']], ['label' => '编辑']]);
        ?>

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">编辑检测项目</div>
                <div class="spacer"></div>
                <a class="btn btn-sm btn-quiet" href="<?php echo buildUrl('/project/list'); ?>"><?= icon('back', 'ic ic-sm') ?>返回列表</a>
            </div>
            <div class="panel-body">
                <form method="POST" action="<?php echo buildUrl('/project/update/' . $id); ?>" class="form" style="max-width:560px">
                    <div class="form-grid">
                        <div class="field">
                            <label class="lbl" for="project_name">项目名称<span class="req">*</span></label>
                            <input class="input" type="text" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                        </div>

                        <div class="field">
                            <label class="lbl" for="project_code">项目代码<span class="req">*</span></label>
                            <input class="input mono" type="text" id="project_code" name="project_code" value="<?php echo htmlspecialchars($project['project_code']); ?>" required>
                        </div>

                        <div class="field">
                            <label class="lbl" for="status">状态</label>
                            <select class="select" id="status" name="status">
                                <option value="draft" <?php echo $project['status'] == 'draft' ? 'selected' : ''; ?>>草稿</option>
                                <option value="submitted" <?php echo $project['status'] == 'submitted' ? 'selected' : ''; ?>>已提交</option>
                                <option value="reviewed" <?php echo $project['status'] == 'reviewed' ? 'selected' : ''; ?>>已审核</option>
                                <option value="archived" <?php echo $project['status'] == 'archived' ? 'selected' : ''; ?>>已存档</option>
                            </select>
                        </div>

                        <div class="field wide">
                            <label class="lbl" for="description">描述</label>
                            <textarea class="textarea" id="description" name="description" rows="5"><?php echo htmlspecialchars($project['description']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?= icon('check', 'ic ic-sm') ?>保存</button>
                        <button type="button" class="btn btn-quiet" onclick="history.back()">取消</button>
                    </div>
                </form>
            </div>
        </section>

        <?php
        appShellClose();
    }

    /**
     * 更新项目
     */
    public function update($id = null)
    {
        global $DB;

        $id = intval(isset($id) ? $id : 0);

        if ($id <= 0) {
            die('项目ID无效');
        }

        $project_name = isset($_POST['project_name']) ? $_POST['project_name'] : '';
        $project_code = isset($_POST['project_code']) ? $_POST['project_code'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : 'draft';
        $description = isset($_POST['description']) ? $_POST['description'] : '';

        try {
            // DEF010: 无完整性校验
            // 即使通过Burp篡改数据也会被接受

            $old_value = json_encode($DB->query("SELECT * FROM detection_projects WHERE id = ?", [$id]));

            $DB->update('detection_projects', [
                'project_name' => $project_name,
                'project_code' => $project_code,
                'status' => $status,
                'description' => $description,
                'updated_by' => $_SESSION['user_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ], ['id' => $id]);

            $new_value = json_encode([
                'project_name' => $project_name,
                'project_code' => $project_code,
                'status' => $status
            ]);

            auditLog('UPDATE', 'project', $id, '更新项目', $old_value, $new_value);

            redirect('/project/list?success=项目已更新');
        } catch (Exception $e) {
            die('更新失败: ' . $e->getMessage());
        }
    }
}

?>