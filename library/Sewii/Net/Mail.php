<?php

/**
 * 郵件類別
 * 
 * @version 2.0.1 2014/06/24 05:49
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */

namespace Sewii\Net;

use Zend\Mime\Mime;
use Zend\Validator;
use Zend\Mail\Message;
use Zend\Mail\Transport;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Sewii\Type\Arrays;
use Sewii\Uri\Uri;

class Mail
{
    /**
     * 設定檔集合
     * 
     * @var array
     */
    public static $setups = array();
    
    /**
     * 執行階段設定
     * 
     * @var array
     */
    protected $_setup = array();
    
    /**
     * 變數集合設定
     * 
     * @var array
     */
    protected $_variables = array();   
    
    /**
     * 變數左標籤
     * 
     * @var string
     */
    protected $_tagLeft = '{';
    
    /**
     * 變數右標籤
     * 
     * @var string
     */
    protected $_tagRight = '}';
    
    /**
     * 郵件類別
     * 
     * @var Zend\Mail\Transport\TransportInterface 
     */
    protected $_transport;
    
    /**
     * 預設信件內容編碼
     * 
     * @const string
     */
    const DEFAULT_CHARSET = 'UTF-8';

    /**
     * 表示 Quoted-Printable 編碼常數
     * 
     * @const string
     */
    const ENCODING_QUOTEDPRINTABLE = Mime::ENCODING_QUOTEDPRINTABLE;

    /**
     * 表示 Base64 編碼常數
     * 
     * @const string
     */
    const ENCODING_BASE64 = Mime::ENCODING_BASE64;

    /**
     * 建構子
     * 
     * @param string|array $profile
     * @param array $setups
     * @return Sewii\Net\Mail
     * @throws Exception
     */
    public function __construct($profile = null, array $setups = null)
    {
        if (is_null($setups)) 
            $setups = self::$setups;
            
        //直接傳入匿名設定檔
        if (is_array($profile)) {
            ksort($profile);
            $key = md5(implode('', $profile));
            $setups = array($key => $profile);
            $profile = $key;
        }
        
        if ($setups) 
        {
            //未指定時使用第一個設定檔
            if (!$profile) $setup = Arrays::getFirst($setups);

            //沒有符合的郵件設定檔
            else if (!isset($setups[$profile])) 
                throw new \Exception('沒有符合的郵件設定檔 ' . $profile);

            //已存在的郵件設定檔
            else $setup = $setups[$profile];

            //設定傳輸協定
            $this->_setup = $setup;
            $this->setTransport($setup);
        }
    }
    
    /**
     * 初始化傳輸協定
     * 
     * @param string|array $name
     * @return Sewii\Net\Mail
     * @throws InvalidArgumentException|RuntimeException
     */
    public function setTransport($name) 
    {
        $transport = null;
        
        //直接傳入匿名設定檔
        if (is_array($name)) {
            $this->_setup = $name;
            $name = Arrays::value($this->_setup, 'transport');
        }

        //傳輸協定設定
        if ($setup = $this->_setup) {
            switch (strtolower($name)) 
            {
                case '':
                case 'smtp':
                    $config = array (
                        'host'              => Arrays::value($setup, 'smtpHost'), 
                        'port'              => Arrays::value($setup, 'smtpPort'), 
                        'connection_class'  => Arrays::value($setup, 'smtpAuth'), 
                        'connection_config' => array(
                            'username' => Arrays::value($setup, 'smtpAuthUsername'), 
                            'password' => Arrays::value($setup, 'smtpAuthPassword'), 
                            'ssl'      => Arrays::value($setup, 'smtpSsl')
                        ), 
                    );
                    if (empty($config['host'])) $config['host'] = ini_get('SMTP');
                    if (empty($config['port'])) $config['port'] = ini_get('smtp_port');
                    foreach ($config as $k => $v) if (is_null($v)) unset($config[$k]);
                    $options = new Transport\SmtpOptions($config);
                    $transport = new Transport\Smtp($options);
                    break;

                case 'sendmail':
                    $params = Arrays::value($setup, 'sendmailParameters');
                    $transport = new Transport\Sendmail($params);
                    break;

                default:
                    throw new \InvalidArgumentException('未知的傳輸協定 ' . $name);
                    break;
            }
        }
        else throw new \RuntimeException('沒有已定義的郵件設定檔');

        $this->_transport = $transport;
        return $this;
    }

