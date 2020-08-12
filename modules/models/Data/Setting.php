<?php

use Sewii\Uri;
use Sewii\Data;

/**
 * 設定選項模組
 * 
 * @version v 2.0.0 2012/04/30 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class MOD_Setting extends COM_Model_Database
{
    /**
     * 資料表
     * 
     * @var string
     */
    public $table = 'sp_settings';
    
    /**
     * 快取表
     * 
     * @var string
     */
    protected $_cache = array();

    /**
     * 建構子
     */    
    protected function __construct() {}

   /**
    * 傳回實體
    * 
    * @return MOD_Setting
    */
    public static function getInstance() 
    {
        static $instance;
        if (!$instance) {
            $classname = __CLASS__;
            $instance = new $classname;
        }
        return $instance;
    }

    /**
     * 儲存設定
     * 
     * @param string|array $field
     * @param mixed $value
     * @return boolean
     */
    public function save($field, $value = null)
    {
        //entry
        $entry = is_array($field) ? $field : array('field' => $field, 'value' => $value);

        //batch
        if (!isset($entry['field'])) {
            foreach($entry as $e) {
                if (isset($e['field'])) {
                    call_user_func(array($this, __FUNCTION__), $e);
                }
            }
            return true;
        }

        //save
        if (!empty($entry['field'])) 
        {
            $entry['$value'] = $entry['value'];
            unset($entry['value']);

            //update
            $where = array('field' => $entry['field']);
            if ($this->fetch($where)) {
                if ($this->update($where, $entry)) 
                    return true;
            }
            //insert
            else {
                $entry['!created'] = 'NOW()';
                if ($this->insert($entry)) 
                    return true;
            }
        }
        return false;
    }
    
    /**
     * 傳回資料列
     * 
     * @param string $field
     * @return mixed
     */
    public function entry($field)
    {
        $entry = new Data\Hashtable();
        if (isset($this->_cache[$field])) $entry = $this->_cache[$field];
        else {
            $where = array('field' => $field);
            if ($row = $this->fetch($where)) $entry = $row;
        }
        return $entry;
    }
    
    /**
     * 傳回值
     * 
     * @param string $field
     * @return mixed
     */
    public function value($field)
    {
        return $this->entry($field)->value;
    }

    /**
     * 傳回是否已設定
     *
     * @param string $field
     * @return boolean
     */
    public function isExists($field) 
    {
        $entry = $this->entry($field);
        return isset($entry->id) ? true : false;
    }
    
    /**
     * 傳回是否為 true
     *
     * @see Data\Variable::isTrue()
     * @param string $field
     * @return boolean
     */
    public function isTrue($field) 
    {
        $value = $this->value($field);
        return Data\Variable::isTrue($value);
    }

    /**
     * 傳回是否為 false
     *
     * @see Data\Variable::isFalse()
     * @param string $field
     * @return boolean
     */
    public function isFalse($field) 
    {
        $value = $this->value($field);
        return Data\Variable::isFalse($value);
    }

    /**
     * 傳回是否為空值
     *
     * @param string $field
     * @return boolean
     */
    public function isEmpty($field) 
    {
        $value = $this->value($field);
        return (!$value) ? true : false;
    }
    
    /**
     * 傳回網站地址
     *
     * @return string
     */
    public function getWebsiteUrl()
    {
        $url = $this->value('websiteUrl');
        if (!$url) $url = Uri\Uri::factory()->getBase();
        $url = ltrim($url, '/');
        return $url;
    }
}

?>