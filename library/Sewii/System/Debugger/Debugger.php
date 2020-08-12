<?php

/**
 * 除錯器
 * 
 * @version 3.1.9 2014/04/10 15:34
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\System\Debugger;

class Debugger
{
    /**
     * 啟用狀態
     * 
     * @var boolean
     */
    protected $enabled = true;

    /**
     * 回報等級
     * 6135 => E_ALL ^ E_NOTICE
     * -1   => E_ALL
     * 
     * @var integer
     */
    protected $reportLevel = -1;

    /**
     * 是否顯示預設的錯誤訊息
     * 
     * @var boolean
     */
    protected $isDisplayDefaultError = false;
    
    /**
     * 是否永遠使用例外捕捉處理錯誤
     * 
     * 不包含致命錯誤 E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT
     * 因為致命錯誤只能在程式結束時補捉，無法再轉例外處理。
     * 
     * @var boolean
     */
    protected $isAlwaysThrowException = false;

    /**
     * 發生錯誤時永遠結束執行
     * 
     * @var boolean
     */
    protected $isAlwaysExit = false;

    /**
     * 是否儲存錯誤日誌
     * 
     * @var boolean
     */
    protected $isLogError = true;

    /**
     * 是否自動寄送電子郵件通知
     * 
     * @var boolean
     */
    protected $isNotify = false;

    /**
     * 除錯器處理錯誤的上限次數
     * 
     * @var integer
     */
    protected $debugMaxTimes = 10;
    
    /**
     * 錯誤回報信箱地址
     * 
     * @const string
     */
    const REPORT_EMAIL = 'report@sewii.com.tw';
    
    /**
     * 錯誤日誌檔案名稱
     * 
     * @const string
     */
    const FILENAME_ERROR = 'error.log';
    
    /**
     * 存取日誌檔案名稱
     * 
     * @const string
     */
    const FILENAME_ACCESS = 'access.log';
    
    /**
     * 錯誤輸出頁面樣版
     * 
     * @const string
     */
    const PATH_TEMPLATE = 'template/debugger.html';
    
    /**
     * 儲存目錄名稱
     * 
     * @var string
     */
    protected $savePath = 'logs';
    
    /**
     * 錯誤編號的累加
     * @var integer
     */
    protected $debugNumber = 1;
    
    /**
     * 儲存最後的錯誤詳細日誌 URL 路徑
     * 
     * @var string
     */
    protected $lastLogDetailUrl;
    
    /**
     * 實體參考
     * 
     * @var Debugger
     */
    protected static $instance;

    /**
     * 建構子
     * 
     */
    private function __construct() {}
    
    /**
     * 初始化
     * 
     * @return $this
     */
    public function init($savePath = null, $enabled = null) 
    {
        if (!is_null($enabled))  $this->setEnabled($enabled);
        if (!is_null($savePath)) $this->setLogPath($savePath);

        error_reporting($this->reportLevel);
        ini_set('display_errors', $this->isDisplayDefaultError);
        set_error_handler(array($this, 'errorHandler'));
        set_exception_handler(array($this, 'exceptionHandler'));
        register_shutdown_function(array($this, 'fatalHandler'));
        return $this;
    }

    /**
     * 設定啟用狀態
     *
     * @param boolean $value
     * @return void
     */
    public function setEnabled($value)
    {
        $this->enabled = (bool) $value;
    }
    
    /**
     * 設定日誌儲存路徑
     *
     * @param string $savePath
     * @return void
     */
    public function setLogPath($savePath)
    {
        $hostname = preg_replace('/^www\./i', '', $_SERVER['SERVER_NAME']);
        $this->savePath = (!$savePath) ? __DIR__ . '/' . $this->savePath : $savePath;
        $this->savePath = $this->savePath . '/' . $hostname;
        $this->savePath = $this->fixPath($this->savePath);
        if (!is_dir($this->savePath)) {
            if (@mkdir($this->savePath, 0777, true)) {
                @chmod($this->savePath, 0777);
            }
        }
    }

    /**
     * 傳回日誌儲存路徑
     *
     * @return string
     */
    public function getLogPath()
    {
        return $this->savePath;
    }

   /**
    * 傳回實體
    * 
    * @return Debugger
    */
    public static function getInstance() 
    {
        if (is_null(self::$instance)) {
            return self::$instance = new self;
        }
        return self::$instance;
    }

    /**
     * 處理一般錯誤的方法
     *
     * 這個方法可以捕捉一般類型的錯誤，但不包含以下致命錯誤：
     * E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT
     *
     * @todo xxx
     * @link http://www.php.net/manual/en/function.set-error-handler.php
     * @link http://php.net/manual/en/errorfunc.constants.php#errorfunc.constants.errorlevels.e-error
     * @param integer $no
     * @param string $message
     * @param string $file
     * @param integer $line
     * @param array $context
     * @return boolean 如果這個方法返回 false 將會繼續正常的錯誤處理，返回 true 將略過錯誤。
     * @throws ErrorException
     */
    public function errorHandler($no, $message, $file, $line, $context)
    {
        //如果使用 @ 抑制即略過處理錯誤
        if (error_reporting() === 0) return true;
        
        //略過超過除錯上限的錯誤
        if ($this->debugNumber && $this->debugNumber >= $this->debugMaxTimes) return true;
        
        //直接拋出例外，讓 try/catch 可以補捉處理
        //如果拋出例外將無法繼續執行，例如 notice、warning 都會直接中斷
        if ($this->isAlwaysThrowException) 
        {
            //TODO 目前已知發生連續誤錯時重新拋出例外會無法補捉錯誤，
            //TODO 例如 $var->func(); 分別連錯2次，$var (未定義) 和 func() (未定義) 連續2次
            //TODO 例如 require 錯誤時，預設會發生 warning error (找不到檔案) 和 fatal erro (載入失敗) 連續2次
            throw new \ErrorException($message, 0, $no, $file, $line);
        }
        
        $this->fire(array (
            'no'      => $no,
            'message' => $message,
            'file'    => $file,
            'line'    => $line,
            'context' => $context,
            'trace'   => $this->getTraceAsString(),
        ));

        //直接結束
        if ($this->isAlwaysExit) exit;

        return false;
    }

    /**
     * 處理致命錯誤的方法
     *
     * 這個方法利用 register_shutdown_function() 可以補足到所有類型的錯誤
     * 為了彌補 set_error_handler() 的不足，將利用此方法補捉以下致命類型錯誤:
     * E_ERROR, E_PARSE, E_CORE_ERROR, E_CORE_WARNING, E_COMPILE_ERROR, E_COMPILE_WARNING, E_STRICT
     * (這個方法只能抓取最後一個錯誤)
     *
     * @return void
     */
    public function fatalHandler()
    {
        //如果使用 @ 抑制即略過處理錯誤
        if (error_reporting() === 0) return;

        if ($error = error_get_last()) {
            $error['no'] = $error['type'];
            switch ($error['no']) {
                case E_PARSE:
                case E_ERROR:
                case E_CORE_ERROR:
                case E_CORE_WARNING:
                case E_COMPILE_ERROR:
                case E_COMPILE_WARNING:
                case E_STRICT:
                    if ($error['no'] === E_ERROR &&
                        preg_match('/^Allowed memory size of \d+.+/', $error['message'])) {
                        $this->onMemoryError($error);
                    }
                    $this->fire($error);
                    break;
            }
        }
    }

    /**
     * 處理例外錯誤的方法
     * 
     * @link http://www.php.net/manual/en/function.set-exception-handler.php
     * @link http://www.php.net/manual/en/language.exceptions.php
     * @param object $exception
     * @return void
     */
    public function exceptionHandler($exception)
    {
        $this->fire(array(
            'no'         => get_class($exception),
            'message'    => $exception->getMessage(),
            'file'       => $exception->getFile(),
            'line'       => $exception->getLine(),
            'trace'      => $this->getTraceAsString($exception->getTrace()),
            'exception'  => $exception
        ));
    }

    /**
     * 觸發錯誤處理的方法
     *
     * @param array $error
     * @param boolean $doNotResponse
     * @return void
     */
    protected function fire(array $error, $doNotResponse = false)
    {
        $error = array_merge($error, array(
            'type'           => $this->getErrorType($error),
            'requestUri'     => $_SERVER['REQUEST_URI'],
            'requestMethod'  => $_SERVER['REQUEST_METHOD'],
            'serverProtocol' => $_SERVER['SERVER_PROTOCOL'],
            'remoteAddress'  => $_SERVER["REMOTE_ADDR"],
            'userAgent'      => $_SERVER["HTTP_USER_AGENT"],
            'serverSoftware' => $_SERVER['SERVER_SOFTWARE'],
            'serverName'     => $_SERVER['SERVER_NAME'],
            'serverPort'     => $_SERVER['SERVER_PORT'],
            'dateTime'       => date('Y-m-d H:i:s'),
            'latestYear'     => date('Y'),
            'reportEmail'    => self::REPORT_EMAIL,
            'debugNumber'    => $this->debugNumber++
        ));

        if (isset($error['file'])) {
            $error['file'] = self::removeRoot($error['file']);
        }

        $this->onError($error);

        //Is Response?
        if (!$doNotResponse) {
            $this->display($error);
        }
    }

    /**
     * 傳回錯誤類型
     *
     * @param array $error
     * @return string
     */
    protected function getErrorType(array $error)
    {
        $types = array(
           E_ERROR                    => 'Error',
           E_WARNING                  => 'Warning',
           E_PARSE                    => 'Parsing error',
           E_NOTICE                   => 'Notice',
           E_CORE_ERROR               => 'Core error',
           E_CORE_WARNING             => 'Core warning',
           E_COMPILE_ERROR            => 'Compile error',
           E_COMPILE_WARNING          => 'Compile warning',
           E_USER_ERROR               => 'User error',
           E_USER_WARNING             => 'User warning',
           E_USER_NOTICE              => 'User notice',
           E_STRICT                   => 'Strict notice',
           E_RECOVERABLE_ERROR        => 'Recoverable error',
           E_DEPRECATED               => 'Deprecated',
           E_USER_DEPRECATED          => 'User deprecated'
        );

        //Exception
        if (isset($error['exception'])) {
            $exception = $error['exception'];
            $type = sprintf('%s (%s)',  $error['no'], $exception->getCode());
            if (method_exists($exception, 'getSeverity')) {
                $severity = $exception->getSeverity();
                if (isset($types[$severity])) {
                    $beforeType = $types[$severity];
                    $type .= sprintf(' [%s]', $beforeType);
                }
            }
            return $type;
        }

        $no = isset($error['no']) ? $error['no'] : null;
        return isset($types[$no]) ? $types[$no] : 'Unknown';
    }
    
    /**
     * 記憶體不足事件
     * 
     * @param array $error
     * @return void
     */
    protected function onMemoryError(array $error)
    {
        if ($this->enabled) {

            // 直接輸出錯誤，否則 PHP 若無記憶體再繼續處理會出輸出 Error 500!!
            exit("Fatal error: {$error['message']} in {$error['file']} on line {$error['line']}");
        }
    }

    /**
     * 錯誤事件
     *
     * @param array $error
     */
    protected function onError(array $error) 
    {
        $logSummary = $this->savePath . '/' . self::FILENAME_ERROR;
        $logDetail = $this->savePath . '/' . date('YmdHis') . '.html';
        $this->lastLogDetailUrl = str_replace($this->fixPath(__DIR__) . '/', $this->getBaseUrl(), $logDetail);
        $detail = $this->display($error, false);

        //傳送電子郵件
        if ($this->isNotify) self::sendReport($detail);

        //儲存錯誤日誌
        if ($this->isLogError) {
            $error['message'] = str_replace("\r", '\r', str_replace("\n", '\n', $error['message']));
            $summary = sprintf(
                'PHP %s: %s in %s on line %s', 
                $error['type'], 
                $error['message'], 
                $error['file'], 
                $error['line']
            );
            error_log($summary, 0);
            error_log(sprintf('[%s] %s [%s]%s', $error['dateTime'], $summary, basename($logDetail), PHP_EOL), 3, $logSummary);
            error_log($detail, 3,  $logDetail);
        }
    }

    /**
     * 輸出方法
     *
     * @param array $error
     * @param boolean $display
     * @return mixed
     */
    protected function display(array $error, $display = true) 
    {
        if (!$this->enabled && $display)
            exit(header('HTTP/1.1 500 Internal Server Error'));

        if (($template = file_get_contents(__DIR__ . '/' . self::PATH_TEMPLATE)) !== false) {
            $error['baseUrl'] = $this->getBaseUrl() . dirname(self::PATH_TEMPLATE) . '/';
            $error['reportUrl'] = $this->getBaseUrl() . basename(__FILE__) . '?report=' . urlencode($this->lastLogDetailUrl);
            
            if (isset($error['message']))
                $error['message'] = htmlspecialchars($error['message']);

            if (isset($error['trace']))
                $error['trace'] = htmlspecialchars($error['trace']);

            $assigns = array();
            foreach ($error as $key => $val) {
                // In PHP 5.4+ will throw an notice say Array to string conversion
                $assigns['{[' . $key . ']}'] = @strval($val);
            }
            $template = strtr($template, $assigns);

            //Remove block of stack trace, if not set
            if (!isset($error['trace'])) {
                $template = preg_replace('/<fieldset\s+class="trace">(.*)<\/fieldset>/isU', '', $template);
            }

            if ($display) print $template;
            else return $template;
        }
    }

    /**
     * 傳回 Base URL
     * 
     * @return string
     */
    protected function getBaseUrl() 
    {
        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
        $host = $_SERVER['SERVER_NAME'];
        $port = ($_SERVER['SERVER_PORT'] != 80) ? ':' . $_SERVER['SERVER_PORT'] : '';
        $path = $this->fixPath(dirname($_SERVER['PHP_SELF']));
        $folder = str_ireplace(dirname($_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF']), '', $this->fixPath(__DIR__));
        $baseUrl = $scheme . $host . $port . $path . $folder . '/';
        $baseUrl = preg_replace('/([^:])\/\//', '$1/', $baseUrl);
        return $baseUrl;    
    }

    /**
     * 修正路徑
     *
     * @param string $path
     * @return string
     *
     */
    protected function fixPath($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * 刪除根路徑
     *
     * @param string $path
     * @return string
     *
     */
    protected static function removeRoot($content)
    {
        $root = dirname($_SERVER['DOCUMENT_ROOT'] . $_SERVER['PHP_SELF']) . '/';
        $root = preg_replace('#[/\\\]#', '[/\\\\\]', $root);
        $root = preg_replace("#$root#", '', $content);
        $root = str_replace('\\', '/', $root);
        return $root;
    }
    
    /**
     * 傳回字串格式堆疊追蹤
     *
     * @param array $trace
     * @return string
     */
    public static function getTraceAsString($trace = null) 
    {
        if (is_null($trace)) 
            $trace = debug_backtrace();
            
        $no = 0;
        $lines = array();
        foreach ($trace as $index => $info) {
            $place   = isset($info['file']) ? sprintf('%s(%s)', self::removeRoot($info['file']), $info['line']) : '[internal function]';
            $method  = isset($info['class']) ? $info['class'] : '';
            $method .= isset($info['type']) ? $info['type'] : '';
            $method .= isset($info['function']) ? $info['function'] : '';
            if (preg_match('/^' . preg_quote(__CLASS__, '/') . '/', $method)) continue;
            foreach ($info['args'] as &$arg) {
                if (is_null($arg)) $arg = 'NULL';
                else if (is_bool($arg)) $arg = $arg ? 'true' :  'false';
                else if (is_object($arg)) $arg = sprintf('Object(%s)', get_class($arg));
                else if (is_string($arg)) {
                    $arg = trim($arg);
                    if (strlen($arg) > ($maxLength = 15)) {
                        $arg = substr($arg, 0, $maxLength) . '...';
                    }
                    $arg = "'$arg'";
                }
                
                // In PHP 5.4+ will throw an notice say Array to string conversion
                $arg = @strval($arg);
            }
            $args = implode(', ', $info['args']);
            $line = sprintf('#%s %s: %s(%s)', $no++, $place, $method, $args);
            array_push($lines, $line);
        }
        array_push($lines, sprintf('#%s {main}', count($lines)));
        return implode(PHP_EOL, $lines);
     }

    /**
     * 發送錯誤回報靜態方法
     *
     * @param array $data - 錯誤回報內容
     * @param string $contentType - 信件格式, 可設定的值 text/plain | text/html
     * @param string $charset - 信件編碼
     * @return boolean
     */
    public static function sendReport($data, $contentType = 'text/html', $charset = 'UTF-8') 
    {
        return error_log($data, 1, self::REPORT_EMAIL, "Content-Type: " . $contentType . "; charset=" . $charset); 
    }

    /**
     * 使用者回報錯誤偵聽器
     */
    public static function reportListener()
    {
        if (!empty($_GET['report']) && basename($_SERVER["PHP_SELF"]) == basename(__FILE__)) {
            header('Content-Type:text/html; charset=utf-8');
            if ($logDetail = @file_get_contents($_GET['report'])) {
                $reportData = $logDetail;
                if (self::sendReport($reportData)) {
                    exit('<h1>Seccuss !<br /></h1>Your report has been sent to sysadmin email address, Thank you.');  
                }
            } 
            exit('<h1>Failure !</h1>Can not send report to sysadmin email address, please try again later.');
        }
    }
    
    /**
     * 傾印變數
     * 
     * @param  mixed  $var
     * @param  string $label
     * @param  bool   $display
     * @return void
     */
    public static function dump($var, $label = null, $display = true)
    {
        ob_start();
        var_dump($var);
        $dump = ob_get_clean();

        $label = ($label === null) ? '' : rtrim($label) . ' ';
        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $dump);
        $output = preg_replace('/([^\r])\n/', "$1\r\n", $output);
        $output = '<pre>'
                . $label
                . $output
                . '</pre>';

        if ($display) {
            echo $output;
        }
        return $output;
    }

    /**
     * 寫入日誌
     * 
     * @param mixed $content
     * @return void
     */
    public static function log($content = null)
    {
        $path = self::getInstance()->getLogPath();
        $file = $path . '/' . self::FILENAME_ACCESS;
        $content = sprintf(
            '[%s] %s%s', 
            date('Y-m-d H:i:s'), 
            trim(strval($content)), 
            PHP_EOL
        );
        error_log($content, 3, $file);
    }

    /**
     * 寫入日誌
     * 
     * @param mixed $content
     * @return void
     * @see dump() log()
     */
    public static function dir($content)
    {
        $content = self::dump($content, null, false);
        $content = preg_replace('/<pre>(.*)<\/pre>/is', '$1', $content);
        self::log($content);
    }
    
    /**
     * 觸發錯誤處理
     * 
     * 此方法只在背景處理錯誤不會給予回應
     *
     * @param integer|array|Exception $no
     * @param string $message
     * @param string $file
     * @param integer $line
     * @return void
     */
    public static function trigger($no, $message = null, $file = null, $line = null)
    {
        if (is_array($no)) extract($no);

        //Error
        $error = array(
            'no'       => $no,
            'message'  => $message,
            'file'     => $file,
            'line'     => $line,
        );

        //Exception
        if ($no instanceof \Exception) {
            $exception = $no;
            $error = array(
                'no'         => get_class($exception),
                'message'    => $exception->getMessage(),
                'file'       => $exception->getFile(),
                'line'       => $exception->getLine(),
                'trace'      => self::getTraceAsString($exception->getTrace()),
                'exception'  => $exception
            );
        }

        self::getInstance()->fire($error, true);
    }
}

Debugger::reportListener();

?>