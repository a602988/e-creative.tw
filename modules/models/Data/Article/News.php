<?php

/**
 * 新聞稿模組類別
 * 
 * @version v 1.3.0 2011/11/17 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class MOD_Article_News extends MOD_Article
{
    /**
     * 建構子
     */
    public function __construct() 
    {
        $this->disableExpired();
    }

    /**
     * 準備記錄列 (callback)
     *
     * @param string $token
     * @return COM_Model_Database_Row
     */
    protected function _onInputEntryRow($token, array $source = null)
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