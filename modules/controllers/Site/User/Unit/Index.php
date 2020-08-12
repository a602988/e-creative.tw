<?php

/**
 * 單元控制器 (Index)
 *
 * @version 1.6.0 2016/09/02 20:10
 * @author JOE (joe@ecreative.tw)
 * @copyright (c) 2016 ecreative
 * @link http://www.youweb.tw/
 */

namespace Spanel\Module\Controller\Site\User\Unit;

use Sewii\Exception;
use Sewii\System\Registry;
use Sewii\System\Config;
use Sewii\Cache\Cache;
use Sewii\Filesystem\Path;
use Sewii\Filesystem\File;
use Sewii\Net\Mail;
use Sewii\Text\Regex;
use Sewii\Text\Strings;
use Sewii\Type\Variable;
use Sewii\Type\Arrays;
use Sewii\Data\Hashtable;
use Sewii\Data\Json;
use Sewii\Data\Dataset\AbstractDataset;
use Sewii\Uri\Http as HttpUri;
use Sewii\View\Template\Template;
use Sewii\Data\Dataset\ArrayDataset;
use Spanel\Module\Component\Controller\Site\Unit;

class UnitIndex extends Unit
{
    /**
     * 設定檔路徑
     *
     * @const string
     */
    const CONFIG = 'configs/site.xml';

    /**
     * Meta 描述長度
     *
     * @const string
     */
    const META_DESCRIPTION_LENGTH = 200;

    /**
     * 頁面載入事件
     *
     * return void
     */
    protected function onLoadLayout()
    {
        $this->listens();
        $this->renders();
    }

    /**
     * 偵聽開始
     *
     * return void
     */
    protected function listens()
    {
        $this->listenResponse();
        $this->listenContact();
        $this->listenWork();
    }

    /**
     * 偵聽回應測試
     *
     * return void
     */
    protected function listenResponse()
    {
        if ($this->request->param->unit === 'hello') {
            $this->response->stop(200);
        }
    }

    /**
     * 偵聽聯絡我們
     *
     * return void
     */
    protected function listenContact()
    {
        if ($this->request->param->unit !== 'contact'
            || empty($_POST['name'])
            || empty($_POST['email'])
            || empty($_POST['phone'])
            || empty($_POST['subject'])
            || empty($_POST['message'])
            || empty($_POST['-validator-contact-mail-'])
            || Regex::isMatch('/^\d{14}$/', $_POST['-validator-contact-mail-']) ) return;

        $config = Registry::getConfig();
        $setting = $this->getConfig()->setting;

        $mail = new Mail;
        $mail::$setups = $config->mail;
        $template = "{$config->path->template}/email/contact.eml";
        $serial = ucfirst(substr($setting->id, 0, 1)) . Strings::serial(0);
        $websiteUrl = $this->getBaseUrl();
        $websiteName = $setting->contact->name;
        $websiteEmail = $setting->contact->from;
        $_POST['message'] = Strings::nl2br($_POST['message']);
        $data = $_POST + array(
            'serial' => $serial,
            'websiteUrl' => $websiteUrl,
            'websiteName' => $websiteName,
            'websiteEmail' => $websiteEmail
        );

        $message = $mail->sample($template, $data);
        $message->setFrom($setting->contact->from, $setting->contact->name);
        $message->addTo($data['email']);

        if (!empty($setting->contact->cc)) {
            $message->addCc(Regex::split('/\s*,\s*/', $setting->contact->cc));
        }

        if (!empty($setting->contact->bcc)) {
            $message->addBcc(Regex::split('/\s*,\s*/', $setting->contact->bcc));
        }

        $mail->send($message);

        // SMS
        if (Variable::isTrue($setting->contact->sms->enabled)) {
            $template = new File("{$config->path->template}/sms/contact.txt");
            if ($template->isReadable()) {
                $message = $template->read();
                $message = $mail->assign($message, $data);
                $message = mb_convert_encoding($message, 'BIG5', 'UTF-8');
                $message = rawurlencode($message);
                $phones = Regex::split('/\s*,\s*/', $setting->contact->sms->to);
                foreach ($phones as $phone) {
			        @File::read(
                        $setting->contact->sms->api
                        . "?username=" . $setting->contact->sms->username
				        . "&password=" . $setting->contact->sms->password
                        . "&dstaddr=" . $phone
				        . "&smbody=" . $message
                    );
                }
            }
        }

        $this->response->stop(200);
    }

