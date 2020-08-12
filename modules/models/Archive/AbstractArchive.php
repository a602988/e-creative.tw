<?php

/**
 * 使用者抽像模組
 * 
 * @version v 1.0.0 2012/08/23 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
abstract class MOD_User extends COM_Model_Database
{
    /**
     * 資料表
     * 
     * @var string
     */
    public $table = 'sp_users';
}

?>