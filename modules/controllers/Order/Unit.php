<?php

use Sewii\Data;
use Sewii\Text;
use Sewii\Util;
use Sewii\Uri;
use Sewii\Intl;
use Sewii\Http;
use Sewii\View\Template;
use Sewii\Filesystem\File;

/**
 * 單元頁面元件
 * 
 * @version v 2.2.0 2012/05/20 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class COM_Unit extends Template\Engine
{
    /**
     * 多語系支援
     * @var string
     */
    public static $intl = true;

    /**
     * 預設使用樣板類型
     * @var string
     */
    public $docType = 'xhtml';
    
    /**
     * 儲存單元資訊
     * @var array
     */
    public $infos = array();
    
    /**
     * 儲存最後輸出時的變數分配集合
     * @var array
     */
    public $assigns = array();

    /**
     * 預設前端類別名稱
     * @const string
     */
    const DEFAULT_CLASS_USER = 'user';

    /**
     * 預設後端類別名稱
     * @const string
     */
    const DEFAULT_CLASS_MANAGER = 'manager';

    /**
     * 預設主版名稱
     * @const string
     */
    const DEFAULT_MASTER = 'index';

    /**
     * 預設單元名稱
     * @const string
     */
    const DEFAULT_NAME = 'default';

    /**
     * 預設外部檔案目錄
     * @const string
     */
    const FOLDER_EXTERNAL = 'externals';

    /**
     * 預設外部客戶端檔案目錄
     * @const string
     */
    const FOLDER_CLIENT = 'clients';

    /**
     * 預設外部檔案副檔名
     * @const string
     */
    const EXTS_HTML = '*.html; *.htm; *.xml; *.php; *.phtml';
    const EXTS_PHP = '*.php; *.php4; *.php5; *.phtml';
    const EXTS_JS = '*.js; *.jse; *.json';

    /**
     * 屬性標籤的選擇器
     * @const string
     */
    const SELECTOR_ATTR = 'unit[type="setup"]';

    /**
     * 崁入標籤的選擇器
     * @const string
     */
    const SELECTOR_EMBED = 'unit[type="embed"]';

    /**
     * 客戶端初始化程式 ID
     * @const string
     */
    const INITIALIZE_ID = 'initialize';

    /**
     * 客戶端初始化程式路徑
     * @const string
     */
    const INITIALIZE_PATH = 'initialize.js';

    /**
     * 表示客戶端程式標記
     * @const string
     */
    const TOKEN_CLIENT_SCRIPT = '__unitClientScript';

    /**
     * 表示單元名稱的查詢欄位
     * @const string
     */
    const FIELD_NAME = 'unit';
    
    /**
     * 最後載入的頁面內容
     * @var string
     */
    protected $_lastContent = null;
    
    /**
     * 頁面標題容器
     * @var array
     */
    protected $_titles = array();
    
    /**
     * 頁面樣式表容器
     * @var array
     */
    protected $_styles = array();
    
    /**
     * 初始化
     *
     * @param string $class
     * @param string $name
     * @param mixed $options
     * @return COM_Unit
     */
    public function init($class, $name, $options = null) 
    {
        //載入 html 頁面
        $paths = $this->getPaths($class, $name);
        if (File::isExists($paths['html'])) {
            $content = $this->open($paths['html']);
            $content = preg_replace('/^[\s\t]*[\r\n]+/', '', rtrim($content));
            $this->load($paths['html'], $this->docType, $content);
        }
        //只有 php 時直接建立空白文件
        else if (File::isExists($paths['php'])) {
            $this->parse(null);
        }
        //找不到主版頁面
        else if (!is_null($this->_lastContent)) {
            throw new Exception('找不到主版頁面: ' . $paths['html']);
        }

        //樣版不存在
        if (!$this->prototype) 
        {
            if (!isset($options['embed'])) 
            {
                //使用預設單元重新載入
                if ($name != self::DEFAULT_NAME) {
                    if ($this->isExists($class, self::DEFAULT_NAME) ||
                        $this->isExists($class, self::DEFAULT_MASTER)) {
                        $this->redirect();
                    }
                }

                //使用主版為預設單元
                else if ($name == self::DEFAULT_NAME && $this->isExists($class, self::DEFAULT_MASTER)) {
                    return call_user_func(array($this, __FUNCTION__), $class, self::DEFAULT_MASTER, $options);
                }
            }

            //沒有預設單元
            if ($this->debug) throw new Exception('沒有符合的單元頁面 ' . $paths['html']);
            else exit(header("HTTP/1.0 404 Not Found"));
        }
        //傳回頁面屬性
        else $properties = $this->find(self::SELECTOR_ATTR)->attrs();

        //拒絕存取單元
        if ($this->isUser($name) && isset($properties['access']) && Data\Variable::isFalse($properties['access'])) {
            if ($name != self::DEFAULT_NAME && $this->isExists($class, self::DEFAULT_NAME)) $this->redirect();
            if ($this->debug) throw new Exception('拒絕存取的單元頁面: ' . $paths['html']);
            else exit(header("http/1.1 403 Forbidden"));
        }
        //儲存單元資訊
        else {
            $this->infos[$name] = array(
                'paths' => $paths,
                'properties' => $properties
            );
        }

        //組合之前的子頁面
        if (!is_null($this->_lastContent)) {
            if (!$properties) throw new Exception('主版頁面 ' . $paths['html'] . ' 未設定屬性標籤');
            $this->assign(
                self::SELECTOR_ATTR, 
                array('replaceWith' => array($this->_lastContent))
            );
        }

        //搜集標題
        if (empty($properties['title'])) $properties['title'] = $this->find('title')->text();
        if (!empty($properties['title'])) array_push($this->_titles, $properties['title']);

        //搜集樣式
        if (isset($properties['styles'])) {
            $insertStyles = preg_split('/\s*,\s*/i', $properties['styles']);
            krsort($insertStyles);
            foreach($insertStyles as $_style) {
                array_unshift($this->_styles, $_style);
            }
        }

        //客戶端程式
        if (file_exists($paths['js'])) {
            $script = $this->script($paths['js-relative'], array(
                'head > link[rel="stylesheet"]:last + script:last' => 'after',
                'head > link[rel="stylesheet"]:last' => 'after',
                'head > script:last' => 'after',
                'head > meta:last' => 'after'
            ));
            $this->find('script[src="' . $paths['js-relative'] . '"]')->addClass(self::TOKEN_CLIENT_SCRIPT);
        }
        
        //崁入單元頁面
        foreach($this->find(self::SELECTOR_EMBED) as $target) {
            $target = pq($target);
            $attrs = $this->find($target)->attrs();

            $unit = new COM_Unit;
            $unit->debug = $this->debug;
            $unit->init($class, $attrs['name'], array('embed' => true));
            $this->assign(
                self::SELECTOR_EMBED . ':first', 
                array('replaceWith' => array($unit->content()))
            );
        }

        //搜尋主版頁面
        $this->find(self::SELECTOR_ATTR)->remove();
        if ($master = $this->_getMaster($class, $name, $properties)) {
            $this->_lastContent = $this->prototype->htmlOuter();
            $this->_lastContent = preg_replace('/^[\s\t]*[\r\n]*/', '', rtrim($this->_lastContent));
            return call_user_func(array($this, __FUNCTION__), $class, $master, $options);
        }

        //***********************************************************************************************************************//

        //改寫根路徑
        if (!isset($properties['base'])) $properties['base'] = $this->isFullPage();
        if (!Data\Variable::isFalse($properties['base']))
            $this->changeBase($paths['base']);
        
        //初始化程式
        $initializePath = $this->_buildUrl(Configure::$path['helper'] . '/' . self::INITIALIZE_PATH);
        $initialize = $this->find('script#' . self::INITIALIZE_ID);
        $unitScripts = $this->find('script.' . self::TOKEN_CLIENT_SCRIPT);
        if (!$initialize->length) {
            if (!isset($properties['init'])) $properties['init'] = $this->isFullPage();
            if (isset($properties['init']) && 
                !Data\Variable::isFalse($properties['init'])) {
                $script = $this->script($initializePath, null, true);
                $script = pq($script)->attr('id', self::INITIALIZE_ID)->htmlOuter();
                $initPlace = strtolower(Data\Arrays::value($properties, 'init'));
                switch($initPlace) 
                {
                    //top
                    case 'top':
                        $places = array('head > link[rel="stylesheet"]:last' => 'after');
                        if (!Data\Variable::isFalse($properties['base'])) {
                            $places['head > script:first'] = 'after';
                        }
                        $places['head > meta:last'] = 'after';
                        $this->script($script, $places);
                        break;

                    //bottom
                    case 'bottom':
                    default:
                        $this->script($script, array('body' => 'append'));
                        break;
                }
                $initialize = $this->find('script#' . self::INITIALIZE_ID);
            }
        }
        if ($initialize->length) 
        {
            //固定客戶端程式位置在初始化程式之後
            $insertScripts = $this->find('script');
            foreach($insertScripts as $script) {
                $script = pq($script);
                if ($script->is('.' . self::TOKEN_CLIENT_SCRIPT) || 
                    $script->is('#' . self::INITIALIZE_ID)) {
                    if (!$script->is('#' . self::INITIALIZE_ID)) {
                        $unitScripts->insertAfter($initialize);
                        $unitScripts->before(Util\Patch::NEWLINE);
                    }
                    break;
                }
            }
            $initialize->attr('src', $initializePath);
            $initialize->removeAttr('id');
        }

        if (!isset($options['embed']))
            $unitScripts->removeClass(self::TOKEN_CLIENT_SCRIPT);

        //設定標題、樣式
        if ($this->_titles) $this->title($this->_titles);
        foreach($this->_styles as $style) $this->style($style);
        foreach($this->find('link[rel="stylesheet"]') as $stylesheet) {
            $stylesheet = pq($stylesheet);
            $path = $stylesheet->attr('href');
            if (preg_match('/^\w+/', $path) &&
               !preg_match('/^\w+:\/\//', $path)) {
                $path = $this->_buildUrl($paths['base'] . '/' . $path);
                $stylesheet->attr('href', $path);
            }
        }

        //***********************************************************************************************************************//
        
        //啟動單元
        foreach($this->infos as $name => $info) 
        {
            if (File::isExists($info['paths']['php'])) {
                $parts = explode('/', $info['paths']['base']);
                array_push($parts, $name);
                foreach($parts as &$part) $part{0} = strtoupper($part{0});
                $className = implode('_', $parts);

                //從匿名函數內引用以開啟不同沙箱
                if (!function_exists('_unit_init_include')) {
                    function _unit_init_include(array $__args__) {
                        include_once($__args__['php']);
                        if (class_exists($__args__['class'], false)) {
                            call_user_func(array($__args__['class'], 'getInstance'));
                        }
                    }
                }
                _unit_init_include(array('php' => $info['paths']['php'], 'class' => $className));
            }
        }
        return $this;
    }

    /**
     * 傳回主版頁面名稱
     *
     * @param string $class
     * @param string $name
     * @param array $properties
     * @return string|null
     */
    protected function _getMaster($class, $name, $properties)
    {
        $master = null;

        if (isset($properties['master'])) {
            $master = !Data\Variable::isFalse($properties['master']) 
                ? $properties['master']
                : false;
        }

        //如果沒有設定主版頁面，而且非完整頁面
        if (is_null($master) && !$this->isFullPage()) 
        {
            //使用預設主版名稱
            if ($name != self::DEFAULT_MASTER) {
                $_paths = $this->getPaths($class, self::DEFAULT_MASTER);
                if (isset($_paths['html']) && File::isExists($_paths['html']))
                    $master = self::DEFAULT_MASTER;
            }
        }

        return $master;
    }

    /**
     * 傳回路徑資訊
     *
     * @param string $class
     * @param string $name
     * @return array
     */
    public function getPaths($class, $name) 
    {
        $paths = array ();

        //folders
        $paths['base'] = Configure::$path['unit'] . '/' . $class;
        $paths['external'] = $paths['base'] . '/' . self::FOLDER_EXTERNAL;
        $paths['client'] = $paths['external'] . '/' . self::FOLDER_CLIENT;

        //files
        foreach (array('html', 'php', 'js') as $type) 
        {
            $allowExts = array(
                'html' => self::EXTS_HTML,
                'php' => self::EXTS_PHP,
                'js' => self::EXTS_JS,
            );

            $parents = array(
                'html' => $paths['base'],
                'php' => $paths['external'],
                'js' => $paths['client'],
            );

            $_paths = $paths;
            if ($exts = preg_split('/\s*;\s*/', $allowExts[$type])) {
                foreach($exts as $i => $ext) 
                {
                    $filename = str_replace('*', $name, $ext);
                    $path = $parents[$type] . '/' . $filename;

                    $_paths[$type] = $path;

                    //make that relative path of JS
                    if ($type == 'js') {
                        $_paths['js-relative'] = $this->_buildUrl($_paths['js']);
                    }

                    //default, if not found
                    if ($i == 0) $paths = $_paths;

                    if (file_exists($path)) {
                        $paths = $_paths;
                        break;
                    }
                }
            }
        }

        //intl
        if (self::$intl) 
        {
            $intl = COM_Intl::getInstance();
            $default = $intl->getDefault();

            $current = $intl->getCurrent();
            if ($current != $default) {
                $fileSuffix = COM_Intl::FIELD_SYMBOL . $current;
                $ext = strrchr($paths['html'], '.');
                $search = '/(' . $ext . ')$/';
                $modify = $fileSuffix . '$1';
                $path = preg_replace($search, $modify, $paths['html']);
                if (file_exists($path)) 
                    $paths['html'] = $path;
            }
        }
        return $paths;
    }
    
    /**
     * 傳回單元是否存在
     *
     * @return boolean
     *
     */
    public function isExists($class, $name) 
    {
        $_paths = $this->getPaths($class, $name);
        if (isset($_paths['html']) && File::isExists($_paths['html']))
            return true;
        return false;
    }
    
    /**
     * 傳回是否為完整頁面
     *
     * @return boolean
     *
     */
    public function isFullPage() {
        if ($this->find('html')->length())
            return true;
        return false;
    }
    
    /**
     * 傳回目前頁面是否為前端單元
     *
     * @static
     * @param mixed $name 
     * @return boolean
     *
     */
    public static function isUser($name = null)
    {
        return self::isClass(self::DEFAULT_CLASS_USER, $name);
    }
    
    /**
     * 傳回目前頁面是否為後端單元
     *
     * @static
     * @param mixed $name 
     * @return boolean
     *
     */
    public static function isManager($name = null)
    {
        return self::isClass(self::DEFAULT_CLASS_MANAGER, $name);
    }

    /**
     * 傳回目前頁面是否為比對單元
     *
     * @static
     * @param mixed $class 
     * @param mixed $name 
     * @return boolean
     *
     */
    public static function isClass($class, $name = null)
    {
        $router = COM_Router::getInstance();
        if (is_null($name)) return $router->action == $class;
        return $router->action == $class && $router->unit == $name;
    }
    
    /**
     * 重新導向單元
     *
     * @static
     * @param mixed $class 
     * @param mixed $name 
     *
     */
    public function redirect($class = null, $name = self::DEFAULT_NAME)
    {
        $router = COM_Router::getInstance();
        if (is_null($class)) $class = $router->action;

        $url = self::isUser() ? './?' : './?' . COM_Router::FIELD_ACTION . '=' . $class . '&';
        $url .= self::FIELD_NAME . '=' . $name;
        Http\Response::redirect($url);
    }
    
    /**
     * 建立網站地圖
     *
     * @param mixed $class 
     * @return string
     *
     */
    public function buildSitemap($class = null, $rewriteEnabled = null)
    {
        $router = COM_Router::getInstance();
        if (is_null($class)) $class = $router->action;
        
        $sitemap = new Http\Sitemap;
        $intl = COM_Intl::getInstance();
        $setting = MOD_Setting::getInstance();
        $rewrite = Http\Rewrite::getInstance();

        $widget = new Http\Configure_Widget();
        $urlToStaticEnabled = is_bool($rewriteEnabled) 
            ? $rewriteEnabled 
            : $widget->isStaticEnabled();

        $exts = '{' . implode(',', preg_split('/\s*;\s*/', self::EXTS_HTML)) . '}';
        $path = Configure::$path['unit'] . '/' . $class . '/' . $exts;
        if ($files = glob($path, GLOB_BRACE)) {
            $template = new Template\Engine();

            $websiteUrl = $setting->value('websiteUrl');
            $args = $websiteUrl ? array(Text\Strings::setSuffix($websiteUrl, '/')) : array();
            $uri = call_user_func_array('Uri\Uri::factory', $args);

            foreach($files as $file) 
            {
                if (is_readable($file)) 
                {
                    //unit
                    $template->load($file);
                    $properties = $template->find(self::SELECTOR_ATTR)->attrs();
                    if (isset($properties['access']) && Data\Variable::isFalse($properties['access'])) continue;
                    $filename = File::getFilename($file);

                    //url
                    list ($name, $lang) = $intl->deLangField($filename);
                    $uri->setQuery(null);
                    if ($lang) $uri->query(Intl\Intl::FIELD_CHANGE . '=' . strtolower($lang));
                    $url = $uri->query(self::FIELD_NAME . '=' . $name)->getUri();
                    if ($urlToStaticEnabled)
                        $url = $rewrite->urlToStatic($url);

                    $sitemap->addUrl($url, array(
                        'lastmod' => date('Y-m-d', filemtime($file)),
                        'changefreq' => 'weekly',
                        'priority' => '1.0',
                    ));
                }
            }
        }

        return $sitemap->toXml();
    }
    
    /**
     * 傳回路徑的絕對 URL
     *
     * @param string $path
     * @return string
     */
    protected function _buildUrl($path)
    {
        if (!isset($this->_baseUrl)) $this->_baseUrl = Uri\Uri::factory()->getBase();
        $url = $this->_baseUrl . '/' . $path;
        return $url;
    }

    /**
     * 格式化輸出
     *
     * @param string $content
     * @return string
     */
    protected function _tidyOutput($content) 
    {
        //lines
        $content = preg_replace('/(<.+>)\s*[\r\n]{2,}/m', "$1\r\n", $content);
 
        //indent
        if (preg_match_all('/^<\w+[^\r\n]*/sm', $content, $matches)) {
            foreach ($matches[0] as $line) {
                if (preg_match_all('/([\s\t]+<.*)[\r\n\s]*' . preg_quote($line, '/') . '/i', $content, $matches2)) {
                    foreach ($matches2[1] as $prev) {
                        $prev = preg_replace('/[\r\n]*/', '', $prev);
                        if (preg_match('/^[\s\t]+/', $prev, $matches3)) {
                            $indent = $matches3[0];
                            $content = preg_replace('/^' . preg_quote($line, '/') . '/sm', $indent . rtrim($line), $content);
                        }
                    }
                }
            }
        }

        //doctype
        if (preg_match_all('/<!DOCTYPE.+>/i', $content, $matches)) {
            $doctype = $_doctype = $matches[0][0];
            $doctype = preg_replace('/("http:.+")/', "\r\n  $1", $doctype);
            $content = str_replace($_doctype, $doctype, $content);
        }

        return $content;
    }
    
    /**
     * 傳回樣版內容
     * 
     * @override
     * @return string
     */
    public function content()
    {
        //批次分配變數
        if (!empty($this->assigns))
            $this->assign($this->assigns);

        //傳回內容
        $content = parent::content();
        
        //修改錨點
        $content = str_ireplace('href="#"', 'href="javascript://#"', $content);

        //重寫鏈結
        $Rewrite = Http\Rewrite::getInstance();
        if ($Rewrite->hasOccurred()) $content = $Rewrite->convertToStatic($content);
        else if ($Rewrite->isIntl() && self::isUser()) 
            $content = $Rewrite->convertToDynamic($content);
        
        //格式化輸出
        if ($this->infos && ($info = Data\Arrays::getLast($this->infos))) {
            if (isset($info['properties']['tidy']) && 
                Data\Variable::isTrue($info['properties']['tidy']))
                $content = $this->_tidyOutput($content);
        }

        return $content;
    }
}

?>