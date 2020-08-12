<?php

namespace Sewii\Util;

use Sewii\System;

/**
 * 處理表單輸入的類別
 * 
 * @version v 1.2.5 2012/03/21 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Form extends System\Singleton
{
    /**
     * 初始化
     * 
     * @return $this
     */
    public function init()
    {
        $this->_preinitialize();
        $this->disableMagicQuotesGpc();
        $this->trimGpc();
        return $this;
    }

    /**
     * 傳入匿名函數處理變數
     *
     * @param string $gpc 被處理的變數 (傳參考)
     * @param string $func 要執行的匿名函數
     */
    private function scanGpc(&$gpc, $func)
    {
        foreach ($gpc as $k => &$v) {
            if (is_array($v)) call_user_func(array($this, __FUNCTION__), $v, $func);
            else $gpc[$k] = call_user_func($func, $v);
        }

        //同步 $_REQUEST 超級變數
        foreach($_REQUEST as $key => &$val) {
            if (isset($gpc[$key]))
                $val = $key;
        }
    }

    /**
     * 停用 magic_quotes_gpc 功能
     * 
     * 官方已停止推薦使用，在 PHP5.30 以後
     * 
     * @see http://tw.php.net/manual/en/info.configuration.php#ini.magic-quotes-gpc
     */
    public function disableMagicQuotesGpc()
    {
        if (get_magic_quotes_gpc()) {
            foreach (array ("_GET", "_POST", "_COOKIE") as $method) {
                $this->scanGpc($GLOBALS[$method], 'stripslashes');
            }
        }
    }

    /**
     * 移除表單變數的前後空白
     */
    public function trimGpc()
    {
        foreach (array ("_GET", "_POST") as $method) {
            $this->scanGpc($GLOBALS[$method], 'trim');
        }
    }
}

?>