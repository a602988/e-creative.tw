<?php

/**
 * 根路徑器
 * 
 * @version 1.0.1 2013/02/07 16:11
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Spanel\Module\Controller\Order;

use Sewii\Exception;
use Sewii\Uri\Uri;
use Sewii\Filesystem\File;
use Spanel\Module\Component\Controller\Order;

class Rooter extends Order
{
    /**
     * 快取過期時間
     * 
     * @const integer
     */
    const CACHE_EXPIRTED_TIME = 604800;
    
    /**
     * 改寫根路徑的欄位名稱
     * 
     * @const string
     */
    const FIELD_PATH = 'path';

    /**
     * 表示頁面根 URL
     * 
     * @var string
     */
    protected $baseUrl;

    /**
     * 表示改寫目的地路徑
     * 
     * @var string
     */
    protected $path;

    /**
     * 初始化
     * 
     * @return Root
     */
    protected function initialize()
    {
    print_r($this->request->param);
        return;
        $this->baseUrl = Uri::factory()->getBaseUrl();
        if ($path = $this->request->getParam(self::FIELD_PATH)) {
            $this->path = str_replace('.', '/', trim($path, '/'));
        }

        if (is_null($this->path)) {
            throw new Exception\RuntimeException('無法取得改寫目的地路徑');
        }

        $this->output();
        return $this;
    }

    /**
     * 設定表頭
     * 
     * @return Root
     */
    protected function setHeaders()
    {
        $maxage = self::CACHE_EXPIRTED_TIME;
        $expires = gmdate('D, d M Y H:i:s', time() + self::CACHE_EXPIRTED_TIME);
        $filename = strtolower(File::getFilename(__FILE__)) . '.js';

        header("Pragma: public");
        header("Cache-Control: maxage=$maxage");
        header("Expires: $expires GMT");
        header("Content-Disposition: inline; filename=$filename");
        //header('Content-type: application/javascript');
    }

    /**
     * 輸出到客戶端
     * 
     * @return Root
     */
    protected function output()
    {
        $this->setHeaders();

        print <<<SCRIPT

        if (document.domain) 
        {
            //Address
            (function() {
                if (document.location.hash) {
                    var hash = document.location.hash.replace(/^#/, '');
                    if (hash.match(/^\//)) document.location.replace(hash);
                }
            })();

            //Server side
            window.__serverside__ = {
                baseUrl: '{$this->baseUrl}',
                path: '{$this->path}'
            };

            document.write('<base href="{$this->baseUrl}/{$this->path}/" />');
        }

SCRIPT;
    }
}

?>