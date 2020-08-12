<?php

/**
 * 註冊表快取類別
 * 
 * @version 1.1.0 2013/05/25 03:52
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Sewii\Cache\Storage;

use Sewii\Exception;
use Sewii\Cache\Cache;
use Sewii\System\Registry as RegistryClass;

class Registry extends Cache
{
    /**
     * 原型物件
     *
     * @var array
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
        if (!RegistryClass::has(self::NAMESPACE_BASE)) {
            RegistryClass::set(self::NAMESPACE_BASE, array());
        }

        $this->prototype = &RegistryClass::get(self::NAMESPACE_BASE);
    }

    /**
     * 傳回是否包含快取
     *
     * {@inheritDoc}
     */
    public function has($name) 
    {
        $name = $this->name($name);
        return array_key_exists($name, $this->prototype);
    }

    /**
     * 傳回快取
     *
     * {@inheritDoc}
     */
    public function &get($name = null) 
    {
        if (is_null($name)) {
            return $this->prototype;
        }

        static $item;
        $item = isset($this->prototype[$name]) ? $this->prototype[$name] : null;
        return $item;
    }
    
    /**
     * 設定快取
     *
     * {@inheritDoc}
     */
    public function set($name, $value) 
    {
        $name = $this->name($name);
        $this->prototype[$name] = $value;
        return true;
    }
    
    /**
     * 刪除快取
     *
     * {@inheritDoc}
     */
    public function delete($name) 
    {
        if ($this->has($name)) {
            $name = $this->name($name);
            unset($this->prototype[$name]);
            return true;
        }
        return false;
    }
    
    /**
     * 清空快取
     *
     * {@inheritDoc}
     */
    public function destroy() 
    {
        $this->prototype = array();
        return true;
    }
}

?>