    /**
     * 發送郵件
     * 
     * @param Zend\Mail\Message $message
     * @return boolean 
     * @throws RuntimeException
     */
    public function send(Message $message)
    {
        if (!$this->_transport instanceof Transport\TransportInterface) 
            throw new \RuntimeException('傳輸協定尚未初始化');

        $this->_transport->send($message);
        return true;
    }

    /**
     * 初始化一個郵件實體
     * 
     * @param string $charset
     * @return Zend\Mail\Message
     */
    public function message($charset = self::DEFAULT_CHARSET)
    {
        $message = new Message();
        $message->setEncoding($charset);
        return $message;
    }

    /**
     * 指派變數內容
     *
     * @param string $content
     * @param array $maps
     * @return string
     */
    public function assign($content, array $variables = array())
    {
        //預先定義的變數
        if (empty($this->_variables)) {
            $this->_variables = array (
                'date' => date ('Y/m/d'),
                'time' => date ('Y/m/d H:i'),
                'ip' => $_SERVER['REMOTE_ADDR'],
            );
        }

        $assigns = array ();
        $variables = array_merge($this->_variables,  $variables);
        foreach ($variables as $name => $value) {
            $variable = $this->_tagLeft . $name . $this->_tagRight;
            $assigns['<!--' . $variable . '-->'] = $value;
            $assigns[$variable] = $value;
        }

        $content = strtr($content, $assigns);
        $matcher = preg_quote($this->_tagLeft, '/') . '.+' . preg_quote($this->_tagRight, '/');
        $content = preg_replace('/' . '<!--' . $matcher . '-->' . '/', '', $content);
        $content = preg_replace('/' . $matcher . '/', '', $content);
        return $content;
    }

    /**
     * 使用範本
     *
     * @param string $path
     * @param array $assigns
     * @return Zend\Mail\Message
     * @throws RuntimeException
     */
    public function sample($path, array $variables = array())
    {
        if (($content = @file_get_contents ($path)) === false)
            throw new \RuntimeException('無法開啟範本 ' . $path);

        $content = $this->assign($content, $variables);
        $split = preg_split('/(\n|\r\n){2}/', $content, 2);
        list ($header, $body) = array_pad($split, 2, null);

        $sample = array();
        $sample['headers'] = array();
        $sample['body'] = $body;
        
        //解析原始表頭
        $headers = array();
        $lines = preg_split('/(\n|\r\n)/', trim($header));
        foreach ($lines as $line) {
            if (preg_match('/^([a-z0-9\-]+):(.+)/i', $line, $matches)) {
                $name = $matches[1];
                $value = $matches[2];
                $headers[$name] = $value;
            } 
            else if (isset($name)) {
                $headers[$name] .= PHP_EOL . $line;
            }
        }
        foreach ($headers as $name => $value) {
            $name = strtolower($name);
            switch ($name) 
            {
                case 'content-type';
                    if (preg_match('/text\/\w+/i', $value)) {
                        $parse = preg_split ('/\s*;\s*/', $value, 2);
                        list ($docType, $charset) = array_pad($parse, 2, null);
                        $sample['docType'] = $docType;
                        if (preg_match('/charset=(.+)/i', $charset, $matches)) 
                            $sample['charset'] = isset($matches[1]) ? trim($matches[1], '"\'') : self::DEFAULT_CHARSET;
                    }
                    break;
                    
                case 'from':
                case 'reply-to':
                case 'return-path':
                    if ($addresses = $this->parseAddress($value)) 
                        $sample[$name] = $addresses[0];
                    break;
                    
                case 'to':
                case 'cc':
                case 'bcc':
                    if ($addresses = $this->parseAddress($value)) 
                        $sample[$name] = $addresses;
                    break;

                case 'subject':
                    $sample['subject'] = $value;
                    break;

                default:
                    if (!in_array($name, array('date', 'message-id'))) 
                        $sample['headers'][$name] = $value;
                    break;
            }
        }

        //郵件實體
        $message = $this->message(Arrays::value($sample, 'charset', null));

        //設定表頭
        foreach ($sample['headers'] as $name => $value) {
            $message->addHeader($name, $value);
        }

        //設定寄件人
        $froms = array('from', 'reply-to', 'return-path');
        foreach ($froms as $type) {
            if (isset($sample[$type])) {
                $method = 'set' . str_replace('-', '', $type);
                $message->$method($sample[$type]['address'], $sample[$type]['name']);
            }
        }

        //設定收件人
        $tos = array('to', 'cc', 'bcc');
        foreach ($tos as $type) {
            if (isset($sample[$type])) {
                foreach ($sample[$type] as $index => $email) {
                    list($email, $name) = array_values($email);
                    $message->{'add' . $type}($email, $name);
                }
            }
        }

        //設定主旨
        if (isset($sample['subject'])) {
            $message->setSubject($sample['subject']);
        }

        //設定內文
        if (isset($sample['docType'])) {
            $part = new MimePart($sample['body']);
            $part->type = $sample['docType'];
            $body = new MimeMessage();
            $body->setParts(array($part));
            $message->setBody($body);
        }
        
        return $message;
    }
    
