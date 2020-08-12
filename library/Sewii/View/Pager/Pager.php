<?php

/**
 * 頁數器
 * 
 * @version 2.5.1 2013/11/29 15:55
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\View\Pager;

use Sewii\Type\Arrays;
use Sewii\System\Accessors\AbstractAccessors;

class Pager extends AbstractAccessors
{
    /**
     * 預設欄位名稱
     * 
     * @const integer
     */
    const FIELD = 'page';

    /**
     * 總列數
     * 
     * @var integer
     */
    private $rows;
    
    /**
     * 每頁列數
     * 
     * @var integer
     */
    private $size;
    
    /**
     * 目前頁數
     * 
     * @var integer
     */
    private $current;

    /**
     * 作用中欄位名稱
     * 
     * @var integer
     */
    private $field;

    /**
     * 建構子
     *
     * @param integer $rows
     * @param integer $size
     * @param integer $current
     * @param string $field
     */
    public function __construct($rows, $size, $current = null, $field = null)
    {
        $this->setRows($rows);
        $this->setSize($size);
        $this->setCurrent($current);
        $this->setField($field);
    }
    
    /**
     * 傳回作用中頁數欄位名稱
     * 
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * 設定作用中頁數欄位名稱
     * 
     * @param string $value
     * @return Pager
     */
    public function setField($value)
    {
        $this->field = isset($value) ? $value : self::FIELD;
        return $this;
    }
    
    /**
     * 傳回總列數
     * 
     * @return integer
     */
    public function getRows()
    {
        return $this->rows;
    }

    /**
     * 設定總列數
     * 
     * @param integer $value
     * @return Pager
     */
    public function setRows($value)
    {
        $this->rows = (int) $value;
        return $this;
    }
    
    /**
     * 傳回每頁列數
     * 
     * @return integer
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * 設定總列數
     * 
     * @param integer $value
     * @return Pager
     */
    public function setSize($value)
    {
        $this->size = (int) $value;
        return $this;
    }
    
    /**
     * 傳回目前頁數
     * 
     * @return integer
     */
    public function getCurrent()
    {
        return $this->current;
    }
    
    /**
     * 設定目前頁數
     * 
     * @param integer $current
     * @return Pager
     */
    public function setCurrent($current)
    {
        if (!isset($current)) {
            $current = Arrays::value($_GET, $this->field, 1);
        }

        $current = intval($current);
        ($current < $this->getFirst()) && $current = $this->getFirst();
        ($current > $this->getLast())  && $current = $this->getLast();
        $this->current = $current;
        return $this;
    }
    
    /**
     * 傳回總頁數
     * 
     * @return integer
     */
    public function getTotal()
    {
        // 總列數 / 每頁列數
        $total = (int) ceil($this->getRows() / $this->getSize());
        if ($total < 1) $total = 1;
        return $total;
    }
    
    /**
     * 傳回開始讀取列數
     * 
     * @return integer
     */
    public function getOffset()
    {
        $offset = $this->getSize() * ($this->getCurrent() - 1);
        return $offset;
    }
    
    /**
     * 傳回結束讀取列數
     * 
     * @return integer
     */
    public function getEnd()
    {
        $end = $this->getSize() * $this->getCurrent();
        if ($end > $this->getRows()) $end = $this->getRows();
        return $end;
    }

    /**
     * 取得第一頁
     * 
     * @return integer
     */
    public function getFirst()
    {
        return 1;
    }

    /**
     * 取得最後頁
     * 
     * @return integer
     */
    public function getLast()
    {
        return $this->getTotal();
    }

    /**
     * 取得上一頁
     * 
     * @return integer
     */
    public function getPrev()
    {
        $prev = $this->getCurrent() - 1;
        if ($prev < $this->getFirst()) $prev = $this->getFirst();
        return $prev;
    }

    /**
     * 取得下一頁
     * 
     * @return integer
     */
    public function getNext()
    {
        $next = $this->getCurrent() + 1;
        if ($next > $this->getLast()) $next = $this->getLast();
        return $next;
    }

    /**
     * 傳回資料序列號
     * 
     * @param integer $row
     * @return integer
     */
    public function getSerial($row)
    {
        $serial = $row + ( $this->getSize() * ($this->getCurrent() - 1) );
        return $serial;
    }
    
    /**
     * 傳回詳細資料
     * 
     * @return array
     */
    public function getInfo()
    {
        return array(
            'rows'    => $this->getRows(),
            'size'    => $this->getSize(),
            'total'   => $this->getTotal(),
            'current' => $this->getCurrent(),
            'first'   => $this->getFirst(),
            'last'    => $this->getLast(),
            'next'    => $this->getNext(),
            'prev'    => $this->getPrev(),
            'offset'  => $this->getOffset(),
            'end'     => $this->getEnd(),
        );
    }

    /**
     * 產生器
     *
     * @param string $type
     * @param array $config
     * @return string
     */
    public function generate()
    {
        list($type, $config) = array_pad(func_get_args(), 2, null);

        $output = '';
        switch($type) {
            default:
            case Classic::ID:
                $classic = new Classic(
                    $this->getRows(), 
                    $this->getSize(), 
                    $this->getCurrent(), 
                    $this->getField()
                );
                $output = $classic->generate($config);
                break;
        }
        return $output;
    }
}

?>