<?php

namespace Sewii\Net\Service\Google;

use Sewii\Uri;
use Sewii\Http;
use Sewii\Data;

/**
 * 網站分析報表類別
 *
 * @version v 1.5.0 2011/08/13 00:00
 * @author Sewii <sewii@sewii.com.tw>
 * @copyright Sewii Design Studio. (sewii.com.tw)
 */
class Analytics
{
    /**
     * 帳戶授權碼
     * @var string
     */
    private $_authToken;
    
    /**
     * 設定檔內容
     * @var array
     */
    public $configs;

    /**
     * 除錯方法
     *
     * @param mixed $add 新增除錯訊息
     * @return mixed
     *
     */
    private $_debugs = array();
    public function debug($add = null)
    {
        if ($add) {
            array_push($this->_debugs, $add);
            return;
        }

        return $this->_debugs;
    }

    /**
     * 驗證 google analytics 登入
     *
     * @param mixed $email 帳號
     * @param mixed $password 密碼
     * @return boolean 驗證失敗時將直接拋例外
     * @see http://code.google.com/intl/zh-TW/apis/accounts/docs/AuthForInstalledApps.html
     */
    public function login($email, $password)
    {
        if (!$email || !$password) return false;
        $ClientUrl = new Http\ClientUrl('https://www.google.com/accounts/ClientLogin');
        $ClientUrl->option(
            array(
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => array(
                    'accountType' => 'HOSTED_OR_GOOGLE',
                    'service' => 'analytics',
                    'Email' => $email,
                    'Passwd' => $password
                )
            )
        );

        if ($result = $ClientUrl->execute()) {
            if ($detail = $ClientUrl->detail()) {
                if ($detail['http_code'] == 200) 
                {
                    //取得授權碼
                    preg_match('/Auth=(.*)/', $result, $matches);
                    if (isset($matches[1])) {
                        $this->_authToken = $matches[1];
                        return true;
                    }
                }
            }
        }
        $this->debug(array('result' => $result, 'detail' => $detail));
        throw new Exception('Google Analytics 帳戶驗證失敗');
    }

    /**
     * 載入 Google Data API 並返回結果 
     *
     * @param string $url
     * @param boolean $json - 是否以 json 傳回
     * @return mixed|xml|json
     * @see http://code.google.com/intl/zh-TW/apis/gdata/docs/developers-guide.html
     *
     */
    public function call($url, $json = true)
    {
        if (!$this->_authToken) return false;

        //附加 json 格式參數
        if ($json) {
            $Uri = new Uri\Uri($url);
            $Uri->addQuery('alt=json');
            $url = $Uri->getUrl();
        }

        $ClientUrl = new Http\ClientUrl($url);
        $ClientUrl->option(array(
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => array(
                'Authorization: GoogleLogin auth=' . $this->_authToken
            )
        ));

        if ($result = $ClientUrl->execute()) {
            if ($detail = $ClientUrl->detail()) {
                switch($detail['http_code']) {
                    case 200:
                        if ($json) return json_decode($result);

                        //剖析 XML 後傳回
                        $dom = new DOMDocument();
                        $dom->loadXML($result);
                        return clone $dom;
                }
            }
        }
        $this->debug(array('result' => $result, 'detail' => $detail));
        throw new Exception('載入 Google Data API 時發生不明錯誤');
    }

    /**
     * 傳回設定檔
     *
     * @param mixed $email 帳號
     * @param mixed $password 密碼
     * @return array
     *
     */
    public function getConfigs($email = null, $password = null) 
    {
        if (!$this->_authToken && ($email && $password))
            $this->login($email, $password);

        if (!$this->_authToken) return false;
        if ($json = $this->call('https://www.google.com/analytics/feeds/accounts/default')) {
            $json = Data\Object::toArray($json);
            $entries = (array)$json['feed']['entry'];
            $configs = array();
            foreach($entries as $entry) {
                $title = $entry['title']['$t'];
                $configs[$title]['title'] = $title;
                $tableId = $entry['dxp$tableId']['$t'];
                $configs[$title]['tableId'] = $tableId;
                $properties = (array)$entry['dxp$property'];
                foreach ($properties as $property) {
                    $propertyKey = preg_replace('/^ga\:/', '', $property['name']);
                    $propertyVal = $property['value'];
                    $configs[$title][$propertyKey] = $propertyVal;
                }
            }
            $this->configs = $configs;
            return $configs;
        }
        throw new Exception('載入 Google Analytics 設定檔時發生不明錯誤');
    }