    /**
     * 偵聽作品介紹
     *
     * return void
     */
    protected function listenWork()
    {
        if ($this->request->param->unit === 'works'
            && $this->request->param->get === 'json'
            && $this->request->param->detail) {

            $output = null;
            if ($work = $this->getWorks($this->request->param->detail)) {
                $output = $work;
            }

            $json = Json::encode($output);
            $this->response->stop($json);
        }
    }

    /**
     * 渲染開始
     *
     * return void
     */
    protected function renders()
    {
        //$config = $this->getConfig();
        //$cache = $this->getCacheable($config->namespace);
        //$output = $cache->get($cacheKey = 'output-' . md5($_SERVER['REQUEST_URI']));

        //if (isset($output)) {
        //    try {
        //        $document = Template::create($output);
        //        $this->site->view->document = $document;
        //        return;
        //    }
        //    catch (Exception\RuntimeException $ex) {}
        //}

        $this->renderPage();
        $this->renderWork();
        $this->renderWorks();
        $this->preloadImage();

        //$cache->$cacheKey = $this->site->view->toString();
    }

    /**
     * 渲染頁面
     *
     * return void
     */
    protected function renderPage()
    {
        $config = $this->getConfig();
        $cache = $this->getCacheable($config->namespace);
        $setting = $config->setting;
        $unit = $this->request->param->unit;
        $view = $this->site->view;
        $main = $view["#main"];
        $page = $main["[data-unit='{$unit}']"];
        $isDefault = $unit === 'default';

        // Is exists?
        if (!$isDefault && !$page->length) {
            $uri = HttpUri::factory()->getUri(HttpUri::FILTER_PATH | HttpUri::FILTER_QUERY);
            $this->response->redirect($uri)->stop();
        }

        // Meta
        $view->element('meta')->name('description', $setting->description)->render();
        $view->element('meta')->name('keywords', $setting->keywords)->render();

        // Favicon
        if ($setting->favicon) {
            $favicon = $view['link[rel="shortcut icon"]'];
            if ($favicon->length) {
                $favicon->attr('href', $setting->favicon);
            } else {
                $view->element('link')->rel('shortcut icon')->href($setting->favicon)->render();
            }
        }

        // Appicon
        if ($setting->appicon) {
            $appicon = $view['link[rel="apple-touch-icon"]'];
            if ($appicon->length) {
                $appicon->attr('href', $setting->appicon);
            } else {
                $view->element('link')->rel('apple-touch-icon')->href($setting->appicon)->render();
            }
        }

        // Themes
        if ($setting->theme) {
            foreach ($setting->theme as $theme) {
                $view->element('link')->rel('stylesheet')->href($theme)->render();
            }
        }

        // Title
        $view->title($setting->title);

        // Default
        if ('default') {
            $items = array();
            $selector = '[data-unit="default"]';
            $slideshow = $view["$selector .slideshow"];
            $scrollable = $slideshow['.items'];
            $pager = "$selector .slideshow .pager .list";
            $size = $scrollable->data('size') ?: $scrollable['.item']->length;

            // Cache
            if (($cached = $cache->get($cacheKey = 'default-slideshow'))
                && (time() - intval($cached['time'])) <= (15 * 24 * 60 * 60)) {
                $cached['scrollable'] = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $cached['scrollable']);
                $scrollable->html($cached['scrollable']);
                $view[$pager]->html($cached['pager']);
            }
            // Create
            else
            {
                // Excludes
                foreach ($scrollable['.item[data-exclude]'] as $item) {
                    $item = $view->object($item);
                    $excluded = $item->data('exclude');
                    $excludes = Regex::split('/\s*,\s*/', $excluded);
                    if (in_array($setting->namespace, $excludes)) {
                        $id = $item->data('id');
                        $scrollable['.item[data-id="' . $id . '"]']->remove();
                    }
                }

                // Priority
                $defaulted = $scrollable->data("priority");
                $defaulted = $scrollable->data("priority-{$setting->namespace}") ?: $defaulted;
                if (!empty($defaulted)) {
                    $defaults = Regex::split('/\s*,\s*/', $defaulted);
                    for ($i = 0, shuffle($defaults), $max = intval($size / 2); $i < $max; $i++) {
                        if (isset($defaults[$i]) && ($id = $defaults[$i])) {
                            $items[$id] = $scrollable['.item[data-id="' . $id . '"]'];
                        }
                    }
                }

                // Ordering
                $maps = $scrollable['.item']->get();
                foreach (shuffle($maps) ? $maps : array() as $item) {
                    $item = $view->object($item);
                    $id = $item->data('id');
                    if (!isset($items[$id])) {
                        $items[$id] = $item;
                    }
                }

                // Actived
                // TODO: 改成快取後 應該不需要再使用此邏輯
                if (($actived = $this->request->param->to)
                    && isset($items[$actived])) {
                    $item = $items[$actived];
                    unset($items[$actived]);
                    Arrays::insert($items, $item, $actived);
                }

                // Wasted
                $wasted = array_slice($items, $size);
                foreach ($wasted as $item) {
                    $item = $view->object($item);
                    $id = $item->data('id');
                    $scrollable['.item[data-id="' . $id . '"]']->remove();
                }

                // Saved
                $saved = array_slice($items, 0, $size);
                foreach ($list = $this->listing(array(
                    'dataset' => AbstractDataset::factory($saved),
                    'container' => $pager,
                )) as $item) {
                    $item = $view->object($item);
                    $id = $item->data('id');
                    $title = $item['.headline']->text();
                    $link  = "../../default/to/{$id}";
                    $list['a']->text($title);
                    $list['a']->attr('href', $link);
                    $list['a']->data('id', $id);
                }

                // Cache
                $cache->$cacheKey = array(
                    'scrollable' => $scrollable->html(),
                    'pager' => $list->getGathered(),
                    'time' => time()
                );
            }
        }

