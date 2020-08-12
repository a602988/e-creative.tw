<?php

/**
 * Script 物件
 * 
 * @version 1.1.2 2013/06/12 14:38
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Sewii\View\Template\Element;

use Sewii\Exception;
use Sewii\View\Template;

class Script extends Element
{
    /**
     * 標籤名稱
     * 
     * {@inheritDoc}
     */
    const ELEMENT_NAME = 'script';
    
    /**#@+
     * 屬性鍵名
     * @const string
     */
    const KEY_CHARSET = 'charset';
    const KEY_TYPE    = 'type';
    const KEY_SRC     = 'src';
    const KEY_DEFER   = 'defer';
    const KEY_ASYNC   = 'async';
    /**#@-*/
    
    /**#@+
     * 未找到選取器時的動作
     * {@inheritDoc}
     */
    const IF_NOT_FOUND_ACTION = 'body|append';
    const OR_NOT_FOUND_ACTION = '|append';
    /**#@-*/
    
    /**
     * 渲染選擇器
     * 
     * {@inheritDoc}
     */
    protected $selectors = array(
        'script:last' => 'after'
    );
    
    /**
     * 設定屬性 charset
     * 
     * @param string $value
     * @return Link
     */
    public function charset($value)
    {
        $this->setAttr(self::KEY_CHARSET, $value);
        return $this;
    }
    
    /**
     * 設定屬性 type
     * 
     * @param string $value
     * @return Script
     */
    public function type($value)
    {
        if (!$this->getContext()->isHmtl5()) {
            $this->setAttr(self::KEY_TYPE, $value);
        }
        return $this;
    }
    
    /**
     * 設定屬性 src
     * 
     * @param string $value
     * @return Script
     */
    public function src($value)
    {
        $this->setAttr(self::KEY_SRC, $value);
        return $this;
    }
    
    /**
     * 設定屬性 defer
     * 
     * @param string $value
     * @return Script
     */
    public function defer($value)
    {
        $this->setAttr(self::KEY_DEFER, $value);
        return $this;
    }
    
    /**
     * 設定屬性 async
     * 
     * @param string $value
     * @return Script
     */
    public function async($value)
    {
        $this->setAttr(self::KEY_ASYNC, $value);
        return $this;
    }
}

?>