<?php

/**
 * 樣板渲染器
 * 
 * @version 1.5.1 2013/06/14 16:36
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\View\Template;

use StdClass;
use Sewii\Data\Hashtable;

class Renderer extends Object
{
    /**
     * 項目選擇器
     * 
     * @const string
     */
    const SELECTOR = '.renderer-item';
    
    /**
     * 實體參考
     * 
     * @var array
     */
    protected static $instances = array();

    /**
     * 執行狀態
     * 
     * @var array
     */
    protected static $status = array();
    
    /**
     * 目前渲染器 id
     * 
     * @var string
     */
    protected $id;
    
    /**
     * 儲存最後查詢
     * 
     * @var string
     */
    protected $selector;

    /**
     * 建構子
     * 
     * {@inheritDoc}
     */
    public function __construct($object = null, $context = null) 
    {
        if ($object instanceof Template) {
            $context = $object;
            $object = null;
        }
        parent::__construct($object, $context);
    }

    /**
     * 工廠方法
     *
     * @todo $container 直接入傳物件
     * @param string $container
     * @param string $item
     * @return Object
     */
    public function factory($container, $item = self::SELECTOR)
    {
        $id = $selector = $container . ' ' . $item . ':first';

        // 從實體參考中尋找
        if (isset(self::$instances[$id])) {

            // TODO: 避免 Clone 方法會自動在頂層元素加上命名空間
            return self::$instances[$id]->clone();
        }

        // 依指定選擇器尋找
        // TODO: 如果重複在選擇器後方加上空白字元或 :first?
        $item = $this->getContext($selector);
        if ($item->length) {
            $this->setPrototype($item);
        }
        
        // 未找到選擇器時嘗試抓取容器內的第一個子元素
        else {
            $firstChild = $container . ' > *:first';
            $item = $this->getContext($firstChild);
            if ($item->length) {
                $this->setPrototype($item);
            }
        }
        
        $cloned = clone $this;
        $cloned = $cloned->clone();
        $cloned->id = $id;
        return self::$instances[$id] = $cloned;
    }
    
    /**
     * 尋找選擇器
     *  
     * {@inheritDoc}
     */
    public function find($selector) 
    {
        $this->selector = $selector;
        
        // 快取上次的查詢
        $key = "{$this->id} {$this->selector} isNotExists";
        if (isset(self::$status[$key]) && 
            self::$status[$key] instanceof self) {
            return self::$status[$key];
        }

        $found = parent::find($selector);
        if (!isset(self::$status[$key])) {
            self::$status[$key] = !$found->length ? $found : false;
        }

        return $found;
    }

    /**
     * 傳回/設定參數資料
     *
     * {@inheritDoc}
     */
    public function param($value = null)
    {
        // Getter
        if (func_num_args() === 0) {

            // 快取上次的內容
            $key = "{$this->id} {$this->selector} param";
            if (isset(self::$status[$key])) {
                return self::$status[$key];
            }

            $param = parent::param();
            self::$status[$key] = $param;
            return $param;
        }

        // Setter
        return parent::param($value);
    }
}

?>