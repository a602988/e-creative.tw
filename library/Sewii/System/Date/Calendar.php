<?php

namespace Sewii\System\Date;

/**
 * 日曆類別
 * 
 * @version v 1.0.0 2011/09/17 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Calendar
{
    /**
     * 傳回某日是否為星期六
     *
     * @param mixed $year
     * @param mixed $month
     * @param mixed $day
     * @return boolean
     *
     */
    public static function isStaurday($year, $month, $day) 
    {
        $week = date('w', mktime(0, 0, 0, $month, $day, $year));
        if ($week == 0) return true;
        return false;
    }
    
    /**
     * 傳回某日是否為星期天
     *
     * @param mixed $year
     * @param mixed $month
     * @param mixed $day
     * @return boolean
     *
     */
    public static function isSunday($year, $month, $day) 
    {
        $week = date('w', mktime(0, 0, 0, $month, $day, $year));
        if ($week == 6) return true;
        return false;
    }
    
    /**
     * 傳回某日是否為週休二日
     *
     * @return boolean
     *
     */
    public static function isHoliday($year, $month, $day) 
    {
        if (self::isSunday($year, $month, $day) || self::isStaurday(self::isSunday($year, $month, $day)))
            return true;
        return false;
    }
    
    /**
     * 傳回某年是否為潤年
     * 
     * 若年號可以被四整除，但不能被一百整除，則此年即閏年，若可被四百整除不在此限。
     * @return boolean;
     * 
     **/
    public static function isLeapYear($year)
    {
        return (bool) date('L', mktime(0, 0, 0, 1, 1, $year));
    }

    /**
     * 傳回月份的最後一天
     *
     * @param integer $year 年份
     * @param integer $month 月份
     * @return integer
     *
     */
    public static function lastDayOfMonth($year, $month) 
    {
        return date('d', mktime(23, 59, 59, $month + 1, 0, $year));
    }
    
    /**
     * 傳回月份的第一天是星期幾
     * 以 0 - 6 表示，0 為星期日
     * 
     * @param integer $year 年份
     * @param integer $month 月份
     * @return integer
     *
     */
    public static function firstDayOfMonth($year, $month) 
    {
        return date('w', mktime(0, 0, 0, $month, 1, $year));
    }

    /**
     * 產生某個月份的月曆
     * 
     * @param integer $year 年份
     * @param integer $month 月份
     * @param boolean $padding 是否填補前後不足的日期格子
     * @return array 
     *
     */
    public static function generator($year, $month, $padding = false) 
    {
        $table = array();
        
        $day = 0;
        $firstDay = self::firstDayOfMonth($year, $month);
        $lastDay = self::lastDayOfMonth($year, $month);
        for ($i = 0; $i <= 5; $i++) 
        {
            $table[$i] = array();
            for ($j = 0; $j <= 6; $j++) 
            {
                $day++;
                
                //第一列
                if ($i == 0) 
                {
                    if ($j >= $firstDay) {
                        $table[$i][$j] = $day;
                    }
                    //第一天以前
                    else
                    {
                        //不計日
                        $day--;

                        //不填補
                        if (!$padding) {
                            $table[$i][$j] = null;
                            continue;
                        }
                        
                        //計算與這個月第一天相差的天數
                        $lackDay = 7 - (7 - $firstDay + $j);
                        
                        //算出相差天數的日期
                        $table[$i][$j] = sprintf('%d', date('d', mktime(0, 0, 0, $month, 1 - $lackDay, $year)));
                    }
                }
                //第二列以上
                else
                {
                    if ($day <= $lastDay) {
                        $table[$i][$j] = $day;
                    } 
                    //最後一天以後
                    else 
                    {
                        //刪除多餘列
                        if ($j == 0) {
                            unset($table[$i]);
                            break;
                        }
                        
                        //不填補
                        if (!$padding) {
                            $table[$i][$j] = null;
                            continue;
                        }

                        //計算與這個月最後一天相差的天數
                        $lackDay = $day - $lastDay;

                        //算出相差天數的日期
                        $table[$i][$j] = sprintf('%d', date('d', mktime(0, 0, 0, $month + 1, $lackDay, $year)));
                    }
                }
            }
        }
        return $table;
    }
}

?>