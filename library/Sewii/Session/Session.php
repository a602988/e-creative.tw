<?php

/**
 * Session 類別
 * 
 * @version 2.3.0 2013/05/08 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Soft. (sewii.com.tw)
 */

namespace Sewii\Session;

use Sewii\System\Singleton;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\System\Server;
use Sewii\Filesystem\Path;
use Sewii\Filesystem\File;
use Sewii\Filesystem\Scanner;

class Session extends Singleton
{
    /**
     * 會話檔案字頭
     * 
     * @const string
     */
    const FILE_PREFIX = 'sess_';

    /**
     * 強制從 URL 指定 ID 的欄位名稱
     * 
     * @const string
     */
    const FILED_FORCE_ID = 'SEWIISESSID';
    
    /**
     * 禁用狀態
     * 
     * @const integer
     */
    const STATE_DISABLED = 0;

    /**
     * 未啟用狀態
     * 
     * @const integer
     */
    const STATE_NONE = 1;

    /**
     * 已啟用狀態
     * 
     * @const integer
     */
    const STATE_ACTIVE = 2;
    
    /**
     * 目前會話狀態
     * 
     * @var integer
     */
    protected static $state;

    /**
     * 容器實體
     * 
     * @var array
     */
    protected static $containers = array();

    /**
     * 設定選項
     * 
     * @var array
     */
    protected static $configs = array
    (
        'save_path'                 => null,
        'name'                      => null,
        'save_handler'              => null,
        'gc_probability'            => null,
        'gc_divisor'                => null,
        'gc_maxlifetime'            => null,
        'serialize_handler'         => null,
        'cookie_lifetime'           => null,
        'cookie_path'               => null,
        'cookie_domain'             => null,
        'cookie_secure'             => null,
        'cookie_httponly'           => null,
        'use_cookies'               => null,
        'use_only_cookies'          => null,
        'referer_check'             => null,
        'entropy_file'              => null,
        'entropy_length'            => null,
        'cache_limiter'             => null,
        'cache_expire'              => null,
        'use_trans_sid'             => null,
        'bug_compat_42'             => null,
        'bug_compat_warn'           => null,
        'hash_function'             => null,
        'hash_bits_per_character'   => null
    );
    
    /**
     * 容器工廠
     *
     * @param string $namespace
     * @return Container
     */
    public static function container($namespace = null)
    {
        $namespace = $namespace ?: Container::NAMESPACE_DEFAULT;
        if (isset(self::$containers[$namespace])) {
            $container = self::$containers[$namespace];
            $container->getPrototype()->start();
            return $container;
        }
        return self::$containers[$namespace] = new Container($namespace);
    }
    
   /**
    * 傳回實體
    * 
    * @return Session
    */
    public static function getInstance()
    {
        if ($instance = parent::getInstance()) 
        {
            //Set Options
            if (!self::$configs) {
                foreach (self::$configs as $name => $value) {
                    if (isset(self::$configs[$name])) {
                        self::setOption($name, $value);
                    }
                }
            }

            //Force Session ID
            if (!empty($_GET[self::FILED_FORCE_ID])) {
                self::setId($_GET[self::FILED_FORCE_ID]);
            }

            self::start();
        }
        return $instance;
    }

    /**
     * 啟動會話
     * 
     * @return boolean
     */
    public static function start()
    {
        if (self::getState() != self::STATE_ACTIVE) {
            if ($start = session_start()) {
                self::setState(self::STATE_ACTIVE);
                return $start;
            }
        }
        return false;
    }

    /**
     * 銷毀會話
     * 
     * @return boolean
     */
    public static function destroy()
    {
        if (self::$containers) {
            foreach (self::$containers as $namespace => $container) {
                unset(self::$containers[$namespace]);
            }
        }
        
        //避免在會話銷毀前檔案流已經被關閉，此處重新啟動會話
        //以確保不會得到錯誤，而且將確保可以被完全銷毀。
        if (self::getState() != self::STATE_ACTIVE) {
            self::start();
        }

        $destroy = session_destroy();
        self::setState(self::STATE_NONE);
        return $destroy;
    }

    /**
     * 關閉檔案流
     * 
     * 若檔案流被關閉後 $_SESSION 變數仍可以供讀寫使用，
     * 但無法再對實體會話檔案進行寫入操作。
     * 
     * @return boolean
     */
    public static function writeClose()
    {
        session_write_close();
        self::setState(self::STATE_NONE);
        return false;
    }

