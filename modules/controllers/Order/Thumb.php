<?php

use Sewii\System;

/**
 * 縮圖器元件
 * 
 * @version v 1.0.0 2012/03/27 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class COM_Thumb extends System\Singleton
{
    /**
     * 動作名稱
     * @const string
     */
    const NAME_ACTION = 'thumb';

    /**
     * 初始化
     * 
     * @return COM_Thumb
     */
    public function init()
    {
        $this->_preinitialize();
        include_once(Configure::$path['library'] . '/Widget/TimThumb/index.php');
        return $this;
    }
}

?>