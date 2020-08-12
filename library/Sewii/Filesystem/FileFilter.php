<?php

/**
 * 檔名過濾器
 * 
 * @version 1.3.0 2013/05/08 00:00
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Filesystem;

use FilterIterator;
use RecursiveFilterIterator;
use Sewii\Exception;
use Sewii\Text\Regex;

class FileFilter extends FilterIterator
{
    /**#@+
     * 過濾器鍵值
     */
    const KEY_WITH    = 'WITH';
    const KEY_WITHOUT = 'WITHOUT';
    const KEY_OR      = 'OR';
    const KEY_AND     = 'AND';
    /**#@-*/

    /**
     * 過濾器規則
     * 
     * @var boolean
     */
    protected $filters = array();
    
    /**
     * 傳回過濾器
     * 
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }
    
    /**
     * 設定過濾器
     * 
     * @return Scanner
     */
    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }
    
    /**
     * 包含其中的一個規則
     * 
     * @param string $pattern
     * @return Scanner
     */
    public function with($pattern)
    {
        $this->filters[self::KEY_WITH][self::KEY_OR][] = $pattern;
        return $this;
    }
    
    /**
     * 包含其中的全部規則
     * 
     * @param string $pattern
     * @return Scanner
     */
    public function withAll($pattern)
    {
        $this->filters[self::KEY_WITH][self::KEY_AND][] = $pattern;
        return $this;
    }
    
    /**
     * 不包含其中的一個規則
     * 
     * @param string $pattern
     * @return Scanner
     */
    public function without($pattern)
    {
        $this->filters[self::KEY_WITHOUT][self::KEY_OR][] = $pattern;
        return $this;
    }
    
    /**
     * 不包含其中的全部規則
     * 
     * @param string $pattern
     * @return Scanner
     */
    public function withoutAll($pattern)
    {
        $this->filters[self::KEY_WITHOUT][self::KEY_AND][] = $pattern;
        return $this;
    }
    
    /**
     * 清除過濾器全部規則
     * 
     * @return FileFilter
     */
    public function clear()
    {
        $this->filters = array();
        return $this;
    }

    /**
     * 接受方法
     *
     * {@inheritDoc}
     */
    public function accept()
    {
        $matched = null;
        if ($filters = $this->filters) {
            $current = $this->current();
            is_string($current) && $current = new FileInfo($current);
            $basename = $current->getBasename();

            //包含 (With) 運算
            if (!empty($filters[self::KEY_WITH])) {
                $with = $filters[self::KEY_WITH];

                //其一 (OR) 運算
                if (!empty($with[self::KEY_OR])) {
                    if ($matched === true || $matched === null) {
                        $matched = false;
                        foreach ($with[self::KEY_OR] as $pattern) {
                            if (Regex::match($pattern, $basename)) {
                                $matched = true;
                                break;
                            }
                        }
                    }
                }

                //全部 (AND) 運算
                if (!empty($with[self::KEY_AND])) {
                    if ($matched === true || $matched === null) {
                        $matched = true;
                        foreach ($with[self::KEY_AND] as $pattern) {
                            if (!Regex::match($pattern, $basename)) {
                                $matched = false;
                                break;
                            }
                        }
                    }
                }
            }

            //不包含 (Without) 運算
            if (!empty($filters[self::KEY_WITHOUT])) {
                $without = $filters[self::KEY_WITHOUT];

                //其一 (OR) 運算
                if (!empty($without[self::KEY_OR])) {
                    if ($matched === true || $matched === null) {
                        $matched = true;
                        foreach ($without[self::KEY_OR] as $pattern) {
                            if (Regex::match($pattern, $basename)) {
                                $matched = false;
                                break;
                            }
                        }
                    }
                }
                
                //全部 (AND) 運算
                if (!empty($without[self::KEY_AND])) {
                    if ($matched === true || $matched === null) {
                        $matched = false;
                        foreach ($without[self::KEY_AND] as $pattern) {
                            if (!Regex::match($pattern, $basename)) {
                                $matched = true;
                                break;
                            }
                        }
                    }
                }
            }
        }
        return is_bool($matched) ? $matched : true;
    }
}

?>