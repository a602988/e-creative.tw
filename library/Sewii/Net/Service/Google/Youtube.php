<?php

namespace Sewii\Net\Service\Google;

/**
 * Youtube 類別
 * 
 * @version v 1.0.0 2011/12/03 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Youtube
{
    /**
     * 檢查影片網址是否正確
     *
     * @param string url 影片網址
     * @return boolean
     */
    public static function isUrl($url)
    {
        return preg_match('/^http:\/\/(www)?.youtube.com\/watch\?v=.+/i', $url);
    }

    /**
     * 傳回影片 ID
     *
     * @param string url 影片網址
     * @return boolean
     */
    public static function getId($url)
    {
        if (self::isUrl($url) && 
            preg_match('/[\\?&]v=([^&#]*)/', $url, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * 傳回影片的預覽縮圖
     *
     * @param string url 影片網址
     * @param string size 圖片尺寸
     * @return mixed 傳回圖片 url
     */
	function getThumb ($url, $size = null) 
    {
        $videoId = self::getId($url);
        if ($videoId) {
            switch($size) {
                case '0':
                    return 'http://img.youtube.com/vi/' . videoId . '/0.jpg';
                case '1':
                    return 'http://img.youtube.com/vi/' . videoId . '/1.jpg';
                case '2':
                    return 'http://img.youtube.com/vi/' . videoId . '/2.jpg';
                default:
                    return 'http://img.youtube.com/vi/' . videoId . '/default.jpg';
            }
        }
        return null;
	}

    /**
     * 傳回影片的播放網址
     *
     * @param string url 影片網址
     * @return mixed 影片網址 url
     */
	function getVideo ($url) 
    {
        $videoId = self::getId($url);
        if ($videoId) return 'http://www.youtube.com/v/' . $videoId;
        return null;
	}
}

?>