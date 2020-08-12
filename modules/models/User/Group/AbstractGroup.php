<?php

/**
 * 使用者群組抽像模組
 * 
 * @version 1.0.0 2013/11/01 17:19
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Model\User\Group;

use Spanel\Module\Component\Model\DatabaseModel;
use Spanel\Module\Component\Model\ModelInterface;

abstract class AbstractGroup
    extends DatabaseModel
    implements ModelInterface
{
    /**
     * 資料表
     * 
     * @var string
     */
    const TABLE = 'sp_user_groups';
}

?>