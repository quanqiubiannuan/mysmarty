<?php
/**
 * Cookie配置
 */
return [
    // cookie 保存时间
    'expire' => env('COOKIE_EXPIRE', 0),
    // cookie 保存路径
    'path' => env('COOKIE_PATH', '/'),
    // cookie 有效域名
    'domain' => env('COOKIE_DOMAIN', ''),
    //  cookie 启用安全传输
    'secure' => env('COOKIE_SECURE', false),
    // httponly设置
    'httponly' => env('COOKIE_HTTPONLY', false),
    // 是否使用 setcookie
    'setcookie' => env('COOKIE_SETCOOKIE', true)
];