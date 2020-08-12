<?php

use Sewii\System;
use Sewii\View\Template;
use Sewii\Http;

/**
 * 上傳器元件
 * 
 * @version v 1.0.0 2012/03/27 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class COM_Uploader extends System\Singleton
{
    /**
     * 初始化
     * 
     * @return void
     */
    public function init()
    {
        $this->_preinitialize();
        $base = Configure::$path['common'] . '/FlexUploader';

        //template
        $Template = new Template\Engine();
        $Template->debug = Configure::$debugMode;
        $Template->register($Template);
        $Template->load($base . '/uploader.html', 'xhtml');
        $Template->changeBase();
        $content = $Template->content();

        //uploader
        include_once($base . '/uploader.php');
        Uploader::getInstance();

        //display
        Http\Response::write($content);
    }
}

?>