<?php

/**
 * CSS 物件
 * 
 * @version 1.1.2 2013/06/12 14:38
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Sewii\View\Template\Element;

use Sewii\Exception;
use Sewii\View\Template;

class Css extends Link
{
    /**
     * CSS 類型
     * 
     * @const string
     */
    const TYPE_CSS = 'text/css';

    /**
     * CSS Rel 類型
     * 
     * @const string
     */
    const REL_CSS = 'stylesheet';
    
    /**
     * 渲染選擇器
     * 
     * {@inheritDoc}
     */
    protected $selectors = array(
        'head > link[rel="stylesheet"]:last' => 'after',
        'head > link:last' => 'after'
    );
    
    /**
     * 設定屬性 type
     * 
     * @param string $value
     * @return Link
     */
    public function type($value)
    {
        if (!$this->getContext()->isHmtl5()) {
            $this->setAttr(self::KEY_TYPE, $value);
        }
        return $this;
    }
    
    /**
     * 設定 CSS
     * 
     * @param string $href
     * @return Css
     */
    public function href($href, $media = null)
    {
        parent::href($href);
        $this->rel(self::REL_CSS);
        $this->type(self::TYPE_CSS);
        if ($media !== null) {
            $this->media($media);
        }
        return $this;
    }
}

?>