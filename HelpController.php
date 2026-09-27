<?php
/**
 * 帮助与系统设置控制器
 */

namespace Controllers;

class HelpController
{
    public function faq()
    {
        $content = <<<HTML
            <h2>常见问题</h2>
            <section>
                <h3>1. 如何登录？</h3>
                <p>使用系统账号登录。初始账号为 <strong>superadmin</strong> / <strong>123456</strong>。</p>
            </section>
            <section>
                <h3>2. 登录后没有权限访问页面怎么办？</h3>
                <p>如果实际遇到访问受限，请确认当前会话是否已登录，或使用管理员账号登录。</p>
            </section>
            <section>
                <h3>3. 如何创建新项目？</h3>
                <p>进入“项目管理”页面，点击“新建项目”，填写项目名称、项目代码、状态和说明后提交即可。</p>
            </section>
            <section>
                <h3>4. 如何备份与恢复？</h3>
                <p>进入“备份管理”页面，点击“执行手动备份”生成备份记录；恢复操作会读取现有备份并恢复到数据库。</p>
            </section>
            <section>
                <h3>5. 审计日志作用是什么？</h3>
                <p>审计日志用于记录系统操作行为。实际生产环境中应保证日志完整性与审计可追溯性。</p>
            </section>
            <section>
                <h3>6. 为什么有些按钮会跳转主页？</h3>
                <p>这是因为系统当前部署在子目录中时，部分页面路径还需要适配基地址。已修复后应当正常跳转。</p>
            </section>
            <section>
                <h3>7. 初始化系统？</h3>
                <p>使用superadmin登录在系统设置中初始化系统。</p>
            </section>
HTML;
        $this->renderPage('常见问题', $content, 'help');
    }

    public function manual()
    {
        $content = <<<HTML
            <h2>用户手册</h2>
            <section>
                <h3>登录与访问</h3>
                <p>在浏览器中访问系统入口地址，输入用户名和密码登录。登录成功后进入仪表板。</p>
            </section>
            <section>
                <h3>项目管理</h3>
                <ul>
                    <li>查看项目列表、编辑项目、删除项目。</li>
                    <li>新建检测项目时请填写名称、唯一代码、状态与描述。</li>
                    <li>删除操作会直接从数据库移除数据，演示系统不做回收。</li>
                </ul>
            </section>
            <section>
                <h3>用户管理</h3>
                <ul>
                    <li>管理员可创建、更新用户信息。</li>
                </ul>
            </section>
            <section>
                <h3>审计日志</h3>
                <p>审计日志记录用户操作行为、时间、结果等信息。用于查看系统变更和安全事件。</p>
            </section>
            <section>
                <h3>备份管理</h3>
                <p>备份页面用于生成数据库备份文件，并支持恢复。恢复操作会覆盖当前数据。</p>
            </section>
            <section>
                <h3>退出登录</h3>
                <p>点击“登出”即可退出当前会话，返回登录页面。</p>
            </section>
HTML;
        $this->renderPage('用户手册', $content, 'help');
    }

    public function security()
    {
        $content = <<<HTML
            <h2>安全指南</h2>
            <section>
                <h3>账号与密码</h3>
                <p>请使用复杂密码并妥善保管账号信息。</p>
            </section>
            <section>
                <h3>访问控制</h3>
                <p>生产环境应按角色分离权限，限制普通用户访问管理页面。</p>
            </section>
            <section>
                <h3>会话安全</h3>
                <p>使用 HTTPS 保护登录凭证与会话 Cookie，避免在不安全网络中泄露敏感信息。</p>
            </section>
            <section>
                <h3>审计与日志</h3>
                <p>审计日志应记录关键操作，并保证日志不能被普通用户篡改。日志应定期归档与备份。</p>
            </section>
            <section>
                <h3>数据备份</h3>
                <p>定期备份数据库并验证恢复过程。恢复前请确认备份文件来源可信且未被篡改。</p>
            </section>
HTML;
        $this->renderPage('安全指南', $content, 'help');
    }

