<?php

/**
 * 管理者群組類別
 * 
 * @version 1.0.0 2013/11/06 15:32
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Model\User\Group;

use Sewii\Type\Arrays;
use Sewii\Text\Regex;

class Manager extends AbstractGroup
{
    /**
     * 模組名稱
     * 
     * @const string
     */
    const MODEL = __CLASS__;
    
    /**
     * 傳回資料集
     * 
     * @return mixed
     */
    public function getDataset()
    {
        //return $this->dataset(array(array(0), array(1), array(2), array(3), array(4), array(5), array(6), array(7), array(9), array(10)));
        $dataset = $this->dataset();
        $select = $dataset->getSource();
        //$select->order('id DESC');
        return $dataset;
    }
    
    /**
     * 搜尋資料集
     * 
     * @param mixed $dataset 
     * @param mixed $keyword 
     * @param mixed $fields 
     * @param mixed $params 
     * @return mixed
     */
    public function search($dataset, $keyword, $fields = null, $params = null)
    {
        $entity = $this->entity();
        $fields = (array) $fields;
        $select = $dataset->source;
        $select->where(function($where) use($entity, $keyword, $fields) {
            foreach ($fields as $field) {
                if ($entity->isExists($field)) {
                    $where->like($field, "%$keyword%")->or;
                }
            }
        });
        return $this;
    }
    
    /**
     * 排列資料集
     * 
     * @param mixed $dataset 
     * @param mixed $field 
     * @param mixed $direction 
     * @param mixed $charset 
     * @return mixed
     */
    public function order($dataset, $field, $direction = null, $charset = null)
    {
        
        $direction = strtoupper($direction) ?: 'ASC';
        $isSafe = Regex::isMatch('/^[\w\.]+/', $field)
              and Regex::isMatch('/^[a-z]+/i', $direction)
              and (Regex::isMatch('/^[a-z\-]+/i', $charset) || $charset);
        
        $entity = $this->entity();
        if ($isSafe && $entity->isExists($field)) {
            $select = $dataset->source;
            $order = "$field $direction";
            if ($charset !== null) {
                $sql = $dataset->sql;
                $platform = $sql->getAdapter()->getPlatform();
                $field = $platform->quoteIdentifier($field);
                $charset = $platform->quoteValue($charset);
                $order = $this->database()->expression("CONVERT($field USING $charset) $direction");
            }
            $select->order($order);
        }
        return $this;
    }
    
    public function sort()
    {
    }
    
    public function toggle($field, $id)
    {
        if ($data = $this->find($id)->current()) {
            if (isset($data->$field)) {
                $value = $data->$field;
                $map = array(
                    '0' => '1',
                    '1' => '0',
                    'y' => 'n',
                    'n' => 'y',
                    'Y' => 'N',
                    'N' => 'Y'
                );
                
                if (isset($map[$value])) {
                    $result = $map[$value];
                    $this->save(array(
                        self::COLUMN_PRIMARY_KEY => $id,
                        $field => $result
                    ));
                    return $result;
                }
            }
        }
        return null;
    }
}

?>