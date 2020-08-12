<?php

/**
 * 資料庫模型抽象類別
 * 
 * @version 1.0.0 2013/10/30 13:39
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Model;

use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\Data\Database\Database;
use Sewii\Data\Database\Sql;
use Sewii\Data\Database\Table;
use Sewii\Data\Database\EntityInterface;

abstract class DatabaseModel extends Model
{
    /**#@+
     * 欄位表
     * 
     * @const string
     */
    const COLUMN_PRIMARY_KEY = 'id';
    const COLUMN_CREATE_DATE = 'created';
    const COLUMN_MODIFY_DATE = 'modified';
    const COLUMN_ACTIVED     = 'actived';
    const COLUMN_INDEX       = 'indexed';
    /**#@-*/
    
    const ENTITY_CLASS_NAME = 'Entity';

    /**
     * 初始化
     * 
     * @return DatabaseModel
     */
    public function initialize()
    {
        $this->preinitialize();

        if (!defined('static::TABLE')) {
            throw new Exception\RuntimeException('資料庫模型物件必須定義 TABLE');
        }

        return $this;
    }
    
    /**
     * 資料庫工廠
     * 
     * @param mixed $profile
     * @return Database
     */
    public function database($profile = null) 
    {
        $database = Database::getInstance($profile);
        return $database;
    }
    
    /**
     * 查詢工廠
     * 
     * @param string $table
     * @return Sql
     */
    public function sql($table = null) 
    {
        $table = $table ?: static::TABLE;
        $sql = $this->database()->sql($table);
        return $sql;
    }
    
    /**
     * 資料表工廠
     * 
     * @param string $table
     * @return Table
     */
    public function table($name = null) 
    {
        $name = $name ?: static::TABLE;
        $table = $this->database()->table($name);
        return $table;
    }
    
    /**
     * 資料列工廠
     * 
     * @param array|EntityInterface $entity
     * @param string $primaryKeyColumn
     * @param string $table
     * @return Row
     */
    public function row($entity = null, $primaryKeyColumn = null, $table = null)
    {
        $table = $table ?: static::TABLE;
        $primaryKeyColumn = $primaryKeyColumn ?: self::COLUMN_PRIMARY_KEY;

        if ($entity instanceof EntityInterface) {
            $entity = $entity->export();
        }
        
        $row = $this->database()->row($primaryKeyColumn, $table);

        if (is_array($entity)) {
            $rowExistsInDatabase = isset($entity[$primaryKeyColumn]);
            $row->fill($entity, $rowExistsInDatabase);
        }

        return $row;
    }
    
    /**
     * 結果集工廠
     * 
     * @param array|EntityInterface $entity
     * @return Row
     */
    public function result($entity = null)
    {
        $entity = $this->entity($data);
    }
    
    /**
     * 資料集工廠
     * 
     * @param mixed $arg1 [, mixed $... ]
     * @return Dataset
     */
    public function dataset()
    {
        $args = func_get_args();
        
        if (empty($args)) {
            $sql = $this->sql();
            $select = $sql->select();
            $dataset = parent::dataset($select, $sql);
            if ($this->hasEntity()) {
                $entity = $this->entity();
                $dataset->setPrototype($entity);
            }
            return $dataset;
        }
        
        return call_user_func_array('parent::dataset', $args);
    }
    
    /**
     * 傳回資料實體類別
     * 
     * @return string
     */
    protected function getEntityClassName()
    {
        $path = dirname(get_called_class());
        $name = static::ENTITY_CLASS_NAME;
        return "$path\\$name";
    }
    
    /**
     * 傳回實料實體是否定義
     * 
     * @return boolean
     */
    public function hasEntity()
    {
        $className = $this->getEntityClassName();
        return class_exists($className);
    }
    
    /**
     * 資料實體工廠
     * 
     * @param mixed $data 
     * @throws Exception\RuntimeException 
     * @return mixed
     */
    public function entity($data = null) 
    {
        if (!$this->hasEntity()) {
            throw new Exception\RuntimeException("沒有已定義的資料實體: $className");
        }
        $className = $this->getEntityClassName();
        $entity = new $className($data);
        return $entity;
    }
    
    /**
     * 儲存資料
     * 
     * @param array $data
     * @return integer
     */
    public function save(array $data)
    {
        $entity = $this->entity($data);
        $row = $this->row($entity);
        $row->save();
        return $row;
    }
    
    /**
     * 刪除資料
     * 
     * @param array $data
     * @return integer
     */
    public function delete(array $data)
    {
        $entity = $this->entity($data);
        $row = $this->row($entity);
        $row->delete();
        return $row;
    }
    
    /**
     * 尋找資料
     * 
     * @param mixed $where
     * @return integer
     */
    public function find($where = null)
    {
        // Use primary key if pass a numeric
        if (is_numeric($where)) {
            $where = array(static::COLUMN_PRIMARY_KEY => $where);
        }

        $table = $this->table();
        $select = $table->select($where);
        return $select;
    }

    public function min($field = null)
    {
        $field = $field ?: self::COLUMN_PRIMARY_KEY;
    }

    public function max($field = null)
    {
        $field = $field ?: self::COLUMN_PRIMARY_KEY;
    }

    public function neighbor($id, $order, $where)
    {
    }

    public function equals($row, $or)
    {
    }

    public function sorts($index, $to, $where)
    {
    }

    /**
     * 呼叫子
     *
     * @param  string $name
     * @param  array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        // findBy(field)
        if ($matched = Regex::match('/^findBy([\w]+)$/i', $name)) {
            $field = lcfirst($matched[1]);
            $value = Arrays::getFirst($args);
            $where = array($field => $value);
            return $this->find($where);
        }

        throw new Exception\BadMethodCallException(
            sprintf('呼叫未定義的方法: %s::%s()', get_called_class(), $name)
        );
    }
}

?>