<?php

/**
 * 自動載入器
 * 
 * @version v 1.0.5 2013/07/20 23:23
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */

namespace Sewii\System;

use Closure;
use Sewii\Exception;

class Autoloader
{
    /**
     * 預設副檔名集合
     * 
     * @const string
     */ 
    const EXTENSIONS = '.inc, .php';
    
    /**
     * 目錄分隔符號
     * 
     * @const string
     */ 
    const DIRECTORY_SEPARATOR = '/';
    
    /**
     * 函式庫集合
     * 
     * @var array
     */ 
    protected $libraries = array();
    
    /**
     * 建構子
     */ 
    public function __construct()
    {
        $this->setExtensions(self::EXTENSIONS);
        $this->register(array($this, '_loader'));
    }
    
    /**
     * 解析物件資訊
     *
     * @param string $name
     * @return array
     */    
    protected function _parse($name)
    {
        $info = array();
        $info['name'] = $name;
        $info['namespace'] = null;
        $info['path'] = $info['name'];

        //Namespace by _
        if (($lastSeparatorPosition = strrpos($name, '_')) !== false) {
            $info['name'] = str_replace('\\', '_', $name);
            $info['namespace'] = str_replace('_', '\\', substr($name, 0, $lastSeparatorPosition));
            $info['path'] = preg_replace('/[\\\_]/', self::DIRECTORY_SEPARATOR, $name);
        }
        //Namespace by \
        else if (($lastSeparatorPosition = strrpos($name, '\\')) !== false) {
            $info['name'] = substr($name, $lastSeparatorPosition + 1);
            $info['namespace'] = substr($name, 0, $lastSeparatorPosition);
            $info['path'] = str_replace('\\', self::DIRECTORY_SEPARATOR, $info['path']);
        }
        return $info;
    }

    /**
     * 預設載入器
     *
     * @param mixed $name
     * @return void
     */
    protected function _loader($name)
    {
        if ($this->libraries) 
        {
            $class = $this->_parse($name);
            foreach ($this->libraries as $vendor => $paths) {
                if (strpos($name, $vendor) !== 0) continue;
                foreach ($paths as $path) {
                    if (!$path) $path = $class['path'];
                    else 
                    {
                        if ($path instanceof Closure) {
                            $callback = $path;
                            $callback($class);
                            return;
                        }
                        
                        $class['path'] = preg_replace('/^\w+[\/\\\]/', '', $class['path']);
                        $path = rtrim(preg_replace('/[\/\\\]/', self::DIRECTORY_SEPARATOR, $path), self::DIRECTORY_SEPARATOR);
                        $path = $path . self::DIRECTORY_SEPARATOR . $class['path'];
                    }
                    $this->load($path);
                }
            }
        }
    }

    /**
     * 傳回是否已定義
     *
     * @params string $name
     *
     * @return boolean
     */
    public function isDeclared($name)
    {
    	return class_exists($name, false)
    	    || interface_exists($name, false)
    	    || (function_exists('trait_exists') && trait_exists($name, false));
    }
    
    /**
     * 新增 SPL 公約標準函式庫
     * 
     * @link https://wiki.php.net/rfc/splclassloader
     * @param string $vendor
     * @param mixed $path
     * @return void
     */    
    public function addSplLibrary($vendor, $path = '')
    {
        $this->libraries[$vendor] = (array) $path;
    }

    /**
     * 設定預設引用路徑
     *
     * @param string $path
     * @param boolean $append
     * @return boolean
     * @throws Sewii\Exception\InvalidArgumentException
     */
    public function setIncludePath($path, $append = true)
    {
        $realPath = realpath($path);
        if (file_exists($realPath)) {
            $includePath = $realPath;
            if ($append) {
                $oldIncludePath = get_include_path();
                $includePath = $oldIncludePath . PATH_SEPARATOR . $includePath;
            }
            return set_include_path($includePath);
        }

        throw new Exception\InvalidArgumentException(
            sprintf('Include path %s not exists', $path)
        );
    }

    /**
     * 自動載入方法
     *
     * @param mixed $path
     * @param mixed $extensions
     * @return boolean
     */    
    public function load($path, $extensions = null) 
    {
        if ($extensions === null) $extensions = $this->getExtensions();
        if (empty($extensions)) $extensions = '.php';

        $extensions = preg_split('/\s*,\s*/', $extensions);
        foreach($extensions as $extension) {
            $file = preg_match('/\.\w+$/', $path)  ? $path : $path . $extension;
            if (is_file($file)) {
                require $file;
                return true;
            }
        }
        return false;
    }

    /**
     * 手動觸發 __autoload() 
     *
     * @param mixed $name
     * @return void
     */    
    public function call($name) 
    {
        spl_autoload_call($name);
    }

    /**
     * 註冊新的 __autoload()
     *
     * @param mixed $autloader
     * @param boolean $prepend 從前面附加，以取得高優先順序。
     * @return boolean
     */    
    public function register($autloader, $prepend = false) 
    {
        return spl_autoload_register($autloader, $throw = true, $prepend);
    }

    /**
     * 取消已註冊的 __autoload()
     *
     * @param mixed $autloader
     * @return boolean
     *
     */
    public function unregister($autloader) 
    {
        return spl_autoload_unregister($autloader);
    }

    /**
     * 傳回所有已註冊的 __autoload()
     *
     * @return array
     */
    public function getRegistered()
    {
        return spl_autoload_functions();
    }

    /**
     * 傳回副檔名集合
     *
     * @return string
     */
    public function getExtensions()
    {
        return spl_autoload_extensions();
    }

    /**
     * 設定副檔名集合
     *
     * @param string $extensions
     * @return void
     */    
    public function setExtensions($extensions)
    {
        $extensions = str_replace(' ', '', $extensions);
        spl_autoload_extensions($extensions);
    }
}

?>