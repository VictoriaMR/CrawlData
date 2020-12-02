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
        $data = Capsule::table('fundcodes')->select(['code', 'name'])->get()->toArray();
        $data = array_column($data, 'name', 'code');
        $downloadArr = [];
        foreach ($data as $key => $value) {
            $sFile = sprintf('%s/%s/%s.json', $this->aConfig['storage'], date('Ymd'), $key);
            $downloadArr[] = [
                'title' => $sFile,
                'url' => $this->url.$key.'.js?rt='.time(),
                'file' => $sFile,
                'name' => $value,
                'code' => $key,
            ];
        }
        $this->getFile($downloadArr, 200, 3);
        $downloadArr = [];
        echo 'downloaded used '.(time() - $time).' s'.PHP_EOL;
        Capsule::table('fundrecords')->where('gztime', '>', date('Y-m-d'))->delete();
        if (empty($this->insert)) {
            return false;
        }
        echo count($this->insert).' 条数据待入库'.PHP_EOL;
        //更新净值
        foreach ($this->insert as $key => $value) {
        	Capsule::table('fundcodes')->where('code', $key)->update(['dwjz'=>$value['dwjz'], 'gsz'=>$value['gsz'], 'gszzl'=>$value['gszzl']]);
        }
        $this->insert = array_chunk($this->insert, 2999);
        foreach ($this->insert as $key => $value) {
            Capsule::table('fundrecords')->insert($value);
        }
        echo 'insert DB USE '.(time() - $time).' s'.PHP_EOL;
        $this->insert = [];
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
                        $response = js_json(trim(trim($response, 'jsonpgz('), '));'));
                        if (!empty($response)) {
                            $response['gztime'] = date('Y-m-d', strtotime($response['gztime']));
                            $this->insert[$response['fundcode']] = $response;
                        }
                        // echo $sFile.' downloaded !!'.PHP_EOL;
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