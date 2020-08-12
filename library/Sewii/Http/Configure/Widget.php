<?php

namespace Sewii\Http\Configure;

use Zend\Validator;
use Sewii\Util;
use Sewii\Http;
use Sewii\Data;

/**
 * 分散式配置檔設定工具類別
 * 
 * @version v 1.4.0 2012/05/18 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Widget extends Http\Configure
{
    /**
     * Hotlink 標籤名稱
     * 
     * @const string
     */
    const LABEL_HOTLINK = 'Hotlink';
    
    /**
     * Hotlink 清單標籤名稱
     * 
     * @const string
     */
    const LABEL_HOTLINK_LIST = 'Hotlink-list';
    
    /**
     * Hotlink 副檔名標籤名稱
     * 
     * @const string
     */
    const LABEL_HOTLINK_EXTS = 'Hotlink-exts';
    
    /**
     * 浮水印標籤名稱
     * 
     * @const string
     */
    const LABEL_WATERMARK = 'watermark';
    
    /**
     * 靜態化網址標籤名稱
     * 
     * @const string
     */
    const LABEL_STATIC = 'Rewrites';
    
    /**
     * Hotlink 副檔名清單
     * 
     * @const string
     */
    const DEFAULT_HOTLINK_EXTENSIONS = 'jpg, jpeg, gif, png, bmp, swf, flv, pdf, doc, docx, xls, xlsx, ppt, pptx, mp3, mp4, wma, wmv, rar, zip, gz, tar, exe';

    /**
     * 設定標籤狀態
     * 
     * @param string $label
     * @param boolean $enabled
     * @return boolean
     */
    public function setLabelEnabled($label = null, $enabled)
    {
        $changed = 0;
        if ($this->isWritable()) {
            $changed = Data\Variable::isTrue($enabled) 
                ? $this->unmark($label) 
                : $this->mark($label);
        }
        return (bool)$changed;
    }

    /**
     * 設定浮水印狀態
     * 
     * @param boolean $enabled
     * @return boolean
     */
    public function setWatermarkEnabled($enabled)
    {
        return $this->setLabelEnabled(self::LABEL_WATERMARK, $enabled);
    }
    
    /**
     * 設定 HotLink 狀態
     * 
     * @param boolean $enabled
     * @return boolean
     */
    public function setHotLinkEnabled($enabled)
    {
        return $this->setLabelEnabled(self::LABEL_HOTLINK, $enabled);
    }
    
    /**
     * 設定靜態化網址狀態
     * 
     * @param boolean $enabled
     * @return boolean
     */
    public function setStaticEnabled($enabled)
    {
        return $this->setLabelEnabled(self::LABEL_STATIC, $enabled);
    }
    
    /**
     * 傳回靜態化網址狀態
     * 
     * @return boolean
     */
    public function isStaticEnabled()
    {
        if ($this->isReadable() &&
            ($hotLlink = $this->read(self::LABEL_STATIC))) {
            return preg_match('/^\s*RewriteRule/im', $hotLlink) ? true : false;
        }
        return false;
    }
    
    /**
     * 傳回 HotLink 狀態
     * 
     * @return boolean
     */
    public function isHotLinkEnabled()
    {
        if ($this->isReadable() &&
            ($hotLlink = $this->read(self::LABEL_HOTLINK))) {
            return preg_match('/^\s*RewriteRule/im', $hotLlink) ? true : false;
        }
        return false;
    }

    /**
     * 取出 HotLink 清單
     * 
     * @return string
     */
    public function getHotLinkList()
    {
        $list = array();
        if ($this->isReadable() &&
            ($hotLlinkList = $this->read(self::LABEL_HOTLINK_LIST)) &&
            preg_match_all('/\(www\\\\.\)\?([^\s]+)/', $hotLlinkList, $matches)) {
            if ($list = $matches[1]) {
                foreach($list as &$host) {
                    $unquote = '/\\\([\.\\\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:\-]+)/';
                    $host = preg_replace($unquote, '$1', $host);
                }
            }
        }

        array_push($list, '');
        $list = implode(Util\Patch::NEWLINE, $list);
        return $list;
    }
    
    /**
     * 設定 HotLink 清單
     * 
     * @param string $list
     * @return boolean
     */
    public function setHotLinkList($list)
    {
        if ($list = preg_split('/\r\n|\n/', trim($list))) {
            $validator = new Validator\Hostname(Validator\Hostname::ALLOW_ALL);
            $pattern = 'RewriteCond %%{HTTP_REFERER} !^https?://(www\.)?%s [NC]';
            foreach($list as $key => &$host) {
                $host = trim($host);
                if ($validator->isValid($host)) {
                    $host = preg_quote($host);
                    $host = sprintf($pattern, $host);
                    continue;
                }
                unset($list[$key]);
            }
            $list = array_unique($list);
            $list = implode(Util\Patch::NEWLINE, $list);
            if ($this->isWritable()) {
                return $this->write($list, self::LABEL_HOTLINK_LIST);
            }
        }
        return false;
    }
    
    /**
     * 設定 HotLink 副檔名
     * 
     * @param string $exts
     * @return boolean
     */
    public function setHotLinkExts($exts = null)
    {
        if ($this->isReadable() &&
            ($hotLlinkExts = $this->read(self::LABEL_HOTLINK_EXTS))) {
            $pattern = 'RewriteCond %%{REQUEST_URI} .+\.(%s)$ [OR]';

            if (is_null($exts)) $exts = self::DEFAULT_HOTLINK_EXTENSIONS;
            $exts = preg_quote($exts, '/');
            $exts = preg_split('/\s*(,|; )\s*/', $exts);
            $exts = implode('|', $exts);
            $exts = sprintf($pattern, $exts);
            
            if ($this->isWritable()) {
                return $this->write($exts, self::LABEL_HOTLINK_EXTS);
            }
        }
        return false;
    }
}

?>