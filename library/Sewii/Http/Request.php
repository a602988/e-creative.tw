<?php

/**
 * 請求物件
 * 
 * @version 1.4.5 2013/07/11 15:20
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Http;

use ArrayAccess;
use Zend\Http\PhpEnvironment\Request as ZendRequest;
use Zend\StdLib\Parameters;
use Sewii\Exception;
use Sewii\System\Accessors\Accessors;
use Sewii\Filesystem\Path;
use Sewii\Text\Regex;
use Sewii\Type\Object;
use Sewii\Uri\Uri;
use Sewii\Uri\Http as HttpUri;
use Sewii\Uri\Http\Query as HttpQuery;

class Request extends ZendRequest
{
    /**
     * 參數集合
     *
     * @var Parameters
     */
    protected $routeParams = null;

    /**
     * 建構子
     */
    public function __construct()
    {
        parent::__construct();
        $this->initializeUri();
    }

    /**
     * 初始化 URI
     *
     * @return Request
     */
    protected function initializeUri()
    {
        $currentUrl = Uri::factory();

        // Sets the request URI
        $requestUri = $currentUrl->getPath();
        if ($query = $currentUrl->getQuery()) {
            $requestUri .= "?$query";
        }
        $this->setRequestUri($requestUri);
        
        // Sets the base path
        $basePath = '';
        $currentDir = Path::fix(dirname($_SERVER['PHP_SELF']));
        $pattern = '%^' . Regex::quote($currentDir, '%') . '%i';
        if ($matches = Regex::match($pattern, $currentUrl->path)) {
            $basePath = rtrim($matches[0], '/');
            $this->setBasePath($basePath);
        }
        
        // Sets the base URL
        $baseUrl = $currentUrl->setPath($basePath)->getUri(HttpUri::FILTER_QUERY);
        $this->setBaseUrl($baseUrl);

        return $this;
    }
    
    /**
     * 設定參數
     *
     * @param string|array|ArrayAccess $name
     * @param mixed $value
     * @return Request
     */
    public function setParam($name, $value = null)
    {
        if ($value === null) {

            if ($name instanceof ArrayAccess) {
                $name = Object::toArray($name);
            }

            if (is_array($name)) {
                $name = new Parameters($name);
            } 
            else {
                throw new Exception\InvalidArgumentException('無效參數的類型: ' . gettype($name));
            }

            $this->routeParams = $name;
            return $this;
        }
        
        $this->getParam()->set($name, $value);
        return $this;
    }
    
    /**
     * 傳回參數
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParam($name = null, $default = null)
    {
        if ($this->routeParams === null) {
            // TODO: Need clone?
            $this->routeParams = $this->getQuery();
        }

        if ($name === null) {
            return $this->routeParams;
        }

        return $this->routeParams->get($name, $default);
    }
    
    /**
     * 傳回參數是否存在
     *
     * @param string $name
     * @return boolean
     */
    public function hasParam($name)
    {
        return array_key_exists($name, $this->getParam());
    }

    /**
     * 取值子
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return Accessors::getter($this, $name);
        
    }
    
    /**
     * 設定子
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value) 
    {
        Accessors::setter($this, $name, $value);
    }
}

?>