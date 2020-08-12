<?php

/**
 * 單元控制器 (Initializer)
 * 
 * @version 1.0.2 2014/07/23 21:55
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2014 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Controller\Site\Common\Unit;

use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\Data\Json;
use Sewii\Cache\Cache;
use Sewii\Filesystem\File;
use Sewii\Uri\Http as HttpUri;
use Spanel\Module\Component\Controller\Site\Site;
use Spanel\Module\Component\Controller\Site\Unit;
use Spanel\Module\Component\Controller\Site\PathInfo;
use Spanel\Module\Component\Router\OrderRoute;
use Spanel\Module\Component\Router\SiteRoute;

class UnitInitializer extends Unit
{
    /**
     * 快取過期時間
     * 
     * @const integer
     */
    const CACHE_EXPIRTED_TIME = 86400;
    
    /**
     * 初始化程式檔名
     *
     * @var string
     */
    const INITIALIZER = 'initializer.js';
    
    /**
     * 載入事件
     * 
     * @return void
     */
    protected function onLoadLayout()
    {
        $pathInfo = $this->getPathInfo();
        $initializer = sprintf('%s/%s', $pathInfo->getBase(), self::INITIALIZER);
        if (File::isExists($initializer)) {
            $this->setHeaders();
            $baseUrl = $this->site->getBaseUrl();
            $commonBaseUrl = $pathInfo->getBaseUrl();
            $routeInfo = $this->getRouteInfo();
            include $initializer;
        }
    }
    
    /**
     * 傳回路由資訊
     * 
     * @todo 目前快取無法解決即時性問題，待實作。
     * @param boolean $updateCache
     * @return string
     */
    protected function getRouteInfo($updateCache = false)
    {
        $cache = $this->getCacheable();
        if (!$updateCache && isset($cache->routeInfo)) {
            return $cache->routeInfo;
        }

        $info = array();

        // Order
        $info['order'] = array(
            'pattern' => OrderRoute::getPattern(),
            'field' => array(
                'order' => OrderRoute::FIELD_ORDER,
            ),
        );

        // Site
        $info['site'] = array(
            'mode' => SiteRoute::MODE,
            'pattern' => SiteRoute::getPattern(),
            'field' => array(
                'site' => SiteRoute::FIELD_SITE,
                'intl' => SiteRoute::FIELD_INTL,
                'unit' => SiteRoute::FIELD_UNIT
            )
        );
        
        foreach (Site::getSites() as $site) {
            $info['site']['units'][$site] = Site::getUnits($site);
            $info['site']['defaults'][$site]['site'] = SiteRoute::getDefault('site', $site);
            $info['site']['defaults'][$site]['intl'] = SiteRoute::getDefault('intl', $site);
            $info['site']['defaults'][$site]['unit'] = SiteRoute::getDefault('unit', $site);
            if (!isset($info['site']['defaultSite'])) {
                $info['site']['defaultSite'] = SiteRoute::getDefault('site');
            }
        }

        $info = Json::encode($info, JSON_HEX_QUOT);
        $info = str_replace('"', '\"', $info);
        return $cache->routeInfo = $info;
    }

    /**
     * 設定表頭
     * 
     * @return Root
     */
    protected function setHeaders()
    {
        $maxage = self::CACHE_EXPIRTED_TIME;
        $expires = gmdate('D, d M Y H:i:s', time() + $maxage);
        $filename = self::INITIALIZER;

        // TODO: $response->sendHeader()
        header('Pragma: public');
        header("Cache-Control: maxage=$maxage");
        header("Expires: $expires GMT");
        header("Content-Disposition: inline; filename=$filename");
        header('Content-type: application/javascript');
    }
    
    /**
     * 傳回快取物件
     * 
     * @return Cache
     */
    protected function getCacheable()
    {
        if (isset($this->cache)) return $this->cache;
        $namespace = substr(md5(__CLASS__), 0, 12);
        $this->cache = Cache::filesystem($namespace);
        return $this->cache;
    }
    
    /**
     * 傳回 URL
     * 
     * @return string
     */
    public static function getUrl()
    {
        return sprintf('/%s%s/%s/%s', 
            SiteRoute::FIELD_SITE,
            self::getRequest()->getBaseUrl(),
            self::getSiteName(),
            self::getUnitName()
        );
    }
    
    /**
     * 傳回 URI
     * 
     * @return string
     */
    public static function getUri()
    {
        return sprintf('%s/%s/%s/%s', 
            self::getRequest()->getBasePath(),
            SiteRoute::FIELD_SITE,
            self::getSiteName(),
            self::getUnitName()
        );
    }
}

?>