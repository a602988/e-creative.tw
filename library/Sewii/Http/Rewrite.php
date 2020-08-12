<?php

namespace Sewii\Http;

use Sewii\System;
use Sewii\Data;
use Sewii\Text;
use Sewii\Uri;
use Sewii\Intl;
use Sewii\Util;
use Sewii\Exception;

/**
 * URL 重寫類別
 * 
 * @version v 2.1.2 2012/05/03 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Rewrite extends System\Singleton
{
    /**
     * 重寫事件欄位名稱
     * 
     * @const string
     */
    const FIELD_REWRITE = 'onRewrite';

    /**
     * 重寫動作欄位名稱
     * 
     * @const string
     */
    const FIELD_ACTION = \COM_Router::FIELD_ACTION;

    /**
     * 重寫單元欄位名稱
     * 
     * @const string
     */
    const FIELD_UNIT = \COM_Unit::FIELD_NAME;

    /**
     * 重寫語系欄位名稱
     * 
     * @const string
     */
    const FIELD_INTL = Intl\Intl::FIELD_CHANGE;

    /**
     * 重寫路徑欄位名稱
     * 
     * @const string
     */
    const FIELD_CHANGE_BASE = \COM_Unit::FIELD_CHANGE_BASE;
    
    /**
     * 重寫參數欄位名稱
     * 
     * @const string
     */
    const FIELD_PARAMS = 'params';

    /**
     * user 的重寫預設欄位名稱
     * 
     * @const string
     */
    const FIELD_PARAMS_USER = 'id';

    /**
     * thumb 的重寫預設欄位名稱
     * 
     * @const string
     */
    const FIELD_PARAMS_THUMB = 'src';

    /**
     * index 的重事寫件名稱
     * 
     * @const string
     */
    const EVENT_INDEX = 'index';

    /**
     * action 的重事寫件名稱
     * 
     * @const string
     */
    const EVENT_ACTION = self::FIELD_ACTION;
    
    /**
     * user 的重事寫件名稱
     * 
     * @const string
     */
    const EVENT_USER = \COM_Unit::DEFAULT_CLASS_USER;
    
    /**
     * manager 的重事寫件名稱
     * @const string
     */
    const EVENT_MANAGER = \COM_Unit::DEFAULT_CLASS_MANAGER;

    /**
     * thumb 的重事寫件名稱
     * 
     * @const string
     */
    const EVENT_THUMB = \COM_Thumb::NAME_ACTION;

    /**
     * root 的重事寫件名稱
     * 
     * @const string
     */
    const EVENT_ROOT = \COM_Root::NAME_ACTION;

    /**
     * 單元預設名稱
     * 
     * @const string
     */
    const DEFAULT_UNIT_NAME = \COM_Unit::DEFAULT_NAME;
    
    /**
     * 重寫作用目錄
     * 
     * @const string
     */
    const DEFAULT_DIRECTORY = '.';

    /**
     * 表示是否已經發生重寫事件
     * 
     * @var boolean
     */
    protected $_hasOccurred = false;

    /**
     * 重寫完成的 query string 結果
     * 
     * @var array
     */
    protected $_parsedQuerys = array();
    
    /**
     * action 的靜態網址正規樣式
     * 
     * @var string
     */
    protected $_patternAction = null;
    
    /**
     * user 的靜態網址正規樣式
     * 
     * @var string
     */
    protected $_patternUser = null;
    
    /**
     * thumb 的靜態網址正規樣式
     * 
     * @var string
     */
    protected $_patternThumb = null;
    
    //***********************************************************************************************************************//
    // 進入點
    //***********************************************************************************************************************//

    /**
     * 初始化
     * 
     * @return Sewii\Http\Rewrite
     */
    public function init()
    {
        $this->_preinitialize();

        //觸發點
        if (isset($_GET[self::FIELD_REWRITE])) {
            $this->_servePatterns();
            $this->_onRewrite();
        }
        return $this;
    }

    //***********************************************************************************************************************//
    // 重寫事件
    //***********************************************************************************************************************//

    /**
     * Rewrite 事件
     */
    protected function _onRewrite()
    {
        //首頁事件
        if ($_GET[self::FIELD_REWRITE] == self::EVENT_INDEX) {
            //.......
        }

        //觸發事件
        switch($_GET[self::FIELD_REWRITE]) 
        {
            //onRewriteUser
            case self::EVENT_USER:
            default:
                $this->_onRewriteUser();
                break;

            //onRewriteThumb
            case self::EVENT_THUMB:
                $this->_onRewriteThumb();
                break;

            //onRewriteAction
            case self::EVENT_ACTION:
                $this->_onRewriteAction();
                break;
        }

        //刪除原始查詢字串
        unset($_GET[self::FIELD_REWRITE]);
        unset($_GET[self::FIELD_PARAMS]);
        if (empty($_GET[self::FIELD_INTL]))
            unset($_GET[self::FIELD_INTL]);

        $this->_parsedQuerys = $_GET;
        $this->_hasOccurred = true;

        $_SERVER['REQUEST_URI'] = $this->getRequestUri();
        $_SERVER['QUERY_STRING'] = $this->getQueryString();
        
        //Response::write('<pre>' . print_r($_GET, true));
    }
    
    /**
     * Rewrite[user]  事件
     */
    protected function _onRewriteUser()
    {
        $this->_parseParams(function($key, $value, $index) {
            $_GET[$key] = $value;
        }, self::FIELD_PARAMS_USER, $this->_getParams());
    }
    
    /**
     * Rewrite[thumb] 事件
     */
    protected function _onRewriteThumb()
    {
        $this->_parseParams(function ($key, $value, $index) {
            $value = ($key == Rewrite::FIELD_PARAMS_THUMB) ? base64_decode($value) : $value;
            $_GET[$key] = $value;
        }, self::FIELD_PARAMS_THUMB, $this->_getParams());
    }
    
    /**
     * Rewrite[action] 事件
     */
    protected function _onRewriteAction()
    {
        //event of manager
        $action = Data\Arrays::value($_GET, self::FIELD_ACTION);
        if ($action == Rewrite::EVENT_MANAGER) {
            $unit = Data\Arrays::value($_GET, self::FIELD_UNIT);
            if (empty($unit)) $unit = self::DEFAULT_UNIT_NAME;
            $base = Uri\Uri::factory()->getBase();
            $uri = Uri\Uri::factory($base);
            $uri->query(self::FIELD_ACTION . '=' . $action . '&' . self::FIELD_UNIT . '=' . $unit);
            $uri->redirect();
        }

        //event of others
        $this->_parseParams(function ($key, $value, $index) {
            $action = Data\Arrays::value($_GET, Rewrite::FIELD_ACTION);
            switch($action) 
            {
                //root
                case Rewrite::EVENT_ROOT:
                    if (empty($_GET[Rewrite::FIELD_CHANGE_BASE])) $_GET[Rewrite::FIELD_CHANGE_BASE] = '';
                    $_GET[Rewrite::FIELD_CHANGE_BASE] .= $value . '/';
                    break;
                    
                //default
                default:
                    $_GET[$key] = $value;
                    break;
            }
        }, null, $this->_getParams(3));
    }

    /**
     * 解析 params 內容
     *
     * @param string $limit
     * @return string
     *
     */
    protected function _getParams($limit = null)
    {
        $params = null;
        if (!empty($_GET[self::FIELD_PARAMS])) {
            $path = trim($this->_getPath(), '/');
            $limit = !is_null($limit) ? $limit : (!$this->isIntl() ? 2 : 4);
            $parts = explode('/', $path, $limit);
            if ($parts) $params = end($parts);
        }
        return $params;
    }

    /**
     * 解析 params 內容
     *
     * @param string $callback
     * @param string $defaultFieldName
     * @param string $params
     * @return string
     *
     */
    protected function _parseParams($callback, $defaultFieldName = null, $params = null)
    {
        if ($params) 
        {
            $isFoundDefault = false;
            $params = explode('/', $params);
            foreach($params as $index => $param) 
            {
                $explode = explode('-', $param, 2);
                if (count($explode) >= 2) list($key, $value) = $explode;
                else list($key, $value) = array(null, $explode[0]);
                $value = urldecode($value);

                //當找到第一個沒有指定名稱的參數時使用預設名稱
                if (is_null($key) && $defaultFieldName && !$isFoundDefault) {
                    $key = $defaultFieldName;
                    $isFoundDefault = true;
                }
                
                //無指定名稱時直接使用參數索引
                if (is_null($key)) $key = $index;

                //callback
                $callback($key, $value, $index);
            }
        } 
    }

    /**
     * 從 parse_url() 結果還原成 URL 格式
     *
     * @param array $parseUrl
     * @param string $suffix
     * @return string
     *
     */
    protected function _makerUrl(array $parseUrl)
    {
        $parseUrl += array(
            'scheme'    =>  null,
            'user'      =>  null,
            'pass'      =>  null,
            'host'      =>  null,
            'port'      =>  null,
            'path'      =>  null,
            'query'     =>  null,
            'fragment'  =>  null
        );

        extract($parseUrl);

        $scheme   = strlen($scheme)    > 0 ? "$scheme://" : '';
        $pass     = strlen($pass)      > 0 ? ":$pass" : '';
        $auth     = strlen($user)      > 0 ? "$user$pass@" : '';
        $port     = strlen($port)      > 0 ? ":$port" : '';
        $query    = strlen($query)     > 0 ? "?$query" : '';
        $fragment = strlen($fragment)  > 0 ? "#$fragment" : '';

        $url = $scheme . $auth . $host . $port . $path . $query . $fragment;
        return $url;
    }

    //***********************************************************************************************************************//
    // 動態網址
    //***********************************************************************************************************************//

    /**
     * 轉換頁面內容的 URLs 為靜態
     *
     * @param string $content
     * @param boolean $onlyOnHasOccurred - 只在已發生了重寫事件才轉換
     * @return string
     *
     */
    public function convertToDynamic($content, $onlyOnHasOccurred = false)
    {
        if (!($onlyOnHasOccurred && !$this->hasOccurred())) 
        {
            $pattern = '%(src|href)=["\']((\.\./\.\./|(\./)?\?' . self::FIELD_ACTION . '|https?://(.+:.+@)?(www\.)?' . preg_quote($_SERVER['HTTP_HOST']) . ')[^"\']*)["\']%i';
            if (preg_match_all($pattern, $content, $matches)) 
            {
                $sources = array_unique((array)$matches[0]);
                $targets = array_unique((array)$matches[2]);

                if ($targets) {
                    $searches = $modifies = array();
                    foreach($targets as $index => $url) {
                        $dynamic = $this->urlToDynamic($url);
                        $dynamic = (!preg_match('/&amp;/', $dynamic)) ? Text\Strings::html2Text($dynamic) : $dynamic;
                        $search = $sources[$index];
                        $modify = str_replace($url, $dynamic, $search);
                        array_push($searches, $search);
                        array_push($modifies, $modify);
                    }
                    $content = str_replace($searches, $modifies, $content);
                }
            }
        }
        return $content;
    }

    /**
     * 將 URL 轉換為動態網址
     *
     * @param string $url
     * @param boolean $onlyOnHasOccurred - 只在已發生了重寫事件才轉換
     * @return string
     *
     */
    public function urlToDynamic($url, $onlyOnHasOccurred = false)
    {
        if (!$this->_isNotSelfHostname($url) && 
            !($onlyOnHasOccurred && !$this->hasOccurred())) 
        {
            //轉換 HTML 實體
            $text2Html = false;
            if (preg_match('/&amp;/', $url)) {
                $url = Text\Strings::text2Html($url);
                $text2Html = true;
            }

            if ($parseUrl = @parse_url($url)) 
            {
                if (empty($parseUrl['path'])) $parseUrl['path'] = '';
                if ($parseUrl['path']) 
                {
                    //拆解路徑內容
                    $base = preg_match('%^[./]*(' . preg_quote(trim($this->_getBase(), '/')) . ')?%is', $parseUrl['path'], $matches) ? $matches[0] : '';
                    $path = preg_replace('%^' . preg_quote($base) . '/?%', '', $parseUrl['path']);

                    //修正實體路徑
                    $parseUrl['path'] = $base;

                    //解出 query string
                    $queryString = array();
                    if (!empty($parseUrl['query'])) 
                        @parse_str($parseUrl['query'], $queryString);

                    //樣式比對符合
                    //action/action...
                    if (preg_match('%' . $this->_patternAction . '%', $path, $matches)) {
                        if (!empty($matches[1])) {
                            switch($matches[1]) {
                                case self::EVENT_ROOT:
                                    $url = $this->_urlToDynamicOfRoot($url, $parseUrl, $queryString, $matches);
                                    break;
                            }
                        }
                    }
                        
                    //樣式比對符合
                    //thumb/params...
                    else if (preg_match('%' . $this->_patternThumb . '%', $path, $matches)) {
                        $url = $this->_urlToDynamicOfThumb($url, $parseUrl, $queryString, $matches);
                    }

                    //樣式比對符合
                    //unit/[params]... OR
                    //intl/lang/[unit]/[params]...
                    else if (preg_match('%' . $this->_patternUser . '%', $path, $matches)) {
                        $url = $this->_urlToDynamicOfUser($url, $parseUrl, $queryString, $matches);
                    }
                }
            }

            //還原 HTML 實體
            if ($text2Html) $url = Text\Strings::html2Text($url);
        }
        return $url;
    }

    /**
     * 將 URL[user] 轉換為動態網址
     *
     * @param string $url
     * @param array $parseUrl
     * @param array $queryString
     * @param array $matches
     * @return string
     *
     */
    private function _urlToDynamicOfUser($url, array $parseUrl, array $queryString, array $matches)
    {
        //初始化集成
        $result = array();
        if ($url && $this->isIntl() && empty($queryString[self::FIELD_INTL])) 
            $result[self::FIELD_INTL] = $_GET[self::FIELD_INTL];
        if (!empty($matches[1])) $result[self::FIELD_INTL] = $matches[1];
        if (!empty($matches[2])) $result[self::FIELD_UNIT] = $matches[2];
        if ($queryString) $result += $queryString;

        //解析 params
        if (isset($matches[3]) &&
            ($params = trim($matches[3], '/'))) {
            $this->_parseParams(function ($key, $value, $index) use ($result) {
                $result[$key] = $value;
            }, self::FIELD_PARAMS_USER, $params);
        }
        
        //集成結果
        if ($result) {
            $parseUrl['query'] = http_build_query($result);
            $url = $this->_makerUrl($parseUrl);
        }
        return $url;
    }

    /**
     * 將 URL[thumb] 轉換為動態網址
     *
     * @param string $url
     * @param array $parseUrl
     * @param array $queryString
     * @param array $matches
     * @return string
     *
     */
    private function _urlToDynamicOfThumb($url, array $parseUrl, array $queryString, array $matches)
    {
        //初始化集成
        $result = array(self::FIELD_ACTION => self::EVENT_THUMB);
        if ($queryString) $result += $queryString;

        //解析 params
        if ($params = trim($matches[1], '/')) {
            $this->_parseParams(function ($key, $value, $index) use ($result) {
                if ($key == Rewrite::FIELD_PARAMS_THUMB) $value = base64_decode($value);
                $result[$key] = $value;
            }, self::FIELD_PARAMS_THUMB, $params);
        }
        
        //集成結果
        if ($result) {
            $parseUrl['query'] = http_build_query($result);
            $url = $this->_makerUrl($parseUrl);
        }
        return $url;
    }

    /**
     * 將 URL[root] 轉換為動態網址
     *
     * @param string $url
     * @param array $parseUrl
     * @param array $queryString
     * @param array $matches
     * @return string
     *
     */
    private function _urlToDynamicOfRoot($url, array $parseUrl, array $queryString, array $matches)
    {
        $result = array(self::FIELD_ACTION => self::EVENT_ROOT);
        if (isset($matches[1])) $result[self::FIELD_CHANGE_BASE] = $matches[1];
        if ($result) {
            $parseUrl['query'] = http_build_query($result);
            $url = $this->_makerUrl($parseUrl);
        }
        return $url;
    }
    
    //***********************************************************************************************************************//
    // 靜態網址
    //***********************************************************************************************************************//

    /**
     * 轉換頁面內容的 URLs 為靜態
     *
     * @param string $content
     * @param boolean $onlyOnHasOccurred - 只在已發生了重寫事件才轉換
     * @return string
     *
     */
    public function convertToStatic($content, $onlyOnHasOccurred = false)
    {
        if (!($onlyOnHasOccurred && !$this->hasOccurred())) 
        {
            $pattern = '%(src|href)=["\']((\.\./\.\./|(\./)?\?' . self::FIELD_ACTION . '|https?://(.+:.+@)?(www\.)?' . preg_quote($_SERVER['HTTP_HOST']) . ')[^"\']*)["\']%i';
            if (preg_match_all($pattern, $content, $matches)) 
            {
                $sources = array_unique((array)$matches[0]);
                $targets = array_unique((array)$matches[2]);
                
                if ($targets) {
                    $searches = $modifies = array();
                    foreach($targets as $index => $url) {
                        $static = $this->urlToStatic($url);
                        $search = $sources[$index];
                        $modify = str_replace($url, $static, $search);
                        array_push($searches, $search);
                        array_push($modifies, $modify);
                    }
                    $content = str_replace($searches, $modifies, $content);
                }
            }
        }
        return $content;
    }

    /**
     * 將 URL 轉換為靜態網址
     *
     * @param string $url
     * @param boolean $onlyOnHasOccurred - 只在已發生了重寫事件才轉換
     * @return string
     *
     */
    public function urlToStatic($url, $onlyOnHasOccurred = false)
    {
        if (!$this->_isNotSelfHostname($url) && 
            !($onlyOnHasOccurred && !$this->hasOccurred())) 
        {
            //轉換 HTML 實體
            $text2Html = false;
            if (preg_match('/&amp;/', $url)) {
                $url = Text\Strings::text2Html($url);
                $text2Html = true;
            }

            if ($parseUrl = @parse_url($url)) 
            {
                if (empty($parseUrl['path'])) $parseUrl['path'] = '';

                //如果包含 index.php 的修正處理
                $parseUrl['path'] = preg_replace('%^(([\./]|(' . preg_quote($this->_getBase()) . '))*/)?index.php%i', '$1', $parseUrl['path']);

                //沒有 ? 符號開始的 query string 的修正處理
                if (empty($parseUrl['query']) && 
                   !preg_match('/\//', $parseUrl['path']) &&
                    preg_match('/[=&]/', $parseUrl['path'])) 
                {
                    $parseUrl['query'] = $parseUrl['path'];
                    $parseUrl['path'] = '';
                }

                if (empty($parseUrl['path']) || 
                    !preg_match('/\w+/is', $parseUrl['path']) || 
                     preg_match('%^[\./]*' . preg_quote($this->_getBase()) . '/?$%i', $parseUrl['path'])) 
                {
                    //解出 query string
                    $queryString = array();
                    if (!empty($parseUrl['query'])) 
                        @parse_str($parseUrl['query'], $queryString);

                    $action = isset($queryString[self::FIELD_ACTION]) ? $queryString[self::FIELD_ACTION] : null;

                    //to user
                    if ($action == self::EVENT_USER || !$action) {
                        $url = $this->_urlToStaticOfUser($url, $parseUrl, $queryString);
                    }
                    //to thumb
                    else if ($action == self::EVENT_THUMB) {
                        $url = $this->_urlToStaticOfThumb($url, $parseUrl, $queryString);
                    }
                    //to root
                    else if ($action == self::EVENT_ROOT) {
                        $url = $this->_urlToStaticOfRoot($url, $parseUrl, $queryString);
                    }
                }
            }
            
            //還原 HTML 實體
            if ($text2Html) $url = Text\Strings::html2Text($url);
        }
        return $url;
    }

    /**
     * 將 URL[user] 轉換為靜態網址
     *
     * @param string $url
     * @param array $parseUrl
     * @param array $queryString
     * @return string
     *
     */
    private function _urlToStaticOfUser($url, array $parseUrl, array $queryString)
    {
        //如果包含一個以上的 query string
        if (count($queryString) >= 1) 
        {
            //必須強制包含 unit 參數
            //否則無法符合靜態網址格式
            if (empty($queryString[self::FIELD_UNIT]))
                $queryString[self::FIELD_UNIT] = self::DEFAULT_UNIT_NAME;
        }

        //在多語系頁面的例外處理
        if ($url && $this->isIntl()) 
        {
            //強制包含 intl 參數
            if (empty($queryString[self::FIELD_INTL]))
                $queryString[self::FIELD_INTL] = $_GET[self::FIELD_INTL];
        }

        //預先準備好關鍵參數
        //稍候直接參考使用
        $prepared = array(
            self::FIELD_INTL => null, 
            self::FIELD_UNIT => null, 
            self::FIELD_PARAMS_USER => null
        );
        foreach($prepared as $key => &$value) {
            if (!empty($queryString[$key])) {
                $value = $queryString[$key];
                unset($queryString[$key]);
            } else unset($prepared[$key]);
        }

        //至少需要 intl 或 unit 參數才可轉靜態
        if (!empty($prepared[self::FIELD_INTL]) || !empty($prepared[self::FIELD_UNIT])) 
        {
            $parts = array();
            $querys = array();
            
            //預設參數集成
            if (!empty($prepared[self::FIELD_INTL])) array_push($parts, self::FIELD_INTL, $prepared[self::FIELD_INTL]);
            if (!empty($prepared[self::FIELD_UNIT])) array_push($parts, $prepared[self::FIELD_UNIT]);

            //一般參數集成
            foreach($queryString as $key => &$value) {
                if ($this->_isWithSpecialChars($value)) {
                    $querys[$key] = $value;
                    continue;
                }
                $value = urlencode($value);
                $part = $key . '-' . $value;
                array_push($parts, $part);
            }
            
            //特殊參數集成
            if (!empty($prepared[self::FIELD_PARAMS_USER])) 
                array_push($parts, urlencode($prepared[self::FIELD_PARAMS_USER]));
            
            $parseUrl['path']  = Text\Strings::setSuffix($parseUrl['path'], '/');
            $parseUrl['path'] .= implode('/', $parts);
            $parseUrl['query'] = http_build_query($querys);
            $url = $this->_makerUrl($parseUrl);
        }
        return $url;
    }

    /**
     * 將 URL[thumb] 轉換為靜態網址
     *
     * @param string $url
     * @param array $parseUrl
     * @param array $queryString
     * @return string
     *
     */
    private function _urlToStaticOfThumb($url, array $parseUrl, array $queryString)
    {
        //預先準備好關鍵參數
        //稍候直接參考使用
        $prepared = array(self::FIELD_ACTION => null, self::FIELD_PARAMS_THUMB => null);
        foreach($prepared as $key => &$value) {
            if (!empty($queryString[$key])) {
                $value = $queryString[$key];
                unset($queryString[$key]);
            } else unset($prepared[$key]);
        }

        //必須有 action、src 參數才可轉靜態
        if (!empty($prepared[self::FIELD_ACTION]) && !empty($prepared[self::FIELD_PARAMS_THUMB])) 
        {
            $parts = array();
            $querys = array();
            
            //預設參數集成
            array_push($parts, $prepared[self::FIELD_ACTION]);
            
            //一般參數集成
            foreach($queryString as $key => &$value) {
                if ($this->_isWithSpecialChars($value)) {
                    $querys[$key] = $value;
                    continue;
                }
                $value = urlencode($value);
                $part = $key . '-' . $value;
                array_push($parts, $part);
            }

            //特殊參數集成
            array_push($parts, base64_encode($prepared[self::FIELD_PARAMS_THUMB]));
            
            $parseUrl['path']  = $this->_getBase();
            $parseUrl['path']  = Text\Strings::setSuffix($parseUrl['path'], '/');
            $parseUrl['path'] .= implode('/', $parts);
            $parseUrl['query'] = http_build_query($querys);
            $url = $this->_makerUrl($parseUrl);
        }
        return $url;
    }

    /**
     * 將 URL[root] 轉換為靜態網址
     *
     * @param string $url
     * @param array $parseUrl
     * @param array $queryString
     * @return string
     *
     */
    private function _urlToStaticOfRoot($url, array $parseUrl, array $queryString)
    {
        $parts = array(self::FIELD_ACTION, self::EVENT_ROOT);
        if (!empty($queryString[self::FIELD_CHANGE_BASE])) {
            array_push($parts, trim($queryString[self::FIELD_CHANGE_BASE], '/'));
        }
        
        $parseUrl['query'] = '';
        $parseUrl['path']  = $this->_getBase();
        $parseUrl['path']  = Text\Strings::setSuffix($parseUrl['path'], '/');
        $parseUrl['path'] .= implode('/', $parts);
        $url = $this->_makerUrl($parseUrl);
        return $url;
    }
    
    //***********************************************************************************************************************//
    // 公用方法
    //***********************************************************************************************************************//
    
    /**
     * 傳回查詢字串內容
     */
    public function getQueryString()
    {
        $queryString = $_SERVER['QUERY_STRING'];
        if ($this->_parsedQuerys) 
            $queryString = http_build_query($this->_parsedQuerys);
        return $queryString;
    }
    
    /**
     * 傳回請求 URI
     */
    public function getRequestUri()
    {
        $requestUri = $this->_getBase();
        if ($queryString = $this->getQueryString())
            $requestUri .= '?' . $queryString;
        return $requestUri;
    }

    /**
     * 傳回是否已經發生重寫事件
     *
     * @return boolean
     *
     */
    public function hasOccurred()
    {
        return $this->_hasOccurred;
    }

    /**
     * 傳回現在是否在多語系頁面
     *
     * @return boolean
     *
     */    
    public function isIntl()
    {
        return !empty($_GET[self::FIELD_INTL]);
    }
    
    /**
     * 傳回目前各項設定
     * 
     * @return string (json)
     */
    public function getConfigures()
    {
        $configs = array(
            'FIELD_ACTION'       => self::FIELD_ACTION,
            'FIELD_UNIT'         => self::FIELD_UNIT,
            'FIELD_INTL'         => self::FIELD_INTL,
            'FIELD_CHANGE_BASE'  => self::FIELD_CHANGE_BASE,
            'FIELD_PARAMS'       => self::FIELD_PARAMS_USER,
            'FIELD_PARAMS_USER'  => self::FIELD_PARAMS_USER,
            'FIELD_PARAMS_THUMB' => self::FIELD_PARAMS_THUMB,
            'EVENT_ACTION'       => self::EVENT_ACTION,
            'EVENT_USER'         => self::EVENT_USER,
            'EVENT_THUMB'        => self::EVENT_THUMB,
            'EVENT_ROOT'         => self::EVENT_ROOT,
            'DEFAULT_UNIT_NAME'  => self::DEFAULT_UNIT_NAME,
            '_patternUser'       => $this->_patternUser,
            '_patternThumb'      => $this->_patternThumb,
            '_patternAction'     => $this->_patternAction,
            '_hasOccurred'       => $this->_hasOccurred
        );
        return Data\Json::encode($configs);
    }
    
    //***********************************************************************************************************************//
    // 私有方法
    //***********************************************************************************************************************//

    /**
     * 設定重寫規則樣式
     *
     * @return void
     * @throw Sewii\Exception\RuntimeException
     *
     */
    protected function _servePatterns()
    {
        $rules = array('user', 'thumb', 'action');
        $rules = array_fill_keys($rules, null);
        $configure = new Configure(self::DEFAULT_DIRECTORY);
        foreach($rules as $name => &$value) {
            if ($content = $configure->read('Rewrite-' . $name)) {
                if (preg_match_all('/RewriteRule.+/', $content, $matches)) {
                    $lastRule = Data\Arrays::getLast($matches[0]);
                    $search = preg_quote('^(?:%s)<>([^<]+)<>.*$', '/');
                    $search = sprintf($search, '(.+)');
                    if (preg_match('/' . $search . '/', $lastRule, $matches)) {
                        $value = $pattern = '^' . $matches[1] . '$';
                    }
                }
            }

            if (!isset($lastKey)) $lastKey = Data\Arrays::getLast(array_keys($rules));
            if ($name == $lastKey) {
                foreach($rules as $name => $rule) {
                    if (!$rule) throw new Exception\RuntimeException('無法取得 URL 重寫規則 (' . $name . ') 樣式');
                    $this->{'_pattern' . ucfirst($name)} = $rule;
                }
            }
        }
    }

    /**
     * 傳回 URL 是否非本台主機
     *
     * @param mixed $url
     * @return boolean
     *
     */
    protected function _isNotSelfHostname($url)
    {
        if (preg_match('%^\w+://.+\..+%i', $url)) 
        {
            //check the host
            if (!preg_match('%^https?://(.+:.+@)?(www.)?' . preg_quote($_SERVER['HTTP_HOST']) . '(:|/)%is', $url)) {
                return true;
            }
            //check the port 
            else {
                $port = $_SERVER['SERVER_PORT'];
                $withPortPattern = '/' . preg_quote($_SERVER['HTTP_HOST']) . ':\d+/i';
                $currentPortPattern = '/' . preg_quote($_SERVER['HTTP_HOST']) . ':' . $port . '[^\\d+]/i';

                //standard port
                if ($port == 80) {
                    if (preg_match($withPortPattern, $url) &&
                       !preg_match($currentPortPattern, $url))
                        return true;
                }
                //other ports
                else {
                    if (!preg_match($currentPortPattern, $url))
                        return true;
                }
            }
        }
        return false;
    }

    /**
     * 傳回是否包含特殊字元
     *
     * @param string $chars
     * @return string
     *
     */
    protected function _isWithSpecialChars ($chars)
    {
        return preg_match('/%2F|%5C|\/|\\\/', $chars);
    }

    /**
     * 傳回以網站根目錄開始的路徑
     *
     * @param string $currentUri
     * @return string
     *
     */
    protected function _getPath($currentUri = null)
    {
        if (is_null($currentUri)) $currentUri = $_SERVER["REQUEST_URI"];
        $base = $this->_getBase();
        $path = preg_replace('%^' . preg_quote($base) . '%', '', $currentUri);
        $path = preg_replace('/\?.*$/', '', $path);
        return $path;
    }

    /**
     * 傳回網站根目錄的路徑
     *
     * @return string
     *
     */
    protected function _getBase()
    {
        return str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
    }
}

?>