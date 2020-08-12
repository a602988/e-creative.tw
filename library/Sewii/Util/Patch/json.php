<?php

use Sewii\Data;

/*
 * json_encode
 * PHP < 5.2.0
 * @see http://tw.php.net/manual/en/function.json-encode.php
 */
if (!function_exists('json_encode'))
{
    function json_encode ($value, $options = 0)
    {
        return Data\Json::encode($value);
    }
}

/*
 * json_decode
 * PHP < 5.2.0
 * @see http://php.net/manual/en/function.json-decode.php
 */
if (!function_exists('json_encode'))
{
    function json_decode ($josn = false, $assoc = false, $depth = 512, $options = 0)
    {
        return Data\Json::decode($josn);
    }
}

?>