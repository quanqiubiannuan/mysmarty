<?php
return [
    // smtp发送电子邮件
    'smtp' => [
        // 发送服务器
        'hostname' => env('SMTP_HOSTNAME', ''),
        // 端口
        'port' => env('SMTP_PORT', 465),
        // 是否使用SSL
        'useSSl' => env('SMTP_USESSL', true),
        // 发送邮箱
        'sendEmailUser' => env('SMTP_SENDEMAILUSER', ''),
        // 发送邮箱密码/授权码
        'sendEmailPass' => env('SMTP_SENDEMAILPASS', ''),
        // 发送邮箱显示名称
        'showEmail' => env('SMTP_SHOWEMAIL', ''),
        // 连接超时，单位秒
        'timeout' => env('SMTP_TIMEOUT', 5),
        // 读取超时，单位秒
        'readTimeout' => env('SMTP_READTIMEOUT', 3)
    ]
];