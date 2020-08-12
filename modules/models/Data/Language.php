<?php

use Sewii\Data\Database;

/**
 * 多語系模組
 * 
 * @version v 2.0.1 2012/10/22 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class MOD_Language extends COM_Intl
{
   /**
    * 建構方法 (Singleton)
    * 
    * @return MOD_Language
    */
    public static function getInstance() 
    {
        static $instance, $class = __CLASS__;
        if (!$instance) $instance = new $class;
        return $instance;
    }

    /**
     * 記錄列輸入事件
     *
     * @param string $token
     * @return COM_Model_Database_Row
     */
    protected function _onInputEntryRow($token, array $source = null)
    {
        $row = new COM_Model_Database_Row;
        $row->bind($this->table, $source);
        $row->date('created')->insertable();
        $row->data('name');
        $row->data('code');
        $row->data('subject');
        $row->data('indexed');
        $row->media('$media');
        $row->filter($token);
        return $row;
    }

    /**
     * 重設資料庫語系
     * 
     * @param mixed $code
     * @return void
     */
    public function resetDatabase($code = null)
    {
        $database = Database\Factory::getInstance();

        $where = 'WHERE 1';
        if (!is_null($code)) 
            $where = array('code' => $code);

        $codes = array();
        while($row = $this->fetchAll($where)) {
            $codes[] = $row['code'];
        }
        
        //略過的資料表
        $ignoreTables = array();
        if (class_exists('MOD_FormItems')) {
            $FormItems = new MOD_FormItems;
            array_push($ignoreTables, $FormItems->tableName);
        }

        //刪除語系欄位
        foreach($database->fetchTables() as $table) {
            if (in_array($table, $ignoreTables)) continue;
            if ($fields = $database->fetchFields($table)) {
                foreach($fields as $field => $info) {
                    if ($this->isLangField($field)) {
                        list ($mainField, $code) = $this->deLangField($field);
                        $query = 'ALTER TABLE `' . $table . '` DROP `' . $field . '`';
                        if (in_array($code, $codes)) 
                            $database->query($query);
                    }
                }
            }
        }
        
        //設定選項欄位
        if (class_exists('MOD_Setting')) {
            $setting = MOD_Setting::getInstance();
            while($row = $setting->fetchAll()) {
                $field = $row['field'];
                if ($this->isLangField($field)) {
                    list ($mainField, $code) = $this->deLangField($field);
                    $where = array('field = ?', $field);
                    if (in_array($code, $codes)) 
                        $database->delete($setting->table, $where);
                }
            }
        }
    }
}

?>