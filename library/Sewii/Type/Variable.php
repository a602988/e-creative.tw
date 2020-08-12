<?php

/**
 * 變數處理類別
 * 
 * @version 1.9.6 2013/07/04 16:45
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Type;

use Sewii\Data\Json;
use Sewii\Text\Regex;

class Variable
{
    /**
     * 表示未定義常數
     *
     * @const integer
     */
    const UNDEFINED = -3.4e38;
    
    /**
     * 傳回變數是否未定義
     *
     * @param mixed $val
     * @return boolean
     */
    public static function isUndefined($var)
    {
        return ($var === self::UNDEFINED);
    }

    /**
     * 傳回內容是否為 true 
     * 
     * 滿足以下條件
     * boolean: true, integer: 1, string: 1/true/y/yes/on
     *
     * @param mixed $val
     * @return boolean
     */
	public static function isTrue($val)
	{
        if ($val === true || $val === 1) return true;
        return Regex::IsMatch('/^(1|true|y|yes|on)$/i', $val);
	}

    /**
     * 傳回內容是否為 false
     * 
     * 滿足以下條件
     * boolean: false, integer: 0, string: 0/false/n/no/off
     *
     * @param mixed $val
     * @return boolean
     */
	public static function isFalse($val)
	{
        if ($val === false || $val === 0) return true;
        return Regex::IsMatch('/^(0|false|n|no|off)$/i', $val);
	}

    /**
     * 傳回變數是否為空值
     * 
     * 此方法與 empty() 相同，
     * 但不包含數字為 0 的變數
     *
     * @param mixed $val
     * @return boolean
     */
	public static function isBlank($val)
	{
        return empty($val) && strval($val) !== '0';
	}

    /**
     * 傳回是否為 serialize 編碼字串
     * 
     * @param string $data
     * @return string
     */
    public static function isSerialize($data)
    {
        if (!empty($data) && 
            is_string($data) &&
            self::unserialize($data) !== false) {
            return true;
        }
        return false;
    }

    /**
     * serialize 編碼
     * 
     * @deprecated
     * @param array $data
     * @param boolean $emptySerialize - 如果為 empty 時是否仍然編碼
     * @return string
     */
    public static function serialize($data, $emptySerialize = false)
    {
        if (!$emptySerialize && !$data) return $data;
        return serialize($data);
    }

    /**
     * serialize 解碼
     * 
     * @deprecated
     * @param string $data
     * @param boolean $json - 傳回 json 格式。預設為 false
     * @return mixed
     */
    public static function unserialize($data, $json = false)
    {
        $unserialize = @unserialize($data);
        if ($json) return Json::encode($unserialize);
        return $unserialize;
    }
    
    /**
     * 傳回其中一個參數
     * 
     * @param mixed $a
     * @param mixed $b
     * @return mixed
     * @see ifNotSet()
     */
    public static function ifSet($a, $b = null) 
    {
        return isset($a) ? $a : $b;
    }
    
    /**
     * 傳回其中一個參數
     * 
     * @param mixed $a
     * @param mixed $b
     * @return mixed
     * @see ifSet()
     */
    public static function ifNotSet($a, $b = null) 
    {
        return !isset($a) ? $a : $b;
    }
    
    /**
     * 傳回其中一個參數
     * 
     * @param mixed $a
     * @param mixed $b
     * @return mixed
     * @see ifNotEmpty()
     */
    public static function ifEmpty($a, $b = null) 
    {
        return empty($a) ? $a : $b;
    }
    
    /**
     * 傳回其中一個參數
     * 
     * @param mixed $a
     * @param mixed $b
     * @return mixed
     * @see ifEmpty()
     */
    public static function ifNotEmpty($a, $b = null) 
    {
        return !empty($a) ? $a : $b;
    }
}

?>