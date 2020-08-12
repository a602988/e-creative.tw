<?php

/**
 * 回應物件
 * 
 * @version 1.3.0 2013/06/28 08:47
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Http;

use Sewii\Exception;
use Zend\Http\PhpEnvironment\Response as ZendResponse;

class Response extends ZendResponse
{
    /**
     * 傳送狀態表頭
     *
     * @param integer $statusCode
     * @return Response
     */
    public function sendHttpStatus($statusCode)
    {
        $this->setStatusCode($statusCode);
        $this->sendHeaders();
        return $this;
    }

    /**
     * 重新導向
     *
     * @param $url
     * @return Response
     */
    public function redirect($url)
    {
        $this->getHeaders()->addHeaderLine('Location', $url);
        $this->sendHttpStatus(self::STATUS_CODE_302);
        return $this;
    }
    
    /**
     * 輸出內容
     *
     * @param mixed $content
     * @return Response
     */
    public function write($content)
    {
        $this->setContent($content);
        $this->send();
        return $this;
    }
    
    /**
     * 以新行輸出內容
     *
     * @param mixed $content
     * @return Response
     */
    public function writeln($content)
    {
        $content .= PHP_EOL;
        $this->write($content);
        return $this;
    }
    
    /**
     * 停止輸出
     *
     * @param mixed $content
     * @return void
     */
    public function stop($content = null)
    {
        if ($content !== null) {
            $this->write($content);
        }
        exit;
    }
}

?>