<?php

namespace library\mysmarty;

use Exception;
use ReflectionClass;

/**
 * 单一实例
 */
class Container
{
    // 保存单一实例变量的数组
    private static array $_instance = [];
    // 除了配置的属性，其它的属性都重新初始化
    protected array $flushExceptVar = [];
    // 重新初始化配置的属性
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
    public function _initialize()
    {
    }

    /**
     * 重新初始化属性的值，静态属性无法重置
     */
    public function _flush()
    {
        try {
            $class = get_called_class();
            $obj = static::$_instance[$class] ?? new $class;
            $reflectionObj = new ReflectionClass($obj);
            foreach ($reflectionObj->getProperties() as $property) {
                if ($property->isStatic()) {
                    continue;
                }
                $propertyName = $property->getName();
                if (in_array($propertyName, ['flushExceptVar', 'flushOnlyVar'])) {
                    continue;
                }
                if (in_array($propertyName, $this->flushExceptVar)) {
                    continue;
                } else {
                    if (!empty($this->flushOnlyVar)) {
                        if (!in_array($propertyName, $this->flushOnlyVar)) {
                            continue;
                        }
                    }
                }
                $property->setAccessible(true);
                $property->setValue($obj, $property->getDefaultValue());
            }
        } catch (Exception $e) {
            error('重新初始化属性失败');
        }
    }
}