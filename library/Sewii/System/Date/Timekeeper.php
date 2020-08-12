<?php

namespace Sewii\System\Date;

/**
 * 計時器類別
 * 
 * @version v 1.0.0 2011/09/15 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Timekeeper
{
    private $_startTime = 0;
    private $_stopTime  = 0;

    /**
     * 建構子
     * 
     * @param boolean $start
     */
    public function __construct($start)
    {
        if ($start) $this->start();
    }
 
    /**
     * 傳回百萬分之一秒
     * 
     */
    function getMicrotime()
    {
        list ($USEC, $SEC) = explode(' ', microtime());
        return ((float) $USEC + (float) $SEC);
    }
 
    /**
     * 開始計時
     * 
     */
    function start()
    {
        $this->_startTime = $this->getMicrotime();
    }
 
    /**
     * 停止計時
     * 
     */
    function stop()
    {
        $this->_stopTime = $this->getMicrotime();
    }
    
    /**
     * 傳回經過時間
     * 
     * @param boolean $stop
     */
    function pasted($stop)
    {
        if ($stop) $this->stop();
        return ($this->_stopTime - $this->_startTime);
        return round (($this->_stopTime - $this->_startTime) * 1000);
    }
}

?>