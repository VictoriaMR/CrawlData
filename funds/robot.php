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
        $this->insert = [];
    }

    public function down_file()
    {
        echo __FUNCTION__.' start !!!'.PHP_EOL;
        $time = time();
        $data = Capsule::table('fundcodes')->select('code')->get()->toArray();
        $data = array_unique(array_column($data, 'code'));
        $downloadArr = [];
        foreach ($data as $value) {
            $sFile = sprintf('%s/%s/%s.json', $this->aConfig['storage'], date('Ymd'), $value);
            $downloadArr[] = [
                'title' => $sFile,
                'url' => $this->url.$value.'.js?rt='.time(),
                'file' => $sFile,
            ];
        }
        $this->getFile($downloadArr, 200);
        echo 'downloaded used '.(time() - $time).' s'.PHP_EOL;
        Capsule::table('fundrecords')->where('gztime', '>', date('Y-m-d').' 00:00')->delete();
        if (empty($this->insert)) {
            return false;
        }
        echo count($this->insert).' 条数据待入库'.PHP_EOL;
        $this->insert = array_chunk($this->insert, 2999);
        foreach ($this->insert as $key => $value) {
            Capsule::table('fundrecords')->insert($value);
        }
        echo 'insert DB USE '.(time() - $time).' s'.PHP_EOL;
        $this->insert = null;
        return false;
    }

    protected function getFile($downloadArr, $request = 1, $time = 1)
    {
        if (empty($downloadArr)) {
            return false;
        }
        //塞入异步进程
        $timecount = 0;
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
                    $response = js_json(trim(trim($response, 'jsonpgz('), '));'));
                    if (!empty($response)) {
                        $this->insert[] = $response;
                    }
                    // echo $sFile.' downloaded !!'.PHP_EOL;
                }
            },
            'rejected' => function ($reason, $index) use ($downloadArr) {
                // $sFile = $downloadArr[$index]['file'];
                // echo $sFile.' download FAILED !!'.PHP_EOL;
            },
        ]);
        $promise = $pool->promise();
        $promise->wait();
        return true;
    }
}