<?php

/**
 * 上傳器類別
 * 
 * @version 1.0.0 2013/01/23 08:32
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Filesystem;

use SplFileObject;
use Sewii\Exception;

class Uploader
{
    /**
     * 表示檔案名稱長度超過限制的錯誤常數
     * @const int
     */
    const UPLOAD_ERR_FILE_NAME_LENGTH = 100;

    /**
     * 定義上傳錯誤訊息
     * 
     * @param integer
     * @return string
     */
    protected static function _getUploadError($error)
    {
        switch ($error)
        {
            case UPLOAD_ERR_INI_SIZE:               return '上傳的檔案大小超過了 php.ini 中 UPLOAD_MAX_FILESIZE 設定';
            case UPLOAD_ERR_FORM_SIZE:              return '上傳的檔案大小超過了 HTML 表單的 MAX_FILE_SIZE 設定';
            case UPLOAD_ERR_PARTIAL:                return '上傳的檔案只有部份被上傳';
            case UPLOAD_ERR_NO_FILE:                return '沒有檔案被上傳';
            case UPLOAD_ERR_NO_TMP_DIR:             return '遺失檔案上傳必須的暫存資料夾';
            case UPLOAD_ERR_CANT_WRITE:             return '無法寫入檔案內容到硬碟';
            case UPLOAD_ERR_EXTENSION:              return '此類型的副檔名已被禁止上傳';
            case self::UPLOAD_ERR_FILE_NAME_LENGTH: return '檔案名稱長度已超過系統最大限制';
            default:                                return '未知的上傳錯誤';
        }
    }
    
    /**
     * 上傳檔案
     *
     * @throws Exception 發生錯誤時將拋出例外
     * @param array $file 傳入 $_FILES 產生的檔案變數
     * @param string $destFolder 上傳檔案的目的資料夾
     * @param mixed $overwhelm 當檔案已存在時是否覆蓋。true 為覆蓋檔案，false 時將會自動重新編號檔案命名，傳入 -1 時將重新隨機命名檔案，傳入字串時可直接指定檔案名稱。
     * @return string 上傳成功將返回上傳後位於伺服器上的檔案路徑。
     */
    public static function upload($file, $destFolder, $overwhelm = false)
    {
        if ($file['error'] == UPLOAD_ERR_OK)
        {
            //linux 可能傳回沒有檔名的檔案?
            //此處隨機產生檔案名稱
            if (!$file['name']) $file['name'] = md5(uniqid());

            //將檔案名稱編碼為特殊格式
            $file['name'] = self::encode($file['name']);
            
            //檢查檔名長度
            //@see http://zh.wikipedia.org/zh-hk/%E6%AA%94%E6%A1%88%E5%90%8D%E7%A8%B1
            if (strlen($file['name']) >= 255)
                throw new \Exception(self::_getUploadError(self::UPLOAD_ERR_FILE_NAME_LENGTH));
            
            //還原系統編碼
            $dest = $destFolder . '/' . self::toLocal($file['name']);
            
            //傳入非 boolean 值
            if (is_bool($overwhelm) && $overwhelm) 
            {
                //隨機指定檔案名稱
                if ($overwhelm === -1) {
                    $fileExt = self::fileExt($dest);
                    $newFilename = substr(md5(uniqid()), 0, 16) . '.' . $fileExt;
                    $dest = $destFolder . '/' . self::toLocal($newFilename);
                }

                //直接指定檔案名稱
                else if (is_string($overwhelm))
                    $dest = $destFolder . '/' . self::toLocal($overwhelm);

                $overwhelm = false;
            }

            //檢查是否已有同名檔案
            if (self::isExists($dest) && !$overwhelm) 
                $dest = self::getAppendIndexDestName($dest);

            //移動到目的地
            if (move_uploaded_file($file['tmp_name'], $dest)) 
            {
            	//還原 UTF-8 編碼
                return self::toUtf8($dest);
            }
        }
        throw new \Exception(self::_getUploadError($file['error']));
    }
}

?>