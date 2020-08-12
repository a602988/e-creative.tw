<?php

/**
 * 標籤物件抽像類別
 * 
 * @version 1.3.0 2013/06/12 20:24
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Sewii\View\Template\Element;

use Sewii\Exception;
use Sewii\Text\Strings;
use Sewii\View\Template;

class Element extends Template\Child
{
    /**
     * 標籤名稱
     * 
     * @const string
     */
    const ELEMENT_NAME = 'element';
    
    /**
     * 未找到選取器時的優先動作
     * 
     * selector|method (選取器|動作)
     * 
     * @const string
     */
    const IF_NOT_FOUND_ACTION = 'head|append';
    
    /**
     * 未找到選取器時的最終動作
     * 
     * selector|method (選取器|動作)
     * 不指定選取器時即指整份文件
     * 
     * @const string
     */
    const OR_NOT_FOUND_ACTION = '|prepend';
    
    /**
     * 表示標籤屬性集合
     * 
     * @var array
     */
    protected $attrs = array();
    
    /**
     * 渲染選擇器
     * 
     * @var array
     */
    protected $selectors = array();

    /**
     * 工廠模式
     * 
     * @todo self::ELEMENT_NAME 應該改為變數才可以生產任何未定義的標籤
     * @param string $name
     * @param Template $context
     * @return Element
     */
    public static function factory($name = null, $context = null) 
    {
        $name = ucfirst($name ?: static::ELEMENT_NAME);
        if (class_exists($className = __NAMESPACE__ . '\\' . $name)) {
            $instance = new $className($context);
            return $instance;
        }
        
        throw new Exception\InvalidArgumentException("建立元素標籤失敗，不支援的項目: $name");
    }
    
    /**
     * 傳回物件
     * 
     * @return Template
     */
    public function get()
    {
        $attrs = array();
        foreach ($this->attrs as $key => $value) {
            $value = Strings::html2Text($value);
            $attr = sprintf('%s="%s"', $key, $value);
            array_push($attrs, $attr);
        }

        if ($attrs = implode(' ', $attrs)) $attrs .= ' ';
        $html = sprintf('<%s %s/>', static::ELEMENT_NAME, $attrs);
        return $this->getContext()->object($html);
    }
    
    /**
     * 設定渲染選擇器
     * 
     * @param array $value
     * @return ElementAbstract
     */
    public function setSelectors(array $value = array())
    {
        $this->selectors = $value;
        return $this;
    }
    
    /**
     * 傳回渲染選擇器
     * 
     * @return array
     */
    public function getSelectors()
    {
        return $this->selectors;
    }
    
    /**
     * 設定屬性
     * 
     * @param string|array $key
     * @param string $value
     * @return ElementAbstract
     */
    public function setAttr($key, $value = null)
    {
        // 傳入 key/value 陣列
        if (is_array($key)) {
            foreach ($key as $key => $value) {
                $this->attrs[$key] = (string) $value;
            }
        }
        // 指定 key/value 值
        else if (is_string($key) || is_numeric($key)) {
            $this->attrs[$key] = (string) $value;
        }

        else throw new Exception\InvalidArgumentException("無效的參數: $key");
        return $this;
    }
    
    /**
     * 渲染方法
     * 
     * 當 $selectors 傳入陣列 (key/value) 時，key 表示選取器，value 為 DOM 方法。
     * 
     * @param string|array $selectors 
     * @return Element
     */
    public function render($selectors = null)
    {
        $selectors = $selectors ?: $this->selectors;
        $element = trim($this->get(), $nl = PHP_EOL);

        // 依選擇器插入
        $inserted = false;
        if (is_string($selectors)) $selectors = array($selectors => null);
        foreach ((array) $selectors as $selector => $method) {
            $target = $this->getContext($selector)->first();
            if ($target->length) {
                switch(strtolower($method)) {
                    default:
                    case 'after':   $element = $nl . $element; break;
                    case 'before':  $element = $element . $nl; break;
                    case 'append':  $element = $element . $nl; break;
                    case 'prepend': $element = $nl . $element; break;
                }
                $target->$method($element);
                $inserted = true;
                break;
            }
        }

        if (!$inserted) 
        {
            // 未找到選擇器從此處插入
            list($selector, $method) = explode('|', static::IF_NOT_FOUND_ACTION);
            if (($target = $this->getContext($selector)) && $target->length) {
                $element = $element . $nl;
                $target->$method($element);
            }
            
            // 都未找到時從此處插入
            else {
                list($selector, $method) = explode('|', static::OR_NOT_FOUND_ACTION);
                $target = empty($selector) ? $this->getContext()->getDocument() : $this->getContext($selector);
                if ($target->length) {
                    $target->$method($element);
                }
            }
        }
        return $this;
    }
}

?>