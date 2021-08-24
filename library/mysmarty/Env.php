<?php

namespace library\mysmarty;

/**
 * 环境变量
 */
class Env
{
    public static array $data = [];

    /**
     * 获取env的值
     * @param string $key
     * @param mixed $defValue
     * @return mixed
     */
    public static function get(string $key, mixed $defValue = ''): mixed
    {
        return self::$data[$key] ?? $defValue;
    }

    /**
     * 初始化env数据
     */
    public static function initEnv()
    {
        if (file_exists(ROOT_DIR . '/.env') && empty(self::$data)) {
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
                self::$data = $data;
            }
        }
    }
}