        // Inside
        if (!$isDefault) {
            $heading = $page["> .heading"]->text();
            $view->title($heading, $view::TITLE_PREPEND);

            switch ($unit) {
                case 'about':
                    $summary = Strings::summary($page->find('.info .text')->text(), self::META_DESCRIPTION_LENGTH);
                    $view->element('meta')->name('description', $summary)->render();
                    break;
                case 'team':
                    $summary = Strings::summary($page->find('.caption .chinese')->text(), self::META_DESCRIPTION_LENGTH);
                    $view->element('meta')->name('description', $summary)->render();

                    if ($value = $this->request->param->name) {
                        $item = $page->find('.person .item[data-id="' . $value . '"]');
                        $title = $item->find('.name')->text() . ' / ' . $item->find('.title .chinese')->text();
                        $summary = Strings::summary($item->find('.summary')->text(), self::META_DESCRIPTION_LENGTH);
                        $view->element('meta')->name('description', $summary)->render();
                        $view->title($title, $view::TITLE_PREPEND);
                    }
                    break;
                case 'environment':
                    $summary = Strings::summary($page->find('.summary')->text(), self::META_DESCRIPTION_LENGTH);
                    $view->element('meta')->name('description', $summary)->render();

                    if ($value = $this->request->param->photo) {
                        $title = $page->find('.lightbox .plane[data-id="' . $value . '"] img')->attr('alt');
                        $view->title($title, $view::TITLE_PREPEND);
                    }
                    break;
                case 'contact':
                    if ($value = $this->request->param->us) {
                        $title = $page->find('.box[data-id="' . $value . '"] > .heading')->text();
                        $view->title($title, $view::TITLE_PREPEND);
                    }
                    break;
            }
        }

