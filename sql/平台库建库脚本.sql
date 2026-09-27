-- Web信息安全性实验系统 · 平台库建库脚本（多租户控制面）
-- 用于管理多个检测组织（多租户）
-- 创建时间：2026-07-24
-- 对应配置：config_database.php → $config['platform']['database'] = 'websec_platform'

CREATE DATABASE IF NOT EXISTS websec_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE websec_platform;

-- ============================================================
-- 组织（租户）表
-- 每个组织拥有独立的数据库和MySQL用户
-- ============================================================
CREATE TABLE IF NOT EXISTS organizations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    org_code VARCHAR(50) NOT NULL UNIQUE COMMENT '组织代号，用于URL路径和库名',
    org_name VARCHAR(255) NOT NULL COMMENT '组织全称',
    db_name VARCHAR(100) NOT NULL COMMENT '组织数据库名 websec_org_{code}',
    db_user VARCHAR(100) NOT NULL COMMENT '组织MySQL用户名',
    db_password VARCHAR(255) NOT NULL COMMENT '组织MySQL密码（明文，供交付和直连使用）',
    db_host VARCHAR(100) DEFAULT 'localhost',
    db_port INT DEFAULT 3306,
    status ENUM('active','inactive') DEFAULT 'active' COMMENT 'active=正常 inactive=停用',
    description TEXT COMMENT '备注',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_org_code (org_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='组织/租户信息';

-- ============================================================
-- 平台管理员表
-- 平台级管理员（管理所有组织，非组织内部用户）
-- ============================================================
CREATE TABLE IF NOT EXISTS platform_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL COMMENT '密码hash（区别于组织库的明文存储）',
    real_name VARCHAR(100),
    email VARCHAR(255),
    status ENUM('active','inactive') DEFAULT 'active',
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='平台管理员';

-- ============================================================
-- 平台操作审计日志
-- ============================================================
CREATE TABLE IF NOT EXISTS platform_audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    platform_user_id INT,
    username VARCHAR(100),
    operation_type VARCHAR(50) NOT NULL COMMENT '操作类型：create_org/delete_org/init_org/...',
    object_type VARCHAR(50) COMMENT '操作对象类型',
    object_id INT COMMENT '操作对象ID',
    operation_desc TEXT COMMENT '操作描述',
    client_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (platform_user_id) REFERENCES platform_users(id) ON DELETE SET NULL,
    INDEX idx_operation (operation_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='平台审计日志';

-- ============================================================
-- 初始平台管理员
-- 密码字段使用 __FIRST_RUN__ 标记，首次访问时强制设置密码
-- ============================================================
INSERT INTO platform_users (username, password_hash, real_name, email) 
VALUES ('admin', '__FIRST_RUN__', '平台管理员', 'admin@example.com');
