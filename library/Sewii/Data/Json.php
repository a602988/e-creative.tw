<?php

/**
 * JSON 類別
 * 
 * @version v 1.1.0 2011/11/18 00:00
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Data;

class Json
{
    /**
     * 編碼
     *
     * @return string
     *
     */
    public static function encode() 
    {
        $args = func_get_args();

        if (function_exists('json_encode'))
            return call_user_func_array(array('self', 'phpEncode'), $args);

        return call_user_func_array(array('self', 'pearEncode'), $args);
    }
    
    /**
     * 解碼
     *
     * @return mixed
     *
     */ 
    public static function decode() 
    {
        $args = func_get_args();

        if (function_exists('json_decode'))
            return call_user_func_array(array('self', 'phpDecode'), $args);

        return call_user_func_array(array('self', 'pearDecode'), $args);
    }
    
    /**
     * 編碼 (by PHP)
     *
     * @see http://www.php.net/manual/en/function.json-encode.php
     * @return string
     *
     */
    public static function phpEncode() 
    {
        $args = func_get_args();
        return call_user_func_array('json_encode', $args);
    }
    
    /**
     * 解碼 (by PHP)
     *
     * @see http://www.php.net/manual/en/function.json-decode.php
     * @return mixed
     *
     */
    public static function phpDecode()
    {
        $args = func_get_args();
        return call_user_func_array('json_decode', $args);
    }
    
    /**
     * 編碼 (by Zend)
     *
     * @see http://framework.zend.com/manual/en/zend.json.html
     * @return string
     *
     */
    public static function zendEncode() 
    {
        $args = func_get_args();
        return call_user_func_array(array('Zend\\Json\\Json', 'encode'), $args);
    }
    
    /**
     * 解碼 (by Zend)
     *
     * @see http://framework.zend.com/manual/en/zend.json.html
     * @return mixed
     *
     */
    public static function zendDecode()
    {
        $args = func_get_args();
        return call_user_func_array(array('Zend\\Json\\Json', 'decode'), $args);
    }
    
    /**
     * 編碼 (by PEAR)
     *
     * @see http://pear.php.net/
     * @return string
     *
     */
    public static function pearEncode() 
    {
        require_once('PEAR/Services/JSON.php');

        $args = func_get_args();
        $JSON = new \Services_JSON;
        return call_user_func_array(array($JSON, 'encode'), $args);
    }
    
    /**
     * 解碼 (by PEAR)
     *
     * @see http://pear.php.net/
     * @return mixed
     *
     */
    public static function pearDecode() 
    {
        require_once('PEAR/Services/JSON.php');

        $args = func_get_args();
        $JSON = new \Services_JSON;
        return call_user_func_array(array($JSON, 'decode'), $args);
    }
    
    /**
     * 解析 JSON
     * 
     * 此方法使用 PEAR JSON decoder，將可以解析標準/非標準 JSON 格式。
     * 
     * @return mixed
     *
     */
    public static function parse() 
    {
        $args = func_get_args();
        return call_user_func_array(array('self', 'pearDecode'), $args);
    }

    /**
     * 傳回是否為 JSON 格式
     * 
     * @param string $string
     * @return boolean
     */
    public static function isFormat($string)
    {
        $result = null;
        if (empty($string) || !is_string($string)) return false;
        if (!preg_match('/^\[.*\]$/', trim($string)) && 
            !preg_match('/^\{.*\}$/', trim($string))) return false;

        //php
        if (function_exists('json_decode')) {
            $result = self::phpDecode($string, true);
        }
        //zend
        else {
            try {
                $result = self::zendDecode($string);
            }
            catch (Exception $ex) {}
        }

        return is_array($result) ? true : false;
    }
}

?>