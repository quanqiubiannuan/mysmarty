<?php

namespace library\mysmarty;

/**
 * 配置类
 */
class Config
{
    // 环境变量前缀值
    private static string $prefix = 'mysmarty_config_';

    /**
     * @param string $name 配置名称
     * @param mixed $defValue 默认值
     * @return mixed
     */
    public static function getConfig(string $name, mixed $defValue = ''): mixed
    {
        $envKey = self::getEnvKey($name);
        $value = getenv($envKey, true);
        if (false === $value) {
            self::initConfig($name);
            $value = getenv($envKey, true);
            if (false === $value) {
                return $defValue;
            }
        }
        return unserialize($value);
    }

    /**
     * 添加一个设置
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public static function setConfig(string $name, mixed $value): bool
    {
        $envKey = self::getEnvKey($name);
        return putenv($envKey . '=' . serialize($value));
    }

    /**
     * 获取配置格式化后的key
     * @param string $name
     * @return string
     */
    private static function getEnvKey(string $name): string
    {
        $envKey = self::$prefix . $name;
        return str_ireplace('.', '_', $envKey);
    }

    /**
     * 初始化配置文件
     * @param string $name
     */
    private static function initConfig(string $name)
    {
        $configData = [];
        $configApplicationData = [];
        $nameArr = explode('.', $name);
        // 主配置目录中查找
        if (file_exists(CONFIG_DIR . '/' . $nameArr[0] . '.php')) {
            $configData = require_once CONFIG_DIR . '/' . $nameArr[0] . '.php';
        }
        // 网站模块文件夹查找
        $applicationDir = APPLICATION_DIR . '/' . MODULE . '/config';
        if (file_exists($applicationDir . '/' . $nameArr[0] . '.php')) {
            $configApplicationData = require_once $applicationDir . '/' . $nameArr[0] . '.php';
        }
        $data = array_replace_recursive($configData, $configApplicationData);
        if (!empty($data)) {
            self::saveConfig($data, $nameArr[0]);
        }
    }

    /**
     * 存储一个数据配置
     * @param array $data
     * @param string $prefix 前缀
     */
    private static function saveConfig(array $data, string $prefix = '')
    {
        if (!empty($prefix)) {
            self::setConfig($prefix, $data);
        }
        foreach ($data as $k => $v) {
            self::setConfig($prefix . '_' . $k, $v);
            if (is_array($v)) {
                self::saveConfig($v, $prefix . '_' . $k);
            }
        }
    }
}