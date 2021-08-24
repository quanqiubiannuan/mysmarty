<?php
//跨域设置,参考：https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Access_control_CORS
return [
    /**
     * 允许访问该资源的外域 URI。对于不需要携带身份凭证的请求
     * 服务器可以指定该字段的值为通配符(Access-Control-Allow-Credentials = false)
     * 表示允许来自所有域的请求
     * 示例：* 或 http://www.example.com
     */
    'access_control_allow_origin' => env('CORS_ACCESS_CONTROL_ALLOW_ORIGIN',''),
    /**
     * 指定了当浏览器的credentials设置为true时
     * 是否允许浏览器读取response的内容
     * 当用在对preflight预检测请求的响应中时
     * 它指定了实际的请求是否可以使用credentials
     * 请注意：简单 GET 请求不会被预检；如果对此类请求的响应中不包含该字段
     * 这个响应将被忽略掉，并且浏览器也不会将相应内容返回给网页
     * 示例：true 或 false
     */
    'access_control_allow_credentials' => env('CORS_ACCESS_CONTROL_ALLOW_CREDENTIALS',''),
    /**
     * 用于预检请求的响应。其指明了实际请求所允许使用的 HTTP 方法
     * 示例：POST, GET, OPTIONS
     */
    'access_control_allow_methods' => env('CORS_ACCESS_CONTROL_ALLOW_METHODS',''),
    /**
     * 用于预检请求的响应。其指明了实际请求中允许携带的首部字段
     * 示例：X-PINGOTHER（自定义的）, Content-Type
     */
    'access_control_allow_headers' => env('CORS_ACCESS_CONTROL_ALLOW_HEADERS',''),
    /**
     * 在跨域访问时，XMLHttpRequest对象的getResponseHeader()方法只能拿到一些最基本的响应头
     * Cache-Control、Content-Language、Content-Type、Expires、Last-Modified、Pragma
     * 如果要访问其他头，则需要服务器设置本响应头
     * 示例：X-My-Custom-Header, X-Another-Custom-Header（自定义的）
     */
    'access_control_expose_headers' => env('CORS_ACCESS_CONTROL_EXPOSE_HEADERS',''),
    /**
     * 指定了preflight请求的结果能够被缓存多久,单位秒
     * 在有效时间内，浏览器无须为同一请求再次发起预检请求
     * 请注意，浏览器自身维护了一个最大有效时间
     * 如果该首部字段的值超过了最大有效时间，将不会生效
     */
    'access_control_max_age' => env('CORS_ACCESS_CONTROL_MAX_AGE',0)
];