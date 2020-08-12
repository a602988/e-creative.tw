<?php

/**
 * 字串類別
 * 
 * @version 1.9.11 2016/04/14 16:13
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2016 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Text;

class Strings
{
    /**
     * 截斷指定長度
     *
     * 這個方法可以補足中英文字串長度切割出來字寬不同的問題
     * 英文字體長度約為中文 1/2，所以遇到英文字元切割長度自動加倍
     *
     * @todo Performance!!
     * @param string $content - 字串內容
     * @param string $length - 字串長度
     * @param string $endSign - 如果成功截斷字串附加在結尾處的記號表示。
     * @param string $encoding - 指定字串編碼。預設為 UTF-8
     * @return string
     */
    public static function breaking($content, $length, $endSign = '...', $encoding = 'UTF-8')
    {
        if ($length >= 1) {
            if (mb_strlen($content, $encoding) >= $length) {
                for ($i = 0, $mixeSize = $length; $i < $length; $i++) {
                    //如果第一個元位組小於 ASCII 128 即為英文
                    if (ord ($content[$i]) < 128) $mixeSize += 0.8; //暫定 0.8 倍
                }
                $breaking = mb_substr($content, 0, (int)$mixeSize, $encoding);
                if ($content != $breaking)
                    if ($endSign) $breaking = $breaking . $endSign;
                return $breaking;
            }
        }
        return $content;
    }
    
    /**
     * 傳回摘要內容
     *
     * @todo Performance!!
     * @param string $content - 字串內容
     * @param string $length - 字串長度
     * @param string $endSign - 如果成功截斷字串附加在結尾處的記號表示。
     * @return string
     *
     */
    public static function summary($content, $length = 0, $endSign = '...') 
    {
        $content = Regex::replace('/\s{2,}/', ' ', strip_tags($content));
        $content = Regex::replace('/[\r\n\t]/', ' ', $content);
        $content = Regex::replace('/&\w+;/', ' ', $content);
        $content = trim($content);
        return self::breaking($content, $length, $endSign);
    }

    /**
     * 產生隨機密碼
     *
     * @todo Performance!!
     * @param string $length 密碼長度
     * @param string $chars 密碼字元集合
     * @return string
     */
    public static function password($length = 6, $chars = 'abcdefghijkmnpqrstuvwxyz23456789')
    {
        for ($i = 0; $i < $length; $i++)
            $password .= $chars[rand() % strlen ($chars)];
        return $password;
    }

    /**
     * 產生絕對唯一的流水編號
     *
     * @todo Performance!!
     * @param string $eng - 英文長度
     * @param string $num - 數字長度
     * @return string
     */
    public static function serial($eng = 2, $num = 8)
    {
        $engKey = Regex::matches('/[[:alpha:]]/', strtoupper (md5 (uniqid (mt_srand ((double) microtime () * 99999), true))));
        $numKey = Regex::matches('/[[:digit:]]/', strtoupper (md5 (uniqid (mt_srand ((double) microtime () * 99999), true))));

        mt_srand ((double) microtime () * 10000000);

        $engRand = $numRand = '';
        
        if ($eng) {
            $eRand = array_rand ($engKey[0], $eng);
            foreach ((array) $eRand as $eAKey) $engRand.= $engKey[0][$eAKey];
        }

        if ($num) {
            $nRand = array_rand ($numKey[0], $num);
            foreach ((array) $nRand as $nAKey) $numRand.= $numKey[0][$nAKey];
        }
        
        return $engRand . $numRand;
    }

    /**
     *  轉換新行成為 HTML 的 <br />
     *
     * @todo Performance!!
     * @param string $content
     * @param boolean $isXHTML - XHTML 關閉標籤，預設為 true
     * @return string
     */
    public static function nl2br($content, $isXHTML = true)
    {
        $br = ($isXHTML) ? '<br />' : '<br>';
        return strtr (
            $content, 
            array ("\r\n" => $br, "\r" => $br, "\n" => $br)
        ); 
    }

    /**
     * 轉換特殊字元成為 HTML 實體
     * 
     * @todo Performance!!
     * @param mixed $content - 字串或是陣列
     * @param boolean $nl2br - 轉換新行成為 HTML 的 <br />
     * @param boolean $removTags - 去除標籤
     * @return string
     */
    public static function html2Text($content, $nl2br = false, $removTags = false)
    {
        $contents = array ();
        if (is_array ($content)) {
            foreach ($content as $k=> $v) {
                $contents[$k] = self::html2Text ($v, $nl2br, $removTags);
            }
        } else {
            if (is_string($content)) {
                if ($removTags) $content = strip_tags ($content);
                $content = htmlspecialchars ($content);
                if ($nl2br) $content = self::nl2br ($content);
            }
        }
        if ($contents) return $contents;
        return $content;
    }

    /**
     * 轉換 HTML 實體成為特殊字元
     * 
     * @param mixed $content
     * @return string
     */
    public static function text2Html($content)
    {
        return strtr($content, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
    }
    
    /**
     * 為內容加上 HTML 超連結
     *
     * @param string $content
     * @return string
     *
     */
    public static function htmlHyperlink($content) 
    {
        $search = array(
            '/((http[s]?)|(ftp)|(telnet)|(gopher)|(news)|(mms)|(rtsp)|(pmn)|(ed2k)|(pdl))+:\/\/[^<>[:space:]]+[[:alnum:]\/]/i'
        );

        $modify = array(
            '<a href="$0" target="_blank">\\0</a>'
        );
        
        return Regex::replace($search,$modify, $content);
    }
    
    /**
     * 設定字頭
     *
     * @param string $string
     * @param string $add
     * @return string
     */
    public static function setPrefix($string, $add) 
    {
        $firstChar = substr($string, 0, 1);
        if ($firstChar !== $add) {
            $string = $add . $string;
        }
        return $string;
    }
    
    /**
     * 設定字尾
     *
     * @param string $string
     * @param string $add
     * @return string
     */
    public static function setSuffix($string, $add) 
    {
        $lastChar = substr($string, -1);
        if ($lastChar !== $add) {
            $string = $add . $string;
        }
        return $string;
    }
    
    /**
     * 傳回字串中是否包含非 ASCII 字元
     *
     * @param string $string
     * @return boolean
     */
    public static function hasNonAscii($string)
    {
        return Regex::IsMatch('/[^(\x20-\x7F)]+/', $string);
    }
    
    /**
     * 傳回指定字元於字串中首次出現的位置
     * 
     * @param string $string
     * @param string $find
     * @param integer $offset
     * @return integer 如果找到將傳回以 0 開始的索引，如果找不到時傳回 -1
     */
    public static function indexOf($string, $find, $offset = 0)
    {
        if (empty($string) || empty($find)) return -1;
        $index = strpos($string, $find, $offset);
        return $index === false ? -1 : $index;
    }
    
    /**
     * 傳回指定字元於字串中首次出現的位置
     * 
     * 此方法將會忽略大小寫比對
     * 
     * @param string $string
     * @param string $find
     * @param integer $offset
     * @return integer 如果找到將傳回以 0 開始的索引，如果找不到時傳回 -1
     */
    public static function iindexOf($string, $find, $offset = 0)
    {
        if (empty($string) || empty($find)) return -1;
        $index = stripos($string, $find, $offset);
        return $index === false ? -1 : $index;
    }
    
    /**
     * 傳回字串中是否包含指定的字元
     * 
     * @param string $find
     * @param string $string
     * @return boolean
     */
    public static function contains($find, $string)
    {
        return (self::indexOf($string, $find) !== -1);
    }
    
    /**
     * 傳回字串中是否包含指定的字元
     * 
     * 此方法將會忽略大小寫比對
     * 
     * @param string $find
     * @param string $string
     * @return boolean
     */
    public static function icontains($find, $string)
    {
        return (self::iindexOf($string, $find) !== -1);
    }
    
    /**
     * 不分大小寫比較二個字串是否一樣
     * 
     * @param string $a
     * @param string $b
     * @return boolean
     */
    public static function isEqual($a, $b)
    {
        return (strcasecmp($a, $b) === 0);
    }
}

?>