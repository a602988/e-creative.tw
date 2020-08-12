<?php

/**
 * 指令控制器
 * 
 * @version 1.0.1 2013/02/06 00:35
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Component\Controller;

use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\System\Registry;
use Sewii\Filesystem\File;
use Sewii\Filesystem\Directory;
use Spanel\Module\Component\Router\Intent;
use Spanel\Module\Component\Router\IntentActionInterface;

abstract class Order
    extends Controller 
    implements IntentActionInterface
{
    /**
     * 控制器命名空間
     *
     * @const string
     */
    const NAMESPACE_CONTROLLER = 'Spanel\Module\Controller\Order';
    
    /**
     * 執行階段狀態
     *
     * @var array
     */
    protected static $states = array();
    
    /**
     * 工廠模式
     * 
     * @param string $site
     * @param string $unit
     * @return Site
     * @throws Exception\RuntimeException
     */
    public static function factory($order = null)
    {
        //從子類別生產
        if (($className = get_called_class()) != __CLASS__) {
            $order = self::getOrder();
        }
        
        if (!self::hasOrder($order)) {
            $order = $order ?: '[NOT SET]';
            ErrorHandler::trigger(404, "找不到符合的指令控制器: $order");
        }
        
        $controller = self::NAMESPACE_CONTROLLER . '\%s';
        $controller = sprintf($controller, ucfirst($order));
        if (!class_exists($controller)) {
            throw new Exception\RuntimeException("無法初始化指令: $order");
        }

        $controller = new $controller();
        return $controller;
    }

    /**
     * 從意圖物件執行
     * 
     * {@inheritDoc}
     */
    public static function executeIntent(Intent $intent)
    {
        $data = $intent->getExtras();

        if (empty($data->order)) {
            throw new Exception\InvalidArgumentException('意圖物件至少必須包含指令名稱');
        }
        
        return self::factory($data->order, $data->params)->run();
    }

    /**
     * 開始執行
     *
     * @return Site
     */
    public function run()
    {
        $initializer = array($this, 'initialize');
        if (is_callable($initializer)) {
            call_user_func($initializer);
        }
        return $this;
    }
    
    /**
     * 傳回指令清單
     *
     * @return array
     * @throws Exception\RuntimeException
     */
    public static function getOrders()
    {
        if (isset(self::$states['orders'])) {
            return self::$states['orders'];
        }
        
        $config = Registry::getConfig();
        $filename = File::getFilename(__FILE__);
        $orderPath = sprintf('%s/%s', $config->path->controller, $filename);
        if (!File::isDir($orderPath)) {
            throw new Exception\RuntimeException("無法取得指令清單: $orderPath");
        }

        $orders = array();
        $directory = new Directory($orderPath);
        $exts = implode('|', Regex::split('/\s*,\s*/', self::EXT_PHP));
        $exts = str_replace('.', '', $exts);
        foreach ($directory as $item) {
            if ($item->isFile() && $item->isExtension($exts)) {
                $filename = $item->getFilename();
                if (Regex::isMatch('/^[\w\-\.]+$/', $filename)) {
                    array_push($orders, $filename);
                }
            }
        }

        self::$states['orders'] = $orders;
        return $orders;
    }
    
    /**
     * 傳回指令是否存在
     *
     * @param string $value
     * @return boolean
     */
    public static function hasOrder($value)
    {
        if ($value) {
            try {
                $value = ucfirst($value);
                $orders = self::getOrders();
                return in_array($value, $orders);
            }
            catch (\Exception $ex) {}
        }
        return false;
    }
    
    /**
     * 傳回指令名稱
     *
     * @return string
     */
    public function getOrder()
    {
        if (($className = get_called_class()) == __CLASS__) {
            throw new Exception\BadMethodCallException('此方法不支援抽象方法呼叫');
        }

        if ($matches = Regex::match('/\w+$/', $className)) {
            $order = lcfirst($matches[0]);
        }
        return $$order;
    }
}

?>