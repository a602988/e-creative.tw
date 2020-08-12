<?php

namespace Sewii\Intl;

use Sewii\System;
use Sewii\Data;
use Sewii\Util;
use Sewii\Uri;
use Sewii\Http;
use Sewii\Session;

/**
 * 多國語系類別
 * 
 * @version v 2.0.8 2012/08/07 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Intl extends System\Singleton
{
    /**
     * 偵測使用者語系
     * @var boolean
     */
    public $detectUserLang = false;

    /**
     * 支援的語系清單
     * @var array
     */
    protected $_languages = array
    (
        'TW' => array(
            'name' => 'zh-TW',
            'subject' => '繁體中文',
            'local' => '中文 (繁體)',
            'description' => null,
            'direction' => 'L'
        ),
        'CN' => array(
            'direction' => 'L',
            'name' => 'zh-CN',
            'subject' => '簡體中文',
            'local' => '简体中文',
            'description' => null,
            'direction' => 'L'
        ),
        'JA' => array(
            'name' => 'ja-JP',
            'subject' => '日本語',
            'local' => '日本語',
            'description' => null,
            'direction' => 'L'
        ),
        'EN' => array(
            'name' => 'en-US',
            'subject' => '英文',
            'local' => 'English',
            'description' => null,
            'direction' => 'L'
        ),
        'ES' => array(
            'name' => 'es-ES',
            'subject' => '西班牙語',
            'local' => 'Español',
            'description' => null,
            'direction' => 'L'
        )
    );
    
    /**
     * 表示多語系欄位的正規樣式
     * @const string
     */
    const SESSION_CURRENT = '__SEWII_INTL_CURRENT';
    
    /**
     * 表示變更語系的 QueryString 欄位
     * @const string
     */
    const FIELD_CHANGE = 'intl';
    
    /**
     * 表示重新導向頁面的 QueryString 欄位
     * @const string
     */
    const FIELD_REDIRECT = 'redirect';

    /**
     * 初始化
     * 
     * @return Sewii\Intl\Intl
     */
    public function init() 
    {
        $this->_preinitialize();
        $this->_onListener();
        return $this;
    }

    /**
     * 偵聽器
     */
    protected function _onListener() 
    {
        //偵聽語系變更事件
        if (!empty($_GET[self::FIELD_CHANGE])) {
            $isRedirect = empty($_GET[self::FIELD_REDIRECT]) ? false : true;
            $this->_onChange($_GET[self::FIELD_CHANGE], $isRedirect);
        }
    }

    /**
     * 變更語系事件
     *
     * @param string $lang
     * @param boolean $isRedirect 是否重新導向頁面
     */
    protected function _onChange($lang, $isRedirect = false)
    {
        $lang = $this->format($lang);
        if ($this->change($lang)) {
            if ($isRedirect) $this->redirect();
        }
    }

    /**
     * 傳回格式化後的語系代碼 (2碼)
     *
     * @param mixed $code
     * @return string
     *
     */
    public function format($lang)
    {
        $lang = strtoupper($lang);
        switch($lang) 
        {
            //中文語系
            case 'ZH-CN':
            case 'ZH-TW':

            //葡萄牙語
            case 'PT-BR':
            case 'PT-PT':

            //荷蘭語
            case 'FY-NL':
                //後 2 碼
                return preg_replace('/^[a-z]{2}\-/i', '', $lang);

            //英文語系
            case 'EN-ZA':
            case 'EN-GB':
            case 'EN-US':

            //西班牙語系
            case 'ES-AR':
            case 'ES-CL':
            case 'ES-ES':
            case 'ES-MX':

            //愛爾蘭
            case 'GA-IE':

            //瑞典
            case 'SV-SE':

            default:
                //前 2 碼
                return preg_replace('/\-[a-z]{2}$/i', '', $lang);
        }
    }
    
    /**
     * 傳回是否支援的語系
     *
     * @param string $lang
     * @return boolean
     *
     */
    public function isSupport($lang)
    {
        $lang = $this->format($lang);
        return (isset($this->_languages[$lang])) ? true : false;    
    }
    
    /**
     * 傳回是否為目前的語系
     *
     * @param string $lang
     * @return boolean
     *
     */
    public function isCurrent($lang)
    {
        $lang = $this->format($lang);
        return ($this->getCurrent() == $lang) ? true : false;    
    }

    /**
     * 傳回預設語系
     *
     * @return string
     *
     */
    public function getDefault() 
    {
        //偵測使用者語系
        if ($this->detectUserLang) {
            $httpAcceptLangs = explode(',', $_SERVER["HTTP_ACCEPT_LANGUAGE"]);
            foreach($httpAcceptLangs as $httpAcceptLang) {
                list($lang, $other) = array_pad(explode(';', $httpAcceptLang), 2, null);
                $lang = $this->format($lang);
                if ($this->isSupport($lang))
                    return $lang;
            }
        }

        //預設為第一個語系
        foreach ($this->_languages as $code => $info) 
            return $code;

        return null;
    }

    /**
     * 傳回目前語系
     *
     * @return string
     */
    public function getCurrent()
    {
        $Session = new Session\Namespaces(__CLASS__);
        if (!empty($Session->{self::SESSION_CURRENT}))
            return $Session->{self::SESSION_CURRENT};
        return $this->getDefault();
    }
    
    /**
     * 傳回語系當地名稱
     *
     * @param string $lang
     * @return string|null
     *
     */
    public function getLocal($lang = null) 
    {
        if (is_null($lang)) $lang = $this->getCurrent();
        $lang = $this->format($lang);
        if ($this->isSupport($lang)) {
            $info = $this->_languages[$lang];
            $local = $info['subject'];
            if (!empty($info['local']))
                $info['subject'] = $info['local'];
            return $local;
        }
        return null;
    }

    /**
     * 傳回語系清單
     *
     * @return array
     */
    public function getLanguages()
    {
        return $this->_languages;
    }

    /**
     * 設定語系清單
     *
     * @return array
     */
    public function setLanguages($langs)
    {
        $this->_languages = $langs;
    }
    
    /**
     * 變更目前語系
     *
     * @param string $lang
     * @return boolean
     */
    public function change($lang) 
    {
        $Session = new Session\Namespaces(__CLASS__);
        if ($this->isSupport($lang)) {
            $Session->{self::SESSION_CURRENT} = $lang;
            return true;
        }
        return false;
    }

    /**
     * 重新導入頁面
     *
     * @return void
     */
    public function redirect() 
    {
        $Rewrite = Http\Rewrite::getInstance();
        if ($referer = $_SERVER['HTTP_REFERER']) {
            if (!preg_match('/\.swf$/', $referer)) {
                $Uri = Uri\Uri::factory($referer);
                $Uri->query('intl=' . strtolower($this->getCurrent()), self::FIELD_REDIRECT);
                $Uri->redirect();
            }
        }
        Http\Response::redirect('./');
    }

    /**
     * 傳回符合語系的內容
     *
     * @param array|string $context
     * @return mixed
     */
    public function getContext($context)
    {
        if (is_array($context)) {
            $current = $this->getCurrent();
            if (isset($context[$current])) return $context[$current];
            Data\Arrays::getFirst($context);
        }
        return $context;
    }
    
    /**
     * 繁體中文轉簡體中文
     *
     * @param string $content
     * @return string
     */
    public function big5ToGbk($content)
    {
        include_once(dirname(__FILE__) . '/Intl/maps/big5_to_gbk.php');
        $content = strtr($content, $map);
        return $content;
    }
    
    /**
     * 簡體中文轉繁體中文
     *
     * @param string $content
     * @return string
     */
    public function gbkToBig5($content)
    {
        include_once(dirname(__FILE__) . '/Intl/maps/gbk_to_big5.php');
        $content = strtr($content, $map);
        return $content;
    }
}

?>