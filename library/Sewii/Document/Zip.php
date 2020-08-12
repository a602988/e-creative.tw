<?php

/**
 * ZIP 壓縮文件處理類別
 *
 * @version v 1.0 2010/06/28 00:00
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Document;

class Zip
{
    /**
     * 建立壓縮檔
     *
     * @param mixed $src 來源路徑，多個路徑使用陣列傳入
     * @param string $dest 目的地
     * @return boolean
     *
     */
    public function create ($src, $dest)
    {
        $zip = new ZipArchive;
        
        //強轉為陣列處理
        if (!is_array($src)) 
            $src = array($src);
        
        //建立壓縮文件
        if ($zip->open($dest, ZipArchive::CREATE) === true) {
            foreach ($src as $item) {
                if (file_exists($item))
                    $this->add($item, $zip);
            }
            $zip->close();
            return true;
        }
        return false;
    }

    /**
     * 新增檔案或目錄到壓縮文件
     *
     * @param string $path 路徑
     * @param object $zip ZipArchive
     *
     */
    public function add ($path, $zip)
    {
        //新增目錄
        if (is_dir($path))
        {
            $zip->addEmptyDir($path);
            $scandir = array_diff (scandir ($path), array (".", ".."));
            
            //掃描目錄
            foreach ($scandir as $item) 
            {
                $itemPath = $path . '/' . $item;
                $this->add($itemPath, $zip);
            }
        }
        //新增檔案
        else if (is_file($path))
            $zip->addFile($path);
    }
    
    /**
     * 解壓縮檔案
     *
     * @param string $src 來源壓縮文件路徑
     * @param string $dest 目的地
     * @return boolean
     *
     */
    public function extract ($src, $dest)
    {
        $zip = new ZipArchive;
        if ($zip->open($src) === true) {
            $zip->extractTo($dest);
            $zip->close();
            return true;
        }
        return false;
    }
}

?>