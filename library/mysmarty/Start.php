<?php

namespace library\mysmarty;
/**
 * 应用启动类
 * @package library\mysmarty
 */
class Start
{
    //当前模块
    public static string $module;
    //当前控制器
    public static string $controller;
    //当前执行方法
    public static string $action;

    /**
     * 初始化引入
     */
    public static function initCommon(): void
    {
        define('MYSMARTY_VERSION', '1.0.3');
        define('APPLICATION_DIR', ROOT_DIR . '/application');
        define('EXTEND_DIR', ROOT_DIR . '/extend');
        define('PUBLIC_DIR', ROOT_DIR . '/public');
        define('STATIC_DIR', PUBLIC_DIR . '/static');
        define('UPLOAD_DIR', PUBLIC_DIR . '/upload');
        define('RUNTIME_DIR', ROOT_DIR . '/runtime');
        define('LIBRARY_DIR', ROOT_DIR . '/library');
        define('CONFIG_DIR', ROOT_DIR . '/config');
        // 自动加载
        spl_autoload_register(function ($class) {
            require_once ROOT_DIR . '/' . str_ireplace('\\', '/', $class) . '.php';
        });
        // 引入核心函数库
        require_once LIBRARY_DIR . '/function.php';
        require_once APPLICATION_DIR . '/common.php';
        // 初始化配置
        if (!config('app.debug', false)) {
            error_reporting(0);
        } else {
            set_error_handler('errorHandler');
            set_exception_handler('exceptionHandler');
        }
        date_default_timezone_set(config('app.default_timezone'));
        if (!empty(config('app.app_init')) && function_exists(config('app.app_init'))) {
            call_user_func(config('app.app_init'));
        }
        // 加载第三方库
        if (file_exists(ROOT_DIR . '/vendor/autoload.php')) {
            require_once ROOT_DIR . '/vendor/autoload.php';
        }
        // 控制台运行，不需要路由
        if (isCliMode()) {
            Console::start();
            exit();
        }
        // 移除X-Powered-By信息
        header_remove('X-Powered-By');
        // session开启
        if (config('session.status') === 1) {
            startSession();
        }
    }

    /**
     * 执行控制器方法
     */
    public static function forward(): void
    {
        self::initCommon();
        $uri = getPath();
        $mat = [];
        $route = App::getInstance()->getAllRoute();
        if (!empty($uri)) {
            foreach ($route as $v) {
                // 匹配当前规则，获取()内的内容
                if ($uri === $v['uri'] || preg_match('#^' . $v['uri'] . '$#iU', $uri, $mat)) {
                    self::runRoute($v, $mat);
                    break;
                }
            }
        } else {
            self::runRoute($route['home'], $mat);
        }
        error(lang('页面找不到'));
    }

    /**
     * 调用模块方法
     * @param array $params 请求参数
     */
    public static function go(array $params = []): void
    {
        $controllerNamespace = 'application\\' . Start::$module . '\controller\\' . Start::$controller;
        $obj = new $controllerNamespace();
        call_user_func_array(array(
            $obj,
            Start::$action
        ), array_values($params));
        // 程序到此结束运行
        exit();
    }


    /**
     * 验证中间件
     * @param string $middleware 中间件的地址
     * @param array $params 路由参数
     */
    private static function checkMiddleware(string $middleware, array $params = []): void
    {
        // 调用中间件方法执行
        $middlewareObj = new $middleware();
        if (false === $middlewareObj->handle($params)) {
            // 失败后执行
            $middlewareObj->fail($params);
            exit();
        }
    }

    /**
     * 执行路由规则
     * @param array $route 匹配到的路由
     * @param array $mat 匹配到的结果
     */
    public static function runRoute(array $route, array $mat): void
    {
        // 方法执行需要的参数
        $params = [];
        foreach ($route['methodParams'] as $param) {
            if (isset($mat[$param])) {
                $params[$param] = $mat[$param];
            }
        }
        // 执行中间件
        foreach ($route['methodMiddleware'] as $midd) {
            self::checkMiddleware($midd, $params);
        }
        $controller = $route['controller'];
        $action = $route['methodName'];
        self::$module = MODULE;
        self::$controller = $controller;
        self::$action = $action;
        // 执行缓存
        if ($route['caching'] && 1 === config('mysmarty.cache', 0)) {
            Template::getInstance()->showCache();
        }
        self::go(params: $params);
    }
}