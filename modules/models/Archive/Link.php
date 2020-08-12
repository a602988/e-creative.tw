<?php

/**
 * 後端使用者帳戶類別
 * 
 * @version v 1.0.0 2012/08/23 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class MOD_User_Manager extends MOD_User
{
    /**
     * 準備記錄列 (callback)
     *
     * @param string $token
     * @return COM_Model_Database_Row
     */
    public function prepareEntryRow($token, array $source = null)
    {
        $row = new COM_Model_Database_Row;
        $row->bind($this->table, $source);
        $row->data('class', $this->class)->insertable();
        $row->date('created')->insertable();
        $row->data('category');
        $row->data('$expireDate');
        $row->data('enabled');
        $row->data('highest');
        $row->data('publishDate');
        $row->data('subject');
        $row->data('content');
        $row->data('indexed');
        $row->serialize('$fields');
        $row->media('$media');
        $row->filter($token);
        return $row;
    }
}

?>