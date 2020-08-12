<?php

/**
 * Session 容器
 * 
 * @version 1.6.0 2013/05/04 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */

namespace Sewii\Session;

use Countable;
use ArrayAccess;
use IteratorAggregate;
use ArrayIterator;
use Sewii\Exception;
use Sewii\Text\Regex;

class Container 
    implements Countable, ArrayAccess, IteratorAggregate
{
    /**
     * 基礎命名空間
     * 
     * @const string
     */
    const NAMESPACE_BASE = '__SW';
    
    /**
     * 計時器命名空間名稱
     * 
     * @const string
     */
    const NAMESPACE_TIMEOUT = '__TT';
    
    /**
     * 預設命名空間名稱
     * 
     * @const string
     */
    const NAMESPACE_DEFAULT = 'default';
    
    /**
     * 表示作用中的命名空間初值
     * 
     * @var mixed
     */
    protected $initialValue = null;

    /**
     * 表示作用中的命名空間名稱
     * 
     * @var string
     */
    protected $namespace;

    /**
     * 表示目前會話實體
     * 
     * @var Session
     */
    protected $prototype;

    /**
     * 建構子
     * 
     * @param string $namespace
     */
    public function __construct($namespace = null) 
    {
        $this->prototype = Session::getInstance();
        $this->namespace = $namespace ?: self::NAMESPACE_DEFAULT;
        
        if (!isset($_SESSION[self::NAMESPACE_BASE])) {
            $_SESSION[self::NAMESPACE_BASE] = array();
        }

        if (!isset($_SESSION[self::NAMESPACE_BASE][$this->namespace])) {
            $_SESSION[self::NAMESPACE_BASE][$this->namespace] = $this->initialValue;
        }

        if (!isset($_SESSION[self::NAMESPACE_BASE][self::NAMESPACE_TIMEOUT])) {
            $_SESSION[self::NAMESPACE_BASE][self::NAMESPACE_TIMEOUT] = array();
        }
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
     * 傳回目前實體
     *
     * @return Session
     */
    public function getPrototype()
    {
        return $this->prototype;
    }

    /**
     * 傳回內容
     * 
     * @param string $name
     * @return mixed
     * @throws Exception\InvalidArgumentException
     */
    public function &get($name = null) 
    {
        $this->procressTimeout();

        $contents = &$_SESSION[self::NAMESPACE_BASE][$this->namespace];
        if (is_null($name)) return $contents;
        
        static $content;
        $content = isset($contents[$name]) ? $contents[$name] : null;
        return $content;
    }
    
    /**
     * 傳回內容是否存在
     * 
     * @param string $name
     * @return boolean
     */
    public function has($name) 
    {
        $contents = $this->get();
        return array_key_exists($name, $contents);
    }
    
    /**
     * 設定內容
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set($name, $value) 
    {
        $contents = &$this->get();
        $contents[$name] = $value;
    }

    /**
     * 刪除內容
     * 
     * @param string $name
     * @return void
     */
    public function delete($name)
    {
        $contents = &$this->get();
        unset($contents[$name]);
        $this->clearTimeout($name);
    }
    
    /**
     * 清空內容
     * 
     * @return void
     */
    public function clear() 
    {
        $contents = &$this->get();
        $contents = $this->initialValue;
        $this->clearTimeout();
    }

    /**
     * 銷毀內容
     * 
     * @return void
     */
    public function destroy()
    {
        unset($_SESSION[self::NAMESPACE_BASE][$this->namespace]);
        unset($_SESSION[self::NAMESPACE_BASE][self::NAMESPACE_TIMEOUT][$this->namespace]);
    }

    /**
     * 設定計時器
     *
     * @param int|array $seconds 逾時秒數，可傳入地圖陣列批次設定
     * @param string|array| $members 成員名稱，可傳入字串 (CSV) 或陣列
     * @return void
     */
    public function setTimeout($seconds, $members = null)
    {
        //傳入地圖陣列批次設定
        if (is_array($seconds)) {
            foreach ($seconds as $key => $val) {
                if ($key && $val) {
                    call_user_func(array($this, __FUNCTION__), $val, $key);
                }
            }
            return;
        }

        if (($seconds = intval($seconds)) > 0) {
            $contents = &$this->get();
            $timers   = &$_SESSION[self::NAMESPACE_BASE][self::NAMESPACE_TIMEOUT][$this->namespace];

            if (is_null($members)) {
                $timers['all'] = array('time' => time(), 'seconds' => $seconds);
            }

            else {
                if (is_string($members)) $members = Regex::split('/\s*,\s*/', $members);
                foreach (array_unique((array) $members) as $member) {
                    if (array_key_exists($member, (array) $contents)) {
                        $timers['members'][$member] = array('time' => time(), 'seconds' => $seconds);
                    }
                }
            }
        }
    }

    /**
     * 清除計時器
     *
     * @param string|array $members 成員名稱，可傳入字串 (CSV) 或陣列
     * @return void
     */
    public function clearTimeout($members = null)
    {
        $timers = &$_SESSION[self::NAMESPACE_BASE][self::NAMESPACE_TIMEOUT][$this->namespace];

        if (is_null($members)) {
            foreach ((array) $timers as $key => $timer) {
                unset($timers[$key]);
            }
        }

        else {
            if (is_string($members)) $members = Regex::split('/\s*,\s*/', $members);
            foreach (array_unique((array) $members) as $member) {
                unset($timers['members'][$member]);
            }
        }
    }

    /**
     * 處理計數器
     *
     * @return void
     */
    protected function procressTimeout()
    {
        $contents = &$_SESSION[self::NAMESPACE_BASE][$this->namespace];
        $timers = &$_SESSION[self::NAMESPACE_BASE][self::NAMESPACE_TIMEOUT][$this->namespace];

        //全部
        if (isset($timers['all'])) {
            $timeout = intval($timers['all']['time']) + intval($timers['all']['seconds']);
            if (time() >= $timeout) {
                $this->clearTimeout();
                $contents = $this->initialValue;
            }
            else $this->setTimeout($timers['all']['seconds']);
        }
        
        //成員
        if (isset($timers['members'])) {
            foreach ((array) $timers['members'] as $member => $timer) {
                $timeout = intval($timer['time']) + intval($timer['seconds']);
                if (time() >= $timeout) {
                    $this->clearTimeout($member);
                    unset($contents[$member]);
                }
                else $this->setTimeout($timer['seconds'], $member);
            }
        }
    }

    /**
     * __get
     *
     * @param string $name
     * @return mixed
     */
    public function &__get($name)
    {
        return $this->get($name);
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
        $this->set($name, $value);
    }
    
    /**
     * __isset
     *
     * @param string $name
     * @return boolean
     */
    public function __isset($name) 
    {
        return $this->has($name);
    }
    
    /**
     * __unset
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
     * 例如 unset($session['test'][0]) 時將無法刪除索引為 0 的元素，並且會得到錯誤訊息。
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
        $contents = $this->get();
        return new ArrayIterator((array) $contents);
    }
    
    /**
     * count
     *  
     * @return int
     */
    public function count() 
    { 
        $contents = $this->get();
        return count($contents);
    }
}

?>