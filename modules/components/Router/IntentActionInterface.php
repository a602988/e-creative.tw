<?php

/**
 * 意圖動作介面
 * 
 * @version 1.0.0 2013/01/27 10:34
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Router;

use Spanel\Module\Component\Router\Intent;

interface IntentActionInterface
{
    /**
     * 類別名稱
     * 
     * @const string
     */
    const CLASS_NAME = __CLASS__;
    
    /**
     * 從意圖物件執行
     * 
     * @param Intent $intent
     * @return IntentActionInterface
     */
    public static function executeIntent(Intent $intent);
}

?>