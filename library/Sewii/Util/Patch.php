<?php

namespace Sewii\Util;

/**
 * 大補帖類別
 * 
 * @version v 1.0.0 2011/11/08 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Patch
{
    const NEWLINE = "\r\n";
}

require_once(__DIR__ . '/Patch/mbstring.php');
require_once(__DIR__ . '/Patch/json.php');

?>