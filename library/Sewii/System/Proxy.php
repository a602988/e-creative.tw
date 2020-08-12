<?php

/**
 * Proxy 代理模式
 * 
 * @version 1.0.0 2013/05/14 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Sewii\System;

use Sewii\Exception;

abstract class Proxy
{
    protected $subject;

    /**
     * Constructor
     * 
     * @param object $subject
     */
    public function __construct($subject)
    {
        $this->setSubject($subject);
    }

    public function setSubject($value) {
        $this->subject = $value;
        return $this;
    }

    public function getSubject() {
        return $this->subject;
    }
    
    /**
     * Getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->subject->$name;
    }
    
    /**
     * Setter
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value) 
    {
        $this->subject->$name = $value;
    }
    
    /**
     * Caller
     *
     * @param  string $name
     * @param  array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->subject, $name), $args);
    }
    
    /**
     * Clone
     *
     * @return object
     */
    public function __clone()
    {
        return clone $this->subject;
    }
}

?>