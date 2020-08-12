<?php

/**
 * 衍生物件抽像類別
 * 
 * @version 1.5.1 2013/06/12 20:57
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\View\Template;

use Sewii\Exception;

abstract class Child
{
    /**
     * 主類別實體
     * 
     * @var Template
     */
    private $context;

    /**
     * 建構子
     * 
     * @param Template $context
     */
    public function __construct($context = null) 
    {
        if ($context !== null) {
            $this->setContext($context);
        }
    }
    
    /**
     * 設定主文件實體
     * 
     * @param Template $instance
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    public function setContext($context)
    {
        if (!$context instanceof Template) {
            throw new Exception\InvalidArgumentException(
                '無法設定主類別實體參考，錯誤的類型: ' . 
                ( is_object($context) ? get_class($context) : gettype($context) )
            );
        }
        $this->context = $context;
    }

    /**
     * 傳回主文件實體
     * 
     * @param string $selector
     * @return Template
     * @throws Exception\RuntimeException
     */
    public function getContext($selector = null)
    {
        $context = $this->context;

        if (!$this->context) {
            $context = Template::getInstance();
        }

        if (!$context instanceof Template) {
            throw new Exception\RuntimeException('無法取得主類別實體參考，沒有任何已建立的樣板文件。');
        }

        if ($selector) {
            return $context[$selector];
        }

        return $context;
    }
}

?>