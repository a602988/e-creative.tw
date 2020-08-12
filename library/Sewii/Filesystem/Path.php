<?php

/**
 * 路徑類別
 * 
 * @version 1.3.1 2013/06/13 14:13
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Filesystem;

use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Text\Strings;
use Sewii\System\Server;

class Path
{
    /**
     * 自動偵測編碼的優先順序
     * 
     * @const string
     */
    const ENCODING_ORDER = 'ASCII, UTF-8, BIG-5, EUC-CN';

    /**
     * 原始編碼
     * 
     * @const string
     */
    const ENCODING_INITIAL = 'UTF-8';

    /**
     * 系統編碼
     * 
     * @const string
     */
    const ENCODING_LOCAL = 'BIG-5';
    
    /**
     * 組合路徑
     *
     * @param string $path1 [, string $... ]
     * @return string
     */
    public static function build()
    {
        $paths = func_get_args();
        foreach ($paths as $index => &$path) {
            if (!$path) {
                unset($paths[$index]);
                continue;
            }
        }
        $path = implode('/', $paths);
        $path = self::fix($path);
        return $path;
    }

    /**
     * 修正路徑格式
     *
     * @param string $path
     * @return string
     */
    public static function fix($path)
    {
        $path = str_replace('\\', '/', $path);
        $path = Regex::replace('#/{2,}#', '/', $path);
        $path = Regex::replace('#(\w+)/$#', '$1', $path);
        return $path;
    }
    
    /**
     * 傳回相對路徑位置
     * 
     * @param string $current
     * @param string $target
     * @return string
     */
    public static function getRelativePath($current, $target = '')
    {
        for ($parts = explode('/', trim($current, '/')),
             $loop = count($parts), 
             $relativePath = '';
             $loop >= 0; 
             $loop--) 
        {
            $path = implode('/', array_slice($parts, 0, $loop));
            if ($loop === 1) $path .= '/';
            if (Regex::match('#^' . Regex::quote($path, '#') . '#', $target)) {
                $relativePath .= Regex::replace('#^' . Regex::quote($path, '#') . '/?#', '', $target);
                break;
            }
            $relativePath .= '../';
        }
        return $relativePath;
    }

    /**
     * 將檔案名稱編碼為特殊格式
     * 
     * 格式為 _(底線) + a(小寫) + [16進位](大寫)
     * 
     * @param string $path
     * @return string
     */
    public static function encode($path) 
    {
        return Regex::replace('/[^[:print:]]/', function($matches) {
            return "_a" . strtoupper(dechex(ord($matches[0])));
        }, addslashes($path));
    }

    /**
     * 將檔案名稱從特殊格式解碼
     * 
     * @param string $path
     * @return string
     */
    public static function decode($path) 
    {
        return Regex::replace('/_a([A-F0-9]{2})/', function($matches) {
            return chr(hexdec($matches[1]));
        }, $path);
    }

    /**
     * 將路徑轉換成原始編碼
     * 
     * @param string $path
     * @param boolean $ignoreOs
     * @return string
     */
    public static function toInitial($path, $ignoreOs = false)
    {
        if (self::isNeedToConvert($path, $ignoreOs)) {
            self::setDetectOrderList();
            $encoding = mb_detect_encoding($path);

            //若非原始編碼進行轉換
            if ($encoding && !Strings::isEqual($encoding, self::ENCODING_INITIAL)) {
                $path = mb_convert_encoding($path, self::ENCODING_INITIAL, $encoding);
            }
        }
        return $path;
    }

    /**
     * 將路徑轉換成系統編碼
     * 
     * @param string $path
     * @param boolean $ignoreOs
     * @return string
     */
    public static function toLocal($path, $ignoreOs = false)
    {
        if (self::isNeedToConvert($path, $ignoreOs)) {
            self::setDetectOrderList();
            $encoding = mb_detect_encoding($path);
                    
            //若為原始編碼進行轉換
            if ($encoding && !Strings::isEqual($encoding, self::ENCODING_LOCAL)) {
                $path = mb_convert_encoding($path, self::ENCODING_LOCAL, $encoding);
            }
        }
        return $path;
    }
    
    /**
     * 傳回是否需要轉換路徑編碼
     * 
     * @param string $path
     * @param boolean $ignoreOs
     * @return boolean
     */
    protected static function isNeedToConvert($path, $ignoreOs)
    {
        $isCurrentOs = !$ignoreOs && Server::isWindows();
        return $isCurrentOs
           and function_exists('mb_convert_encoding')
           and Strings::hasNonAscii($path);
    }
    
    /**
     * 設定偵測編碼的優先順序
     * 
     * @return void
     */
    protected static function setDetectOrderList()
    {
        static $detectOrderList;
        if (!isset($detectOrderList)) {
            $detectOrderList = Regex::split('/\s*,\s*/', self::ENCODING_ORDER);
        };

        $current = mb_detect_order();
        if ($current !== $detectOrderList) {
            mb_detect_order($detectOrderList);
        }
    }
}

?>