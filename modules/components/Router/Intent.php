<?php

/**
 * 意圖物件
 * 
 * @version 1.3.2 2013/06/16 05:10
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Router;

use Sewii\Exception;
use Sewii\System\Accessors\Accessors;
use Sewii\Data\Hashtable;
use Spanel\Module\Component\Controller;

class Intent extends Accessors
{
    /**
     * 意圖動作
     *
     * @var array
     */   
    protected $action = null;
    
    /**
     * 觸發者
     *
     * @var mixed
     */   
    protected $caller = null;
    
    /**
     * 額外資料
     *
     * @var array
     */   
    protected $extras = array();

    /**
     * 建構子
     * 
     * @param mixed $action
     */
    public function __construct($action)
    {
        $this->setAction($action);
    }
    
    /**
     * 建構實體
     *
     * @return Intent
     */
    public static function newInstance()
    {
        return new self;
    }
    
    /**
     * 傳回動作
     *
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }
    
    /**
     * 設定動作
     *
     * @param mixed $value
     * @return Intent
     */
    public function setAction($value)
    {
        $this->action = $value;
        return $this;
    }
    
    /**
     * 傳回觸發者
     *
     * @return mixed
     */
    public function getCaller()
    {
        return $this->caller;
    }
    
    /**
     * 設定觸發者
     *
     * @param mixed $value
     * @return mixed
     */
    public function setCaller($value)
    {
        $this->caller = $value;
        return $this;
    }
    
    /**
     * 傳回額外資料
     *
     * @return Hastable
     */
    public function getExtras()
    {
        return $this->extras;
    }
    
    /**
     * 設定額外資料
     *
     * @param array $value
     * @return Intent
     */
    public function setExtras($value)
    {
        $value = new Hashtable($value);
        $this->extras = $value;
        return $this;
    }
}

?>