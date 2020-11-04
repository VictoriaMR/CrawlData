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
        $time = time();
        $data = Capsule::table('fundcodes')->select('code')->get()->toArray();
        $data = array_unique(array_column($data, 'code'));
        $downloadArr = [];
        foreach ($data as $value) {
            $sFile = sprintf('%s/%s/%s.json', $this->aConfig['storage'], date('Ymd'), $value);
            if (!is_file($sFile)) {
                $downloadArr[] = [
                    'title' => $sFile,
                    'url' => $this->url.$value.'.js?rt='.time(),
                    'file' => $sFile,
                ];
            }
        }
        $res = $this->getFile($downloadArr, 200, 3);
        $insert = [];
        foreach ($data as $value) {
            $sFile = sprintf('%s/%s/%s.json', $this->aConfig['storage'], date('Ymd'), $value);
            if (is_file($sFile)) {
                $content = file_get_contents($sFile);
                if (!empty($content)) {
                    $content = json_decode($content, true);
                    if (!empty($content)) {  
                        $insert[] = $content;
                    }
                }
            }
        }
        Capsule::table('fundrecords')->where('gztime', '>', date('Y-m-d').' 00:00')->delete();
        $insert = array_chunk($insert, 2999);
        foreach ($insert as $key => $value) {
            Capsule::table('fundrecords')->insert($value);
        }
        echo 'USE '.(time() - $time).' s'.PHP_EOL;
        return false;
    }

    protected function getFile($downloadArr, $request = 1, $time = 1)
    {
        if (empty($downloadArr)) {
            return false;
        }
        //塞入异步进程
        $timecount = 0;
        while (count($downloadArr) > 0 && $timecount < $time) {
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
                        $response = trim(trim($response, 'jsonpgz('), ');');
                        if (!empty($response)) {
                            $sFile = $downloadArr[$index]['file'];
                            existsOrCreate($sFile);
                            file_put_contents($sFile, $response);
                            // echo $sFile.' downloaded !!'.PHP_EOL;
                        }
                    }
                },
                'rejected' => function ($reason, $index) use ($downloadArr) {
                    // $sFile = $downloadArr[$index]['file'];
                    // echo $sFile.' download FAILED !!'.PHP_EOL;
                },
            ]);
            $promise = $pool->promise();
            $promise->wait();
            foreach ($downloadArr as $key => $value) 
            {
                if (is_file($value['file'])) unset($downloadArr[$key]);
            }
            $timecount++;
        }
        return true;
    }
}