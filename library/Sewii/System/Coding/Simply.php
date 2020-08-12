<?php

namespace Sewii\System\Coding;

/**
 * 簡單編碼類別
 * 
 * @version v 1.1.1 2012/04/30 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Simply
{
    /**
     * 字首符號
     *
     * @const string
     *
     */
    const PREFIX_SYMBOLS = '~!@#$';

    /**
     * 編碼
     *
     * @param string $str
     * @return string
     *
     */
    public static function encode($str)
    {
        if (!empty($str) && is_string($str)) {
            $str = self::PREFIX_SYMBOLS . urlencode($str);
            $str = self::PREFIX_SYMBOLS . convert_uuencode($str);
            $str = base64_encode($str);
        }
        return $str;
    }
    
    /**
     * 解碼
     *
     * @param string $str
     * @return string
     *
     */
    public static function decode($str) 
    {
        if (!empty($str) && is_string($str)) {
            $result = $str;
            if (Base64::isValid($result) === true) {
                $pattern = '/^' . preg_quote(self::PREFIX_SYMBOLS, '/') . '/';
                $result = preg_replace($pattern, '$1', $result, -1, $count);
                if ($count >= 1) {
                    $result = convert_uudecode($result);
                    $result = preg_replace($pattern, '$1', $result, -1, $count);
                    if ($count >= 1) {
                        $str = urldecode($result);
                    }
                }
                return $str;
            }
        }
        return $str;
    }
    
    /**
     * 檢查格式
     *
     * @param string $str
     * @return boolean
     *
     */
    public static function isValid($str) 
    {
        if (Base64::isValid($str) === true) {
            $pattern = '/^' . preg_quote(self::PREFIX_SYMBOLS, '/') . '/';
            return preg_match($pattern, $str) ? true : false;
        }
    }
}

?>