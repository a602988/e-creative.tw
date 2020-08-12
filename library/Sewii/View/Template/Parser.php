<?php

/**
 * 樣版解析器
 * 
 * @version 1.0.2 2013/05/26 22:10
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Sewii\View\Template;

use phpQuery;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Text\Strings;

class Parser
{
    /**#@+
     * 文件類型定義
     * @const string
     */
    const DOCTYPE_HTML5 = '<!DOCTYPE html>';
    const DOCTYPE_HTML4 = '<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">';
    const DOCTYPE_XHTML = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
    /**#@-*/

    /**
     * 已轉換內容標誌
     * 
     * @const string
     */
    const TO_PARSABLE_TOKEN = '<!--TO-PARSABLE-TOKEN-->';
    
    /**
     * 解析方法
     *
     * @param string $content
     * @param string $contentType
     * @param string $encoding
     * @return Template\Object
     * @throws Exception\Exception
     */
    public static function parse($content, $contentType = self::DEFAULT_CONTENT_TYPE, $encoding = self::DEFAULT_ENCODING)
    {
        $content = self::toParsable($content, $contentType);

        try {
            switch (strtolower($contentType)) {
                case TEMPLATE::CONTENT_TYPE_HTML  : $prototype = phpQuery::newDocumentHTML($content);  break;
                case TEMPLATE::CONTENT_TYPE_XHTML : $prototype = phpQuery::newDocumentXHTML($content); break;
                case TEMPLATE::CONTENT_TYPE_XML   : $prototype = phpQuery::newDocumentXML($content);   break;
                default                           : $prototype = phpQuery::newDocument($content);      break;
            }

            if ($encoding) {
                $prototype->document->encoding = $encoding;
            }

            $object = new Object($prototype);
            return $object;
        }
        catch (\Exception $ex) {
            throw new Exception\Exception($ex->getMessage());
        }
    }
    
    /**
     * 轉換為可解析內容
     *
     * @todo Do not use isMatch
     * @param string $content
     * @param string $contentType
     * @return boolean
     */
    public static function toParsable($content, $contentType)
    {
        $pattern = '#' . Regex::quote(self::DOCTYPE_HTML5, '#') . '#i';
        if (Regex::isMatch($pattern, $content)) {

            //Modify the Doctype
            $content = Regex::replace($pattern, self::getDoctype($contentType), $content);

            //Modify the ContentType
            $search = '#<meta\s+.*charset=["\']?([\w\-]*)["\']?[^/]*(/)?\s*>#i';
            $modify = '<meta http-equiv="Content-Type" content="text/html; charset=$1"$2>';
            $content = Regex::replace($search, $modify, $content);
            
            //Add a token
            $content .= self::TO_PARSABLE_TOKEN;
        }
        return $content;
    }
    
    /**
     * 轉換為原始內容
     *
     * @todo Do not use isMatch
     * @param string $content
     * @param string $contentType
     * @return boolean
     */
    public static function toOriginal($content)
    {
        $pattern = '#' . Regex::quote(self::TO_PARSABLE_TOKEN, '#') . '#i';
        if (Regex::isMatch($pattern, $content)) {

            //Modify the Doctype
            $content = Regex::replace('/<!DOCTYPE[^>]*>/i', self::DOCTYPE_HTML5, $content);

            //Modify the ContentType
            $search = '#<meta\s+.*content=.*charset=([\w\-]*)[^/]*(/)?\s*>#i';
            $modify = '<meta charset="$1"$2>';
            $content = Regex::replace($search, $modify, $content);
            $content = Regex::replace('#"/>#', '" />', $content, 1);
            
            //Remove the token
            $content = str_replace(self::TO_PARSABLE_TOKEN, '', $content);
        }
        return $content;
    }
    
    /**
     * 傳回文件類型
     *
     * @param string $contentType
     * @return string
     */
    protected static function getDoctype($contentType)
    {
        switch (strtolower($contentType)) {
            case TEMPLATE::CONTENT_TYPE_XML:   return self::DOCTYPE_XHTML;
            case TEMPLATE::CONTENT_TYPE_XHTML: return self::DOCTYPE_XHTML;
            case TEMPLATE::CONTENT_TYPE_HTML:  return self::DOCTYPE_HTML4;
        }
    }
}

?>