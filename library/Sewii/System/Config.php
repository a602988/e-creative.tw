<?php

/**
 * 設定檔
 * 
 * @version 1.0.0 2013/01/18 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */

namespace Sewii\System;

use Sewii\Exception;
use Zend\Config\Config as ZendConfig;
use Zend\Config\Factory;

class Config extends ZendConfig
{
    /**
     * 建構子
     * 
     * {@inheritDoc}
     */
    public function __construct(array $array, $allowModifications = true)
    {
        parent::__construct($array, $allowModifications);
    }
    
    /**
     * 讀取器工廠
     * 
     * @param string $reader
     * @return ReaderInterface
     */
    public static function reader($reader)
    {
        $class = 'Zend\Config\Reader\\' . ucfirst($reader);
        if (!class_exists($class)) {
            throw new Exception\InvalidArgumentException(
                "沒有已定義的設定檔讀取器 {$reader}"
            );
        }
        return new $class;
    }
}

?>