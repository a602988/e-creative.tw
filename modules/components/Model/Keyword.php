<?php

/**
 * 搜尋關鍵字類別
 * 
 * @version v 1.0.2 2013/12/17 17:45
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Spanel\Module\Component\Model;

use Sewii\Text\Regex;
use Sewii\System\Accessors\AbstractAccessors;

class Keyword
    extends AbstractAccessors
{
    /**
     * 原始字串
     * 
     * @var string
     */
    protected $original = null;
    
    /**
     * 包含的關鍵詞
     * 
     * @var array
     */
    protected $include = array();
    
    /**
     * 不包含的關鍵詞
     * 
     * @var array
     */
    protected $exclude = array();
    
    /**
     * 過濾器規則
     * 
     * @var string IS|NOT
     */
    protected $filter = null;
    
    /**
     * 邏輯運算子
     * 
     * @var string OR|AND
     */
    protected $operator = null;

    /**
     * 建構子
     */
    public function __construct($keyword) 
    {
        $this->original = $keyword;

        // 包含(IS)
        $this->filter = 'IS';
        if (Regex::isMatch('/^is\s+.+/i', $keyword)) {
            $keyword = Regex::replace('/^is\s+/i', '', $keyword);
        }
        // 相反(NOT)
        else if (Regex::isMatch('/^not\s+.+/i', $keyword)) {
            $keyword = Regex::replace('/^not\s+/i', '', $keyword);
            $this->filter = 'NOT';
        }

        // 多重(OR)
        $this->operator = 'OR';
        if (Regex::isMatch('/^or\s+.+/i', $keyword)) {
            $keyword = Regex::replace('/^or\s+/i', '', $keyword);
        }
        // 鏈結(AND)
        else if (Regex::isMatch('/^and\s+.+/i', $keyword)) {
            $keyword = Regex::replace('/^and\s+/i', '', $keyword);
            $this->operator = 'AND';
        }
        
        // 關鍵詞
        $this->include = $this->exclude = array();
        if (Regex::matches('/"[^"]+"/', $keyword, $matches)) {
            foreach ((array)$matches[0] as $matched) {
                $replaced = Regex::replace('/\+/', '@@@', $matched);
                $replaced = Regex::replace('/\s+/', '___', $replaced);
                $keyword = Regex::replace('/' . Regex::quote($matched, '/') . '/', $replaced, $keyword);
            }
        }
        
        if ($this->include = Regex::split('/(\s*[\+\s]+\s*)|(\s+)/', $keyword)) {
            foreach($this->include as $key => &$part) 
            {
                $part = Regex::replace('/@@@/', '+', $part);
                $part = Regex::replace('/___/', ' ', $part);

                // 負號過濾
                if (Regex::isMatch('/^\-.+/', $part)) {
                    $part = Regex::replace('/^\-/', '', $part);
                    $part = trim($part, '"');
                    array_push($this->exclude, $part);
                    unset($this->include[$key]);
                    continue;
                }

                $part = trim($part, '"');
            }
        }
    }
}

?>