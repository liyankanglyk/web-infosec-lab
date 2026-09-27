<?php
/**
 * Web信息安全性实验系统 · 入口
 * PHP 7.4+
 */

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// 错误输出
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 会话初始化
// DEF026: 无会话超时 / 空闲退出逻辑——不记录最后活动时间、不设过期，会话长期有效
session_start();

// 常量
define('BASE_PATH', __DIR__);
define('LOG_PATH', BASE_PATH . '/logs/');
define('BACKUP_PATH', BASE_PATH . '/backups/');

define('CONFIG_FILE', BASE_PATH . '/config_database.php');

// 确保日志目录存在
if (!is_dir(LOG_PATH)) {
    mkdir(LOG_PATH, 0755, true);
}

// 确保备份目录存在
if (!is_dir(BACKUP_PATH)) {
    mkdir(BACKUP_PATH, 0755, true);
}

// 加载配置
if (!file_exists(CONFIG_FILE)) {
    die('配置文件不存在: config_database.php');
}

$config = require_once CONFIG_FILE;
if (!isset($config['database'])) {
    die('数据库配置无效');
}

// 数据库连接
// 优先连接平台管理库，失败则回退到默认业务库（单租户兼容模式）
$platformAvailable = false;
try {
    $DB = new Database($config['platform']);
    $platformAvailable = true;
} catch (Exception $e) {
    // 平台库不可用（未导入 sql/平台库建库脚本.sql），回退到原单库模式
    try {
        $DB = new Database($config['database']);
    } catch (Exception $e2) {
        die('数据库连接失败: ' . $e2->getMessage());
    }
}

// 审计开关：从 audit_config 表加载
if (!isset($_SESSION['audit_disabled'])) {
    try {
        $auditCfg = $DB->query("SELECT config_value FROM audit_config WHERE config_key = 'enable_audit'");
        $_SESSION['audit_disabled'] = ($auditCfg && $auditCfg['config_value'] == '0');
    } catch (Exception $e) {
        $_SESSION['audit_disabled'] = false;
    }
}

// 展示层：布局与组件（不影响任何业务与安全逻辑）
require_once BASE_PATH . '/layout.php';

// 自动加载控制器
spl_autoload_register(function ($class) {
    $prefix = 'Controllers\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $name = substr($class, strlen($prefix));
    $file = BASE_PATH . '/' . $name . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 路由解析
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptPath = $_SERVER['SCRIPT_NAME'];
$scriptDir = dirname($scriptPath);

if ($scriptDir !== '/' && strpos($requestUri, $scriptDir) === 0) {
    $requestUri = substr($requestUri, strlen($scriptDir));
}

$requestUri = trim($requestUri, '/');
if (strpos($requestUri, basename($scriptPath) . '/') === 0) {
    $requestUri = substr($requestUri, strlen(basename($scriptPath)) + 1);
}

$requestUri = trim($requestUri, '/');
if ($requestUri === '') {
    $requestUri = 'dashboard/index';
}

$parts = explode('/', $requestUri);
$firstSegment = isset($parts[0]) ? $parts[0] : '';

// ========= 多租户模式检测 =========
if ($platformAvailable && $firstSegment === 'platform') {
    // 平台管理模式
    $_SESSION['mode'] = 'platform';
    array_shift($parts);
    $controller = 'Platform';
    $action = isset($parts[0]) && $parts[0] !== '' ? $parts[0] : 'dashboard';
    $args = array_slice($parts, 1);
} elseif ($platformAvailable && $firstSegment !== '' && ($orgInfo = findOrgByCode($firstSegment))) {
    // 组织模式：切换到组织独立数据库
    if (isset($_SESSION['org_code']) && $_SESSION['org_code'] !== $orgInfo['org_code']) {
        // 切换到不同组织，清除旧登录状态
        unset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['username']);
    }
    $_SESSION['mode'] = 'org';
    $_SESSION['org_code'] = $orgInfo['org_code'];
    $_SESSION['org_id'] = $orgInfo['id'];

    // 切换到组织数据库
    $DB = new Database([
        'host'     => $orgInfo['db_host'],
        'port'     => $orgInfo['db_port'],
        'database' => $orgInfo['db_name'],
        'user'     => $orgInfo['db_user'],
        'password' => $orgInfo['db_password'],
        'charset'  => 'utf8mb4'
    ]);

    array_shift($parts);
    $controller = ucfirst(isset($parts[0]) && $parts[0] !== '' ? $parts[0] : 'Dashboard');
    $action = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : 'index';
    $args = array_slice($parts, 2);
} else {
    // 单租户兼容模式（原行为不变）
    $_SESSION['mode'] = 'single';
    $controller = ucfirst(isset($parts[0]) && $parts[0] !== '' ? $parts[0] : 'Dashboard');
    $action = isset($parts[1]) && $parts[1] !== '' ? $parts[1] : 'index';
    $args = array_slice($parts, 2);
}

if ($action === 'list') {
    $action = 'index';
}

$controllerClass = 'Controllers\\' . $controller . 'Controller';
$controllerFile = BASE_PATH . '/' . $controller . 'Controller.php';

if (!file_exists($controllerFile)) {
    $controllerClass = 'Controllers\\DashboardController';
    $action = 'index';
}

try {
    if (!class_exists($controllerClass)) {
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
        }
    }

    if (!class_exists($controllerClass)) {
        die('404: 控制器不存在');
    }

    $ctrl = new $controllerClass();

    if (!isPublicPage($controller, $action) && !isLoggedIn()) {
        redirect('/login');
    }

    if (!isPublicPage($controller, $action) && !hasPermission($action)) {
        die('403: 禁止访问');
    }

    if (method_exists($ctrl, $action)) {
        call_user_func_array([$ctrl, $action], $args);
    } elseif (method_exists($ctrl, 'notfound')) {
        $ctrl->notfound();
    } else {
        die('404: 操作不存在');
    }
} catch (Exception $e) {
    logError($e->getMessage());
    die('500: 系统错误');
}

