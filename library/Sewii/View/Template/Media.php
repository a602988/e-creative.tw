<?php

/**
 * 樣板多媒體資料類別
 * 
 * @version 1.6.1 2013/06/12 14:38
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\View\Template;

use Sewii\Uri\Uri;
use Sewii\Type\Variable;
use Sewii\Data\Json;
use Sewii\Type\Arrays;
use Sewii\Text\Regex;
use Sewii\Filesystem\File;
use Sewii\Net\Service\Google\Youtube;

class Media extends Child
{
    /**
     * 縮圖器位置
     * 
     * @const string
     */
    const THUMB_URL = '?to=thumb';
    
    /**
     * 縮圖基礎 URL
     * 
     * @todo 去除 uri
     * @var string
     */
    protected static $_baseUrl = null;

    /**
     * 編碼多媒體資料
     * 
     * @todo 去除 uri
     * @param array $media
     * @return string|null
     */
    public static function encode($media)
    {
        $result = null;
        if (is_array($media) && !empty($media)) {
            $values = array_values($media);
            $result = Variable::serialize($values);
        }
        return $result;
    }

    /**
     * 解碼多媒體資料
     * 
     * @param string $media - 經過 serialize 編碼過的字串
     * @param boolean $json - 傳回 json 格式。預設為 false
     * @return array
     */
    public static function decode($media, $json = false)
    {
        $result = array();
        if (is_string($media) && !empty($media)) {
            $media = Variable::unserialize($media);
            if (is_array($media)) $result = $media;
        }
        if ($json) $result = Json::encode($result);
        return $result;
    }

    /**
     * 傳回是否為多媒體資料
     * 
     * @param array|string $media
     * @return boolean
     */
    public static function isValid($media)
    {
        if (is_string($media) && !empty($media)) 
            $media = self::decode($media);

        if (is_array($media)) {
            if ($medium = Arrays::getFirst($media)) {
                if (is_array($medium) && !empty($medium['url']))
                    return true;
            }
        }
        return false;
    }

    /**
     * 傳回多媒體資料的預設索引
     * 
     * @param array|string $media
     * @return integer
     */
    public static function index($media, $index = 0)
    {
        if (!is_array($media)) $media = self::decode($media);

        $index = 0;
        foreach((array)$media as $i => $medium) {
            if (isset($medium['default']) && 
                Variable::isTrue($medium['default']))
                $index = $i;
        }
        return $index;
    }

    /**
     * 傳回多媒體資料
     * 
     * @param array|string $media
     * @param integer $index - 索引位置，如果省略將自動傳回預設索引
     * @return array
     */
    public static function data($media, $index = null)
    {
        if (!is_array($media)) $media = self::decode($media);
        if (is_null($index)) $index = self::index($media);

        $data = array();
        if (isset($media[$index])) 
            $data = $media[$index];
        return $data;
    }

    /**
     * 解析多媒體檔案
     * 
     * 此方法將會隱含轉換字串型式的多媒體檔案內容
     * 
     * @param medium
     * @return array
     */
    public static function parse($medium)
    {
        if (is_string($medium)) {
            if (self::isValid($medium)) $medium = self::data($medium);
            else if (!empty($medium)) $medium = array('url' => $medium);
        }
        return (array)$medium;
    }

    /**
     * 傳回縮圖 URL
     *
     * @param array|string $medium
     * @param array $options 
     * @return string
     */
    public static function thumb($medium, $options = array())
    {
        $medium = self::parse($medium);

        $url = null;
        if (!empty($medium['url']) && is_array($medium)) 
        {
            $url = $medium['url'];

            //只有圖片或 Youtube 可以縮圖
            if (File::isImage($medium['url']) || Youtube::isUrl($medium['url'])) 
            {
                //預設值
                $default = array(
                    'src' => null, //圖片
                    'w'   => null, //寬度
                    'h'   => null, //高度
                    'q'   => null, //壓縮
                    'p'   => null, //等比
                    'zc'  => null, //剪裁
                    'a'   => null, //對齊
                    'f'   => null, //濾鏡
                    's'   => null, //銳利
                    'cc'  => null, //色版
                    'ct'  => null, //透明
                );

                //參數合成
                $params = $options + $medium + $default;
                if (empty($params['src'])) 
                    $params['src'] = $medium['url'];

                //參數設定
                if ($params['src']) {
                    if (!isset(self::$_baseUrl)) self::$_baseUrl = Uri::factory()->getBase();
                    $url = self::$_baseUrl . '/' . self::THUMB_URL;

                    //濾鏡參數
                    if ($filters = Regex::grep('/^f\d+$/i', array_keys($params))) {
                        foreach ($filters as $filter) {
                            $key = intval(Regex::replace('/^\w/', '', $filter));
                            $val = intval($params[$filter]);
                            if ($val !== 0) 
                            {
                                if (empty($params['f'])) $params['f'] = '';
                                
                                //飽和度使用相反數值
                                if ($key === 4) $val = $val * -1;
                                
                                if ($params['f']) $params['f'] .= '|';
                                $params['f'] .= $key;
                                
                                //接受第二個以上參數 (only filter 3, 4, 5, 11)
                                if ($val && in_array($key, array(3, 4, 5, 11))) $params['f'] .= ',' . $val;
                            }
                        }
                    }
                    
                    //套用參數
                    $params = array_intersect_key($params, $default);
                    $params = Regex::grep('/^.+$/i', $params);
                    $url   .= '&' . Uri\Http\Query::build($params);
                }
            }
        }
        return $url;
    }

    /**
     * 設定多媒體物件
     * 
     * @throws Exception
     * @param object|string $target
     * @param array|string $medium
     * @param integer $width
     * @param integer $height
     * @param array $options
     * @param boolean $ignoreHtmlParams
     * @return phpQueryObject
     */
    public function set($target, $medium = null, $width = '', $height = '', array $options = array(), $ignoreHtmlParams = false)
    {
        if (is_array($target)) extract($target);

        //搜尋選擇器
        if (!is_object($target) && is_string($target)) 
            $target = $this->getContext($target . ':first');

        $thumbUrl = null;
        if (is_object($target)) 
        {
            $medium = self::parse($medium);

            //搜集屬性
            $params = $options + $medium;
            if (!$ignoreHtmlParams && $htmlParams = $this->getContext($target)->data(Engine::DEFAULT_PARAMS_NAME)) 
                $params = $options + $htmlParams + $medium;
            if ($width) $params['w'] = $width;
            if ($height) $params['h'] = $height;
            
            //設定詳情
            if (isset($params['info'])) 
                $this->getContext($target)->data('info', $medium);
            
            //設定標題
            if (isset($params['title'])) 
                $target->attr('title', $params['title']);

            //設定縮圖
            if ($thumbUrl = self::thumb($medium, $params)) {            
                $target->attr('src', $thumbUrl);

                //設定標籤
                if (isset($params['tag'])) 
                    $target->attr('alt', $params['tag']);
            }
            return $target;
        }

        //傳出一個空的物件
        //讓串接的時候不會發生例外
        return Template::create('<div />');
    }
}

?>