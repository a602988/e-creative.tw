<?php

/**
 * 網站路由
 * 
 * @version 1.7.16 2016/04/06 17:50
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Router;

use Sewii\Exception;
use Sewii\Text\Strings;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\Type\Variable;
use Sewii\System\Registry;
use Sewii\Filesystem\Path;
use Sewii\Uri\Http as HttpUri;
use Sewii\Uri\Http\Query;
use Spanel\Module\Component\Controller\Site\Site;

class SiteRoute extends AbstractRoute
{
    /**#@+
     * 欄位名稱
     *
     * @var string
     */
    const FIELD_SITE = 'site';
    const FIELD_INTL = 'intl';
    const FIELD_UNIT = 'unit';
    /**#@-*/

    /**
     * 類別名稱
     * 
     * @const string
     */
    const CLASS_NAME = __CLASS__;

    /**
     * 路由模式
     * 
     * 1: 複合模式 (支援複合式的單元名稱，e.g., member/register => memberRegister)
     * 2: 簡易模式 (不支援複合式的單元名稱，並且在此模式下不會檢查單元是否真實存在)
     * 
     * @const string
     */
    const MODE = 2;

    /**
     * 重寫樣式
     *
     * @var string
     */
    protected static $pattern = '^(?:/?#site/([^\/]+))?(?:/?#intl/([^\/]+)/?)?(?:/?(.*))';

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
            $parsed = self::parseRewrite($parseUri->rewritePath);
            
            // Site of parsed
            $site = isset($parsed['site']) ? $parsed['site'] : null;

            // Gather the query
            $parts = array();
            $query = Query::parse($parseUri->query);

            foreach ($parsed as $field => $value) 
            {
                // For site, intl, unit
                if (defined($const = 'self::FIELD_' . strtoupper($field))) {

                    // Skip default value
                    if (!self::inUrl($field, $value, array(
                        'path' => $parseUri->rewritePath,
                        'query' => false
                    ))) continue;

                    // Field name
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
            $parsed = self::parseQuery($query);
            
            // Site of parsed
            $site = isset($parsed['site']) ? $parsed['site'] : null;

            // Gather the path
            $parts = array();
            foreach ($parsed as $field => $value) 
            {
                // For site, intl, unit
                if (defined($const = 'self::FIELD_' . strtoupper($field))) {
                
                    // Skip empty value
                    if (Variable::isBlank($value)) {
                        continue;
                    }

                    // Skip default value
                    if (!self::inUrl($field, $value, array(
                        'query' => $parseUri->query,
                        'path' => false
                    ))) continue;

                    // Field name
                    $field = constant($const);
                }

                // For unit of path
                if ($field === self::FIELD_UNIT) {
                    $value = self::toUnitPath($value);
                    $part = $value;
                }
                // For other of path
                else {
                    if (is_array($value)) {
                        $part = self::arrayToStaticRoute($field, $value);
                    }
                    else {
                        $field = rawurlencode($field);
                        $value = rawurlencode($value);
                        $part = "$field/$value";
                    }
                }
                array_push($parts, $part);
            }
            $path = implode('/', $parts);

            // Build the URI
            unset($parseUri->query);
            $parseUri->path = Path::build($parseUri->path, $path);
            $uri = self::buildUri($parseUri);
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
            return array_key_exists('site', $params)
                && array_key_exists('intl', $params)
                && array_key_exists('unit', $params);
        }

        // Match for route
        $rewritePath  = self::getRewritePath();
        $parseRewrite = self::parseRewrite($rewritePath);
        $query        = self::getQuery()->toArray();
        $parseQuery   = self::parseQuery($query, $parseRewrite);
        $this->parsed = $parseQuery + $parseRewrite;
        
        return true;
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
        $parsed = array(
            'site'   => self::getDefault('site'),
            'intl'   => self::getDefault('intl'),
            'unit'   => self::getDefault('unit')
        );

        // Rewrite
        $pattern = self::getPattern('%');
        if ($matches = Regex::match($pattern, $rewritePath)) 
        {
            // Assign
            list(, $matches['site'], $matches['intl'], $matches['path']) = $matches;

            // Site
            if (!empty($matches['site'])) {
                $parsed['site'] = $matches['site'];
                $parsed['intl'] = self::getDefault('intl', $parsed['site']);
                $parsed['unit'] = self::getDefault('unit', $parsed['site']);
            }

            // Intl
            if (!empty($matches['intl'])) {
                $parsed['intl'] = $matches['intl'];
            }

            // Unit
            $unit = &$parsed['unit'];

            // 分解路徑
            if (!empty($matches['path'])) 
            {
                $path = trim($matches['path'], '/');
                $unitPart = null;
                $paramPart = $path;
                
                // 分解單元
                if (self::MODE === 1) {
                    for (
                        $input = Regex::replace('%([A-Z])%', '/$1', self::toUnitName($path)),
                        $explode = explode('/', $input),
                        $loop = count($explode) - 1, 
                        $possibleList = array(); 
                        $loop >= 0; 
                        $loop--
                    ) {
                        $searchName = implode('', array_slice($explode, 0, $loop + 1));
                        array_push($possibleList, $searchName);
                    }

                    if ($units = Site::getUnits($parsed['site'])) {
                        if ($found = array_values(array_intersect($possibleList, $units))) {
                            $unitPart = $unit = $found[0];
                            if (!Regex::IsMatch("/^{$unit}/", $path)) {
                                $unitPart = self::toUnitPath($unit);
                            }
                        }
                    }
                }
                // 簡易模式
                else {
                    // TODO: 第一個參數永遠當作單元名稱的話，將無法省略預設單元名稱
                    // TODO: 在此模式下必須永遠包含單元名稱，例如 /default/arg1/a/arg2
                    $explode = explode('/', $path);
                    $unitPart = $unit = $explode[0];
                }
                
                // 分解參數
                $paramPart = Regex::replace('%^' . Regex::quote($unitPart, '%') . '/?%', '', $path);
                if ($params = self::parseParams($paramPart)) {
                    $params = array_diff_key($params, $parsed);
                    $parsed = array_replace($parsed, $params);
                }
            }
        }
        
        //print_r($parsed);
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
        $parsed = !is_null($defaults) ? $defaults : array(
            'site' => self::getDefault('site'),
            'intl' => self::getDefault('intl'),
            'unit' => self::getDefault('unit')
        );
        
        // Site
        if (isset($query[self::FIELD_SITE])) {
            $parsed['site'] = $query[self::FIELD_SITE];
            $parsed['intl'] = self::getDefault('intl', $parsed['site']);
            $parsed['unit'] = self::getDefault('unit', $parsed['site']);
            unset($query[self::FIELD_SITE]);
        }
        
        // Intl
        if (isset($query[self::FIELD_INTL])) {
            $parsed['intl'] = $query[self::FIELD_INTL];
            unset($query[self::FIELD_INTL]);
        }

        // Unit
        if (isset($query[self::FIELD_UNIT])) {
            $parsed['unit'] = $query[self::FIELD_UNIT];
            unset($query[self::FIELD_UNIT]);
        }

        // Params
        if (count($query)) {
            $parsed = array_replace($parsed, $query);
            unset($query);
        }

        return $parsed;
    }
    
    /**
     * 轉換為單元路徑
     *
     * @param string $unit
     * @return string
     */
    public static function toUnitPath($unit)
    {
        if (self::MODE === 1) {
            $unit = preg_replace_callback('/([A-Z])/', function($matches) {
                return strToLower("/{$matches[1]}");
            }, $unit);
        }
        return $unit;
    }
    
    /**
     * 轉換為單元名稱
     *
     * @param string $unit
     * @return string
     */
    public static function toUnitName($unit)
    {
        if (self::MODE === 1) {
            $unit = preg_replace_callback('%/([a-z])%', function($matches) {
                return strToUpper("{$matches[1]}");
            }, $unit);
        }
        return $unit;
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

            // TODO:: Performance?
            if ($field === 'unit') {
                return parent::inUrl(self::toUnitPath($value), $value, $params)
                    || parent::inUrl($field, $value, $params);
            }
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
        $action = 'Spanel\Module\Component\Controller\Site\Site';
        $intent = new Intent($action);
        $intent->setCaller($this);
        $intent->setExtras($this->parsed);
        return $intent;
    }
    
    /**
     * 傳回預設值
     *
     * @param string $name
     * @param string $site
     * @return mixed
     */
    public static function getDefault($name, $site = null)
    {
        $defaultSite = ucfirst(Site::DEFAULT_SITE);
        $site = ucfirst( $site ?: $defaultSite );
        if (!class_exists(Site::NAMESPACE_CONTROLLER . '\\' . $site . '\\' . $site)) {
            $site = $defaultSite;
        }

        switch ($name) {
            case 'site': return lcfirst($site);
            case 'intl': return constant(Site::NAMESPACE_CONTROLLER . "\\{$site}\\{$site}::DEFAULT_INTL");
            case 'unit': return constant(Site::NAMESPACE_CONTROLLER . "\\{$site}\\{$site}::DEFAULT_UNIT");
        }
        return null;
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
            '#site' => self::FIELD_SITE,
            '#intl' => self::FIELD_INTL
        ));
        if ($delimiter) $pattern = $delimiter . $pattern . $delimiter;
        return $pattern;
    }
}

?>