// ========= 辅助函数 =========
function isPublicPage($controller, $action)
{
    // 平台登录页公开
    if (isset($_SESSION['mode']) && $_SESSION['mode'] === 'platform' && $controller === 'Platform' && $action === 'login') {
        return true;
    }
    $publicControllers = ['Login'];
    return in_array(ucfirst(strtolower($controller)), $publicControllers);
}

function findOrgByCode($code)
{
    global $DB;
    try {
        return $DB->query("SELECT * FROM organizations WHERE org_code = ? AND status = 'active'", [$code]);
    } catch (Exception $e) {
        return null;
    }
}

function isLoggedIn()
{
    // 平台模式使用独立的登录状态
    if (isset($_SESSION['mode']) && $_SESSION['mode'] === 'platform') {
        return isset($_SESSION['platform_user_id']) && !empty($_SESSION['platform_user_id']);
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function hasPermission($permission)
{
    // 平台管理员拥有平台所有权限
    if (isset($_SESSION['mode']) && $_SESSION['mode'] === 'platform' && isset($_SESSION['platform_user_id'])) {
        return true;
    }

    if (!isset($_SESSION['user_role'])) {
        return false;
    }

    // DEF003: 权限检查弱化，所有已登录用户均可访问
    return in_array($_SESSION['user_role'], ['super_admin', 'admin', 'operator', 'auditor']);
}

function getBaseUrl()
{
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    $scriptDir = dirname($scriptPath);

    if ($scriptDir === '/' || $scriptDir === '\\') {
        return '';
    }

    return rtrim($scriptDir, '/');
}

function buildUrl($url)
{
    if (strpos($url, '/') !== 0) {
        return $url;
    }

    $baseUrl = getBaseUrl();
    $scriptName = basename($_SERVER['SCRIPT_NAME']);

    // 多租户模式：自动添加前缀
    $prefix = '';
    if (isset($_SESSION['mode'])) {
        if ($_SESSION['mode'] === 'platform') {
            $prefix = '/platform';
        } elseif ($_SESSION['mode'] === 'org' && isset($_SESSION['org_code'])) {
            $prefix = '/' . $_SESSION['org_code'];
        }
    }

    if ($baseUrl === '') {
        return '/' . $scriptName . $prefix . $url;
    }

    return $baseUrl . '/' . $scriptName . $prefix . $url;
}

function redirect($url)
{
    header('Location: ' . buildUrl($url));
    exit;
}

function getCurrentUser()
{
    global $DB;

    if (!isLoggedIn()) {
        return null;
    }

    // 平台模式不适用（平台使用 platform_users 表）
    if (isset($_SESSION['mode']) && $_SESSION['mode'] === 'platform') {
        return null;
    }

    return $DB->query('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
}

/**
 * 业务库结构脚本（唯一数据源）路径解析
 *
 * 「初始化系统」与「组织建库/重建」均依赖此脚本，且需在破坏性操作前完成定位，
 * 因此统一由本函数解析（主名为中文文件名，同时保留早期 ASCII 名与根目录布局兼容），找不到返回 null。
 */
function businessSchemaFile()
{
    $candidates = [
        BASE_PATH . '/sql/业务库建库脚本.sql',
        BASE_PATH . '/sql/database_schema.sql',
        BASE_PATH . '/业务库建库脚本.sql',
        BASE_PATH . '/database_schema.sql'
    ];
    foreach ($candidates as $file) {
        if (is_file($file)) {
            return $file;
        }
    }
    return null;
}

function logError($message)
{
    $logFile = LOG_PATH . date('Y-m-d') . '.log';
    $content = '[' . date('Y-m-d H:i:s') . '] ERROR: ' . $message . "\n";
    file_put_contents($logFile, $content, FILE_APPEND);
}

function auditLog($operation_type, $object_type, $object_id, $description, $old_value = null, $new_value = null)
{
    global $DB;

    if (!isLoggedIn()) {
        return;
    }

    // DEF023: 审计开关仅存于会话与 audit_config，无保护（可在 AuditController::toggleaudit() 被关闭），关闭后全局不记录
    if (isset($_SESSION['audit_disabled']) && $_SESSION['audit_disabled']) {
        return;
    }

    // DEF022: 容量不足，超过上限不记录
    $maxCfg = $DB->query("SELECT config_value FROM system_config WHERE config_key = 'max_audit_logs'");
    $maxLogs = $maxCfg ? intval($maxCfg['config_value']) : 5000;
    $countRow = $DB->query("SELECT COUNT(*) as count FROM audit_logs");
    if ($countRow && $countRow['count'] >= $maxLogs) {
        return;
    }

    $user = getCurrentUser();

    $sql = "INSERT INTO audit_logs (
        operation_time, user_id, username, user_role,
        operation_type, object_type, object_id, operation_desc,
        old_value, new_value, client_ip, user_agent, result
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    // DEF015: operation_time 取应用服务器时间 date('Y-m-d')，无数据库时间戳固化机制，改系统时间即可篡改操作时间
    $params = [
        date('Y-m-d'),
        $_SESSION['user_id'],
        isset($user['username']) ? $user['username'] : 'unknown',
        isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'unknown',
        $operation_type,
        $object_type,
        $object_id,
        $description,
        $old_value,
        $new_value,
        // DEF018: client_ip 恒为空串，不记录操作来源 IP
        '',
        isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
        'success'
    ];

    try {
        $DB->execute($sql, $params);
    } catch (Exception $e) {
        logError('审计日志写入失败: ' . $e->getMessage());
    }
}

class Database
{
    private $pdo;
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
        $this->connect();
    }

    private function connect()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $this->config['host'],
                $this->config['port'],
                $this->config['database'],
                isset($this->config['charset']) ? $this->config['charset'] : 'utf8mb4'
            );

            $this->pdo = new PDO(
                $dsn,
                $this->config['user'],
                $this->config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4'
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('数据库连接失败: ' . $e->getMessage());
        }
    }

    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception('查询失败: ' . $e->getMessage());
        }
    }

    public function queryAll($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception('查询失败: ' . $e->getMessage());
        }
    }

    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception('执行失败: ' . $e->getMessage());
        }
    }

    public function insert($table, $data)
    {
        $columns = array_keys($data);
        $values = array_values($data);
        $placeholders = implode(',', array_fill(0, count($values), '?'));

        $sql = "INSERT INTO $table (" . implode(',', $columns) . ") VALUES ($placeholders)";
        return $this->execute($sql, $values);
    }

    public function update($table, $data, $where)
    {
        $setParts = [];
        $values = [];

        foreach ($data as $key => $value) {
            $setParts[] = "$key = ?";
            $values[] = $value;
        }

        $whereParts = [];
        foreach ($where as $key => $value) {
            $whereParts[] = "$key = ?";
            $values[] = $value;
        }

        $sql = "UPDATE $table SET " . implode(', ', $setParts);
        if (!empty($whereParts)) {
            $sql .= " WHERE " . implode(' AND ', $whereParts);
        }

        return $this->execute($sql, $values);
    }

    public function delete($table, $where)
    {
        $whereParts = [];
        $values = [];

        foreach ($where as $key => $value) {
            $whereParts[] = "$key = ?";
            $values[] = $value;
        }

        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $whereParts);
        return $this->execute($sql, $values);
    }

    public function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
}

?>