<?php

/**
 * JS 物件
 * 
 * @version 1.0.5 2013/06/12 14:38
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Sewii\View\Template\Element;

use Sewii\Exception;
use Sewii\View\Template;

class Js extends Script
{
    /**
     * JS 類型
     * 
     * @const string
     */
    const TYPE_JS = 'text/javascript';

    /**
     * 渲染選擇器
     * 
     * {@inheritDoc}
     */
    protected $selectors = array(
        'script[type="text/javascript"]:last' => 'after',
        'script:last' => 'after'
    );
    
    /**
     * 設定 Javascript
     * 
     * @param string $src
     * @return Js
     */
    public function src($src)
    {
        parent::src($src);
        $this->type(self::TYPE_JS);
        return $this;
    }
}

?>