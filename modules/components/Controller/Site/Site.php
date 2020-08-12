<?php

/**
 * 網站控制器
 * 
 * @version 1.3.18 2014/06/28 08:04
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Controller\Site;

use SimpleXMLElement;
use Sewii\Exception;
use Sewii\Text\Strings;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\Type\Object;
use Sewii\Type\Variable;
use Sewii\Data\Hashtable;
use Sewii\System\Registry;
use Sewii\Filesystem\File;
use Sewii\Filesystem\Directory;
use Sewii\View\Template\Template;
use Spanel\Module\Component\Router\Intent;
use Spanel\Module\Component\Router\IntentActionInterface;
use Spanel\Module\Component\Controller\Controller;
use Spanel\Module\Component\Controller\ErrorHandler;
use Spanel\Module\Component\Controller\Site\Exception\MissSiteArgumentException;

abstract class Site 
    extends Controller 
    implements IntentActionInterface
{
    /**
     * 預設網站名稱
     *
     * @var string
     */
    const DEFAULT_SITE = 'user';
    
    /**
     * 預設單元名稱
     *
     * @const string
     */
    const DEFAULT_UNIT = 'default';

    /**
     * 預設主板名稱
     *
     * @const string
     */
    const DEFAULT_MASTER = 'index';
    
    /**
     * 預設語系名稱
     *
     * @const string
     */
    const DEFAULT_INTL = null;
    
    /**
     * 允許使用預設網站
     *
     * @const boolean
     */
    const ALLOW_DEFAULT_SITE = true;
    
    /**
     * 允許使用預設單元
     *
     * @const boolean
     */
    const ALLOW_DEFAULT_UNIT = true;
    
    /**
     * 文件類型
     *
     * @const boolean
     */
    const CONTENT_TYPE = Template::DEFAULT_CONTENT_TYPE;
    
    /**
     * 控制器字首
     *
     * @const string
     */
    const CONTROLLER_SUFFIX = 'Site';

    /**
     * 控制器命名空間
     *
     * @const string
     */
    const NAMESPACE_CONTROLLER = 'Spanel\Module\Controller\Site';
    
    /**#@+
     * 單元標籤設定
     * @const string
     */
    const ELEMENT_NAME              = 'unit';
    const ELEMENT_ATTR_VIRTUAL      = 'virtual';
    const ELEMENT_ATTR_MASTER       = 'master';
    const ELEMENT_ATTR_EMBED        = 'embed';
    const ELEMENT_ATTR_ACCESS       = 'access';
    const ELEMENT_ATTR_ROOTER       = 'rooter';
    const ELEMENT_ATTR_INITIALIZER  = 'initializer';
    /**#@-*/
    
    /**#@+
     * 執行階段物件
     * @var mixed
     */
    protected $unit;
    protected $intl;
    protected $view;
    protected $parsed = array();
    protected static $instances = array();
    protected static $states = array();
    /**#@-*/
    
    /**
     * 建構子
     * 
     * @param string $site
     * @param string $unit
     */
    public function __construct($unit = null, $intl = null)
    {
        $this->setUnit($unit);
        $this->setIntl($intl);
        self::$instances[self::getSite()] = $this;
    }
    
    /**
     * 工廠模式
     * 
     * @param string $site
     * @param string $unit
     * @return Site
     * @throws Exception\RuntimeException
     */
    public static function factory($site = null, $unit = null, $intl = null)
    {
        //從子類別生產
        if (($className = get_called_class()) != __CLASS__) {
            $unit = $site;
            $site = self::getSite();
        }

        if (!self::hasSite($site)) 
        {
            //嘗試搜尋預設網站
            if (self::ALLOW_DEFAULT_SITE) {
                if ($site != self::DEFAULT_SITE) {
                    if (!self::hasSite(self::DEFAULT_SITE)) {
                        ErrorHandler::trigger(404, "找不到符合的預設網站: $site");
                    }
                    $site = self::DEFAULT_SITE;
                }
            }

            if (!self::hasSite($site)) {
                $site = $site ?: '[NOT SET]';
                ErrorHandler::trigger(404, "找不到符合的網站: $site");
            }
        }
        
        $controller = self::NAMESPACE_CONTROLLER . '\%s\%s';
        $controller = sprintf($controller, $ucfirstSite = ucfirst($site), $ucfirstSite);
        if (!class_exists($controller)) {
            throw new Exception\RuntimeException("無法初始化網站: $site");
        }

        $controller = new $controller($unit, $intl);
        return $controller;
    }
    
    /**
     * 從意圖物件執行
     * 
     * {@inheritDoc}
     * @throws Exception\InvalidArgumentException
     */
    public static function executeIntent(Intent $intent)
    {
        $data = $intent->getExtras();

        if (empty($data->site)) {
            throw new Exception\InvalidArgumentException('意圖物件至少必須包含網站名稱');
        }
        
        return self::factory($data->site, $data->unit, $data->intl)->run();
    }

    /**
     * 開始執行
     *
     * @return Site
     */
    public function run()
    {
        $this->loadView();
        $this->loadUnit();
        $this->output();
        return $this;
    }
    
    /**
     * 傳回網站實體
     * 
     * @param string $site
     * @return void
     */
    public static function getInstance($site = null)
    {
        if (($className = get_called_class()) != __CLASS__) {
            $site = self::getSite();
        }

        if (isset(self::$instances[$site])) {
            return self::$instances[$site];
        }

        throw new Exception\RuntimeException("無法取得網站實體: $site");
    }
    
    /**
     * 傳回網站清單
     *
     * @return array
     * @throws Exception\RuntimeException
     */
    public static function getSites()
    {
        if (isset(self::$states['sites'])) {
            return self::$states['sites'];
        }
        
        $config = Registry::getConfig();
        if (!File::isDir($config->path->site)) {
            throw new Exception\RuntimeException("無法取得網站清單: {$config->path->site}");
        }

        $sites = array();
        $directory = new Directory($config->path->site);
        foreach ($directory as $item) {
            if ($item->isDir()) {
                array_push($sites, $item->getBasename());
            }
        }

        return self::$states['sites'] = $sites;
    }
    
    /**
     * 傳回網站是否存在
     *
     * @param string $value
     * @return boolean
     */
    public static function hasSite($value)
    {
        if ($value) {
            try {
                $sites = self::getSites();
                return in_array($value, $sites);
            }
            catch (\Exception $ex) {}
        }
        return false;
    }
    
    /**
     * 傳回網站名稱
     *
     * @return string
     * @throws BadMethodCallException
     */
    public static function getSite()
    {
        if (($className = get_called_class()) == __CLASS__) {
            throw new Exception\BadMethodCallException('此方法不支援抽象方法呼叫');
        }

        if ($matches = Regex::match('/\w+$/', $className)) {
            $site = lcfirst($matches[0]);
        }
        return $site;
    }
    
    /**
     * 傳回單元清單
     *
     * @param string $site
     * @return array
     * @throws MissSiteArgumentException
     */
    public static function getUnits($site = null)
    {
        if ($site === null && ($className = get_called_class()) == __CLASS__) {
            throw new MissSiteArgumentException('抽象方法呼叫必須傳入網站名稱參數');
        }
        
        $units = array();
        $config = Registry::getConfig();
        $site = $site ?: self::getSite();

        // 從快取傳回
        if (isset(self::$states['units'][$site])) {
            return self::$states['units'][$site];
        }

        if (File::isDir($unitPath = "{$config->path->site}/{$site}")) 
        {
            // 尋找單元目錄
            $directory = new Directory($unitPath);
            foreach ($directory as $item) {
                $path = $item->getPathname();
                if (PathInfo::isValidHtml($item->getPathname())) {
                    $name = PathInfo::getFilename($path);
                    array_push($units, $name);
                }
            }
        
            // 尋找控制器目錄
            if (File::isDir($controllerPath = Unit::getControllerPath($site))) {
                $directory = new Directory($controllerPath);
                foreach ($directory as $item) {
                    $path = $item->getPathname();
                    if (PathInfo::isValidPhp($item->getPathname())) {
                        $name = lcfirst(PathInfo::getFilename($path));
                        array_push($units, $name);
                    }
                }
            }
        
            $units = array_unique($units);
            sort($units);
            self::$states['units'][$site] = $units;
        }
        return $units;
    }
    
    /**
     * 傳回單元是否存在
     *
     * @param string $value
     * @param string $site
     * @return boolean
     */
    public static function hasUnit($value, $site = null)
    {
        if ($value) {
            try {
                $units = self::getUnits($site);
                return in_array($value, $units);
            }
            catch (MissSiteArgumentException $ex) {
                throw $ex;
            }
            catch (\Exception $ex) {}
        }
        return false;
    }
    
    /**
     * 傳回單元名稱
     *
     * @return string
     */
    public function getUnit()
    {
        return $this->unit;
    }
    
    /**
     * 設定單元
     *
     * @param string $value
     * @return Site
     */
    public function setUnit($value)
    {
        if (!self::hasUnit($value)) 
        {
            // 嘗試搜尋預設單元
            if (static::ALLOW_DEFAULT_UNIT) {
                $isFound = false;
                $defaults = array(static::DEFAULT_UNIT, static::DEFAULT_MASTER);
                foreach ($defaults as $default) {
                    if (self::hasUnit($default)) {
                        $value = $default;
                        $isFound = true;
                        break;
                    }
                }

                if (!$isFound) {
                    $defaults = implode(', ', $defaults);
                    ErrorHandler::trigger(404, "找不到符合的預設網站單元: [$defaults]");
                }
            }

            if (!self::hasUnit($value)) {
                $value = $value ?: '[NOT SET]';
                ErrorHandler::trigger(404, "找不到符合的網站單元: $value");
            }
        }

        $this->unit = $value;
        return $this;
    }

    /**
     * 傳回語系
     *
     * @return string
     */
    public function getIntl()
    {
        return $this->intl;
    }
    
    /**
     * 設定語系
     *
     * @param string $value
     * @return Site
     */
    public function setIntl($value)
    {
        $this->intl = $value;
        return $this;
    }
    
    /**
     * 傳回控制器路徑
     *
     * @return string
     */
    public static function getControllerPath($site = null)
    {
        $path  = parent::getControllerPath();
        $path .= '/' . Arrays::getLast(explode('\\', __CLASS__));

        if ($site === null && ($className = get_called_class()) !== __CLASS__) {
            $site = self::getSite();
        }
        
        if ($site !== null) {
            $path .= '/' . ucfirst($site);
        }

        return $path;
    }

    /**
     * 載入介面
     *
     * @return Site
     */
    protected function loadView()
    {
        // 封裝方法
        $that = $this;
        $loader = function ($site, $unit, $embedMode = false) use (&$loader, $that) 
        {
            // 實作方法
            $parsed = array();
            $parser = function ($pathInfo) use (&$parser, &$loader, &$parsed, $embedMode, $that) 
            {
                $site = $pathInfo->getSite();
                $unit = $pathInfo->getUnit();
                
                if (!$pathInfo->isExistsHtml() && !$pathInfo->isExistsPhp()) {
                    throw new Exception\RuntimeException("沒有符合的單元頁面: " . $pathInfo->getHtml());
                }

                // 避免無窮
                if (isset($parsed[$unit])) {
                    $previousPlace = null;
                    if ($trace = debug_backtrace() && isset($trace[1]['args'][0])) {
                        if ($trace[1]['args'][0] instanceof PathInfo) {
                            $previousPlace = "於: " . $trace[1]['args'][0]->getHtml();
                        }
                    }
                    throw new Exception\RuntimeException("無窮 master 標籤指向錯誤 ($unit) $previousPlace");
                }
                
                $loaded = $pathInfo->isExistsHtml() ? File::read($pathInfo->getHtml()) : null;

                $parsed[$unit]['pathInfo'] = $pathInfo;
                $parsed[$unit]['content'] = null;
                $parsed[$unit]['element'] = array();
                $parsed[$unit]['property'] = array();
                $parsed[$unit]['content'] = $loaded;

                // 若無 master 標籤時，建立一個虛擬標籤
                $matches  = Regex::matches("#<" . Site::ELEMENT_NAME . "(\s[^/>]*)?/>#i", $loaded);
                $elements = Arrays::getFirst($matches);
                $embeds   = Regex::grep('/\s+embed=[\'"].*[\'"]/i', $elements);
                if (count($elements) - count($embeds) == 0) {
                    array_unshift($elements, '<' . Site::ELEMENT_NAME . ' virtual="1"/>');
                }

                // Property
                foreach ($elements as $index => $element) {
                    $parsed[$unit]['element'][$index] = $element;
                    $parsed[$unit]['property'][$index] = $property = new Hashtable();

                    // Parse
                    try {
                        $xmlElement = @new SimpleXMLElement($element);
                        if ($array = Object::toArray($xmlElement->attributes())) {
                            $attrs = Arrays::getFirst($array);
                            $property->exchangeArray($attrs);
                        }
                    }
                    catch (\Exception $ex) {
                        throw new Exception\RuntimeException("解析單元標籤時發生錯誤: $element");
                    }

                    // Access
                    if (Variable::isFalse($property->access)) {
                        if ($that->getUnit() == $unit) {
                            ErrorHandler::trigger(403, "拒絕存取的網站單元: $unit");
                        }
                    }

                    // Embed
                    if (isset($property->embed)) {
                        $embed = $property->embed;
                        if (isset($parsed[$embed])) {
                            $place = $pathInfo->getHtml();
                            throw new Exception\RuntimeException("無窮 embed 標籤指向錯誤 ($embed) 於 $place");
                        }
                        $parsed[$unit]['embed'][$index] = $loader($site, $embed, true);
                    }

                    // Master
                    else 
                    {
                        // 僅識別第一個標籤
                        if (isset($parsed[$unit]['master'])) continue;
                        else $parsed[$unit]['master'] = $index;

                        $master = null;

                        // Sets false
                        if (Variable::isFalse($property->master)) {
                            $pathInfo = new PathInfo($site, $property->master);
                            if ($pathInfo->isExistsHtml()) {
                                $master = $property->master;
                            }
                        }

                        // Sets Others
                        else {
                            $master = $property->master;
                            
                            // 若無設定主版頁面，而且非完整頁面
                            if (empty($master) && !Strings::contains('</html>', $loaded)) 
                            {
                                // 非崁入模式時自動使用預設主版名稱
                                if (!$embedMode) {
                                    if ($unit != Site::DEFAULT_MASTER) {
                                        $pathInfo = new PathInfo($site, Site::DEFAULT_MASTER);
                                        if ($pathInfo->isExistsHtml()) {
                                            $master = Site::DEFAULT_MASTER;
                                        }
                                    }
                                }
                            }
                        }
                            
                        if (isset($master)) {
                            $pathInfo = new PathInfo($site, $master);
                            $parser($pathInfo);
                        }
                    }
                    // end master
                }
                // end foreach
            };

            // 開始解析
            $pathInfo = new PathInfo($site, $unit);
            $parser($pathInfo);
            return $parsed;
        };
        
        // 開始載入
        $parsed = $loader($this->getSite(), $this->getUnit());
        $parsed = new Hashtable($parsed, true);
        $this->parsed = $parsed;
        return $this;
    }
    
    /**
     * 載入單元
     *
     * @return Site
     */
    protected function loadUnit()
    {
        $that = $this;
        $loader = function ($parsed) use (&$loader, $that) {
            foreach ($parsed as $unit => $info) {
                $controller = Unit::getController(
                    $info->pathInfo->getSite(), 
                    $info->pathInfo->getUnit(),
                    $autoLoad = true,
                    $isLoaded
                );

                if ($isLoaded) {
                    $controller = new $controller($that);
                }

                // Embed
                if (isset($info->embed)) {
                    foreach ($info->embed as $embed) {
                        $loader($embed);
                    }
                }
            }
        };

        if ($this->parsed) {
            $loader($this->parsed);
        }
        return $this;
    }
    
    /**
     * 傳回介面物件
     *
     * @return Template
     */
    public function getView()
    {
        $that = $this;
        $render = function ($parsed) use (&$render, $that) 
        {
            // 置換方法
            $toReplace = function($search, $modify, $content) {
                $search = Regex::quote($search, '/');
                $search = trim($modify) ? "/[\t\x20]*({$search})([\t\x20]*)/" : "/({$search})([\s]*)/";
                return Regex::replace($search, $modify, $content, 1);
            };
            
            // 渲染流程
            $previous = null;
            $first = Arrays::getFirst($parsed->getKeys());
            foreach ($parsed as $unit => $info) {

                // Embed
                foreach ($info->property as $index => $property) {
                    if (isset($property->embed)) {
                        $embed = $render($info->embed[$index]);
                        $info->content = $toReplace($info->element[$index], $embed, $info->content);
                        continue;
                    }

                    // 去除多餘標籤
                    if ($index != $info->master || $unit == $first) {
                        $info->content = $toReplace($info->element[$index], '', $info->content);
                    }
                }

                // Master
                if (is_null($previous)) $previous = $info->content;
                else 
                {
                    $element = $info->element[$info->master];

                    // 若無實體 master 標籤時直接從後面附加
                    if (isset($property->virtual) && !Strings::contains($element, $info->content)) {
                        $info->content .= PHP_EOL . $element;
                    }

                    $previous = $toReplace($element, $previous, $info->content);
                }
            }
            return $previous;
        };
        
        // Build as Template
        if (is_null($this->view)) {
            if ($this->parsed) {
                $config = Registry::getConfig();
                $content = $render($this->parsed);
                $template = new Template();
                $template->setDebug($config->debugMode);
                $template->setContentType(static::CONTENT_TYPE);
                $template->build($content);
                $this->view = $template;
            }
        }
        return $this->view;
    }
    
    /**
     * 輸出內容
     *
     * @return Site
     */
    protected function output()
    {
        $content = null;
        if ($this->parsed) {
            $view = $this->getView();
            $finalProperty = new Hashtable();
            $scripts = $styles = $titles = array();
            foreach ($this->parsed as $unit => $info) {
                $property = $info->property[$info->master];
                $finalProperty->merge($property->toArray());

                // Title
                if (!empty($property->title)) {
                    array_push($titles, $property->title);
                }

                // Style
                if ($info->pathInfo->isExistsCss()) {
                    $href = $info->pathInfo->getCss();
                    $style = $view->element('css')->href($href);
                    array_unshift($styles, $style);
                }

                // Script
                if ($info->pathInfo->isExistsJs()) {
                    $src = $info->pathInfo->getJs();
                    $script = $view->element('js')->src($src)->setSelectors(array(
                        'body > script[type="text/javascript"]:last' => 'after',
                        'body > script:last' => 'after'
                    ));
                    array_unshift($scripts, $script);
                }
            }

            // Base Href
            if (!Variable::isFalse($finalProperty->base)) {
                if ($view('html')->length) {
                    $href = $info->pathInfo->getBaseHref();
                    $view->element('base')->href($href)->render();
                }
            }

            // Initializer
            if ($setting = $finalProperty->initializer) {
                if (!Variable::isFalse($setting)) {
                    $controller = Unit::getController('common', 'initializer');
                    $initializer = $controller::getUri();
                    $initializer = $view->element('js')->src($initializer);
                    $importants = array('important', 'major', 'head', 'topest');
                    if (in_array(strtolower($setting), $importants)) {
                        $initializer->setSelectors(array(
                            'head > link[rel="stylesheet"]:last' => 'after',
                            'head > style:last' => 'after',
                            'head > base:last' => 'after',
                            'head > meta:last' => 'after',
                        ));
                    }
                    array_unshift($scripts, $initializer);
                }
            }

            // Titles
            if ($titles) {
                $flags = Template::TITLE_PREPEND;
                $view->title($titles, $flags);
            }
            
            // Styles
            if ($styles) {
                foreach ($styles as $style) {
                    $style->render();
                }
            }

            // Scripts
            if ($scripts) {
                foreach ($scripts as $script) {
                    $script->render();
                }
            }

            $content = $view->toString();
        }
        
        $content = $this->formatOutput($content);
        $this->response->write($content);
        return $this;
    }
    
    /**
     * 格式化輸出
     * 
     * @param string $content 
     * @return string
     */
    protected function formatOutput($content)
    {
        // Scripts
        // $content = Regex::replace('/(<\/script>)[\r\n]{2,}/i', "$1\r\n", $content);
        
        // Lines
        if ($matches = Regex::matches('/^<\w+[^\r\n]*/sm', $content)) {
            foreach ($matches[0] as $line) {
                if ($matches2 = Regex::matches('/([\s\t]+<.*)[\r\n]' . Regex::quote($line, '/') . '/i', $content)) {
                    foreach ($matches2[1] as $prev) {
                        $prev = ltrim($prev, "\r\n");
                        if ($matches3 = Regex::match('/^[\s\t]+/', $prev)) {
                            $indent = $matches3[0];
                            $content = Regex::replace('/^' . Regex::quote($line, '/') . '/sm', $indent . $line, $content);
                        }
                    }
                }
            }
        }
        
        return $content;
    }
}

?>