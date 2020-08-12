<?php

/**
 * 屬性存取操作抽像類別
 * 
 * @version 1.3.6 2013/06/11 01:12
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Sewii\System\Accessors;

use ReflectionObject;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionException;
use Sewii\Exception;
use Sewii\Text\Regex;

abstract class AbstractAccessors
{
    /**
     * 取值子
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $class = $this;
        if (is_array($name) && count($name) >= 2) {
            $class = $name[0];
            $name  = $name[1];
        }
        return self::getter($class, $name);
    }
    
    /**
     * 設定子
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value = null) 
    {
        $class = $this;
        if (is_array($name) && count($name) >= 3) {
            $class = $name[0];
            $name  = $name[1];
            $value = $name[2];
        }
        self::setter($class, $name, $value);
    }
    
    /**
     * 取值器
     *
     * @param object $class
     * @param string $name
     * @return mixed
     */
    private static function getter($class, $name)
    {
        $getter = 'get' . ucfirst($name);
        if (self::isPublicMethod($class, $getter)) {
            return $class->$getter();
        }

        if (!self::isPropertyExists($class, $name)) {
            throw new Exception\RuntimeException(
                self::getUndefinedExceptionMessage($class, $name)
            );
        }
        else if (!self::isPublicProperty($class, $name)) {
            throw new Exception\RuntimeException(
                self::getAccessExceptionMessage($class, $name)
            );
        }
    }
    
    /**
     * 設定器
     *
     * @param object $class
     * @param string $name
     * @param mixed $value
     * @return void
     */
    private static function setter($class, $name, $value)
    {
        $setter = 'set' . ucfirst($name);
        if (self::isPublicMethod($class, $setter)) {
            $class->$setter($value);
            return;
        }

        if (!self::isPropertyExists($class, $name)) {
            $class->$name = $value;
        }
        else if (!self::isPublicProperty($name, $class)) {
            throw new Exception\RuntimeException(
                self::getAccessExceptionMessage($class, $name)
            );
        }
    }
    
    /**
     * 傳回未定義例外訊息
     *
     * @param object $class
     * @param string $name
     * @return string
     */
    private static function getUndefinedExceptionMessage($class, $name)
    {
        return sprintf('Undefined property: %s::$%s', get_class($class), $name);
    }
    
    /**
     * 傳回禁止存取例外訊息
     *
     * @param object $class
     * @param string $name
     * @return string
     */
    private static function getAccessExceptionMessage($class, $name)
    {
        return sprintf('Cannot access %s property %s::$%s', 
            self::getPropertyModifier($class, $name), 
            get_class($class), $name 
        );
    }
    
    /**
     * 傳回方法是否存在
     *
     * @param object $class
     * @param string $name
     * @return boolean
     */
    private static function isMethodExists($class, $name)
    {
        return method_exists($class, $name);
    }
    
    /**
     * 傳回屬性是否存在
     *
     * @param object $class
     * @param string $name
     * @return boolean
     */
    private static function isPropertyExists($class, $name)
    {
        return property_exists($class, $name);
    }
    
    /**
     * 傳回方法是否公開
     *
     * @param object $class
     * @param string $name
     * @return boolean
     */
    private static function isPublicMethod($class, $name)
    {
        if (self::isMethodExists($class, $name)) {
            return self::getReflectionObject($class)->getMethod($name)->isPublic();
        }
        return false;
    }
    
    /**
     * 傳回屬性是否公開
     *
     * @param object $class
     * @param string $name
     * @return boolean
     */
    private static function isPublicProperty($class, $name)
    {
        if (self::isPropertyExists($class, $name)) {
            return self::getReflectionObject($class)->getProperty($name)->isPublic();
        }
        return false;
    }
    
    /**
     * 傳回方法修飾詞
     *
     * @param object $class
     * @param string $name
     * @return string
     */
    private static function getMethodModifier($class, $name)
    {
        if (self::isMethodExists($class, $name)) {
            $modifiers = self::getReflectionObject($class)->getMethod($name)->getModifiers();
            if ($modifiers & ReflectionMethod::IS_PUBLIC)    return 'public';
            if ($modifiers & ReflectionMethod::IS_PROTECTED) return 'protected';
            if ($modifiers & ReflectionMethod::IS_PRIVATE)   return 'private';
        }
        return null;
    }
    
    /**
     * 傳回屬性修飾詞
     *
     * @param object $class
     * @param string $name
     * @return string
     */
    private static function getPropertyModifier($class, $name)
    {
        if (self::isPropertyExists($class, $name)) {
            $modifiers = self::getReflectionObject($class)->getProperty($name)->getModifiers();
            if ($modifiers & ReflectionProperty::IS_PUBLIC)    return 'public';
            if ($modifiers & ReflectionProperty::IS_PROTECTED) return 'protected';
            if ($modifiers & ReflectionProperty::IS_PRIVATE)   return 'private';
        }
        return null;
    }
    
    /**
     * 傳回鏡射物件
     *
     * @param object $class
     * @return boolean
     */
    private static function getReflectionObject($class)
    {
        static $reflectionObjects;
        $name = get_class($class);
        if (!isset($reflectionObjects[$name])) {
            $reflectionObjects[$name] = new ReflectionObject($class);
        }
        return $reflectionObjects[$name];
    }
}

?>