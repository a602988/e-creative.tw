<?php

/**
 * 樣板表單類別
 * 
 * @version 1.4.2 2013/06/12 14:37
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\View\Template;

use Sewii\Type\Variable;
use Sewii\Data\Json;
use Sewii\Text\Regex;

class Form extends Child
{
    /**
     * 表示控制項元素標籤名稱
     * 
     * @const string
     */
    const ELEMENT_TAGS = 'input, textarea, select, button';

    /**
     * 批次設定表單控制項內容
     *
     * @param array $data
     * @param mixed form 表單名稱或選擇器
     * @return integer
     */
    public function assigns($data, $form = null) 
    {
        $hasAssigns = array();
        $updated = 0; 
        if (is_array($data)) {
            $_form = $this->getForm($form);
            $elements = $_form->find(self::ELEMENT_TAGS)->filter('[name]');
            foreach($elements as $key => $element) {
                $element = pq($element);
                if ($name = $element->attr('name')) {
                    $name = Regex::replace('/\[.+$/', '', $name);
                    if (array_key_exists($name, $data) && !isset($hasAssigns[$name])) {
                        $this->assign($data[$name], $name, $form);
                        $hasAssigns[$name] = true;
                        $updated++;
                    }
                }
            }
        }
        return $updated;
    }
    
    /**
     * 設定表單控制項內容
     *
     * @param string value 控制項內容值
     * @param mixed element 控制項名稱或選擇器
     * @param mixed form 表單名稱或選擇器
     */
    public function assign($value, $element, $form = null)
    {
        $_element = $this->getElement($element, $form);

        //無法取得控制項時
        //嘗試以陣列型式尋找
        if (is_object($_element) && !$_element->length) {
            if ($this->isName($element) && !Regex::match('/\[.*\]$/i', $element)) {
                call_user_func(array($this, __FUNCTION__), $value, $element . '[]', $form);
            }
        }
        else 
        {
            $type = $this->getType($_element);
            if ($type) 
            {
                if (Variable::isBlank($value)) $value = '';
                $value = $this->format($value, $_element, $form);
                switch($type) 
                {
                    //text
                    default:
                    case 'input':
                    case 'input.text':
                        $_element->val($value);
                        break;

                    //checkbox
                    case 'input.checkbox':
                        if (!is_array($value)) $value = Regex::split('/\s*,\s*/', $value);
                        $_element->removeAttr('checked');
                        foreach($_element as $e) {
                            $e = pq($e);
                            if (in_array($e->val(), $value)) {
                                $e->attr('checked', 'checked');
                            }
                        }
                        break;

                    //radio
                    case 'input.radio':
                        $_element->removeAttr('checked');
                        $_element->filter('[value="' . $value . '"]')->attr('checked', 'checked');
                        break;

                    //textarea
                    case 'textarea':
                        $_element->text($value);
                        break;

                    //select
                    case 'select':
                        $_element->val($value);
                        break;

                    //select.multiple
                    case 'select.multiple':
                        if (!is_array($value)) $value = Regex::split('/\s*,\s*/', $value);
                        $_element['option']->removeAttr('selected');
                        foreach($_element['option'] as $e) {
                            $e = pq($e);
                            if (in_array($e->val(), $value)) {
                                $e->attr('selected', 'selected');
                            }
                        }
                        break;

                    //button
                    case 'button':
                    case 'button.button':
                    case 'button.submit':
                    case 'button.reset':
                        $_element->html($value);
                        break;
                }
            }
        }
    }
    
    /**
     * 取得表單控制項內容
     *
     * @param mixed element 控制項名稱或選擇器
     * @param mixed form 表單名稱或選擇器
     */
    public function value($element, $form = null)
    {
        $_element = $this->getElement($element, $form);

        //無法取得控制項時
        //嘗試以陣列型式尋找
        if (is_object($_element) && !$_element->length) {
            if ($this->isName($element) && !Regex::match('/\[.*\]$/i', $element)) {
                call_user_func(array($this, __FUNCTION__), $element . '[]', $form);
            }
        }
        else 
        {
            $type = $this->getType($_element);
            if ($type) 
            {
                switch($type) 
                {
                    //text
                    default:
                    case 'input':
                    case 'input.text':
                        return $_element->val();
                        break;

                    //checkbox (will return array)
                    case 'input.checkbox':
                        $checked = array();
                        foreach($_element->filter(':checked') as $e) {
                            $e = pq($e);
                            array_push($checked, $e->val());
                        }
                        return $checked;

                    //radio
                    case 'input.radio':
                        return $_element->filter(':checked')->val();

                    //textarea
                    case 'textarea':
                        return $_element->text();

                    //select
                    case 'select':
                        return $_element->val();

                    //select.multiple (will return array)
                    case 'select.multiple':
                        $selected = array();
                        foreach($_element->find('option')->filter('[selected]') as $e) {
                            $e = pq($e);
                            array_push($selected, $e->val());
                        }
                        return $selected;

                    //button
                    case 'button':
                    case 'button.button':
                    case 'button.submit':
                    case 'button.reset':
                        return $_element->html();
                }
            }
        }
    }

    /**
     * 批次格式化表單控制項資料
     *
     * @param array $data
     * @param mixed $form
     * @return array
     */
    public function formats($data, $form = null)
    {
        $hasFormats = array();
        if (is_array($data)) 
        {
            $_form = $this->getForm($form);
            $elements = $_form->find(self::ELEMENT_TAGS)->filter('[name]');
            foreach($elements as $key => $element) {
                $element = pq($element);
                if ($name = $element->attr('name')) {
                    $name = Regex::replace('/\[.+$/', '', $name);
                    if (array_key_exists($name, $data) && !isset($hasFormats[$name])) {
                        $data[$name] = $this->format($data[$name], $element, $form);
                        $hasFormats[$name] = true;
                    }
                }
            }
        }
        return $data;
    }
    
    /**
     * 格式化表單控制項資料
     *
     * @param string value 控制項內容值
     * @param mixed element 控制項名稱或選擇器
     * @param mixed form 表單名稱或選擇器
     * @return mixed
     */
    public function format($data, $element, $form)
    {
        $_element = $this->getElement($element, $form);

        //無法取得控制項時
        //嘗試以陣列型式尋找
        if (is_object($_element) && !$_element->length) {
            if ($this->isName($element) && !Regex::match('/\[.*\]$/i', $element)) {
                call_user_func(array($this, __FUNCTION__), $value, $element . '[]', $form);
            }
        }
        else
        {
            if ($param = $this->getContext($_element)->data(Template::FIELD_PARAMS)) 
            {
                //資料格式化
                if (isset($param['type'])) {
                    switch(strtolower($param['type'])) 
                    {
                        //數字格式化
                        case 'number':
                            $data = number_format($result);
                            break;

                        //日期格式化
                        case 'date':
                        case 'time':
                        case 'datetime':
                            if (empty($param['format'])) {
                                switch($param['type']) {
                                    case 'date': $param['format'] = 'Y-m-d'; break;
                                    case 'time': $param['format'] = 'H:i:s'; break;
                                    case 'datetime': $param['format'] = 'Y-m-d H:i'; break;
                                }
                            }
                            if ($timer = strtotime($data))
                                $data = date($param['format'], $timer);
                            break;

                        //json
                        case 'json':
                        case 'media':
                        case 'fields':

                            //from Media
                            if (Media::isFormat($data)) {
                                $data = Media::decode($data, true);
                            }
                            //from Serialize
                            else if (Variable::isSerialize($data)) {
                                $data = Variable::unserialize($data);
                                $data = Json::encode($data);
                            }

                            break;
                    }
                }
            }
        }
        return $data;
    }
    
    /**
     * 傳回控制項類型
     *
     * @param mixed element
     * @return string
     */
    protected function getType($element)
    {
        $type = null; $_element = pq($element);

        //textarea
        if ($_element->is('textarea')) $type = 'textarea';

        //select
        else if ($_element->is('select')) {
            $type = 'select';
            if ($_element->attr('multiple'))
                $type .= '.multiple';
        }
        //button
        else if ($_element->is('button')) {
            $type = 'button';
            if ($_element->attr('type') == 'button') $type .= '.button';
            else if ($_element->attr('type') == 'submit') $type .= '.submit';
            else if ($_element->attr('type') == 'reset') $type .= '.reset';
        }
        //inputs
        else if ($_element->is('input')) {
            $type = 'input';
            if ($_element->attr('type'))
                $type .= '.' . $_element->attr('type');
        }
        return $type;
    }
    
    /**
     * 傳回控制項類型
     *
     * @param mixed element
     * @param mixed form
     * @return object
     */
    protected function getElement($element, $form)
    {
        if (is_object($element)) return $element;
        $_form = $this->getForm($form); $_element = null;
        if ($this->isName($element)) $_element = $_form->find(self::ELEMENT_TAGS)->filter('[name="' . $element . '"]');
        else if ($this->isSelector($element)) $_element = $_form->find($element);
        return $_element;
    }
    
    /**
     * 傳回表單物件
     *
     * @param mixed name
     * @return object
     */
    protected function getForm($name)
    {
        if (is_object($name)) return $name;
        $form = $this->getContext('html');
        if ($this->isName($name)) $form = $this->getContext('form[name="' . $name . '"]');
        else if ($this->isSelector($name)) $form = $this->getContext($name);
        return $form;
    }
    
    /**
     * 傳回是否為選擇器
     *
     * @param string name
     * @return boolean
     */
    protected function isSelector($selector)
    {
        if (!$selector) return false;
        return Regex::match('/^[a-z0-9_\-\.\[\]\(\)\s#=:,"\']+$/i', $selector) ? true : false;
    }
    
    /**
     * 傳回是否為表單名稱
     *
     * @param string name
     * @return boolean
     */
    protected function isName($name)
    {
        if (!$name) return false;
        return Regex::match('/^[a-z0-9_\-\[\]]+$/i', $name) ? true : false;
    }
}

?>