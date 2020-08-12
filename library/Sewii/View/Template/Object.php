<?php

/**
 * 樣板物件類別
 *
 * @version 2.5.6 2014/04/10 19:25
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\View\Template;

use ArrayAccess;
use Iterator;
use Countable;
use phpQuery;
use phpQueryObject;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Text\Strings;
use Sewii\Data\Json;
use Sewii\Type\Arrays;
use Sewii\Type\Variable;
use Sewii\Type\Object as DataObject;

class Object extends Child implements ArrayAccess, Iterator, Countable
{
    /**
     * 數據資料字首
     *
     * @const string
     */
    const DATA_PREFIX = 'data-';

    /**
     * 數據資料的參數名稱
     *
     * @const string
     */
    const DATA_PARAM = 'param';

    /**
     * 執行階段原型物件
     *
     * @var phpQueryObject
     */
    protected $prototype = null;

    /**
     * 迭代器進度
     *
     * @var integer
     */
    protected $position = 0;

    /**
     * 建構子
     *
     * @param string|array|DOMNode|DOMNodeList|phpQueryObject|Object $object
     * @param Template $context
     * @throws Exception\Exception
     */
    public function __construct($object = null, $context = null)
    {
        parent::__construct($context);
        $this->setPrototype($object);
    }

    /**
     * 傳回/設定多媒體
     *
     * @param string $content
     * @param boolean $removeIfNotSet
     * @return mixed
     */
    public function media($medium = null, $removeIfNotSet = false)
    {
        foreach($this as $index => $element) {

            //無資料時移除
            if (!$medium) {
                if ($removeIfNotSet === true) $element->remove();
                else if (is_string($removeIfNotSet)) {
                    $this[$removeIfNotSet]->remove();
                }
                continue;
            }

            $param = (array) $element->param();
        }
        return $this;
    }

    /**
     * 傳回/設定內容
     *
     * @param string $content
     * @param boolean $removeIfNotSet
     * @return mixed
     */
    public function content($content = null, $removeIfNotSet = false, $html = false)
    {
        foreach($this as $index => $element) {

            // 無資料時移除
            if (!$content) {
                if ($removeIfNotSet === true) $element->remove();
                else if (is_string($removeIfNotSet)) {
                    $this[$removeIfNotSet]->remove();
                }
                continue;
            }

            $param = (array) $element->param();
            $formatted = $content;

            // 資料格式化
            if (isset($param['type'])) {
                switch(strtolower($param['type']))
                {
                    // 數字格式化
                    case 'number':
                        $formatted = number_format($formatted);
                        break;

                    // 日期格式化
                    case 'date':
                    case 'time':
                    case 'datetime':
                        if (empty($param['format'])) {
                            switch($param['type']) {
                                case 'date':     $param['format'] = 'Y/m/d';     break;
                                case 'time':     $param['format'] = 'H:i:s';     break;
                                case 'datetime': $param['format'] = 'Y/m/d H:i'; break;
                            }
                        }
                        if ($timer = strtotime($formatted)) {
                            $formatted = date($param['format'], $timer);
                        }
                        break;
                }
            }

            // 工具提示
            if (!empty($param['tooltip'])) {
                $element->attr('title', Strings::summary($formatted));
            }

            // 摘要處理
            if (isset($param['length']) && intval($param['length']) >= 1) {
                $formatted = Strings::summary($formatted, $param['length']);
            }

            // 內容輸出
            $type = strtolower(Arrays::value($param, 'type'));
            $method = ($type == 'html') ? 'html' : 'text';
            Variable::isTrue($html) &&  $method = 'html';
            Variable::isFalse($html) && $method = 'text';
            $element->$method($formatted);
        }
        return $this;
    }

    /**
     * 傳回/設定參數資料
     *
     * @param string $content
     * @return mixed
     */
    public function param($value = null)
    {
        $target = $this->first();

        // Getter
        if (func_num_args() === 0) {
            if (is_null($param = $target->data(self::DATA_PARAM)) ) {

                // Find data-param-*
                if ($length = $target->elements[0]->attributes->length) {
                    $keys = array_keys(iterator_to_array($target->elements[0]->attributes));
                    $pattern = sprintf('/^%s%s/', Regex::quote(self::DATA_PREFIX, '/'), self::DATA_PARAM);
                    if ($matches = Regex::grep($pattern, $keys)) {
                        $param = array();
                        foreach($matches as $attr) {
                            $pattern = sprintf('/%s(%s(?:\-(.+)))/', Regex::quote(self::DATA_PREFIX, '/'), self::DATA_PARAM);
                            if ($matched = Regex::match($pattern, $attr)) {

                                $name = Regex::replace('%\-([a-z])%', function($matches) {
                                    return '-' . strToUpper($matches[1]);
                                }, $matched[2]);

                                $param[$name] = $target->elements[0]->attributes->getNamedItem($attr)->value;
                            }
                        }
                    }
                }
            }
            return $param;
        }

        // Setter
        if (is_array($value) || is_object($value)) {
            $value = DataObject::toArray($value);
            foreach ($value as $k => $v) {
                $target->data(self::DATA_PARAM . '-' . $k, $v);
            }
            return $this;
        }
        return $target->data(self::DATA_PARAM, $value);
    }

    /**
     * 傳回/設定數據資料
     *
     * @todo 應該要像 jquery 一樣的駝峰式?
     * @todo 改成只有 $this->param() 可以接受鬆散 JSON 格式
     * @param string $key
     * @param string $value
     * @return mixed
     */
    public function data($key, $value = null)
    {
        // Getter
        if (func_num_args() === 1) {
            $data = null;
            if ($this->length) {
                $target = $this->eq(0);
                if ($data = $target->attr(self::DATA_PREFIX . $key)) {
                    $_data = trim($data);
                    if (Regex::match('/^\{.*\}$/', $_data) || Regex::match('/^\[.*\]$/', $_data)) {
                        if (!($parsed = Json::decode($_data))) {
                            $parsed = Json::parse($_data);
                        }
                        if (is_object($parsed)) {
                            $data = DataObject::toArray($parsed);
                        }
                    }
                }
            }
            return $data;
        }

        // Setter
        if ($this->length) {
            if (is_array($value) || is_object($value)) {
                $value = Json::encode($value);
            }
            $this->attr(self::DATA_PREFIX . $key, $value);
        }
        return $this;
    }

    /**
     * 傳回標籤屬性清單
     *
     * @param mixed $target
     * @return mixed
     */
    public function attrs()
    {
        $target = $this->eq(0);

        $attrs = array();
        if ($target->length) {
            if ($list = $target->elements[0]->attributes) {
		        foreach($list as $index => $attr) {
                    $attrs[$attr->name] = $attr->value;
                }
            }
        }
        return $attrs;
    }

    /**
     * 分配內容
     *
     * 此方法首先插入樣變數後再進行分配內容，
     * 對於大型內容能夠有更好的效率。
     *
     * @see Template::assign()
     * @param mixed $value
     * @return Object
     */
    public function assign($value)
    {
        $base = $this->getContext();
        $name = md5(uniqid());
        $tag = $base->tagLeft . $name . $base->tagRight;
        $this->html($tag);
        $base->assign($name, $value);
        return $this;
    }

    /**
     * 傳回符合選擇器的第一個元素
     *
     * @return mixed
     */
    public function first()
    {
        return $this->eq(0);
    }

    /**
     * 傳回符合選擇器的最後個元素
     *
     * @return mixed
     */
    public function last()
    {
        return $this->eq($this->length - 1);
    }

    /**
     * 產生新副本
     *
     * @param mixed $prototype
     * @return mixed
     */
    protected function makeDuplicate($prototype)
    {
        // $duplicate = new static($prototype, $this->getContext());

        // Clone faster than new create!!
        // But are there other problems??
        $duplicate = clone $this;
        $duplicate->prototype = null;
        $duplicate->setPrototype($prototype);
        return $duplicate;
    }

    /**
     * 設定原型物件
     *
     * @see phpQuery::pq()
     * @param string|array|DOMNode|DOMNodeList|phpQueryObject|Object $object
     * @return Object
     */
    protected function setPrototype($object)
    {
        if ($object instanceof self) {
            $object = $object->prototype;
        }

        if ($object instanceof phpQueryObject) {
            $this->prototype = $object;
        }

        if (is_null($this->prototype)) {
            try {
                $object = $object ?: '<div/>';
                $this->prototype = phpQuery::pq($object);
            }
            catch(\Exception $ex) {
                throw new Exception\Exception($ex->getMessage());
            }
        }

        return $this;
    }

    /**
     * __call
     *
     * @param  string $name
     * @param  array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        switch($name)
        {
            default:

                // 如果方法參數中包含目前物件時，
                // 將其轉換為原型物件再交由原生方法處理
                foreach ($args as &$arg) {
                    if ($arg instanceof self) {
                        $arg = $arg->prototype;
                    }
                }

                // 從原型物件呼叫方法
                $callable = array($this->prototype, $name);
                $returned = call_user_func_array($callable, $args);

                // 如果方法返回原型物件時將其轉換為目前物件
                if ($returned instanceof phpQueryObject) {
                    return $this->makeDuplicate($returned);
                }

                // 攔截輸出方法的額外處理
                if (empty($args)) {
                    switch(strtolower($name)) {
                        case 'html':
                        case 'xml':
                        case 'htmlouter':
                        case 'xmlouter':
                        case 'markup':
                        case 'markupouter':
                            $returned = Parser::toOriginal($returned);
                            break;
                    }
                }

                return $returned;
        }
    }

    /**
     * __get
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        switch ($name) {
            default:
                return $this->prototype->$name;
        }
    }

    /**
     * __set
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        switch ($name) {
            default:
                $this->prototype->$name =  $value;
                break;
        }
    }

    /**
     * __toString
     *
     * @param mixed $offset
     * @return mixed
     */
	public function __toString()
    {
		return $this->markupOuter();
	}

    /**
     * __invoke
     *
     * @param string $selector
     * @return Object
     */
    public function __invoke($selector)
    {
        return $this->offsetGet($selector);
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
        return $this->find($offset)->length > 0;
    }

    /**
     * offsetUnset
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->find($offset)->remove();
    }

    /**
     * rewind
     *
     * @return void
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * current
     *
     * @return mixed
     */
    public function current()
    {
        $element = $this->elements[$this->position];
        return $this->makeDuplicate($element);
    }

    /**
     * key
     *
     * @return integer
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * next
     *
     * @return void
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * valid
     *
     * @return boolean
     */
    public function valid()
    {
        return isset($this->elements[$this->position]);
    }

    /**
     * count
     *
     * @return integer
     */
    public function count()
    {
        return $this->size();
    }
}

?>