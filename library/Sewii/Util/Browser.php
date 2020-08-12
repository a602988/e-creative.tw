<?php

/**
 * 瀏覽器類別
 * 
 * @version 1.0.6 2013/07/20 21:38
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Util;

use Sewii\Text\Regex;

class Browser
{
    /**
     * Internet Explorer
     * 
     * @const string
     */
    const PRODUCT_MSIE = 'msie';

    /**
     * Mozilla
     * 
     * @const string
     */
    const PRODUCT_MOZILLA = 'mozilla';

    /**
     * WebKit
     * 
     * @const string
     */
    const PRODUCT_WEBKIT = 'webkit';

    /**
     * Opera
     * 
     * @const string
     */
    const PRODUCT_OPERA = 'opera';

    /**
     * 傳回使用者代理字串
     *
     * @return string
     */
    public static function userAgent()
    {
        return @$_SERVER['HTTP_USER_AGENT'];
    }
    
    /**
     * 傳回所有產品名稱
     *
     * @return array
     */
    public static function products()
    {
        return array(
            self::PRODUCT_MSIE,
            self::PRODUCT_MOZILLA,
            self::PRODUCT_WEBKIT,
            self::PRODUCT_OPERA
        );
    }
    
    /**
     * 傳回產品名稱
     *
     * @return array
     */
    public static function product()
    {
        foreach (self::products() as $product) {
            $pattern = Regex::quote($product, '#');
            if (Regex::isMatch('#' . $pattern . '#i', self::userAgent())) {
                return $product;
            }
        }
        return null;
    }
    
    /**
     * 傳回版本號
     *
     * @return float
     */
    public static function version()
    {
        $patterns = array(
            '/(msie) ([\w.]+)/',
            '/(webkit)[ \/]([\w.]+)/',
            '/(mozilla)(?:.*? rv:([\w.]+))?/',
            '/(opera)(?:.*version)?[ \/]([\w.]+)/',
        );
        
        $ua = strtolower(self::userAgent());
        foreach ($patterns as $pattern) {
            if ($match = Regex::match($pattern, $ua)) {
                return (float) $match[2];
            }
        }

        return -1;
    }
    
    /**
     * 是否為 Internet Explorer
     *
     * @return boolean
     */
    public static function msie()
    {
        return self::product() === self::PRODUCT_MSIE;
    }
    
    /**
     * 是否為 Mozilla
     *
     * @return boolean
     */
    public static function mozilla()
    {
        return self::product() === self::PRODUCT_MOZILLA;
    }
    
    /**
     * 是否為 WebKit
     *
     * @return boolean
     */
    public static function webkit()
    {
        return self::product() === self::PRODUCT_WEBKIT;
    }
    
    /**
     * 是否為 Opera
     *
     * @return boolean
     */
    public static function opera()
    {
        return self::product() === self::PRODUCT_OPERA;
    }
}

?>