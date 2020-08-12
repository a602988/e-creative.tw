<?php

namespace Sewii\System\Date;

/**
 * 時間類別
 * 
 * @version v 1.0.0 2011/09/17 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Time
{
    /**
     * 傳回已經過時間並格式化為易懂的可讀格式
     *
     * @param int $time - timestamp
     * @return string
     */
    public static function toElapsed($time)
    {
        $year   = floor($time / 60 / 60 / 24 / 365);
        $time  -= $year * 60 * 60 * 24 * 365;

        $month  = floor($time / 60 / 60 / 24 / 30);
        $time  -= $month * 60 * 60 * 24 * 30;

        $week   = floor($time / 60 / 60 / 24 / 7);
        $time  -= $week * 60 * 60 * 24 * 7;

        $day    = floor($time / 60 / 60 / 24);
        $time  -= $day * 60 * 60 * 24;

        $hour   = floor($time / 60 / 60);
        $time  -= $hour * 60 * 60;

        $minute = floor($time / 60);
        $time  -= $minute * 60;

        $second = $time;
        $elapse = '';

        $unitArr = array (
            '年'   => 'year', 
            '個月' => 'month',  
            '周'   => 'week', 
            '天'   => 'day', 
            '小時' => 'hour', 
            '分鐘' => 'minute', 
            '秒'   => 'second'
        );

       foreach ($unitArr as $cn => $u)
       {
           if ($$u > 0)
           {
               $elapse = $$u . ' ' . $cn;
               break;
           }
       }
       return $elapse;
    }

	/**
	 * 格式化為 Time-Span
	 * 
	 * @param float $sec
	 * @param boolean $padHours
	 * @return string
	 */
	public static function toSpan($sec, $padHours = true) 
    {
		// holds formatted string
		$hms = "";

		// there are 3600 seconds in an hour, so if we
		// divide total seconds by 3600 and throw away
		// the remainder, we've got the number of hours
		$hours = intval(intval($sec) / 3600); 

		// add to $hms, with a leading 0 if asked for
		$hms .= ($padHours) 
		      ? str_pad($hours, 2, "0", STR_PAD_LEFT). ':'
		      : $hours. ':';
		 
		// dividing the total seconds by 60 will give us
		// the number of minutes, but we're interested in 
		// minutes past the hour: to get that, we need to 
		// divide by 60 again and keep the remainder
		$minutes = intval(($sec / 60) % 60); 

		// then add to $hms (with a leading 0 if needed)
		$hms .= str_pad($minutes, 2, "0", STR_PAD_LEFT). ':';

		// seconds are simple - just divide the total
		// seconds by 60 and keep the remainder
		$seconds = intval($sec % 60); 

		// add to $hms, again with a leading 0 if needed
		$hms .= str_pad($seconds, 2, "0", STR_PAD_LEFT);

		// done!
		return $hms;
	}
}

?>