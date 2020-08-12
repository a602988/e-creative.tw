<?php

/**
 * 資料實體抽象類別
 * 
 * @version 1.0.1 2013/12/04 15:33
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Model;

use ArrayIterator;
use Sewii\Data\Database\EntityInterface;

abstract class AbstractEntity
    implements EntityInterface
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
            if ($this->isExists($name)) {
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
        $fields = $this->toArray();
        return array_filter($fields, function($var) {
            return isset($var);
        });
        return $fields;
    }

    /**
     * 傳回陣列
     *
     * @return array
     */
    public function toArray()
    {
        return (array) get_object_vars($this);
    }
    
    /**
     * 傳回是否已定義
     *
     * @param object $class
     * @param string $name
     * @return boolean
     */
    public function isExists($name)
    {
        return property_exists($this, $name);
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
     * offsetGet
     * 
     * 此方法無法宣告為傳參考呼叫，若於 unset 多維陣列的元素時將無法刪除。
     * @link https://bugs.php.net/bug.php?id=32983
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset) 
    {
        return $this->__get($offset);
    }

    /**
     * offsetSet
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) 
    {
        $this->__set($offset, $value);
    }

    /**
     * offsetExists
     *
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset) 
    {
        return $this->__isset($offset);
    }

    /**
     * offsetUnset
     *
     * @param mixed $offset 
     * @return void
     */
    public function offsetUnset($offset) 
    {
        $this->__unset($offset);
    }

    /**
     * __get
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (!$this->isExists($name)) {
            return null;
        }
        
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
        return isset($this->$name);
    }
    
    /**
     * __unset
     *
     * @param string $name
     * @return void
     */
    public function __unset($name) 
    {
        if (isset($this->$name)) {
            unset($this->$name);
        }
    }
}

?>