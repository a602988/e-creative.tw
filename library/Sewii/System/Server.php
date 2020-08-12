<?php

/**
 * 伺服器類別
 * 
 * @version 1.2.2 2013/01/30 11:40
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */

namespace Sewii\System;

use Sewii\Text\Strings;
use Sewii\Filesystem\Path;

class Server
{
    /**
     * 伺服器是否為 Windows 系統
     * 
     * @return boolean
     */
    public static function isWindows()
    {
        return Strings::contains('WIN', PHP_OS) ? true : false;
    }

    /**
     * 伺服器是否為 64-Bit 系統
     *
     * @return boolean
     *
     */
    public static function is64Bit() 
    {
        $max64 = 9223372036854775807;
        return  (intval($max64) == $max64) ? true : false; 
    }

    /**
     * 伺服器是否為 32-Bit 系統
     *
     * @return boolean
     *
     */
    public static function is32Bit() 
    {
        return !self::is64Bit();
    }

    /**
     * 傳回暫存目錄
     *
     * @return string
     */
    public static function getTemporaryPath()
    {
        $path = getenv('TMP') ?: getenv('TMP');
        $path = Path::fix($path);
        return $path;
    }
}

?>