<?php

use Sewii\View\Template;
use Sewii\Intl;
use Sewii\Data\Database;
use Sewii\Data;

/**
 * 多國語系元件
 * 
 * @version v 2.2.1 2012/05/31 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class COM_Intl extends COM_Model_Database 
{
    /**
     * 資料表
     * @var mixed
     */
    public $table = 'sp_languages';

    /**
     * 原型物件
     * @var Sewii\Intl\Intl
     */
    public $prototype = null;
    
    /**
     * 表示多語系欄位的正規樣式
     * @const string
     */
    const PATTERN_LANG_FIELD = '/^(.+)_([A-Z]){2}/';
    
    /**
     * 表示多語系欄位的連接符號
     * @const string
     */
    const FIELD_SYMBOL = '_';

    /**
     * 建構子
     */    
    protected function __construct() {}

   /**
    * 建構方法 (Singleton)
    * 
    * @return COM_Intl
    */
    public static function getInstance() 
    {
        static $instance, $class = __CLASS__;
        if (!$instance) {
            $instance = new $class;
            $instance->_init();
        }
        return $instance;
    }

    /**
     * 初始化
     * 
    * @return COM_Intl
     */
    private function _init() 
    {
        //同步建構 Sewii\Intl\Intl 實體
        $this->prototype = Intl\Intl::getInstance();
        $this->_loadLangs();
    }

    /**
     * 呼叫子
     *
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args)
    {
        //嘗試呼叫 prototype 方法
        if (method_exists($this->prototype, $name))
            return call_user_func_array(array($this->prototype, $name), $args);

        throw new Exception('未定義的方法: ' . __CLASS__ . '::' . $name . '()');
    }

    /**
     * 載入語系清單
     */
    protected function _loadLangs()
    {
        if ($this->isTableExists($this->table)) 
        {
            $languages = array();
            while($row = $this->fetchAll(
                $where = 'WHERE enabled = "Y"', 
                $order = 'defaulted ASC, indexed ASC, created DESC, id DESC')) 
            {
                $media = Template\Media::data($row->media);
                $flag = (!empty($media)) ? $media['url'] : null;
                $languages[$row->code] = array(
                    'name' => $row->name,
                    'subject' => $row->subject,
                    'local' => $row->local,
                    'description' => $row->description,
                    'flag' => $flag
                );
            }

            //載入成功才覆寫
            if ($languages)
                $this->prototype->setLanguages($languages);
        }
    }

    /**
     * 取出符合目前語系的資料
     *
     * @param array $row
     * @param string $lang
     * @return array
     *
     */
    public function extract(array &$row, $lang = null)
    {
        if ($langFields = $this->getLangFields($row))  
        {
            $hasProcessed = array();
            $default = $this->getDefault();
            $current = $this->getCurrent();
            if ($lang && $this->isSupport($lang)) {
                $current = $lang;
            }

            foreach ($langFields as $field) {
                list ($mainField, $code) = $this->deLangField($field);
                if (in_array($mainField, $hasProcessed)) continue;
                if ($currentLangData = $row[$this->enLangField($mainField, $current)]) 
                {                    
                    //將預設欄位內容變更為目前語系內容
                    $row[$mainField] = $currentLangData;
                    
                    //表示已處理過這個語系欄位名稱
                    array_push($hasProcessed, $mainField);
                }
            }
        }
        return $row;
    }

    /**
     * 語系資料欄位產生器
     *
     * @param array $row
     * @param array $source
     * @param string $table
     * @return array
     *
     */
    public function generator(array &$row, array $source, $table = null) 
    {
        $database = Database\Factory::getInstance();

        //建立語系資料欄位
        $default = $this->getDefault();
        foreach ($row as $field => $value)
        {
            $field = $database->removeSpecialFieldSymbol($field);
            foreach($this->getLanguages() as $code => $info)
            {
                $langField = $this->enLangField($field, $code);
                if (isset($source[$langField])) 
                {
                    $row['$' . $this->enLangField($field, $default)] = $value;
                    $row['$' . $langField] = $source[$langField];

                    //media
                    if (Template\Media::isFormat($value)) {
                        $row['$' . $langField] = Template\Media::encode($source[$langField]);
                    }
                    //serialize
                    else if (Data\Variable::isSerialize($value)) {
                        $row['$' . $langField] = Data\Variable::serialize($source[$langField]);
                    }
                    //json
                    else if (Data\Json::isFormat($value)) {
                        $row['$' . $langField] = Data\Json::encode($source[$langField]);
                    }
                }
            }
        }

        //建立語系表格欄位
        if (!is_null($table)) 
        {
            if ($langFields = $this->getLangFields($row)) 
            {
                $fields = $database->fetchFields($table);
                foreach (array_reverse($langFields) as $field) 
                {
                    $field = $database->removeSpecialFieldSymbol($field);
                    if ($this->isLangField($field)) {
                        if (array_key_exists($field, $fields)) continue;
                        list ($mainField, $code) = $this->deLangField($field);
                        if ($fieldInfo = $fields[$mainField]) 
                        {
                            //永遠設定欄位允許 NULL 值
                            $definition = str_replace(' NOT NULL', '', $fieldInfo->definition);
                            $database->query('
                                ALTER TABLE `' . $table . '` 
                                ADD `' . $field . '` ' . $definition . ' 
                                AFTER `' . $mainField . '`
                            ');
                        }
                    }
                }
            }
        }
        return $row;
    }

    /**
     * 傳回多語系欄位
     *
     * @param array $row
     * @return array
     *
     */
    public function getLangFields(array $row)
    {
        $result = array();
        if ($langFields = preg_grep(self::PATTERN_LANG_FIELD, array_keys($row))) 
            $result = $langFields;
        return $result;
    }

    /**
     * 傳回是否為多語系欄位
     *
     * @param string $field
     * @return boolean
     *
     */
    public function isLangField($field) 
    {
        return preg_match(self::PATTERN_LANG_FIELD, $field);
    }

    /**
     * 組合多語系欄位
     *
     * @param string $name
     * @param string $code
     * @return string
     *
     */
    public function enLangField($name, $code) 
    {
        return $name . self::FIELD_SYMBOL . $code;
    }

    /**
     * 拆解多語系欄位
     *
     * @param string $name
     * @return array
     *
     */
    public function deLangField($field) 
    {
        list($name, $code) = array_pad(explode(self::FIELD_SYMBOL, $field), 2, null);
        return array($name, $code, 'name' => $name, 'code' => $code);
    }
}

?>