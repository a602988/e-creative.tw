<?php

/**
 * 圖型處理類別
 * 
 * @version v 1.1 2009/10/26 15:32
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Drawing;

class Image
{
    /**
     *
     * 縮圖處理
     * @param string $from_filename - 來源路徑
     * @param string $save_filename - 目的路徑
     * @param integer $in_width - 縮圖寬度
     * @param integer $in_height - 縮圖高度
     * @param integer $quality  - 縮圖品質 (1 - 100)
     * @see $this->getResizePercent()
     *
     * Usage:
     *   ImageResize('ram/xxx.jpg', 'ram/ooo.jpg');
     */
    function resize ($from_filename, $save_filename, $in_width = 400, $in_height = 300, $quality = 100)
    {
        $allow_format = array ('jpeg', 'png', 'gif');
        $sub_name = $t = '';

        // Get new dimensions
        $img_info = getimagesize ($from_filename);
        $width    = $img_info['0'];
        $height   = $img_info['1'];
        $imgtype  = $img_info['2'];
        $imgtag   = $img_info['3'];
        $bits     = $img_info['bits'];
        $channels = $img_info['channels'];
        $mime     = $img_info['mime'];

        list ($t, $sub_name) = split ('/', $mime);
        if ($sub_name == 'jpg') {
            $sub_name = 'jpeg';
        }

        if (!in_array ($sub_name, $allow_format)) {
            return false;
        }

        // 取得縮圖在此範圍內的比例
        $percent = $this->getResizePercent ($width, $height, $in_width, $in_height);
        $new_width  = $width * $percent;
        $new_height = $height * $percent;

        // Resample
        $image_new = imagecreatetruecolor ($new_width, $new_height);
        $image = call_user_func ('imagecreatefrom' . $sub_name, $from_filename);
        imagecopyresampled ($image_new, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        return imagejpeg ($image_new, $save_filename, $quality);
    }

    /**
     * 抓取要縮圖的比例
     * @param integer $source_w : 來源圖片寬度
     * @param integer $source_h : 來源圖片高度
     * @param integer $inside_w : 縮圖預定寬度
     * @param integer $inside_h : 縮圖預定高度
     * @return integer
     *
     * Test:
     *   $v = (getResizePercent(1024, 768, 400, 300));
     *   echo 1024 * $v . "\n";
     *   echo  768 * $v . "\n";
     */
    function getResizePercent ($source_w, $source_h, $inside_w, $inside_h)
    {
        if ($source_w < $inside_w && $source_h < $inside_h) {
            return 1; // Percent = 1, 如果都比預計縮圖的小就不用縮
        }

        $w_percent = $inside_w / $source_w;
        $h_percent = $inside_h / $source_h;

        return ($w_percent > $h_percent) ? $h_percent : $w_percent;
    }
}

?>