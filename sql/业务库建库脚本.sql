-- Web信息安全性实验系统 · 业务库建库脚本（单租户默认库）
-- 版本：V1.0
-- 创建时间：2026-05-29
-- 对应配置：config_database.php → $config['database']['database'] = 'websec_db'
-- 多租户场景：PlatformController 会本脚本中的库名替换为 websec_org_{组织代号} 后执行

-- 创建数据库
CREATE DATABASE IF NOT EXISTS websec_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE websec_db;

-- ========== 系统表 ==========

-- 用户表 (DEF001, DEF002, DEF005: 预埋缺陷)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,  -- DEF001: admin账户存在
    password_hash VARCHAR(255) NOT NULL,  -- DEF005: 明文存储
    email VARCHAR(255),
    -- DEF029: 个人信息明文存储（real_name / employee_id / phone），且用户列表页不对任何角色做脱敏
    real_name VARCHAR(100),
    employee_id VARCHAR(50),
    phone VARCHAR(20) NOT NULL,  -- DEF030: 强制采集非必需字段
    department VARCHAR(100),
    status ENUM('active','inactive','locked','deleted') DEFAULT 'active',
    last_login DATETIME,
    last_login_ip VARCHAR(45),
    login_fail_count INT DEFAULT 0,  -- DEF028: 无登录失败限制
    locked_until DATETIME,
    password_change_date DATETIME,
    must_change_password TINYINT DEFAULT 1,  -- DEF002: 首次登录无强制改密
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    -- DEF027: username 仅建普通索引 idx_username，无 UNIQUE 约束，因此可创建重名账号
    INDEX idx_username (username),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 角色表
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 权限表
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    permission_code VARCHAR(100) UNIQUE NOT NULL,
    permission_name VARCHAR(100),
    description TEXT,
    module VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 角色权限关联表
CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY unique_role_permission (role_id, permission_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 用户角色关联表
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========== 业务数据表 ==========

-- 检测项目表 (DEF010, DEF011: 数据完整性缺陷)
CREATE TABLE detection_projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_name VARCHAR(255) NOT NULL,
    project_code VARCHAR(100) NOT NULL,
    manager_id INT NOT NULL,
    description TEXT,
    status ENUM('draft','submitted','reviewed','archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    updated_by INT,
    data_integrity_check VARCHAR(64),  -- DEF011: 校验字段无实际作用
    FOREIGN KEY (manager_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status),
    INDEX idx_project_code (project_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 检测结果表
CREATE TABLE detection_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    test_item VARCHAR(255),
    result ENUM('pass','fail','warning') DEFAULT 'pass',
    findings TEXT,
    test_date DATE,
    tester_id INT NOT NULL,
    signature_hash VARCHAR(256),  -- DEF014: 签章字段无实际作用
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES detection_projects(id) ON DELETE CASCADE,
    FOREIGN KEY (tester_id) REFERENCES users(id),
    INDEX idx_project_id (project_id),
    INDEX idx_test_date (test_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========== 审计日志表 (DEF016-026: 审计相关缺陷) ==========

CREATE TABLE audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    operation_time DATE,  -- DEF017, DEF019: 精度不足，仅精确到日期
    user_id INT,
    username VARCHAR(100),
    user_role VARCHAR(50),
    operation_type VARCHAR(50) NOT NULL,  -- DEF016: 删除操作可能不记录
    object_type VARCHAR(50),
    object_id INT,
    operation_desc TEXT,
    old_value LONGTEXT,
    new_value LONGTEXT,
    client_ip VARCHAR(45),  -- DEF018: 可能为NULL
    user_agent VARCHAR(255),
    http_method VARCHAR(10),
    request_url VARCHAR(500),
    request_params LONGTEXT,
    response_status INT,
    result ENUM('success','failed','partial') DEFAULT 'success',
    error_msg TEXT,
    execution_time INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_time (user_id, operation_time),
    INDEX idx_operation (operation_type),
    INDEX idx_log_time (operation_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 登录日志表
CREATE TABLE login_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    username VARCHAR(100),
    ip_address VARCHAR(45),
    login_time DATETIME,
    logout_time DATETIME,
    status ENUM('success','failed','timeout') DEFAULT 'success',
    reason VARCHAR(255),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_login_time (login_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========== 备份恢复表 (DEF012, DEF013: 备份与恢复缺陷) ==========

CREATE TABLE backup_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_name VARCHAR(255),
    backup_type ENUM('manual','auto') DEFAULT 'manual',
    file_path VARCHAR(500),
    file_size BIGINT,
    database_size BIGINT,
    record_count INT,
    backup_time DATETIME,
    created_by INT,
    description TEXT,
    status ENUM('success','failed','deleted') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_backup_time (backup_time),
    INDEX idx_backup_type (backup_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE restore_records (
    id INT PRIMARY KEY AUTO_INCREMENT,
    backup_id INT,
    restore_time DATETIME,
    restored_by INT,
    restore_status ENUM('success','failed','partial') DEFAULT 'success',
    error_message TEXT,
    affected_records INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (backup_id) REFERENCES backup_records(id),
    FOREIGN KEY (restored_by) REFERENCES users(id),
    INDEX idx_restore_time (restore_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========== 系统配置表 ==========

CREATE TABLE system_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value LONGTEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ========== 初始化数据 ==========

-- 插入角色
INSERT INTO roles (role_name, display_name, description) VALUES
('super_admin', '超级管理员', '系统最高权限'),
('admin', '普通管理员', '普通管理员权限'),
('operator', '操作员', '数据操作员权限'),
('auditor', '审计管理员', '审计管理员权限');

-- 插入权限
INSERT INTO permissions (permission_code, permission_name, module) VALUES
('user.create', '创建用户', 'user'),
('user.edit', '编辑用户', 'user'),
('user.delete', '删除用户', 'user'),
('user.view', '查看用户', 'user'),
('role.manage', '管理角色', 'role'),
('permission.manage', '管理权限', 'permission'),
('project.create', '创建项目', 'project'),
('project.edit', '编辑项目', 'project'),
('project.delete', '删除项目', 'project'),
('project.view', '查看项目', 'project'),
('audit.view', '查看审计日志', 'audit'),
('audit.delete', '删除审计日志', 'audit'),
('system.settings', '系统设置', 'system'),
('backup.manage', '备份管理', 'backup'),
('report.export', '导出报告', 'report');

-- 角色权限关联
INSERT INTO role_permissions (role_id, permission_id) 
SELECT r.id, p.id FROM roles r, permissions p 
WHERE r.role_name = 'super_admin';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.role_name = 'admin' AND p.permission_code NOT IN ('role.manage', 'permission.manage', 'system.settings', 'audit.delete');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.role_name = 'operator' AND p.permission_code LIKE 'project.%';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.role_name = 'auditor' AND p.permission_code = 'audit.view';

-- 插入默认管理员账户 (DEF001: 默认账户, DEF002: 默认密码)
INSERT INTO users (username, password_hash, real_name, employee_id, phone, status, must_change_password) 
VALUES ('superadmin', '123456', '系统管理员', '00001', '13800138000', 'active', 1);

-- 分配超级管理员角色给superadmin用户
INSERT INTO user_roles (user_id, role_id) 
VALUES (1, (SELECT id FROM roles WHERE role_name = 'super_admin'));

-- 插入系统配置
-- DEF022: max_audit_logs 仅 5000 条，超限后 auditLog() 静默丢弃，不记录也不告警
INSERT INTO system_config (config_key, config_value, description) VALUES
('audit_enabled', '1', '审计功能是否启用'),
('max_audit_logs', '5000', '审计日志最大条数'),
('session_timeout', '1800', '会话超时时间（秒）'),
('password_expiry_days', '90', '密码过期天数'),
('backup_schedule', '0 0 * * *', 'Cron备份计划'),
('max_login_attempts', '5', '最大登录失败次数'),
('lockout_duration', '600', '账户锁定时长（秒）');

-- 插入审计配置 (DEF020-026: 审计缺陷配置)
INSERT INTO audit_config (config_key, config_value, description) VALUES
('enable_audit', '1', '是否启用审计'),
('audit_database_enabled', '1', '是否记录数据库操作'),
('audit_login_enabled', '1', '是否记录登录信息'),
('min_audit_log_hours', '0', '最小审计日志保留小时数'),
('purge_logs_older_days', '30', '清理超过多少天的日志');

