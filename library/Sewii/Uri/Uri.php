<?php

/**
 * URI 抽像類別
 * 
 * @version 1.2.6 2013/07/21 05:38
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Uri;

use Sewii\Exception;
use Sewii\System\Accessors\AbstractAccessors;

abstract class Uri 
    extends AbstractAccessors 
{
    final protected function __construct() {}

    /**
     * 工廠模式
     *
     * @param string $uri
     * @return Sewii\Uri\Uri
     * @throws Sewii\Exception\InvalidArgumentException
     */
    public static function factory($uri = 'http')
    {
        $className = null;
        $parts = explode(':', $uri, 2);
        switch ($scheme = strtolower($parts[0])) {
            case 'http':
            case 'https':
                $className = __NAMESPACE__ . '\\Http';
                if (empty($parts[1])) {
                    $uri = null;
                }
                break;
            default:
                throw new Exception\InvalidArgumentException('不支援的 URI 協議: ' . $scheme . '');
        }

        return $className::factory($uri);
    }

    /**
     * __toString
     *
     * @see getUri()
     * @return string
     */
    public function __toString()
    {
        return $this->getUri();
    }

    /**
     * 傳回 URI
     *
     * @return string
     */
    abstract public function getUri();
}

?>