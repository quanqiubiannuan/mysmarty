<?php
/**
 * 应用配置
 */

return [
    // 调试，false 关闭，true 开启
    'debug' => env('APP_DEBUG', false),
    // 应用初始化执行方法
    'app_init' => env('APP_INIT', ''),
    // 加密 key，定义之后不要修改，否则会导致之前加密的数据无法解密
    'encryption_key' => env('ENCRYPTION_KEY', ''),
    // 默认时区
    'default_timezone' => env('DEFAULT_TIMEZONE', 'Asia/Shanghai'),
    // 应用默认url，不要以http开头或以 / 结尾，如：127.0.0.1
    'app_url' => env('APP_URL', '')
];