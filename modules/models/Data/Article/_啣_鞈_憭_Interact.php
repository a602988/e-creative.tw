<?php

/**
 * 互動式文章模組抽像類別
 * (留言板、討論區、部落格、迴響)
 * 
 * @version v 1.5.3 2011/09/06 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
abstract class MOD_Abstract_ArticleInteract extends MOD_Abstract_Articles
{
    /**
     * 作者身份定義
     * @var array
     */
    public $authorTypes = array(
        'manager'   => array('name' => '管理員'),
        'member'    => array('name' => '會員'),
        'anonymous' => array('name' => '匿名'),
    );
    
    /**
     * 傳回使用者帳戶發表群的 combobox
     *
     * @return string
     *
     */
    public function getUserAuthorComboBox()
    {
        global $database, $Settings, $Users;

        $queryUsers = $database->query('
            SELECT * 
            FROM :tableName 
            ORDER BY createDate ASC, rowId ASC
        ', array (
            '!tableName' => $Users->tableName
        ));
        
        $list = '';
        while ($row = $database->fetch ($queryUsers)) 
        {
            //如果包含 users 權限才可使用他人帳號
            if (!$Users->hasPower('users')) {
                if ($row['username'] != $Users->hasLogged())
                    continue;
            }

            $selected = ($row['username'] == $Users->hasLogged()) ? ' selected="selected" ' : '';
            $list .= '<option value="' . $row['username'] . '" ' . $selected . '>';
            $list .= $row['username'] . ' (' . $row['name'] . ')';
            $list .='</option>';
        }
        return $list;
    }
    
    /**
     * 設定貼張文章的發表人、會員、使用者帳戶資料列欄位
     * 這個方法會依照目前頁面或環境不同自動產生以下合適的欄位:
     * author、member、manager、modifier 
     * 
     * @param array $rows - 資料列(傳參考)
     * @return array
     *
     */
    protected function _generatorPostingAuthorRows(array &$rows)
    {
        global $Users, $Members;
        
        switch($_GET['action']) 
        {
            //前台發表
            default:
            case 'user':
                //會員發表
                if ($username = $Members->hasLogged()) {
                    $rows['author'] = $username;
                    $rows['$member'] = $username;
                    $rows['$manager'] = null;
                }
                //匿名發表
                else {
                    $rows['author'] = $_POST['author'];
                    $rows['$manager'] = null;
                    $rows['$member'] = null;
                }
                
                //最後修改人
                if ($_GET['edit'])
                    $rows['modifier'] = ($rows['author']) ? $rows['author'] : $username;
                break;

            //後台發表
            case 'manager':
                $username = $Users->hasLogged();
                $rows['$manager'] = $username;
                $rows['$member'] = null;
                
                //指定帳號
                if ($_POST['author']) {
                    $rows['$manager'] = 
                    $rows['author'] = 
                    $_POST['author'];
                }
                //未指定
                else
                {
                    //修改模式
                    if ($_GET['edit']) {
                        //不要變更
                        unset($rows['$manager']);
                        unset($rows['$member']);
                    }
                    //新增模式
                    else
                        $rows['author'] = $username;
                }
                
                //最後修改人
                if ($_GET['edit'])
                    $rows['modifier'] = ($rows['author']) ? $rows['author'] : $username;
                break;
        }
        
        //新增模式
        if (!$_GET['edit']) {
            if (!$rows['author']) 
                throw new exception("必須包含文章發表人欄位 (author)");
        }
        return $rows;
    }
    
    /**
     * 傳回文章是否為會員發表
     *
     * @param integer $rowId 
     * @return string|false - username
     *
     */
    public function isAuthorMember($rowId)
    {
        if ($data = $this->fetch($rowId)) {
            if ($data['member']) return $data['member'];
        }
        return false;
    }
    
    /**
     * 傳回文章是否為管理員發表
     *
     * @param integer $rowId 
     * @return string|false - username
     *
     */
    public function isAuthorManager($rowId)
    {
        if ($data = $this->fetch($rowId)) {
            if ($data['manager']) return $data['manager'];
        }
        return false;
    }
    
    /**
     * 傳回文章是否為匿名發表
     *
     * @param integer $rowId 
     * @return boolean
     *
     */
    public function isAuthorAnonymous($rowId)
    {
        if ($data = $this->fetch($rowId)) {
            if (!$data['manager'] && !$data['member']) return true;
        }
        return false;
    }
    
    /**
     * 傳回文章作者帳號
     *
     * @param integer $rowId 
     * @return string - 失敗傳回 false
     *
     */
    public function getAuthorUsername($rowId)
    {
        if ($data = $this->fetch($rowId)) {
            if ($data['member']) return $data['member'];
            if ($data['manager']) return $data['manager'];
        }
        return false;
    }
    
    /**
     * 傳回文章作者註冊姓名
     *
     * @param integer $rowId 
     * @return string - 失敗傳回 false
     *
     */
    public function getAuthorRegisterName($rowId)
    {
        if ($data = $this->getAuthorProfile($rowId)) {
            return $data['name'];
        }
        return false;
    }
    
    /**
     * 傳回文章作者註冊暱稱
     * 如果沒有暱稱將傳回註冊帳號
     *
     * @param integer $rowId 
     * @param string $defaultField 
     * @return string - 失敗傳回 false
     *
     */
    public function getAuthorRegisterNickname($rowId, $defaultField = 'username')
    {
        if ($data = $this->getAuthorProfile($rowId)) 
        {
            if ($data['nickname']) 
                return $data['nickname'];

            if ($this->isAuthorManager($rowId))
                return $data['name'];

            return $data[$defaultField];
        }
        return false;
    }
    
    /**
     * 傳回文章作者身份類型
     *
     * @param integer $rowId 
     * @return string manager|member|user
     *
     */
    public function getAuthorType($rowId)
    {
        if ($this->isAuthorManager($rowId)) return 'manager';
        if ($this->isAuthorMember($rowId)) return 'member';
        return 'anonymous';
    }
    
    /**
     * 傳回文章作者身份類型名稱
     *
     * @param integer $rowId 
     * @return string manager|member|user
     *
     */
    public function getAuthorTypeName($rowId)
    {
        return $this->authorTypes[$this->getAuthorType($rowId)]['name'];
    }

    /**
     * 傳回文章作者資料
     *
     * @param integer $rowId 
     * @return array - 失敗傳回 false
     *
     */
    public function getAuthorProfile($rowId)
    {
        $Members = new MOD_Members;
        $Users = new MOD_Users;

        if ($username = $this->isAuthorManager($rowId))
            return $Users->getProfile($username);

        if ($username = $this->isAuthorMember($rowId))
            return $Members->getProfile($username);

        return false;
    }
    
    /**
     * 傳回主題文章的最後一篇迴響
     *
     * @param integer $topicId  主題 id
     * @param boolean $both 是否傳回包含下架的迴響，預設為 false 只傳回上架迴響
     * @return array - 失敗傳回 false
     *
     */
    public function getLastReplyData($topicId, $both = false)
    {
        global $database;
        
        $result = $database->fetch('
            SELECT *
            FROM :tableName
            WHERE 
            classId = :classId AND
            topicId = :topicId
            :enableExp
            ORDER BY createDate DESC, rowId DESC
            LIMIT 1
        ', array (
            '!tableName'    => $this->tableName,
            'classId'       => $this->classId(),
            'topicId'       => $topicId,
            '!enableExp'    => (!$both) ? ' AND enable = "Y" ' : ''
        ));
        return ($result) ? $result : false;
    }
    
    /**
     * 傳回主題文章的最後發表人
     *
     * @param integer $topicId  主題 id
     * @param boolean $both 是否傳回包含下架的文章，預設為 false 只傳回上架文章
     * @return string - 失敗傳回 false
     *
     */
    public function getLastReplyAuthor($topicId, $both = false)
    {
        if (!$data = $this->getLastReplyData($topicId, $both)) {
            $data = $this->fetch($topicId);
        }
        return ($data) ? $data['author'] : false;
    }
    
    /**
     * 傳回主題文章的最後發表時間
     *
     * @param integer $topicId  主題 id
     * @param boolean $both 是否傳回包含下架的文章，預設為 false 只傳回上架文章
     * @return string - 失敗傳回 false
     *
     */
    public function getLastReplyDate($topicId, $both = false)
    {
        if (!$data = $this->getLastReplyData($topicId, $both)) {
            $data = $this->fetch($topicId);
        }
        return ($data) ? $data['createDate'] : false;
    }

    /**
     * 傳回主題文章的回應數
     *
     * @param integer $topicId  主題 id
     * @param boolean $both 是否傳回包含下架的迴響，預設為 false 只統計上架文章
     * @return integer
     *
     */
    public function getReplyCount($topicId, $both = false)
    {
        global $database;
        
        $result = $database->fetch('
            SELECT 
            COUNT(*) AS count
            FROM :tableName
            WHERE 
            classId = :classId AND
            topicId = :topicId
            :enableExp
        ', array (
            '!tableName'    => $this->tableName,
            'classId'       => $this->classId(),
            'topicId'       => $topicId,
            '!enableExp'    => (!$both) ? ' AND enable = "Y" ' : ''
        ));
        return (int) $result['count'];
    }
    
    /**
     * 傳回文章的 E-mail 地址是否公開
     *
     * @param mixed $rowId
     * @return string
     *
     */
    public function isPublicEmail($rowId)
    {
        if ($data = $this->fetch($rowId)) {
            if ($data['emailPublic'] != 'N')
                return true;
        }
        return false;
    }
    
    /**
     * 設定後台修改文章時發表人的 comboBox 欄位狀態
     *
     * @param array $formData
     *
     */
    public function setAuthorComboBoxState($formData)
    {
        global $Page;

        //非管理員發表
        if (!$formData['manager']) 
        {
            //無法修改
            $authorTypeName = $this->getAuthorTypeName($formData['rowId']);
            $authorSubject = $formData['author'] . ' (' . $authorTypeName . '發表)';
            $authorOption = '<option value="' . $formData['author'] . '">' . $authorSubject . '</option>';
            $Page->find('#author')
              ->append($authorOption)
              ->attr('disabled', 'disabled')
              ->val($formData['author']);
        } 
        else $Page->form('#author', $formData['manager']);
    }
}

?>