    /**
     * 解析電子信箱地址清單
     *
     * 這個方法將會以 RFC822 協議解析格式
     *
     * @param string $addresses
     * @return array
     * 
     */
    public static function parseAddress($address) 
    {
        //允許以分號區隔
        $search[] = '/\s*;\s*/';
        $modify[] = ', ';

        //允許以中括號包覆
        $search[] = '/\[(([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,}))\]/';
        $modify[] = '<$1>';

        //允許以小括號包覆
        $search[] = '/\((([^@\s]+)@((?:[-a-z0-9]+\.)+[a-z]{2,}))\)/';
        $modify[] = '<$1>';
        
        $address = preg_replace($search, $modify, $address);

        //解析地址
        $addresses = array();
        $RFC822Parser = new RFC822Parser;
        $RFC822Parser->ParseAddressList($address, $addresses);
        
        //有效清單
        $validList = array();
        if ($addresses) {
            foreach ($addresses as $val) {
                if (isset($val['address'])) {
                    if (!isset($val['name'])) $val['name'] = '';
                    array_push($validList, $val);
                }
            }
        }
        return $validList;
    }

    /**
     * 驗證電子信箱地址是否有效
     * 
     * 此驗證包含網路連線檢查
     * 
     * @param string $email
     * @return boolean
     *
     */
    public static function verifyAddress($address)
    {
        $validator = new Validator\EmailAddress();
        return $validator->isValid($address);
    }

    /**
     * 傳回電子信箱地址是否有效
     *
     * @see http://fightingforalostcause.net/misc/2006/compare-email-regex.php
     * @param string $email
     * @return boolean
     *
     */
    public static function isValidAddress($address)
    {
        return preg_match('/^(?:(?:(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|\x5c(?=[@,"\[\]\x5c\x00-\x20\x7f-\xff]))(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|(?<=\x5c)[@,"\[\]\x5c\x00-\x20\x7f-\xff]|\x5c(?=[@,"\[\]\x5c\x00-\x20\x7f-\xff])|\.(?=[^\.])){1,62}(?:[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]|(?<=\x5c)[@,"\[\]\x5c\x00-\x20\x7f-\xff])|[^@,"\[\]\x5c\x00-\x20\x7f-\xff\.]{1,2})|"(?:[^"]|(?<=\x5c)"){1,62}")@(?:(?!.{64})(?:[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.?|[a-zA-Z0-9])+\.(?:xn--[a-zA-Z0-9]+|[a-zA-Z]{2,6})|\[(?:[0-1]?\d?\d|2[0-4]\d|25[0-5])(?:\.(?:[0-1]?\d?\d|2[0-4]\d|25[0-5])){3}\])$/', $address);
    }

}

?>