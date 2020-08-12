<?php

/**
 * 樣版輸出器
 * 
 * @version 1.0.0 2013/05/26 18:50
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Sewii\View\Template;

use phpQuery;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Text\Strings;

class Output
{
    public static function format($content)
    {
        //$content = self::href($content);
        //$content = self::line($content);
        return $content;
    }
    
    /**
     * 格式化連結
     *
     * @param string $content
     * @return string
     */  
    protected static function href($content)
    {
        $content = Regex::replace('/href="#"/i', 'href="javascript:#"', $content);
        return $content;
    }
    
    /**
     * 格式化行
     *
     * @param string $content
     * @return string
     */ 
    protected static function line($content)
    {
        if ($matches = Regex::matches('/^<\w+[^\r\n]*/sm', $content)) {
            foreach ($matches[0] as $line) {
                if ($matches2 = Regex::matches('/(\s+<.*)[\r\n]' . Regex::quote($line, '/') . '/i', $content)) {
                    foreach ($matches2[1] as $prev) {
                        $prev = ltrim($prev, "\r\n");
                        if ($matches3 = Regex::match('/^\s+/', $prev)) {
                            $indent = $matches3[0];
                            $content = Regex::replace('/^' . Regex::quote($line, '/') . '/sm', $indent . $line, $content);
                        }
                    }
                }
            }
        }
        return $content;
    }
    
    /**
     * 格式化行
     *
     * @param string $content
     * @return string
     */ 
    protected static function linea($content)
    {
        if ($matches = Regex::matches('/^<\w+[^\r\n]*/sm', $content)) {
            foreach ($matches[0] as $line) {
                if ($matches2 = Regex::matches('/(\s+<.*)[\r\n]' . Regex::quote($line, '/') . '/i', $content)) {
                    foreach ($matches2[1] as $prev) {
                        $prev = ltrim($prev, "\r\n");
                        if ($matches3 = Regex::match('/^\s+/', $prev)) {
                            $indent = $matches3[0];
                            $content = Regex::replace('/^' . Regex::quote($line, '/') . '/sm', $indent . $line, $content);
                        }
                    }
                }
            }
        }
        return $content;
    }
}

?>