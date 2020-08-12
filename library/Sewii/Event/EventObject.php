<?php

/**
 * 事件物件
 * 
 * @version 1.1.3 2013/11/29 16:24
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Event;

use ArrayAccess;
use ReflectionMethod;
use ReflectionFunction;
use Sewii\Exception;
use Sewii\Data\Hashtable;
use Sewii\System\Accessors\AbstractAccessors;

class EventObject
    extends AbstractAccessors
{
    /**
     * 事件名稱
     * 
     * @var string 
     */
    protected $type;

    /**
     * 事件目標
     * 
     * @var mixed 
     */
    protected $target;

    /**
     * 事件參數
     * 
     * @var mixed
     */
    protected $params = null;

    /**
     * 停止傳播流程
     * 
     * @var bool 
     */
    protected $stopPropagation = false;

    /**
     * 建構子
     *
     * @param  string $type
     * @param  mixed $target
     * @param  array|ArrayAccess $params
     */
    public function __construct($type = null, $target = null, $params = null)
    {
        if ($type   !== null) $this->setType($type);
        if ($target !== null) $this->setTarget($target);
        if ($params !== null) $this->setParam($params);
    }

    /**
     * 傳回事件類型
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 設定事件類型
     *
     * @param  string $value
     * @return Event
     */
    public function setType($value)
    {
        $this->type = $value;
        return $this;
    }

    /**
     * 傳回事件目標
     *
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * 設定事件目標
     *
     * @param  mixed $target
     * @return Event
     */
    public function setTarget($value)
    {
        $this->target = $value;
        return $this;
    }

    /**
     * 傳回指定參數
     *
     * @param  string $name
     * @param  mixed $default
     * @return mixed
     */
    public function getParam($name = null, $default = null)
    {
        if ($this->params === null) {
            $this->params = new Hashtable();
        }

        if ($name === null) {
            return $this->params;
        }

        if (!isset($this->params->$name)) {
            return $default;
        }

        return $this->params->$name;
    }

    /**
     * 設定指定參數
     *
     * @param  mixed $name
     * @param  mixed $value
     * @return EventObject
     */
    public function setParam($name, $value = null)
    {
        if ($value === null) {

            if ($name instanceof ArrayAccess) {
                $name = Object::toArray($name);
            }

            if (is_array($name)) {
                $name = new Hashtable($name);
            }

            if (!is_object($name)) {
                throw new Exception\InvalidArgumentException('無效參數的類型: ' . gettype($name));
            }

            $this->params = $name;
            return $this;
        }

        $this->params[$name] = $value;
        return $this;
    }

    /**
     * 停止事件傳播流程
     *
     * @return void
     */
    public function stopPropagation()
    {
        $this->stopPropagation = true;
    }

    /**
     * 傳回傳播流程是否已經停止
     *
     * @return bool
     */
    public function isPropagationStopped()
    {
        return $this->stopPropagation;
    }
}

?>