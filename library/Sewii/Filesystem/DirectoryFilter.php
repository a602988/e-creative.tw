<?php

/**
 * 目錄過濾器
 * 
 * @version 1.0.2 2013/05/08 00:00
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Filesystem;

use FilterIterator;
use Sewii\Exception;
use Sewii\Text\Regex;

class DirectoryFilter extends FilterIterator
{
    /**
     * 接受方法
     *
     * {@inheritDoc}
     */
    public function accept()
    {
        $current = $this->current();
        if (is_string($current)) {
            $current = new FileInfo($current);
        }
        return $this->isDir();
    }
}

?>