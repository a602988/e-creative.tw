<?php

namespace Sewii\Http;

/**
 * Cookie 類別
 * 
 * @version v 1.0.1 2012/03/23 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Cookie
{
    /**
     * 設定 cookie
     * 
     * 這個方法將會在沒有傳入 path 參數時
     * 直接使用網站根目錄做為路徑
     * 
     * 與原生方法沒有傳入 path 參數時自動使用目前目錄有所不同
     * 
     * @see http://php.net/manual/en/function.setcookie.php
     * @return bool
     *
     */
    public static function setting()
    {
        $args = func_get_args();
        if (!isset($args[2])) $args[2] = 0;
        if (!isset($args[3])) {
            $base = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
            $base .= preg_match('/\/$/', $base) ? '' : '/';
            $args[3] = $base;
        }
        return call_user_func_array('setcookie', $args);
    }
}

?>