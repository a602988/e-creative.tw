<?php

/**
 * 自訂物件類別
 * 
 * @version 1.5.1 2013/07/31 17:30
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Data;

use ArrayObject;
use Zend\Stdlib\Parameters;
use Sewii\Data\Json;

class Hashtable extends Parameters
{
    /**
     * 建構子
     *
     * @param array $values
     * @param boolean $recursive
     */
    public function __construct(array $values = null, $recursive = false)
    {
        if ($recursive) {
            $hashtable = self::newRecursive($values);
            $this->setFlags($hashtable->getFlags());
            $this->exchangeArray($hashtable->toArray());
        }
        else {
            if ($values === null) $values = array();
            parent::__construct($values, ArrayObject::ARRAY_AS_PROPS);
        }
    }

    /**
     * 遞迴建構
     *
     * @param array $values
     * @return Hashtable
     */
    public static function newRecursive(array $array)
    {
        $self = new self($array);
        foreach ($self as $key => $value) {
            if (is_array($value)) {
                $self->$key = new self($value, true);
            }
        }
        return $self;
    }

    /**
     * prepend
     *
     * @param mixed $value
     * @return void
     */
    public function prepend($value)
    {
        $array = $this->toArray();
        array_unshift($array, $value);
        $this->exchangeArray($array);
    }

    /**
     * merge
     *
     * @param array $array [, array $... ]
     * @return array
     */
    public function merge()
    {
        $arrays = func_get_args();
        array_unshift($arrays, $this->toArray());
        $merged = call_user_func_array('array_merge', $arrays);
        $this->exchangeArray($merged);
        return $merged;
    }

    /**
     * getValues
     *
     * @return array
     */
    public function getValues()
    {
        return array_values($this->toArray());
    }

    /**
     * getKeys
     *
     * @return array
     */
    public function getKeys()
    {
        return array_keys($this->toArray());
    }

    /**
     * isExists
     *
     * @return boolean
     */
    public function isExists($key)
    {
        return array_key_exists($key, $this);
    }

    /**
     * toJson
     *
     * @return string
     */
    public function toJson()
    {
        return Json::encode($this);
    }

    /**
     * offsetUnset
     *
     * @param mixed $offset 
     * @return void
     */
    public function offsetUnset($offset) 
    {
        if (isset($this[$offset])) {
            parent::offsetUnset($offset);
        }
    }
}

?>