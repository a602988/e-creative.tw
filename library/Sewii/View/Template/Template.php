<?php

/**
 * 樣版引擎類別
 * 
 * @version 1.6.5 2013/06/14 22:45
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */

namespace Sewii\View\Template;

use ReflectionClass;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Text\Strings;
use Sewii\Type\Arrays;
use Sewii\Filesystem\File;

class Template extends AbstractTemplate
{
    const CONTENT_TYPE_HTML  = 'html';
    const CONTENT_TYPE_XHTML = 'xhtml';
    const CONTENT_TYPE_XML   = 'xml';
    const DEFAULT_CONTENT_TYPE = self::CONTENT_TYPE_XHTML;
    const DEFAULT_ENCODING = 'UTF-8';
    const DEBUG_TITLE = 'Debug mode - ';
    const TITLE_DELIMITER = ' - ';
    const TITLE_APPEND = 1;
    const TITLE_PREPEND = 2;
    const TITLE_REPLACE = 4;
    const FIELD_PARAMS = 'params';
    const SINGLE_TAGS = 'area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr';
    protected $removeXmlTag = true;
    protected $contentType = self::DEFAULT_CONTENT_TYPE;
    protected $encoding = self::DEFAULT_ENCODING;
    protected $debug = true;
    protected $repair = true;
    protected $document;
    protected $path;
    protected $source;
    protected $quirks;
    protected $tagLeft = '{[(';
    protected $tagRight = ')]}';
    protected $assigns = array();
    protected $states = array();
    protected static $instances = array();

    /**
     * 建構子
     * 
     * @param string $contentType
     * @param string $encoding
     */
    public function __construct($contentType = self::DEFAULT_CONTENT_TYPE, $encoding = self::DEFAULT_ENCODING)
    {
        $this->setContentType($contentType);
        $this->setEncoding($encoding);
        $this->setInstance(md5(uniqid()));
    }
    
    /**
     * 載入樣板檔案
     *
     * @param string $path
     * @return Template
     * @throws Exception\InvalidArgumentException
     */
    public function load($path)
    {
        if (!File::isFile($path) || !File::isReadable($path)) {
            throw new Exception\InvalidArgumentException("無法開啟樣版檔案: $path");
        }
        
        $this->path = $path;
        $content = File::read($path);
        $instance = $this->build($content);
        return $instance;
    }
    
    /**
     * 建立樣板物件
     *
     * @param string $content
     * @return Template
     * @throws Exception\InvalidArgumentException
     */
    public function build($content)
    {
        $contentType = $this->getContentType();
        $encoding = $this->getEncoding();

        try {
            if ($document = $this->create($content, $contentType, $encoding)) {
                $document->setContext($this);
                $this->setDocument($document);
                $this->source = $content;
            }
        }
        catch (Exception\RuntimeException $ex)
        {
            //嘗試修復
            $message = sprintf('無法解析 %s 樣版: %s (%s)', $contentType, $this->getPath(), $ex->getMessage());
            $exception = new Exception\RuntimeException($message);
            if (!$this->tryToRepair($content, $exception)) {
                throw new $exception;
            }
        }
        return $this;
    }

    /**
     * 建立新文件
     *
     * @todo 是否和 $this->object() 有矛盾??
     * @param string $content
     * @param string $contentType
     * @param string $encoding
     * @return Object
     * @throws Exception\RuntimeException
     */
    public static function create($content, $contentType = self::DEFAULT_CONTENT_TYPE, $encoding = self::DEFAULT_ENCODING) 
    {
        try {
            return Parser::parse($content, $contentType, $encoding);
        }
        catch (\Exception $ex) {
            throw new Exception\RuntimeException($ex->getMessage());
        }
    }
    
