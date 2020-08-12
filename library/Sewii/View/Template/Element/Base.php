<?php

/**
 * Base 物件
 * 
 * @version 1.0.4 2013/06/12 14:38
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Sewii\View\Template\Element;

use Sewii\Exception;
use Sewii\View\Template;

class Base extends Element
{    
    /**
     * 標籤名稱
     * 
     * {@inheritDoc}
     */
    const ELEMENT_NAME = 'base';

    /**#@+
     * 屬性鍵名
     * @const string
     */
    const KEY_HREF   = 'href';
    const KEY_TARGET = 'target';
    /**#@-*/

    /**
     * 渲染選擇器
     * 
     * {@inheritDoc}
     */
    protected $selectors = array(
        'head > meta:last + title' => 'after',
        'head > meta:last' => 'after'
    );
    
    /**#@+
     * 未找到選取器時的動作
     * {@inheritDoc}
     */
    const IF_NOT_FOUND_ACTION = 'head|prepend';
    const OR_NOT_FOUND_ACTION = '|prepend';
    /**#@-*/
    
    /**
     * 設定屬性 href
     * 
     * @param string $value
     * @return Base
     */
    public function href($value)
    {
        $this->setAttr(self::KEY_HREF, $value);
        return $this;
    }
    
    /**
     * 設定屬性 target
     * 
     * @param string $value
     * @return Base
     */
    public function target($value)
    {
        $this->setAttr(self::KEY_TARGET, $value);
        return $this;
    }
    
    /**
     * 新增到文件
     * 
     * {@inheritDoc}
     */
    public function render($selectors = null)
    {
        //如果已存在時直接更新
        $isExists = false;
        $target = $this->getContext(self::ELEMENT_NAME);
        if ($target->length) {
            $element = trim($this->get(), PHP_EOL);
            $element = $target->last()->replaceWith($element);
            $target->not($element)->remove();
            $isExists = true;
        }

        if (!$isExists) {
            parent::render($selectors);
        }

        return $this;
    }
}

?>