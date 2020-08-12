<?php

namespace Sewii\Http;

/**
 * ClientUrl 類別
 * 
 * @version v 1.1.0 2011/09/12 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class ClientUrl
{
    /**
     * curl 控制器
     *
     * @var mixed 
     *
     */
    public $handle;

    /**
     * 建構子
     * 初始化一個 url 對話
     *
     * @param string $url
     * @return resource
     *
     */
    public function __construct ($url = null) 
    {
        return $this->handle = curl_init($url);
    }
    
    /**
     * 解構子
     * 關閉 url 對話
     */
    public function __destruct ()
    {
        if (is_resource($this->handle))
            curl_close($this->handle);
    }

    /**
     * 選項設定
     *
     * @see http://www.php.net/manual/en/function.curl-setopt.php
     * @param integer $option - curl 選項，可傳入一個陣列地圖一次設定
     * @param mixed $value curl 選項值
     * @return boolean
     *
     */
    public function option($option, $value = null)
    {
        return (is_array ($option)) 
            ? curl_setopt_array($this->handle, $option)
            : curl_setopt($this->handle, $option, $value);
    }

    /**
     * 執行 url 對話並傳回結果
     *
     * @param boolean $output 如果為 true 時將自動輸出返回結果
     * @return mixed
     *
     */
    public function execute($output = false)
    {
        //設定 CURLOPT_RETURNTRANSFE 選項抑制輸出
        if (!$output) $this->option(CURLOPT_RETURNTRANSFER, true);
        return curl_exec($this->handle);
    }

    /**
     * 傳回詳細資訊
     *
     * @see http://www.php.net/manual/en/function.curl-getinfo.php
     * @param integer $option - 傳回選項
     * @return mixed - 如果沒有設定選項預設傳回一個關聯陣列
     *
     */
    public function detail($option = null) 
    {
        if ($error = $this->error()) return false;
        $args = array($this->handle);
        if (!is_null($option)) $args[] = $option;
        return call_user_func_array('curl_getinfo', $args);
    }

    /**
     * 傳回錯誤
     *
     * @return mixed
     *
     */
    public function error($onlyMessage = true) 
    {
        if ($error = curl_error($this->handle)) {
            if ($onlyMessage) return $error;
            return array('code' => curl_errno($this->handle), 'message' => $error);
        }
        return false;
    }
}

?>