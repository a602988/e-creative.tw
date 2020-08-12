<?php

/**
 * Meta 物件
 * 
 * @version 1.0.6 2013/06/12 14:38
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Sewii\View\Template\Element;

use Sewii\Exception;
use Sewii\View\Template;

class Meta extends Element
{    
    /**
     * 標籤名稱
     * 
     * {@inheritDoc}
     */
    const ELEMENT_NAME = 'meta';

    /**#@+
     * 屬性鍵名
     * @const string
     */
    const KEY_NAME       = 'name';
    const KEY_HTTP_EQUIV = 'http-equiv';
    const KEY_CONTENT    = 'content';
    /**#@-*/

    /**
     * 渲染選擇器
     * 
     * {@inheritDoc}
     */
    protected $selectors = array(
        'head > meta:last' => 'after'
    );
    
    /**
     * 設定屬性 name
     * 
     * @param string $name
     * @param string $content
     * @return Meta
     */
    public function name($name, $content = null)
    {
        $this->setAttr(self::KEY_NAME, $name);
        if ($content !== null) {
            //TODO:: 該用 self::setContent() ???
            $this->setAttr(self::KEY_CONTENT, $content);
        }
        return $this;
    }
    
    /**
     * 設定屬性 http-equiv
     * 
     * @param string $httpEquiv
     * @param string $content
     * @return Meta
     */
    public function httpEquiv($httpEquiv, $content = null)
    {
        $this->setAttr(self::KEY_HTTP_EQUIV, $httpEquiv);
        if ($content !== null) {
            $this->setAttr(self::KEY_CONTENT, $content);
        }
        return $this;
    }
    
    /**
     * 設定屬性 Content
     * 
     * @param string $content
     * @return Meta
     */
    public function setContent($content)
    {
        $this->setAttr(self::KEY_CONTENT, $content);
        return $this;
    }
    
    /**
     * 新增到文件
     * 
     * {@inheritDoc}
     */
    public function render($selectors = null)
    {
        //TODO: 如果有2個以上時會有問題
        //TODO: 要照 Base::render() 寫法
        //如果已存在時直接更新
        $isExists = false;
        foreach (array(self::KEY_NAME, self::KEY_HTTP_EQUIV) as $key) {
            if (isset($this->attrs[$key])) {
                $value = $this->attrs[$key];
                $target = $this->getContext(self::ELEMENT_NAME . '[' . $key. '="' . $value . '"]');
                if ($target->length) {
                    $tag = trim($this->get(), PHP_EOL);
                    $target->replaceWith($tag);
                    $isExists = true;
                }
            }
        }

        if (!$isExists) {
            parent::render($selectors);
        }

        return $this;
    }
}

?>