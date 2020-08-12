<?php

/**
 * 資料集合閘道介面
 * 
 * @version 1.0.0 2013/12/03 15:48
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Sewii\Data\Dataset;

use Countable;
use IteratorAggregate;

interface DatasetInterface extends Countable, IteratorAggregate
{
    /**
     * 設定資料偏移量
     *
     * @param integer $value
     * @return DatasetInterface
     */
    public function offset($value);
    
    /**
     * 設定資料讀取量
     *
     * @param integer $value
     * @return DatasetInterface
     */
    public function limit($value);
    
    /**
     * 檢查資料來源是否可被接受
     *
     * @return bool
     */
    public static function isAccept($source);
}

?>