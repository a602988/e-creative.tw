<?php

/**
 * 啟動程式
 * 
 * @version 1.0.5 2016/04/06 17:23
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Spanel\Module\Component;

use Sewii\Exception;
use Sewii\System\Autoloader;
use Sewii\System\Debugger\Debugger;
use Sewii\System\Registry;
use Sewii\System\Config;
use Sewii\Text\Regex;
use Sewii\Filesystem\File;
use Sewii\Data\Database\Database;
use Sewii\Net\Mail;
use Sewii\Cache;
use Spanel\Module\Component\Router\Router;

class Bootstrap
{
    protected $config = array();

    /**
     * 開始執行
     * 
     * @param array $config
     * @return Bootstrap
     */
    public static function run(array $config)
    {
        static $instance;
        if (is_null($instance)) {
            $instance = new self;
            $instance->config = $config;
            $instance->initialize();
        }
        return $instance;
    }

    /**
     * 初始化
     * 
     * @return void
     */
    protected function initialize()
    {
        $this->required();
        $this->setup();
        $this->autoLoad();
        $this->build();
        $this->test();
        $this->boot();
    }
    
    /**
     * 載入核心
     * 
     * @return void
     */
    protected function required()
    {
        $config = $this->config;
        require $config['path']['library'] . '/Sewii/System/Debugger/Debugger.php';
        require $config['path']['library'] . '/Sewii/System/Autoloader.php';
    }

    /**
     * 環境設置
     * 
     * @return void
     */
    protected function setup()
    {
        $config = &$this->config;

        // Set the path of root
        if (is_null($config['path']['root'])) {
            $config['path']['root'] = dirname($_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF']);
            $config['path']['root'] = preg_replace('/^\w\:/', '', str_replace('\\', '/', $config['path']['root']));
        }

        // Set the path of base
        if (is_null($config['path']['base'])) 
            $config['path']['base'] = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));

        // Set the time zone
        if (isset($config['timeZone'])) 
            date_default_timezone_set($config['timeZone']);
            
        // Set the compress output
        if (isset($config['gzipOutput'])) 
            ini_set('zlib.output_compression', $config['gzipOutput']);
            
        // Set the Debugger
        $logPath = $config['path']['root'] . '/' . $config['path']['debug'];
        $debugger = Debugger::getInstance();
        $debugger->setEnabled($config['debugMode']);
        $debugger->setLogPath($logPath);
        $debugger->init();
    }
    
    /**
     * 自動載入器
     * 
     * @return void
     */
    protected function autoLoad()
    {
        $config = $this->config;

        // Standard
        $autoloader = new Autoloader;
        $autoloader->setIncludePath($config['path']['library']);
        foreach ($splLibraries = array(
            'Zend' => $config['path']['library'] . '/Zend',
            'Sewii' => $config['path']['library'] . '/Sewii',
            'PHPExcel' => $config['path']['library'] . '/PHPExcel'
        ) as $vendor => $splLibrary) {
            $autoloader->addSplLibrary($vendor, $splLibrary);
        }

        // Spanel
        $autoloader->addSplLibrary('Spanel', function ($class) use ($config, $autoloader) {
            $path = preg_replace('/^\w+[\/\\\]/', '', $class['path']);

            $path = preg_replace_callback('#^(Module)/(\w+)#', function($matches) {
                return strtolower("{$matches[1]}s/{$matches[2]}s");
            }, $path);

            $path = preg_replace_callback('#^(Module)#', function($matches) {
                return strtolower("{$matches[1]}s");
            }, $path);

            $autoloader->load($path);
        });

        // Common
        $autoloader->register($self = function($class) use (&$self, $splLibraries, $config, $autoloader) 
        {
            $directories = array($config['path']['library']);
            if (is_array($class)) list($directories, $class) = each($class);
            $class = str_replace('\\', '/', $class);
            foreach ((array) $directories as $directory) {
                $path = $directory . '/' . $class;
                if ($autoloader->load($path)) return true;
                else if ($scandir = array_diff(scandir($directory), array('.', '..'))) 
                {
                    if (preg_match('/\//', $class)) return;

                    //echo print_r($path, true) . PHP_EOL;
                    if (($index = array_search($class, $scandir)) !== false) {
                        $first = $scandir[$index];
                        unset($scandir[$index]);
                        array_unshift($scandir, $first);
                    }

                    foreach ($scandir as $item) {
                        $child = $directory . '/' . $item;
                        if (in_array($child, $splLibraries)) continue;
                        if (preg_match('/\./', $item) && !preg_match('/\.php(4|5)?|inc$/i', $item)) continue;
                        if (is_dir($child) && $self(array($child => $class))) return;
                    }
                }
            }
        });
    }

    /**
     * 環境建置
     * 
     * @return void
     */
    protected function build()
    {
        // Config
        $config = new Config($this->config);
        Registry::setConfig($config);
        
        // Cache
        if (isset($config->path->cache)) {
            Cache\Storage\Filesystem::setStorePath($config->path->cache);
        }
        
        // Mail
        if (isset($config->mail)) {
            Mail::$setups = $config->mail->toArray();
        }

        // Database
        if (isset($config->database)) {
            Database::configure($config->database->toArray());
        }
    }

    /**
     * 環境測試
     * 
     * @return void
     * @throws Sewii\Exception\RuntimeException
     */
    protected function test()
    {
        $config = Registry::getConfig();

        foreach (array(
            $config->path->log, 
            $config->path->document, 
            $config->path->upload, 
            $config->path->cache, 
            $config->path->temporary, 
            Debugger::getInstance()->getLogPath()
        ) as $path) {
            if (!File::isWritable(realpath($path))) {
                throw new Exception\RuntimeException(
                    sprintf('目錄 %s 無法使用或沒有寫入權限', $path)
                );
            }
        }
    }

    /**
     * 啟動程式
     * 
     * @return void
     */
    protected function boot()
    {
        Router::enter();
    }
}

?>