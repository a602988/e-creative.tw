<?php

/**
 * 資料實體抽象類別
 * 
 * @version 1.0.1 2014/04/10 18:10
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Sewii\Data\Dataset;

use ArrayIterator;
use LimitIterator;

class ArrayDataset extends AbstractDataset
{
    /**
     * 資料來源
     * 
     * @var Iterator
     */
    protected $source;
    
    /**
     * 資料總數
     * 
     * @var integer
     */
    protected $count;
    
    /**
     * 建構子
     * 
     * @param Select $select
     * @param Sql|Adapter
     */
    public function __construct(array $source) 
    {
        $this->source = $source;
    }
    
    /**
     * 計數方法
     *  
     * @return integer
     */
    public function count()
    {
        if ($this->count !== null) {
            return $this->count;
        }
        
        $this->count = count($this->source);
        return $this->count;
    }

    /**
     * 傳回迭代器
     *
     * @return Traversable
     */
    public function getIterator() 
    { 
        $iterator = new ArrayIterator($this->source);
        if (isset($this->offset) && isset($this->limit)) {
            $iterator = new LimitIterator($iterator, $this->offset, $this->limit);
        }
        return $iterator;
    }
    
    /**
     * 檢查資料來源是否可被接受
     *
     * {@inheritDoc}
     */
    public static function isAccept($source)
    {
        return is_array($source);
    }
}