    /**
     * 分配樣板變數
     * 
     * 以非標準 DOM 模式直接分配變數及值到原始檔。
     * 這個方法本身並不實作，而是在 toString() 輸出時進行。
     *
     * @param string|array $name
     * @param mixed $value
     * @return Template
     */
    public function assign($name, $value = null) 
    {
        if ($value === null) {
            if (is_array($name)) {
                foreach ($name as $key => $val) {
                    $self = array($this, __FUNCTION__);
                    call_user_func($self, $key, $val);
                }
            }
        }
        else {
            $variable = $this->tagLeft . $name . $this->tagRight;
            $this->assigns['<!--' . $variable . '-->'] = $value;
            $this->assigns[$variable] = $value;
        }
        return $this;
    }

    /**
     * 插入樣版變數
     *
     * @param string $id
     * @param string $value
     * @return Template
     */
    public function insert($id, $value)
    {
        $variable = sprintf(
            '<input type="hidden" id="%s" value="%s" />', 
            Strings::html2Text($id), 
            Strings::html2Text($value)
        );
        
        $nl = PHP_EOL;
        $target = $this('body');
        $document = $this->getDocument();

        //From target append
        if ($target->length) {
            $variable = $variable . $nl;
            $target->append($variable);
        }
        //From document append
        else {
            $variable = $variable . $nl;
            $document->append($variable);
        }
        return $this;
    }
    
    /**
     * 設定樣版標題
     *
     * @todo 改成和 initializer.js 的 title 方法一樣
     * @param string|array $text
     * @param boolean $flag TITLE_REPLACE | TITLE_PREPEND | TITLE_APPEND
     * @param boolean $ignoreQuirks
     * @param string $delimiter
     * @return Template
     */
    public function title($text, $flags = null, $ignoreQuirks = false, $delimiter = self::TITLE_DELIMITER)
    {
        if ($this->isQuirks() && !$ignoreQuirks) return $this;
        if (!is_array($text)) $text = array($text);
        if ($flags === null) $flags = self::TITLE_REPLACE;
        
        // Title
        $target = $this('title');
        if (!$target->length) {
            $title = $this->element('title')->render();
            $target = $this('title');
        }
        
        // Prepend
        if ($flags & self::TITLE_PREPEND) {
            if ($original = $target->text()) {
                array_push($text, $original);
            }
        }
        // Append
        else if ($flags & self::TITLE_APPEND) {
            if ($original = $target->text()) {
                array_unshift($text, $original);
            }
        }

        $title = implode($delimiter, $text);
        $target->text($title);
        return $this;
    }

    /**
     * 元素工廠
     *
     * @param string $name
     * @return Element
     */
    public function element($name)
    {
        return Element\Element::factory($name, $this);
    }

    /**
     * 物件工廠
     *
     * @param mixed $object
     * @return Object
     */
    public function object($object = null)
    {
        return new Object($object, $this);
    }

    /**
     * 推遲器工廠
     *
     * @param mixed $arg1 [, mixed $... ]
     * @return Renderer
     */
    public function deferrer()
    {
        $instance = new Deferrer($this);
        if ($args = func_get_args()) {
            $callable = array($instance, 'find');
            return call_user_func_array($callable, $args);
        }
        return $instance;
    }

    /**
     * 傳回渲染器
     *
     * @param mixed $arg1 [, mixed $... ]
     * @return Renderer
     */
    public function renderer()
    {
        $key = __FUNCTION__;
        if (!isset($this->states[$key])) {
            $this->states[$key] = new Renderer($this);
        }

        if ($args = func_get_args()) {
            $callable = array($this->states[$key], 'factory');
            return call_user_func_array($callable, $args);
        }

        return $this->states[$key];
    }

    /**
     * 傳回表單物件
     *
     * @param mixed $arg1 [, mixed $... ]
     * @return Form
     */
    public function form()
    {
        $key = __FUNCTION__;
        if (!isset($this->states[$key])) {
            $this->states[$key] = new Form($this);
        }
        return $this->states[$key];
    }

    /**
     * 傳回多媒體物件
     *
     * @param mixed $arg1 [, mixed $... ]
     * @return Media
     */
    public function media()
    {
        $key = __FUNCTION__;
        if (!isset($this->states[$key])) {
            $this->states[$key] = new Media($this);
        }
        return $this->states[$key];
    }

