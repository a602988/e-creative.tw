<?php

/**
 * 事件處理
 * 
 * @version 1.1.9 2013/07/17 10:06
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Event;

use ArrayAccess;
use ReflectionMethod;
use ReflectionFunction;
use Sewii\Exception;

class Event 
{
    /**
     * 事件表
     *
     * @var array
     */
    public static $events = array();
    
    /**
     * 附加事件
     *
     * @param string $type
     * @param mixed $listener
     * @param boolean $one
     * @return void
     */
    public static function on($type, $listener, $one = false)
    {
        if (!is_callable($listener)) {
            throw new Exception\InvalidArgumentException('無效的事件 Callback');
        }
        self::$events[$type][] = array(
            'listener' => $listener, 
            'one' => (bool) $one
        );
    }
    
    /**
     * 附加事件 (只執行一次)
     *
     * @see self::on()
     * @param string $event
     * @param mixed $listener
     * @return void
     */
    public static function one($type, $listener)
    {
        self::on($type, $listener, true);
    }
    
    /**
     * 卸載事件
     *
     * @param string $event
     * @param mixed $listener
     * @return void
     */
    public static function off($type, $listener = null)
    {
        if (isset(self::$events[$type])) {
            if (isset($listener)) {
                foreach (self::$events[$type] as $index => $event) {
                    if ($listener === $event['listener']) {
                        unset(self::$events[$type][$index]);
                    }
                }
            }
            else unset(self::$events[$type]);
        }
    }
    
    /**
     * 觸發事件
     *
     * @todo $params 直接附加在 $eventObject 上!!
     * @param string $type
     * @param mixed $target
     * @param array|ArrayAccess|object $params
     * @return void
     */
    public static function trigger($type, $target = null, $params = array())
    {
        if (isset(self::$events[$type])) {
            foreach (self::$events[$type] as $index => $event) {

                // Only one
                if ($event['one'] === true) {
                    unset(self::$events[$type][$index]);
                }

                // Callback
                $eventObject = new EventObject($type, $target, $params);
                $paramsNumber = self::getParamsNumber($event['listener']);
                $args = array_pad(array($eventObject), $paramsNumber, null);
                call_user_func_array($event['listener'], $args);

                // Is stopped?
                if ($eventObject->isPropagationStopped()) break;
            }
        }
    }
    
    /**
     * 傳回 Callback 的參數
     *
     * @param mixed $listener
     * @return integer
     */
    protected static function getParamsNumber($listener) 
    {
        $instance = null;

        //callable
        if (is_array($listener)) {
            list($class, $method) = $listener;
            $instance = new ReflectionMethod($class, $method);
        }
        //class::method syntax
        else if (is_string($listener) && strpos($listener, '::') !== false) {
            list($class, $method) = explode('::', $listener);
            $instance = new ReflectionMethod($class, $method);
        }
        //objects as functions
        else if (method_exists($listener, '__invoke')) {
            $instance = new ReflectionMethod($listener, '__invoke');
        }
        //assume it's a function
        else {
            $instance = new ReflectionFunction($listener);
        }
        return $instance->getNumberOfRequiredParameters();
     }
}

?>