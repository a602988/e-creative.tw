<?php

/**
 * 單元路徑物件
 * 
 * @version 1.6.8 2013/07/21 02:12
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Controller\Site;

use Sewii\Exception;
use Sewii\System\Registry;
use Sewii\Filesystem\File;
use Sewii\Text\Regex;
use Sewii\Uri\Uri;
use Sewii\Util\Browser;

class PathInfo
{
    /**#@+
     * 預設副檔名
     * @const string
     */
    const EXT_PHP  = Site::EXT_PHP;
    const EXT_HTML = '.html, .htm, .xml, .phtml';
    const EXT_CSS  = '.css, .xsl';
    const EXT_JS   = '.js, .jse';
    /**#@-*/

    /**#@+
     * 預設目錄
     * @const string
     */
    const PATH_SCRIPT = 'scripts';
    const PATH_THEME  = 'styles/parts';
    /**#@-*/
    
    /**
     * 作用中網站名稱
     *
     * @var string
     */
    protected $site;
    
    /**
     * 作用中單元名稱
     *
     * @var string
     */
    protected $unit;

    /**
     * 建構子
     */
    public function __construct($site, $unit)
    {
        $this->site = $site;
        $this->unit = $unit;
    }
    
    /**
     * 傳回網站名稱
     * 
     * @return string
     */
    public function getSite()
    {
        return $this->site;
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
     * 傳回根路徑
     * 
     * @return string
     */
    public function getBase()
    {
        $config = Registry::getConfig();
        $path = $config->path->site . '/' . $this->getSite();
        return $path;
    }
    
    /**
     * 傳回絕對根路徑
     * 
     * @todo 用 IE 開發工具摸擬 IE8、9 時，好像和真實的不太一樣，有待測試
     * @return string
     */
    public function getBaseHref()
    {
        // IE9 以下只支援 URL 格式?
        //if (Browser::msie() && Browser::version() <= 9) {
            return $this->getBaseUrl() . "/";
        //}
        
        $basePath = Site::getBasePath();
        $base = $this->getBase();
        $path = "$basePath/$base/";
        return $path;
    }
    
    /**
     * 傳回根 URL
     * 
     * @return string
     */
    public function getBaseUrl()
    {
        $baseUrl = Site::getBaseUrl();
        $base = $this->getBase();
        $url = "$baseUrl/$base";
        return $url;
    }
    
    /**
     * 傳回控制器路徑
     * 
     * @return string
     */
    public function getControllerPath()
    {
        $site  = $this->getSite();
        $path  = Unit::getControllerPath($site);
        return $path;
    }
    
    /**
     * 傳回腳本路徑
     * 
     * @return string
     */
    public function getScript()
    {
        return self::PATH_SCRIPT;
    }
    
    /**
     * 傳回主題路徑
     * 
     * @return string
     */
    public function getTheme()
    {
        return self::PATH_THEME;
    }
    
    /**
     * 傳回 HTML 路徑
     * 
     * @return string
     */
    public function getHtml()
    {
        $directory = $this->getBase();
        $extensions = self::EXT_HTML;
        $file = $this->getFile($directory, $extensions);
        return $file;
    }
    
    /**
     * 傳回 PHP 路徑
     * 
     * @return string
     */
    public function getPhp()
    {
        $directory = $this->getControllerPath();
        $extensions = self::EXT_PHP;
        $path = $this->getFile($directory, $extensions);
        $basename = File::getBasename($path);
        $newName = ucfirst($basename);
        $file = Regex::replace("/{$basename}$/", $newName, $path);
        return $file;
    }
    
    /**
     * 傳回 JS 路徑
     * 
     * @return string
     */
    public function getJs()
    {
        $directory = $this->getScript();
        $extensions = self::EXT_JS;
        $file = $this->getFile($directory, $extensions);
        return $file;
    }
    
    /**
     * 傳回 JS URL
     * 
     * @return string
     */
    public function getJsUrl()
    {
        $baseUrl = $this->getBaseUrl();
        $path = $this->getJs();
        $url = "$baseUrl/$path";
        return $url;
    }
    
    /**
     * 傳回 CSS 路徑
     * 
     * @return string
     */
    public function getCss()
    {
        $directory = $this->getTheme();
        $extensions = self::EXT_CSS;
        $file = $this->getFile($directory, $extensions);
        return $file;
    }
    
    /**
     * 傳回 CSS URL
     * 
     * @return string
     */
    public function getCssUrl()
    {
        $baseUrl = $this->getBaseUrl();
        $path = $this->getCss();
        $url = "$baseUrl/$path";
        return $url;
    }
    
    /**
     * 傳回 HTML 是否存在
     * 
     * @return boolean
     */
    public function isExistsHtml()
    {
        $path = $this->getHtml();
        return File::isExists($path);
    }
    
    /**
     * 傳回 PHP 是否存在
     * 
     * @return boolean
     */
    public function isExistsPhp()
    {
        $path = $this->getPhp();
        return File::isExists($path);
    }
    
    /**
     * 傳回 JS 是否存在
     * 
     * @return boolean
     */
    public function isExistsJs()
    {
        $base = $this->getBase();
        $path = $this->getJs();
        $path = "$base/$path";
        return File::isExists($path);
    }
    
    /**
     * 傳回 CSS 是否存在
     * 
     * @return boolean
     */
    public function isExistsCss()
    {
        $base = $this->getBase();
        $path = $this->getCss();
        $path = "$base/$path";
        return File::isExists($path);
    }
    
    /**
     * 傳回路徑是否為有效的 HTML 檔案
     * 
     * @return boolean
     */
    public static function isValidHtml($path)
    {
        return self::isValidFile($path, self::EXT_HTML);
    }
    
    /**
     * 傳回路徑是否為有效的 CSS 檔案
     * 
     * @return boolean
     */
    public static function isValidCss($path)
    {
        return self::isValidFile($path, self::EXT_CSS);
    }
    
    /**
     * 傳回路徑是否為有效的 JS 檔案
     * 
     * @return boolean
     */
    public static function isValidJs($path)
    {
        return self::isValidFile($path, self::EXT_JS);
    }
    
    /**
     * 傳回路徑是否為有效的 PHP 檔案
     * 
     * @return boolean
     */
    public static function isValidPhp($path)
    {
        return self::isValidFile($path, self::EXT_PHP);
    }
    
    /**
     * 傳回檔案名稱
     * 
     * @param string $path
     * @return boolean
     */
    public static function getFilename($path)
    {
        $filename = File::getFilename($path);
        $name = Regex::replace('/\-[a-zA-Z]{2}$/', '', $filename);
        return $name;
    }
    
    /**
     * 傳回檔案路徑
     * 
     * @param string $directory
     * @param string $extensions
     * @return string
     */
    protected function getFile($directory, $extensions)
    {
        $default = null;
        $extensions = Regex::split('/\s*,\s*/', $extensions);
        foreach ($extensions as $index => $extension) {
            $filename = str_replace('.', "{$this->getUnit()}.", $extension);
            $path = "{$directory}/$filename";
            if ($index == 0) $default = $path;
            if (File::isFile($path)) {
                return $path;
            }
        }
        return $default;
    }
    
    /**
     * 傳回路徑是否為有效的檔案
     * 
     * @param string $extensions
     * @return boolean
     */
    protected static function isValidFile($path, $extensions)
    {
        $basename = File::getBasename($path);
        $extensions = implode('|', Regex::split('/\s*,\s*/', $extensions));
        $extensions = str_replace('.', '', $extensions);
        return Regex::match('/^[\w\-\.]+$/', $basename)
            && File::isExtension($basename, $extensions) 
            && File::isFile($path);
    }
}

?>