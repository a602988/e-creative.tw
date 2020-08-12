<?php

namespace Sewii\Util;

/***
 * 用戶端資訊類型
 *
 * @version v 1.0.1 2011/09/19 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class UserInfo
{
    /**
     * 傳回用戶端可讀資訊
     *
     * @param mixed $item 
     * @return mixed 
     *
     */
    public static function details($item = null)
    {        
        $details = array (
          'HTTP_ACCEPT'           => null,
          'HTTP_ACCEPT_LANGUAGE'  => null,
          'HTTP_UA_CPU'           => null,
          'HTTP_ACCEPT_ENCODING'  => null,
          'HTTP_USER_AGENT'       => null,
          'HTTP_HOST'             => null,
          'HTTP_CONNECTION'       => null,
          //'REMOTE_HOST'           => null,
          'REMOTE_ADDR'           => null,
          'REMOTE_PORT'           => null,
          'REQUEST_METHOD'        => null,
          'REQUEST_URI'           => null,
          'REQUEST_TIME'          => null,
          'SERVER_PROTOCOL'       => null,
          'HTTP_REFERER'          => null,
          'HTTP_FORWARDED'        => null,
          'HTTP_X_FORWARDED_FOR'  => null,
          'HTTP_CLIENT_IP'        => null,
          'HTTP_VIA'              => null,
          'HTTP_XROXY_CONNECTION' => null,
          'HTTP_CACHE_CONTROL'    => null,
          'HTTP_CACHE_INFO'       => null
        );

        foreach ($details as $key => &$val) {
            switch($key) {
                case 'REMOTE_HOST':
                    $val = @gethostbyaddr($_SERVER['REMOTE_ADDR']);
                    break;
                default:
                    if (isset($_SERVER[$key]))
                        $val = $_SERVER[$key];
                    break;
            }
        }

        if ($item) return $details[$item];
        return $details;
    }
}

?>