<?php
/**
 * 用户管理控制器
 * 包含缺陷: DEF004, DEF005, DEF009, DEF016, DEF027, DEF030
 */

namespace Controllers;

class UserController
{

    /**
     * 用户列表
     */
    public function index()
    {
        global $DB;

        // DEF029: 列表直接输出 real_name / employee_id / phone 等个人信息，无脱敏与字段级权限控制
        try {
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $perPage = 10;
            $total = $DB->query("SELECT COUNT(*) as count FROM users WHERE status != 'deleted'");
            $totalPages = max(1, ceil($total['count'] / $perPage));
            $offset = ($page - 1) * $perPage;
            $users = $DB->queryAll("SELECT u.*, r.role_name FROM users u LEFT JOIN user_roles ur ON u.id = ur.user_id LEFT JOIN roles r ON ur.role_id = r.id WHERE u.status != 'deleted' ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset");
        } catch (Exception $e) {
            die('查询失败: ' . $e->getMessage());
        }

        ?>
        <?php
        appShellOpen('用户管理', 'user', [['label' => '系统管理'], ['label' => '用户管理']]);
        pageHeader('系统管理', '用户管理', '账号、角色与状态维护');
        ?>

        <div class="toolbar">
            <div class="tb-group">
                <span class="tb-meta">共 <b><?php echo $total['count']; ?></b> 个非删除账号</span>
            </div>
            <div class="tb-spacer"></div>
            <div class="tb-group">
                <a class="btn btn-quiet btn-sm" href="<?php echo buildUrl('/dashboard'); ?>"><?= icon('back', 'ic ic-sm') ?>返回工作台</a>
                <a class="btn btn-sm btn-primary" href="<?php echo buildUrl('/user/create'); ?>"><?= icon('plus', 'ic ic-sm') ?>新建用户</a>
            </div>
        </div>

        <section class="panel">
            <div class="panel-body panel-body-flush">
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width:72px">用户ID</th>
                                <th style="width:120px">角色</th>
                                <th>用户名</th>
                                <th>姓名</th>
                                <th>电话</th>
                                <th style="width:96px">状态</th>
                                <th style="width:150px">创建时间</th>
                                <th style="width:140px" class="c-actions">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="c-num"><?php echo htmlspecialchars($user['id']); ?></td>
                                <td>
                                    <span class="pill pill-<?php echo roleBadgeClass(isset($user['role_name']) ? $user['role_name'] : ''); ?>">
                                        <?php echo htmlspecialchars(roleBadgeLabel(isset($user['role_name']) ? $user['role_name'] : '')); ?>
                                    </span>
                                </td>
                                <td class="c-mono"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="c-primary"><?php echo htmlspecialchars($user['real_name']); ?></td>
                                <td class="c-mono"><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td><?php echo statusBadge($user['status']); ?></td>
                                <td class="c-muted mono"><?php echo htmlspecialchars($user['created_at']); ?></td>
                                <td class="c-actions">
                                    <?php if ((isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') || !(isset($user['role_name']) && $user['role_name'] === 'super_admin')): ?>
                                        <?php if ($user['id'] != $_SESSION['user_id'] || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin')): ?>
                                    <a class="btn btn-sm btn-quiet" href="<?php echo buildUrl('/user/edit/' . $user['id']); ?>"><?= icon('edit', 'ic ic-sm') ?>编辑</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <?php if ((isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') || !(isset($user['role_name']) && $user['role_name'] === 'super_admin')): ?>
                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-danger-quiet" onclick="if(confirm('确定删除用户 <?php echo htmlspecialchars($user['username']); ?> 吗？')) location.href='<?php echo buildUrl('/user/delete/' . $user['id']); ?>'"><?= icon('trash', 'ic ic-sm') ?>删除</button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php renderPagination($page, $totalPages); ?>
            </div>
        </section>

        <?php
        appShellClose();
    }

    /**
     * 创建用户页面
     */
    public function create()
    {
        ?>
        <?php
        appShellOpen('新建用户', 'user', [['label' => '系统管理'], ['label' => ['url' => buildUrl('/user/list'), 'label' => '用户管理']], ['label' => '新建']]);
        ?>

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">新建用户</div>
                <div class="spacer"></div>
                <a class="btn btn-sm btn-quiet" href="<?php echo buildUrl('/user/list'); ?>"><?= icon('back', 'ic ic-sm') ?>返回列表</a>
            </div>
            <div class="panel-body">

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-err" id="error-message">
                    <?= icon('alert') ?>
                    <div class="alert-body"><?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                </div>
            <?php else: ?>
                <div class="alert alert-err hidden" id="error-message">
                    <?= icon('alert') ?>
                    <div class="alert-body"></div>
                </div>
            <?php endif; ?>

            <script>
                function validateForm() {
                    var username = document.querySelector('input[name="username"]').value.trim();
                    var password = document.querySelector('input[name="password"]').value;
                    var realname = document.querySelector('input[name="real_name"]').value.trim();
                    var phone = document.querySelector('input[name="phone"]').value.trim();
                    var err = document.getElementById('error-message');
                    var body = err.querySelector('.alert-body');

                    if (!username || !password || !realname || !phone) {
                        body.textContent = '必填字段不能为空';
                        err.classList.remove('hidden');
                        return false;
                    }
                    if (password.length < 6) {
                        body.textContent = '密码长度不能低于6位';
                        err.classList.remove('hidden');
                        return false;
                    }
                    if (password.length === 6 && /^\d+$/.test(password)) {
                        body.textContent = '6位密码不能为纯数字';
                        err.classList.remove('hidden');
                        return false;
                    }
                    err.classList.add('hidden');
                    return true;
                }
            </script>

            <?php
            $form = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
            unset($_SESSION['form_data']);
            ?>

            <form method="POST" action="<?php echo buildUrl('/user/save'); ?>" onsubmit="return validateForm()" class="form" style="max-width:520px">
                <div class="form-grid">
                    <div class="field">
                        <label class="lbl" for="username">用户名<span class="req">*</span></label>
                        <input class="input" type="text" id="username" name="username" required autofocus autocomplete="off" value="<?php echo isset($form['username']) ? htmlspecialchars($form['username']) : ''; ?>">
                    </div>

                    <div class="field">
                        <label class="lbl" for="password">密码<span class="req">*</span></label>
                        <input class="input" type="password" id="password" name="password" required autocomplete="new-password">
                    </div>

                    <div class="field">
                        <label class="lbl" for="real_name">姓名<span class="req">*</span></label>
                        <input class="input" type="text" id="real_name" name="real_name" required value="<?php echo isset($form['real_name']) ? htmlspecialchars($form['real_name']) : ''; ?>">
                    </div>

                    <div class="field">
                        <label class="lbl" for="employee_id">工号</label>
                        <input class="input" type="text" id="employee_id" name="employee_id" value="<?php echo isset($form['employee_id']) ? htmlspecialchars($form['employee_id']) : ''; ?>">
                    </div>

                    <div class="field">
                        <label class="lbl" for="phone">电话<span class="req">*</span></label>
                        <input class="input" type="text" id="phone" name="phone" required value="<?php echo isset($form['phone']) ? htmlspecialchars($form['phone']) : ''; ?>">
                    </div>

                    <div class="field">
                        <label class="lbl" for="role">角色</label>
                        <select class="select" id="role" name="role">
                            <option value="">-- 不选择（空权限）--</option>
                            <option value="admin" <?php echo (isset($form['role']) && $form['role'] == 'admin') ? 'selected' : ''; ?>>普通管理员</option>
                            <option value="operator" <?php echo (isset($form['role']) && $form['role'] == 'operator') ? 'selected' : ''; ?>>操作员</option>
                            <option value="auditor" <?php echo (isset($form['role']) && $form['role'] == 'auditor') ? 'selected' : ''; ?>>审计管理员</option>
                        </select>
                        <div class="hint">不选择将创建无任何角色的账户</div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= icon('check', 'ic ic-sm') ?>创建用户</button>
                    <button type="button" class="btn btn-quiet" onclick="history.back()">取消</button>
                </div>
            </form>
            </div>
        </section>

        <?php
        appShellClose();
    }

    /**
     * 保存用户
     * 缺陷位置: DEF004, DEF009, DEF027, DEF030
     */
    public function save()
    {
        global $DB;

        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $real_name = isset($_POST['real_name']) ? $_POST['real_name'] : '';
        $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : '';
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        $department = isset($_POST['department']) ? $_POST['department'] : '';
        $role = isset($_POST['role']) ? $_POST['role'] : '';

        if (empty($username) || empty($password) || empty($real_name)) {
            die('必填字段不能为空');
        }

        // 密码策略检测
        if (strlen($password) < 6) {
            die('密码长度不能低于6位');
        }
        if (strlen($password) == 6 && ctype_digit($password)) {
            die('6位密码不能为纯数字');
        }

        // 不允许创建超级管理员角色用户
        if ($role === 'super_admin') {
            die('不允许创建超级管理员角色用户');
        }

        // DEF027: 不检查用户名唯一性 - 支持重复账户
        // 正常应该检查：
        // $existing = $DB->query("SELECT * FROM users WHERE username = ?", [$username]);
        // if ($existing) { die('用户名已存在'); }

        // DEF009: 不检查密码复杂度 - 接受任意密码
        // 正常应该检查：
        // if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        //     die('密码必须包含大小写字母、数字和特殊符号，长度8位以上');
        // }

        // DEF005: 密码明文存储
        $password_hash = $password;  // 故意不加密

        // DEF030: 强制采集phone字段

        try {
            $DB->insert('users', [
                'username' => $username,
                'password_hash' => $password_hash,  // 明文
                'real_name' => $real_name,
                'employee_id' => $employee_id,
                'phone' => $phone,
                'department' => $department,
                'status' => 'active',
                'must_change_password' => 1,
                'created_by' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $user_id = $DB->getLastInsertId();

            // DEF004: 允许空权限账户创建
            if (!empty($role)) {
                $role_data = $DB->query("SELECT id FROM roles WHERE role_name = ?", [$role]);
                if ($role_data) {
                    $DB->insert('user_roles', [
                        'user_id' => $user_id,
                        'role_id' => $role_data['id'],
                        'created_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }
            // 否则不分配角色，形成空权限账户

            auditLog('CREATE', 'user', $user_id, '创建用户: ' . $username);

            redirect('/user/list?success=用户创建成功');
        } catch (Exception $e) {
            die('创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 编辑用户
     */
    public function edit($id = null)
    {
        global $DB;

        $id = intval(isset($id) ? $id : 0);

        if ($id <= 0) {
            die('用户ID无效');
        }

        try {
            $user = $DB->query("SELECT * FROM users WHERE id = ?", [$id]);

            if (!$user) {
                die('用户不存在');
            }
            if ($id == $_SESSION['user_id'] && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')) {
                die('不能编辑自己');
            }

            $targetRole = $DB->query("SELECT r.role_name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?", [$id]);
            if ($targetRole && $targetRole['role_name'] === 'super_admin' && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin')) {
                die('非超级管理员不能编辑超级管理员');
            }

            $roles = $DB->queryAll("SELECT * FROM roles");
            $user_role = $DB->query("
                SELECT r.* FROM roles r
                JOIN user_roles ur ON r.id = ur.role_id
                WHERE ur.user_id = ?
            ", [$id]);

        } catch (Exception $e) {
            die('查询失败: ' . $e->getMessage());
        }

        ?>
        <?php
        appShellOpen('编辑用户', 'user', [['label' => '系统管理'], ['label' => ['url' => buildUrl('/user/list'), 'label' => '用户管理']], ['label' => '编辑']]);
        ?>

        <section class="panel">
            <div class="panel-head">
                <div class="panel-title">编辑用户</div>
                <div class="spacer"></div>
                <a class="btn btn-sm btn-quiet" href="<?php echo buildUrl('/user/list'); ?>"><?= icon('back', 'ic ic-sm') ?>返回列表</a>
            </div>
            <div class="panel-body">

            <div class="alert alert-err hidden" id="error-message">
                <?= icon('alert') ?>
                <div class="alert-body"></div>
            </div>

            <script>
                function validateForm() {
                    var password = document.querySelector('input[name="password"]').value;
                    var err = document.getElementById('error-message');
                    var body = err.querySelector('.alert-body');

                    // 密码填了才校验，不填则跳过
                    if (password !== '') {
                        if (password.length < 6) {
                            body.textContent = '密码长度不能低于6位';
                            err.classList.remove('hidden');
                            return false;
                        }
                        if (password.length === 6 && /^\d+$/.test(password)) {
                            body.textContent = '6位密码不能为纯数字';
                            err.classList.remove('hidden');
                            return false;
                        }
                    }
                    err.classList.add('hidden');
                    return true;
                }
            </script>

            <form method="POST" action="<?php echo buildUrl('/user/update/' . $id); ?>" onsubmit="return validateForm()" class="form" style="max-width:520px">
                <div class="form-grid">
                    <div class="field">
                        <label class="lbl" for="username">用户名<span class="req">*</span></label>
                        <input class="input" type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>

                    <div class="field">
                        <label class="lbl" for="password">新密码</label>
                        <input class="input" type="password" id="password" name="password" placeholder="如不修改密码请留空" autocomplete="new-password">
                        <div class="hint">留空保持现有密码不变</div>
                    </div>

                    <div class="field">
                        <label class="lbl" for="real_name">姓名<span class="req">*</span></label>
                        <input class="input" type="text" id="real_name" name="real_name" value="<?php echo htmlspecialchars($user['real_name']); ?>" required>
                    </div>

                    <div class="field">
                        <label class="lbl" for="phone">电话<span class="req">*</span></label>
                        <input class="input" type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                    </div>

                    <div class="field">
                        <label class="lbl" for="status">状态</label>
                        <select class="select" id="status" name="status">
                            <option value="active" <?php echo $user['status'] == 'active' ? 'selected' : ''; ?>>激活</option>
                            <option value="inactive" <?php echo $user['status'] == 'inactive' ? 'selected' : ''; ?>>禁用</option>
                            <option value="locked" <?php echo $user['status'] == 'locked' ? 'selected' : ''; ?>>锁定</option>
                        </select>
                    </div>

                    <div class="field">
                        <label class="lbl" for="role">角色</label>
                        <select class="select" id="role" name="role">
                            <option value="">-- 不选择 --</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo ($user_role && $user_role['id'] == $role['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($role['display_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
     * 更新用户
     */
    public function update($id = null)
    {
        global $DB;

        $id = intval(isset($id) ? $id : 0);

        if ($id <= 0) {
            die('用户ID无效');
        }

        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $real_name = isset($_POST['real_name']) ? $_POST['real_name'] : '';
        $phone = isset($_POST['phone']) ? $_POST['phone'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : 'active';
        $role_id = isset($_POST['role']) ? $_POST['role'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if ($id == $_SESSION['user_id'] && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin')) {
            die('不能编辑自己');
        }

        // 非超级管理员不能编辑超级管理员
        $targetRole = $DB->query("SELECT r.role_name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?", [$id]);
        if ($targetRole && $targetRole['role_name'] === 'super_admin' && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin')) {
            die('非超级管理员不能编辑超级管理员');
        }

        // 如果提供了新密码，进行验证并更新
        if (!empty($password)) {
            if (strlen($password) < 6) {
                die('密码长度不能低于6位');
            }
            if (strlen($password) == 6 && ctype_digit($password)) {
                die('6位密码不能为纯数字');
            }
        }

        try {
            $updateData = [
                'username' => $username,
                'real_name' => $real_name,
                'phone' => $phone,
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            if (!empty($password)) {
                $updateData['password_hash'] = $password;  // 明文存储（同创建逻辑）
            }
            $DB->update('users', $updateData, ['id' => $id]);

            // 更新角色
            $DB->delete('user_roles', ['user_id' => $id]);

            if (!empty($role_id)) {
                $DB->insert('user_roles', [
                    'user_id' => $id,
                    'role_id' => $role_id,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }

            auditLog('UPDATE', 'user', $id, '更新用户: ' . $username);

            redirect('/user/list?success=用户已更新');
        } catch (Exception $e) {
            die('更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除用户
     */
    public function delete($id = null)
    {
        global $DB;

        $id = intval(isset($id) ? $id : 0);

        if ($id <= 0) {
            die('用户ID无效');
        }

        try {
            $user = $DB->query("SELECT * FROM users WHERE id = ?", [$id]);
            if (!$user) {
                die('用户不存在');
            }
            if ($id == $_SESSION['user_id']) {
                die('不能删除自己');
            }
            if ($user['username'] === 'admin') {
                die('不能删除admin用户');
            }

            // 非超级管理员不可删除超级管理员
            $targetRole = $DB->query("SELECT r.role_name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?", [$id]);
            if ($targetRole && $targetRole['role_name'] === 'super_admin' && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin')) {
                die('非超级管理员不能删除超级管理员');
            }

            $DB->execute("SET FOREIGN_KEY_CHECKS = 0");
            $DB->delete('users', ['id' => $id]);
            $DB->delete('user_roles', ['user_id' => $id]);
            $DB->execute("SET FOREIGN_KEY_CHECKS = 1");

            // DEF016: 操作员删除操作不纳入审计范围
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'operator') {
                auditLog('DELETE', 'user', $id, '删除用户: ' . $user['username']);
            }

            redirect('/user/list?success=用户已删除');
        } catch (Exception $e) {
            die('删除失败: ' . $e->getMessage());
        }
    }
}

?>