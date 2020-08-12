<?php

/**
 * Link 物件
 * 
 * @version 1.2.2 2013/06/12 14:38
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Sewii\View\Template\Element;

use Sewii\Exception;
use Sewii\View\Template;

class Link extends Element
{
    /**
     * 標籤名稱
     * 
     * {@inheritDoc}
     */
    const ELEMENT_NAME = 'link';
    
    /**#@+
     * 屬性鍵名
     * @const string
     */
    const KEY_CHARSET   = 'charset';
    const KEY_TYPE      = 'type';
    const KEY_HREF      = 'href';
    const KEY_HREFLANG  = 'hreflang';
    const KEY_REL       = 'rel';
    const KEY_REV       = 'rev';
    const KEY_TARGET    = 'target';
    const KEY_MEDIA     = 'media';
    const KEY_SIZES     = 'sizes';
    /**#@-*/
    
    /**
     * 渲染選擇器
     * 
     * {@inheritDoc}
     */
    protected $selectors = array(
        'head > link:last' => 'after'
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
     * @return Link
     */
    public function type($value)
    {
        $this->setAttr(self::KEY_TYPE, $value);
        return $this;
    }
    
    /**
     * 設定屬性 href
     * 
     * @param string $value
     * @return Link
     */
    public function href($value)
    {
        $this->setAttr(self::KEY_HREF, $value);
        return $this;
    }
    
    /**
     * 設定屬性 hreflang
     * 
     * @param string $value
     * @return Link
     */
    public function hreflang($value)
    {
        $this->setAttr(self::KEY_HREFLANG, $value);
        return $this;
    }
    
    /**
     * 設定屬性 rel
     * 
     * @param string $value
     * @return Link
     */
    public function rel($value)
    {
        $this->setAttr(self::KEY_REL, $value);
        return $this;
    }
    
    /**
     * 設定屬性 rev
     * 
     * @param string $value
     * @return Link
     */
    public function rev($value)
    {
        $this->setAttr(self::KEY_REV, $value);
        return $this;
    }
    
    /**
     * 設定屬性 target
     * 
     * @param string $value
     * @return Link
     */
    public function target($value)
    {
        $this->setAttr(self::KEY_TARGET, $value);
        return $this;
    }
    
    /**
     * 設定屬性 media
     * 
     * @param string $value
     * @return Link
     */
    public function media($value)
    {
        $this->setAttr(self::KEY_MEDIA, $value);
        return $this;
    }
    
    /**
     * 設定屬性 sizes
     * 
     * @param string $value
     * @return Link
     */
    public function sizes($value)
    {
        $this->setAttr(self::KEY_SIZES, $value);
        return $this;
    }
}

?>