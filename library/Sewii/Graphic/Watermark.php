<?php

/**
 * 浮水印類別
 * 
 * @version v 1.1.5 2012/05/03 00:00
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Drawing;

use Sewii\Data;
use Sewii\Util;
use Sewii\Filesystem\File;

class Watermark
{
    /**
     * 儲存來源圖片物件
     * 
     * @var array 
     */
    private $_source;

    /**
     * 儲存浮水印圖片物件
     * 
     * @var array 
     */
    private $_watermark;
    
    /**
     * 合成品質 (1-100)
     * 設定為 0 時表示不變更品質
     * 
     * @var integer 
     */
    const QUALITY = 0;
    
    /**
     * 浮水印百分比 (1-100)
     * 設定為 0 時表示不重新調整尺寸
     * 
     * @var integer 
     */
    const size = 50;
    
    /**
     * 印透明度
     * 
     * @var integer 
     */
    const TRANSPARENT = 30;
    
    /**
     * 邊界距離
     * @var integer 
     */
    const MARGIN = 0;
    
    /**
     * 記憶體使用限制
     * 
     * @var string
     */
    const MEMORY_LIMIT = '128M';
    
    /**
     * 浮水印位置 (置中靠左)
     * 
     * @var integer 
     */
    const POSITION_MIDDLE_LEFT = 'l';
    
    /**
     * 浮水印位置 (置中)
     * 
     * @var integer 
     */
    const POSITION_MIDDLE = 'c';
    
    /**
     * 浮水印位置 (置中靠右)
     * 
     * @var integer 
     */
    const POSITION_MIDDLE_RIGHT = 'r';
    
    /**
     * 浮水印位置 (置上靠左)
     * 
     * @var integer 
     */
    const POSITION_TOP_LEFT = 'tl';
    
    /**
     * 浮水印位置 (置上)
     * 
     * @var integer 
     */
    const POSITION_TOP = 't';
    
    /**
     * 浮水印位置 (置上靠右)
     * 
     * @var integer 
     */
    const POSITION_TOP_RIGHT = 'tr';
    
    /**
     * 浮水印位置 (置下靠左)
     * 
     * @var integer 
     */
    const POSITION_BOTTOM_LEFT ='bl';
    
    /**
     * 浮水印位置 (置下)
     * 
     * @var integer 
     */
    const POSITION_BOTTOM = 'b';
    
    /**
     * 浮水印位置 (置下靠右)
     * 
     * @var integer 
     */
    const POSITION_BOTTOM_RIGHT = 'br';

    /**
     * 建構子
     * 
     * @param string $source
     * @throws Exception
     */
    public function __construct($source = null)
    {
        @ini_set ('memory_limit', self::MEMORY_LIMIT);
        if (!is_null($source))
            $this->_source($source);
    }

    /**
     * 設定原始檔
     *
     * @param string $filename
     * @throws Exception
     *
     */    
    public function source($filename)
    {
        $this->_source = $this->_make($filename);
    }

    /**
     * 套用浮水印
     *
     * @param string $watermark
     * @param int $position
     * @param int $transparent
     * @return bool
     * @throws Exception
     *
     */
    public function apply($watermark, $position = null, $size = null, $transparent = null, $margin = null)
    {
        if (is_null($size)) $size = self::size;
        if (is_null($position)) $position = self::POSITION_MIDDLE;
        if (is_null($transparent)) $transparent = self::TRANSPARENT;
        if (is_null($margin)) $margin = self::MARGIN;

        $this->_watermark = $this->_make($watermark);
        $this->_autoResizeWatermark($size);

        if ($position = $this->_getPosition($position, $margin)) {
            return $this->_imageCopyMergeAlpha(
                $this->_source['stream'], 
                $this->_watermark['stream'],
                $position['x'], 
                $position['y'], 
                $xSource = 0,
                $ySource = 0,
                $this->_watermark['width'], 
                $this->_watermark['height'], 
                $transparent
            );
        }
        return false;
    }

    /**
     * 另存檔案
     *
     * @param string $filename
     * @param int $quality (1-100)
     * @return bool
     *
     */
    public function saveAs($filename, $quality = null) 
    {
        return $this->_output($filename, $quality);
    }

    /**
     * 顯示圖片
     *
     * @param int $quality (1-100)
     * @return bool
     *
     */
    public function show($quality = null) 
    {
        return $this->_output(null, $quality);
    }

    /**
     * 輸出圖片
     *
     * @param string $filename
     * @param int $quality (1-100)
     * @return bool
     *
     */
    private function _output($filename, $quality = null)
    {
        if ($this->_source) 
        {
            $args = array($this->_source['stream'], $filename);
            if (is_null($quality)) $quality = self::QUALITY;
            if ($quality > 0) array_push($args, $quality);
            
            if (!$filename)
                header('Content-Disposition: inline; filename = "' . $this->_source['filename'] . '"');
            
            switch ($this->_source['type']) 
            {
			    case IMAGETYPE_GIF:
                    if (!$filename) header('Content-type: image/gif');
                    if (isset($args[2])) unset($args[2]);
                    return call_user_func_array('imageGIF', $args);
                    break;

			    case IMAGETYPE_JPEG:
                    if (!$filename) header('Content-type: image/jpeg');
                    return call_user_func_array('imageJPEG', $args);
                    break;

			    case IMAGETYPE_PNG:
                    if (!$filename) header('Content-type: image/png');
                    if (isset($args[2])) $args[2] = $this->_pngQuality($quality);
                    imageSaveAlpha($this->_source['stream'], true);
                    return call_user_func_array('ImagePNG', $args);
                    break;
            }
        }
        return false;
    }

    /**
     * 格式化 PNG 壓縮率
     * 輸入 1-100 自動轉換為 png 壓縮率 0-9
     *
     * @param int $value
     * @return int
     *
     */
    private function _pngQuality($value)
    {
        $quality = (intval($value) - 100) / 11.111111;
        $quality = round(abs($quality));
        return $quality;
    }

    /**
     * 傳回浮水印的 x, y 座標
     *
     * @param int $position
     * @param mixed $margin
     * @return array
     *
     */
    private function _getPosition($position, $margin = self::margin)
    {
        if ($margin > 1) $margin = $margin / 100;

        switch($position)
        {
            case self::POSITION_MIDDLE_LEFT:
                $x = 0;
                $y = ($this->_source['height'] / 2) - ($this->_watermark['height'] / 2); 
                $x = $this->_watermark['width'] * $margin;
                break;

            case self::POSITION_MIDDLE:
            default:
                $x = ($this->_source['width'] / 2) - ($this->_watermark['width'] / 2); 
                $y = ($this->_source['height'] / 2) - ($this->_watermark['height'] / 2); 
                break;

            case self::POSITION_MIDDLE_RIGHT:
                $x = $this->_source['width'] - $this->_watermark['width']; 
                $y = ($this->_source['height'] / 2) - ($this->_watermark['height'] / 2); 
                $x -= $this->_watermark['width'] * $margin;
                break;

            case self::POSITION_TOP_LEFT:
                $x = 0; 
                $y = 0; 
                $x += $this->_watermark['width'] * $margin;
                $y += $this->_watermark['height'] * $margin;
                break;

            case self::POSITION_TOP:
                $x = ($this->_source['width'] - $this->_watermark['width']) / 2; 
                $y = 0; 
                $y += $this->_watermark['height'] * $margin;
                break;

            case self::POSITION_TOP_RIGHT:
                $x = $this->_source['width'] - $this->_watermark['width']; 
                $y = 0; 
                $x -= $this->_watermark['width'] * $margin;
                $y += $this->_watermark['height'] * $margin;
                break;

            case self::POSITION_BOTTOM_LEFT:
                $x = 0; 
                $y = $this->_source['height'] - $this->_watermark['height']; 
                $x += $this->_watermark['width'] * $margin;
                $y -= $this->_watermark['height'] * $margin;
                break;

            case self::POSITION_BOTTOM:
                $x = ($this->_source['width'] - $this->_watermark['width']) / 2; 
                $y = $this->_source['height'] - $this->_watermark['height']; 
                $y -= $this->_watermark['height'] * $margin;
                break;

            case self::POSITION_BOTTOM_RIGHT:
                $x = $this->_source['width'] - $this->_watermark['width']; 
                $y = $this->_source['height'] - $this->_watermark['height']; 
                $x -= $this->_watermark['width'] * $margin;
                $y -= $this->_watermark['height'] * $margin;
                break;
        }
        return array('x' => $x, 'y' => $y);
    }

    /**
     * 重新調整浮水印尺寸
     * 
     * @param $size
     * @return bool
     */
    private function _autoResizeWatermark($size)
    {
        if ($this->_watermark) 
        {
            if (($size /= 100) > 0) 
            {
                $width = $this->_watermark['width'];
                $height = $this->_watermark['height'];
                $newWidth = $width;
                $newHeight = $height;

                $scaleX = $width / ($this->_source['width'] * $size); 
                $scaleY = $height / ($this->_source['height'] * $size); 
                if ($scaleY > $scaleX){ 
                    $newWidth = round($width * (1 / $scaleY)); 
                    $newHeight = round($height * (1 / $scaleY)); 
                }
                else {
                    $newWidth = round($width * (1 / $scaleX)); 
                    $newHeight = round($height * (1 / $scaleX)); 
                }

                $canvas = imageCreateTrueColor($newWidth, $newHeight);
                $transparent = imageColorAllocate($canvas, 0, 0, 0);
                imageColorTransparent($canvas, $transparent);
                imageAlphaBlending($canvas, false);
                imageSaveAlpha($canvas, true);
                if (imageCopyResampled($canvas, $this->_watermark['stream'], 0, 0, 0, 0, $newWidth, $newHeight, $width, $height)) {
                    $this->_watermark['stream'] = $canvas;
                    $this->_watermark['width'] = $newWidth;
                    $this->_watermark['height'] = $newHeight;
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 製作物件
     *
     * @param string $filename
     * @return array
     * @throws Exception
     *
     */
    private function _make($filename)
    {
        if (!file_exists($filename))
            throw new exception('檔案不存在 ' . $filename);

        $prepare = array();
        $prepare = $this->_getImageInfo($filename);
        $prepare['stream'] = $this->_imageCreateFromType($prepare['type'], $filename);
        return $prepare;
    }

    /**
     * 傳回圖片詳細資訊
     *
     * @param string $filename
     * @return array
     *
     */
    private function _getImageInfo($filename) 
    {
        $info = array();
        if ($_info = getImageSize($filename)) {
            $info['path']     = $filename;
            $info['basename'] = File::getBasename($filename);
            $info['filename'] = File::getFilename($info['basename']);
            $info['width']    = Data\Arrays::value($_info, 0);
            $info['height']   = Data\Arrays::value($_info, 1);
            $info['type']     = Data\Arrays::value($_info, 2);
            $info['bits']     = Data\Arrays::value($_info, 'bits');
            $info['channels'] = Data\Arrays::value($_info, 'channels');
            $info['mime']     = Data\Arrays::value($_info, 'mime');
        }
        return $info;
    }

    /**
     * 開啟圖片串流
     *
     * @param string $type
     * @param string $filename
     * @return resource
     *
     */
    private function _imageCreateFromType($type, $filename) 
    {
        $stream = null;
        switch ($type) 
        {
			case IMAGETYPE_GIF:
                $stream = imageCreateFromGif($filename);
                break;

			case IMAGETYPE_JPEG:
                //忽略開啟 JPG 檔案時可能會產生的警告錯誤
                //某些軟體存儲的圖片檔案結尾處可能有不正確內容
                //imagecreatefromjpeg() recoverable error: Premature end of JPEG file
                @ini_set('gd.jpeg_ignore_warning', 1);
                $stream = imageCreateFromJpeg($filename);
                break;

			case IMAGETYPE_PNG:
                $stream = imageCreateFromPNG($filename);
                break;
        }
        return $stream;
    }

    /**
     * 合併二張圖片 (精準版)
     * (此方法支援 alpha 透明)
     *
     * @param mixed $dst_im 
     * @param mixed $src_im 
     * @param mixed $dst_x
     * @param mixed $dst_y
     * @param mixed $src_x
     * @param mixed $src_y
     * @param mixed $src_w
     * @param mixed $src_h
     * @param mixed $pct
     *
     */
    private function _imageCopyMergeAlpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    { 
        if (!isset($pct)) {
            return false;
        }
        $pct /= 100;

        // Get image width and height
        $w = imagesx($src_im);
        $h = imagesy($src_im);

        // Turn alpha blending off
        imagealphablending ($src_im, false);

        // Find the most opaque pixel in the image (the one with the smallest alpha value)
        $minalpha = 127;
        for ( $x = 0; $x < $w; $x++ )
        for ($y = 0; $y < $h; $y++) {
            $alpha = (imagecolorat($src_im, $x, $y) >> 24) & 0xFF;
            if ($alpha < $minalpha) {
                $minalpha = $alpha;
            }
        }

        //loop through image pixels and modify alpha for each
        for ($x = 0; $x < $w; $x++)
        {
            for ($y = 0; $y < $h; $y++)
            {
                //get current alpha value (represents the TANSPARENCY!)
                $colorxy = imagecolorat($src_im, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;

                //calculate new alpha
                if ($minalpha !== 127) {
                    $alpha = 127 + 127 * $pct * ($alpha - 127) / (127 - $minalpha);
                } else {
                    $alpha += 127 * $pct;
                }

                //get the color index with new alpha
                $alphacolorxy = imagecolorallocatealpha($src_im, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);

                //set pixel with the new color + opacity
                if (!imagesetpixel($src_im, $x, $y, $alphacolorxy)) {
                    return false;
                }
            }
        }

        // The image copy
        imagecopy ($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h);
    }

    /**
     * 合併二張圖片 (快速版)
     * (此方法支援 alpha 透明)
     *
     * @param mixed $dst_im 
     * @param mixed $src_im 
     * @param mixed $dst_x
     * @param mixed $dst_y
     * @param mixed $src_x
     * @param mixed $src_y
     * @param mixed $src_w
     * @param mixed $src_h
     * @param mixed $pct
     *
     */
    private function _imageCopyMergeAlpha2($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct)
    { 
        // creating a cut resource 
        $cut = imagecreatetruecolor($src_w, $src_h); 

        // copying relevant section from background to the cut resource 
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h); 
        
        // copying relevant section from watermark to the cut resource 
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h); 
        
        // insert cut resource to destination image 
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
    } 
}

?>