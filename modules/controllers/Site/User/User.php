<?php

/**
 * 網站控制器 (User)
 * 
 * @version 1.0.1 2013/05/14 00:00
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Controller\Site\User;

use Sewii\System\Registry;
use Sewii\Filesystem\File;
use Spanel\Module\Component\Controller\Site\Site;

class User extends Site
{
    /**#@+
     * 預設值
     * @const mixed
     */
    const DEFAULT_UNIT = Site::DEFAULT_UNIT;
    const DEFAULT_INTL = Site::DEFAULT_INTL;
    const ALLOW_DEFAULT_UNIT = Site::ALLOW_DEFAULT_UNIT;
    const CONTENT_TYPE = Site::CONTENT_TYPE;
    /**#@-*/
}

?>