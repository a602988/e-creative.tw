<?php

/**
 * ������~���ҥ~
 * 
 * @version 1.0.0 2013/11/02 20:12
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Model\Exception;

use Sewii\Exception;

class BadMethodCallException
    extends Exception\BadMethodCallException
    implements ExceptionInterface 
{}
