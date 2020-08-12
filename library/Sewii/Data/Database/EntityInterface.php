<?php

/**
 * 資料表物件
 *
 * @version 1.0.1 2013/12/04 15:33
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Data\Database;

use Countable;
use Serializable;
use IteratorAggregate;
use ArrayAccess;

interface EntityInterface
    extends Countable, Serializable, IteratorAggregate, ArrayAccess
{
    public function import(array $data);
    public function export();
    public function toArray();
    public function isExists($name);
}

?>