<?php

/**
 * 樣板推遲器
 * 
 * @version 1.0.0 2013/06/12 00:05
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\View\Template;

class Deferrer extends Child
{
    /**
     * 推遲地圖
     * 
     * @var array
     */
    protected $map = array();
    
    /**
     * 實體參考
     * 
     * @var array
     */
    protected static $instances = array();

    /**
     * 建構子
     * 
     * @param Template $base
     */
    public function __construct($base = null) 
    {
        self::$instances[] = $this;
    }

    /**
     * 多載子
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        $this->map[] = array($name, $args);
        return $this;
    }

    /**
     * 執行推遲方法
     * 
     * @return void
     */
    public function execute() 
    {
        $base = $this->getContext();
        foreach ($this->map as $executable) {
            list($method, $args) = $executable;
            $callable = array($base, $method);
            $base = call_user_func_array($callable, $args);
        }
        $this->map = array();
    }

    /**
     * 執行所有推遲方法
     * 
     * @return void
     */
    public static function executeAll() 
    {
        foreach (self::$instances as $instance) {
            $instance->execute();
            unset($instance);
        }
        self::$instances = array();
    }
}

?>