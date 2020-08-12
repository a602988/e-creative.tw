<?php

/**
 * 會員群組資料實體
 * 
 * @version 1.0.0 2013/11/06 15:32
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Model\User\Group;

use Spanel\Module\Component\Model\AbstractEntity;

class Entity extends AbstractEntity
{
    protected $id;
    protected $created;
    protected $modified;
    protected $actived;
    protected $model;
    protected $alias;
    protected $name;
    protected $description;
    protected $xx;
}

?>