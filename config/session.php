<?php
// session设置
return [
    // 开启状态，1 总是开启 2 根据情况按需开启
    'status' => env('SESSION_STATUS', 2),
    // Cookie 的 生命周期，以秒为单位。
    'lifetime' => env('SESSION_LIFETIME', 3600),
    // 此 cookie 的有效 路径。 on the domain where 设置为“/”表示对于本域上所有的路径此 cookie 都可用。
    'path' => env('SESSION_PATH', '/'),
    // Cookie 的作用 域。 例如：“www.php.net”。 如果要让 cookie 在所有的子域中都可用，此参数必须以点（.）开头，例如：“.php.net”。
    'domain' => env('SESSION_DOMAIN', ''),
    // 设置为 TRUE 表示 cookie 仅在使用 安全 链接时可用。
    'secure' => env('SESSION_SECURE', false),
    // 设置为 TRUE 表示 PHP 发送 cookie 的时候会使用 httponly 标记。
    'httponly' => env('SESSION_HTTPONLY', true)
];