    /**
     * 傳回分析資料
     *
     * @param string $tableId 設定檔 ID
     * @param string $metrics 資料類型(指標)，使用逗號區隔，每次最多10個。
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @param array $options 選項
     * @return array
     * 
     * @see http://code.google.com/intl/zh-TW/apis/analytics/docs/gdata/gdataReferenceDataFeed.html
     * @see http://code.google.com/intl/zh-TW/apis/analytics/docs/gdata/gdataReferenceDimensionsMetrics.html
     *
     */
    public function getData($tableId, $metrics, $startDate = null, $endDate = null, array $options = array()) 
    {
        if (!$this->_authToken || !$tableId) return false;

        //日期格式化
        if (!$startDate) $startDate = 'month';
        switch ($startDate) {
            case 'yesterday':
                $startDate = date('Y-m-d', strtotime('yesterday'));
                $endDate = date('Y-m-d', strtotime('yesterday'));
                break;
            case 'week':
                $startDate = date('Y-m-d', strtotime('1 week ago'));
                $endDate = date('Y-m-d', strtotime('yesterday'));
                break;
            case 'month':
                $startDate = date('Y-m-d', strtotime('1 month ago'));
                $endDate = date('Y-m-d', strtotime('yesterday'));
                break;
            case 'year':
                $startDate = date('Y-m-d', strtotime('1 year ago'));
                $endDate = date('Y-m-d', strtotime('yesterday'));
                break;
        }

        //$startDate = '2010-03-02';
        //$endDate = '2010-04-02';

        //url
        if (!preg_match('/^ga/', $tableId)) $tableId = 'ga:' . $tableId;
        $url = 'https://www.google.com/analytics/feeds/data?ids=' . $tableId . '&metrics=' . $metrics . '&start-date=' . $startDate . '&end-date=' . $endDate;
        $Uri = new Uri\Uri($url);
        $Uri->addQuery(http_build_query($options));

        //載入
        if ($json = $this->call($Uri->getUrl())) {
            $json = Data\Object::toArray($json);
            $entries = (array)$json['feed']['entry'];

            $data = array();

            //詳細資料
            $data['details'] = array(
                'updated' => date('Y-m-d H:i:s', strtotime($json['feed']['updated']['$t'])),
                'startDate' => $json['feed']['dxp$startDate']['$t'],
                'endDate' => $json['feed']['dxp$endDate']['$t'],
                'totalResults' => $json['feed']['openSearch$totalResults']['$t']
            );

            //資料合計
            $aggregates = (array)$json['feed']['dxp$aggregates'];
            foreach($aggregates as $type => $aggregate) {
                switch($type) {
                    case 'dxp$metric':
                        foreach ($aggregate as $item) {
                            $itemKey = preg_replace('/^ga\:/', '', $item['name']);
                            $itemVal = $item['value'];
                            $data['details']['aggregate'][$itemKey] = $itemVal;
                        }
                        break;
                }
            }

            //設定檔內容
            foreach($this->configs as $name => $config) {
                if (preg_grep('/' . preg_quote($tableId, '/') . '/is', $config)) {
                    foreach ($config as $configKey => $configVal) {
                        $data['details']['config'][$configKey] = $configVal;
                    }
                    break;
                }
            }

            //資料結果
            foreach($entries as $index => $entry) {
            
                $dimensions = (array)$entry['dxp$dimension'];
                foreach($dimensions as $dimension) {
                    $dimensionKey = preg_replace('/^ga\:/', '', $dimension['name']);
                    $dimensionVal = $dimension['value'];
                    $data['entries'][$index]['dimensions'][$dimensionKey] = $dimensionVal;
                }

                $metrics = (array)$entry['dxp$metric'];
                foreach ($metrics as $metric) {
                    $metricKey = preg_replace('/^ga\:/', '', $metric['name']);
                    $metricVal = $metric['value'];
                    $data['entries'][$index]['metrics'][$metricKey] = $metricVal;
                }
            }
            return $data;
        }
    }

