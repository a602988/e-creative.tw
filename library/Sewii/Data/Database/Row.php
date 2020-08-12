<?php

/**
 * 資料列物件
 *
 * @version 1.0.0 2013/11/12 17:36
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Data\Database;

use Zend\Db;

class Row extends Db\RowGateway\RowGateway
{
    /**
     * 填充資料
     * 
     * @param array|EntityInterface $entity
     * @return Row
     */
    public function fill($entity, $rowExistsInDatabase = false)
    {
        if ($entity instanceof EntityInterface) {
            $entity = $entity->toArray();
        }
        return $this->populate($entity, $rowExistsInDatabase);
    }
    
    /**
     * 綁定資料
     * 
     * @param array|EntityInterface $entity
     * @return Row
     */
    public function bind($entity)
    {
        return $this->fill($entity, true);
    }
}

?>