<?php

namespace Sewii\Http;

use Sewii\Data;
use Sewii\Util;
use Sewii\Filesystem\File;
use Sewii\Exception;

/**
 * 分散式配置檔類別
 * 
 * @version v 1.1.3 2012/05/18 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Configure
{
    /**
     * 註解符號
     * 
     * @const string
     */
    const SYMBOL_MARK = '##!';
    
    /**
     * 標籤符號
     * 
     * @const string
     */
    const SYMBOL_LABEL = '##@';
    
    /**
     * 開始標籤樣式
     * 
     * @const string
     */
    const PATTERN_LABEL_START = '##@<%s>';
    
    /**
     * 結束標籤樣式
     * 
     * @const string
     */
    const PATTERN_LABEL_END   = '##@</%s>';
    
    /**
     * 預設設定檔名
     * 
     * @const string
     */
    const DEFAULT_FILENAME = '.htaccess';
    
    /**
     * 目前設定檔路徑
     * 
     * var string
     */
    protected $_path;
    
    /**
     * 目前設定檔內容
     * 
     * @var string
     */
    protected $_content;

    /**
     * 建構子
     * 
     * @param string $path
     * @return void
     * @throw Sewii\Exception\RuntimeException
     */
    public function __construct($path = '.') 
    {
        $path = $path . '/' . self::DEFAULT_FILENAME;
        $path = preg_replace('/\\\/', '/', $path);
        $path = preg_replace('/^\.\//', '', $path);
        $path = preg_replace('/\/\//', '/', $path);
        $this->_path = $path;

        if (!File::isExists($path)) {
            if (@file_put_contents($path, null) === false) {
                throw new Exception\RuntimeException('無法建立分散式配置檔案 ' . $path);
            }
        }

        if (File::isExists($path))
            @chmod($path, 0666);
    }
    
    /**
     * 標記註解
     *
     * @param string $label 不分大小寫
     * @return integer
     *
     */
    public function mark($label = null)
    {
        $marked = 0;
        if ($content = $this->read($label)) {
            $symbolOfLastLabel = substr(self::SYMBOL_LABEL, -1);
            $mark = preg_quote(self::SYMBOL_MARK, '/');
            $search = '/^(?!\s*' . $mark . '?' . $symbolOfLastLabel . '?|\s*#(?=[^#])|\s*[\r\n])(\s*)(.+)/m';
            $modify = '$1' . self::SYMBOL_MARK . '$2';
            $content = preg_replace($search, $modify, $content, -1, $replaced);
            if ($replaced) {
                $this->write($content, $label);
                $marked = $replaced;
            }
        }
        return $marked;
    }
    
    /**
     * 取消標記註解
     *
     * @param string $label 不分大小寫
     * @return integer
     *
     */
    public function unmark($label = null)
    {
        $unmarked = 0;
        if ($content = $this->read($label)) {
            $mark = preg_quote(self::SYMBOL_MARK, '/');
            $search = '/^(\s*)' . $mark . '(.+)/m';
            $modify = '$1$2';
            $content = preg_replace($search, $modify, $content, -1, $replaced);
            if ($replaced) {
                $this->write($content, $label);
                $unmarked = $replaced;
            }
        }
        return $unmarked;
    }
    
    /**
     * 傳回參數
     *
     * @param string $name 不分大小寫
     * @return mixed
     *
     */
    public function getParam($name)
    {
        $param = null;
        if ($source = $this->read()) {
            if (preg_match_all('/^[\t ]*(' . $name . ')\s+([^\r\n]+)/ism', $source, $matches)) {
                list ($matcheds, $key, $originalValue) = $matches;
                $originalValue = Data\Arrays::getLast($originalValue);
                $value = trim($originalValue);
                $param = $value;
            }
        }
        return $param;
    }

    /**
     * 寫入參數
     *
     * @param string $name 不分大小寫
     * @param string $value
     * @return boolean
     *
     */
    public function setParam($name, $value)
    {
        if ($source = $this->read()) {
            if (preg_match_all('/^[\t ]*(' . $name . ')\s+([^\r\n]+)/ism', $source, $matches)) {
                list ($matcheds, $key, $originalValue) = $matches;
                
                $assigns = array();
                foreach ($matcheds as $index => $matched) {
                    $marker = '{' . md5(uniqid()) . '}';
                    $source = preg_replace('/^([\t ]*)' . $name . '\s+[^\r\n]+/ism', '$1' . $marker, $source, 1);
                    $assigns[$marker] = ltrim($matched);
                    if ($index == count($matcheds) - 1) {
                        $assigns[$marker] =  $name . ' ' . $value;
                    }
                }

                if ($assigns) {
                    $source = strtr($source, $assigns);
                    return $this->_write($source);
                }
            }
        }
        return false;
    }

    /**
     * 讀取內容
     *
     * @param string $label 不分大小寫
     * @return string
     *
     */
    public function read($label = null, $indent = 2)
    {
        $source = $this->_read();

        //by label
        if (!is_null($label)) {
            $startLabel = preg_quote(sprintf(self::PATTERN_LABEL_START, $label), '/');
            $endLabel = preg_quote(sprintf(self::PATTERN_LABEL_END, $label), '/');
            $pattern = '/([\t ]*)' . $startLabel . '(.*)' . $endLabel . '/isU';
            if (preg_match_all($pattern, $source, $matches)) {
                list ($matcheds, $originalIndent, $inner) = $matches;
                $index = Data\Arrays::getLast(array_keys($matcheds));
                $indent = $originalIndent[$index] . str_repeat(' ', $indent);
                $inner = ltrim(rtrim($inner[$index]), "\r\n");
                $source = preg_replace('/^' . $originalIndent[$index] . '([^\s]+)/m', $indent . '$1', $inner);
                $source = preg_replace('/^' . $indent . '/m', '', $source);
            }
            else $source = null;
        }
        
        return $source;
    }
    
    /**
     * 寫入內容
     *
     * @param string|boolean $label 不分大小寫, 傳入 true 時將附加至檔案結尾
     * @param string $content
     * @param integer $indent
     * @return boolean
     * @throw Sewii\Exception\RuntimeException
     *
     */
    public function write($content, $label = null, $indent = 2)
    {
        //by label
        if (!is_null($label)) {
            if ($source = $this->read()) {
                $startLabel = preg_quote(sprintf(self::PATTERN_LABEL_START, $label), '/');
                $endLabel = preg_quote(sprintf(self::PATTERN_LABEL_END, $label), '/');
                $pattern = '/([\t ]*)' . $startLabel . '(.*)' . $endLabel . '/isU';
                if (preg_match_all($pattern, $source, $matches)) {
                    list ($matcheds, $originalIndent, $inner) = $matches;

                    $assigns = array();
                    foreach ($matcheds as $index => $matched) {
                        $marker = '{' . md5(uniqid()) . '}';
                        $search = '/(' . $startLabel . ')(' . preg_quote($inner[$index], '/') . ')(' . $endLabel . ')/i';
                        $modify = '$1' . $marker . '$3';
                        $source = preg_replace($search, $modify, $source, 1);
                        $assigns[$marker] = $inner[$index];
                        if ($index == count($matcheds) - 1) {
                            $indent = $originalIndent[$index] . str_repeat(' ', $indent);
                            $content = ltrim(rtrim($content), "\r\n");
                            $content = preg_replace('/^(.)/m', $indent . '$1', $content);
                            $content = Util\Patch::NEWLINE . $content . Util\Patch::NEWLINE . $originalIndent[$index];
                            $assigns[$marker] =  $content;
                        }
                    }
                    
                    if ($assigns) {
                        $content = strtr($source, $assigns);
                    }
                }
                else $content = $source;
            }
        }
        
        $append = ($label === true) ? true: false;
        return $this->_write($content, $append);
    }

    /**
     * 傳回是否可寫入
     *
     * @return boolean
     *
     */
    public function isWritable()
    {
        return is_writable($this->_path);
    }
    
    /**
     * 傳回是否可讀取
     *
     * @return boolean
     *
     */
    public function isReadable()
    {
        return is_readable($this->_path);
    }
    
    /**
     * 讀取內容內部方法
     *
     * @return string
     * @throw Sewii\Exception\RuntimeException
     *
     */
    protected function _read() 
    {
        if (!$this->isReadable()) 
            throw new Exception\RuntimeException('無法讀取分散式配置檔案 ' . $this->_path);

        if (!is_null($this->_content)) return $this->_content;
        if (($content = file_get_contents($this->_path)) !== false) {
            $this->_content = strval($content);
        }
        return $this->_content;
    }
    
    /**
     * 寫入內容內部方法
     *
     * @param string $content
     * @param boolean $append
     * @return boolean
     * @throw Sewii\Exception\RuntimeException
     *
     */
    protected function _write($content, $append = false)
    {
        if (!$this->isWritable()) 
            throw new Exception\RuntimeException('無法寫入分散式配置檔案 ' . $this->_path);

        $flags = ($append === true) ? FILE_APPEND : 0;
        if (file_put_contents($this->_path, $content, $flags) !== false) {
            $this->_content = $content;
            return true;
        }
        return false;
    }
}

?>