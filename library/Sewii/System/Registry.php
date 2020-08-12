<?php

/**
 * 註冊表
 * 
 * @version 1.0.6 2013/05/08 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */

namespace Sewii\System;

use Sewii\Exception;
use Sewii\Text\Regex;

class Registry
{
    /**
     * 存儲器
     *
     * @var array
     */    
    protected static $storage;

    /**
     * 建構子
     * 
     * @throws Sewii\Exception\RuntimeException
     */
    final public function __construct() 
    {
        throw new Exception\RuntimeException('The Registry class was prohibited to construct.');
    }
    
    /**
     * 傳回是否已註冊
     *
     * @param string $name
     * @return boolean
     */
    public static function has($name)
    {
        return isset(self::$storage[$name]);
    }

    /**
     * 傳回方法
     *
     * @param string $name
     * @return mixed
     */
    public static function &get($name)
    {
        $item = null;
        if (self::has($name)) {
            $item = &self::$storage[$name];
        }
        return $item;
    }
    
    /**
     * 設定方法
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function set($name, $value = null)
    {
        //如果 $name 傳入物件而且沒有給 $value 時
        //則表示省略名稱並直接以物件名稱設定名稱
        if (is_object($name) && is_null($value)) {
            $value = $name;
            $name = get_class($name);
        }
        self::$storage[$name] = $value;
    }
    
    /**
     * 刪除方法
     *
     * @param string $name
     * @return void
     */
    public static function delete($name)
    {
        if (self::has($name)) {
            unset(self::$storage[$name]);
        }
    }
    
    /**
     * 靜態呼叫子
     *
     * @param string $name 
     * @param array $args
     * @return mixed
     * @throw Sewii\Exception\InvalidArgumentException
     */
    public static function __callStatic($name, $args)
    {
        $action = strtolower(substr($name, 0, 3));
        $name = lcfirst(Regex::replace("/^$action/", '', $name));
        switch($action) {
            case 'get':
                return self::get($name);
            case 'set':
                if (count($args) < 1) {
                    throw new Exception\InvalidArgumentException('必須傳入設定值');
                }
                array_unshift($args, $name);
                return call_user_func_array(array('self', 'set'), $args);
        }

        throw new Exception\BadMethodCallException(
            sprintf('呼叫未定義的方法: %s::%s()', __CLASS__, $name)
        );
    }
}

?>