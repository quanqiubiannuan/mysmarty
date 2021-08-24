<?php

use library\mysmarty\Cache;
use library\mysmarty\Ckeditor;
use library\mysmarty\Config;
use library\mysmarty\Cookie;
use library\mysmarty\ElasticSearch;
use library\mysmarty\Emoji;
use library\mysmarty\Env;
use library\mysmarty\Query;
use library\mysmarty\Route;
use library\mysmarty\Session;
use library\mysmarty\Tinymce;

/**
 * 格式化字节单位
 * @param int $size 多少字节
 * @param int $decimals 小数点保留几位
 * @return string
 */
function formatFileSize(int $size, int $decimals = 0): string
{
    if ($size < 1024) {
        $str = $size . 'bytes';
    } else if ($size < 1048576) {
        $str = number_format($size / 1024, $decimals, '.', '') . 'KB';
    } else if ($size < 1073741824) {
        $str = number_format($size / 1048576, $decimals, '.', '') . 'MB';
    } else if ($size < 1099511627776) {
        $str = number_format($size / 1073741824, $decimals, '.', '') . 'GB';
    } else {
        $str = number_format($size / 1099511627776, $decimals, '.', '') . 'TB';
    }
    return $str;
}

/**
 * 是否为GET请求
 * @return bool
 */
function isGet(): bool
{
    return getServerValue('REQUEST_METHOD') === 'GET';
}

/**
 * 是否为POST请求
 * @return bool
 */
function isPost(): bool
{
    return getServerValue('REQUEST_METHOD') === 'POST';
}

/**
 * 是否为PUT请求
 * @return bool
 */
function isPut(): bool
{
    return getServerValue('REQUEST_METHOD') === 'PUT';
}

/**
 * 是否为DELTE请求
 * @return bool
 */
function isDelete(): bool
{
    return getServerValue('REQUEST_METHOD') === 'DELETE';
}

/**
 * 是否为HEAD请求
 * @return bool
 */
function isHead(): bool
{
    return getServerValue('REQUEST_METHOD') === 'HEAD';
}

/**
 * 是否为PATCH请求
 * @return bool
 */
function isPatch(): bool
{
    return getServerValue('REQUEST_METHOD') === 'PATCH';
}

/**
 * 是否为OPTIONS请求
 * @return bool
 */
function isOptions(): bool
{
    return getServerValue('REQUEST_METHOD') === 'OPTIONS';
}

/**
 * 判断当前是否为cgi模式
 * @return bool
 */
function isCgiMode(): bool
{
    return str_starts_with(PHP_SAPI, 'cgi');
}

/**
 * 获取当前分配的php内存，字节
 * 1字节(B) = 8 位(bit)
 * 1 kb = 1024 字节
 * 1 mb = 1024 kb
 * @return int
 */
function getMemoryUsage(): int
{
    return memory_get_usage();
}

/**
 * 获取当前时间，微秒
 * 1 毫秒 = 1000 微秒
 * 1 秒 = 1000 毫秒
 * @return float
 */
function getCurrentMicroTime(): float
{
    list($usec, $sec) = explode(' ', microtime());
    return ((float)$usec + (float)$sec);
}

/**
 * 判断服务器是否是windows操作系统
 * @return bool
 */
function isWin(): bool
{
    if (stripos(PHP_OS, 'WIN') === 0) {
        return true;
    }
    return false;
}

/**
 * 文章内容排版
 * @param string $str
 * @param bool $downloadImg 自动下载内容中的图片
 * @param int $editor 富文本编辑器，1 Ckeditor，2 Tinymce
 * @return string
 */
function paiban(string $str, bool $downloadImg = true, int $editor = 1): string
{
    switch ($editor) {
        case 1:
            return Ckeditor::getInstance()->getContent($str, $downloadImg);
        case 2:
            return Tinymce::getInstance()->getContent($str, $downloadImg);
    }
    return $str;
}

/**
 * 下载图片
 * @param string $imgSrc
 * @return bool|string
 */
function downloadImg(string $imgSrc): string|bool
{
    if (0 === stripos($imgSrc, '//')) {
        $imgSrc = 'https:' . $imgSrc;
    }
    if (0 === stripos($imgSrc, 'http')) {
        if (preg_match('/\.jpg/i', $imgSrc)) {
            $hz = 'jpg';
        } else if (preg_match('/\.jpeg/i', $imgSrc)) {
            $hz = 'jpeg';
        } else if (preg_match('/\.gif/i', $imgSrc)) {
            $hz = 'gif';
        } else {
            $hz = 'png';
        }
        $data = Query::getInstance()->setPcUserAgent()
            ->setRandIp()
            ->setUrl($imgSrc)
            ->getOne();
    } else if (0 === stripos($imgSrc, 'data:image')) {
        if (preg_match('~^data:image/(.+);base64,~i', $imgSrc, $mat)) {
            if (false !== stripos($mat[1], 'icon')) {
                $hz = 'ico';
            } else if (false !== stripos($mat[1], 'jpg')) {
                $hz = 'jpg';
            } else if (false !== stripos($mat[1], 'jpeg')) {
                $hz = 'jpeg';
            } else if (false !== stripos($mat[1], 'gif')) {
                $hz = 'gif';
            } else {
                $hz = 'png';
            }
            $data = base64_decode(str_ireplace($mat[0], '', $imgSrc));
        } else {
            return false;
        }
    } else {
        return $imgSrc;
    }
    if (empty($data)) {
        return false;
    }
    $pathDir = '/upload/' . date('Ymd');
    $dir = ROOT_DIR . '/public' . $pathDir;
    if (!createDir($dir)) {
        return false;
    }
    $filename = md5(time() . $imgSrc) . '.' . $hz;
    if (file_put_contents($dir . '/' . $filename, $data)) {
        return $pathDir . '/' . $filename;
    }
    return false;
}

