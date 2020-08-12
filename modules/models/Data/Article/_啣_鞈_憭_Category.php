<?php

/**
 * 文章類別模組抽像類別
 * 
 * @version v 1.11 2010/09/9 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
abstract class MOD_Abstract_ArticleCategory extends MOD_Abstract_ArticleClass
{
    /**
     * 資料表名稱
     * @var string
     */
    public $tableName = 'sp_article_category';
}

?>