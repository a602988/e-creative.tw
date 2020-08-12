<?php

/**
 * 使用者群組抽像模組
 * 
 * @version v 1.0.0 2012/08/23 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
 
 
namespace Spanel\Module\Model\User\Permission;

use Spanel\Module\Component\Model\DatabaseModel;
use Spanel\Module\Component\Model\ModelInterface;

abstract class Capability
    extends DatabaseModel
    implements ModelInterface
{
    const FULL      = 'FULL';
    const READ_ONLY = 'READONLY';
    const CUSTOM    = 'CUSTOM';
    const READ      = 'READ';
    const WRITE     = 'WRITE';
    const ADD       = 'ADD';
    const EDIT      = 'EDIT';
    const DELETE    = 'DELETE';
    const SEND      = 'SEND';
    const IMPORT    = 'IMPORT';
    const EXPORT    = 'EXPORT';
}

?>