/**
 * 获取当前主域
 * @return string
 */
function getDomain(): string
{
    $domain = getServerValue('SERVER_NAME');
    if (empty($domain)) {
        $domain = getServerValue('HTTP_HOST');
    }
    return $domain;
}

/**
 * 获取文章描叙
 * @param string $content
 * @param int $len
 * @return string
 */
function getDescriptionforArticle(string $content, int $len = 200): string
{
    $content = strip_tags(htmlspecialchars_decode($content));
    $content = preg_replace('/([\n]|[\r\n])/', '', $content);
    $content = preg_replace('/[\s]{2,}/u', '', $content);
    $content = mb_substr($content, 0, $len, 'utf-8');
    $content = myTrim($content);
    $content = htmlspecialchars($content, double_encode: false);
    return preg_replace('/&[\w]+;/Ui', '', $content);
}

/**
 * 去掉空格
 * @param string $str
 * @return string
 */
function myTrim(string $str): string
{
    $str = preg_replace('/^(&nbsp;|\s|<br>|[\x{200B}-\x{200D}])+|(&nbsp;|\s|<br>|[\x{200B}-\x{200D}])+$/iu', '', $str);
    return trim($str);
}

/**
 * 在控制台输出一条消息并换行
 * @param string $msg
 */
function echoCliMsg(string $msg): void
{
    echo $msg . PHP_EOL;
}

/**
 * 获取中文字符串
 * @param int $num 多少个
 * @return string
 */
function getZhChar(int $num = 1): string
{
    $char = '';
    for ($i = 0; $i < $num; $i++) {
        $tmp = chr(mt_rand(0xB0, 0xD0)) . chr(mt_rand(0xA1, 0xF0));
        $char .= iconv('GB2312', 'UTF-8', $tmp);
    }
    return $char;
}

/**
 * 获取body请求的数据
 * @return false|string
 */
function getRequestBodyContent(): string|bool
{
    return file_get_contents('php://input');
}

/**
 * 刷新页面
 * @param string $url 刷新的网址
 * @param int $refreshTime 刷新间隔时间，单位秒
 */
function refresh(string $url = '', int $refreshTime = 1): void
{
    $url = getFixedUrl($url);
    echo '<meta http-equiv="refresh" content="' . $refreshTime . ';url=' . $url . '">';
    exit();
}

/**
 * 是否为控制台模式
 * @return bool
 */
function isCliMode(): bool
{
    return PHP_SAPI === 'cli';
}

/**
 * 获取剩余内存占比
 * @return int 0 - 100
 */
function getMemFreeRate(): int
{
    $data = getMemInfo();
    if (empty($data)) {
        return 0;
    }
    return (int)(100 * $data['MemFree'] / $data['MemTotal']);
}

/**
 * 设置缓存
 * @param string $name 键
 * @param string $value 值
 * @param int $expire 过期时间
 * @return bool
 */
function setCache(string $name, string $value, int $expire = 3600): bool
{
    return Cache::set($name, $value, $expire);
}

/**
 * 获取缓存
 * @param string $name 键
 * @param string $defValue 默认值
 * @return string
 */
function getCache(string $name, string $defValue = ''): string
{
    return Cache::get($name, $defValue);
}

/**
 * 删除缓存
 * @param string $name 键
 * @return bool
 */
function deleteCache(string $name): bool
{
    return Cache::rm($name);
}

/**
 * 获取浏览器useragent
 * @return string
 */
