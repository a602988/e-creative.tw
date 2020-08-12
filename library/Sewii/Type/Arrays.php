<?php

/**
 * 陣列類別
 * 
 * @version 1.4.2 2014/04/11 18:12
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Type;

use Sewii\Exception;

class Arrays
{
    /**
     * 遞迴傳回陣列的全部元素
     *
     * @param object $ar
     * @return array
     */
     public static function getAllValues($ar) {
        $values = array ();
        foreach ($ar as $k => $v) {
            $values[] = $v;
            if (is_array ($v))
                $values = array_merge($values,  call_user_func(array(__CLASS__, __FUNCTION__), $v));
        }
        return $values;
    }

    /**
     * 遞迴傳回陣列的全部索引值
     *
     * @param object $ar
     * @return array
     */
     public static function getAllKeys($ar)
    {
        $keys = array ();
        foreach($ar as $k => $v) {
            $keys[] = $k;
            if (is_array ($v))
                $keys = array_merge($keys, call_user_func(array(__CLASS__, __FUNCTION__), $v));
        }
        return $keys;
    }

    /**
     * 遞迴傳回陣列的全部索引值和元素
     * 索引 k 的元素為鍵、索引 v 的元素為鍵值
     * 
     * @param object $ar
     * @return array
     */
     public static function getAllKeysAndValues($ar) {
        $values = array ();
        foreach ($ar as $k => $v) {
            $values[] = array ('k' => $k, 'v' => $v);
            if (is_array ($v))
                $values = array_merge($values, call_user_func(array(__CLASS__, __FUNCTION__), $v));
        }
        return $values;
    }

    /**
     * 交換陣列元素的索引位置
     *
     * @param array $array 陣列
     * @param integer $src - 要移動的陣列元素原索引位置
     * @param integer $dest - 要移動的陣列元素新索引位置
     * @return array
     */
    public static function swapIndex($array, $src, $dest)
    {
        $arrayValue = $array[$src];
        array_splice($array, $src, 1);
        $arrayNew = array_slice($array, $dest);
        array_unshift($arrayNew, $arrayValue);
        array_splice($array, $dest, count($array), $arrayNew);
        return $array;
    }

    /**
     * 傳回是否為為雜湊類型的陣列
     *
     * @param array $array
     */
    public static function isAssoc($array) 
    {
         return (is_array($array) && (count($array) == 0 || 0 !== count(array_diff_key($array, array_keys(array_keys($array))) )));
    } 

    /**
     * 傳回陣列的第一個元素
     *
     * @param array $array
     * @param boolean $returnKey
     * @return mixed
     */
    public static function getFirst($array, $returnKey = false) 
    {
        if (is_array($array)) {
            foreach ($array as $key => $element) {
                if ($returnKey) return $key;
                return $element;
            }
        }
        return null;
    }

    /**
     * 傳回陣列的第一個鍵值
     *
     * @param array $array
     * @return mixed
     */
    public static function getFirstKey($array) 
    {
        return self::getFirst($array, true);
    }
    
    /**
     * 傳回陣列的最後一個元素
     *
     * @param array $array
     * @param boolean $returnKey
     * @return mixed
     */
    public static function getLast($array, $returnKey = false) 
    {
        if (is_array($array)) {
            if ($returnKey) {
                $keys = array_keys($array);
                return array_pop($keys); 
            }
            return end($array);
        }
        return null;
    }

    /**
     * 傳回陣列的最後一個鍵值
     *
     * @param array $array
     * @return mixed
     */
    public static function getLastKey($array) 
    {
        return self::getLast($array, true);
    }

    /**
     * 遞迴合併多個陣列
     * 
     * 如果被合併的陣列元素是陣列時將會以遞迴方式進行附加合併
     *
     * @todo array_replace_recursive() 可用??
     * @todo 重複數字索引附加成新元素有無必要??
     * @return array
     */
    public static function mergeRecursive()
    {
        $arrays = func_get_args();
        if ($arrays) {
            $merged = array();
            foreach ($arrays as $serial => $array) {
                if (is_array($array)) {
                    if (!$merged) {
                        $merged = $array;
                        continue;
                    }
                    foreach ($array as $key => $element) 
                    {
                        //如果為重複數字索引附加成新元素
                        if (is_numeric($key)) {
                            $merged[] = $element;
                            continue;
                        }

                        //如果為重複字串索引而且皆為陣列值遞迴合併
                        if (array_key_exists($key, $merged) && is_array($merged[$key]) && is_array($element)) {
                            $merged[$key] = call_user_func(array(__CLASS__, __FUNCTION__), $merged[$key], $element);
                            continue;
                        }

                        //如果為重複字串索引直接覆寫
                        $merged[$key] = $element;
                    }
                }
                else throw new Exception\InvalidArgumentException('參數 ' . ($serial + 1) . ' 必須是陣列');
            }
            return $merged;
        }
        return $arrays;
    }

    /**
     * 依索引傳回陣列元素
     *
     * @param array $array
     * @param string $keys
     * @return mixed
     */
    public static function value(array $array, $keys, $defaultValue = null)
    {
        $keys = preg_split('/\s*,\s*/', $keys);
        foreach($keys as $key) {
            if (!array_key_exists($key, $array)) return $defaultValue;
            $array = $array[$key];
        }
        return $array;
    }
    
    /**
     * 插入一個元素到陣列的起始處
     *
     * @param array $array
     * @param string $key
     * @return integer
     */
    public static function insert(array &$array, $value, $key = null)
    {
        if ($key == null) return array_unshift($array, $value);
        else if (is_string($key)) {
            $array = array_reverse($array, true);
            $array[$key] = $value;
            $array = array_reverse($array, true);
        }
        return count($array);
    }
}

?>