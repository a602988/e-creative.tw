<?php

/**
 * 檔案快取類別
 * 
 * @version 2.2.5 2014/08/06 11:52
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Cache\Storage;

use GlobIterator;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\System\Server;
use Sewii\Filesystem\Directory;
use Sewii\Filesystem\File;
use Sewii\Filesystem\Glob;
use Sewii\Cache\Cache;
use Zend\Cache\StorageFactory;

class Filesystem extends Cache
{
    /**
     * 基礎命名空間
     *
     * @const string
     */
    const NAMESPACE_BASE = 'serializable';

    /**
     * 檔案副檔名
     *
     * @var string
     */
    const FILE_EXT = 'dat';

    /**
     * 快取目錄位置
     *
     * @var string
     */
    protected static $storePath;
    
    /**
     * 快取工作路徑
     *
     * @var string
     */
    protected $workspace;

    /**
     * 序列化儲存
     *
     * @var boolean
     */
    protected $serializable;
    
    /**
     * 建構子
     * 
     * @param string $namespace
     */
    public function __construct($namespace = null, $serializable = false)
    {
        $this->setNamespace($namespace);
        $this->setSerializable($serializable);

        // Workspace
        $this->workspace = self::getStorePath() . '/' . self::NAMESPACE_BASE . '/' . $this->getNamespace();
        if (!File::isDir($this->workspace) && !Directory::create($this->workspace, 0777, true) ) {
            throw new Exception\RuntimeException("快取工作路徑初始失敗: {$this->workspace}");
        }
    }
    
    /**
     * 傳回是否包含快取
     *
     * {@inheritDoc}
     */
    public function has($name) 
    {
        return $this->file($name)->isExists();
    }

    /**
     * 傳回快取
     *
     * {@inheritDoc}
     */
    public function &get($name = null) 
    {
        static $item;
        $item = null;
        
        if (is_null($name)) {
            $item = array();
            foreach ($this->scan() as $file) {
                $key = $file->getFilename();
                $val = File::read($file->getLocalPath());
                $val = $this->unserialize($val);
                $item[$key] = $val;
            }
        }

        else if ($this->has($name)) {
            $file = $this->file($name);
            $item = $file->read();
            $item = $this->unserialize($item);
        }

        return $item;
    }
    
    /**
     * 設定快取
     *
     * {@inheritDoc}
     */
    public function set($name, $value) 
    {
        $file = $this->file($name);
        $value = $this->serialize($value);
        // return $file->write($value, LOCK_EX | LOCK_NB) !== false;
        return $file->write($value, LOCK_EX) !== false;
    }
    
    /**
     * 刪除快取
     *
     * {@inheritDoc}
     */
    public function delete($name) 
    {
        if ($this->has($name)) {
            return (bool) $this->file($name)->delete();
        }
        return false;
    }
    
    /**
     * 清空快取
     *
     * @return boolean
     */
    public function destroy() 
    {
        $successful = true;
        foreach ($this->scan() as $file) {
            $path = $file->getLocalPath();
            if (!File::delete($path)) {
                $successful = false;
            }
        }
        return $successful;
    }
    
    /**
     * 傳回存放路徑
     *
     * @return string
     */
    public static function getStorePath()
    {
        return self::$storePath ?: Server::getTemporaryPath();
    }
    
    /**
     * 設定存放路徑
     *
     * @param string $path
     * @return void
     */
    public static function setStorePath($path)
    {
        if (!File::isDir($path)) {
            throw new Exception\InvalidArgumentException("快取儲存路徑無效: {$path}");
        }
        self::$storePath = $path;
    }
    
    /**
     * 傳回序列化儲存狀態
     *
     * @return boolean
     */
    public function getSerializable()
    {
        return $this->serializable;
    }
    
    /**
     * 設定序列化儲存
     *
     * @param boolean $value
     * @return void
     */
    public function setSerializable($value)
    {
        $this->serializable = (bool) $value;
    }

    /**
     * 序列化方法
     * 
     * @param mixed $data
     * @return string
     */
    protected function serialize($data)
    {
        if (!$this->serializable) return $data;
        return serialize($data);
    }

    /**
     * 反序列化方法
     * 
     * @todo 如果無法解碼會有 notice，暫時先用 @ 抑制
     * @param string $data
     * @return mixed
     */
    protected function unserialize($data)
    {
        if (!$this->serializable) return $data;
        return @unserialize($data);
    }
    
    /**
     * 檔案物件
     *
     * @return File
     */
    protected function file($name)
    {
        $path = "{$this->workspace}/{$name}." . self::FILE_EXT;
        return new File($path);
    }

    /**
     * 掃描物件
     *
     * @param string $pattern
     * @return Glob
     */
    protected function scan($pattern = null)
    {
        $pattern = $pattern ?: '/*.' . self::FILE_EXT;
        return new Glob($this->workspace . $pattern);
    }
}

?>