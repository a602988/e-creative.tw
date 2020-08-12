<?php

/**
 * 文章模組抽像類別
 * 
 * @version v 1.3 2010/09/24 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
abstract class MOD_Abstract_Articles extends MOD_Abstract_ArticleClass
{
    /**
     * 資料表名稱
     * @var string
     */
    public $tableName = 'sp_articles';
    
    /**
     * 傳回類別的 combo box
     *
     * @param boolean $both 是否傳回包含停用的類別，預設為 false 只傳回啟用類別
     * @return string
     *
     */
    public function getCategoryComboBox($both = false)
    {
        $ArticleFolders = new MOD_ArticleFolders;
        return $ArticleFolders->getComboBox($this->classId(), $both);
    }
    
    /**
     * 設定文章類別的 combo box
     * 
     * 這個方法會檢查單元 articleCategoryEnable 的欄位是否啟用目錄功能
     * 如果啟用將會同時設定 #categoryId, #changeCategory 的 combo box
     * 反之會將它們移除
     * 
     * @return boolean
     *
     */
    public function setCategoryComboBox()
    {
        global $Users, $Page;
        
        $ArticleFolders = new MOD_ArticleFolders;
        if (($data = $ArticleFolders->getArticleClassData($this->classId())) /* && $Users->hasPower($this->classes['articleFolders']) */) {
            if (Sewii::isTrue($data['articleCategoryEnable']) && Sewii::isTrue($data['enable'])) {
                //支援類別
                $comboBox = $this->getCategoryComboBox(true);
                $Page->find('#categoryId, #changeCategory')->append($comboBox);
                return true;
            }
        }
        //不支援類別
        $Page->find('#changeCategory')->parents('.rightBlock')->remove();
        if ($Page->find('#categoryId')->parents('fieldset')->find('.row')->length <= 1)
            $Page->find('#categoryId')->parents('fieldset')->remove(); //如果只有類別直接刪除 fieldset
        $Page->find('#categoryId')->parents('.row')->remove();
        $Page->find('.categoryColumn')->remove();
        return false;
    }

    /**
     * 點閱數累加
     * @return boolean
     */
    public function looked($id)
    {
        if ($data = $this->fetch($id)) {
            return $this->update($id, array('!looked' => 'looked + 1'));
        }
        return false;
    }
}

?>