<?php

/**
 * 目錄類別
 * 
 * @version 1.5.1 2013/07/18 09:57
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Filesystem;

use RecursiveDirectoryIterator;
use ReflectionClass;
use Sewii\Exception;
use Sewii\Text\Regex;

class Directory extends RecursiveDirectoryIterator
{
    /**
     * 建構子
     * 
     * {@inheritDoc}
     */
    public function __construct($path, $flags = null)
    {
        if ($flags === null) {
            $flags = self::KEY_AS_PATHNAME 
                   | self::CURRENT_AS_FILEINFO 
                   | self::SKIP_DOTS
                   | self::UNIX_PATHS;
        }

        $path  = Path::fix($path);
        $path  = Path::toLocal($path);
        $this->setInfoClass(FileInfo::CLASS_NAME);
        parent::__construct($path, $flags);
    }
    
    /**
     * 建構實體
     *
     * @return Directory
     */
    public static function newInstance()
    {
        $args = func_get_args();
        $reflect  = new ReflectionClass(__CLASS__);
        $instance = $reflect->newInstanceArgs($args);
        return $instance;
    }
    
    /**
     * 建立目錄
     * 
     * @param string $path
     * @param integer $mod
     * @param boolean $recursive
     * @return boolean
     */
    public static function create($path, $mod = 0777, $recursive = false)
    {
        $path = Path::toLocal($path);
        return mkdir($path, $mod, $recursive);
    }
    
    /**
     * 傳回數量
     * 
     * @return array
     */
    public function count()
    {
        return iterator_count($this);
    }
    
    /**
     * 以陣列傳回
     * 
     * @return array
     */
    public function toArray()
    {
        return iterator_to_array($this);
    }
    
    /**
     * getChildren
     *
     * {@inheritDoc}
     */
    public function getChildren()
    {
        $children = parent::getChildren();
        if (is_string($children)) {
            $path = $this->getPathname();
            $flags = $this->getFlags();
            $children = new self($path, $flags);
        }
        return $children;
    }
    
    /**
     * current
     *
     * {@inheritDoc}
     */
    public function current() 
    {
        $current = parent::current();
        if (is_string($current)) {
            $current = Path::fix($current);
        }
        return $current;
    }
    
    /**
     * key
     *
     * {@inheritDoc}
     */
    public function key() 
    {
        $key = parent::key();
        return Path::fix($key);
    }
}
?>