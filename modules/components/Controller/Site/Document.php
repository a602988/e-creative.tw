<?php

/**
 * 單元文件類別
 * 
 * @todo 不要繼承 Unit??
 * @version 1.1.0 2013/12/03 15:35
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
namespace Spanel\Module\Component\Controller\Site;

use ArrayAccess;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\System\Config;

abstract class Document 
    extends Unit 
    implements ArrayAccess
{
    /**#@+
     * 事件定義
     * @const string
     */
    const EVENT_INIT = 'init';
    /**#@-*/

    /**
     * 設定檔
     * 
     * @var array
     */
    protected $config = array(
        'container' => null,
    );

    /**
     * 事件表
     * 
     * @var array
     */
    protected $events = array();
    
    /**
     * 執行狀態
     * 
     * @var array
     */
    protected $states = array();
    
    /**
     * 渲染物件
     * 
     * @var mixed
     */
    protected $renderer;

    /**
     * 初始化
     * 
     * @param array $config
     * @return Document
     */
    public function init(array $config = array())
    {
        if (isset($config['events'])) {
            $this->events = (array) $config['events'];
            unset($config['events']);
        }

        $this->config = new Config(
            Arrays::mergeRecursive($this->config, $config)
        );

        $this->renderer = $this->site->view->object();
        if ($container = $this->isContainerExists()) {
            $this->renderer = $container;
        }
        
        $this->trigger(self::EVENT_INIT);

        return $this;
    }
    
    /**
     * 傳回容器是否存在
     * 
     * @return boolean|Sewii\View\Template\Object
     */  
    public function isContainerExists()
    {
        if (!empty($this->config->container)) {
            if (isset($this->states['container'])) {
                return $this->states['container'];
            }
            $container = $this->site->view[$this->config->container];
            $this->states['container'] = $container->length ? $container : false;
            return $container;
        }
        return false;
    }

    /**
     * 觸發事件
     *
     * @param mixed $event [, mixed $arg1... ]
     * @return Document
     */
    protected function trigger($event)
    {
        if (isset($this->events)) {
            if (!empty($this->events[$event])) {
                $callback = $this->events[$event];
                if (is_callable($callback)) {
                    $args = func_get_args();
                    $args[0] = $this;
                    call_user_func_array($callback, $args);
                }
            }
        }
        return $this;
    }
    
    /**
     * 傳回設定檔
     * 
     * @return array
     */   
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * 傳回事件表
     * 
     * @return array
     */   
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * __invoke
     *
     * @param string $selector
     * @return Renderer
     */
    public function __invoke($selector)
    {
        return $this->find($selector);
    }

    /**
     * offsetGet
     * 
     * @param mixed $offset
     * @return Renderer
     */
    public function offsetGet($offset) 
    {
        return $this->find($offset);
    }

    /**
     * offsetSet
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value) 
    {
        $this->find($offset)->html($value);
    }

    /**
     * offsetExists
     *
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset) 
    {
        return ($this->find($offset)->length > 0);
    }

    /**
     * offsetUnset
     *
     * @param mixed $offset 
     * @return void
     */
    public function offsetUnset($offset) 
    {
        $this->find($offset)->remove();
    }
    
    /**
     * 搜尋選擇器
     * 
     * @param mixed $selector
     * @return mixed
     */
    public function find($selector) 
    {
        return $this->renderer[$selector];
    }
}

?>