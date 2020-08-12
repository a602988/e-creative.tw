<?php

/**
 * 快取類別
 * 
 * @version 1.1.0 2013/05/25 03:51
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Cache;

use Countable;
use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;
use ReflectionClass;
use Sewii\Exception;
use Sewii\Text\Regex;

abstract class Cache
    implements Countable, ArrayAccess, IteratorAggregate
{
    /**
     * 基礎命名空間
     *
     * @const string
     */
    const NAMESPACE_BASE = 'cacheable';
    
    /**
     * 預設命名空間名稱
     * 
     * @const string
     */
    const NAMESPACE_DEFAULT = 'default';
    
    /**
     * 命名空間符號
     *
     * @const string
     */
    const NAMESPACE_SEPARATOR = '-';
    
    /**
     * 目前命名空間
     *
     * @const string
     */
    protected $namespace;
    
    /**
     * 工廠方法
     *
     * @param string $name 
     * @param array $args
     * @return mixed
     * @throw Sewii\Exception\InvalidArgumentException
     */
    public static function __callStatic($name, $args)
    {
        $adapter = __NAMESPACE__ . '\\Storage\\' . ucfirst($name);
        if (class_exists($adapter)) {
            $reflect = new ReflectionClass($adapter);
            return $reflect->newInstanceArgs($args);
        }

        throw new Exception\BadMethodCallException(
            sprintf('呼叫未定義的方法: %s::%s()', __CLASS__, $name)
        );
    }
    
    /**
     * 設定命名空間
     *
     * @param string $namespace
     * @return Cache
     */
    public function setNamespace($namespace)
    {
        $this->namespace = $namespace ?: self::NAMESPACE_DEFAULT;
        return $this;
    }
    
    /**
     * 傳回命名空間
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }
    
    /**
     * 傳回快取名稱
     *
     * @param string $name
     * @return string
     */
    protected function name($name)
    {
        if ($this->getNamespace()) {
            return $this->getNamespace()
                 . self::NAMESPACE_SEPARATOR
                 . $name;
        }
        return $name;
    }
    
    /**
     * 取值子
     *
     * @param string $name
     * @return mixed
     */
    public function &__get($name)
    {
        return $this->get($name);
    }
    
    /**
     * 設定子
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value) 
    {
        $this->set($name, $value);
    }
    
    /**
     * 測試子
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name) 
    {
        return $this->has($name);
    }
    
    /**
     * 刪除子
     *
     * @param string $name
     * @return void
     */
    public function __unset($name) 
    {
        $this->delete($name);
    }

    /**
     * offsetGet
     * 
     * 此方法無法宣告為傳參考呼叫，若於 unset 多維陣列的元素時將無法刪除。
     * 例如 unset($cache['test'][0]) 時將無法刪除索引為 0 的元素，並且會得到錯誤訊息。
     * @link http://stackoverflow.com/questions/2881431/arrayaccess-multidimensional-unset
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
     * getIterator
     *
     * @return ArrayIterator
     */
    public function getIterator() 
    {
        $data = $this->get();
        return new ArrayIterator((array) $data);
    }
    
    /**
     * count
     *  
     * @return int
     */
    public function count() 
    { 
        $data = $this->get();
        return count($data);
    }

    /**
     * 傳回是否包含快取
     *
     * @param string $name
     * @return boolean
     */
    abstract public function has($name);

    /**
     * 傳回快取
     *
     * @param string $name
     * @return mixed
     */
    abstract public function get($name = null);
    
    /**
     * 設定快取
     *
     * @param string $name
     * @param mixed $value
     * @return boolean
     */
    abstract public function set($name, $value);
    
    /**
     * 刪除快取
     *
     * @param string $name
     * @return boolean
     */
    abstract public function delete($name);
    
    /**
     * 清空快取
     *
     * @param string $name
     * @return boolean
     */
    abstract public function destroy();
}

?>