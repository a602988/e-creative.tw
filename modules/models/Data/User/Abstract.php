<?php

use Sewii\Util\UserInfo;
use Sewii\Filesystem;
use Sewii\Uri;
use Sewii\Data;
use Sewii\Http;

/**
 * 使用者抽像模組
 * 
 * @version v 1.0.0 2011/11/02 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
abstract class MOD_User_Abstract extends COM_DBAccess
{
    /**
     * 資料表名稱
     * @var string
     */
    public $tableName = 'sp_users';
    
    /**
     * 記住登入資訊使用的 COOKIE 名稱
     * @var string
     */
    private $_rememberMeCookieName = 'userRememberLogin';
    
    /**
     * 表示為已登入使用的 SESSION 名稱
     * @var string
     */
    private $_userHasLoggedSessionName = 'userHasLogged';
    
    /**
     * 表示最後一次的查詢字串 SESSION 名稱
     * @var string
     */
    private $_userLastQueryStringSessionName = 'userLastQueryString';
    
    /**
     * 表示為開發人員登入的 SESSION 名稱
     * @var string
     */
    private $_sysadminModeSessionName = 'sysadminMode';
    
    /**
     * 略過檢查的單元集合
     * @var array
     */
    private $ignoreUnits = array(COM_Page::UNIT_DEFAULT_NAME, 'userProfile');

    /**
     * 新增資料
     * @param array $rows
     * @return boolean
     */
    public function insert ($rows = null)
    {
        global $database;
        
        if (!$rows) 
        {
            $rows = array (
                'group'            => $_POST['group'],
                'username'         => $_POST['username'],
                'password'         => md5($_POST['password']),
                'passwordExpired'  => $_POST['passwordExpired'],
                'loginTo'          => $_POST['loginTo'],
                'email'            => $_POST['email'],
                'name'             => $_POST['name'],
                'phone'            => $_POST['phone'],
                'title'            => $_POST['title'],
                'department'       => $_POST['department'],
                'address'          => $_POST['address'],
                'notes'            => $_POST['notes'],
                'enable'           => $_POST['enable']
            );
        }
        return $database->insert ($this->tableName, $rows);
    }

    /**
     * 修改資料
     * @param mixed $where
     * @param array $rows
     * @return boolean
     */
    public function update ($where, $rows = null)
    {
        global $database;

        if (!$rows) 
        {
            $rows = array (
                'group'            => $_POST['group'],
                'passwordExpired'  => $_POST['passwordExpired'],
                'loginTo'          => $_POST['loginTo'],
                'email'            => $_POST['email'],
                'name'             => $_POST['name'],
                'phone'            => $_POST['phone'],
                'title'            => $_POST['title'],
                'department'       => $_POST['department'],
                'address'          => $_POST['address'],
                'notes'            => $_POST['notes'],
                'enable'           => $_POST['enable']
            );
            
            if ($_POST['password']) {
                $rows['password'] = md5($_POST['password']);
            }
        }

        return $database->update (
            $this->tableName, 
            $rows, 
            $this->_whereExpression($where)
        );
    }
    
    /**
     * 檢查使用者帳號和密碼
     *
     * @param mixed $username 帳號
     * @param mixed $password 密碼
     * @return array 成功傳回使用者資料，反之傳回 false
     *
     */
    public function checkIdAndPassword($username, $password) 
    {
        $profile = $this->fetch(
            array(
                'username = :username AND password = :password AND enable = "Y"', 
                array('username' => $username, 'password' => md5($password)
            )
        ));

        if ($profile) 
        {
            //如果是超級管理員直接過關
            if ($this->isSuperAdmin($username)) return true;
            
            //檢查群組是否啟用
            $groupInfo = $this->getGroupInfo($username);
            if ($groupInfo['enable'] == 'Y')
                return $profile;
        }
        return false;
    }
    
    /**
     * 記住登入資訊
     *
     * @param mixed $username 帳號
     * @param mixed $days 保存天數
     *
     */
    public function rememberMe($username, $days = 7) 
    {
        Http\Cookie::setting($this->_rememberMeCookieName, $username, time() + 86400 * $days);
    }
    
    /**
     * 刪除登入資訊
     */
    public function deleteRememberMe() 
    {
        Http\Cookie::setting($this->_rememberMeCookieName, null, time() - 3600);
    }
    
    /**
     * 傳回是否有已保存的登入資訊
     */
    public function hasRememberMe() 
    {
        if ($_COOKIE[$this->_rememberMeCookieName])
            return $_COOKIE[$this->_rememberMeCookieName];
        return false;
    }
    
    /**
     * 設定最後一次的查詢字串
     *
     * @param mixed $queryString 如果省略表示刪除
     * @param mixed $filters - 過濾不需要的參數，多個參數以陣列傳入或以逗號分隔。
     *
     */
    public function setLastQeuryString($queryString = null, $filters = null) 
    {
        if ($queryString) 
        {    
            //過濾不需要的參數
            if ($filters) {
                $queryString = Uri\Http\Query::modify('', $filters, $queryString)->getQuery();
            }
            $_SESSION[$this->_userLastQueryStringSessionName] = $queryString;
        }
        //刪除
        else unset($_SESSION[$this->_userLastQueryStringSessionName]);
    }
    
    /**
     * 傳回最後一次的查詢字串
     *
     * @return string
     */
    public function getLastQeuryString() 
    {
        return $_SESSION[$this->_userLastQueryStringSessionName];
    }
    
    /**
     * 註冊登入資訊
     * @param mixed $username 帳號
     */
    public function registerLogged ($username) {
        $_SESSION[$this->_userHasLoggedSessionName] = $username;
        if ($_GET['sysadmin']) 
            $_SESSION[$this->_sysadminModeSessionName] = true;
    }
    
    /**
     * 刪除已註冊的登入資訊
     */
    public function unregisterLogged () {
        unset($_SESSION[$this->_userHasLoggedSessionName]);
        unset($_SESSION[$this->_sysadminModeSessionName]);
    }
    
    /**
     * 傳回是否已經登入
     * @return boolean - 已登入者傳回帳號, 反之為 false
     */
    public function hasLogged () {
        if ($_SESSION[$this->_userHasLoggedSessionName])
            return $_SESSION[$this->_userHasLoggedSessionName];
        return false;
    }
    
    /**
     * 傳回是否為開發人員登入
     * (必須同時是超級管理員登入)
     * @return boolean
     */
    public function isSysadmin () 
    {
        if (!$this->isSuperAdmin()) return false;
        return (isset($_SESSION[$this->_sysadminModeSessionName])) ? true : false;
    }
    
    /**
     * 登入導向頁面
     */
    public function signUp() 
    {
        global $Settings;

        //嘗試登入上次的 URL
        if ($this->getLastQeuryString()) {
            $queryString = $this->getLastQeuryString();
            $this->setLastQeuryString();
            $locationTo = Uri\Http\Query::modify('done=login&time=' . time(), '', $queryString)->getPath();
            Http\Response::redirect($locationTo);
        }
        
        //選擇預設登入頁面
        $loginTo = 'default';
        if ($Settings->datas['userLoginTo']) $loginTo = $Settings->datas['userLoginTo'];
        if ($profile = $this->fetch(array('username' => $this->hasLogged()))) {
            if ($profile['loginTo'] != '0' && $profile['loginTo']) {
                $loginTo = $profile['loginTo'];
            }
        }

        Http\Response::redirect('?action=manager&unit=' . $loginTo . '&done=login&time=' . time());
    }

    /**
     * 登出導向頁面
     */
    public function signOut() {
        Http\Response::redirect('?action=manager&unit=login&done=logout&time=' . time());
    }
    
    /**
     * 登入
     * @param mixed $username 帳號
     * @param mixed $rememberMe 是否記住登入資訊
     */
    public function login($username, $rememberMe = false) 
    {
        if ($rememberMe) $this->rememberMe($username);
        $this->registerLogged($username);
        $this->update(
            array('username' => $username), 
            array('userInfo' => Data\Variable::serialize(Userinfo::details()))
        );
        $UserLogs = new MOD_UserLogs;
        $UserLogs->login(true, $username);
        $this->signUp();
    }
    
    /**
     * 登出
     */
    public function logout() 
    {
        $this->unregisterLogged();
        $this->deleteRememberMe();
        $this->signOut();
    }
    
    /**
     * 傳回最後登入的客戶端資訊
     *
     * @param string $username - 如無指定帳號預設使用目前登入帳號
     * @return array
     *
     */
    public function lastInfo($username = null) {
        if (!$username)
            $username = $this->hasLogged();
        $profile = $this->fetch(array('username' => $username));
        return Data\Variable::unserialize($profile['userInfo']);
    }
    
    /**
     * 傳回最後登入 IP
     *
     * @param string $username
     * @return string
     *
     */
    public function lastLoginIp($username = null) 
    {
        $lastInfo = $this->lastInfo($username);
        return $lastInfo['REMOTE_ADDR'];
    }
    
    /**
     * 傳回最後登入時間
     *
     * @param string $username
     * @return string
     *
     */
    public function lastLoginTime($username = null) 
    {
        $lastInfo = $this->lastInfo($username);
        return $lastInfo['REQUEST_TIME'];
    }
    
    /**
     * 傳回所屬的群組資訊
     * 如果群組超級管理員，將傳回 -1
     *
     * @param string $username - 如無指定帳號預設使用目前登入帳號
     * @return array - 失敗傳回 false
     */
    public function getGroupInfo($username = null) 
    {
        if (!$username) $username = $this->hasLogged();
        if ($profile = $this->fetch(array('username' => $username))) {
            $UserGroups = new mod_UserGroups;
            if ($UserGroups->isSuperGroup($profile['group'])) return -1;
            if ($profile['enable'] == 'N') return false; //帳號被停用則無權限
            if ($groupData = $UserGroups->fetch($profile['group'])) {
                if ($groupData['enable'] == 'N') return false; //群組被停用則無權限
                return $groupData;
            }
        }
        return false;
    }
    
    /**
     * 傳回使用者權限
     * 如果群組 id 為 1 表示為超級管理員，將傳回 -1
     *
     * @param string $username - 如無指定帳號預設使用目前登入帳號
     * @return array - 失敗傳回 false
     */
    public function getPowers($username = null)
    {
        if ($groupInfo = $this->getGroupInfo($username)) {
            if ($groupInfo == -1) return -1; //超級管理員
            return Data\Variable::unserialize($groupInfo['powers']);
        }
        return false;
    }
    
    /**
     * 傳回使用者是否為超級管理員
     *
     * @param string $username - 如無指定帳號預設使用目前登入帳號
     * @return boolean
     *
     */
    public function isSuperAdmin ($username = null) 
    {
        if ($this->getPowers($username) == -1)
            return true;
        return false;
    }
    
    /**
     * 傳回是否擁有單元權限
     *
     * @param string $unitName - 如無指定單元名稱預設使用 $_GET['unit'] 內容
     * @param string $username - 如無指定帳號預設使用目前登入帳號
     * @return boolean
     *
     */
    public function hasPower ($unitName = null, $username = null) 
    {
        if (!$unitName) $unitName = $_GET['unit'];
        if (in_array($unitName, $this->ignoreUnits)) return true;

        $application = new COM_Application;
        $application->userPowers = $this->getPowers();
        $application->units = $application->filteringUnits();
        if (!$application->getUnit($unitName)) return false;

        if ($this->isSuperAdmin()) return true;
        if ($userPowers = $this->getPowers($username)) {
            return ($userPowers[$unitName]) ? true : false;
        }
        return false;
    }
    
    /**
     * 傳回是否存取的單元是否只能讀取
     *
     * @param string $unitName - 如無指定單元名稱預設使用 $_GET['unit'] 內容
     * @param string $username - 如無指定帳號預設使用目前登入帳號
     * @return boolean
     *
     */
    public function isReadOnly ($unitName = null, $username = null) 
    {
        if ($this->isSuperAdmin()) return false;
        if (!$unitName) $unitName = $_GET['unit'];
        if (in_array($unitName, $this->ignoreUnits)) return false;
        if ($userPowers = $this->getPowers($username)) 
        {
            $UserGroups = new MOD_UserGroups;
            if ($userPowers[$unitName]) {
                if ($userPowers[$unitName] != $UserGroups->ACCESS_READ_ONLY) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * 傳回密碼是否已經過期
     *
     * @param string $username - 如無指定帳號預設使用目前登入帳號
     * @return boolean
     *
     */
    public function passwordExpired($username = null) 
    {
        if (!$username) $username = $this->hasLogged();
        if ($profile = $this->fetch(array('username' => $username))) {
            if ($profile['passwordExpired'] == 'Y')
                return true;
        }
        return false;
    }
    
    /**
     * 傳回使用者帳戶上傳的根目錄
     *
     * @param string $baseUploadPath - 最基礎的根目錄路徑，預設使用設定檔路徑
     * @return array 成功傳回路徑，反之傳回 false
     *
     */
    public function getBaseUploadPath($baseUploadPath = null) 
    {
        if (is_null($baseUploadPath)) $baseUploadPath = Configure::$path['upload'];
        $baseUploadPath .= '/users';
        if (!is_dir ($baseUploadPath)) {
            if (@mkdir ($baseUploadPath, 0777))
                @chmod ($baseUploadPath, 0777);
        }
        return (is_dir ($baseUploadPath)) ? $baseUploadPath : false;
    }
    
    /**
     * 傳回使用者帳戶個人上傳目錄
     *
     * @param string $username - 如無指定帳號預設使用目前登入帳號
     * @param string $baseUploadPath - 最基礎的根目錄路徑，預設使用設定檔路徑
     * @return string 成功傳回路徑，反之傳回 false
     *
     */
    public function getUploadFolder($username = null, $baseUploadPath = null) 
    {
        if (!$username) $username = $this->hasLogged();
        if (is_null($baseUploadPath)) $baseUploadPath = Configure::$path['upload'];
        if ($profile = $this->fetch(array('username' => $username))) {
            if ($baseUploadPath = $this->getBaseUploadPath($baseUploadPath)) {
                $baseUploadPath .= '/' . $username;
                if (!is_dir ($baseUploadPath)) {
                    if (@mkdir ($baseUploadPath, 0777))
                        @chmod ($baseUploadPath, 0777);
                }
                if (is_dir ($baseUploadPath))
                    return $baseUploadPath;
            }
        }
        return false;
    }
    
    /**
     * 刪除使用者帳戶個人上傳目錄
     *
     * @param mixed $username 會員帳號
     * @return boolean
     *
     */
    public function deleteUploadFolder($username) 
    {
        if ($uploadFolder = $this->getUploadFolder($username)) {
            try {
                Filesystem\File::delete($uploadFolder, true);
            } catch (exception $ex) { }
        }
        return false;
    }
    
    /**
     * 刪除資料
     * @param mixed $where
     * @return boolean
     */
    public function delete ($where)
    {
        global $database;
        
        //逐一刪除使用者帳戶個人上傳目錄
        foreach ($this->fetchAll($where) as $row) {
            $this->deleteUploadFolder($row['username']);
        }
        
        return $database->delete (
            $this->tableName, 
            $this->_whereExpression($where)
        );
    }
    
    /**
     * 傳回登入選項
     *
     * @return string
     *
     */
    public function getLoginOptions($units = null, $loop = 1) 
    {
        if (is_null ($units)) {
            $application = new COM_Application;
            $units = array ('unit' => $application->units);
        }
        
        $list = '';
        if ($loop == 1) {
            $list .= '<option value="0" selected="selected">依系統設定值</option>';
            if (Data\Variable::isFalse($application->settings['theme']['controlCenter']['disabled']))
                $list .= '<option value="default">管理中心</option>';
        }
        
        foreach ((array)$units['unit'] as $unit) {
            if ($unit['@attr']['name']) {
                if ($this->hasPower($unit['@attr']['name']))
                    $list .= '<option value="' . $unit['@attr']['name'] . '">' . $unit['@attr']['label'] . '</option>';
            }
            
            if (is_array ($unit['unit'])) {
                $list .= call_user_func (array (__CLASS__, __FUNCTION__), $unit, $loop + 1); 
            }
        }
        return $list;
    }
    
    /**
     * 傳回使用者帳戶資料
     *
     * @param string $username - 如無指定帳號預設使用目前登入帳號
     * @return string
     *
     */
    public function getProfile($username = null) 
    {
        if (!$username) $username = $this->hasLogged();
        if (!$profile = $this->fetch(array('username' => $username)))
            return false;
        return $profile;
    }

    /**
     * 傳回使用者偏好設定
     *
     * @param mixed $name
     * @param mixed $username
     * @return mixed
     *
     */
    public function getPreference($name = null, $username = null)
    {
        if (!$username) $username = $this->hasLogged();
        if ($profile = $this->getProfile()) {
            if ($profile['preference']) {
                $preference = unserialize($profile['preference']);
                if (isset($preference[$name])) return $preference[$name];
                return $preference;
            }
        }
        return false;
    }

    /**
     * 寫入使用者偏好設定
     *
     * @param mixed $preference
     * @param mixed $username
     * @return mixed
     *
     */
    public function setPreference($preference = null, $username = null)
    {
        if (!$username) $username = $this->hasLogged();
        return $this->update(
            array('username' => $username), 
            array('$preference' => Data\Variable::serialize($preference))
        );
    }

    /**
     * 重設使用者偏好設定
     *
     * @param mixed $username 
     * @return mixed
     *
     */
    public function clearPreference($username = null)
    {
        if (!$username) $username = $this->hasLogged();
        return $this->setPreference(null, $username);
    }
}

?>