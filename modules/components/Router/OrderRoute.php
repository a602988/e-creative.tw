<?php

/**
 * 指令路由
 * 
 * @version 1.6.4 2014/04/11 15:59
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Router;

use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\Type\Variable;
use Sewii\Uri\Http as HttpUri;
use Sewii\Uri\Http\Query;
use Sewii\Filesystem\Path;

class OrderRoute extends AbstractRoute
{
    /**
     * 欄位名稱
     *
     * @var string
     */
    const FIELD_ORDER = 'to';

    /**
     * 類別名稱
     * 
     * @const string
     */
    const CLASS_NAME = __CLASS__;

    /**
     * 路由重寫樣式
     *
     * @var string
     */
    protected static $pattern = '^(?:/?#order/([^\/]+))(/?(.*))';

    /**
     * 解析結果
     *
     * @var array
     */
    protected $parsed = array();
    
    /**
     * 轉換為動態路由
     *
     * {@inheritDoc}
     */
    public static function toDynamic($uri)
    {
        if ($parseUri = parent::parseStatic($uri))
        {
            // Parse the path of rewrite
            if ($parsed = self::parseRewrite($parseUri->rewritePath)) 
            {
                // Gather the query
                $parts = array();
                $query = Query::parse($parseUri->query);

                foreach ($parsed as $field => $value) 
                {
                    // Field name
                    if (defined($const = 'self::FIELD_' . strtoupper($field))) {
                        $field = constant($const);
                    }

                    $parts[$field] = $value;
                }
                $queries = $parts + $query;
            
                // Build the URI
                unset($parseUri->rewritePath);
                $parseUri->query = Query::build($queries);
                $uri = self::buildUri($parseUri);
            }
        }
        return $uri;
    }
    
    /**
     * 轉換為靜態路由
     *
     * {@inheritDoc}
     */
    public static function toStatic($uri)
    {
        if ($parseUri = self::parseDynamic($uri))
        {
            // Parse the query string
            $query = Query::parse($parseUri->query);
            if ($parsed = self::parseQuery($query)) 
            {
                // Gather the path
                $parts = array();
                foreach ($parsed as $field => $value) 
                {
                    // Field name
                    if (defined($const = 'self::FIELD_' . strtoupper($field))) {
                        $field = constant($const);
                    }
                    
                    if (is_array($value)) {
                        $part = self::arrayToStaticRoute($field, $value);
                    }
                    else {
                        $field = rawurlencode($field);
                        $value = rawurlencode($value);
                        $part = "$field/$value";
                    }

                    array_push($parts, $part);
                }
                $path = implode('/', $parts);

                // Build the URI
                unset($parseUri->query);
                $parseUri->path = Path::build($parseUri->path, $path);
                $uri = self::buildUri($parseUri);
            }
        };
        return $uri;
    }

    /**
     * 比對方法
     *
     * {@inheritDoc}
     */
    public function match($params = null)
    {
        // Match for params
        if (isset($params)) {
            foreach ($params as $key => $val) {
                if ($key === 'order') {
                    return true;
                }
            }
            return false;
        }
        
        // Match for route
        $rewritePath  = self::getRewritePath();
        $parseRewrite = self::parseRewrite($rewritePath);
        $query        = self::getQuery()->toArray();
        $parseQuery   = self::parseQuery($query, $parseRewrite);
        $this->parsed = $parseRewrite + $parseQuery;
        
        return count($this->parsed) >= 1;
    }
    
    /**
     * 解析重寫規則
     *
     * @param string $rewritePath
     * @return array
     */
    protected static function parseRewrite($rewritePath)
    {
        // Default
        $parsed = array();

        // Rewrite
        $pattern = self::getPattern('%');
        if ($matches = Regex::match($pattern, $rewritePath)) 
        {
            // Assign
            list(, $matches['order'], $matches['params']) = $matches;

            // Order
            $parsed['order'] = $matches['order'];

            // Params
            if ($params = self::parseParams($matches['params'])) {
                $params = array_diff_key($params, $parsed);
                $parsed = array_replace($parsed, $params);
            }
        }

        return $parsed;
    }
    
    /**
     * 解析基本規則
     *
     * @param array $query
     * @param array $defaults
     * @return array
     */
    protected static function parseQuery(array $query, array $defaults = null)
    {
        // Default
        $parsed = !is_null($defaults) ? $defaults : array();

        // Order
        if (isset($query[self::FIELD_ORDER]) && Arrays::getFirstKey($query) == self::FIELD_ORDER) {
            $parsed['order'] = $query[self::FIELD_ORDER];
            unset($query[self::FIELD_ORDER]);
        }

        // Params
        if (isset($parsed['order'])) {
            if (count($query)) {
                $parsed = array_replace($parsed, $query);
                unset($query);
            }
        }

        return $parsed;
    }
    
    /**
     * 傳回欄位是否已設定於 URL
     *
     * @param string $field
     * @param string $value
     * @param array $params
     * @return boolean
     */
    public static function inUrl($field, $value, array $params = null)
    {
        if (defined($const = 'self::FIELD_' . strtoupper($field))) {
            $field = constant($const);
            return parent::inUrl($field, $value, $params);
        }
        return true;
    }

    /**
     * 傳回路由意圖
     *
     * {@inheritDoc}
     */
    public function getIntent()
    {
        $action = 'Spanel\Module\Component\Controller\Order';
        $intent = new Intent($action);
        $intent->setCaller($this);
        $intent->setExtras($this->parsed);
        return $intent;
    }

    /**
     * 傳回重寫樣式
     *
     * @param string $delimiter
     * @return array
     */
    public static function getPattern($delimiter = null)
    {
        $pattern = strtr(self::$pattern, array(
            '#order' => self::FIELD_ORDER
        ));
        if ($delimiter) $pattern = $delimiter . $pattern . $delimiter;
        return $pattern;
    }
}

?>