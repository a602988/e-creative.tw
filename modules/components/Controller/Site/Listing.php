<?php

/**
 * 列表文件類別
 * 
 * @version 1.1.1 2016/09/02 19:30
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
namespace Spanel\Module\Component\Controller\Site;

use Iterator;
use Countable;
use Sewii\Exception;
use Sewii\Text\Regex;
use Sewii\Type\Arrays;
use Sewii\Type\Variable;
use Sewii\Data\Hashtable;
use Sewii\Data\Dataset\DatasetInterface;
use Sewii\Data\Json;
use Sewii\System\Config;
use Sewii\View\Pager\Pager;
use Sewii\View\Pager\Classic;
use Sewii\View\Template\Renderer;

class Listing 
    extends Document 
    implements Iterator, Countable
{
    /**
     * 設定檔
     * 
     * @todo $config->selector 直接傳入物件
     * @var array
     */
    protected $config = array (
        'dataset'      => null,
        'container'    => '.render-list',
        'intl'         => false,
        'events'       => array(),
        'infoPrefix'   => 'info',
        'emptyRow'     => '.render-empty',
        'pager'        => array(
            'selector' => '.render-pager',
            'type'     => Classic::ID,
            'field'    => Classic::FIELD,
            'config'   => array()
        ),
        'render'       => array(
            'list'     => true,
            'pager'    => true,
            'info'     => false
        )
    );

    /**#@+
     * 事件定義
     * @const string
     */
    const EVENT_START    = 'start';
    const EVENT_APPLY    = 'apply';
    const EVENT_ORDER    = 'order';
    const EVENT_CATEGORY = 'category';
    const EVENT_FILTER   = 'filter';
    const EVENT_SEARCH   = 'search';
    const EVENT_SORT     = 'sort';
    const EVENT_COUNT    = 'count';
    const EVENT_EACH     = 'each';
    const EVENT_FINISH   = 'finish';
    /**#@-*/
    
    /**
     * 資料集合
     * 
     * @var array
     */
    private $dataset;
    
    /**
     * 迭代器
     * 
     * @var Iterator
     */
    private $iterator;
    
    /**
     * 循環進度
     * 
     * @var integer
     */
    private $loop = 0;
    
    /**
     * 資料列總數
     * 
     * @var integer
     */
    private $total = 0;
    
    /**
     * 儲存收集格式列
     * 
     * @var array
     */
    private $gathered = array();
    
    /**
     * 頁數器
     * 
     * @var Pager
     */
    private $pager = null;

    /**
     * 初始化
     * 
     * {@inheritDoc}
     */
    public function init(array $config = array())
    {
        // Dataset
        if (!isset($config['dataset'])) {
            throw new Exception\InvalidArgumentException('必須包含資料來源集合物件');
        } 
        else if (!$config['dataset'] instanceof DatasetInterface) {
            throw new Exception\InvalidArgumentException(
                '資料來源集合物件必須實作 Sewii\Data\DatasetInterface 介面'
            );
        }
        else {
            $this->dataset = $config['dataset'];
            unset($config['dataset']);
        }

        // Initialize
        parent::init($config);

        // Outer configure
        if ($container = $this->isContainerExists()) {
            if ($param = $container->param()) {
                if (!empty($param['size'])) {
                    $param['size'] = intval($param['size']);
                    $this->config->pager->size = $param['size'];
                }
                if (!empty($param['sort'])) {
                    $this->config->orderBy = $param['sort'];
                }
            }
        }

        return $this;
    }
    
    /**
     * 迭代方法
     *  
     * @param callable $callback
     * @return Document
     */
    public function each($callback = null) 
    {
        foreach ($this as $key => $value) {
            if ($callback && is_callable($callback)) {
                call_user_func($callback, $this, $key, $value);
            }
        }
        return $this;
    }
    
    /**
     * Rewind
     *  
     * @return void
     */
    public function rewind() 
    {
        $this->onStart();
        if ($this->iterator === null) {
            $this->iterator = $this->dataset->getIterator();
        }
        $this->iterator->rewind();
    }
    
    /**
     * current
     *  
     * @return mixed
     */
    public function current() 
    {
        if ($this->isContainerExists()) {
            $this->renderer = $this->site->view->renderer($this->config->container);
            $this->gather();
        }
        
        $value = $this->iterator->current();
        $this->trigger(self::EVENT_EACH, $this->key(), $value = $this->iterator->current());
        return $value;
    }
    
    /**
     * key
     *  
     * @return integer
     */
    public function key() 
    {
        return $this->iterator->key();
    }
    
    /**
     * next
     *  
     * @return void
     */
    public function next() 
    {
        $this->loop++;
        $this->iterator->next();
    }
    
    /**
     * valid
     *  
     * @return boolean
     */
    public function valid() 
    {
        if (!$this->iterator->valid()) {
            $this->onFinish();
            return false;
        }
        return true;
    }
    
    /**
     * count
     *  
     * @return integer
     */
    public function count() 
    { 
        return $this->getTotal();
    }
    
    /**
     * 開始事件
     * 
     * @return Document
     */  
    protected function onStart()
    {
        return $this
            ->trigger(self::EVENT_START)
            ->trigger(self::EVENT_APPLY)
            ->trigger(self::EVENT_CATEGORY)
            ->trigger(self::EVENT_FILTER)
            ->trigger(self::EVENT_SEARCH)
            ->trigger(self::EVENT_ORDER)
            ->trigger(self::EVENT_SORT)
            ->onReset()
            ->onCount()
            ->onPager();
    }
    
    /**
     * 重設事件
     * 
     * @return Document
     */
    protected function onReset() 
    {
        $this->gathered = array();
        $this->pager = null;
        $this->loop = 0;
        return $this;
    }
    
    /**
     * 計數事件
     * 
     * @return Document
     */  
    protected function onCount()
    {
        $this->total = count($this->dataset);
        $this->trigger(self::EVENT_COUNT);
        return $this;
    }
    
    /**
     * 頁數器事件
     * 
     * @return Document
     */  
    protected function onPager()
    {
        if (!empty($this->config->pager->size)) {
            $this->pager = new Pager(
                $this->total, 
                $this->config->pager->size,
                $this->request->param[$this->config->pager->field], 
                $this->config->pager->field
            );

            $this->dataset->offset($this->pager->offset);
            $this->dataset->limit($this->pager->size);
        }
        return $this;
    }
    
    /**
     * 結束事件
     * 
     * @return void
     */  
    protected function onFinish()
    {
        $this->trigger(self::EVENT_FINISH);
        $this->render();
        return $this;
    }

    /**
     * 渲染方法
     * 
     * @return Document
     */    
    public function render()
    {
        $output = array();
        $view = $this->site->getView();

        // Info
        if ($isRenderInfo = $this->isRender('info')) {
            $info = $this->pager ? $this->pager->info : array('rows' => $this->total);
            if ($isRenderInfo === -1) $output[$this->config->infoPrefix] = $info;
            foreach ($info as $key => $val) {
                $view->deferrer("#{$this->config->infoPrefix}-$key")->text($val);
            }
        }

        // Pager
        if ($this->pager && $isRenderPager = $this->isRender('pager')) {
            $pager = $this->pager->generate($this->config->pager->type, $this->config->pager->config);
            if ($isRenderPager === -1) $output['pager'] = $pager;
            else $view->deferrer($this->config->pager->selector)->html($pager);
        }
        
        // List
        if (empty($this->gathered)) $this->gathered[] = $this->getEmptyRow();
        if ($isRenderList = $this->isRender('list')) {
            if ($isRenderList === -1) $output['list'] = $this->getGathered();
            else if ($container = $this->isContainerExists()) {
                $view->deferrer($this->config->container)->html($this->getGathered());
            }
        }

        // Output
        if (!empty($output)) {
            $output['config'] = $this->config->toArray();
            $output = Json::encode($output);
            $this->site->response->write($output);
        }

        return $this;
    }

    /**
     * 傳回項目是否需要渲染
     * 
     * Yes: true, No: false, Output: -1
     *
     * @param string $name
     * @return boolean|integer 
     */
    protected function isRender($name) 
    {
        $isNeed = false;
        if (isset($this->config->render)) {
            $setting = $this->config->render instanceof Config
                     ? $this->config->render->$name
                     : $this->config->render;

            if (Variable::isTrue($setting)) $isNeed = true;
            else if (Variable::isFalse($setting)) $isNeed = false;
            else if ($setting === -1) $isNeed = -1;
        }
        return $isNeed;
    }

    /**
     * 傳回無資料時的顯示列
     *
     * @return mixed
     */
    public function getEmptyRow()
    {
        if ($container = $this->isContainerExists()) {
            if (isset($this->config->emptyRow)) {
                $emptyRow = $container[$this->config->emptyRow];
                if ($emptyRow->length) {

                    // Automatic span for TR element
                    if ($emptyRow->is('tr') && $emptyRow['td']->length === 1) {
                        if ($renderer = $this->site->view->renderer($this->config->container)) {
                            if ($colspan = $renderer['td']->length) {
                                $emptyRow['td']->attr('colspan', $colspan);
                            }
                        }
                    }
                    return $emptyRow;
                }
            }
        }
        return '';
    }
    
    /**
     * 傳回資料集合
     * 
     * @return array
     */   
    public function getDataset()
    {
        return $this->dataset;
    }
    
    /**
     * 傳回頁數器
     * 
     * @return Pager
     */   
    public function getPager()
    {
        return $this->pager;
    }

    /**
     * 收集格式化列
     * 
     * @return Document
     */    
    public function gather()
    {
        if ($this->renderer instanceof Renderer) {
            $this->gathered[] = $this->renderer;
        }
        return $this;
    }
    
    /**
     * 傳回已收集的列
     * 
     * @return string
     */   
    public function getGathered()
    {
        $gathered = '';
        if (is_array($this->gathered)) {
            $gathered = implode(PHP_EOL, $this->gathered);
        }
        return $gathered;
    }
    
    /**
     * 傳回目前是否為偶數列
     *
     * @return boolean
     */
    public function isEvenRow()
    {
        return ($this->loop + 1) % 2 === 0;
    }
    
    /**
     * 傳回目前是否為單數列
     *
     * @return boolean
     */
    public function isOddRow()
    {
        return ($this->loop + 1) % 2 === 1;
    }
    
    /**
     * 傳回目前是否為最後列
     *
     * @return boolean
     */
    public function isLastRow()
    {
        return $this->loop === $this->total;
    }
    
    /**
     * 傳回目前是否為第一列
     *
     * @return boolean
     */
    public function isFirstRow()
    {
        return $this->loop === 0;
    }
    
    /**
     * 傳回資料總列數
     * 
     * @return integer
     */   
    public function getTotal()
    {
        return $this->total;
    }
    
    /**
     * 傳回序列號
     * 
     * @return integer
     */   
    public function getSerial()
    {
        $serial = $this->loop + 1;
        if ($this->pager) {
            $serial = $this->pager->getSerial($serial);
        }
        return $serial;
    }
    
    /**
     * 傳回目前循環
     * 
     * @return integer
     */   
    public function getLoop()
    {
        return $this->loop;
    }
}

?>