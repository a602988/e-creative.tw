<?php

/**
 * 意圖動作介面
 * 
 * @version 1.0.1 2013/07/16 06:24
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Router;

interface RouteInterface
{
    /**
     * 比對方法
     *
     * @param array|ArrayAccess $params
     * @return boolean
     */
    public function match($params = null);

    /**
     * 傳回路由意圖
     * 
     * @return Intent
     */
    public function getIntent();

    /**
     * 轉換為動態路由
     *
     * @param string $uri
     * @return string
     */
    public static function toDynamic($uri);
    
    /**
     * 轉換為靜態路由
     *
     * @param string $uri
     * @return string
     */
    public static function toStatic($uri);
}

?>