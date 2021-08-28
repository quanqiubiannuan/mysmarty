<?php

namespace library\mysmarty;

use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class App
{
    private static ?self $obj = null;
    // 配置文件
    private string $configFile = RUNTIME_DIR . '/cache/' . MODULE . '/mysmarty_config.php';
    private array $configData = [];
    // env文件配置
    private string $envFile = ROOT_DIR . '/.env';
    private array $envData = [];
    // route文件配置
    private string $routeFile = RUNTIME_DIR . '/cache/' . MODULE . '/mysmarty_route.php';
    private array $routeData = [];
    // 多语言文件
    private string $langFile = RUNTIME_DIR . '/cache/' . MODULE . '/mysmarty_lang.php';
    private array $langData = [];
    // 当前语言
    private string $lang;

    /**
     * 禁止实例化
     */
    private function __construct()
    {
    }

    /**
     * 禁止克隆
     */
    private function __clone()
    {
    }

    /**
     * 获取静态操作对象
     * @return static
     */
    public static function getInstance(): static
    {
        if (is_null(static::$obj)) {
            static::$obj = new static;
            static::$obj->initData();
        }
        return static::$obj;
    }

    /**
     * 初始化数据
     */
    private function initData()
    {
        $this->initEnv();
        // 初始化配置文件
        if ($this->isFileUpdate(CONFIG_DIR . '/app.php') || $this->isFileUpdate(APPLICATION_DIR . '/' . MODULE . '/config/app.php') || $this->isFileUpdate($this->envFile)) {
            $debug = true;
        } else {
            $this->configData = unserialize(file_get_contents($this->configFile));
            if (empty($this->configData)) {
                $debug = true;
            } else {
                $debug = $this->getConfig('app.debug', true);
            }
        }
        if ($debug) {
            if (!$this->initConfig()) {
                exit(lang('配置文件初始化失败'));
            }
            $this->initRoute();
            $this->initLang();
        } else {
            $lang = config('app.default_lang');
            if (file_exists($this->routeFile)) {
                $this->routeData = unserialize(file_get_contents($this->routeFile));
            } else {
                $this->initRoute();
            }
            if (!empty($lang)) {
                if (file_exists($this->langFile)) {
                    $this->langData = unserialize(file_get_contents($this->langFile));
                } else {
                    $this->initLang();
                }
            }
        }
        $this->lang = $this->getCurrentLang();
    }

    /**
     * 获取配置的值
     * @param string $name 配置名称
     * @param mixed $defValue 默认值
     * @return mixed
     */
    public function getConfig(string $name, mixed $defValue = ''): mixed
    {
        $name = str_ireplace('.', '_', $name);
        return $this->configData[$name] ?? $defValue;
    }

    /**
     * 获取env配置的值
     * @param string $name 配置名称
     * @param mixed $defValue 默认值
     * @return mixed
     */
    public function getEnv(string $name, mixed $defValue = ''): mixed
    {
        return $this->envData[$name] ?? $defValue;
    }

    /**
     * 初始化所有数组配置
     * @return bool
     */
    public function initConfig(): bool
    {
        if (empty($this->configData)) {
            $configData = $this->getDirConfigData(CONFIG_DIR);
            $configApplicationData = $this->getDirConfigData(APPLICATION_DIR . '/' . MODULE . '/config');
            $data = array_replace_recursive($configData, $configApplicationData);
            $result = [];
            foreach ($data as $k => $v) {
                if (!is_array($v)) {
                    continue;
                }
                $result = array_merge($result, $this->formatConfig($v, $k));
            }
            $this->configData = $result;
            if (!empty($result) && createDirByFile($this->configFile)) {
                return file_put_contents($this->configFile, serialize($result)) !== false;
            }
        }
        return true;
    }

    /*
     * 初始化env数据
     */
    private function initEnv()
    {
        if (file_exists(ROOT_DIR . '/.env') && empty($this->envData)) {
            $data = parse_ini_file(ROOT_DIR . '/.env', false, INI_SCANNER_RAW);
            if (!empty($data)) {
                foreach ($data as $k => $v) {
                    $data[$k] = match (strtolower($v)) {
                        'true', '(true)' => true,
                        'false', '(false)' => false,
                        'empty', '(empty)' => '',
                        'null', '(null)' => null,
                        default => $v
                    };
                }
                $this->envData = $data;
            }
        }
    }

    /**
     * 格式化一个数据配置
     * @param array $data
     * @param string $prefix 前缀
     * @return array
     */
    private function formatConfig(array $data, string $prefix = ''): array
    {
        static $result = [];
        if (!empty($prefix)) {
            $result[$prefix] = $data;
        }
        foreach ($data as $k => $v) {
            $result[$prefix . '_' . $k] = $v;
            if (is_array($v)) {
                $this->formatConfig($v, $prefix . '_' . $k);
            }
        }
        return $result;
    }

    /**
     * 读取指定文件夹下的配置文件
     * @param string $dir
     * @return array
     */
    private function getDirConfigData(string $dir): array
    {
        $data = [];
        if (file_exists($dir)) {
            //读取$dir目录下的配置
            $files = scandir($dir);
            foreach ($files as $file) {
                if (str_ends_with($file, '.php')) {
                    $data[str_ireplace('.php', '', $file)] = require_once $dir . '/' . $file;
                }
            }
        }
        return $data;
    }

    /**
     * 判断文件是否修改过
     * @param $file
     * @return bool
     */
    private function isFileUpdate($file): bool
    {
        if (!file_exists($this->configFile)) {
            return true;
        }
        if (!file_exists($file)) {
            return false;
        }
        $currentMTime = filectime($file) ?: 0;
        $configFileMTime = filectime($this->configFile);
        if ($currentMTime > $configFileMTime) {
            return true;
        }
        return false;
    }

    /**
     * 初始化路由
     */
    public function initRoute()
    {
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
                error(lang('未定义主页路由'));
            }
            $data['home'] = $home;
        } catch (ReflectionException $e) {
            error(lang('路由文件生成失败') . '：' . $e->getMessage());
        }
        $this->routeData = $data;
        if (createDirByFile($this->routeFile)) {
            if (!file_put_contents($this->routeFile, serialize($data))) {
                error(lang('路由文件保存失败'));
            }
        } else {
            error(lang('无法创建路由文件夹'));
        }
    }

    /**
     * 返回所有的路由
     * @return array
     */
    public function getAllRoute(): array
    {
        return $this->routeData;
    }

    /**
     * 获取当前语言
     * @return string
     */
    private function getCurrentLang(): string
    {
        $defLang = config('app.default_lang');
        if (!empty($defLang)) {
            $detectLangVar = config('app.detect_lang_var');
            if (!empty($detectLangVar)) {
                $lang = getString($detectLangVar);
                if (!empty($lang)) {
                    return $lang;
                }
            }
            $cookieLangVar = config('app.cookie_lang_var');
            if (!empty($cookieLangVar)) {
                $lang = getLocalCookie($cookieLangVar);
                if (!empty($lang)) {
                    return $lang;
                }
            }
        }
        return $defLang;
    }

    /**
     * 初始化多语言
     * @return bool
     */
    public function initLang(): bool
    {
        $lanDir = APPLICATION_DIR . '/' . MODULE . '/lang';
        $langData = $this->getDirLangData($lanDir);
        $result = [];
        foreach ($langData as $k => $v) {
            if (!is_array($v)) {
                continue;
            }
            $result = array_merge($result, $this->formatLang($v, $k));
        }
        $this->langData = $result;
        if (!empty($result) && createDirByFile($this->langFile)) {
            return file_put_contents($this->langFile, serialize($result)) !== false;
        }
        return true;
    }

    /**
     * 读取指定文件夹下的多语言文件
     * @param string $dir
     * @return array
     */
    private function getDirLangData(string $dir): array
    {
        $prefix = str_ireplace(APPLICATION_DIR . '/' . MODULE . '/lang', '', $dir);
        if (!empty($prefix)) {
            $prefix = trim($prefix, '/');
            $prefix = str_ireplace('/', '_', $prefix);
            $prefix .= '_';
        }
        static $data = [];
        if (file_exists($dir)) {
            //读取$dir目录下的配置
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $curFile = $dir . '/' . $file;
                if (is_dir($curFile)) {
                    $this->getDirLangData($curFile);
                } else {
                    if (str_ends_with($file, '.php')) {
                        $data[$prefix . str_ireplace('.php', '', $file)] = require_once $curFile;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * 获取当前语言的值
     * @param string $name 语言名称
     * @return string
     */
    public function getLang(string $name): string
    {
        if (empty($this->lang)) {
            return $name;
        }
        $key1 = $this->lang . '_' . toDivideName(str_ireplace('\\', '/', Start::$controller), '/');
        $key1 = str_ireplace('/', '_', $key1) . '_' . $name;
        $key2 = $this->lang . '_' . $name;
        return $this->langData[$key1] ?? $this->langData[$key2] ?? $name;
    }

    /**
     * 格式化一个语言配置
     * @param array $data
     * @param string $prefix 前缀
     * @return array
     */
    private function formatLang(array $data, string $prefix = ''): array
    {
        static $result = [];
        if (!empty($prefix)) {
            $result[$prefix] = $data;
        }
        foreach ($data as $k => $v) {
            $result[$prefix . '_' . $k] = $v;
            if (is_array($v)) {
                $this->formatLang($v, $prefix . '_' . $k);
            }
        }
        return $result;
    }
}