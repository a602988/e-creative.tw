<?php

/**
 * Glob 類別
 * 
 * @version 1.0.1 2016/05/24 00:00
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\Filesystem;

use GlobIterator;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\System\Server;

class Glob extends GlobIterator
{
    /**
     * 建構子
     * 
     * {@inheritDoc}
     */
    public function __construct($path, $flags = null)
    {
        if ($flags === null) {
            $flags = self::KEY_AS_PATHNAME 
                   | self::CURRENT_AS_FILEINFO 
                   | self::SKIP_DOTS
                   | self::UNIX_PATHS;
        }

        $path = self::fixPath($path);
        parent::__construct($path, $flags);
        parent::setInfoClass(FileInfo::CLASS_NAME);
    }
    
    /**
     * 修正路徑
     * 
     * @param string $path
     */
    protected static function fixPath($path)
    {
        //修正 Windows 系統下使用相對路徑的錯誤
        if (Server::isWindows()) {
            if (!$isAbsolute = Regex::isMatch('/^[\/\\\]|[a-z]:/i', $path)) {
                $currentDir = File::info('./')->getRealPath();
                if ($backTimes = substr_count($path, '../')) {
                    $currentDir .= '/' . str_repeat('../', $backTimes);
                    $currentDir = File::info($currentDir)->getRealPath();
                }
                $currentDir = Path::fix($currentDir);
                $path = Regex::replace('/^[\.\/\\\]*/', "$currentDir/", $path);
            }
        }

        $path  = Path::fix($path);
        $path  = Path::toLocal($path);
        return $path;
    }
}

?>