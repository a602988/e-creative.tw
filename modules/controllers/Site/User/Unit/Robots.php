<?php

/**
 * 單元控制器 (Robots)
 * 
 * @version 1.0.1 2014/08/23 07:51
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
use Spanel\Module\Component\Controller\Site\Unit;

class UnitRobots extends Unit
{
    /**
     * 頁面載入事件
     * 
     * return void
     */
    protected function onLoadLayout()
    {
        $this->output();
    }
    
    /**
     * 輸出內容
     * 
     * return void
     */
    protected function output()
    {
        header('Content-Type: text/plain; charset=utf-8');
        
        $output  = 'User-agent: *'   . PHP_EOL;
        $output .= 'Disallow: '      . PHP_EOL;
        // $output .= 'Disallow: /works/detail/*/*' . PHP_EOL;
        // $output .= 'Crawl-delay: 10' . PHP_EOL;
        
        $this->response->stop($output);
    }
}

?>