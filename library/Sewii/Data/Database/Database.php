<?php

/**
 * 資料庫物件
 *
 * @version 1.6.0 2013/12/15 23:17
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Data\Database;

use Sewii\Exception;
use Sewii\Type\Arrays;

class Database
{
    /**
     * 設定檔集合
     * 
     * @var array
     */
    protected static $configs = array();

    /**
     * 實體容器
     * 
     * @var array
     */
    protected static $instances = array();

    /**
     * 橋接器
     * 
     * @var Adapter
     */
    protected $adapter;
    
    /**
     * 建構子
     * 
     * @return void
     */
    protected function __construct(array $config) 
    {
        if (isset($config['driver'])) {
            switch (strtolower($config['driver'])) {
                case 'mysqli':
                    // Only for mysqli buffered queries
                    $config['options']['buffer_results'] = true;
                    break;
            }
        }
        
        try {
            $this->adapter = new Adapter($config);
        } catch (\Exception $ex) {
            throw new Exception\InvalidArgumentException(
                sprintf("資料庫於初始化時發生失敗 (%s)", $ex->getMessage())
            );
        }
    }

    /**
     * 傳回實體
     * 
     * @param string|array $profile
     * @param array $configs
     * @return Database
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public static function getInstance($profile = null, array $configs = null) 
    {
        if (is_array($profile)) {
            ksort($profile);
            $key = md5(implode('', $profile));
            $configs = array($key => $profile);
            $profile = $key;
        }

        if (self::$instances) {
            $profile = $profile ?: Arrays::getFirstKey(self::$instances);
            if (isset(self::$instances[$profile])) {
                return self::$instances[$profile];
            }
        }

        if (!($configs = $configs ?: self::$configs)) {
            throw new Exception\RuntimeException('沒有已定義的資料庫設定檔');
        }

        if (!isset($configs[$profile = $profile ?: Arrays::getFirstKey($configs)])) {
            throw new Exception\InvalidArgumentException("沒有符合的資料庫設定檔: $profile");
        }

        $config = $configs[$profile];
        $instance = new static($config);
        self::$instances[$profile] = $instance;
        return $instance;
    }
    
    /**
     * 查詢工廠
     * 
     * @param string $name
     * @return Sql
     */
    public function sql($table = null)
    {
        $adapter = $this->adapter;
        $sql = new Sql($adapter);
        if ($table !== null) {
            $sql->setTable($table);
        }
        return $sql;
    }
    
    /**
     * 資料表工廠
     * 
     * @param string $name
     * @return Table
     */
    public function table($name)
    {
        $adapter = $this->adapter;
        $table = new Table($name, $adapter);
        return $table;
    }
    
    /**
     * 資料列工廠
     * 
     * @param string $primaryKeyColumn
     * @param string $table
     * @return Row
     */
    public function row($primaryKeyColumn, $table)
    {
        $adapter = $this->adapter;
        $table = new Row($primaryKeyColumn, $table, $adapter);
        return $table;
    }
    
    /**
     * 運算式工廠
     * 
     * @param mixed $expression 
     * @param mixed $parameters 
     * @param array $types 
     * 
     * return Expression
     */
    public function expression($expression = '', $parameters = null, array $types = array())
    {
        $expression = new Expression($expression, $parameters, $types);
        return $expression;
    }
    
    /**
     * 設定檔
     * 
     * @param array $configs
     * @return void
     */
    public static function configure(array $configs)
    {
        self::$configs = $configs;
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
        switch ($name) {
            default:
                $callable = array($this->adapter, $name);
                $returned = call_user_func_array($callable, $args);
                return $returned;
        }
    }
}

?>