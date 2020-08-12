<?php

/**
 * 掃描器類別
 * 
 * @version 1.3.2 2014/06/04 12:18
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Filesystem;

use RecursiveIteratorIterator;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Event\Event;

class Scanner extends Directory
{
    /**
     * 僅掃描檔案
     * 
     * @const integer
     */
    const FILE_ONLY = 1;
    
    /**
     * 僅掃描目錄
     * 
     * @const integer
     */
    const DIR_ONLY = 2;
    
    /**
     * 從外面開始掃描
     * 
     * @const integer
     */
    const FROM_OUTSIDE = 4;
    
    /**
     * 從裡面開始掃描
     * 
     * @const integer
     */
    const FROM_INSIDE = 8;
    
    /**
     * 補捉子迭代拋出的異常
     * 
     * @const integer
     */
    const CATCH_CHILD = 16;
    
    /**
     * 起始目錄
     * 
     * @var string
     */
    protected $path;
    
    /**
     * 掃描模式
     * 
     * @var integer
     */
    protected $modes;
    
    /**
     * 僅掃描目錄
     * 
     * @var boolean
     */
    protected $dirOnly;
    
    /**
     * 僅掃描目錄
     * 
     * @var boolean
     */
    protected $catchChild;
    
    /**
     * 迭代器物件
     * 
     * @var integer
     */
    protected $iterator;

    /**
     * 建構子
     * 
     * {@inheritDoc}
     */
    public function __construct($path, $modes = null, $flags = null)
    {
        parent::__construct($path, $flags);
        $this->path = $path;
        $this->setModes($modes);
    }
    
    /**
     * 準備物件
     * 
     * @return void
     */
    protected function prepare()
    {
        $filters = array();
        $flags = parent::getFlags();
        if ($this->iterator instanceof FileFilter) {
            $filters = $this->iterator->getFilters();
            $flags = $this->iterator->getFlags();
        }
        
        $modes = $this->getModes();
        $this->iterator = parent::newInstance($this->path, $flags);
        $this->iterator = new RecursiveIteratorIterator(
            $this->iterator, $modes, 
            $this->catchChild 
                ? RecursiveIteratorIterator::CATCH_GET_CHILD 
                : 0
        );

        // 僅迭代目錄
        if ($this->dirOnly) {
            $this->iterator = new DirectoryFilter($this->iterator);
        }
        
        // 篩選過濾器
        $this->iterator = new FileFilter($this->iterator);
        $this->iterator->setFilters($filters);
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
        switch (strtolower($name)) {
            case 'clear':
            case 'with':
            case 'withall':
            case 'without':
            case 'withoutall':
                call_user_func_array(array($this->iterator, $name), $args);
                return $this;
        }

        throw new Exception\BadMethodCallException(
            sprintf('呼叫未定義的方法: %s::%s()', __CLASS__, $name)
        );
    }
    
    /**
     * 設定掃描模式
     * 
     * @return string
     */
    public function setModes($modes)
    {
        $default = self::FROM_OUTSIDE;
        
        if ($modes & self::DIR_ONLY) {
            $this->dirOnly = true;
        }
        
        if ($modes & self::CATCH_CHILD) {
            $this->catchChild = true;
        }
        
        if ($modes === null
            || $modes === self::DIR_ONLY
            || $modes === self::CATCH_CHILD) {
            $modes = $default;
        }

        $this->modes = null;
        if ($modes & self::FILE_ONLY)    $this->modes |= RecursiveIteratorIterator::LEAVES_ONLY;
        if ($modes & self::FROM_OUTSIDE) $this->modes |= RecursiveIteratorIterator::SELF_FIRST;
        if ($modes & self::FROM_INSIDE)  $this->modes |= RecursiveIteratorIterator::CHILD_FIRST;

        $this->prepare();
        return $this;
    }
    
    /**
     * 傳回掃描模式
     * 
     * @return string
     */
    public function getModes()
    {
        return $this->modes;
    }
    
    /**
     * count
     * 
     * {@inheritDoc}
     */
    public function count()
    {
        return iterator_count($this->iterator);
    }
    
    /**
     * toArray
     * 
     * {@inheritDoc}
     */
    public function toArray()
    {
        return iterator_to_array($this->iterator);
    }
    
    /**
     * getFlags
     *
     * {@inheritDoc}
     */
    public function getFlags() 
    {
        $this->iterator->getFlags();
    }
    
    /**
     * setFlags
     *
     * {@inheritDoc}
     */
    public function setFlags($flags = null) 
    {
        $this->iterator->setFlags($flags);
    }
    
    /**
     * rewind
     *
     * {@inheritDoc}
     */
    public function rewind() 
    {
        $this->iterator->rewind();
    }
    
    /**
     * current
     *
     * {@inheritDoc}
     */
    public function current() 
    {
        return $this->iterator->current();
    }
    
    /**
     * key
     *
     * {@inheritDoc}
     */
    public function key() 
    {
        return $this->iterator->key();
    }
    
    /**
     * next
     *
     * {@inheritDoc}
     */
    public function next() 
    {
        $this->iterator->next();
    }
    
    /**
     * seek
     *
     * {@inheritDoc}
     */
    public function seek($position) 
    {
        $this->iterator->seek($position);
    }
    
    /**
     * valid
     *
     * {@inheritDoc}
     */
    public function valid() 
    {
        return $this->iterator->valid();
    }
}

?>