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
use Sewii\Data\Database;

class SaveHandler extends Singleton
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
     * 資料表
     *
     * @const string
     */
    const TABLE = 'sys_sessions';
    
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
     * 開啟會話
     * 
     * @param string $savePath
     * @param string $sessionName
     * @return boolean
     */
    public function open($savePath, $sessionName) 
    {
        $this->savePath = $savePath;
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
        $database = Database::getInstance();
        $result = $database->fetch('
            SELECT * FROM `:table` WHERE session = :session
        ', array(
            '!table'  => self::TABLE,
            'session' => $sessionId
        ));

        return strval($result->data);
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
        $database = Database::getInstance();
        return (bool) $database->query('
            INSERT INTO `:table` (created, session, data) 
            VALUES (NOW(), :session, :data) 
            ON DUPLICATE KEY UPDATE data = :data
        ', array(
            '!table'  => self::TABLE,
            'session' => $sessionId,
            'data'    => $data,
        ));
    }
    
    /**
     * 銷毀資料
     * 
     * @param string $sessionId
     * @return boolean
     */
    public function destroy($sessionId)
    {
        $database = Database::getInstance();
        $where = $database->assign('id = ?', $sessionId);
        return (bool) $database->delete(self::TABLE, $where);
    }
    
    /**
     * 回收事件
     * 
     * @param string $sessionId
     * @return boolean
     */
    public function gc($lifetime)
    {
        $database = Database::getInstance();
        $expired = date('Y-m-d H:i:s', time() - $lifetime);
        $where = $database->assign('modified < ?', $expired);
        return (bool) $database->delete(self::TABLE, $where);
    }
}

?>