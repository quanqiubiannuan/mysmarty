<?php

namespace library\mysmarty;

/**
 * 单一实例
 */
class Container
{
    // 保存单一实例变量的数组
    private static array $_instance = [];
    // 除了配置的变量，其它的都重新初始化
    protected array $flushExceptVar = [];
    // 重新初始化配置的变量
    protected array $flushOnlyVar = [];

    /**
     * 获取单一实例
     * @return static
     */
    final public static function getInstance(): static
    {
        $class = get_called_class();
        if (false === array_key_exists($class, static::$_instance)) {
            static::$_instance[$class] = new static;
            static::$_instance[$class]->_initialize();
        }
        return static::$_instance[$class];
    }

    /**
     * 实例化后，调用初始化
     */
    protected function _initialize()
    {
    }

    /**
     * 重新初始化变量的值
     */
    protected function _flush()
    {
    }
}