        // First of inside
        switch ($unit) {
            case 'default':
                if (isset($this->request->param->to)) {
                    $wrap = $page['.slideshow .items'];
                    $inside = $wrap[".item[data-id='{$this->request->param->to}']"];
                    if ($inside->length) {
                        $indent = PHP_EOL . str_repeat("\x20", 16);
                        $html = $indent .  $inside->htmlOuter();
                        $wrap->prepend($html);
                        $inside->remove();
                    }
                }
                break;
            case 'team':
                if (isset($this->request->param->name)) {
                    $wrap = $page['#team-person .items'];
                    $inside = $wrap[".item[data-id='{$this->request->param->name}']"];
                    if ($inside->length) {
                        $indent = PHP_EOL . str_repeat("\x20", 14);
                        $html = $indent .  $inside->htmlOuter();
                        $wrap->prepend($html);
                        $inside->remove();
                    }
                }
                break;
            case 'environment':
                if (isset($this->request->param->photo)) {
                    $wrap = $page['.lightbox .planes'];
                    $inside = $wrap[".plane[data-id='{$this->request->param->photo}']"];
                    if ($inside->length) {
                        $indent = PHP_EOL . str_repeat("\x20", 14);
                        $html = $indent .  $inside->htmlOuter();
                        $wrap->prepend($html);
                        $inside->remove();
                    }
                }
                break;
            case 'contact':
                if (isset($this->request->param->us)) {
                    $indent = PHP_EOL . str_repeat("\x20", 10);
                    $inside = $page[".box[data-id='{$this->request->param->us}']"];
                    if ($inside->length) {
                        $html = $indent .  $inside->htmlOuter();
                        $page['.navigation']->after($html);
                        $inside->remove();
                    }
                }
                break;
        }

        // Assign
        $view['#header .logo .title'] = $setting->title;
        $view['#footer .copyright .year'] = date('Y');
        $view['#footer a.google']->attr('href', $setting->google->url);
        $view['#footer a.facebook']->attr('href', $setting->facebook->url);
        $view->assign('id', $setting->id);
        $view->assign('name', $setting->name);
        $view->assign('nickname', $setting->nickname);
        $view->assign('company', $setting->company);
        $view->assign('email', str_replace('@', '(at)', $setting->contact->from));
        $view->insert('namespace', $setting->namespace);
        $view->insert('facebook', $setting->facebook->id);
        $view->insert('google-analytics', $setting->google->analytics);