    /**
     * 傳回 Tidy 物件
     *
     * @return Tidy
     */
    public function tidy()
    {
        $key = __FUNCTION__;
        if (!isset($this->states[$key])) {
            $this->states[$key] = new Tidy($this);
        }
        return $this->states[$key];
    }

    /**
     * 尋找選擇器
     * 
     * @param string $selector
     * @return Object
     */
    public function find($selector)
    {
        $document = $this->getDocument();
        return $document->find($selector);
    }
    
    /**
     * 建構實體
     *
     * @return Template
     */
    public static function newInstance()
    {
        $args = func_get_args();
        $reflect  = new ReflectionClass(__CLASS__);
        $instance = $reflect->newInstanceArgs($args);
        return $instance;
    }

    /**
     * 設定實體介面
     * 
     * @param string|integer $name
     * @return Template
     */
    public function setInstance($name)
    {
        self::$instances[$name] = $this;
        return $this;
    }

    /**
     * 傳回實體介面
     * 
     * @param string $name
     * @return Template
     */
    public static function getInstance($name = null) 
    {
        // Default if not set
        if (is_null($name)) {

            // Should be last?
            if ($first = Arrays::getFirst(self::$instances)) {
                return $first;
            }
        }

        if (isset(self::$instances[$name])) {
            return self::$instances[$name];
        }

        return null;
    }
    
    /**
     * 註冊 namespace
     *
     * @param string $prefix
     * @param string $uri
     * @return mixed
     */
    public function registerNamespace($prefix, $uri)
    {
        $document = $this->getDocument();
        return $document->xpath->registerNamespace($prefix, $uri);
    }
    
    /**
     * 是否去除 XML 標籤
     *
     * @return boolean
     */
    public function isRemoveXmlTag()
    {
        return $this->removeXmlTag;
    }
    
    /**
     * 設定去除 XML 標籤
     *
     * @param boolean $value
     * @return Template
     */
    public function setRemoveXmlTag($value)
    {
        $this->removeXmlTag = $value;
        return $this;
    }
    
    /**
     * 傳回樣板變數左標籤
     *
     * @return boolean
     */
    public function getTagLeft()
    {
        return $this->tagLeft;
    }
    
    /**
     * 傳回樣板變數右標籤
     *
     * @return boolean
     */
    public function getTagRight()
    {
        return $this->tagRight;
    }
    
    /**
     * 傳回是否在容錯模式中
     *
     * @return boolean
     */
    public function isQuirks()
    {
        return $this->quirks;
    }
    
    /**
     * 傳回樣板路徑
     *
     * @return boolean
     */
    public function getPath()
    {
        return $this->path;
    }
    
    /**
     * 傳回樣板原始碼
     *
     * @param string $value
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }
    
    /**
     * 是否開啟除錯模式
     *
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }
    
    /**
     * 設定除錯模式
     *
     * @param boolean $value
     * @return Template
     */
    public function setDebug($value)
    {
        $this->debug = $value;
        return $this;
    }
    
    /**
     * 是否開啟除錯模式
     *
     * @return boolean
     */
    public function isRepair()
    {
        return $this->repair;
    }
    
    /**
     * 設定修復模式
     *
     * @param boolean $value
     * @return Template
     */
    public function setRepair($value)
    {
        $this->repair = $value;
        return $this;
    }
    
