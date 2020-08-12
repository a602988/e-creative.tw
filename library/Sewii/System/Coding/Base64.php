<?php

namespace Sewii\System\Coding;

/**
 * 編碼類別
 * 
 * @version v 1.1.1 2012/04/29 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Base64
{
    /**
     * 傳回是否為 base64 編碼
     *
     * @param string $content 傳參考
     * @return boolean
     *
     */
    public static function isValid(&$content)
    {
        if (!empty($content) && is_string($content) && strlen($content) % 4 == 0) {
            //$content = preg_replace('/\s/', '+', $content); //space!?
            if (preg_match('/^[a-zA-Z0-9\/\+=]+$/', $content)) {
                if (($_decoded = @base64_decode($content, true)) !== false) {
                    $content = $_decoded;
                    return true;
                }
            }
        }
        return false;
    }
}

?>