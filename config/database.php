<?php
/**
 * 数据库配置
 */
return [
    /**
     * 不同的数据库可以配置不同的名称，mysql为默认连接名称
     */
    'mysql' => [
        // 主机ip
        'host' => env('DB_HOST', '127.0.0.1'),
        // mysql 用户名
        'user' => env('DB_USERNAME', 'root'),
        // mysql 密码
        'password' => env('DB_PASSWORD', '123456'),
        // mysql 端口
        'port' => env('DB_PORT', 3306),
        // mysql 默认数据库
        'database' => env('DB_DATABASE', 'test'),
        // mysql 字符编码
        'charset' => env('DB_CHARSET', 'utf8mb4')
    ],
    'redis' => [
        // 主机ip
        'host' => env('REDIS_HOST', '127.0.0.1'),
        // redis 端口
        'port' => env('REDIS_PORT', 6379),
        // redis 密码
        'pass' => env('REDIS_PASS', '')
    ],
    'elasticsearch' => [
        // 协议
        'protocol' => env('ELASTICSEARCH_PROTOCOL', 'http'),
        // 主机ip
        'ip' => env('ELASTICSEARCH_IP', '127.0.0.1'),
        // 端口
        'port' => env('ELASTICSEARCH_PORT', 9200),
        // 默认 数据库，索引
        'database' => env('ELASTICSEARCH_DATABASE', 'test'),
        // 默认 表，文档
        'table' => env('ELASTICSEARCH_TABLE', 'library')
    ]
];