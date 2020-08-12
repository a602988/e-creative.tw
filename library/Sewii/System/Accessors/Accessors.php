<?php

/**
 * 屬性存取操作工具
 * 
 * @version 1.0.0 2013/02/06 16:33
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Sewii\System\Accessors;

use Sewii\Exception;

class Accessors extends AbstractAccessors
{
    /**
     * 實體參照
     *
     * @var Accessors
     */
    protected static $instance;
    
    /**
     * 取值器
     *
     * @param string $name
     * @return mixed
     */
    public static function getter($class, $name)
    {
        $args = func_get_args();
        return self::getInstance()->__get($args);
    }
    
    /**
     * 設定器
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public static function setter($class, $name, $value)
    {
        $args = func_get_args();
        self::getInstance()->__set($args);
    }
    
    /**
     * 傳回實體
     *
     * @param object $class
     * @return boolean
     */
    protected static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self;
        }
        return self::$instance;
    }
}

?>