        // First of unit
        if (!$isDefault) {
            $indent = PHP_EOL . str_repeat("\x20", 6);
            $html = $indent .  $page->htmlOuter();
            $main->prepend($html);
            $page->remove();
        }
    }

    /**
     * 渲染作品介紹
     *
     * return void
     */
    protected function renderWork()
    {
        if ($this->request->param->unit === 'works'
            && $this->request->param->detail) {

            // Not found
            if (!($work = $this->getWorks($this->request->param->detail))) {
                $uri = HttpUri::factory()->query(null, 'detail');
                $this->response->redirect($uri)->stop();
            }

            $view = $this->site->view;
            $workspace = $view['#works .detail'];
            $workspace->data('id', $work->id);

            if ($work->color) {
                foreach (($work->color) as $name => $color) {
                    $workspace->data("color-$name", $color);
                }
            }

            $workspace['.header .demo']->attr('href', $work->link);
            $workspace['.header .category .type'] = (string) $work->category;
            $workspace['.header .name'] = (string) $work->name;
            $workspace['.description'] = (string) $work->description;
            $workspace['.view img']->attr('src', $work->detail);
            $workspace['.view img']->attr('alt', $work->name);

            if (isset($work->description)) {
                $description = Strings::summary($work->description, self::META_DESCRIPTION_LENGTH);
                $view->element('meta')->name('description', $description)->render();
            }

            if (isset($work->comments)) {
                $element = '<em class="comments" itemprop="keywords">' . $work->comments . '</em>';
                $workspace['.description']->append($element);
            }

            if ($properties = $this->getProperties($work->id)) {
                $view->element('meta')->name('keywords', $properties)->render();
                $element = '<em class="properties" itemprop="keywords">' . $properties . '</em>';
                $workspace['.description']->append($element);
            }

            $view->title($work->name, $view::TITLE_PREPEND);

            $nameWithSpaceQuoted = str_replace(' ', '+', $work->name);
            $isVirtualUri = isset($this->request->param[$nameWithSpaceQuoted]);
            if ($isVirtualUri && !empty($properties)) {
                $length = self::META_DESCRIPTION_LENGTH - strlen($properties);
                $description = Strings::summary($work->description, $length);
                $description = $description . $properties;
                $view->find('meta[name="description"]')->attr('content', $description);
                $view->title($properties, $view::TITLE_APPEND);
            }
        }
    }

    /**
     * 渲染作品清單
     *
     * return void
     */
    protected function renderWorks()
    {
        $config = $this->getConfig();
        $cache = $this->getCacheable($config->namespace);
        $gathered = $cache->get($cacheKey = 'works-render');
        $container = '#works .list .items';

        if (!isset($gathered)) {
            $works = $this->getWorks();
            $dataset = AbstractDataset::factory($works);
            $list = $this->listing(array(
                'dataset' => $dataset,
                'container' => $container,
                'render' => false,
            ));

            foreach ($list as $row) {
                $row   = new Hashtable($row);
                $id    = $row->id;
                $path  = "{$config->setting->works->path}/$id";
                $thumb = "$path/thumb.jpg";
                $logo  = "$path/logo.png";
                $name  = strval($row->name);
                $link  = "../../works/detail/{$id}";

                $list['.heading']->text($name);
                $list['.thumb']->attr('src', $thumb);
                $list['.thumb']->attr('alt', $name);
                $list['.logo']->attr('src', $logo);
                $list['.logo']->attr('alt', $name);
                $list['.link']->attr('href', $link);
                $list['.link']->data('id', $id);

                $uri = $link . '/' . urlencode($name) . '/' . $this->getPropertiesForUrl($id);
                $list['.more']->attr('href', $uri)->text("$name");
            }

            $gathered = $list->getGathered();
            $cache->$cacheKey = $gathered;
        }

        $this->site->view[$container]->html($gathered);
    }

    /**
     * 傳回作品清單
     *
     * @param string $id
     * return Config
     * @throws Exception\UnexpectedValueException
     */
    protected function getWorks($id = null)
    {
        $config = $this->getConfig();
        $cache = $this->getCacheable($config->namespace);
        $works = $cache->get($cacheKey = 'works-raw');

        if (!isset($works)) {
            $works = array();
            if ($config->work instanceof Config) {

                foreach ($config->work as $work) {

                    if (empty($work->id)) {
                        throw new Exception\UnexpectedValueException("必須設定作品編號");
                    }

                    if (isset($works[$work->id])) {
                        throw new Exception\UnexpectedValueException("作品編號重複: {$work->id}");
                    }

                    if (isset($work->enabled) && !Variable::isTrue($work->enabled)) {
                        continue;
                    }

                    $works[$work->id] = $work->toArray();
                }
            }

            $cache->$cacheKey = $works;
        }

        if (func_num_args() >= 1) {
            $work = null;
            if (isset($works[$id])) {
                $work = $works[$id];
                $work = new Hashtable($work);
                $work->path = "{$config->setting->works->path}/{$work->id}";
                $work->detail = "{$work->path}/detail.jpg"  ;

                $description = $this->getPathInfo()->getBase() . "/{$work->path}/description.txt";
                $description = File::isExists($description) ? File::read($description) : null;

                if (!empty($description)) {
                    if ($matched = Regex::match("#\[comments\](.*)\[/comments\]#is", $description)) {
                        $description = str_replace($matched[0], "", $description);
                        $work->comments = Strings::html2Text(trim($matched[1]), true);
                    }
                }

                $work->description = Strings::html2Text(trim($description), true);
            }
            return $work;
        }

        // echo count($works);
        return $works;
    }

    /**
     * 預載圖片處理
     *
     * return void
     */
    protected function preloadImage()
    {
        $view = $this->site->view;
        $unit = $this->request->param->unit;
        $blank = $view['#loading .blank']->attr('src');
        $view["#main > .page:not(#{$unit}) img[src]:not(.without)"]->each(function($index, $image) use ($blank) {
            $image = $this->site->view->object($image);
            $url = $image->attr('src');
            $image->data('src', $url);
            $blank && $image->attr('src', $blank);
            $blank || $image->removeAttr('src');
        });
    }

    /**
     * 傳回 SEO 屬性 URL
     *
     * @param string $id
     * @return string
     */
    protected function getPropertiesForUrl($id)
    {
        $result = '';
        if ($properties = Regex::split('/\s*,\s*/', $this->getProperties($id))) {
            foreach ($properties as &$property) {
                $property = urlencode($property);
            }
            $result = implode('/', $properties);
        }
        return $result;
    }

    /**
     * 傳回 SEO 屬性集合
     *
     * @param string $id
     * @return string
     */
    protected function getProperties($id, $count = 5)
    {
        $namespace = 'properties';
        $cacheKey = "$namespace-$id";
        $cache = $this->getCacheable($namespace);

        if (!isset($cache->$cacheKey)) {
            $config = $this->getConfig();
            $setting = $config->setting;
            $properties = Strings::summary($setting->properties);
            $properties = Regex::split('/\s*,\s*/', $properties);
            $properties = array_unique($properties);

            // sort($properties);
            // exit(implode(', ', $properties));

            if ($properties) {
                shuffle($properties);
                $list = array_rand($properties, $count);
                foreach ($list as &$key) {
                    $key = $properties[$key];
                }

                $cache->$cacheKey = implode(', ', $list);
            }
        }

        return $cache->$cacheKey;
    }

    /**
     * 載入設定檔
     *
     * @param string $filename
     * @param boolean $fresh
     * @return Config
     * @throws Exception\RuntimeException Exception\UnexpectedValueException
     */
    protected function getConfig($filename = self::CONFIG, $fresh = false)
    {
        $namespace = substr(md5($filename), 0, 12);
        $path = $this->getPath($filename);
        $cache = $this->getCacheable($namespace);
        $md5 = $this->getMd5($filename);
        $isModified = $cache->md5 != $md5;
        ($isModified || $fresh) && $cache->destroy();

        $cache->md5 = $md5;

        if ($isModified || !isset($cache->parsed)) {

            try {
                if ($parsed = Config::reader('xml')->fromFile($path)) {
                    $config = new Config((array) $parsed);

                    if ($cellection = array('theme' => $config->setting, 'work' => $config)) {
                        foreach ($cellection as $name => $wrap) {
                            if (is_string($wrap->$name)) {
                                $wrap->$name = array($wrap->$name);
                            }
                            else if ($wrap->$name instanceof Config && !isset($wrap->$name[0])) {
                                $wrap->$name[0] = $wrap->$name->toArray();
                                foreach ($wrap->$name as $k => $v) {
                                    if (is_string($k)) {
                                        unset($wrap->$name[$k]);
                                    }
                                }
                            }
                        }
                    }

                    $config->namespace = $namespace;
                    $cache->parsed = $config;
                }
            }
            catch(\Zend\Config\Exception\RuntimeException $ex) {
                throw new Exception\RuntimeException("無法解析設定檔: $path");
            }
        }

        if ($filename === self::CONFIG) {
            try {
                $hostname = Regex::replace('/^www\./', '', $_SERVER['HTTP_HOST']);
                $domain = Regex::replace('/[^a-z0-9]/', '', Arrays::getFirst(explode('.', $hostname)));
                $filename = Path::build(File::getPath($filename), "$domain.xml");

                if ($extra = $this->getConfig($filename, $isModified)) {
                    $extended = $cache->parsed->merge($extra);
                    return $extended;
                }
            }
            catch(Exception\RuntimeException $ex) {}
        }

        return $cache->parsed ?: null;
    }

    /**
     * 傳回快取物件
     *
     * @return Cache
     */
    protected function getCacheable($namespace = null)
    {
        $namespace = $namespace ? $namespace : substr(md5(__CLASS__), 0, 12);
        $workspace = "cache-$namespace";
        if (isset($this->$workspace)) return $this->$workspace;
        $this->$workspace = Cache::filesystem($namespace);
        $this->$workspace->setSerializable(true);
        return $this->$workspace;
    }

    /**
     * 傳回 MD5 金鑰
     *
     * @param string $filename
     * @return string
     */
    public function getMd5($filename)
    {
        $app = __FILE__;
        $html = $this->getPathInfo()->getHtml();
        $config = $this->getPath($filename);
        return md5(md5_file($app) . md5_file($html) . md5_file($config));
    }

    /**
     * 傳回設定檔路徑
     *
     * @param string $filename
     * @return string
     * @throws Exception\RuntimeException
     */
    protected function getPath($filename)
    {
        $basePath = Registry::getConfig()->path->resource;
        $path = Path::build($basePath, $filename);
        if (!File::isExists($path) || !File::isReadable($path)) {
            throw new Exception\RuntimeException("無法存取設定檔: $path");
        }
        return $path;
    }
}

?>