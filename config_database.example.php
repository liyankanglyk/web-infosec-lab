<?php
/**
 * 数据库配置模板
 *
 * 使用方式：复制本文件为 config_database.php，按本地环境修改后再启动系统。
 *
 *   cp config_database.example.php config_database.php
 *
 * - platform: 平台管理库（组织信息、平台管理员）。该库可连通时进入多租户模式，
 *             业务系统通过 /{org_code}/ 前缀访问；不存在时自动回退单租户模式。
 * - database: 默认业务库（兼容单租户模式）。
 *
 * 建库脚本见 sql/业务库建库脚本.sql（业务库 websec_db）与 sql/平台库建库脚本.sql（平台库 websec_platform）。
 */

$config = [
    // 平台管理库配置
    'platform' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'database' => 'websec_platform',
        'user'     => 'root',
        'password' => '在这里填写你的 MySQL 口令',
        'charset'  => 'utf8mb4'
    ],

    // 默认业务库（单租户模式）
    'database' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'database' => 'websec_db',
        'user'     => 'root',
        'password' => '在这里填写你的 MySQL 口令',
        'charset'  => 'utf8mb4'
    ],
];

return $config;