    /**
     * 傳回網站分析摘要
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getSummary($tableId, $startDate = null, $endDate = null)
    {
        //預設值
        $summary = array(
            'visits'            => 0,       //造訪數
            'newVisits'         => 0,       //新造訪
            'newVisitsRate'     => '0.00%', //新造訪率
            'oldVisits'         => 0,       //舊造訪
            'oldVisitsRate'     => '0.00%', //回訪率
            'visitors'          => 0,       //絕對不重複訪客
            'pageviews'         => 0,       //瀏覽量
            'uniquePageviews'   => 0,       //不重複瀏覽量
            'pagesPerVisit'     => '0.00',  //平均瀏覽量(單次造訪頁數)
            'bounces'           => 0,       //跳出數
            'bouncesRate'       => 0,       //跳出率
            'timeOnSite'        => '0.0',   //網站停留時間
            'timeOnSiteAverage' => '0.0',   //平均網站停留時間
            'exits'             => 0,       //離開數
            'exitsRate'         => '0.00%', //離開率
            'entrances'         => 0,       //進入數
        );

        //第一次查詢
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces,ga:visitors',
            $startDate = $startDate, 
            $endDate = $endDate
        );

        $report = $data;
        $report['formatted'] = &$summary;

        if ($data['entries']) 
        {
            $data = $data['entries'][0]['metrics'];
            $summary['visits'] = $data['visits'];
            $summary['newVisits'] = $data['newVisits'];
            $summary['oldVisits'] = $data['visits'] - $data['newVisits'];
            $summary['pageviews'] = $data['pageviews'];
            $summary['timeOnSite'] = $data['timeOnSite'];
            $summary['bounces'] = $data['bounces'];
            $summary['visitors'] = $data['visitors'];

            //避免除數為零
            if ($data['visits'] >= 1) 
            {
                $summary['newVisitsRate']     = sprintf('%0.2f', ($data['newVisits'] / $data['visits'] * 100)) . '%';
                $summary['oldVisitsRate']     = sprintf('%0.2f', (($data['visits'] - $data['newVisits']) / $data['visits'] * 100)) . '%';
                $summary['pagesPerVisit']     = sprintf('%0.2f', ($data['pageviews'] / $data['visits']));
                $summary['bouncesRate']       = sprintf('%0.2f', ($data['bounces'] / $data['visits'] * 100)) . '%';
                $summary['timeOnSiteAverage'] = sprintf('%0.1f', ($data['timeOnSite'] / $data['visits']));
            }
        }
        
        //第二次查詢
        $data2 = $this->getData(
            $tableId, 
            $metrics = 'ga:uniquePageviews,ga:exits,ga:entrances',
            $startDate = $startDate, 
            $endDate = $endDate
        );

        if ($data2['entries']) 
        {
            $data2 = $data2['entries'][0]['metrics'];
            $summary['uniquePageviews'] = $data2['uniquePageviews'];
            $summary['exits'] = $data2['exits'];
            $summary['entrances'] = $data2['entrances'];
            
            //避免除數為零
            if ($data['pageviews'])
                $summary['exitsRate'] = sprintf('%0.2f', ($data2['exits'] / $data['pageviews'] * 100)) . '%';
        }

        //讓摘要與其他報表格式相同
        if ($summary) $summary = array($summary);
        return $report;
    }

    /**
     * 格式化報表資料
     *
     * @param array $data
     * @return array
     *
     */
    private function _formatted(array $data)
    {
        $formatted = array();
        $data['formatted'] = &$formatted;
        if ($data['entries']) {
            $aggregate = &$data['details']['aggregate'];
            foreach($data['entries'] as $entry) 
            {
                $subject = implode(' / ', (array)$entry['dimensions']);
                $formatted[$subject] = $entry['metrics'];
                $metrics = &$formatted[$subject];

                //造訪率
                if (isset($metrics['visits']) && $aggregate['visits'] >= 1) {
                    $metrics['visitsRate'] = sprintf('%0.2f', ($metrics['visits'] / $aggregate['visits'] * 100)) . '%';
                } else if (isset($metrics['visits'])) $metrics['visitsRate'] = '0.00%';

                //新造訪率
                if (isset($metrics['newVisits']) && $aggregate['visits'] >= 1) {
                    $metrics['newVisitsRate'] = sprintf('%0.2f', ($metrics['newVisits'] / $aggregate['visits'] * 100)) . '%';
                } else if (isset($metrics['newVisits'])) $metrics['newVisitsRate'] = '0.00%';

                //瀏覽率
                if (isset($metrics['pageviews']) && $aggregate['pageviews'] >= 1) {
                    $metrics['pageviewsRate'] = sprintf('%0.2f', ($metrics['pageviews'] / $aggregate['pageviews'] * 100)) . '%';
                } else if (isset($metrics['pageviews'])) $metrics['pageviewsRate'] = '0.00%';

                //平均瀏覽量
                if (isset($metrics['pageviews']) && $metrics['visits'] >= 1) {
                    $metrics['pagesPerVisit'] = sprintf('%0.2f', ($metrics['pageviews'] / $metrics['visits']));
                } else if (isset($metrics['pageviews'])) $metrics['pagesPerVisit'] = '0.00';

                //網站平均停留時間
                if (isset($metrics['timeOnSite']) && $metrics['visits'] >= 1)
                    $metrics['timeOnSiteAverage'] = sprintf('%0.1f', ($metrics['timeOnSite'] / $metrics['visits']));
                else if (isset($metrics['timeOnSite'])) $metrics['timeOnSiteAverage'] = '0.0';

                //跳出率
                if (isset($metrics['bounces']) && $metrics['visits'] >= 1) {
                    $metrics['bouncesRate'] = sprintf('%0.2f', ($metrics['bounces'] / $metrics['visits'] * 100)) . '%';
                } else if (isset($metrics['bounces'])) $metrics['bouncesRate'] = '0.00%';

                //離開率
                if (isset($metrics['exits']) && $metrics['pageviews'] >= 1) {
                    $metrics['exitsRate'] = sprintf('%0.2f', ($metrics['exits'] / $metrics['pageviews'] * 100)) . '%';
                } else if (isset($metrics['exits'])) $metrics['exitsRate'] = '0.00%';
            }

            //合計新造訪率
            if (isset($aggregate['newVisits']) && $aggregate['visits'] >= 1)
                $aggregate['newVisitsRate'] = sprintf('%0.2f', ($aggregate['newVisits'] / $aggregate['visits'] * 100)) . '%';
            else if (isset($aggregate['newVisits'])) $aggregate['newVisitsRate'] = '0.00%';

            //合計平均瀏覽量
            if (isset($aggregate['pageviews']) && $aggregate['visits'] >= 1)
                $aggregate['pagesPerVisit'] = sprintf('%0.2f', ($aggregate['pageviews'] / $aggregate['visits']));
            else if (isset($aggregate['pageviews'])) $aggregate['pagesPerVisit'] = '0.00';

            //合計跳出率
            if (isset($aggregate['bounces']) && $aggregate['visits'] >= 1)
                $aggregate['bouncesRate'] = sprintf('%0.2f', ($aggregate['bounces'] / $aggregate['visits'] * 100)) . '%';
            else if (isset($aggregate['bounces'])) $aggregate['bouncesRate'] = '0.00%';
            
            //合計離開率
            if (isset($aggregate['exits']) && $aggregate['pageviews'] >= 1)
                $aggregate['exitsRate'] = sprintf('%0.2f', ($aggregate['exits'] / $aggregate['pageviews'] * 100)) . '%';
            else if (isset($aggregate['exits'])) $aggregate['exitsRate'] = '0.00%';
        }
        return $data;
    }

