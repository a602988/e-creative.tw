<?php

/**
 * 路由轉換器
 * 
 * @version 1.0.0 2013/02/08 03:05
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Router;

use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Data\Hashtable;

class RouteConvert
{
    /**
     * 轉換為靜態路由
     *
     * @param string $uri
     * @return string
     */
    public static function toStatic($uri)
    {
        foreach (self::getRoutes() as $route) {
            $toStatic = $route::toStatic($uri);
            if ($toStatic != $uri) {
                $uri = $toStatic;
                break;
            }
        }
        return $uri;
    }

    /**
     * 轉換為動態路由
     *
     * @param string $uri
     * @return string
     */
    public static function toDynamic($uri)
    {
        foreach (self::getRoutes() as $route) {
            $toDynamic = $route::toDynamic($uri);
            if ($toDynamic != $uri) {
                $uri = $toDynamic;
                break;
            }
        }
        return $uri;
    }
    
    /**
     * 傳回路由表
     *
     * @return array
     */
    protected static function getRoutes()
    {
        return Router::getInstance()->getRoutes();
    }
}

?>