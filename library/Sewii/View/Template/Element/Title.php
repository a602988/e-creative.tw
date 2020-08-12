<?php

/**
 * Title 物件
 * 
 * @version 1.0.0 2014/07/18 18:17
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Sewii\View\Template\Element;

use Sewii\Exception;
use Sewii\View\Template;

class Title extends Element
{
    /**
     * 標籤名稱
     * 
     * {@inheritDoc}
     */
    const ELEMENT_NAME = 'title';
    
    /**
     * 渲染選擇器
     * 
     * {@inheritDoc}
     */
    protected $selectors = array(
        'head > meta:last' => 'after',
    );
}

?>