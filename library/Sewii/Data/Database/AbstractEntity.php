<?php

/**
 * 資料表物件
 *
 * @version 1.0.0 2013/11/07 14:28
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Data\Database;

use Countable;
use Serializable;
use IteratorAggregate;
use ArrayIterator;

abstract class AbstractEntity
    implements Countable, Serializable, IteratorAggregate
{
    /**
     * 建構子
     * 
     * @param array $data
     */
    public function __construct(array $data = null) 
    {
        if ($data !== null) {
            $this->import($data);
        }
    }
    
    /**
     * 匯入資料
     *
     * @param array $data
     * @return AbstractEntity
     */
    public function import(array $data)
    {
        foreach ($data as $name => $value) {
            if ($this->isDefined($name)) {
                $this->__set($name, $value);
            }
        }
        return $this;
    }
    
    /**
     * 匯出資料
     *
     * @return array
     */
    public function export()
    {
        return array_filter($this->toArray(), 'strlen');
    }

    /**
     * 以陣列傳回資料
     *
     * @return array
     */
    public function toArray()
    {
        return get_object_vars($this);
    }
    
    /**
     * 傳回是否已定義
     *
     * @param object $class
     * @param string $name
     * @return boolean
     */
    public function isDefined($name)
    {
        return property_exists($this, $name);
    }
    
    /**
     * 丟出未定義例外
     *
     * @param string $name
     * @return void
     * @throws Exception\RuntimeException
     */
    protected function throwExceptionForUndefined($name)
    {
        if (!$this->isDefined($name)) {
            throw new Exception\RuntimeException("未定義的物件成員: $name");
        }
    }

    /**
     * 傳回迭代器
     *
     * @return ArrayIterator
     */
    public function getIterator() 
    { 
        return new ArrayIterator($this->toArray());
    }
    
    /**
     * 計算數量
     *  
     * @return intger
     */
    public function count() 
    { 
        return count($this->toArray());
    }

    /**
     * 序列化
     *
     * @return string
     */
    public function serialize() 
    {
        return serialize($this->toArray());
    }

    /**
     * 反序列化
     *
     * @return void
     */
    public function unserialize($data) 
    {
        $data = unserialize($data);
        if (is_array($data)) {
            $this->import($data);
        }
    }

    /**
     * __get
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $this->throwExceptionForUndefined($name);
        $getter = 'get' . ucfirst($name);
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return $this->$name;
    }
    
    /**
     * __set
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value) 
    {
        $this->throwExceptionForUndefined($name);
        $setter = 'set' . ucfirst($name);
        if (method_exists($this, $setter)) {
            return $this->$setter($value);
        }
        $this->$name = $value;
        return;
    }
    
    /**
     * __isset
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name) 
    {
        return $this->isDefined($name);
    }
    
    /**
     * __unset
     *
     * @param string $name
     * @return void
     */
    public function __unset($name) 
    {
        if ($this->isDefined($name)) {
            unset($this->$name);
        }
    }
}

?>