<?php

/**
 * 檔案串流類別
 * 
 * @version 1.0.1 2013/05/07 00:00
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Filesystem;

use SplFileObject;
use Sewii\Exception;

class FileStream extends SplFileObject
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
        $this->setInfoClass(FileInfo::CLASS_NAME);
        $this->setFileClass(__CLASS__);
        parent::__construct($path);
    }
}

?>