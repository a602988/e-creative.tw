<?php

/**
 * Singleton 模式抽像類別
 * 
 * @version 1.4.5 2013/10/28 13:48
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft
 */

namespace Sewii\System;

use Sewii\Exception;

abstract class Singleton 
{
    /**
     * 初始化方法名稱
     *
     * @const string
     */
    const INITIALIZE_METHOD_NAME = 'initialize';

    /**
     * 儲存實體參考
     *
     * @var array 
     */
    private static $instances = array();

    /**
     * 儲存實體參考
     *
     * @var array 
     */
    private static $initialized = array();
    
    /**
     * 表示目前操作是否來自內部
     *
     * @var boolean
     */
    private static $isFromInternal = false;

    /**
     * 建構子
     * 
     * @throws Sewii\Exception\RuntimeException
     */
    final public function __construct() 
    {
        if (!self::$isFromInternal) {
            $className = get_called_class();
            throw new Exception\RuntimeException("The Singleton class $className was prohibited to construct.");
        }
    }

    /**
     * 衍生子
     * 
     * @throws Sewii\Exception\RuntimeException
     */
    public function __clone() 
    {
        if (!self::$isFromInternal) {
            $className = get_called_class();
            throw new Exception\RuntimeException("The Singleton class $className was prohibited to clone.");
        }
    }

   /**
    * 傳回實體
    * 
    * @param boolean $initialize
    * @param array $args
    * @return Singleton 
    */
    public static function getInstance()
    {
        $args = func_get_args();
        $initialize = isset($args[0]) ? $args[0] : true;
        $args = isset($args[1]) ? $args[1] : array();

        $className = get_called_class();
        if (!self::isConstructed($className)) 
        {
            // 構建方法
            self::$isFromInternal = true;
            self::$instances[$className] = new $className;
            self::$isFromInternal = false;

            // 自動初始化
            if ($initialize && !self::isInitialized($className)) {
                $method = array(self::$instances[$className], self::INITIALIZE_METHOD_NAME);
                if (is_callable($method)) {
                    call_user_func_array($method, $args);
                    self::$initialized[$className] = true;
                }
            }
        }
        return self::$instances[$className];
    }
    
   /**
    * 傳回實體但不要自動初始化
    * 
    * @return Singleton 
    */
    public function getInstanceWithoutInitialize()
    {
        return $this->getInstance(false);
    }

   /**
    * 預先初始化
    * 
    * @return void
    * @throws Sewii\Exception\RuntimeException
    */
    protected function preinitialize() 
    {
        $className = get_called_class();
        if (self::isInitialized($className)) {
            throw new Exception\RuntimeException("The Singleton class $className was prohibited to repeat initialize.");
        }

        if (!isset(self::$initialized[$className]))
            self::$initialized[$className] = true;
    }

   /**
    * 傳回是否已經建構
    * 
    * @param string $className
    * @return boolean
    */
    protected static function isConstructed($className) 
    {
        return isset(self::$instances[$className]);
    }

   /**
    * 傳回是否已經初始化
    * 
    * @param string $className
    * @return boolean
    */
    protected static function isInitialized($className) 
    {
        return isset(self::$initialized[$className]);
    }
}

?>