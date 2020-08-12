<?php

/**
 * 單元控制器
 * 
 * @version 1.3.1 2014/06/05 18:07
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Controller\Site;

use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\Http\Request;
use Sewii\Http\Response;
use Spanel\Module\Component\Controller\Controller;

abstract class Unit extends Controller
{
    /**
     * 控制器字首
     *
     * @const string
     */
    const CONTROLLER_SUFFIX = 'Unit';

    /**#@+
     * 事件定義
     * @var mixed
     */
    const EVENT_LOAD_LAYOUT = 'onLoadLayout';
    const EVENT_LOAD_HEADER = 'onLoadHeader';
    const EVENT_LOAD_BODY   = 'onLoadBody';
    const EVENT_LOAD_FOOTER = 'onLoadFooter';
    const EVENT_GET_CATEGORY = '';
    /**#@-*/
    
    /**
     * GET 事件流程
     *
     * @var array
     */
    protected $getEvents = array(
        'category' => true,
        'sort' => true,
        'order' => true,
        'filter' => true,
        'filter' => true
    );

    /**
     * 載入事件流程
     *
     * @var array
     */
    protected $loadEvents = array(
        self::EVENT_LOAD_LAYOUT => true,
        self::EVENT_LOAD_HEADER => true,
        self::EVENT_LOAD_BODY   => true,
        self::EVENT_LOAD_FOOTER => true,
    );
    
    /**#@+
     * 執行階段物件
     * @var mixed
     */
    protected $site;
    /**#@-*/
    
    /**
     * 建構子
     * 
     * @param Site $site
     */
    public function __construct(Site $site)
    {
        $this->site = $site;
        $this->toListen();
    }
    
    /**
     * 事件偵聽器
     *
     * {@inheritDoc}
     */
    protected function toListen($caller = null)
    {
        parent::toListen($caller);
        foreach ($this->loadEvents as $event => $enabled) {
            if (!$enabled) continue;
            $event = array($this, $event);
            if (is_callable($event)) {
                call_user_func($event);
            }
        }
    }
    
    /**
     * 列表文件工廠
     *
     * @param array $config
     * @return Document
     */
    protected function listing(array $config = null)
    {
        $document = new Listing($this->site);
        if (isset($config)) {
            $document->init($config);
        }
        return $document;
    }
    
    /**
     * 傳回控制器路徑
     *
     * @return string
     */
    public static function getControllerPath($site = null)
    {
        $site  = $site ?: self::getSiteName();
        $path  = Site::getControllerPath($site);
        $path .= '/' . self::CONTROLLER_SUFFIX;
        return $path;
    }
    
    /**
     * 傳回控制器名稱
     *
     * @param string $site
     * @param string $unit
     * @param boolean $autoLoad
     * @param boolean $isLoaded
     * @return string
     * @throws BadMethodCallException
     */
    public static function getController($site, $unit = null, $autoLoad = true, &$loaded = false)
    {
        if ($unit === null) {
            if (get_called_class() === __CLASS__) {
                throw new Exception\BadMethodCallException('抽象方法呼叫必須傳入必要參數');
            }
            $unit = $site;
            $site = self::getSiteName();
        }

        $controller = sprintf(
            '\%s\%s\%s\%s%s', 
            Site::NAMESPACE_CONTROLLER,
            ucfirst($site), 
            self::CONTROLLER_SUFFIX,
            self::CONTROLLER_SUFFIX,
            ucfirst($unit)
        );

        if ($autoLoad && !class_exists($controller, false)) {
            $pathInfo = new PathInfo($site, $unit);
            if ($pathInfo->isExistsPhp()) {
                require $pathInfo->getPhp();
                $loaded = true;
            }
        }

        return $controller;
    }
    
    /**
     * 傳回路徑物件
     * 
     * @return PathInfo
     */
    public static function getPathInfo()
    {
        $siteName = self::getSiteName();
        $unitName = self::getUnitName();
        $pathInfo = new PathInfo($siteName, $unitName);
        return $pathInfo;
    }
    
    /**
     * 傳回單元名稱
     * 
     * @return string
     * @throws BadMethodCallException
     */
    public static function getUnitName()
    {
        if (($className = get_called_class()) === __CLASS__) {
            throw new Exception\BadMethodCallException('此方法不支援抽象方法呼叫');
        }

        $classParts = explode('\\', $className);
        $className = Arrays::getLast($classParts);
        $unitName = Regex::replace('/^' . self::CONTROLLER_SUFFIX . '/', '', $className);
        $unitName = lcfirst($unitName);
        return $unitName;
    }
    
    /**
     * 傳回網站名稱
     * 
     * @return string
     * @throws BadMethodCallException
     */
    public static function getSiteName()
    {
        if (($className = get_called_class()) === __CLASS__) {
            throw new Exception\BadMethodCallException('此方法不支援抽象方法呼叫');
        }

        $classParts = explode('\\', $className);
        $siteIndex = count($classParts) - 3;
        $siteName = $classParts[$siteIndex];
        $siteName = lcfirst($siteName);
        return $siteName;
    }

    /**
     * 設定瀏覽器標誌
     * 
     * @return void
     */
    protected function setBrowserToken()
    {
        $unit = COM_Unit::getInstance();
        if ($html = $unit->find('html')) {
            $product = Browser::product();
            $product = Browser::msie() ? str_replace('ms', '', $product) : $product;
            $version = Arrays::getFirst(explode('.', Browser::version()));
            $fullname = $product . $version;
            $html->addClass($product);
            $html->addClass($fullname);
        }
    }
}

?>