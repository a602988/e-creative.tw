<?php

namespace Sewii\Net\Service\Google;

use Sewii\Http;

/**
 * 統計圖表類別
 *
 * @version v 1.0 2010/06/28 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 * @link http://code.google.com/intl/zh-TW/apis/chart/image/
 */
class Chart
{
    public $_apiUrl = 'http://chart.apis.google.com/chart';
    
    /**
     * 類型
     * @var string
     */
    public $type = null;
    
    /**
     * 寬度
     * @var integer
     */
    public $width = null;
    
    /**
     * 高度
     * @var integer
     */
    public $height = null;
    
    /**
     * 資料
     * @var string|array
     */
    public $data = null;
    
    /**
     * 標題
     * @var string
     */
    public $title = null;
    
    /**
     * 標籤
     * @var string|array
     */
    public $label = null;
    
    /**
     * 圖例
     * @var string|array
     */
    public $legend = null;
    
    /**
     * 顏色
     * @var string|array
     */
    public $color = null;
    
    
    /**
     * 建構子
     *
     * @param array $configs 參數
     *
     */
    public function __construct ($configs = null)
    {
        if ($configs) {
            foreach((array) $configs as $key => $val) {
                if ($key)
                    $this->$key = $val;
            }
        }
    }
    
    /**
     * 傳回類型參數
     *
     * @return string
     *
     */
    private function _paramType ()
    {
        if ($this->type)
            return 'cht=' . urlencode($this->type);
        return false;
    }
    
    /**
     * 傳回尺寸參數
     *
     * @return string
     *
     */
    private function _paramSize ()
    {
        if ($this->width && $this->height)
            return 'chs=' . urlencode($this->width . 'x' . $this->height);
        return false;
    }
    
    /**
     * 傳回資料參數
     *
     * @return string
     *
     */
    private function _paramData ()
    {
        if ($this->data) {
            if (is_array($this->data))
                $this->data = implode(',', $this->data);
            return 'chd=t:' . urlencode($this->data);
        }
        return false;
    }
    
    /**
     * 傳回標題參數
     *
     * @return string
     *
     */
    private function _paramTitle ()
    {
        if ($this->title)
            return 'chtt=' . urlencode($this->title);
        return false;
    }
    
    /**
     * 傳回標籤參數
     *
     * @return string
     *
     */
    private function _paramLabel ()
    {
        if ($this->label) {
            if (is_array($this->label))
                $this->label = implode('|', $this->label);
            return 'chl=' . urlencode($this->label);
        }
        return false;
    }
    
    /**
     * 傳回圖例參數
     *
     * @return string
     *
     */
    private function _paramLegend ()
    {
        if ($this->legend) {
            if (is_array($this->legend))
                $this->legend = implode('|', $this->legend);
            return 'chdl=' . urlencode($this->legend);
        }
        return false;
    }
    
    /**
     * 傳回顏色參數
     *
     * @return string
     *
     */
    private function _paramColor ()
    {
        if ($this->color) {
            if (is_array($this->color))
                $this->color = implode('|', $this->color);
            return 'chco=' . urlencode($this->color);
        }
        return false;
    }
    
    /**
     * 傳回所有參數
     *
     * @return string
     *
     */
    private function _params () 
    {
        $params = array();

        if ($this->_paramType()) 
            array_push($params, $this->_paramType());

        if ($this->_paramSize()) 
            array_push($params, $this->_paramSize());
            
        if ($this->_paramData()) 
            array_push($params, $this->_paramData());
            
        if ($this->_paramTitle()) 
            array_push($params, $this->_paramTitle());
            
        if ($this->_paramLabel()) 
            array_push($params, $this->_paramLabel());
            
        if ($this->_paramLegend()) 
            array_push($params, $this->_paramLegend());
            
        if ($this->_paramColor()) 
            array_push($params, $this->_paramColor());

        return ($p = implode('&', $params)) ? $p : $_SERVER['QUERY_STRING'];
    }
    
    /**
     * 傳回圖表 URL
     *
     * @return string
     *
     */
    public function apiUrl ()
    {
        $url = $this->_apiUrl . '?' . $this->_params();
        return $url;
    }
    
    /**
     * 傳回圖表 URL 別名
     *
     * @return string
     *
     */
    public function url ()
    {
        $url = '../../?action=chart&' . $this->_params();
        return $url;
    }

    /**
     * 重新導向
     *
     */
    public function redirect () 
    {
        Http\Response::redirect($this->apiUrl ());
    }

    /**
     * 顯示圖表
     *
     */
    public function show () 
    {
        header("Content-Disposition: inline; filename = chart.png");
        header("Content-Type: image/png");
        header("Cache-Control: max-age=9999, must-revalidate");
        readfile($this->apiUrl());
    }
}

?>