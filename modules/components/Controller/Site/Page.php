<?php

/**
 * 單元控制器
 * 
 * @version 1.1.0 2013/06/15 03:39
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */
 
namespace Spanel\Module\Controller\Site\Manager;

use Sewii\Type\Arrays;
use Sewii\Http\Request;
use Sewii\Http\Response;
use Spanel\Module\Component\Controller\Site\Unit;

abstract class Page extends Unit
{
    /**#@+
     * 事件定義
     * @const string
     */
    const EVENT_LOAD_FORM = 'onLoadForm';
    const EVENT_LOAD_LIST = 'onLoadList';
    /**#@-*/
    
    /**#@+
     * 欄位名稱
     *
     * @const string
     */
    const FIELD_ID      = 'id';
    const FIELD_VALUE   = 'value';
    const FIELD_FORM    = 'form';
    const FIELD_SWITCH  = 'switch';
    const FIELD_DELETE  = 'delete';
    /**#@-*/

    /**
     * 載入事件流程
     *
     * {@inheritDoc}
     */
    protected $loadEvents = array(
        self::EVENT_LOAD_LAYOUT => true,
        self::EVENT_LOAD_HEADER => true,
        self::EVENT_LOAD_BODY   => true,
        self::EVENT_LOAD_FORM   => true,
        self::EVENT_LOAD_LIST   => true,
        self::EVENT_LOAD_FOOTER => true,
    );
    
    /**
     * GET 事件
     *
     * @param Request $request
     * @return void
     */
    protected function onGet(Request $request)
    {
        parent::onGet($request);
        
        isset($request->param->filter);
    }

    /**
     * 新增事件
     * 
     * @param Request $request
     * @return void
     */
    protected function onHttpGetCategory(Request $request)
    {
    }

    /**
     * 新增事件
     * 
     * @param Request $request
     * @return void
     */
    protected function onApplyInsert(Request $request)
    {
    }

    /**
     * 新增事件
     * 
     * @param Request $request
     * @return void
     */
    protected function onInsert(Request $request)
    {
    }
    
    /**
     * 更新事件
     * 
     * @param Request $request
     * @return void
     */
    protected function onUpdate(Request $request)
    {
    }
    
    /**
     * 刪除事件
     * 
     * @param Request $request
     * @return void
     */
    protected function onDelete(Request $request)
    {
    }

    /**
     * 複製事件
     * 
     * @param Request $request
     * @return void
     */
    protected function onCopy(Request $request)
    {
    }

    /**
     * 儲存事件
     * 
     * @param Request $request
     * @return void
     */
    protected function onSave(Request $request)
    {
    }

    /**
     * 資料事件
     * 
     * @param Request $request
     * @return void
     */
    protected function onDatum(Request $request)
    {
    }

    /**
     * 開關事件
     * 
     * @param Request $request
     * @return void
     */
    protected function onSwitch(Request $request)
    {
    }

    /**
     * 載入表單事件
     * 
     * @param Request $request
     * @return void
     */
    protected function onLoadForm()
    {
    }
    
    /**
     * 列表文件工廠
     *
     * @param array $config
     * @return Listing
     */
    protected function listing(array $config = null)
    {
        // 共用設定檔
        if (isset($config[$profile = 'default'])) {
            $config = Arrays::mergeRecursive(array(
                'container'    => '#render-list',
                'events'       => array(
                    'start'    => array($this, 'onLoadListStart'),
                    'apply'    => array($this, 'onLoadListApply'),
                    'order'    => array($this, 'onLoadListOrder'),
                    'category' => array($this, 'onLoadListCategory'),
                    'filter'   => array($this, 'onLoadListFilter'),
                    'search'   => array($this, 'onLoadListSearch'),
                    'sort'     => array($this, 'onLoadListSort'),
                    'count'    => array($this, 'onLoadListCount'),
                    'each'     => array($this, 'onLoadListEach'),
                    'finish'   => array($this, 'onLoadListFinish'),
                ),
                'render' => true,
                'pager' => array(
                    'selector' => '#render-pager',
                    'size'     => 10,
                    'size'     => 3,
                )
            ),
            $config[$profile]);
        }

        return parent::listing($config);
    }
}

?>