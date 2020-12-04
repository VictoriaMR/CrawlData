<?php

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * 爬虫
 */
class Robot
{
    private $aConfig;
    private $url;
    private $oClient;

    public function __construct($aConfig)
    {
    	// setting
        $this->aConfig = $aConfig;
        $this->url = 'http://fundgz.1234567.com.cn/js/';

        //实例化爬取线程
    	$this->oClient = new Client([
            'timeout' => 30,
        ]);
    }

    public function down_file()
    {
        echo __FUNCTION__.' start !!!'.PHP_EOL;
        $codeArr = Capsule::table('fundcodes')->select(['code', 'name'])->get()->toArray();
        $codeArr = array_column($codeArr, 'name', 'code');
        $year = date('Y');
        $downloadArr = [];
        foreach ($codeArr as $key => $value) {
            $sFile = sprintf('%s/%s.json', $this->aConfig['storage'], $key);
            // http://fund.eastmoney.com/pingzhongdata/001186.js?v=20160518155842
            if (!is_file($sFile)) {
                $downloadArr[] = [
                    'title' => $sFile,
                    'url' => 'http://fund.eastmoney.com/pingzhongdata/'.$key.'.js?v='.time(),
                    'file' => $sFile,
                    'name' => $value,
                    'code' => $key,
                ];
            }
        }
        echo 'download file '.count($downloadArr).PHP_EOL;
        $this->getFile($downloadArr, 100, 10);
        $downloadArr = [];
        Capsule::table('funds_logger')->truncate();
        foreach ($codeArr as $key => $value) {
            $sFile = sprintf('%s/%s.json', $this->aConfig['storage'], $key);
            if (is_file($sFile)) {
                $tempArr = json_decode(file_get_contents($sFile), true);
                if (empty($tempArr)) continue;
                foreach ($tempArr as $k => $v) {
                    $insert[] = [
                        'fundcode' => $key,
                        'name' => $value,
                        'gsz' => $v['y'],
                        'gszzl' => $v['equityReturn'],
                        'gztime' => date('Y-m-d', substr($v['x'], 0, -3)),
                    ];
                }
                if (count($insert) > 6000) {
                    Capsule::table('funds_logger')->insert($insert);
                    $insert = [];
                }
            }
        }
        if (!empty($insert)) {
            Capsule::table('funds_logger')->insert($insert);
        }
        $insert = [];
        return false;
    }

    protected function getFile($downloadArr, $request = 1, $time = 1)
    {
        if (empty($downloadArr)) {
            return false;
        }
        //塞入异步进程
        $timecount = 0;
        $timecount = 0;
        while ($timecount <= $time) {
            if (empty($downloadArr)) {
                return true;
            }
            $requests = function ($arr) {
                foreach ($arr as $value) {
                    yield new Request('GET', $value['url']);
                }
            };

            $pool = new Pool($this->oClient, $requests($downloadArr), [
                'concurrency' => $request,
                'fulfilled' => function ($response, $index) use ($downloadArr) {
                    if (200 == $response->getStatusCode()) {
                        $response = $response->getBody()->getContents();
                        if (!empty($response)) {
                            $response = substr($response, strpos($response, 'Data_netWorthTrend = '));
                            $response = substr($response, 0, strpos($response, ']')).']';
                            existsOrCreate($downloadArr[$index]['file']);
                            file_put_contents($downloadArr[$index]['file'], trim($response, 'Data_netWorthTrend = '));
                            echo $downloadArr[$index]['name'].' downloaded !!'.PHP_EOL;
                        }
                    }
                },
                'rejected' => function ($reason, $index) use ($downloadArr) {
                },
            ]);
            $promise = $pool->promise();
            $promise->wait();
            if ($time > 1) {
                $tempArr = [];
                foreach ($downloadArr as $key => $value) {
                    if (empty($this->insert[$value['code']])) {
                        $tempArr[] = $value;
                    }
                }
                $downloadArr = $tempArr;
            } else {
                break;
            }
            $timecount ++;
            echo '尝试次数 '.$timecount.PHP_EOL;
        }
        foreach ($downloadArr as $key => $value) {
            echo sprintf('%s %s 无返回值', $value['name'], $value['code']).PHP_EOL;
        }
        return true;
    }
}