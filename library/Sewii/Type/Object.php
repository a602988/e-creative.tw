<?php

/**
 * 物件類別
 * 
 * @version 1.1.7 2013/06/18 01:43
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Type;

use ArrayAccess;
use Traversable;

class Object
{
    /**
     * 傳回方法或函式是否可以呼叫
     * 
     * 此方法會於檢查前確認方法或函式是否已定義，
     * 以避免觸發 __call() 魔術方法。
     *
     * @param mixed $object
     * @param string $method
     * @return array|false
     */
    public static function isCallable($object, $method = null)
    {
        $callable = array($object, $method);
        if (is_null($method)) $callable = $object;
        
        $isDefine = false;
        if (is_string($callable)) {

            //static method
            $parts = explode('::', $callable);
            if (count($parts) == 2) {
                $callable = $parts;
                list($object, $method) = $parts;
                $isDefine = method_exists($object, $method);
            }

            //function 
            else {
                $isDefine = function_exists($callable);
            }
        }
        //callable method
        else if (is_array($callable)) {
            list($object, $method) = array_pad($callable, 2, null);
            $isDefine = method_exists($object, $method);
        }

        return ($isDefine && is_callable($callable)) ? $callable : false;
    }

    /**
     * 轉換成為陣列
     *
     * @param object $object
     * @return array
     */
    public static function toArray($object)
    {
        if (is_object($object)) {
        
            // ArrayAccess 快轉
            if ($object instanceof ArrayAccess) {
                foreach (array('getArrayCopy', 'toArray') as $method) {
                    if (method_exists($object, $method)) {
                        $callable = array($object, $method);
                        return call_user_func($callable);
                    }
                }
            }

            // Traversable 快轉
            if ($object instanceof Traversable) {
                // TODO: 無法深度轉換，是否要包含此類型物件的快轉功能，仍有待考量!!
                // $toArray = iterator_to_array($object);
                // return $toArray;
            }

            // JSON 快轉
            if ($toJson = @json_encode($object)) {
                $toArray = @json_decode($toJson, true);
                if (is_array($toArray)) return $toArray;
            }

            // 遞迴轉換
            if (is_object($object)) {
                $toObject = function($object) use (&$toObject) {
                    if (is_object($object)) {
                        $toArray = get_object_vars($object);
                        return array_map($toObject, $toArray);
                    }
                    return $object;
                };
                return $toObject($object);
            }
        }
        return $object;
    }
}

?>