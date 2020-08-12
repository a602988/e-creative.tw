<?php

use Sewii\System;
use Sewii\Http;

/**
 * 壓縮器元件
 * 
 * @version v 1.0.0 2012/05/11 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class COM_Sitemap extends System\Singleton
{
    /**
     * 動作名稱
     * @const string
     */
    const NAME_ACTION = 'sitemap';

    /**
     * 初始化
     * 
     * @return COM_Sitemap
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
        $sitemap = $setting->value('sitemap');
        header('Content-Type: text/xml; charset=utf-8');
        Http\Response::write($sitemap);
    }
}

?>