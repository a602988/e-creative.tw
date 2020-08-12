<?php

/**
 * 註冊表快取類別
 * 
 * @version 1.1.0 2013/05/25 03:51
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Sewii\Cache\Storage;

use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Cache\Cache;
use Sewii\Session\Session as SessionClass;

class Session extends Cache
{
    /**
     * 原型物件
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
        $this->setNamespace($namespace);
        $this->prototype = SessionClass::container(self::NAMESPACE_BASE);
    }

    /**
     * 傳回是否包含快取
     *
     * {@inheritDoc}
     */
    public function has($name) 
    {
        $name = $this->name($name);
        return $this->prototype->has($name);
    }

    /**
     * 傳回快取
     *
     * {@inheritDoc}
     */
    public function &get($name = null) 
    {
        if (is_null($name)) {
            return $this->prototype->get();
        }
        $name = $this->name($name);
        return $this->prototype->get($name);
    }
    
    /**
     * 設定快取
     *
     * {@inheritDoc}
     */
    public function set($name, $value) 
    {
        $key = $name;
        $name = $this->name($name);
        $this->prototype->set($name, $value);
        return $this->has($key);
    }
    
    /**
     * 刪除快取
     *
     * {@inheritDoc}
     */
    public function delete($name) 
    {
        $key = $name;
        $name = $this->name($name);
        $this->prototype->delete($name);
        return !$this->has($key);
    }
    
    /**
     * 清空快取
     *
     * {@inheritDoc}
     */
    public function destroy() 
    {
        $this->prototype->destroy();
        return true;
    }
}

?>