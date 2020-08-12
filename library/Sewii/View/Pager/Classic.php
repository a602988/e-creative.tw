<?php

/**
 * 經典版頁數器
 * 
 * @version 1.3.8 2013/12/12 14:30
 * @author JOE (joe@e-creative.tw)
 * @copyright (c) 2013 e-creative
 * @link http://www.youweb.tw/
 */

namespace Sewii\View\Pager;

use Sewii\Uri;
use Sewii\Type\Arrays;
use Sewii\Text\Strings;
use Sewii\Text\Regex;

class Classic extends Pager
{
    /**
     * 識別碼
     * 
     * @var string
     */
    const ID = "classic";

    /**
     * 設定值
     * 
     * @var array
     */
    public $config = array
    (
        'labels' => array(
            'TW' => array(
                'prev' => '&laquo; 上一頁',
                'next' => '下一頁 &raquo;',
                'page' => '%s',
                'title' => '第 %s 頁'
            ),
            'EN' => array(
                'prev' => '&laquo; Prev',
                'next' => 'Next &raquo;',
                'page' => '%s',
                'title' => 'Page of %s'
            )
        ),
        'intl'          => false,
        'limit'         => 4,
        'wrapper'       => 'li',
        'selectedClass' => 'selected',
        'prevWrapClass' => '',
        'nextWrapClass' => '',
        'pageWrapClass' => '',
        'prevLinkClass' => 'prev',
        'nextLinkClass' => 'next',
        'pageLinkClass' => 'page',
        'skipClass'     => '',
        'skipSign'      => '[ ... ]'
    );

    /**
     * 產生器
     * 
     * @param array $config
     * @return string
     */
    public function generate()
    {
        list($config) = func_get_args();
        if (is_array($config)) {
            $this->config = Arrays::mergeRecursive($this->config, $config);
        }

        // 左偏移
        $leftLimit = $this->current - $this->config['limit'];
        if ($leftLimit < 1) $leftLimit = 1;

        // 右偏移
        $rightLimit = $this->config['limit'] + ($this->current - 1);
        
        $code = '';

        // 上一頁：第二頁以上顯示
        if ($this->current > 1) {
            $link = $this->getLink($this->getLabel('prev'), $this->getUrl($this->prev), $this->config['prevLinkClass']);
            $code .= $this->getWraps($link, $this->config['prevWrapClass']);
        }
        
        // 左偏移大於 1 時顯示
        if ($leftLimit > 1) {
            $link = $this->getLink($this->first, $this->getUrl($this->first));
            $code .= $this->getWraps($link, $this->config['pageWrapClass']);
            if ($leftLimit > 2) {
                $code .= $this->getWraps($this->config['skipSign'], $this->config['skipClass']);
            }
        }

        // 頁數清單
        for ($p = $leftLimit; $p <= $this->total; $p++)
        {
            // 不得大於右偏移
            if ($p > $rightLimit) break;
            $selectedClass = ($p == $this->current) ? $this->config['selectedClass'] : '';
            $link = $this->getLink(sprintf($this->getLabel('page'), $p), $this->getUrl($p), $selectedClass, sprintf($this->getLabel('title'), $p));
            $code .= $this->getWraps($link, $this->config['pageWrapClass']);
        }
        
        // 右偏移小於總頁數時
        if ($rightLimit < $this->last) {
            if ($rightLimit < $this->last - 1) {
                $code .= $this->getWraps($this->config['skipSign'], $this->config['skipClass']);
            }
            $link = $this->getLink($this->last, $this->getUrl($this->last));
            $code .= $this->getWraps($link, $this->config['pageWrapClass']);
        }

        // 下一頁：小於後最頁時顯示
        if ($this->current < $this->last) {
            $link = $this->getLink($this->getLabel('next'), $this->getUrl($this->next), $this->config['nextLinkClass']);
            $code .= $this->getWraps($link, $this->config['nextWrapClass']);
        }

        return $code;
    }

    /**
     * 傳回標籤
     *
     * @param integer $page
     * @return mixed
     */
    protected function getLabel($name)
    {
        $labels = Arrays::getFirst($this->config['labels']);
        if ($this->config['intl']) {
            $labels = Intl\Intl::getInstance()->getContext($this->config['labels']);
        }
        return $labels[$name];
    }

    /**
     * 傳回頁數 URL
     *
     * @param integer $page
     * @return mixed
     */
    protected function getUrl($page)
    {
        $query = new Uri\Http\Query();
        $query->setSeparator('&amp;');
        $query->add("{$this->field}=$page");
        return $query->toPath();
    }

    /**
     * 傳回左/右方向容器
     *
     * @param string $direction
     * @param string $class
     * @return string
     */
    protected function getWrap($direction, $class = '')
    {
        if (!empty($class)) $class = ' class="' . $class . '"';
        if (strtolower($direction) == 'left') return "<{$this->config['wrapper']}{$class}>";
        if (strtolower($direction) == 'right') return "</{$this->config['wrapper']}>";
    }

    /**
     * 傳回包含內容的容器
     *
     * @param string $content
     * @param string $class
     * @return string
     */
    protected function getWraps($content, $class = '')
    {
        return $this->getWrap('left', $class) . $content . $this->getWrap('right');
    }

    /**
     * 傳回連結
     *
     * @param string $label
     * @param string $href
     * @param string $class
     * @return string
     */
    protected function getLink($label, $href = '', $class = '', $title = '')
    {
        if (!empty($class)) $class = ' class="' . $class . '"';
        if (empty($title)) $title = Strings::summary($label);
        $matches = Regex::match("/{$this->field}[=\/](\d+)/", $href);
        $page = isset($matches[1]) ? $matches[1] : '';
        $link = '<a href="%s" title="%s"%s data-page="%s">%s</a>';
        return sprintf($link, $href, $title, $class, $page, $label);
    }

}

?>