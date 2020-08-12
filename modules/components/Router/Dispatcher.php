<?php

/**
 * 路由分發器
 * 
 * @version 1.0.5 2013/02/02 11:39
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Router;

use ReflectionClass;
use Sewii\Exception;

class Dispatcher
{
    /**
     * 分發方法
     *
     * @param Intent $intent
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    public function dispatch(Intent $intent)
    {
        if ($action = $intent->getAction()) 
        {
            if (!is_object($action) && !class_exists($action)) {
                throw new Exception\InvalidArgumentException("不明的意圖動作物件: $action");
            }

            $class = new ReflectionClass($action);
            $interface = IntentActionInterface::CLASS_NAME;
            if (!$class->implementsInterface($interface)) {
                throw new Exception\InvalidArgumentException("意圖動作物件必須實作 {$interface} 介面: {$action}");
            }

            $controller = $action::executeIntent($intent);
        }
    }
}

?>