function getUserAgent(): string
{
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * 获取server值
 * @param string $name
 * @param string $defValue
 * @return string
 */
function getServerValue(string $name, string $defValue = ''): string
{
    return $_SERVER[$name] ?? $defValue;
}

/**
 * 格式化js
 * @param string $js
 * @return string
 */
function formatJs(string $js): string
{
    // 替换 /* */
    $js = preg_replace('/\/\*.*\*\//Uis', '', $js);
    $js = preg_replace('/([^:\'"\\\=])\/\/.*([\n]|[\r\n])?/i', '$1', $js);
    // 替换换行
    $js = preg_replace('/([\n]|[\r\n])/', '', $js);
    $js = preg_replace('/[\t]+/', ' ', $js);
    // 替换两个空格及以上空格 为一个
    $js = preg_replace('/[ ]{2,}/', ' ', $js);
    return myTrim($js);
}

/**
 * 格式化css
 * @param string $css
 * @return string
 */
function formatCss(string $css): string
{
    $css = preg_replace('/\/\*.*\*\//Uis', '', $css);
    // 替换换行
    $css = preg_replace('/([\n]|[\r\n])/', '', $css);
    $css = preg_replace('/[\t]+/', ' ', $css);
    // 替换两个空格及以上空格 为一个
    $css = preg_replace('/[ ]{2,}/', ' ', $css);
    return $css;
}

/**
 * 格式化html
 * @param string $html html代码的字符串
 * @return string
 */
function formatHtml(string $html): string
{
    // 不替换pre内的内容
    $preData = [];
    if (preg_match_all('/<pre[^>]*>(.*)<\/pre>/iUs', $html, $mat)) {
        foreach ($mat[1] as $k => $v) {
            $key = 'pre_' . md5($k);
            $preData[$key] = $v;
            $html = str_ireplace($v, $key, $html);
        }
    }
    // 页面中的代码注释
    $html = preg_replace('/<!--.*-->/Us', '', $html);
    // 页面中匹配到js代码
    $reg = '/<script[^>]*>(.*)<\/script>/iUs';
    $html = preg_replace_callback($reg, function ($matchs) {
        $js = preg_replace('/\/\*.*\*\//Uis', '', $matchs[0]);
        return preg_replace('/([^:\'"\\\=])\/\/.*([\n]|[\r\n])?/i', '$1', $js);
    }, $html);
    // 页面中匹配到css代码
    $reg = '/<style[^>]*>(.*)<\/style>/iUs';
    $html = preg_replace_callback($reg, function ($matchs) {
        return preg_replace('/\/\*.*\*\//Uis', '', $matchs[0]);
    }, $html);
    // 替换换行
    $html = preg_replace('/([\n]|[\r\n])/', '', $html);
    $html = preg_replace('/[\t]+/', ' ', $html);
    // 替换两个空格及以上空格 为一个
    $html = preg_replace('/[ ]{2,}/', ' ', $html);
    foreach ($preData as $k => $v) {
        $html = str_ireplace($k, $v, $html);
    }
    return myTrim($html);
}

/**
 * 获取内存信息，单位，字节（kb）
 * 仅支持在Linux系统运行
 * @return array
 */
function getMemInfo(): array
{
    $data = [];
    if (getPlatformName() === 'linux') {
        exec('cat /proc/meminfo', $output);
        if (!empty($output)) {
            foreach ($output as $o) {
                $oArr = explode(':', $o);
                $data[trim($oArr[0])] = intval($oArr[1]);
            }
        }
    }
    return $data;
}

/**
 * 获取操作系统平台
 * @return string
 */
function getPlatformName(): string
{
    return strtolower(PHP_OS);
}

/**
 * 获取后台配置项数据
 * @param string $name 数组键名，支持 . 连接的键名
 * @param mixed $defValue 默认值
 * @return mixed
 */
function config(string $name, mixed $defValue = ''): mixed
{
    return Config::getConfig($name, $defValue);
}

/**
 * 500服务端错误
 * @param string $msg 错误信息
 * @param int $code
 */
function error(string $msg, int $code = 503): void
{
    tip($msg, '/', $code);
}

/**
 * 文件未找到
 */
function notFound(): void
{
    http_response_code(404);
    echoHtmlHeader();
    echo file_get_contents(LIBRARY_DIR . '/tpl/not_found.html');
    exit();
}

/**
 * 重定向
 * @param string $url 跳转网址
 * @param int $code 状态码
 */
function redirect(string $url, int $code = 301): void
{
    $url = getFixedUrl($url);
    header('Location: ' . $url, true, $code);
    exit();
}

/**
 * 获取网站网址
 * @return string
 */
function getAbsoluteUrl(): string
{
    if (defined('URL')) {
        return URL;
    }
    $url = '';
    if (!isCliMode()) {
        $serverPort = (int)getServerValue('SERVER_PORT', 80);
        $url = 'http://';
        if (443 === $serverPort) {
            $url = 'https://';
        }
        $appUrl = \config('app.app_url');
        if (!empty($appUrl)) {
            $url .= $appUrl;
        } else {
            $url .= getServerValue('HTTP_HOST');
        }
    }
    define('URL', $url);
    return $url;
}

/**
 * 提示跳转
 * @param string $msg
 * @param string $url
 * @param int $code
 */
function tip(string $msg, string $url = '', int $code = 200): void
{
    http_response_code($code);
    $color = '#b8daff';
    if ($code >= 400) {
        $color = 'red';
    }
    $url = getFixedUrl($url);
    $html = file_get_contents(LIBRARY_DIR . '/tpl/tip.html');
    $html = str_ireplace('{$url}', $url, $html);
    $html = str_ireplace('{$color}', $color, $html);
    $html = str_ireplace('{$msg}', $msg, $html);
    echoHtmlHeader();
    echo $html;
    exit();
}

/**
 * 获取POST\GET参数数据
 * @param string $name 字段
 * @param mixed $defValue 默认值
 * @param bool $trim 是否去掉空格
 * @return mixed
 */
function input(string $name, mixed $defValue = '', bool $trim = true): mixed
{
    if (isset($_POST[$name])) {
        $value = $_POST[$name];
    } else if (isset($_GET[$name])) {
        $value = $_GET[$name];
    } else {
        $value = $defValue;
    }
    if ($trim && is_string($value)) {
        $value = trim($value);
    }
    return $value;
}

/**
 * 获取GET请求参数
 * @param string $name 字段
 * @param bool $trim 是否去掉空格
 * @return string
 */
function getString(string $name, bool $trim = true): string
{
    $value = $_GET[$name] ?? '';
    if ($value && $trim) {
        $value = myTrim($value);
    }
    return (string)$value;
}

/**
 * 获取GET请求参数
 * @param string $name 字段
 * @return int
 */
function getInt(string $name): int
{
    $value = $_GET[$name] ?? 0;
    return (int)$value;
}

/**
 * 获取GET请求参数
 * @param string $name 字段
 * @return array
 */
function getAarray(string $name): array
{
    $value = $_GET[$name] ?? [];
    if (!is_array($value)) {
        $value = [];
    }
    return $value;
}

/**
 * 获取POST请求参数
 * @param string $name 字段
 * @param bool $trim 是否去掉空格
 * @return string
 */
function getPostString(string $name, bool $trim = true): string
{
    $value = $_POST[$name] ?? '';
    if ($value && $trim) {
        $value = myTrim($value);
    }
    return (string)$value;
}

/**
 * 获取POST请求参数
 * @param string $name 字段
 * @return int
 */
function getPostInt(string $name): int
{
    $value = $_POST[$name] ?? 0;
    return (int)$value;
}

/**
 * 获取POST请求参数
 * @param string $name 字段
 * @return array
 */
function getPostAarray(string $name): array
{
    $value = $_POST[$name] ?? [];
    if (!is_array($value)) {
        $value = [];
    }
    return $value;
}

/**
 * 获取客户端ip
 * @param bool $getProxyIp 是否获取代理ip
 * @return string
 */
function getIp(bool $getProxyIp = false): string
{
    if ($getProxyIp) {
        $realIp = '';
        if (!empty(getServerValue('HTTP_X_FORWARDED_FOR'))) {
            $arr = explode(',', getServerValue('HTTP_X_FORWARDED_FOR'));
            foreach ($arr as $ip) {
                $ip = trim($ip);
                if ($ip !== 'unknown') {
                    $realIp = $ip;
                    break;
                }
            }
        } else if (!empty(getServerValue('HTTP_CLIENT_IP'))) {
            $realIp = getServerValue('HTTP_CLIENT_IP');
        }
        if (isIp($realIp)) {
            return $realIp;
        }
    }
    return getServerValue('REMOTE_ADDR');
}

/**
 * 检测是否是合法的IP地址
 * @param string $ip IP地址
 * @param string $type IP地址类型 (ipv4, ipv6)
 * @return boolean
 */
function isValidIp(string $ip, string $type = ''): bool
{
    $flag = match (strtolower($type)) {
        'ipv4' => FILTER_FLAG_IPV4,
        'ipv6' => FILTER_FLAG_IPV6,
        default => null,
    };
    return boolval(filter_var($ip, FILTER_VALIDATE_IP, $flag));
}

/**
 * 生成url
 * @param string $path url path部分
 * @return string
 */
function generateUrl(string $path = ''): string
{
    return getFixedUrl($path);
}

/**
 * 获取中文文本分词，基于ElasticSearch
 * 请先安装中文分词器，
 * git网址：https://github.com/medcl/elasticsearch-analysis-ik
 * 安装方法，ElasticSearch bin目录下执行，elasticsearch-plugin install https://github.com/medcl/elasticsearch-analysis-ik/releases/download/v6.3.2/elasticsearch-analysis-ik-6.3.2.zip
 * @param string $text 待分词文本
 * @param int $minLen
 * @param string $type 需要的分词类别，ENGLISH、CN_WORD
 * @param string $analyzer 分词器，ik_smart、ik_max_word、icu_tokenizer
 * @param int $num 获取多少个分词个数
 * @return array
 */
function getAnalyzingText(string $text, int $minLen = 1, string $type = '', string $analyzer = 'ik_smart', int $num = 30): array
{
    $text = myTrim(strip_tags($text));
    $result = ElasticSearch::getInstance()->getAnalyze($text, $analyzer);
    $data = [];
    if (!empty($result)) {
        $tokens = $result['tokens'];
        foreach ($tokens as $token) {
            if (mb_strlen($token['token'], 'utf-8') < $minLen) {
                continue;
            }
            if (empty($type)) {
                $data[] = $token['token'];
            } else {
                if (strtolower($type) === strtolower($token['type'])) {
                    $data[] = $token['token'];
                }
            }
        }
        $data = array_unique($data);
        $data = array_slice($data, 0, $num);
    }
    return $data;
}

/**
 * 格式化模块名称，转为小写
 * @param string $module 模块名称
 * @return string
 */
function formatModule(string $module): string
{
    return strtolower($module);
}

/**
 * 格式化控制器名称，转为每个单词首字母大写（将_分隔的小写控制器）
 * @param string $controller 控制器名称
 * @return string
 */
function formatController(string $controller): string
{
    return str_ireplace('_', '', ucwords($controller, '_'));
}

/**
 * 格式化方法
 * @param string $action 转为每个单词首字母大写，第一个字母转为小写（将_分隔的小写方法）
 * @return string
 */
function formatAction(string $action): string
{
    return lcfirst(str_ireplace('_', '', ucwords($action, '_')));
}

/**
 * 输出json数据
 * @param int $status
 * @param array|string $data
 * @param string $msg
 * @param int $type
 */
function echoJson(int $status = 1, array|string $data = [], string $msg = '', int $type = JSON_UNESCAPED_UNICODE): void
{
    json([
        'data' => $data,
        'status' => $status,
        'msg' => $msg
    ], $type);
}

/**
 * 输出json数据
 * @param array|string $data
 * @param int $type
 */
function json(string|array $data, int $type = JSON_UNESCAPED_UNICODE): void
{
    header('content-type:text/json;charset=utf-8');
    if (is_array($data)) {
        echo json_encode($data, $type);
    } else {
        echo $data;
    }
    exit();
}

/**
 * 输出跨域json数据
 * @param int $status
 * @param array $data
 * @param string $msg
 * @param int $type
 */
function echoCorsJson(int $status = 1, array $data = [], string $msg = '', int $type = JSON_UNESCAPED_UNICODE): void
{
    $access_control_allow_origin = config('cors.access_control_allow_origin');
    if (!empty($access_control_allow_origin)) {
        header('Access-Control-Allow-Origin:' . $access_control_allow_origin);
    }
    $access_control_allow_credentials = config('cors.access_control_allow_credentials');
    if (!empty($access_control_allow_credentials)) {
        header('Access-Control-Allow-Credentials:' . $access_control_allow_credentials);
    }
    $access_control_allow_methods = config('cors.access_control_allow_methods');
    if (!empty($access_control_allow_methods)) {
        header('Access-Control-Allow-Methods:' . $access_control_allow_methods);
    }
    $access_control_allow_headers = config('cors.access_control_allow_headers');
    if (!empty($access_control_allow_headers)) {
        header('Access-Control-Allow-Headers:' . $access_control_allow_headers);
    }
    $access_control_expose_headers = config('cors.access_control_expose_headers');
    if (!empty($access_control_expose_headers)) {
        header('Access-Control-Expose-Headers:' . $access_control_expose_headers);
    }
    $access_control_max_age = config('cors.access_control_max_age');
    if ($access_control_max_age > 0) {
        header('Access-Control-Max-Age:' . $access_control_max_age);
    }
    json([
        'data' => $data,
        'status' => $status,
        'msg' => $msg
    ], $type);
}

/**
 * 异常处理
 * @param Throwable $exception
 */
function exceptionHandler(Throwable $exception): void
{
    $sep = '<br>';
    if (isCliMode()) {
        $sep = PHP_EOL;
    }
    echo '错误文件: ', $exception->getFile(), $sep;
    echo '错误行号: ', $exception->getLine(), $sep;
    echo '错误代码: ', $exception->getCode(), $sep;
    echo '错误信息: ', $exception->getMessage(), $sep;
    echo '错误路由信息: ', $exception->getTraceAsString(), $sep;
}

/**
 * 错误处理
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param int $errline
 */
function errorHandler(int $errno, string $errstr, string $errfile, int $errline): void
{
    $data = ['错误文件: ' . $errfile, '错误行号: ' . $errline, '错误信息: ' . $errstr, '错误级别: ' . $errno];
    if (isCliMode()) {
        echo implode(PHP_EOL, $data);
    } else {
        echo implode('<br>', $data);
    }
    exit();
}

/**
 * 将大写分割为_连接的小写字符串，如MyName -> my_name
 * @param string $name 待转换的字符串
 * @param string $splitStr 分割字符串
 * @return string
 */
function toDivideName(string $name, string $splitStr = ''): string
{
    if (empty($splitStr)) {
        $name = preg_replace('/([A-Z])/', '_$1', $name);
        $name = strtolower(trim($name, '_'));
    } else {
        $splitRegStr = preg_quote($splitStr);
        if (preg_match('#[' . $splitRegStr . ']#', $name)) {
            $tmp = preg_split('#[' . $splitRegStr . ']#', $name);
            $name = '';
            foreach ($tmp as $v) {
                if (empty($name)) {
                    $name = toDivideName($v);
                } else {
                    $name .= $splitStr . toDivideName($v);
                }
            }
        } else {
            $name = toDivideName($name);
        }
    }
    return $name;
}

/**
 * 获取模板配置变量,区分大小写
 * @param string $configFile 模板配置文件名
 * @param string $name 字段名
 * @param string $section 字段所在节点（域）
 * @return boolean|string
 */
function getTempletConfig(string $configFile, string $name, string $section = ''): string|bool
{
    // 判断模板配置文件是否存在
    $file = APPLICATION_DIR . '/' . MODULE . '/config/' . $configFile;
    if (!file_exists($file)) {
        return false;
    }
    $handle = fopen($file, 'rb');
    if (!$handle) {
        return false;
    }
    // 当前模板所在节点
    $isSection = '';
    // 当前值
    $mData = '';
    // 多行查找标记
    $mFlag = false;
    // 一行一行的读取
    while (($str = fgets($handle)) !== false) {
        // 去掉当前行内容的空格
        $str = trim($str);
        if (empty($str)) {
            continue;
        }
        // 截取当前行的第一个字符
        switch ($str[0]) {
            case '#':
                // 注释
                continue 2;
            case '[':
                // 节点
                if (preg_match('/\[(.*)\]/U', $str, $mat)) {
                    // 当前节点
                    $isSection = trim($mat[1]);
                    $isSection = trim($isSection, '.');
                }
                break;
        }
        // 查找的节点与当前节点不一样
        if ($isSection !== $section) {
            continue;
        }
        // 不是多行
        if (!$mFlag) {
            // 分隔当前行内容
            $arr = explode('=', $str);
            if (count($arr) !== 2) {
                continue;
            }
            // 字段是否相等
            $key = trim($arr[0]);
            if ($key !== $name) {
                continue;
            }
            // 当前值
            $value = trim($arr[1]);
        } else {
            // 多行，当前值就等于当前行内容
            $value = $str;
        }
        if (str_starts_with($value, '"""')) {
            // 多行
            $mData = substr($value, 3) . PHP_EOL;
            $mFlag = true;
        } else if (substr($value, -3) === '"""') {
            // 多行结束
            $mData .= substr($value, 0, -3);
            $mFlag = false;
            break;
        } else {
            if (!$mFlag) {
                // 不是多行，直接返回当前值
                $mData = $value;
                break;
            }
            // 是多行，就拼接数据
            $mData .= $value . PHP_EOL;
        }
    }
    fclose($handle);
    return trim($mData, PHP_EOL);
}

/**
 * 效验邮箱是否正确
 * @param string $email 电子邮箱
 * @return boolean
 */
function isEmail(string $email): bool
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    return true;
}

