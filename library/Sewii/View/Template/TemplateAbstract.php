<?php

/**
 * 樣版引擎抽像類別
 * 
 * @version 1.1.0 2013/02/06 21:40
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\View\Template;

use ArrayAccess;
use Sewii\System\Accessors\AccessorsAbstract;

abstract class TemplateAbstract 
    extends AccessorsAbstract
    implements ArrayAccess
{
    /**
     * __invoke
     *
     * @param string $selector
     * @return Object
     */
    public function __invoke($selector)
    {
        return $this->find($selector);
    }

    /**
     * offsetGet
     * 
     * @param mixed $offset
     * @return Object
     */
    public function offsetGet($offset) 
    {
        return $this->find($offset);
    }

    /**
     * offsetSet
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) 
    {
        $this->find($offset)->html($value);
    }

    /**
     * offsetExists
     *
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset) 
    {
        return ($this->find($offset)->length > 0);
    }

    /**
     * offsetUnset
     *
     * @param mixed $offset 
     * @return void
     */
    public function offsetUnset($offset) 
    {
        $this->find($offset)->remove();
    }
    
    /**
     * 搜尋選擇器
     * 
     * @param mixed $selector
     * @return Object
     */
    abstract public function find($selector);
}

?>