<?php

use Sewii\System;
use Sewii\Data;
use Sewii\Http;

/**
 * Robots 元件
 * 
 * @version v 1.0.0 2012/05/11 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class COM_Robot extends System\Singleton
{
    /**
     * 動作名稱
     * @const string
     */
    const NAME_ACTION = 'sitemap';

    /**
     * 初始化
     * 
     * @return COM_Robot
     */
    public function init()
    {
        $this->_preinitialize();
        $this->_show();
        return $this;
    }
    
    /**
     * 輸出
     * 
     * @return COM_Sitemap
     */
    protected function _show()
    {
        $setting = MOD_Setting::getInstance();
        header('Content-Type: text/plain; charset=utf-8');
        
        if ($enabled = $setting->value('robotsEnabled')) {
            if (Data\Variable::isFalse($enabled)) {
                Http\Response::writeln('User-agent: *');
                Http\Response::write('Disallow: /');
            }
        }

        //排除機器人
        if ($excludeUserAgent = $setting->value('robotsExcludeUserAgent')) {
            $lines = preg_split('/\r\n|\n/', $excludeUserAgent);
            if ($lines) {
                foreach($lines as $line) {
                    $userAgent = $line;
                    Http\Response::writeln('User-agent: ' . $userAgent);
                    Http\Response::writeln('Disallow: /');
                    Http\Response::writeln('');
                }
            }
        }

        Http\Response::writeln('User-agent: *');

        //排除頁面
        if ($excludePages = $setting->value('robotsExcludePages')) {
            $lines = preg_split('/\r\n|\n/', $excludePages);
            if ($lines) {
                foreach($lines as $line) {
                    $path = $line;
                    Http\Response::writeln('Disallow: ' . $path);
                }
            }
        }
        else Http\Response::writeln('Disallow: ');

        //探測頻率
        if ($crawlDelay = $setting->value('robotsCrawlDelay')) {
            Http\Response::writeln('Crawl-delay: ' . $crawlDelay);
        }
    }
}

?>