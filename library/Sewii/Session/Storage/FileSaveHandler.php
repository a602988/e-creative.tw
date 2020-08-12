<?php

/**
 * Session 儲存處理器
 * 
 * @version 1.0.0 2013/03/25 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */
 
namespace Sewii\Session;

use Sewii\System\Singleton;

class FileSaveHandler extends Singleton
{
    /**
     * 儲存路徑 
     *
     * @var string
     */
    protected $savePath;

    /**
     * 欄位名稱
     *
     * @var string
     */
    protected $sessionName;

    /**
     * 欄位名稱
     *
     * @var resource
     */
    protected $stream;
    
    /**
     * 檔名字頭
     *
     * @const string
     */
    const PREFIX = 'sess_';
    
    /**
     * 初始化
     * 
     * @return void
     */
    public function initialize()
    {
        if (session_set_save_handler(
            array($this, 'open'),
            array($this, 'close'),
            array($this, 'read'),
            array($this, 'write'),
            array($this, 'destroy'),
            array($this, 'gc')
        )) {
            register_shutdown_function('session_write_close');
        }
    }

    /**
     * 偵測存放目錄
     *
     * @return string
     */
    protected function detectSavePath($savePath)
    {
        if (is_writeable($savePath)) return $savePath;
        if (is_writeable($savePath = getenv('TMP'))) return $savePath;
        if (is_writeable($savePath = getenv('TEMP'))) return $savePath;
        if (is_writeable($savePath = '/tmp')) return $savePath;
        return str_replace ("\\", "/", $sessionPath);
    }
    
    /**
     * 傳回檔案路徑
     *
     * @param string $sessionId
     * @return string
     */
    protected function getFilePath($sessionId)
    {
        return sprintf('%s/%s%s', $this->savePath, self::PREFIX, $sessionId);
    }
    
    /**
     * 寫入檔案
     *
     * @param string $path
     * @param mixed $data
     * @return boolean
     */
    function writeFile($path, $data)
    {
        $wrote = false;
        $stream = $this->stream = fopen($path, 'w');
        if (flock($stream, LOCK_EX | LOCK_NB)) {
            fwrite($stream, $data);
            flock($stream, LOCK_UN);
            $wrote = true;
        }
        fclose($stream);
        return $wrote;
    }
    
    /**
     * 開啟會話
     * 
     * @param string $savePath
     * @param string $sessionName
     * @return boolean
     */
    public function open($savePath, $sessionName) 
    {
        $this->savePath = $this->detectSavePath($savePath);
        $this->sessionName = $sessionName;
        return true;
    }
    
    /**
     * 關閉會話
     * 
     * @return boolean
     */
    public function close() 
    {
        if ($this->stream) {
            flock($this->stream, LOCK_UN);
        }
        return true;
    }
    
    /**
     * 讀取資料
     * 
     * @param string $sessionId
     * @return string
     */
    public function read($sessionId) 
    {
        $filePath = $this->getFilePath($sessionId);
        $data = @file_get_contents($filePath);
        return (string) $data;
    }
    
    /**
     * 寫入資料
     * 
     * @param string $sessionId
     * @param string $data
     * @return boolean
     */
    public function write($sessionId, $data) 
    {
        $filePath = $this->getFilePath($sessionId);
        $write = $this->writeFile($filePath, $data);
        return (boolean) $write;
    }
    
    /**
     * 銷毀資料
     * 
     * @param string $sessionId
     * @return boolean
     */
    public function destroy($sessionId)
    {
        $filePath = $this->getFilePath($sessionId);
        return @unlink($filePath);
    }
    
    /**
     * 回收事件
     * 
     * @param string $sessionId
     * @return boolean
     */
    public function gc($lifetime = 0)
    {
        $search = sprintf('%s/%s*', $this->savePath, self::PREFIX);
        foreach (glob($search) as $path) {
            if (filemtime($path) + $lifetime < time()) {
                @unlink($path);
            }
        }
        return true;
    }
}

?>