/**
 * 验证手机号
 * @param string $phone 手机号
 * @return boolean
 */
function isPhone(string $phone): bool
{
    if (!preg_match('/^1[\d]{10}$/U', $phone)) {
        return false;
    }
    return true;
}

/**
 * 验证url
 * @param string $url 网址
 * @return boolean
 */
function isUrl(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    return true;
}

/**
 * ip是否有效
 * @param string $ip
 * @return bool
 */
function isIp(string $ip): bool
{
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return false;
    }
    return true;
}

/**
 * 判断是否是主域
 * @param string $domain 主域
 * @return boolean
 */
function isDomain(string $domain): bool
{
    if (preg_match('/com.cn/i', $domain)) {
        if (!preg_match('/^[\w]+\.com\.cn$/Ui', $domain)) {
            return false;
        }
    } else {
        if (!preg_match('/^[\w]+\.[\w]+$/Ui', $domain)) {
            return false;
        }
    }
    return true;
}

/**
 * 判断当前是不是手机端
 * @return boolean
 */
function isMobile(): bool
{
    if (preg_match('/mobile|android|iphone/i', getServerValue('HTTP_USER_AGENT'))) {
        return true;
    }
    return false;
}

/**
 * 获取修正后的url
 * @param string $url
 * @return string
 */
