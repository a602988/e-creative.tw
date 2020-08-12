<?php

/**
 * 檔案操作類別
 * 
 * @version 1.2.0 2014/06/05 15:15
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Filesystem;

use ReflectionMethod;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;

class File
{
    /**
     * 實體檔案路徑
     *
     * @var string
     */   
    protected $path = null;

    /**
     * 實體物件快取
     *
     * @var array
     */    
    protected static $cache = array();

    /**
     * 建構子
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $path = Path::fix($path);
        $path = Path::toLocal($path);
        $this->path = $path;
    }
    
    /**
     * 呼叫子
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        Arrays::insert($args, $this->path);
        $callable = array($this, "_{$name}");
        return self::__callInternal($callable, $args, $name);
    }
    
    /**
     * 靜態呼叫子
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($name, $args)
    {
        $callable = array(__CLASS__, "_{$name}");
        return self::__callInternal($callable, $args, $name);
    }
    
    /**
     * 多載內部方法
     *
     * @param array $callable
     * @param array $args
     * @param string $name
     * @return mixed
     */
    protected static function __callInternal($callable, $args, $name)
    {
        // 多載 File 內部方法
        list($class, $method) = $callable;
        if (method_exists($class, $method)) {
            $method = new ReflectionMethod($class, $method);
            if (count($args) < ($number = $method->getNumberOfRequiredParameters())) {
                throw new Exception\InvalidArgumentException(
                    sprintf('至少需要傳入 %s 個參數: %s::%s()', $number, __CLASS__, $name)
                );
            }
            return call_user_func_array($callable, $args);
        }
        
        // 多載 FileInfo 方法
        $key = md5($path = Arrays::getFirst($args));
        $info = isset(self::$cache[$key]) ? self::$cache[$key] : File::info($path);
        self::$cache[$key] = $info;
        
        if (method_exists($info, $name)) {
            if (!$path) throw new Exception\InvalidArgumentException(
                sprintf('必須指定路徑參數: %s::%s()', __CLASS__, $name)
            );
            
            unset($args[0]);
            $callable = array($info, $name);
            return call_user_func_array($callable, $args);
        }

        // 未定義呼叫
        throw new Exception\BadMethodCallException(
            sprintf('呼叫未定義的方法: %s::%s()', __CLASS__, $name)
        );
    }
    
    /**
     * 複製檔案或目錄
     *
     * @param string $src
     * @param string $dest
     * @param boolean $overwhelm
     * @return boolean
     * @throws Exception\InvalidArgumentException
     */
    protected static function _copy($src, $dest, $overwhelm = false)
    {
        // 原生方法
        $copy = function ($src, $dest) {
            $src = Path::toLocal($src);
            $dest = Path::toLocal($dest);
            return copy($src, $dest);
        };
        
        // 複製目錄
        if (self::isDir($src)) {
            if (!self::isDir($dest)) {
                throw new Exception\InvalidArgumentException("目的目錄不存在: $dest");
            }

            // 新建目錄
            $dest = Path::build($dest, self::getBasename($src));
            if (!self::isExists($dest) && !Directory::create($dest, 0755, true)) return false;
            
            $directory = new Directory($src);
            foreach ($directory as $file) {
                $item = $file->getLocalPath();

                // 複製目錄
                if (self::isDir($item)) {
                    $self = array(__CLASS__, __FUNCTION__);
                    call_user_func($self, $item, $dest, $overwhelm);
                    continue;
                }

                // 複製檔案
                $newDest = $dest . '/' . self::getBasename($item);
                if (!$overwhelm) $newDest = self::uniqueName($newDest);
                $copy($item, $newDest);
            }
            return true;
        }

        //複製檔案
        if (self::isExists($src) && self::isExists($dest)) {
            if (self::isDir($dest)) $dest = Path::build($dest, self::getBasename($src));
            if (!$overwhelm) $dest = self::uniqueName($dest);
        }
        return $copy($src, $dest);
    }
    
    /**
     * 移動檔案或目錄
     *
     * @param string $src
     * @param string $dest
     * @param boolean $overwhelm
     * @return boolean
     */
    protected static function _move($src, $dest, $overwhelm = false)
    {
        if (self::isExists($src) && self::isExists($dest)) {
            if (self::isDir($dest)) {
                $dest = Path::build($dest, self::getBasename($src));
                if (self::isDir($dest))
                {
                    // 將來源目錄中的檔案搬移進目標內
                    if (self::isDir($src)) {
                        $directory = new Directory($src);
                        foreach ($directory as $file) {
                            $item = $file->getLocalPath();
                            $self = array(__CLASS__, __FUNCTION__);
                            if (!call_user_func($self, $item, $dest, $overwhelm)) 
                                return false;
                        }
                    }
                    return self::delete($src);
                }
            }

            // 覆寫或更名
            if (self::isExists($dest)) {
                if ($overwhelm) self::delete($dest);
                else $dest = self::uniqueName($dest);
            }
        }
        return self::rename($src, $dest);
    }
    
    /**
     * 刪除檔案或目錄
     * 
     * @param string $path
     * @return boolean
     */
    protected static function _delete($path)
    {
        $path = Path::toLocal($path);
        if (File::isDir($path)) {
            $directory = new Directory($path);
            foreach ($directory as $file) {
                $item = $file->getLocalPath();
                if ($file->isDir()) {
                    $self = array(__CLASS__, __FUNCTION__);
                    call_user_func($self, $item);
                    continue;
                }
                if (!unlink($item)) return false;
            }
            return rmdir($path);
        }
        return unlink($path);
    }
    
    /**
     * 更名檔案
     *
     * @param string $src
     * @param string $dest
     * @return boolean
     */
    protected static function _rename($src, $dest)
    {
        $src = Path::toLocal($src);
        $dest = Path::toLocal($dest);
        return rename($src, $dest);
    }
    
    /**
     * 讀取檔案內容
     *
     * @param string $path
     * @return string|boolean
     */
    protected static function _read($path)
    {
        $path = Path::toLocal($path);
        return file_get_contents($path);
    }
    
    /**
     * 寫入檔案內容
     *
     * @param string $path
     * @param mixed $data
     * @param int $flags FILE_USE_INCLUDE_PATH | FILE_APPEND | LOCK_EX | LOCK_NB
     * @return int|boolean
     */
    protected static function _write($path, $data = null, $flags = 0)
    {
        $path = Path::toLocal($path);
        
        // Non-Blocking
        if (($flags & LOCK_NB) && ($flags & LOCK_EX)) {

            // Use include path
            if ($flags & FILE_USE_INCLUDE_PATH && !self::isExists($path)) {
                $includePaths = explode(PATH_SEPARATOR, get_include_path());
                foreach ($includePaths as $includePath) {
                    if (self::isExists($search = "$includePath/$path")) {
                        $path = $search;
                        break;
                    }
                }
            }

            // Writing & Locking
            $wrote = false;
            $mode = ($flags & FILE_APPEND) ? 'ab' : 'wb';
            $stream = fopen($path, $mode);
            if (flock($stream, LOCK_EX | LOCK_NB)) {
                $wrote = fwrite($stream, $data);
                flock($stream, LOCK_UN);
            }
            fclose($stream);
            return $wrote;
        }

        return file_put_contents($path, $data, $flags);
    }
    
    /**
     * 傳回檔案資訊物件
     *
     * @param string $path
     * @return FileInfo
     */
    protected static function _info($path)
    {
        return new FileInfo($path);
    }
    
    /**
     * 傳回檔案串流物件
     *
     * @param string $path
     * @return FileStream
     */
    protected static function _stream($path)
    {
        return new FileStream($path);
    }

    /**
     * 傳回唯一的檔名
     * 
     * 如果目的檔案存在將在檔案名稱結尾加上編號傳回
     * 
     * @todo 會有 name_1.jpg 變成 name_1_1.jpg 問題??
     * @param string $dest
     * @return string
     */
    public static function uniqueName($name) 
    {
        $file = self::info($name);
        if ($file->isExists()) {
            $index = 1;
            do {
                $number    = $index++;
                $name      = $file->getPathname();
                $basename  = $file->getBasename();
                $filename  = $file->getFilename();
                $extension = $file->getExtension();
                $rename    = "{$filename}_{$number}.{$extension}";
                $newname   = str_replace($basename, $rename, $name);
            }
            while (File::isExists($newname));
            $name = $newname;
        }
        return $name;
    }
}

?>