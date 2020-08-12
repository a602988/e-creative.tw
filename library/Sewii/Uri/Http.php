<?php

/**
 * HTTP URI 類別
 * 
 * @version 2.0.10 2013/07/22 13:46
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Uri;

use Zend\Uri\Uri as ZendUri;
use Zend\Validator\Hostname;
use Sewii\Type\Arrays;
use Sewii\Data\Hashtable;
use Sewii\Text\Strings;
use Sewii\Text\Regex;
use Sewii\Event\Event;
use Sewii\Filesystem\File;
use Sewii\Filesystem\Path;
use Sewii\Exception;

class Http extends Uri
{
    /**
     * 預設 HTTP 連接埠
     *
     * @var intger
     */
    const DEFAULT_PORT_HTTP = 80;
    
    /**
     * 預設 HTTPS 連接埠
     *
     * @var intger
     */
    const DEFAULT_PORT_HTTPS = 443;

    /**
     * URI 傳回事件
     *
     * @const string
     */
    const EVENT_GET_URI = 'uri.http.to.string';

    /**
     * 原生請求 URI 傳回事件
     *
     * @const string
     */
    const EVENT_GET_NATIVE_REQUEST_URI = 'uri.http.get.native.request.uri';
    
    /**#@+
     * 過濾器旗幟
     * @const integer
     */
    const FILTER_SCHEME   = 0x01;
    const FILTER_USERNAME = 0x02;
    const FILTER_PASSWORD = 0x04;
    const FILTER_HOST     = 0x08;
    const FILTER_PORT     = 0x10;
    const FILTER_PATH     = 0x20;
    const FILTER_QUERY    = 0x40;
    const FILTER_FRAGMENT = 0x80;
    /**#@-*/
    
    /**
     * scheme
     *
     * @var string
     */
    protected $scheme = '';

    /**
     * 主機名稱
     *
     * @var string
     */
    protected $host = '';

    /**
     * 連接埠
     *
     * @var string
     */
    protected $port = '';

    /**
     * 路徑
     *
     * @var string
     */
    protected $path = '';

    /**
     * 查詢字串
     *
     * @var string
     */
    protected $query = '';

    /**
     * 使用者帳號
     *
     * @var string
     */
    protected $username = '';

    /**
     * 使用者密碼
     *
     * @var string
     */
    protected $password = '';

    /**
     * 訊息片段
     *
     * @var string
     */
    protected $fragment = '';
    
    /**
     * 原生請求 URI
     *
     * @var string
     */
    protected static $nativeRequestUri = null;
    
    /**
     * 執行狀態
     * 
     * @var array
     */
    protected static $states = array();

    /**
     * 工廠模式
     *
     * {@inheritDoc}
     */
    public static function factory($uri = null)
    {
        $uri = $uri ?: self::detectCurrentUrl();
        $cacheKey = 'instance-' . $uri;
        if (isset(self::$states[$cacheKey])) {
            return clone self::$states[$cacheKey];
        }

        $instance = new self($uri);
        if ($parsed = self::parse($uri)) {
            try {
                $hostPort = self::isSslOn() ? self::DEFAULT_PORT_HTTPS : self::DEFAULT_PORT_HTTP;
                $instance->setScheme(Arrays::value($parsed, 'scheme'));
                $instance->setUsername(Arrays::value($parsed, 'user'));
                $instance->setPassword(Arrays::value($parsed, 'pass'));
                $instance->setHost(Arrays::value($parsed, 'host'));
                $instance->setPort(Arrays::value($parsed, 'port', $hostPort));
                $instance->setPath(Arrays::value($parsed, 'path'));
                $instance->setQuery(Arrays::value($parsed, 'query'));
                $instance->setFragment(Arrays::value($parsed, 'fragment'));
            } catch(Exception\InvalidArgumentException $ex) {
                throw new Exception\InvalidArgumentException('無效的 URI 格式: ' . $uri);
            }
        }

        self::$states[$cacheKey] = $instance;
        return clone $instance;
    }

    /**
     * 傳回目前的 URI
     *
     * @param integer $flags
     * @return string
     */
    public function getUri($flags = null)
    {
        Event::trigger(self::EVENT_GET_URI, $this);

        $parts = array(
            'scheme'    =>  $this->getScheme(),
            'username'  =>  $this->getUsername(),
            'password'  =>  $this->getPassword(),
            'host'      =>  $this->getHost(),
            'port'      =>  $this->getPort(),
            'path'      =>  $this->getPath(),
            'query'     =>  $this->getQuery(),
            'fragment'  =>  $this->getFragment()
        );

        // Filter
        if (isset($flags) && intval($flags)) {

            if ($flags & self::FILTER_SCHEME || $flags & self::FILTER_HOST) {
                unset($parts['scheme']);
                unset($parts['username']);
                unset($parts['password']);
                unset($parts['host']);
                unset($parts['port']);
            }

            if ($flags & self::FILTER_USERNAME || $flags & self::FILTER_PASSWORD) {
                unset($parts['username']);
                unset($parts['password']);
            }

            if ($flags & self::FILTER_PORT)      unset($parts['port']);
            if ($flags & self::FILTER_PATH)      unset($parts['path']);
            if ($flags & self::FILTER_QUERY)     unset($parts['query']);
            if ($flags & self::FILTER_FRAGMENT)  unset($parts['fragment']);
        }

        $uri = self::build($parts, true);
        return $uri;
    }

    /**
     * 修改 query 
     *
     * @param mixed $add
     * @param mixed $remove
     * @return Sewii\Uri\Http\Query
     */
    public function query($add, $remove = null)
    {
        $query = Http\Query::modify($add, $remove, $this->getQuery());
        $this->setQuery($query->getQuery());
        return $this;
    }
    
    /**
     * 傳回標頭
     * 
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }
    
    /**
     * 設定標頭
     *
     * @param string $value
     * @return Http
     */
    public function setScheme($value)
    {
        $this->validate(__FUNCTION__, $value);
        $this->scheme = $value;
        return $this;
    }
    
    /**
     * 傳回使用者帳號
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * 設定使用者帳號
     *
     * @param string $value
     * @return Http
     */
    public function setUsername($value)
    {
        $this->validate(__FUNCTION__, $value);
        $this->username = $value;
        return $this;
    }
    
    /**
     * 設定使用者密碼
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * 設定使用者密碼
     *
     * @param string $value
     * @return Http
     */
    public function setPassword($value)
    {
        $this->validate(__FUNCTION__, $value);
        $this->password = $value;
        return $this;
    }
    
    /**
     * 傳回主機名稱
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }
    
    /**
     * 設定主機名稱
     *
     * @param string $value
     * @return Http
     */
    public function setHost($value)
    {
        $this->validate(__FUNCTION__, $value);
        $this->host = $value;
        return $this;
    }
    
    /**
     * 設定連接埠
     *
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }
    
    /**
     * 設定連接埠
     *
     * @param string $value
     * @return Http
     */
    public function setPort($value)
    {
        $this->validate(__FUNCTION__, $value);
        $this->port = $value;
        return $this;
    }
    
    /**
     * 傳回路徑
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * 設定路徑
     *
     * @param string $value
     * @return Http
     */
    public function setPath($value)
    {
        $this->validate(__FUNCTION__, $value);
        $this->path = Strings::setPrefix($value, '/');
        return $this;
    }
    
    /**
     * 設定查詢字串
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }
    
    /**
     * 設定查詢字串
     *
     * @param string $value
     * @return Http
     */
    public function setQuery($value)
    {
        $this->validate(__FUNCTION__, $value);
        if (Strings::contains('?', $value)) {
            $value = Regex::replace('/^(.+)?\?(.+)/', '$2', $value);
        }
        $this->query = $value;
        return $this;
    }
    
    /**
     * 傳回訊息片段
     * 
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }
    
    /**
     * 設定訊息片段
     *
     * @param string $value
     * @return Http
     */
    public function setFragment($value)
    {
        $this->validate(__FUNCTION__, $value);
        $this->fragment = $value;
        return $this;
    }
    
    /**
     * 快速驗證方法
     *
     * @param string $name
     * @param string $value
     * @throws Exception\InvalidArgumentException
     */
    protected function validate($name, $value)
    {
        $target = Regex::replace('/^set(\w+)/i', '$1', $name);
        $validator = 'validate' . ucfirst($target);

        if (!method_exists($this, $validator)) {
            throw new Exception\InvalidArgumentException('無此驗證方法: ' . $validator);
        }

        if (!call_user_func(array($this, $validator), $value)) {
            throw new Exception\InvalidArgumentException('無效的 URI 設定值 (' . lcfirst($target) . '): ' . $value);
        }
    }

    /**
     * 驗證標題
     *
     * @param  string $scheme
     * @return boolean
     */
    public function validateScheme($scheme = null)
    {
        if (is_null($scheme)) $scheme = $this->scheme;
        $scheme = strtolower($scheme);
        return $scheme === 'http' || $scheme === 'https';
    }

    /**
     * 驗證使用者帳號
     *
     * @param  string $username
     * @return boolean
     */
    public function validateUsername($username = null)
    {
        if (is_null($username)) $username = $this->username;
        $regex = '/^(?:[a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=:]+|%[A-Fa-f0-9]{2})*$/';
        return Regex::isMatch($regex, $username);
    }

    /**
     * 驗證使用者密碼
     *
     * @param  string $password
     * @return boolean
     */
    public function validatePassword($password = null)
    {
        if (is_null($password)) $password = $this->password;
        return $this->validateUsername();
    }

    /**
     * 驗證主機名稱
     *
     * @param  string $host
     * @return boolean
     */
    public function validateHost($host = null)
    {
        if (is_null($host)) $host = $this->host;
        $validator = new Hostname(Hostname::ALLOW_ALL);
        return $validator->isValid($host);
    }

    /**
     * 驗證連接埠
     *
     * @param  string $port
     * @return boolean
     */
    public function validatePort($port = null)
    {
        if (is_null($port)) $port = $this->port;
        $port = intval($port);
        return $port >= 1 && $port <= 65535;
    }

    /**
     * 驗證路徑
     *
     * @param  string $path
     * @return boolean
     */
    public function validatePath($path = null)
    {
        if (is_null($path)) $path = $this->path;
        $pchar   = '(?:[a-zA-Z0-9_\-\.~:@&=\+\$,]+|%[A-Fa-f0-9]{2})*';
        $segment = "$pchar(?:;{$pchar})*";
        $regex   = "/^{$segment}(?:\/{$segment})*$/";
        return Regex::isMatch($regex, $path);
    }

    /**
     * 驗證查詢字串
     *
     * @param  string $query
     * @return boolean
     */
    public function validateQuery($query = null)
    {
        if (is_null($query)) $query = $this->query;
        $regex = '/^(?:[a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=:@\/\?]+|%[A-Fa-f0-9]{2})*$/';
        return Regex::isMatch($regex, $query);
    }

    /**
     * 驗證訊息片段
     *
     * @param  string $fragment
     * @return boolean
     */
    public function validateFragment($fragment = null)
    {
        if (is_null($fragment)) $fragment = $this->fragment;
        return $this->validateQuery($fragment);
    }
    
    /**
     * 解析 URL
     *
     * @param string url
     * @return array|boolean
     */
    public static function parse($url, $component = -1)
    {
        return @parse_url($url, $component);
    }

    /**
     * 組合 URL
     *
     * @param array $parts
     * @param boolean $ignoreStandardPort
     * @return string
     */
    public static function build(array $parts, $ignoreStandardPort = false)
    {
        $scheme   = isset($parts['scheme'])   ? $parts['scheme']   : null;
        $username = isset($parts['username']) ? $parts['username'] : null;
        $password = isset($parts['password']) ? $parts['password'] : null;
        $host     = isset($parts['host'])     ? $parts['host']     : null;
        $port     = isset($parts['port'])     ? $parts['port']     : null;
        $path     = isset($parts['path'])     ? $parts['path']     : null;
        $query    = isset($parts['query'])    ? $parts['query']    : null;
        $fragment = isset($parts['fragment']) ? $parts['fragment'] : null;

        if ($ignoreStandardPort) {
            if ($port == self::DEFAULT_PORT_HTTP || 
                $port == self::DEFAULT_PORT_HTTPS) {
                $port = null;
            }
        }

        $scheme   = strlen($scheme)    > 0 ? "$scheme://"                     : '';
        $password = strlen($password)  > 0 ? ":$password"                     : '';
        $auth     = strlen($username)  > 0 ? "$username$password@"            : '';
        $port     = strlen($port)      > 0 ? ":$port"                         : '';
        $path     = strlen($path)      > 0 ? Strings::setPrefix($path, '/')    : '';
        $query    = strlen($query)     > 0 ? "?$query"                        : '';
        $fragment = strlen($fragment)  > 0 ? "#$fragment"                     : '';
        
        return $scheme . $auth . $host . $port . $path . $query . $fragment;
    }

    /**
     * 偵測目前請求的 URL
     *
     * @return string
     */
    protected static function detectCurrentUrl()
    {
        $cacheKey = 'currentUrl-' . self::getNativeRequestUri();
        if (isset(self::$states[$cacheKey])) {
            return self::$states[$cacheKey];
        }

        $parts = array(
            'scheme'   =>  self::isSslOn() ? 'https' : 'http',
            'username' =>  Arrays::value($_SERVER, 'PHP_AUTH_USER'),
            'password' =>  Arrays::value($_SERVER, 'PHP_AUTH_PW'),
            'host'     =>  $_SERVER['SERVER_NAME'],
            'port'     =>  self::detectCurrentPort(),
            'path'     =>  self::detectCurrentPath(),
            'query'    =>  Http\Query::getNativeQuery(),
            'fragment' =>  null
        );

        $url = self::build($parts);
        return self::$states[$cacheKey] = $url;
    }
    
    /**
     * 傳回目前連線是否為 SSL
     *
     * @return string
     */
    protected static function isSslOn()
    {
        return (Arrays::value($_SERVER, 'HTTPS') === 'on');
    }
    
    /**
     * 偵測目前連接埠
     *
     * @return string
     */
    protected static function detectCurrentPort()
    {
        $defaultPorts = array(self::DEFAULT_PORT_HTTP, self::DEFAULT_PORT_HTTPS);
        $port = Regex::replace('/^' . implode('|', $defaultPorts) . '$/', '' , $_SERVER['SERVER_PORT']);
        return $port;
    }
    
    /**
     * 偵測目前請求的路徑
     *
     * @return string
     */
    protected static function detectCurrentPath()
    {
        $requestUri = self::getNativeRequestUri();
        $requestUri = Regex::replace('/\?.*$/', '', $requestUri);
        if (Regex::isMatch('/[^a-zA-Z0-9_\-\.~:@&=\+\$,\/]+/', $requestUri)) {
            $requestUri = Regex::replace('/([^\/])/', function($matches) {
                return rawurlencode($matches[1]);
            }, rawurldecode($requestUri));
        }
        $requestUri = Path::fix($requestUri);
        return $requestUri;
    }
    
    /**
     * 傳回原生的請求 URI
     *
     * @return string
     */
    public static function getNativeRequestUri()
    {
        $sender = isset($this) ? $this : null;
        Event::trigger(self::EVENT_GET_NATIVE_REQUEST_URI, $sender);
        $requestUri = isset(self::$nativeRequestUri) ? self::$nativeRequestUri : $_SERVER["REQUEST_URI"];
        return $requestUri;
    }
    
    /**
     * 設定原生的請求 URI
     *
     * @param string $requestUri
     * @return void
     */
    public static function setNativeRequestUri($requestUri)
    {
        self::$nativeRequestUri = $requestUri;
    }
}
