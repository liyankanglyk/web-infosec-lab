<?php
/**
 * 登录控制器
 * 包含缺陷: DEF001, DEF002, DEF005, DEF006, DEF007, DEF008, DEF024, DEF028
 */

namespace Controllers;

class LoginController
{

    /**
     * 显示登录页面
     */
    public function index()
    {
        pageHead('登录');
        ?>
        <div class="auth">
            <div class="auth-top">
                <div class="brand-mark"><?= icon('shield') ?></div>
                <span>Web信息安全性实验系统</span>
                <span class="sub">登录</span>
            </div>
            <div class="auth-body">
                <div class="auth-card wide">
                    <div class="h-eyebrow">实验入口</div>
                    <h1>登录实验环境</h1>
                    <p class="lede">请使用实验下发的账号进入业务系统。</p>

                    <div class="cred-box">
                        <div class="cb-title">测试凭证</div>
                        <div class="kv"><span class="k">用户名</span><span class="v mono">&nbsp;</span></div>
                        <div class="kv"><span class="k">密码</span><span class="v mono">&nbsp;</span></div>
                    </div>

                    <?php if (isset($_GET['error'])): ?>
                        <?php flashMessage('error', $_GET['error']); ?>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo buildUrl('/login/authenticate'); ?>">
                        <div class="field">
                            <label class="lbl" for="username">用户名</label>
                            <input class="input" type="text" id="username" name="username" required autofocus autocomplete="username">
                        </div>
                        <div class="field">
                            <label class="lbl" for="password">密码</label>
                            <input class="input" type="password" id="password" name="password" required autocomplete="current-password">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">登录</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="auth-foot">
                Web信息安全性实验系统 · 仅供受控实验环境使用
            </div>
        </div>
        </body>
        </html>
        <?php
    }

    /**
     * 处理登录认证
     * 缺陷位置: DEF002, DEF005, DEF006, DEF028
     */
    public function authenticate()
    {
        global $DB;

        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        if (empty($username) || empty($password)) {
            redirect('/login?error=用户名和密码不能为空');
        }

        // DEF006: 登录凭证通过HTTP明文传输（在本文件中体现为无加密处理）
        // 实际上如果系统使用HTTP而非HTTPS，就会出现明文传输

        // 查询用户 - DEF001: 内置默认账户 superadmin 未重命名/未删除，可直接用默认口令登录
        try {
            $user = $DB->query("SELECT * FROM users WHERE username = ?", [$username]);
        } catch (Exception $e) {
            redirect('/login?error=数据库查询失败');
        }

        if (!$user) {
            redirect('/login?error=用户名或密码错误');
        }

        // DEF005: 密码是明文存储的，直接比较明文
        // 正常应该使用password_verify($password, $user['password_hash'])
        if ($user['password_hash'] !== $password) {
            redirect('/login?error=用户名或密码错误');
        }

        // DEF028: 无登录失败次数限制和账户锁定
        // 登录成功 - DEF002: 首次登录无强制改密
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        // 查询用户实际角色
        $roleData = $DB->query("SELECT r.role_name FROM roles r JOIN user_roles ur ON r.id = ur.role_id WHERE ur.user_id = ?", [$user['id']]);
        $_SESSION['user_role'] = $roleData ? $roleData['role_name'] : '';  // 从数据库获取真实角色

        // DEF007, DEF008: 在Cookie中存储敏感信息
        setcookie('username', $user['username'], time() + 86400, '/');
        setcookie('user_id', $user['id'], time() + 86400, '/');
        setcookie('auth_key', md5($user['id'] . $user['username']), time() + 86400, '/');

        // DEF024: 登录成功只更新 users.last_login，从不向 login_logs 表写入任何记录（该表实际恒为空）
        // 更新最后登录时间
        $DB->update('users', [
            'last_login' => date('Y-m-d H:i:s'),
            'last_login_ip' => $_SERVER['REMOTE_ADDR']
        ], [
            'id' => $user['id']
        ]);

        auditLog('LOGIN', 'user', $user['id'], '用户登录');

        redirect('/dashboard');
    }

    /**
     * 登出
     */
    public function logout()
    {
        auditLog('LOGOUT', 'user', $_SESSION['user_id'], '用户登出');

        session_destroy();
        setcookie('username', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');
        setcookie('auth_key', '', time() - 3600, '/');
        redirect('/login');
    }
}

?>