     //**************************************************************************************
     // 訪客趨勢
     //**************************************************************************************

    /**
     * 傳回日期範圍的訪客趨勢分析報表
     * (用於計算訪問者忠誠度)
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getDates($tableId, $startDate = null, $endDate = null)
    {
        //第一次查詢
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:visitors,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:date'
            )
        );
        
        //第二次查詢 (追加不重覆瀏覽量)
        $data2 = $this->getData(
            $tableId, 
            $metrics = 'ga:uniquePageviews',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:date'
            )
        );

        //合併查詢
        if ($data && $data2) {
            $data['details']['aggregate'] += $data2['details']['aggregate'];
            foreach($data['entries'] as $index => &$entry) {
                $entry['metrics'] += $data2['entries'][$index]['metrics'];
            }
        }
        
        return $this->_formatted($data);
    }

     //**************************************************************************************
     // 訪客分佈
     //**************************************************************************************

    /**
     * 傳回造訪者所在洲分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getContinent($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:Continent'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪者所在洲的次級區域分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getSubContinent($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:subContinent'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪者所在國家分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getCountry($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:country'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪者所在國家之區域分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getRegion($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:region'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪者所在國家之城市分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getCity($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:city,ga:country'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪者瀏覽器語言分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getLanguage($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:language'
            )
        );
        return $this->_formatted($data);
    }

     //**************************************************************************************
     // 訪客忠誠度
     //**************************************************************************************

    /**
     * 傳回造訪者類型分析報表
     * 
     * 1. New Visitor 新訪客
     * 2. Returning Visitor 回訪客
     * 
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getVisitorType($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:visitorType'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪次數(忠誠度)分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getVisitCount($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:visitCount'
            )
        );

        if ($data['entries']) 
        {
            sort($data['entries']);
            $result = array();
            $groups = array();

            foreach($data['entries'] as $index => &$entry) 
            {
                //非連續天數
                if (($lastDay && $entry['dimensions']['visitCount'] > $lastDay + 1) ||
                    $entry['dimensions']['visitCount'] > 8) 
                {
                    //稍後以天數範圍計算
                    $groups[] = $entry;
                }
                //連續天數
                else
                {                        
                    $lastDay = $entry['dimensions']['visitCount'];
                    $entry['dimensions']['visitCount'] .= ' 次';
                    $entry['metrics']['value'] = $entry['metrics']['visits'];
                    array_push($result, $entry);
                }
            }

            //天數範圍的處理
            if ($groups) 
            {
                $ranges = array(15, 25, 50, 100, 200, 400, 800, 1600, 3200, 6400, 12800, 25600, 51200, 102400);

                //計算起始開始天數範圍
                $groupStart = $groups[0]['dimensions']['visitCount'];
                if ($groupStart > $ranges[0]) {
                    $finalGroupStart = $groupStart;
                    foreach($ranges as $index => $range) {
                        if ($groupStart > $range) {
                            $finalGroupStart = $range;
                        }
                    }
                    $groupStart = $finalGroupStart;
                }
                
                //計算起始結束天數範圍
                $groupEnd = null;
                foreach($ranges as $range) {
                    if ($groupStart < $range) {
                        $theEnd = $range - 1;
                        if ($theEnd > $groupStart) {
                            $groupEnd = $theEnd;
                            break;
                        }
                    }
                }

                //群組統計
                $groupResult = array();
                foreach($groups as $group) 
                {
                    //下一組
                    if ($group['dimensions']['visitCount'] > $groupEnd) {
                        $groupStart = $groupEnd + 1;
                        foreach($ranges as $range) {
                            if ($groupStart < $range) {
                                $groupEnd = $range;
                                break;
                            }
                        }
                    }

                    //範圍計算
                    if ($group['dimensions']['visitCount'] <= $groupEnd) 
                    {
                        $group['dimensions']['visitCount'] = $groupStart . '-' . $groupEnd . ' 次';
                        $group['metrics']['value'] = $group['metrics']['visits'];

                        //合併計算
                        $target = &$groupResult[$group['dimensions']['visitCount']];
                        $target['dimensions'] = $group['dimensions']['visitCount'];
                        foreach($group['metrics'] as $key => $val) 
                            $target['metrics'][$key] = floatval($target['metrics'][$key]) + floatval($val);
                    }
                }
                    
                //附加至最終結果
                if ($groupResult) {
                    foreach($groupResult as $entry) 
                        array_push($result, $entry);
                }
            }
            if ($result) $data['entries'] = $result;
        }
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪時間長度(秒)分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getVisitLength($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:visitLength'
            )
        );

        if ($data['entries']) 
        {
            sort($data['entries']);

            $result = array();
            $ranges = array('0-10', '11-30', '31-60', '61-180', '181-600', '601-1800', '1800');
            foreach($ranges as $range) 
            {
                list($min, $max) = explode('-', $range);
                foreach($data['entries'] as $index => &$entry) 
                {
                    $isMatch = false;
                    if (isset($min) && isset($max)) $isMatch = ($entry['dimensions']['visitLength'] >= $min && $entry['dimensions']['visitLength'] <= $max);
                    if (isset($min) && !isset($max)) $isMatch = ($entry['dimensions']['visitLength'] >= $min);

                    if ($isMatch) 
                    {
                        $subject = $entry['dimensions']['daysSinceLastVisit'];
                        if (isset($min) && isset($max))  $subject = $min . '-' . $max;
                        if (isset($min) && !isset($max)) $subject = $min . '+';
                        $subject .= ' 秒';

                        $result[$subject]['dimensions'] = $subject;
                        foreach($entry['metrics'] as $key => $val) {
                            $result[$subject]['metrics'][$key] = 
                                floatval($result[$subject]['metrics'][$key]) + floatval($val);
                        }
                    }
                }
            }

            if ($result) 
            {
                $data['entries'] = $result;

                //移除無記錄
                foreach($data['entries'] as $index => &$entry) {
                    if (intval($entry['metrics']['visits']) == 0)
                        unset($data['entries'][$index]);
                }
            }
        }
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪深度(頁數)分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getPageDepth($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:pageDepth'
            )
        );

        if ($data['entries']) 
        {
            sort($data['entries']);
            
            $max = 20;
            $result = array();
            foreach($data['entries'] as $index => &$entry) 
            {
                $subject = '瀏覽了 %s 頁 ';

                //單頁
                if ($entry['dimensions']['pageDepth'] < $max) 
                {
                    $subject = sprintf($subject, $entry['dimensions']['pageDepth']);
                    $result[$subject]['dimensions'] = $subject;
                    $result[$subject]['metrics'] = $entry['metrics'];
                }
                //合併
                else
                {
                    $subject = sprintf($subject, $max . '+');
                    $result[$subject]['dimensions'] = $subject;
                    foreach($entry['metrics'] as $key => $val) {
                        $result[$subject]['metrics'][$key] = 
                            floatval($result[$subject]['metrics'][$key]) + floatval($val);
                    }
                }
            }
            if ($result) $data['entries'] = $result;
        }
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪者上次訪問網站後經過的天數分析報表
     * (用於計算訪問者回訪率)
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getDaysSinceLastVisit($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:bounces,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:daysSinceLastVisit'
            )
        );

        if ($data['entries']) 
        {
            sort($data['entries']);
            $result = array();
            $groups = array();
            foreach($data['entries'] as $index => &$entry) 
            {
                //特殊統計
                if (intval($entry['dimensions']['daysSinceLastVisit']) === 0) 
                {
                    $entry['dimensions']['daysSinceLastVisit'] = '最初造訪';
                    $entry['metrics']['value'] = $entry['metrics']['newVisits'];
                    array_push($result, $entry);
                        
                    $entry['dimensions']['daysSinceLastVisit'] = '同一天';
                    $entry['metrics']['value'] = $entry['metrics']['visits'] - $entry['metrics']['newVisits'];
                    array_push($result, $entry);
                }
                else
                {
                    //非連續天數
                    if (($lastDay && $entry['dimensions']['daysSinceLastVisit'] > $lastDay + 1) ||
                        $entry['dimensions']['daysSinceLastVisit'] > 7) 
                    {
                        //稍後以天數範圍計算
                        $groups[] = $entry;
                    }
                    //連續天數
                    else 
                    {                        
                        $lastDay = $entry['dimensions']['daysSinceLastVisit'];
                        $entry['dimensions']['daysSinceLastVisit'] .= ' 天前';
                        $entry['metrics']['value'] = $entry['metrics']['visits'];
                        array_push($result, $entry);
                    }
                }
            }

            //天數範圍的處理
            if ($groups) 
            {
                $ranges = array(15, 30, 60, 120, 240, 480, 960, 1920);

                //計算起始開始天數範圍
                $groupStart = $groups[0]['dimensions']['daysSinceLastVisit'];
                if ($groupStart > $ranges[0]) {
                    $finalGroupStart = $groupStart;
                    foreach($ranges as $index => $range) {
                        if ($groupStart > $range) {
                            $finalGroupStart = $range;
                        }
                    }
                    $groupStart = $finalGroupStart;
                }
                
                //計算起始結束天數範圍
                $groupEnd = null;
                foreach($ranges as $range) {
                    if ($groupStart < $range) {
                        $theEnd = $range - 1;
                        if ($theEnd > $groupStart) {
                            $groupEnd = $theEnd;
                            break;
                        }
                    }
                }

                //群組統計
                $groupResult = array();
                foreach($groups as $group) 
                {
                    //下一組
                    if ($group['dimensions']['daysSinceLastVisit'] > $groupEnd) {
                        $groupStart = $groupEnd + 1;
                        foreach($ranges as $range) {
                            if ($groupStart < $range) {
                                $groupEnd = $range;
                                break;
                            }
                        }
                    }

                    //範圍計算
                    if ($group['dimensions']['daysSinceLastVisit'] <= $groupEnd) 
                    {
                        $group['dimensions']['daysSinceLastVisit'] = $groupStart . '-' . $groupEnd . ' 天前';
                        $group['metrics']['value'] = $group['metrics']['visits'];

                        //合併計算
                        $target = &$groupResult[$group['dimensions']['daysSinceLastVisit']];
                        $target['dimensions'] = $group['dimensions']['daysSinceLastVisit'];
                        foreach($group['metrics'] as $key => $val) 
                            $target['metrics'][$key] = floatval($target['metrics'][$key]) + floatval($val);
                    }
                }
                    
                //附加至最終結果
                if ($groupResult) {
                    foreach($groupResult as $entry) 
                        array_push($result, $entry);
                }
            }
            if ($result) $data['entries'] = $result;
        }
        return $this->_formatted($data);
    }

     //**************************************************************************************
     // 瀏覽器功能
     //**************************************************************************************

    /**
     * 傳回瀏覽器分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getBrowser($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:browser'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回瀏覽器版本分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getBrowserVersion($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:browser,ga:browserVersion'
            )
        );

        if ($data['entries']) 
        {
            $result = array();
            foreach($data['entries'] as $index => &$entry) 
            {
                $version = array();
                foreach((array) explode('.', $entry['dimensions']['browserVersion']) as $i => $v) {
                    array_push($version, $v);
                    if (preg_match('/Safari/', $entry['dimensions']['browser'])) if ($i >= 0) break;
                    if ($i >= 1) break;
                }
                $version = implode('.', $version);
                $subject = $entry['dimensions']['browser'] . ' / '. $version;

                $result[$subject]['dimensions'] = $subject;
                foreach($entry['metrics'] as $key => $val) {
                    $result[$subject]['metrics'][$key] = 
                        floatval($result[$subject]['metrics'][$key]) + floatval($val);
                }
            }
            if ($result) $data['entries'] = $result;
        }
        return $this->_formatted($data);
    }

    /**
     * 傳回 Flash 版本分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getFlashVersion($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:flashVersion',
                'max-results' => '20'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回 Java 開啟狀態分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getJavaEnabled($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:javaEnabled'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回作業系統分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getOperatingSystem($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:operatingSystem'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回作業系統版本分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getOperatingSystemVersion($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:operatingSystem,ga:operatingSystemVersion'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回螢幕色彩報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getScreenColors($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:screenColors'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回螢幕解析度分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getScreenResolution($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:screenResolution',
                'sort' => '-ga:visits',
                'max-results' => '20'
            )
        );
        return $this->_formatted($data);
    }

     //**************************************************************************************
     // 造訪者分佈
     //**************************************************************************************

    /**
     * 傳回連線速率分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getConnectionSpeed($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:connectionSpeed'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪者透過哪些主機名進入網站分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getHostname($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:hostname'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回 ISP 主機名稱分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getNetworkDomain($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:networkDomain',
                'sort' => '-ga:visits',
                'max-results' => '10'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回 ISP 供應商名稱分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getNetworkLocation($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:networkLocation'
            )
        );
        return $this->_formatted($data);
    }

     //**************************************************************************************
     // 行動裝置
     //**************************************************************************************

    /**
     * 傳回是否為行動裝置分析報表
     * 
     * 1. Yes
     * 2. No
     * 
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getIsMobile($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:isMobile'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回行動平台作業系統分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getMobileOperatingSystem($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:operatingSystem,ga:operatingSystemVersion',
                'filters' => 'ga:isMobile==Yes'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回行動通訊業者分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getMobileNetworkLocation($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:networkLocation',
                'filters' => 'ga:isMobile==Yes'
            )
        );
        return $this->_formatted($data);
    }

     //**************************************************************************************
     // 流量來源
     //**************************************************************************************

    /**
     * 傳回造訪者流量來源分析報表
     * 
     * 1. (none): 直接流量
     * 2. organic: 搜尋引擎
     * 3. referral: 推薦連結網站
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getMedium($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:medium'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪者流量來源網域分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getSource($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:source'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回引擎流量分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getSourceOrganic($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:source',
                'filters' => 'ga:source=~^\w+$'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回推薦連結網站分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getSourceReferral($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:source',
                'filters' => 'ga:source=~.*\.\w+$;ga:source!~(google|yahoo|baidu)',
                'sort' => '-ga:visits',
                'max-results' => '50'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪者從搜尋引擎透過哪些關鍵字進入網站分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getKeyword($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:keyword',
                'sort' => '-ga:visits',
                'max-results' => '50'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪者透過哪些引薦網頁路徑進入分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getReferralPath($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:timeOnSite,ga:newVisits,ga:bounces',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:referralPath'
            )
        );
        return $this->_formatted($data);
    }

     //**************************************************************************************
     // 瀏覽內容
     //**************************************************************************************

    /**
     * 傳回造訪頁面路徑分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getPagePath($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:bounces,ga:exits,ga:timeOnSite,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:pagePath',
                'sort' => '-ga:visits',
                'max-results' => '10'
            )
        );
        
        //第二次查詢 (追加不重覆瀏覽量)
        $data2 = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:uniquePageviews',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:pagePath',
                'sort' => '-ga:visits',
                'max-results' => '10'
            )
        );

        //合併查詢
        if ($data && $data2) {
            $data['details']['aggregate'] += $data2['details']['aggregate'];
            if (is_array($data['entries'])) {
                foreach($data['entries'] as $index => &$entry) {
                    $entry['metrics'] += $data2['entries'][$index]['metrics'];
                }
            }
        }
        
        return $this->_formatted($data);
    }

    /**
     * 傳回造訪頁面標題分析報表
     *
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getPageTitle($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:bounces,ga:exits,ga:timeOnSite,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:pageTitle',
                'sort' => '-ga:visits',
                'max-results' => '10'
            )
        );
        
        //第二次查詢 (追加不重覆瀏覽量)
        $data2 = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:uniquePageviews',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:pageTitle',
                'sort' => '-ga:visits',
                'max-results' => '10'
            )
        );

        //合併查詢
        if ($data && $data2) {
            $data['details']['aggregate'] += $data2['details']['aggregate'];
            if (is_array($data['entries'])) {
                foreach($data['entries'] as $index => &$entry) {
                    $entry['metrics'] += $data2['entries'][$index]['metrics'];
                }
            }
        }
        
        return $this->_formatted($data);
    }

    /**
     * 傳回主要到達網頁路徑分析報表
     * (第一次到達進入的頁面)
     * 
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getLandingPagePath($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:bounces,ga:exits,ga:timeOnSite,ga:newVisits,ga:entrances',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:landingPagePath',
                'sort' => '-ga:entrances',
                'max-results' => '10'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回主要開離網頁路徑分析報表
     * (最後一次瀏覽的頁面)
     * 
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getExitPagePath($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:visits,ga:pageviews,ga:bounces,ga:exits,ga:timeOnSite,ga:newVisits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:exitPagePath',
                'sort' => '-ga:exits',
                'max-results' => '10'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回在造訪網站上的另一網頁後訪問的網頁路徑分析報表
     * 
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getNextPagePath($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:pageviews,ga:exits',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:nextPagePath'
            )
        );
        return $this->_formatted($data);
    }

    /**
     * 傳回在造訪網站上的另一網頁前訪問的網頁路徑分析報表
     * 
     * @param string $tableId 設定檔 ID
     * @param string $startDate 資料起始時間 yyyy-mm-dd
     * @param string $endDate 資料結束時間 yyyy-mm-dd
     * @return array
     *
     */
    public function getPreviousPagePath($tableId, $startDate = null, $endDate = null)
    {
        $data = $this->getData(
            $tableId, 
            $metrics = 'ga:pageviews,ga:entrances',
            $startDate = $startDate, 
            $endDate = $endDate,
            $options = array(
                'dimensions' => 'ga:previousPagePath'
            )
        );
        return $this->_formatted($data);
    }
}

?>