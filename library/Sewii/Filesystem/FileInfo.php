<?php

/**
 * 檔案資訊類別
 * 
 * @version 1.0.12 2014/06/05 12:02
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Filesystem;

use SplFileInfo;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\System\Server;

class FileInfo extends SplFileInfo
{
    /**
     * 類別名稱
     * 
     * @const string
     */
    const CLASS_NAME = __CLASS__;

    /**
     * 建構子
     * 
     * {@inheritDoc}
     */
    public function __construct($path)
    {
        $path = Path::fix($path);
        $path = Path::toLocal($path);
        $this->setInfoClass(__CLASS__);
        $this->setFileClass(FileStream::CLASS_NAME);
        parent::__construct($path);
    }
    
    /**
     * 字串子
     * 
     * @return string
     */
    public function __toString()
    {
        $path = parent::getPathname();
        $path = Path::toInitial($path);
        return $path;
    }
    
    /**
     * 傳回群組
     * 
     * @param boolean $toReadable
     * @return int|string
     */
    public function getGroup($toReadable = false)
    {
        $group = parent::getGroup();
        if ($toReadable && function_exists('posix_getpwuid')) {
            if ($info = posix_getgrgid($group)) {
                if (isset($info['name'])) {
                    $group = $info['name'];
                }
            }
        }
        return $group;
    }
    
    /**
     * 傳回擁有者
     * 
     * @param boolean $toReadable
     * @return int|string
     */
    public function getOwner($toReadable = false)
    {
        $owner = parent::getOwner();
        if ($toReadable && function_exists('posix_getpwuid')) {
            if ($info = posix_getgrgid($owner)) {
                if (isset($info['name'])) {
                    $owner = $info['name'];
                }
            }
        }
        return $owner;
    }
    
    /**
     * 傳回檔案權限
     * 
     * @param boolean $toReadable
     * @return int
     */
    public function getPerms($toReadable = false)
    {
        $perms = parent::getPerms();
        if ($toReadable) {
            $perms = decoct($perms) % 1000;
        }
        return $perms;
    }
    
    /**
     * 傳回大小
     * 
     * @param boolean $toReadable
     * @return int|string
     */
    public function getSize($toReadable = false)
    {
        $size = parent::getSize();
        if ($toReadable) {
            $toUnit = function ($bytes, $precision = 1) { 
                $units  = array('B', 'KB', 'MB', 'GB', 'TB'); 
                $bytes  = max($bytes, 0); 
                $pow    = floor(($bytes ? log($bytes) : 0) / log(1024)); 
                $pow    = min($pow, count($units) - 1); 
                $bytes /= pow(1024, $pow);
                return round($bytes, $precision) . ' ' . $units[$pow]; 
            };
            $size = $toUnit($size);
        }
        return $size;
    }
    
    /**
     * 傳回最後存取時間
     * 
     * @param boolean $toReadable
     * @return int|string
     */
    public function getATime($toReadable = false)
    {
        $time = parent::getATime();
        if ($toReadable) {
            $time = date($toReadable, $time);
        }
        return $time;
    }
    
    /**
     * 傳回最後更改時間
     * 
     * @param boolean $toReadable
     * @return int|string
     */
    public function getCTime($toReadable = false)
    {
        $time = parent::getCTime();
        if ($toReadable) {
            $time = date($toReadable, $time);
        }
        return $time;
    }
    
    /**
     * 傳回最後修改時間
     * 
     * @param boolean $toReadable
     * @return int|string
     */
    public function getMTime($toReadable = false)
    {
        $time = parent::getMTime();
        if ($toReadable) {
            $time = date($toReadable, $time);
        }
        return $time;
    }
    
    /**
     * 傳回使用作業系統編碼的路徑
     * 
     * @return string
     */
    public function getLocalPath()
    {
        return parent::getPathname();
    }
    
    /**
     * 傳回檔案路徑
     * 
     * {@inheritDoc}
     */
    public function getPathname()
    {
        $method = __FUNCTION__;
        $value = parent::$method();
        $value = Path::toInitial($value);
        return $value;
    }
    
    /**
     * 傳回檔案實體路徑
     * 
     * {@inheritDoc}
     */
    public function getRealPath()
    {
        $method = __FUNCTION__;
        $value = parent::$method();
        $value = Path::toInitial($value);
        return $value;
    }
    
    /**
     * 傳回不包含檔名的路徑
     * 
     * {@inheritDoc}
     */
    public function getPath()
    {
        $method = __FUNCTION__;
        $value = parent::$method();
        $value = Path::toInitial($value);
        return $value;
    }
    
    /**
     * 傳回不包含路徑的檔名
     * 
     * {@inheritDoc}
     */
    public function getBasename($suffix = null)
    {
        $value = $this->getPathname();
        $split = Regex::split('/\\\\|\//', $value);
        $value = end($split);
        return $value;
    }
    
    /**
     * 傳回不包含副檔名的檔名
     * 
     * {@inheritDoc}
     */
    public function getFilename()
    {
        $value = $this->getBasename();
        $value = Regex::replace('/\.\w+$/i', '', $value);
        return $value;
    }
    
    /**
     * 傳回檔案的副檔名
     * 
     * {@inheritDoc}
     */
    public function getExtension()
    {
        $method = __FUNCTION__;
        $value = parent::$method();
        $value = Path::toInitial($value);
        return $value;
    }
    
    /**
     * 傳回檔案是否存在
     * 
     * @return boolean
     */
    public function isExists() 
    {
        $path = parent::getPathname();
        return file_exists($path);
    }
    
    /**
     * 傳回檔案或目錄是否為空
     * 
     * @return boolean|integer
     */
    public function isEmpty() 
    {
        if ($this->isExists()) 
        {
            //Directory
            $path = parent::getPathname();
            if ($this->isDir()) {
                $directory = new Directory($path);
                return !(iterator_count($directory) > 0);
            }
            //File
            else if ($this->isFile()) {
                $site = File::getSize($path);
                return !($site > 0);
            }
            return false;
        }
        return -1;
    }

    /**
     * 傳回檔案是否符合副檔名
     * 
     * @param string $exts
     * @return string
     */
    public function isExtension($exts = '\w+')
    {
        $path = $this->getPathname();
        return Regex::isMatch('/\.(' . $exts . ')$/i', $path);
    }
    
    /**
     * 傳回檔案是否為圖片
     *
     * @param string $exts
     * @return boolean
     */
    public function isImage($exts = 'gif|jpe?g|png|bmp') 
    {
        return self::isExtension($path, $exts);
    }
    
    /**
     * 傳回檔案是否為 Flash
     *
     * @param string $exts
     * @return boolean
     */
    public function isFlash($exts = 'swf|flv') 
    {
        return self::isExtension($path, $exts);
    }
}

?>