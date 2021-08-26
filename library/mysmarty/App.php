<?php

namespace library\mysmarty;

class App
{
    private static ?self $obj = null;
    private string $configFile = RUNTIME_DIR . '/cache/' . MODULE . '/mysmarty_config.php';
    private array $configData = [];
    // env文件配置
    private string $envFile = ROOT_DIR . '/.env';
    private array $envData = [];
    // route文件配置
    private string $routeFile = RUNTIME_DIR . '/cache/' . MODULE . '/mysmarty_route.php';

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
        if ($debug && !$this->initConfig()) {
            exit('配置文件初始化失败');
        }
    }

    /**
     * 获取配置的值
     * @param string $name 配置名称
     * @param mixed|string $defValue 默认值
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
     * @param mixed|string $defValue 默认值
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
    private function initConfig(): bool
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
}