<?php

/**
 * 路由器
 * 
 * @version 1.2.17 2013/07/21 16:42
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Router;

use Sewii\Text\Regex;
use Sewii\Event\Event;
use Sewii\System\Singleton;
use Sewii\System\Registry;
use Sewii\Filesystem\Path;
use Sewii\Http\Request;
use Sewii\Http\Response;
use Sewii\Uri\Uri;
use Sewii\Uri\Http as HttpUri;
use Sewii\Uri\Http\Query as HttpQuery;

class Router extends Singleton
{
    /**
     * 路由表
     *
     * @var array
     */
    protected $routes = array(
        OrderRoute::CLASS_NAME,
        SiteRoute::CLASS_NAME
    );
    
    /**#@+
     * 執行階段物件
     * 
     * @var mixed
     */
    protected $request;
    protected $response;
    /**#@-*/

    /**
     * 開始執行
     * 
     * @return Router
     */
    public static function enter()
    {
        return self::getInstance();
    }

    /**
     * 初始化
     * 
     * @return Router
     */
    public function initialize()
    {
        $this->preinitialize();
        $this->build();
        $this->listen();
        return $this;
    }

    /**
     * 環境建置
     * 
     * @return void
     */
    protected function build()
    {
        $this->response = new Response();
        $this->request = new Request();
    }

    /**
     * 事件偵聽
     * 
     * @return void
     */
    protected function listen() 
    {
        Event::on(
            HttpUri::EVENT_GET_URI, 
            array($this, 'onRewriteUri')
        );

        Event::one(
            HttpUri::EVENT_GET_NATIVE_REQUEST_URI, 
            array($this, 'onRewriteRequestUri')
        );

        Event::one(
            HttpQuery::EVENT_GET_NATIVE_QUERY, 
            array($this, 'onRewriteQuery')
        );
        
        foreach ($this->routes as $route) {
            $route = new $route();
            if ($route->match()) {
                $this->onMatch($route);
                break;
            }
        }
    }
    
    /**
     * 匹配事件
     * 
     * @param AbstractRoute $route
     * @return void
     */
    protected function onMatch(AbstractRoute $route)
    {
        $intent = $route->getIntent();
        $data = $intent->getExtras();
        $this->request->setParam($data);
        $this->onDispatch($intent);
    }
    
    /**
     * 分發事件
     * 
     * @param Intent $intent
     * @return void
     */
    protected function onDispatch(Intent $intent)
    {
        $dispatcher = new Dispatcher;
        $dispatcher->dispatch($intent);
    }
    
    /**
     * URI 重寫事件
     * 
     * @param EventObject $event
     * @return void
     */
    public function onRewriteUri($event)
    {
        $target = $event->target;
        if ($query = $target->query) {
            $realPath = $target->path;
            $rewritePath = RouteConvert::toStatic($query);
            $path = Path::build($realPath, $rewritePath);
            $target->setPath($path)->setQuery('');
        }
    }
    
    /**
     * 原生 URI 重寫事件
     * 
     * @param EventObject $event
     * @return void
     */
    public function onRewriteRequestUri($event)
    {
        $requestUri = $this->request->basePath ?: '/';
        if ($query = $this->getParamAsQueryString()) {
            $requestUri .= "?{$query}";
        }
        HttpUri::setNativeRequestUri($requestUri);
    }
    
    /**
     * 原生查詢字串重寫事件
     * 
     * @param EventObject $event
     * @return void
     */
    public function onRewriteQuery($event)
    {
        $query = $this->getParamAsQueryString();
        HttpQuery::setNativeQuery($query);
    }
    
    /**
     * 傳回查詢字串格式的參數內容
     *
     * @return string
     */
    protected function getParamAsQueryString()
    {
        $routeClass = AbstractRoute::CLASS_NAME;
        foreach ($this->getRoutes() as $route) {
            $route = new $route;
            if ($route->match($this->request->param)) {
                $routeClass = $route;
                break;
            }
        }
        
        $params = array();
        foreach ($this->request->param as $field => $value) {
            if (!$routeClass::inUrl($field, $value)) {
                continue;
            }
            $params[strval($field)] = $value;
        }
        return HttpQuery::build($params);
    }
    
    /**
     * 傳回路由清單
     *
     * @return array
     */
    public function getRoutes()
    {
       return $this->routes;
    }
    
    /**
     * 傳回回應物件
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }
    
    /**
     * 傳回請求物件
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}

?>