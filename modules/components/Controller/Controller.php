<?php

/**
 * 控制器抽象類別
 * 
 * @version 1.0.12 2013/02/06 17:25
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Controller;

use ArrayAccess;
use Sewii\Exception;
use Sewii\System\Accessors\AbstractAccessors;
use Sewii\System\Registry;
use Sewii\Data\Hashtable;
use Sewii\Type\Variable;
use Sewii\Type\Arrays;
use Sewii\Text\Regex;
use Sewii\Event\Event;
use Sewii\Http\Request;
use Sewii\Http\Response;
use Sewii\Uri\Uri;
use Sewii\Session\Session;
use Spanel\Module\Component\Router\Router;
use Spanel\Module\Component\Model\Model;

abstract class Controller 
    extends AbstractAccessors
{
    /**
     * Http 事件流程
     *
     * @var array
     */
    protected $httpEvents = array(
        Request::METHOD_OPTIONS => true,
        Request::METHOD_GET     => true,
        Request::METHOD_HEAD    => true,
        Request::METHOD_POST    => true,
        Request::METHOD_PUT     => true,
        Request::METHOD_DELETE  => true,
        Request::METHOD_TRACE   => true,
        Request::METHOD_CONNECT => true,
        Request::METHOD_PATCH   => true,
    );

    /**
     * 控制器命名空間
     *
     * @const string
     */
    const NAMESPACE_CONTROLLER = 'Spanel\Module\Controller';
    
    /**
     * 控制器副檔名
     *
     * @const string
     */
    const EXT_PHP = '.php, .php5, .inc';
    
    /**#@+
     * 欄位名稱
     *
     * @var string
     */
    const FIELD_APPLY = 'apply';
    /**#@-*/
    
    /**#@+
     * 執行階段物件
     */
    protected static $toListened = array();
    /**#@-*/
    
    /**
     * 套用事件
     *
     * @param Request $request
     * @param string $event
     * @return void
     */
    protected function onApply(Request $request, $event)
    {
        $event = ucfirst($event);
        $event = array($this, "onApply$event");
        if (is_callable($event)) {
            call_user_func($event, $request);
        }
    }
    
    /**
     * GET 事件
     *
     * @param Request $request
     * @return void
     */
    protected function onGet(Request $request)
    {
        if ($apply = $request->param[self::FIELD_APPLY]) {
            $event = array($this, 'onApply');
            if (is_callable($event)) {
                call_user_func($event, $request, $apply);
            }
        }
    }
    
    /**
     * POST 事件
     *
     * @param Request $request
     * @return void
     */
    protected function onPost(Request $request)
    {
        if ($apply = $request->post[self::FIELD_APPLY]) {
            $event = array($this, 'onApply');
            if (is_callable($event)) {
                call_user_func($event, $request, $apply);
            }
        }
    }
    
    /**
     * 事件偵聽器
     *
     * @return void
     */
    protected function toListen($caller = null)
    {
        if ($caller === null) {
            $trace = debug_backtrace();
            $caller = isset($trace[1]['class']) ? $trace[1]['class'] : null;
        }

        if (!isset(self::$toListened[$caller])) {
            self::$toListened[$caller] = true;
            foreach ($this->httpEvents as $event => $enabled) {
                if ($enabled) {
                    $request = $this->request;
                    $type = ucfirst(strtolower($event));
                    $isRequest = array($request, "is$event");
                    if (call_user_func($isRequest)) {
                        $event = array($this, "on$event");
                        if (is_callable($event)) {
                            call_user_func($event, $request);
                        }
                    }
                }
            }
        }
    }
    
    /**
     * 定義參數
     * 
     * @param string|array $defined 
     * @param integer $start
     * @return Hashtable
     */
    protected function defineParams($defined = null, $start = 3)
    {
        $getParamAsArray = function ($start = 0) {
            $array  = array();
            $params = $this->request->param->toArray();
            if ($slice = array_slice($params, $start, null, true)) {
                $delimiter = md5(uniqid());
                $query = http_build_query($slice, '', '=');
                $query = str_replace('=', $delimiter, $query);
                $array = explode($delimiter, urldecode($query));
            
                if (Arrays::getLast($array) === '') {
                    $lastIndex = Arrays::getLastKey($array);
                    unset($array[$lastIndex]);
                }
            }
            return $array;
        };

        $params = $getParamAsArray($start);
        
        if (is_string($defined)) {
            $defined = Regex::split('/\s*,\s*/', $defined);
        }

        if ($params && is_array($defined)) {
            foreach ($defined as $index => $name) {
                if ($name) {
                    $value = isset($params[$index]) ? $params[$index] : null;
                    $params[$name] = $value;
                }
            }
        }
        
        $params = new Hashtable($params);
        
        return $params;
    }
    
    /**
     * 關閉會話檔案流
     *
     * @return Controller
     */
    public function unsession()
    {
        Session::writeClose();
        return $this;
    }
    
    /**
     * 模型工廠
     *
     * @return Model
     */
    public static function model($target)
    {
        return Model::factory($target);
    }
    
    /**
     * 傳回控制器路徑
     *
     * @return string
     */
    public static function getControllerPath()
    {
        return Registry::get('config')->path->controller;
    }
    
    /**
     * 傳回根 URL
     * 
     * @return string
     */
    public static function getBaseUrl()
    {
        return self::getRequest()->baseUrl;
    }
    
    /**
     * 傳回根路徑
     * 
     * @return string
     */
    public static function getBasePath()
    {
        return self::getRequest()->basePath;
    }

    /**
     * 傳回請求物件
     *
     * @return Request
     */
    public static function getRequest()
    {
        return Router::getInstance()->getRequest();
    }
    
    /**
     * 傳回回應物件
     *
     * @return Response
     */
    public static function getResponse()
    {
        return Router::getInstance()->getResponse();
    }
}

?>