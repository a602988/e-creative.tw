<?php

namespace Sewii\System\Date;

/**
 * 日期類別
 * 
 * @version v 1.0.0 2011/09/17 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Date
{
    /**
     * 格式化英文日期成中文
     *
     * @param string $date 一個時間字串
     * @param mstring $title 附加星期幾前的中文字串
     * @return string
     *
     */
    public static function toLocale($date, $weekTitle = '星期') 
    {
        $date = strtr ($date, array (
            'Monday'    => $weekTitle . '一',
            'Tuesday'   => $weekTitle . '二',
            'Wednesday' => $weekTitle . '三',
            'Thursday'  => $weekTitle . '四',
            'Friday'    => $weekTitle . '五',
            'Saturday'  => $weekTitle . '六',
            'Sunday'    => $weekTitle . '日',
            'Mon'       => $weekTitle . '一',
            'Tue'       => $weekTitle . '二',
            'Wed'       => $weekTitle . '三',
            'Thu'       => $weekTitle . '四',
            'Fri'       => $weekTitle . '五',
            'Sat'       => $weekTitle . '六',
            'Sun'       => $weekTitle . '日',
            'AM'        => '上午',
            'PM'        => '下午'
        ));
        
        return $date;
    }
}

?>