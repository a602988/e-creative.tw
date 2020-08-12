<?php

/**
 * 文章模組單元類別
 * 
 * @version v 1.5.0 2011/09/07 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
abstract class MOD_Abstract_ArticleClass extends COM_DBUtility
{
    /**
     * 資料表名稱
     * @var string
     */
    private $tableName = 'sp_article_class';
    
    /**
     * 單元識別代號定義
     * @var string
     */
    public $classes = array 
    (
        'articleFolders'       => 'articleFolders',
        'articleReplies'       => 'articleReplies',
        'articleNews'          => 'articleNews',
        'articleFaq'           => 'articleFaq',
        'articleTeam'          => 'articleTeam',
        'articleForum'         => 'articleForum',
        'articleMessage'       => 'articleMessage',
        'articleProject'       => 'articleProject',
        'articleLink'          => 'articleLink',
        'articleDownload'      => 'articleDownload',
        'articleActivity'      => 'articleActivity',
        'articleAlbum'         => 'articleAlbum',
        'articleWorks'         => 'articleWorks',
        'articleWares'         => 'articleWares',
        'product'              => 'product',
        'commodity'            => 'commodity',
        'tours'                => 'tours',
        'tourWares'            => 'tourWares',
        'articleRoom'          => 'articleRoom',
        'articleRate'          => 'articleRate',
        'schoolTeachers'       => 'schoolTeachers',
        'schoolClasses'        => 'schoolClasses',
        'schoolClassCategory'  => 'schoolClassCategory',
        'builds'               => 'builds',
        'buildProgress'        => 'buildProgress',
        'modelCases'           => 'modelCases',
        'donates'              => 'donates',
    );
    
    /**
     * 傳回單元的資料表名稱
     *
     * @return string
     *
     */
    public function getArticleClassTableName() 
    {
        return $this->tableName;
    }

    /**
     * 傳回單元資料
     *
     * @param string $classId
     * @return mixed
     *
     */
    public function getArticleClassData($classId)
    {
        global $database;
        $data = $database->fetch ('
            SELECT * 
            FROM :tableName 
            WHERE classId = :classId
        ', array (
            '!tableName' => $this->getArticleClassTableName(),
            'classId' => $classId
        ));
        if ($data) return $data;
        return false;
    }
    
    /**
     * 傳回單元標題名稱
     *
     * @param string $classId
     * @return string
     *
     */
    public function getArticleClassName($classId)
    {
        $application = new COM_Application;
        $className = $application->getUnitLabel($classId);
        if ($data = $this->getArticleClassData($classId)) {
            if ($data['subject'])
                $className = $data['subject'];
        }
        return preg_replace('/管理$/', '', $className);
    }
    
    /**
     * 傳回可用的單元清單
     *
     * @param string $where
     * @return array
     *
     */
    public function getArticleClassList($where = null) 
    {
        global $database, $Users;
        
        if ($where) $where = 'WHERE ' . $this->_removeWhereQuery($where);
        $query = $database->query ('
            SELECT * 
            FROM :tableName 
            :where
            ORDER BY rowId ASC
        ', array (
            '!tableName' => $this->getArticleClassTableName(),
            '!where' => $where
        ));

        $list = array();
        while($row = $database->fetch($query)) {
            if (!$Users->hasPower($row['classId'])) continue;
            if (!Sewii::isTrue($row['enable'])) continue;
            $list[$row['classId']] = $this->getArticleClassName($row['classId']);
        }
        return $list;
    }
    
    /**
     * 傳回單元 id
     * @abstract
     *
     * @return string
     */
    abstract public function classId();
}

?>