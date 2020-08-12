<?php
    
use Sewii\Data\Database;
use Sewii\Http;

set_time_limit(0);

exit;
$database = Database\Factory::init();
switch($_GET['command']) 
{
    //網站分析報表同步化工作
    case 'analytics':
        $AnalyticsLogs = new MOD_AnalyticsLogs;
        $result = $AnalyticsLogs->syncFromRemote();
        Http\Response::write($result ? 200 : 500);
        break;

    //清除檔案
    case 'clearFiles':
        function doing($directory)
        {
            //每 N 天執行一次
            $daysClear = 1;

            //保留天數
            $daysPreserve = 14;

            //檢查上次清除執行時間
            $lastClearCacheFile = $directory . '/__lastcleaned__';
            if (file_exists($lastClearCacheFile)) {
                $lastClearCacheTime = strtotime(file_get_contents($lastClearCacheFile));
                if ((time() - $lastClearCacheTime) < (24 * 60 * 60 * $daysClear)) {
                    return;
                }
            }

            //寫入本次執行時間
            file_put_contents($lastClearCacheFile, date('Y-m-d H:i:s'));
            
            //時間比較方法
            if (!function_exists('filemtime_compare')) 
            {
                function filemtime_compare ($a, $b) {
                    $break = explode ('/', $_SERVER['SCRIPT_FILENAME']);
                    $filename = $break[count ($break) - 1];
                    $filepath = str_replace ($filename, '', $_SERVER['SCRIPT_FILENAME']);
                    $file_a = realpath ($filepath . $a);
                    $file_b = realpath ($filepath . $b);
                    return filemtime ($file_a) - filemtime ($file_b);
                }
            }
            
            $files = glob ($directory . '/*', GLOB_BRACE);
            usort ($files, 'filemtime_compare');

            $days = (time () - (24 * 60 * 60 * $daysPreserve));
            foreach ($files as $file) {
                if (@filemtime ($file) > $days) break;
                if (file_exists ($file)) @unlink ($file);
            }
        }
        
        doing(Configure::$path['cache']);
        doing(Configure::$path['temporary']);
        break;

    //電子報回流
    case 'epaperFeedback':
        $EpaperFeedback = new MOD_EpaperFeedback;
        $EpaperFeedback->onFeedback();
        break;
}

?>