<?php

/**
 * Excel 類別
 * 
 * @version v 1.2 2011/06/14 00:00
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Document;

class Excel
{
    /**
     * 建構子
     * @param string $memoryLimit
     */
    public function __construct($memoryLimit = '256M') 
    {
        if ($memoryLimit) @ini_set ('memory_limit', $memoryLimit);
    }

    /**
     * 開啟檔案流
     *
     * @param string $fileName
     * @return PHPExcel
     *
     */
    public function load($fileName)
    {
        return PHPExcel_IOFactory::load($fileName);
    }

    /**
     * 解析 Excel 內容到陣列
     *
     * @param mixed $fileName 檔案路徑
     * @param mixed $sheetIndex - 目的工作表 (編號從 0 開始)
     * @return mixed This is the return value description
     *
     */
    public function parseToArray($fileName, $sheetIndex = 0)
    {
        $objPHPExcel = $this->load($fileName);
        
        //讀取第一個工作表(編號從 0 開始)
        $sheet = $objPHPExcel->getSheet($sheetIndex);
        
        //傳回工作表全部儲存格集合
        $cellCollection = $sheet->getCellCollection();
        
        $cellsByRow = array();
        foreach ($cellCollection as $cellID) {
            $cell = $sheet->getCell($cellID);
            $val = $cell->getValue();
            $cellsByRow[$cell->getRow()][$cell->getColumn()] = $val;
        }
        
        return $cellsByRow;
    }
}

?>