function getFixedUrl(string $url): string
{
    if (empty($url)) {
        $url = getAbsoluteUrl();
    } else if (false === stripos($url, 'http')) {
        $url = getAbsoluteUrl() . '/' . trim($url, '/');
    }
    return $url;
}

/**
 * 获取session值
 * @param string $name
 * @return mixed
 */
function getSession(string $name): mixed
{
    return Session::getInstance()->get($name, false);
}

/**
 * 设置session
 * @param string $name
 * @param mixed $value
 */
function setSession(string $name, mixed $value): void
{
    Session::getInstance()->set($name, $value);
}

/**
 * 删除session
 * @param string $name
 */
function deleteSession(string $name): void
{
    Session::getInstance()->delete($name);
}

/**
 * 开启session
 */
function startSession(): void
{
    Session::getInstance()->startSession();
}

/**
 * 删除所有session
 */
function clearAllSession(): void
{
    Session::getInstance()->clear();
}

/**
 * 获取cookie值
 * @param string $name
 * @return string
 */
function getLocalCookie(string $name): string
{
    return Cookie::getInstance()->get($name, false);
}

/**
 * 设置cookie
 * @param string $name key
 * @param string $value 值
 * @return bool
 */
function setLocalCookie(string $name, string $value): bool
{
    return Cookie::getInstance()->set($name, $value);
}

