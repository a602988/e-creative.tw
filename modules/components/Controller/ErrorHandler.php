<?php

/**
 * 錯誤處理器
 * 
 * @version 1.0.5 2013/06/28 08:50
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Controller;

use Sewii\Exception;
use Sewii\System\Debugger\Debugger;
use Sewii\System\Registry;
use Spanel\Module\Component\Router;
use Spanel\Module\Component\Controller\Exception\HttpStatusException;

class ErrorHandler
{
    /**
     * 觸發器
     *
     * @param integer $type
     * @param string $message
     * @return void
     * @throws Exception\RuntimeException
     */
    public static function trigger($type, $message = null)
    {
        $exception = new HttpStatusException($message, $type);
        if (Registry::getConfig()->debugMode) {
            throw $exception;
        }

        Debugger::trigger($exception);
        Controller::getResponse()->sendHttpStatus($type);
    }
}

?>