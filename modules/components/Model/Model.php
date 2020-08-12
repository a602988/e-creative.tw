<?php

/**
 * 模型抽象類別
 * 
 * @version 1.0.0 2013/10/30 13:39
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Model;

use ReflectionClass;
use Sewii\Text\Regex;
use Sewii\System\Singleton;
use Sewii\Data\Dataset\AbstractDataset;

abstract class Model extends Singleton
{
    /**
     * 模型命名空間
     *
     * @const string
     */
    const NAMESPACE_CONTROLLER = 'Spanel\Module\Model';

    /**
     * 工廠模式
     * 
     * @return Model
     */
    public static function factory($model) 
    {
        $model = ucfirst(Regex::replace('/^[^\w]+/', '', $model));
        $model = Regex::replace('%/([a-z])%i', function($matches) {
            return '\\' . strToUpper($matches[1]);
        }, $model);
        $model = sprintf('%s\%s', self::NAMESPACE_CONTROLLER, $model);

        if (!class_exists($model)) {
            throw new Exception\InvalidArgumentException("無法載入模型物件: $model");
        }

        $class = new ReflectionClass($model);
        $interface = ModelInterface::CLASS_NAME;
        if (!$class->implementsInterface($interface)) {
            throw new Exception\InvalidArgumentException("模型物件必須實作 {$interface} 介面: {$model}");
        }
        
        return $model::getInstance();
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
        $className = AbstractDataset::CLASS_NAME;
        return call_user_func_array("$className::factory", $args);
    }
}

?>