/**
 * 删除cookie
 * @param string $name key
 * @return bool
 */
function deleteCookie(string $name): bool
{
    return Cookie::getInstance()->delete($name);
}

/**
 * 清除所有cookie
 */
function clearAllCookie(): void
{
    Cookie::getInstance()->clear();
}

/**
 * 格式化时间
 * @param int $time 时间，单位秒
 * @return string
 */
function formatTime(int $time): string
{
    $cha = time() - $time;
    if ($cha === 0) {
        return '刚刚';
    }
    $unit = '前';
    if ($cha < 0) {
        $cha *= -1;
        $unit = '后';
    }
    if ($cha < 60) {
        return $cha . '秒' . $unit;
    } else if ($cha < 3600) {
        return (int)($cha / 60) . '分钟' . $unit;
    } else if ($cha < 86400) {
        return (int)($cha / 3600) . '小时' . $unit;
    } else {
        return (int)($cha / 86400) . '天' . $unit;
    }
}

/**
 * 将xml结构转为数组
 * @param string $xml
 * @return array
 */
function xmlToArray(string $xml): array
{
    try {
        $xml = preg_replace('/<!\[CDATA\[(.*)\]\]>/isU', '$1', $xml);
        return json_decode(json_encode(simplexml_load_string($xml)), true);
    } catch (\Exception $e) {
        return [];
    }
}

/**
 * 将数组转为标准的xml结构
 * @param array $data
 * @return string|bool
 * @throws Exception
 */
function arrayToXml(array $data): string|bool
{
    $xml = arrayToXmlStr($data);
    if (empty($xml)) {
        return false;
    }
    $xmlObj = new SimpleXMLElement($xml);
    return $xmlObj->asXML();
}

/**
 * 将数组转为xml字符串
 * @param array $data
 * @return string
 */
function arrayToXmlStr(array $data): string
{
    if (!is_array($data)) {
        return '';
    }
    $xml = '';
    foreach ($data as $k => $v) {
        if (is_array($v)) {
            $xml .= '<' . $k . '>' . arrayToXmlStr($v) . '</' . $k . '>';
        } else {
            $xml .= '<' . $k . '><![CDATA[' . $v . ']]></' . $k . '>';
        }
    }
    return $xml;
}

/**
 * 判断字符串是否为中文字符
 * @param string $str
 * @return bool
 */
function isZh(string $str): bool
{
    if (!preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $str)) {
        return false;
    }
    return true;
}

/**
 * 获取框架版本号
 * @return string
 */
function getMySmartyVersion(): string
{
    return MYSMARTY_VERSION;
}

/**
 * 是否是代理ip
 * @return bool
 */
function isProxyIp(): bool
{
    if (!empty(getServerValue('HTTP_VIA'))) {
        return true;
    }
    return false;
}

/**
 * 通过给定的文件创建目录
 * @param string $file 文件路径
 * @return bool
 */
