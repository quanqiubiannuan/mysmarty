<?php

namespace library\mysmarty;
/**
 * 缓存类
 */
class Cache
{

    /**
     * 添加缓存
     * @param string $name 键
     * @param mixed $value 值
     * @param int $expire 缓存时间，单位秒
     * @return bool
     */
    public static function set(string $name, mixed $value, int $expire = 3600): bool
    {
        $value = serialize($value);
        $cachingType = config('mysmarty.caching_type');
        switch ($cachingType) {
            case 'redis':
                Redis::getInstance()->select(config('mysmarty.caching_type_params.redis.db', 0));
                return Redis::getInstance()->set($name, $value, $expire);
            default:
                $cacheDir = ROOT_DIR . '/runtime/cache';
                createDir($cacheDir);
                return file_put_contents($cacheDir . '/' . md5($name), (time() + $expire) . '|' . $value);
        }
    }

    /**
     * 获取缓存
     * @param string $name 键
     * @param mixed $defValue 默认值
     * @return string
     */
    public static function get(string $name, mixed $defValue = ''): string
    {
        $cachingType = config('mysmarty.caching_type');
        switch ($cachingType) {
            case 'redis':
                Redis::getInstance()->select(config('mysmarty.caching_type_params.redis.db', 0));
                $value = Redis::getInstance()->get($name);
                if (!empty($value)) {
                    return unserialize($value);
                }
                return $defValue;
            default:
                $file = ROOT_DIR . '/runtime/cache/' . md5($name);
                if (!file_exists($file)) {
                    return $defValue;
                }
                $data = file_get_contents($file);
                $expireTime = substr($data, 0, strpos($data, '|'));
                if ($expireTime > time()) {
                    return unserialize(substr($data, strpos($data, '|') + 1));
                }
                return $defValue;
        }
    }

    /**
     * 删除缓存
     * @param string $name 键
     * @return bool
     */
    public static function rm(string $name): bool
    {
        $cachingType = config('mysmarty.caching_type');
        switch ($cachingType) {
            case 'redis':
                Redis::getInstance()->select(config('mysmarty.caching_type_params.redis.db', 0));
                return Redis::getInstance()->del($name);
            default:
                $file = ROOT_DIR . '/runtime/cache/' . md5($name);
                if (!file_exists($file)) {
                    return false;
                }
                return unlink($file);
        }
    }
}