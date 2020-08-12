<?php

use Sewii\Data;
use Sewii\Util;

/**
 * 文章模組抽像類別
 * 
 * @version v 1.0.1 2011/11/11 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
abstract class MOD_Article extends COM_Model_Database
{
    /**
     * 資料表
     * 
     * @var string
     */
    public $table = 'sp_archives';

    /**
     * 自動下架過期文章
     *
     * @return Database|false
     */
    public function disableExpired($whereExp = null, $withFields = null)
    {
        if (is_null($whereExp)) $whereExp = "class = '{$this->class}'";
        if (is_null($withFields)) $withFields = array('!expireDate' => 'NULL');
        return $this->disableExpiredRows($whereExp, $withFields);
    }

    /**
     * 準備重新排序範圍條件語句 (callback)
     *
     * @return string
     */    
    protected function _prepareOrderWhereExp($row =  null)
    {
        return "class = '{$this->class}'";
    }

    /**
     * 傳回 class 名稱
     *
     * @return string|null
     *
     */
    protected function _getClass()
    {
        //抓取子類別名稱
        $output = print_r($this, true);
        if (preg_match('/^\w+/', $output, $matches)) {
            $className = $matches[0];

            //抓取子類別名稱主要命名
            $className = Data\Arrays::getLast(explode('_', $className));
            $className = strtolower($className);
            return $className;
        }
        return null;
    }
    
    /**
     * 存取子
     * getter
     *
     * @param string $member
     * @return mixed
     *
     */
    public function __get($member) 
    {
        switch ($member) {
            case 'class':
                return $this->_getClass();
        }
        return null;
    }
}

?>