function createDirByFile(string $file): bool
{
    if (file_exists($file)) {
        return true;
    }
    $dirname = pathinfo($file, PATHINFO_DIRNAME);
    return createDir($dirname);
}

/**
 * 创建文件夹
 * @param string $dir
 * @return bool
 */
function createDir(string $dir): bool
{
    if (!is_dir($dir)) {
        return mkdir($dir, 0777, true);
    }
    return true;
}

/**
 * 输出html响应头
 */
function echoHtmlHeader(): void
{
    header('content-type:text/html;charset=utf-8');
}

/**
 * 截取字符串
 * @param string $str 原字符
 * @param int $len 截取长度
 * @return string
 */
function len(string $str, int $len = 30): string
{
    $str = strip_tags($str);
    $str = preg_replace('/^[\s　]+/', '', $str);
    if (mb_strlen($str, 'utf-8') < $len) {
        return $str;
    }
    return mb_substr($str, 0, $len) . '...';
}

/**
 * 格式化时间
 * @param string|int $time 时间戳或时间格式
 * @param string $format Y-m-d H:i:s
 * @return string
 */
function formatToTime(string|int $time, string $format = 'Y-m-d H:i:s'): string
{
    if (!is_int($time)) {
        $time = strtotime($time);
    }
    return date($format, $time);
}

/**
 * 输出表情
 * @param string $str 表情文字
 */
function emoji(string $str): void
{
    Emoji::echoByName($str);
}

/**
 * 获取url的panthinfo，不包含请求参数
 * @return string
 */
function getPath(): string
{
    if (!defined('URI_PATH')) {
        $pathinfo = urldecode(parse_url(getServerValue('REQUEST_URI'), PHP_URL_PATH));
        define('URI_PATH', trim($pathinfo, '/'));
    }
    return URI_PATH;
}

/**
 * 获取当前页面唯一的缓存key
 * @return string
 */
function getCacheKey(): string
{
    return md5(MODULE . getServerValue('REQUEST_URI'));
}

/**
 * 判断当前请求是否为网页html请求
 * @return bool
 */
function isRequestHtml(): bool
{
    return str_contains(getServerValue('HTTP_ACCEPT'), 'text/html');
}

/**
 * 判断当前请求是否为json请求
 * @return bool
 */
function isRequestJson(): bool
{
    return str_contains(getServerValue('HTTP_ACCEPT'), 'json');
}

/**
 * 生成路由文件
 */
function generateRoute(): void
{
    if (!file_exists(ROUTE_FILE) || \config('app.debug')) {
        // 重新生成
        $controllerDir = APPLICATION_DIR . '/' . MODULE . '/controller';
        $classData = getNamespaceClass($controllerDir);
        $data = [];
        $sortLevelData = [];
        $sortLenData = [];
        try {
            foreach ($classData as $class) {
                // 获取类上的路由设置
                $obj = new ReflectionClass($class);
                $attributes = $obj->getAttributes(Route::class);
                $topRoute = '';
                $topPattern = [];
                $topMiddleware = [];
                $topLevel = Route::MIDDLE;
                $topCaching = true;
                if (1 === count($attributes)) {
                    // 定义了路由
                    $topRouteObj = $attributes[0]->newInstance();
                    $topRoute = $topRouteObj->getUrl();
                    $topPattern = $topRouteObj->getPattern();
                    $topMiddleware = $topRouteObj->getMiddleware();
                    $topLevel = $topRouteObj->getLevel();
                    $topCaching = $topRouteObj->isCaching();
                }
                if ($topCaching) {
                    $defaultProperties = $obj->getDefaultProperties();
                    if (!isset($defaultProperties['myCache']) || false === $defaultProperties['myCache']) {
                        $topCaching = false;
                    }
                }
                $controllerPath = str_ireplace('application\\' . MODULE . '\controller\\', '', $class);
                // 获取方法上的路由设置
                $methods = $obj->getMethods(ReflectionMethod::IS_PUBLIC);
                foreach ($methods as $method) {
                    $methodRoute = '';
                    $methodPattern = [];
                    $methodMiddleware = [];
                    $methodLevel = $topLevel;
                    // 方法参数列表
                    $methodParams = [];
                    $methodName = $method->getName();
                    // 去掉构造方法
                    if ('__construct' === $methodName) {
                        continue;
                    }
                    $methodAttributes = $method->getAttributes(Route::class);
                    $methodCaching = $topCaching;
                    if (1 === count($methodAttributes)) {
                        // 方法使用了路由
                        $methodRouteObj = $methodAttributes[0]->newInstance();
                        $methodRoute = $methodRouteObj->getUrl();
                        $methodPattern = $methodRouteObj->getPattern();
                        $methodMiddleware = $methodRouteObj->getMiddleware();
                        $methodLevel = $methodRouteObj->getLevel();
                        $methodCaching = $methodRouteObj->isCaching();
                    }
                    if (empty($methodRoute)) {
                        // 转为普通访问方式
                        $methodRoute = toDivideName($methodName);
                    }
                    if (!str_starts_with($methodRoute, '/')) {
                        if (empty($topRoute)) {
                            // 转为普通访问方式
                            $tmp = str_ireplace('\\', '/', $controllerPath);
                            $tmp = toDivideName($tmp, '/');
                            $topRoute = MODULE . '/' . $tmp;
                        }
                        $methodRoute = trim($topRoute, '/') . '/' . $methodRoute;
                    }
                    $methodParameters = $method->getParameters();
                    foreach ($methodParameters as $methodParameter) {
                        $methodParams[] = $methodParameter->getName();
                    }
                    // 处理路由文件
                    $methodPattern = array_merge($topPattern, $methodPattern);
                    $methodMiddleware = array_merge($topMiddleware, $methodMiddleware);
                    $uri = trim($methodRoute, '/');
                    $uri = preg_quote($uri);
                    // 替换正则表达式
                    $reg = '/\\\{([a-z0-9_]+)\\\}/iU';
                    $uri = preg_replace_callback($reg, function ($match) use ($methodPattern) {
                        return '(?P<' . $match[1] . '>' . ($methodPattern[$match[1]] ?? '[a-z0-9_]+') . ')';
                    }, $uri);
                    // 处理中间件，方法名区分大小写
                    $dealMethodMiddleware = [];
                    foreach ($methodMiddleware as $midd => $middleware) {
                        if (is_array($middleware)) {
                            // 排除
                            $middExcept = $middleware['except'] ?? [];
                            if (!empty($middExcept) && !in_array($methodName, $middExcept)) {
                                $dealMethodMiddleware[] = $midd;
                            }
                            // 仅包括
                            $middOnly = $middleware['only'] ?? [];
                            if (!empty($middOnly) && in_array($methodName, $middOnly)) {
                                $dealMethodMiddleware[] = $midd;
                            }
                        } else if (is_string($middleware)) {
                            $dealMethodMiddleware[] = $middleware;
                        }
                    }
                    $dealMethodMiddleware = array_unique($dealMethodMiddleware);
                    // 排序
                    $sortLevelData[] = $methodLevel;
                    $sortLenData[] = mb_strlen($uri, 'utf-8');
                    // 处理缓存
                    if (false === $topCaching && true === $methodCaching) {
                        $methodCaching = false;
                    }
                    $data[] = [
                        'class' => $class,
                        'methodName' => $methodName,
                        'methodParams' => $methodParams,
                        'methodLevel' => $methodLevel,
                        'uri' => $uri,
                        'methodMiddleware' => $dealMethodMiddleware,
                        'methodPattern' => $methodPattern,
                        'controller' => $controllerPath,
                        'caching' => $methodCaching
                    ];
                }
            }
            array_multisort($sortLevelData, SORT_DESC, $sortLenData, SORT_DESC, $data);
            $home = [];
            $homeClass = 'application\\' . MODULE . '\controller\\' . CONTROLLER;
            foreach ($data as $k => $v) {
                // 判断是否为首页
                if ($homeClass === $v['class'] && ACTION === $v['methodName']) {
                    $home = $v;
                    unset($data[$k]);
                    break;
                }
            }
            if (empty($home)) {
                error('未定义主页路由');
            }
            $data['home'] = $home;
        } catch (ReflectionException $e) {
            error('路由文件生成失败');
        }
        file_put_contents(ROUTE_FILE, json_encode($data));
        define('ROUTE', $data);
    } else {
        // 不需要生成
        define('ROUTE', json_decode(file_get_contents(ROUTE_FILE), true));
    }
}