    /**
     * 傳回 ID
     *
     * @return string 
     */
    public static function getId()
    {
        return session_id();
    }

    /**
     * 設定 ID
     *
     * @param string $id
     * @return void
     */
    public static function setId($id)
    {
        if (self::getState() == self::STATE_ACTIVE) {
            throw new Exception\RuntimeException('會話 ID 必須在會話啟動前指定');
        }
        session_id($id);
    }

    /**
     * 傳回 name
     *
     * @return string 
     */
    public static function getName()
    {
        return session_name();
    }

    /**
     * 設定 name
     *
     * @param string $name
     * @return void
     */
    public static function setName($name)
    {
        if (self::getState() == self::STATE_ACTIVE) {
            throw new Exception\RuntimeException('會話名稱必須在會話啟動前指定');
        }
        session_name($name);
    }

    /**
     * 傳回選項
     *
     * @param string $option
     * @return mixed
     */
    protected static function getOption($name)
    {
        $prefix = 'session.';
        if (!Regex::isMatch('/^' . Regex::quote($prefix) . '/', $name)) {
            $name = "{$prefix}{$name}";
        }
        return ini_get($name);
    }

    /**
     * 設定選項
     *
     * @param string $option
     * @param string $value
     * @return boolean
     */
    protected static function setOption($name, $value)
    {
        if (self::getState() == self::STATE_ACTIVE) {
            throw new Exception\RuntimeException('設定選項必須在會話啟動前指定');
        }

        $prefix = 'session.';
        if (!Regex::isMatch('/^' . Regex::quote($prefix) . '/', $name)) {
            $name = "{$prefix}{$name}";
        }
        return (ini_set($name, $value) !== false);
    }

    /**
     * 傳回狀態
     * 
     * @return integer
     */
    public static function getState()
    {
        if (function_exists('session_status')) {
            return session_status();
        }
        return self::$state;
    }
    
    /**
     * 設定狀態
     * 
     * @param integer $state
     * @return void
     */
    protected static function setState($state)
    {
        self::$state = $state;
    }
    
    /**
     * 傳回內容
     *
     * @param string $id
     * @return array
     */
    public static function getContent($id = null)
    {
        if ($path = self::getPath($id)) {
            if (File::isReadable($path)) {
                $content = File::read($path);
                $decoded = self::decode($content);
                return $decoded;
            }
        }
        return null;
    }

    /**
     * 傳回存放目錄
     *
     * @return string
     */
    public static function getDirectory()
    {
        $path = Server::getTemporaryPath();
        if ($sessionPath = session_save_path()) {
            $path = Arrays::getLast(explode(';', $sessionPath));
        }
        $path = Path::fix($path);
        return $path;
    }

    /**
     * 傳回檔案路徑
     *
     * @param string $id
     * @return string|null
     */
    public static function getPath($id = null)
    {
        if ($id = ($id ?: self::getId())) {
            $directory = self::getDirectory();
            $filename = sprintf('%s%s', self::FILE_PREFIX, $id);
            if (File::isFile($path = "$directory/$filename")) {
                return $path;
            }

            //Scaning
            $scanner = self::scan();
            if ($sessions = $scanner->toArray()) {
                if (isset($sessions[$filename])) {
                    $file = $sessions[$filename];
                    return $file->getPathname();
                }
            }
        }
        return null;
    }
    
    /**
     * 掃描會話清單
     *
     * @param integer $modes
     * @param integer $flags
     * @return Scanner
     */
    public static function scan($modes = Scanner::FILE_ONLY, $flags = Scanner::KEY_AS_FILENAME)
    {
        $directory = self::getDirectory();
        $scanner = new Scanner($directory, $modes, $flags);
        $scanner->with('/^' . self::FILE_PREFIX . '\w+/');
        return $scanner;
    }

    /**
     * 解碼內容
     *
     * @param string $data
     * @return array
     */
    public static function decode($data)
    {
        $vars = Regex::split('/([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\|/', $data, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        for ($result = array(), $i = 0; isset($vars[$i]); $i++) {
            $result[$vars[$i++]] = unserialize($vars[$i]);
        }
        return $result;
    }

    /**
     * 編碼內容
     *
     * @param array $data
     * @return string
     */
    public static function encode(array $data)
    {
        if (is_array($data)) {
            $encoded = array();
            foreach ($data as $key => $value) {
                $encoded[] = $key . '|' . serialize($value);
            }
            return implode('', $encoded);
        }
        return null;
    }
}

?>