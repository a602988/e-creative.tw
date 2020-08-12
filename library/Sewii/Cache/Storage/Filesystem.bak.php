<?php

/**
 * 檔案快取類別
 * 
 * @version 1.1.2 2013/05/25 03:51
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Cache\Storage;

use GlobIterator;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\System\Server;
use Sewii\Filesystem\File;
use Sewii\Filesystem\Glob;
use Sewii\Cache\Cache;
use Sewii\Type\Arrays;
use Zend\Cache\StorageFactory;

class Filesystem extends Cache
{
    /**
     * 快取目錄位置
     *
     * @var string
     */
    protected static $storePath;

    /**
     * 原型物件
     *
     * @var Adapter\Filesystem
     */
    protected $prototype;
    
    /**
     * 建構子
     * 
     * @todo 建構速度慢，至少超過30ms!!
     * @param string $namespace
     * @link http://framework.zend.com/manual/2.1/en/modules/zend.cache.storage.adapter.html
     */
    public function __construct($namespace = null)
    {
        $this->setNamespace($namespace);
        $this->prototype = StorageFactory::factory(array(
            'adapter' => array(
                'name' => 'filesystem',
                'options' => array (
                    'namespace' => self::NAMESPACE_BASE,
                    'namespaceSeparator' => self::NAMESPACE_SEPARATOR,
                    'cache_dir' => self::getStorePath(),
                    'dir_level' => 1
                )
            ),
            'plugins' => array(
                'exception_handler' => array('throw_exceptions' => false),
                'serializer',
            ),
        ));
    }
    
    /**
     * 傳回是否包含快取
     *
     * {@inheritDoc}
     */
    public function has($name) 
    {
        $name = $this->name($name);
        return $this->prototype->hasItem($name);
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
            $keys = array();
            $this->scan(function($file) use(&$keys) {
                $filename = $file->getFilename();
                $pattern = '/^' . Regex::quote(Cache::NAMESPACE_BASE . Cache::NAMESPACE_SEPARATOR) . '/';
                $key = Regex::replace($pattern, '', $filename);
                array_push($keys, $key);
            });

            $item = !empty($keys) 
                ? $this->prototype->getItems($keys) 
                : array();

            return $item;
        }
        
        if ($this->has($name)) {
            $name = $this->name($name);
            $item = $this->prototype->getItem($name);
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
        $name = $this->name($name);
        return $this->prototype->setItem($name, $value);
    }
    
    /**
     * 刪除快取
     *
     * {@inheritDoc}
     */
    public function delete($name) 
    {
        $name = $this->name($name);
        return $this->prototype->removeItem($name);
    }
    
    /**
     * 清空快取
     *
     * @return boolean
     */
    public function destroy() 
    {
        if ($files = $this->scan()) {
            $parentPaths = array();
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $path = $file->getLocalPath();
                    if (@File::delete($path)) {
                        $path = $file->getPath();
                        $parentPaths[$path] = $path;
                    }
                }
            }

            if ($parentPaths) {
                foreach ($parentPaths as $path) {
                    $file = new File($path);
                    if ($file->isEmpty()) {
                        @$file->delete();
                    }
                }
            }
        }
        return true;
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
     * 掃描快取
     *
     * @param mixed $callback
     * @return mixed
     */
    protected function scan($callback = null)
    {
        $options  = $this->prototype->getOptions();
        $nsPrefix = self::NAMESPACE_BASE . $options->getNamespaceSeparator();
        $namespace = $this->getNamespace();
        $path = $options->getCacheDir()
                . str_repeat(DIRECTORY_SEPARATOR . $nsPrefix . '*', $options->getDirLevel())
                . DIRECTORY_SEPARATOR . $nsPrefix . $namespace . '-*';

        $glob = new Glob($path);
        if ($callback) {
            foreach ($glob as $file) {
                if ($file->isFile()) {
                    $callback($file);
                }
            }
        }
        return iterator_to_array($glob);
    }
}

?>