/**
 * 获取指定文件夹内class的命名空间地址
 * @param string $dir
 * @return array
 */
function getNamespaceClass(string $dir): array
{
    static $classData = [];
    static $prefix = ROOT_DIR . '/';
    if (file_exists($dir)) {
        //读取$dir目录下的配置
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                if (str_ends_with($file, '.php')) {
                    $classData[] = str_ireplace('/', '\\', str_ireplace($prefix, '', $dir . '/' . str_ireplace('.php', '', $file)));
                } else {
                    return getNamespaceClass($dir . '/' . $file);
                }
            }
        }
    }
    return $classData;
}

/**
 * 获取指定文件夹内控制器文件是否有修改
 * @param string $dir
 * @return bool
 */
function checkeFileUpdate(string $dir): bool
{
    $checkFileTime = filemtime(ROUTE_FILE);
    if (file_exists($dir)) {
        //读取$dir目录下的配置
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                if (str_ends_with($file, '.php')) {
                    if (filemtime($dir . '/' . $file) > $checkFileTime) {
                        return true;
                    }
                } else {
                    return checkeFileUpdate($dir . '/' . $file);
                }
            }
        }
    }
    return false;
}

/**
 * 获取当前访问路径，不包含GET请求参数
 * @return string
 */
function getHref(): string
{
    $uri = getPath();
    if (!empty($uri)) {
        return getAbsoluteUrl() . '/' . $uri;
    }
    return getAbsoluteUrl();
}

/**
 * 判断指定的文件是否为真实的图片文件
 * @param string $filePath 文件路径
 * @return bool
 */
function isImage(string $filePath): bool
{
    if (file_exists($filePath)) {
        return getimagesize($filePath) !== false;
    }
    return false;
}

/**
 * 递归删除文件夹内容
 * @param string $dir 文件夹
 * @param bool $deleteDir 是否删除文件夹
 * @return bool
 */
function removeDir(string $dir, bool $deleteDir = false): bool
{
    if (!is_dir($dir)) {
        return true;
    }
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $f = $dir . '/' . $file;
        if (is_dir($f)) {
            removeDir($f, $deleteDir);
        } else {
            unlink($f);
        }
    }
    if ($deleteDir) {
        return rmdir($dir);
    }
    return true;
}

/**
 * 获取.env配置的值
 * @param string $key 配置key
 * @param mixed $defValue 默认值
 * @return mixed
 */
function env(string $key, mixed $defValue = ''): mixed
{
    return Env::get($key, $defValue);
}