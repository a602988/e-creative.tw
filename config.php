<?php

/**
 * 全域設定文件
 * 
 * @version 1.0.1 2013/06/16 09:42
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

return array
(
    /**
     * 資料庫設定檔
     * 
     * @var array
     */
    'database' => array
    (
        'default' => array
        (
            /**
             * 資料庫類型
             * 
             * @var string
             */
            'driver' => 'mysqli',
            'driver' => 'pdo_mysql',
            
            /**
             * 主機位址
             * 
             * @var string
             */
            'hostname' => '127.0.0.1',
            
            /**
             * 連接埠號
             * 
             * @var integer
             */
            'port' => '3306',

            /**
             * 使用者帳號
             * 
             * @var string 
             */
            'username' => 'root',

            /**
             * 使用者密碼
             * 
             * @var string 
             */
            'password' => 'root888',

            /**
             * 資料庫名稱
             * 
             * @var string 
             */
            'database' => 'spanel_v4',

            /**
             * 資料字元集
             * 
             * @var string 
             */
            'charset' => 'UTF8'
        )
    ),
    
    /**
     * 郵件設定檔
     * 
     * @var array
     */
    'mail' => array
    (
        'default' => array
        (
            /**
             * 傳輸協定
             * smtp|sendmail
             * 
             * @var string|null 
             */
            'transport' => null,

            /**
             * SMTP 伺服器
             * 
             * @var string 
             */
            'smtpHost' => null,

            /**
             * SMTP 連接埠
             * 
             * @var integer|null
             */
            'smtpPort' => null,

            /**
             * SMTP 連線類型
             * ssl|tls
             * 
             * @var string|null
             */
            'smtpSsl' => null,

            /**
             * SMTP 驗證類型
             * plain|login|crammd5
             * 
             * @var string|null 
             */
            'smtpAuth' => null,

            /**
             * SMTP 驗證帳號
             * 
             * @var string|null
             */
            'smtpAuthUsername' => null,

            /**
             * SMTP 驗證密碼
             * 
             * @var string|null
             */
            'smtpAuthPassword' => null,

            /**
             * Sendmail 參數設定
             * 
             * @var string|null
             */
            'sendmailParameters' => null,
        )
    ),
    
    /**
     * 路徑設定檔
     * 
     * @var array
     */
    'path' => array
    (
        'library'    => 'library',
        'module'     => 'modules',
        'component'  => 'modules/components',
        'controller' => 'modules/controllers',
        'widget'     => 'modules/widgets',
        'model'      => 'modules/models',
        'site'       => 'sites',
        'common'     => 'sites/common',
        'resource'   => 'resources',
        'setup'      => 'resources/setups',
        'temporary'  => 'resources/temporaries',
        'cache'      => 'resources/temporaries/cache',
        'thumb'      => 'resources/temporaries/thumb',
        'log'        => 'resources/logs',
        'debug'      => 'resources/logs/debugs',
        'upload'     => 'resources/uploads',
        'document'   => 'resources/documents',
        'template'   => 'resources/templates',
        'root'       => null,
        'base'       => null
    ),

    /**
     * 預設時區設定
     * 
     * @var string
     */
    'timeZone' => 'Asia/Taipei',
    
    /**
     * 啟用壓縮輸出
     * 
     * @var boolean
     */
    'gzipOutput' => false,
    
    /**
     * 強制加密連線
     * 
     * @var boolean
     */
    'forceSsl' => false,
    
    /**
     * 強制靜態化網址
     * 
     * @var boolean
     */
    'forceStaticUrl' => false,
    
    /**
     * 啟用除錯模式
     * 
     * @var boolean
     */
    'debugMode' => true,
);

?>