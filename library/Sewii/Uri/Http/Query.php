<?php

/**
 * Http Query 類別
 * 
 * @version 1.5.9 2013/07/12 00:11
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Uri\Http;

use Sewii\Text\Regex;
use Sewii\Event\Event;
use Sewii\Uri\Uri;
use Sewii\Uri\Http as HttpUri;

class Query
{
    /**
     * 原生查詢內容傳回事件
     *
     * @const string
     */
    const EVENT_GET_NATIVE_QUERY = 'uri.http.query.get.native';

    /**
     * 分隔符號
     *
     * @var string
     */
    protected $separator = '&';
    
    /**
     * 預設過濾內容
     *
     * @var array
     */
    protected static $filters = array();
    
    /**
     * 原生查詢內容
     *
     * @var string
     */
    protected static $nativeQuery = null;
    
    /**
     * 查詢字串內容
     *
     * @var string
     */
    protected $query;

    /**
     * 建構子
     *
     * @param string $query
     * @param string $separator
     */
    public function __construct($query = null, $separator = null)
    {
        if (isset($separator)) $this->setSeparator($separator);
        if (isset($query)) $this->setQuery($query);
        else $this->setQuery($this->getNativeQuery());
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
    
    /**
     * 從字串解析為陣列
     *
     * @param mixed $query
     * @return array
     */
    public static function parse($query)
    {
        if (is_array($query)) return $query;
        $parsed = array();
        parse_str(strval($query), $parsed);
        return $parsed;
    }
    
    /**
     * 從陣列還原成為字串
     *
     * @param array $params
     * @param string  $separator
     * @return string
     */
    public static function build(array $params, $separator = null)
    {
        $args = array($params);
        if (!is_null($separator)) array_push($args, '', $separator);
        return call_user_func_array('http_build_query', $args);
    }

    /**
     * 新增
     *
     * @param mixed $query
     * @return Query
     */    
    public function add($query = null) 
    {
        if ($query) {
            $original = $this->getFiltered();
            $addible = self::parse($query);
            $params = array_merge($original, $addible);
            $this->setQuery(self::build($params));
        }
        return $this;
    }
    
    /**
     * 移除
     *
     * @param mixed $query
     * @return Query
     */ 
    public function remove($query = null) 
    {
        if ($query) {
            $original = $this->getFiltered();
            $removable = array_fill_keys(Regex::split('/\s*,\s*/', strval($query)), 0);
            $params = array_diff_key($original, $removable);
            $this->setQuery(self::build($params));
        }
        return $this;
    }
    
    /**
     * 修改
     *
     * @param mixed $add
     * @param mixed $remove
     * @param mixed $original
     * @return Query
     */ 
    public static function modify($add = null, $remove = null, $original = null) 
    {
        is_array($add) && extract($add);
        
        // TODO: 是否可以用 $this??
        $instance = isset($this) ? $this : new self();

        if (isset($original)) {
            $instance->setQuery($original);
        }
        $instance->add($add);
        $instance->remove($remove);
        return $instance;
    }

    /**
     * 設定預設過濾內容
     *
     * @param mixed $filter
     * @param boolean $push
     * @return array
     */
    public static function filter($filter, $push = true) 
    {
        if (is_string($filter)) {
            $filter = Regex::split('/\s*,\s*/', $filter);
        }

        if (!$push) self::$filters = array();
        foreach ((array) $filter as $f) {
            array_push(self::$filters, $f);
        }

        return self::$filters;
    }
    
    /**
     * 傳回經過濾的內容
     *
     * @return array
     */  
    protected function getFiltered()
    {
        return array_diff_key(
            self::parse($this->query), 
            array_fill_keys(self::$filters, 0)
        );
    }

    /**
     * 以 URL 格式傳回
     *
     * @return string
     */
    public function toUrl() 
    {
        return Uri::factory()->setQuery($this->getQuery())->getUri();
    }

    /**
     * 以路徑格式傳回
     *
     * @todo 改 toUri?
     * @return string
     */
    public function toPath() 
    {
        return Uri::factory()->setQuery($this->getQuery())->getUri(HttpUri::FILTER_SCHEME);
    }
    
    /**
     * 以字串格式傳回
     *
     * @return array
     */
    public function toString() 
    {
        return $this->getQuery();
    }
    
    /**
     * 以陣列格式傳回
     *
     * @return array
     */
    public function toArray() 
    {
        return self::parse($this->query);
    }

    /**
     * 傳回查詢字串
     *
     * @param string name
     * @return string
     */
    public function getQuery($name = null) 
    {
        $params = $this->toArray();

        if ($name !== null) {
            if (array_key_exists($name, $params)) {
                return $params[$name];
            }
            return null;
        }

        return self::build($params, $this->separator);
    }

    /**
     * 設定查詢字串
     *
     * @param mixed $query
     * @return Query
     */
    public function setQuery($value) 
    {
        if (is_array($value)) {
            $value = self::build($value);
        }
        $this->query = Regex::replace('/^(.+)?\?(.+)/', '$2', $value);
        return $this;
    }
    
    /**
     * 傳回分隔符號
     *
     * @return string
     */
    public function getSeparator() 
    {
        return $this->separator;
    }
    
    /**
     * 設定分隔符號
     *
     * @param string $value
     * @return Query
     */
    public function setSeparator($value) 
    {
        $this->separator = $value;
        return $this;
    }
    
    /**
     * 傳回原生的查詢內容
     *
     * @return string
     */
    public static function getNativeQuery()
    {
        $sender = isset($this) ? $this : null;
        Event::trigger(self::EVENT_GET_NATIVE_QUERY, $sender);
        return isset(self::$nativeQuery) 
            ? self::$nativeQuery 
            : self::build($_GET);
    }
    
    /**
     * 設定原生的查詢內容
     *
     * @param string $query
     * @return void
     */
    public static function setNativeQuery($query)
    {
        self::$nativeQuery = $query;
    }
}

?>