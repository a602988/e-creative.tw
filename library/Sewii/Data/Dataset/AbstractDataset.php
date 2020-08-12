<?php

/**
 * 資料實體抽象類別
 * 
 * @version 1.0.0 2013/12/15 23:16
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Sewii\Data\Dataset;

use Sewii\System\Accessors\AbstractAccessors;

abstract class AbstractDataset 
    extends AbstractAccessors
    implements DatasetInterface
{
    /**
     * 資料偏移量
     * 
     * @var integer
     */
    protected $limit;
    
    /**
     * 資料讀取量
     * 
     * @var integer
     */
    protected $offset;
    
    /**
     * 資料原型
     * 
     * @var mixed
     */
    protected $prototype;

    /**
     * 類別名稱
     * 
     * @const string
     */
    const CLASS_NAME = __CLASS__;
    
    /**
     * 工廠模式
     * 
     * @param mixed $source 
     * @throws Exception\InvalidArgumentException 
     * @return AbstractDataset
     */
    public static function factory($source)
    {
        $dataset = null;
        
        if (ArrayDataset::isAccept($source)) {
            $dataset = new ArrayDataset($source);
        }
        
        else if (IteratorDataset::isAccept($source)) {
            $dataset = new IteratorDataset($source);
        }

        else if (DatabaseDataset::isAccept($source)) {
            $dataset = new DatabaseDataset($source);
        }
        
        if (!$dataset) {
            throw new Exception\InvalidArgumentException('不支援此類型的資料來源物件');
        }

        return $dataset;
    }
    
    /**
     * 設定資料偏移量
     *
     * {@inheritDoc}
     */
    public function offset($value)
    {
        $this->offset = (int) $value;
        return $this;
    }
    
    /**
     * 設定資料讀取量
     *
     * {@inheritDoc}
     */
    public function limit($value)
    {
        $this->limit = (int) $value;
        return $this;
    }
    
    /**
     * 設定資料原型
     * 
     * @param mixed $prototype 
     * @return $this
     */
    public function setPrototype($prototype)
    {
        $this->prototype = $prototype;
        return $this;
    }
    
    public function getSource()
    {
        return $this->source;
    }
}
