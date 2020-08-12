<?php

namespace Sewii\Http;

use Sewii\Util;
use Sewii\Text;

/**
 * 網站地圖類別
 * 
 * @version v 1.0.1 2012/05/18 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Sitemap
{
    /**
     * XML 編碼
     *
     * @var string
     */
    const XML_ENCODING = 'UTF-8';
    
    /**
     * XML 版本
     *
     * @var string
     */
    const XML_VERSION = '1.0';
    
    /**
     * 協議版本
     *
     * @var string
     */
    const PROTOCOL_VERSION = '0.9';
    
    /**
     * 目前的 url 清單
     *
     * @var string
     */
    protected $_urls = array();
    
    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toXml();
    }
    
    /**
     * 輸出為 XML
     *
     * @return string
     */
    public function toXml()
    {
        $nl = Util\Patch::NEWLINE;
        $xml  = '<?xml version="' . self::XML_VERSION . '" encoding="' . self::XML_ENCODING . '" ?>' . $nl;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/' . self::PROTOCOL_VERSION . '">' . $nl . $nl;
        foreach ($this->_urls as $url) {
            $xml .= '  <url>' . $nl;
            foreach($url as $k => $v) 
                $xml .= '    <' . $k . '>' . Text\Strings::html2Text($v) . '</' . $k . '>' . $nl;
            $xml .= '  </url>' . $nl . $nl;
        }
        $xml .= '</urlset>';
        return $xml;
    }
    
    /**
     * 新增頁面 URL
     *
     * @param string $url
     * @param array $option
     * @return void
     */
    public function addUrl($url, array $options = array())
    {
        $url = array('loc' => $url);
        $url += $options;
        array_push($this->_urls, $url);
    }
}

?>