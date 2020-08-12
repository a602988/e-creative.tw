<?php

/**
 * 正規表達式類別
 * 
 * @version 1.2.0 2013/07/03 12:26
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Text;

class Regex
{
    /**
     * 測試是否符合樣式
     *
     * @see self::isMatch()
     * @param string $pattern
     * @param string $subject
     * @return array
     */
    public static function test($pattern, $subject) 
    {
        return self::isMatch($pattern, $subject);
    }

    /**
     * 傳回是否符合樣式
     *
     * @param string $pattern
     * @param string $subject
     * @return array
     */
    public static function isMatch($pattern, $subject) 
    {
        return (bool) self::match($pattern, $subject);
    }

    /**
     * 依指定樣式抓取符合的結果
     *
     * @param string $pattern
     * @param string $subject
     * @param int $flags
     * @param int $offset 
     * @return array
     */
    public static function match($pattern, $subject, $flags = 0, $offset = 0) 
    {
        $matches = array();
        preg_match($pattern, $subject, $matches, $flags, $offset);
        return $matches;
    }
    
    /**
     * 依指定樣式抓取符合的所有結果
     *
     * @param string $pattern
     * @param string $subject
     * @param int $flags
     * @param int $offset 
     * @return array
     */
    public static function matches($pattern, $subject, $flags = PREG_PATTERN_ORDER, $offset = 0) 
    {
        $matches = array();
        preg_match_all($pattern, $subject, $matches, $flags, $offset);
        return $matches;
    }
    
    /**
     * 依指定樣式搜尋與置換字串
     *
     * @param mixed $pattern
     * @param mixed $replacement
     * @param mixed $subject
     * @param int $limit
     * @param int $count Pass by reference
     * @return mixed
     */
    public static function replace($pattern, $replacement, $subject, $limit = -1, &$count = null) 
    {
        $replace = is_callable($replacement) ? 'preg_replace_callback' : 'preg_replace';
        return $replace($pattern, $replacement, $subject, $limit, $count);
    }
    
    /**
     * 依指定樣式切割字串
     *
     * @param string $pattern
     * @param string $subject
     * @param int $limit
     * @param int $flags
     * @return array
     */
    public static function split() 
    {
        $args = func_get_args();
        return call_user_func_array('preg_split', $args);
    }
    
    /**
     * 依指定樣式搜尋陣列
     *
     * @param string $pattern
     * @param array $input
     * @param int $flags
     * @return array
     */
    public static function grep() 
    {
        $args = func_get_args();
        return call_user_func_array('preg_grep', $args);
    }
    
    /**
     * 脫逸正規表達式的字元
     *
     * @param string $str
     * @param string $delimiter
     * @return string
     */
    public static function quote() 
    {
        $args = func_get_args();
        return call_user_func_array('preg_quote', $args);
    }
}

?>