<?php

/**
 * 路由抽像類別
 * 
 * @version 1.8.6 2013/07/21 16:40
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Router;

use Sewii\Text\Strings;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\Data\Hashtable;
use Sewii\Http\Request;
use Sewii\Uri\Http as HttpUri;
use Sewii\Uri\Http\Query;

abstract class AbstractRoute implements RouteInterface
{
    /**
     * 類別名稱
     * 
     * @const string
     */
    const CLASS_NAME = __CLASS__;

    /**
     * 執行狀態
     *
     * @var array
     */
    protected static $status = array();

    /**
     * 解析動態路由
     *
     * @param string $uri
     * @return Hashtable|null
     */
    protected static function parseDynamic($uri)
    {
        $stateKey = __FUNCTION__ . $uri;
        if (array_key_exists($uri, self::$status)) {
            return isset(self::$status[$stateKey]) 
                 ? clone self::$status[$stateKey] 
                 : self::$status[$stateKey];
        }

        $result = null;
        if ($parseUri = self::parseUri($uri)) {
            $basePathRegex = Regex::quote(trim(self::getBasePath(), '/'));

            // 修正包含 index 的路徑
            $parseUri->path = Regex::replace("#^(([\./]|({$basePathRegex}))*/)?index\.\w+#i", '$1', $parseUri->path);

            // 修正沒有 ? 符號的查詢字串
            if ($isEmptyQuery = empty($parseUri->query) && 
                $isHaveNotPath = !Regex::isMatch('#/#', $parseUri->path) && 
                $isPathAsQueryString = Regex::isMatch('#[&\=\w]#', $parseUri->path)) {
                $parseUri->query = $parseUri->path;
                $parseUri->path = '';
            }

            // 檢查是否符合預期的路徑格式
            if ($isEmptyPath = empty($parseUri->path) || 
                $isWithoutDirName = !Regex::isMatch("#\w+#", $parseUri->path) || 
                $isWithBasePath = Regex::isMatch("#^[\./]*{$basePathRegex}/?$#i", $parseUri->path)) {
                $result = $parseUri;
            }
        }
        
        return self::$status[$stateKey] = isset($result) 
             ? clone $result 
             : $result;
    }
    
    /**
     * 解析靜態路由
     *
     * @param string $uri
     * @return Hashtable|null
     */
    protected static function parseStatic($uri)
    {
        $stateKey = __FUNCTION__ . $uri;
        if (array_key_exists($uri, self::$status)) {
            return isset(self::$status[$stateKey]) 
                 ? clone self::$status[$stateKey] 
                 : self::$status[$stateKey];
        }

        $result = null;
        if ($parseUri = self::parseUri($uri)) {
            $basePathRegex = Regex::quote(trim(self::getBasePath(), '/'));
            if ($parseUri->path) {

                // 拆解路徑內容
                $parseUri->rewritePath = $parseUri->path;
                if ($matches = Regex::match("#^[\./]*({$basePathRegex})?#i", $parseUri->path)) {
                    $basePathRegex = Regex::quote($matches[0], '#');
                    $parseUri->rewritePath = Regex::replace("#^{$basePathRegex}/?#", '', $parseUri->path);
                    $parseUri->path = $matches[0];
                }
                
                $result = $parseUri;
            }
        }
        
        return self::$status[$stateKey] = isset($result) 
             ? clone $result 
             : $result;
    }
    
    /**
     * 解析 URI 格式
     *
     * @param string $uri
     * @return Hashtable|null
     */
    protected static function parseUri($uri)
    {
        $uri = trim($uri);
        if (!self::isNotLocalHostUrl($uri)) {

            if ($parsed = HttpUri::parse($uri)) {
                $parsed = new Hashtable($parsed);
                
                // 修正查詢字串的 HTML 字元
                if (Strings::contains($search = '&amp;', $uri)) {
                    $parsed->restoreHtmlSpecialChar = true;
                    foreach (array('query', 'path') as $key) {
                        $parsed->$key = str_replace($search, '&', $parsed->$key);
                    }
                }

                return $parsed;
            }
        }
        return null;
    }
    
    /**
     * 還原 URI 格式
     *
     * @param Hashtable $parts
     * @return string
     */
    protected static function buildUri($parts)
    {
        $uri = HttpUri::build($parts->toArray());

        // 還原查詢字串的 HTML 字元
        if (isset($parts->restoreHtmlSpecialChar)) {
            $uri = str_replace('&', '&amp;', $uri);
        }

        return $uri;
    }

    /**
     * 傳回是否非本機的 URL
     *
     * @param string $url
     * @return boolean
     */
    protected static function isNotLocalHostUrl($url)
    {
        $url = trim($url);
        if (Regex::isMatch('%^\w+://.+\..+%i', $url)) {
            $host = Regex::quote($_SERVER['SERVER_NAME']);
            $port = $_SERVER['SERVER_PORT'];

            // Check the host
            if (!Regex::match("%^https?\://(.+\:.+@)?(www\.)?{$host}[^\w\.]%i", $url)) {
                return true;
            }
            // Check the port 
            else {
                $withPortPattern    = "/{$host}\:\d+/i";
                $currentPortPattern = "/{$host}\:{$port}[^\d]/i";

                // Standard port
                if ($port == HttpUri::DEFAULT_PORT_HTTP || $port == HttpUri::DEFAULT_PORT_HTTPS) {
                    if (Regex::match($withPortPattern, $url) &&
                       !Regex::match($currentPortPattern, $url)) {
                        return true;
                    }
                }
                // Other ports
                else {
                    if (!Regex::match($currentPortPattern, $url)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * 轉換陣列為靜態路由
     *
     * @param string $field
     * @param array  $value
     * @return string
     */
    protected static function arrayToStaticRoute($field, array $value)
    {
        $parts = array();
        $staticRoute = Query::build(array($field => $value));
        $staticRoute = Regex::replace('/\=|&/', '/', $staticRoute);
        foreach (explode('/', $staticRoute) as $index => $part) {
            if ($index % 2 === 0) {
                $part = Regex::replace('/%5B/', '-', $part);
                $part = Regex::replace('/%5D/', '', $part);
            }
            $parts[] = $part;
        }
        $staticRoute = implode('/', $parts);
        return $staticRoute;
    }
    
    /**
     * 解析路徑參數
     *
     * @todo 可否不用 eval?
     * @param string $path
     * @return array
     */
    protected static function parseParams($path)
    {
        $params = array();
        if ($path = trim($path, '/')) {
            $parts = explode('/', $path);
            foreach ($parts as $index => $part) {
                if ($index % 2 !== 0) continue;
                $next = $index + 1;
                $key = rawurldecode($part);
                $value = isset($parts[$next]) ? $parts[$next] : '';

                // Array parser
                if (Regex::isMatch('/^\w+\-\w+/', $key)) {
                    $offset = '';
                    for ($i = 0, $keys = explode('-', $key), $max = count($keys); $i < $max; $i++) {
                        if ($i === 0) {
                            $key = $keys[$i];
                            eval('$_' . $keys[$i] . '_ = array();');
                            continue;
                        }
                        $offset .= '["' . $keys[$i] . '"]';
                        $val = ($i === ($max - 1)) ? rawurldecode($value) : array();
                        eval('$_' . $key . '_' . $offset . ' = $val;');
                    }
                    eval('$value = $_' . $key . '_;');
                    if (isset($params[$key])) {
                        $params[$key] = Arrays::mergeRecursive($params[$key], $value);
                        continue;
                    }
                }

                $params[$key] = is_scalar($value) ? rawurldecode($value) : $value;
            }
        }
        return $params;
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
        $rewritePath = isset($params['path']) ? $params['path'] : self::getRewritePath();
        $rewritePath = '/' . rawurldecode($rewritePath) . '/';
        if ($inRewrite = Regex::isMatch('%/' . $field . '(\-[\w\-]+)*/%', $rewritePath)) {
            return true;
        }

        // TODO: 不要直接 $_SERVER['QUERY_STRING']
        $queryString = isset($params['query']) ? $params['query'] : $_SERVER['QUERY_STRING'];
        $queryString = '?' . rawurldecode($queryString);
        if ($inQuery = Regex::isMatch('%(\?|&)' . $field . '(\[.*\])*\=%', $queryString)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 傳回重寫路徑
     *
     * @return string
     */
    public static function getRewritePath()
    {
        if (isset(self::$status[$cacheKey = '_rewritePath_'])) {
            return self::$status[$cacheKey];
        }

        $requestUri = self::getRequest()->getRequestUri();
        $basePath = Regex::quote(self::getBasePath());
        $path = Regex::replace("%^{$basePath}%", '', $requestUri);
        $path = Regex::replace('/\?.*$/', '', $path);
        return self::$status[$cacheKey] = $path;
    }
    
    /**
     * 傳回查詢內容
     *
     * @return ArrayAccess
     */
    protected static function getQuery()
    {
        return self::getRequest()->getQuery();
    }
    
    /**
     * 傳回網站根路徑
     *
     * @return string
     */
    protected static function getBasePath()
    {
        return self::getRequest()->getBasePath();
    }
    
    /**
     * 傳回請求物件
     *
     * @return Request
     */
    protected static function getRequest()
    {
        return Router::getInstance()->getRequest();
    }
}
?>