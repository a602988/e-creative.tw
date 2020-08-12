<?php

/**
 * 資料實體抽象類別
 * 
 * @version 1.0.0 2013/12/03 15:48
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Sewii\Data\Dataset;

use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Adapter\Adapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\ResultSet\HydratingResultSet;
use Zend\Stdlib\Hydrator\Reflection as HydratorReflection;
use Sewii\Data\Database\Database;
use Sewii\Type\Arrays;
use Sewii\Text\Regex;

class DatabaseDataset extends AbstractDataset
{
    /**
     * 資料來源
     * 
     * @var Select
     */
    protected $source;
    
    /**
     * 資料總數
     * 
     * @var integer
     */
    protected $count;
    
    /**
     * SQL 物件
     * 
     * @var Sql
     */
    protected $sql;
    
    /**
     * 建構子
     * 
     * @param Select $source 
     * @param Adapter|Sql $adapterOrSql 
     * @throws Exception\InvalidArgumentException 
     */
    public function __construct(Select $source, $adapterOrSql = null) 
    {
        $this->source = $source;

        if ($adapterOrSql === null) {
            $database = Database::getInstance();
            $adapterOrSql = $database->sql();
        }

        if ($adapterOrSql instanceof Adapter) {
            $adapterOrSql = new Sql($adapterOrSql);
        }

        if (!$adapterOrSql instanceof Sql) {
            throw new Exception\InvalidArgumentException(
                '$adapterOrSql must be an instance of Zend\Db\Adapter\Adapter or Zend\Db\Sql\Sql'
            );
        }

        $this->sql = $adapterOrSql;
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
        
        $select = clone $this->source;
        $select->reset(Select::LIMIT);
        $select->reset(Select::OFFSET);
        $select->reset(Select::ORDER);

        $countExp = new Expression('COUNT(1)');
        $countSelect = new Select;
        $countSelect->columns(array($countExp));
        $countSelect->from(array('original' => $select));

        $statement = $this->sql->prepareStatementForSqlObject($countSelect);
        $result = $statement->execute();
        $row = $result->current();
        $count = Arrays::getFirst($row);
        $this->count = intval($count);

        return $this->count;
    }

    /**
     * 傳回迭代器
     *
     * @return Traversable
     */
    public function getIterator() 
    { 
        $select = clone $this->source;

        if ($this->limit !== null) {
            $select->limit($this->limit);
        }

        if ($this->offset !== null) {
            $select->offset($this->offset);
        }
        
        $statement = $this->sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        
        if ($this->prototype !== null) {
            $resultSet = new HydratingResultSet(new HydratorReflection, $this->prototype);
            $resultSet->initialize($result);
        } else {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);
        }
        
        return $resultSet;
    }
    
    public function filter($exp)
    {
        $select = $this->source;
        $select->where($exp);
    }
    
    public function order($field, $direction = null, $charset = null)
    {
        $select = $this->source;
        
        $direction = strtoupper( $direction) ?: Select::ORDER_ASCENDING;
        $isSafe = Regex::isMatch('/^[\w\.]+/', $field)
              and Regex::isMatch('/^[a-z]+/i', $direction)
              and ($charset || Regex::isMatch('/^[a-z\-]+/i', $charset));
        
        if ($isSafe) {
            $order = "$field $direction";
            if ($charset !== null) {
                $platform = $this->sql->getAdapter()->getPlatform();
                $field = $platform->quoteIdentifier($field);
                $charset = $platform->quoteValue($charset);
                $order = new Expression("CONVERT($field USING $charset) $direction");
            }
            $select->order($order);
        }
        return $this;
    }
    
    public function sort()
    {
    }
    
    public function getSql()
    {
        return $this->sql;
    }
    
    /**
     * 檢查資料來源是否可被接受
     *
     * {@inheritDoc}
     */
    public static function isAccept($source)
    {
        return ($source instanceof Select);
    }
}