    /**
     * 傳回文件類型
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }
    
    /**
     * 設定文件類型
     *
     * @param string $value
     * @return Template
     * @throws Exception\InvalidArgumentException
     */
    public function setContentType($value)
    {
        $value = strtolower($value);
        $allowTypes = array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_XHTML, self::CONTENT_TYPE_XML);
        if (!in_array($value, $allowTypes)) {
            throw new Exception\InvalidArgumentException("無效的文件類型參數: $value");
        }
        $this->contentType = $value;
        return $this;
    }
    
    /**
     * 傳回文件編碼
     *
     * @return string
     */
    public function getEncoding()
    {
        return $this->encoding;
    }
    
    /**
     * 設定文件編碼
     *
     * @param string $value
     * @return Template
     */
    public function setEncoding($value)
    {
        $this->encoding = $value;
        if ($document = $this->getDocument()) {
            $document->document->encoding = $value;
        }
        return $this;
    }
    
    /**
     * 傳回文件物件
     *
     * @return Object
     */
    public function getDocument()
    {
        return $this->document;
    }
    
    /**
     * 設定文件物件
     *
     * @param Object $value
     * @return Template
     */
    public function setDocument($value)
    {
        if (!$value instanceof Object) {
            throw new Exception\InvalidArgumentException('無效的文件物件: ' . get_class($value));
        }
        $this->document = $value;
        return $this;
    }

    /**
     * 輸出樣版
     * 
     * @param boolean $isExit
     * @return void
     */
    public function display($isExit = false)
    {
        print $this->toString();
        if ($isExit) exit;
    }

    /**
     * 傳回樣版內容
     * 
     * @return string
     */
    public function toString()
    {
        $content = '';
        if ($document = $this->getDocument()) {
        
            //Execute the deferred methods
            $this->deferrer()->executeAll();

            //Output the content
            $content = $document->markupOuter();
            $this->implementAssign($content);

            //Remove the XML tag
            if ($this->isRemoveXmlTag()) {
                $this->removeXmlTag($content);
            }
        }
        return $content;
    }
    
    /**
     * 字串子
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }
    
    /**
     * 傳回是否為 HTML5
     * 
     * @return boolean
     */
    public function isHmtl5()
    {
        return Regex::isMatch('/<!DOCTYPE html>/i', $this->source);
    }
    
    /**
     * 實作分配樣板變數
     * 
     * @param string $content
     * @return Template
     */
    protected function implementAssign(&$content)
    {
        if ($this->assigns) {
            $content = strtr($content, $this->assigns);
        }
        
        //刪除未分配變數
        $matcher = Regex::quote($this->tagLeft, '/') . '.+' . Regex::quote($this->tagRight, '/');
        $content = Regex::replace('/' . '<!--' . $matcher . '-->' . '/', '', $content);
        $content = Regex::replace('/' . $matcher . '/', '', $content);

        return $this;
    }

    /**
     * 去除 XML 標籤
     *
     * @param string $content
     * @return Template
     */
    protected function removeXmlTag(&$content)
    {
        $pattern = '/[\r\n\s]*<\?xml(.*)\?>[\r\n\s]*/i';
        $content = Regex::replace($pattern, '', $content);
        return $this;
    }

    /**
     * 嘗試修復 XHTML 的樣版解析錯誤
     *
     * 當開啟修復功能時這個方法會嘗試將樣板重新改以 HTML 內容解析
     *
     * @param string $content
     * @param \Exception $exception
     * @return boolean
     */
    protected function tryToRepair($content, $exception = null)
    {
        $currentType = $this->getContentType();
        $allowTypes = array(self::CONTENT_TYPE_XHTML, self::CONTENT_TYPE_XML);
        if ($this->isRepair() && in_array($currentType, $allowTypes)) {
            try {
                $contentType = self::CONTENT_TYPE_HTML;
                $encoding = $this->getEncoding();
                $this->removeXmlTag($content);
                if ($document = self::create($content, $contentType, $encoding)) {
                    $this->setDocument($document);
                    $this->setContentType($contentType);
                    $this->source = $content;
                    
                    //於 Title 標籤提示錯誤
                    if ($this->isDebug()) {
                        if ($exception instanceof \Exception) {
                            $message = $exception->getMessage();
                            $this->title(self::DEBUG_TITLE . $message);
                        }
                    }
                    
                    $this->quirks = true;
                    return true;
                }
            }
            catch (Exception $ex) {}
        }
        return false;
    }
}

?>