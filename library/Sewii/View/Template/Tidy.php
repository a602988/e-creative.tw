<?php

/**
 * Tidy 類別
 * 
 * @version 1.0.3 2013/06/12 14:39
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\View\Template;

class Tidy extends Child
{
    /**
     * 預設格式化參數
     * 
     * @var array
     */
    protected static $config = array(
        'show-body-only' => false,
        'clean' => true,
        'char-encoding' => 'utf8',
        'add-xml-decl' => false,
        'add-xml-space' => false,
        'output-html' => false,
        'output-xml' => false,
        'output-xhtml' => true,
        'numeric-entities' => false,
        'ascii-chars' => false,
        'doctype' => 'strict',
        'bare' => false,
        'fix-uri' => false,
        'indent' => true,
        'indent-spaces' => 2,
        'tab-size' => 2,
        'wrap-attributes' => false,
        'wrap' => 0,
        'indent-attributes' => false,
        'join-classes' => false,
        'join-styles' => false,
        'enclose-block-text' => false,
        'fix-bad-comments' => false,
        'fix-backslash' => false,
        'replace-color' => false,
        'wrap-asp' => false,
        'wrap-jste' => false,
        'wrap-php' => false,
        'write-back' => false,
        'drop-proprietary-attributes' => false,
        'hide-comments' => false,
        'hide-endtags' => false,
        'literal-attributes' => false,
        'drop-empty-paras' => false,
        'enclose-text' => false,
        'quote-ampersand' => false,
        'quote-marks' => false,
        'quote-nbsp' => false,
        'vertical-space' => false,
        'wrap-script-literals' => false,
        'tidy-mark' => false,
        'merge-divs' => false,
        'repeated-attributes' => 'keep-last',
        'break-before-br' => false,
    );
    
    /**
     * 傳回格式化內容
     * 
     * @param string $content
     * @param array $config
     * @return string|null
     */
    public static function format($content, array $config = null)
    {
        $output = $content;
        if (class_exists('\Tidy')) {
            $Template = new Template;
            $encoding = str_replace('-', '', $Template->getEncoding());

            $tidy = new \Tidy;
            if (is_null($config)) $config = self::$config;
            $tidy->parseString($content, $config, $encoding);
            $tidy->cleanRepair();
            $output = strval($tidy);
        }
        return $output;
    }
}

?>