    public function settings()
    {
        global $DB;
        $content = <<<HTML
            <h2>系统设置</h2>
            <section>
                <h3>基本配置</h3>
                <p>包括系统名称、时区、页面主题等基础信息。当前演示页面仅展示说明，未实现真正配置保存。</p>
            </section>
            <section>
                <h3>备份策略</h3>
                <p>建议设置自动备份周期、备份保存时间和备份存储位置。演示系统仅提供手动备份说明。</p>
            </section>
            <section>
                <h3>安全策略</h3>
                <p>建议启用强密码策略、账户锁定、会话超时和操作审计。测试系统目前未完全实现这些策略。</p>
            </section>
            <section>
                <h3>系统维护</h3>
                <p>定期检查日志、数据库连接、备份文件和应用更新。系统维护对于长期稳定运行非常重要。</p>
            </section>
            <section>
                <h3>说明</h3>
                <p>当前页面为功能演示，实际生产版本应通过管理后台提供可配置项，并将配置保存到数据库或配置文件。</p>
            </section>
HTML;
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') {
            $content .= '<section><h3>系统初始化</h3><p>清空所有数据并恢复到初始状态，此操作不可撤销。</p><form method="POST" action="' . buildUrl('/help/init') . '" onsubmit="return confirm(\'确定要初始化系统吗？所有数据将被清空！\');"><button type="submit" class="btn btn-danger">初始化系统</button></form></section>';
        }
        $this->renderPage('系统设置', $content, 'settings');
    }

    private function renderPage($title, $content, $active = 'help')
    {
        appShellOpen($title, $active, [['label' => '支持'], ['label' => $title]]);
        ?>
        <article class="panel">
            <div class="panel-body doc-body">
                <?php echo $content; ?>
                <div style="margin-top:26px;padding-top:16px;border-top:1px solid var(--line-hair);display:flex;gap:8px">
                    <a class="btn btn-quiet" href="<?php echo buildUrl('/dashboard'); ?>"><?= icon('back', 'ic ic-sm') ?>返回工作台</a>
                </div>
            </div>
        </article>
        <?php
        appShellClose();
    }

    public function init()
    {
        global $DB;

        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
            die('403: 仅超级管理员可操作');
        }

        // ⓪ fail-fast：必须先确认唯一数据源可读，再执行任何破坏性重建
        $schemaFile = businessSchemaFile();
        if ($schemaFile === null) {
            die('初始化失败: sql/业务库建库脚本.sql 文件不存在');
        }
        $schemaSql = file_get_contents($schemaFile);

        try {
            // ① 整表删除后重建（脚本内为裸 CREATE TABLE，故需 DROP 而非 TRUNCATE）
            $DB->execute("SET FOREIGN_KEY_CHECKS = 0");
            $tables = ['audit_config', 'audit_logs', 'backup_records', 'detection_projects', 'detection_results', 'login_logs', 'permissions', 'restore_records', 'role_permissions', 'roles', 'system_config', 'user_roles', 'users'];
            foreach ($tables as $table) {
                $DB->execute("DROP TABLE IF EXISTS $table");
            }
            $DB->execute("SET FOREIGN_KEY_CHECKS = 1");

            // ② 重建表结构与种子数据
            // 去掉 CREATE DATABASE 和 USE 行（当前已连接数据库）
            $schemaSql = preg_replace('/^CREATE DATABASE.*?;$/im', '', $schemaSql);
            $schemaSql = preg_replace('/^USE .*?;$/im', '', $schemaSql);

            $statements = $this->splitSql($schemaSql);
            foreach ($statements as $stmt) {
                $stmt = trim($stmt);
                if (!empty($stmt)) {
                    $DB->execute($stmt);
                }
            }

            // ③ 生成4990条审计日志
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
                $tpl = $ops[array_rand($ops)];
                $day = rand(1, 10);
                $date = '2026-07-' . sprintf('%02d', $day);
                $hour = rand(0, 23);
                $min = rand(0, 59);
                $sec = rand(0, 59);
                $ts = sprintf('%s %02d:%02d:%02d', $date, $hour, $min, $sec);
                $objId = rand(1, 20);
                $desc = $tpl[4];
                if (in_array($tpl[0], ['CREATE', 'UPDATE', 'DELETE']) && $tpl[1] == 'user') {
                    $desc .= ': ' . $usernames[array_rand($usernames)];
                } elseif ($tpl[0] == 'DELETE' && $tpl[1] == 'audit_log') {
                    $desc .= ': ' . rand(1, 50) . ' 条';
                } elseif (in_array($tpl[0], ['CREATE', 'UPDATE', 'DELETE']) && $tpl[1] == 'project') {
                    $desc .= ': ' . $objNames[array_rand($objNames)];
                }
                $values[] = "('$date', 1, '{$tpl[2]}', '{$tpl[3]}', '{$tpl[0]}', '{$tpl[1]}', $objId, '$desc', NULL, NULL, '', '$ua', 'success', '$ts')";
                if (count($values) >= 100) {
                    $DB->execute($sql . implode(',', $values));
                    $values = [];
                }
            }
            if (!empty($values)) {
                $DB->execute($sql . implode(',', $values));
            }

            redirect('/help/settings?msg=系统初始化完成');
        } catch (\Exception $e) {
            logError('系统初始化失败: ' . $e->getMessage());
            die('初始化失败: ' . $e->getMessage());
        }
    }

    /**
     * 拆分SQL语句（按分号分隔，忽略注释）
     */
    private function splitSql($sql)
    {
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
