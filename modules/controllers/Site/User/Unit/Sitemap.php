<?php

/**
 * 單元控制器 (Sitemap)
 * 
 * @version 1.0.3 2014/08/07 14:55
 * @author JOE (joe@ecreative.tw)
 * @copyright (c) 2014 ecreative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Controller\Site\User\Unit;

use Sewii\Exception;
use Sewii\System\Registry;
use Sewii\System\Config;
use Sewii\Cache\Cache;
use Sewii\Filesystem\Path;
use Sewii\Filesystem\File;
use Sewii\Net\Mail;
use Sewii\Text\Regex;
use Sewii\Text\Strings;
use Sewii\Type\Variable;
use Sewii\Data\Hashtable;
use Sewii\Data\Json;
use Sewii\View\Template\Template;
use Sewii\Data\Dataset\AbstractDataset;
use Sewii\Uri\Http as HttpUri;
use Spanel\Module\Component\Controller\Site\Unit;
use Spanel\Module\Component\Router\RouteConvert;

class UnitSitemap extends Unit
{
    /**
     * XML 編碼
     *
     * @const string
     */
    const XML_ENCODING = 'UTF-8';
    
    /**
     * XML 版本
     *
     * @const string
     */
    const XML_VERSION = '1.0';
    
    /**
     * 協議版本
     *
     * @const string
     */
    const PROTOCOL_VERSION = '0.9';
    
    /**
     * 目前的 URL 清單
     *
     * @var string
     */
    protected $urls = array();
    
    /**
     * 頁面載入事件
     * 
     * return void
     */
    protected function onLoadLayout()
    {
        $this->build();
        $this->output();
    }
    
    /**
     * 輸出內容
     * 
     * return void
     */
    protected function output()
    {
        header('Content-Type: text/xml; charset=utf-8');
        $xml = $this->toXml();
        $this->response->stop($xml);
    }
    
    /**
     * 建立地圖
     * 
     * return void
     */
    protected function build()
    {
        $content = File::read($this->baseUrl);
        $template = new Template;
        $template->build($content);
        
        $that = $this;
        $template['a[href]']->each(function($index, $item) use (&$that) {
            $item = $this->site->view->object($item);
            $href = $item->attr('href');
            if (Regex::isMatch('/^\.\.\//', $href)) {
                
                $uri = HttpUri::factory();
                $dynamic = RouteConvert::toDynamic($href);
                $parts = HttpUri::parse($dynamic);
                $uri->setQuery(isset($parts['query']) ? $parts['query'] : null);
                $url = $uri->getUri();
                
                $priority =  substr_count($url, '/') > 3 ? '0.8' : '1.0';
                
                $that->addUrl($url, array(
                    'lastmod' => date('Y-m-d', strtotime('last monday')),
                    'changefreq' => 'daily',
                    'priority' => $priority,
                ));
            }
        });
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
        $key = $url;
        $url = array('loc' => $url);
        $url += $options;
        $this->urls[$key] = $url;
    }
    
    /**
     * 輸出為 XML
     *
     * @return string
     */
    public function toXml()
    {
        $xml  = sprintf('<?xml version="%s" encoding="%s" ?>', self::XML_VERSION, self::XML_ENCODING);
        $xml .= sprintf('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/%s">', self::PROTOCOL_VERSION);
        
        $map = array();
        foreach ($this->urls as $url) {
            $xml .= "<url>";
            foreach($url as $tag => $val) {
                $val = Strings::html2Text($val);
                $xml .= sprintf('<%s>%s</%s>', $tag, $val, $tag);
            }
            $xml .= "</url>";
        }
        $xml .= '</urlset>';
        return $xml;
    }
}

?>