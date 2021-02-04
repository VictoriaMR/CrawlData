<?php
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Sunra\PhpSimple\HtmlDomParser;
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
        $this->url = 'https://www.js-lottery.com/PlayZone/ajaxLottoData';

        //实例化爬取线程
    	$this->oClient = new Client([
            'timeout' => 30,
        ]);
    }

    public function sync()
    {
        echo __FUNCTION__.' start !!!'.PHP_EOL;
        $i = 1;
        $lastNo = Capsule::table('lottery')->max('qishu');
        $check = false;
        $keyName = ['num1', 'num2', 'num3', 'num4', 'num5', 'num6', 'num7'];
        $insert = [];
        $all_count = 0;
        while (!$check) {
            try {
                $oGuzzle = $this->oClient->post($this->url, ['form_params' => ['current_page' => $i, 'all_count' =>$all_count]]);
                if (200 == $oGuzzle->getStatusCode()) {
                    echo $i.'page downloaded'.PHP_EOL;
                    $sGuzzle = $oGuzzle->getBody()->getContents();
                    if (!empty($sGuzzle)) {
                        $sGuzzle = json_decode($sGuzzle, true);
                        $all_count = $sGuzzle['all_count'] ?? 0;
                        if (!empty($sGuzzle['items'])) {
                            foreach ($sGuzzle['items'] as $key => $value) {
                                $qishu = $value['num'];
                                if ($qishu <= $lastNo) {
                                    echo '无需更新'.PHP_EOL;
                                    return false;
                                }
                                $data = [
                                    'num1' => $value['one'],
                                    'num2' => $value['two'],
                                    'num3' => $value['three'],
                                    'num4' => $value['four'],
                                    'num5' => $value['five'],
                                    'num6' => $value['six'],
                                    'num7' => $value['seven'],
                                 ];
                                $data['qishu'] = $qishu;
                                $insert[$qishu] = $data;
                            }
                        } else {
                            $check = true;
                        }
                    }
                } else {
                    echo 'network fail!!'.PHP_EOL;
                }
                $i ++;
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                echo 'connection error'.PHP_EOL;
            }
        }
        if (!empty($insert)) {
            ksort($insert);
            $insert = array_chunk($insert, 2000);
            foreach ($insert as $key => $value) {
                Capsule::table('lottery')->insert($value);
            }
        }
        return false;
    }

    public function getNumber()
    {
        echo __FUNCTION__.' start !!!'.PHP_EOL;
        $keyName = ['num1', 'num2', 'num3', 'num4', 'num5', 'num6', 'num7'];
        $data = Capsule::table('lottery')->select($keyName)->get()->toArray();
        $data1 = array_count_values(array_column($data, 'num1'));
        $data2 = array_count_values(array_column($data, 'num2'));
        $data3 = array_count_values(array_column($data, 'num3'));
        $data4 = array_count_values(array_column($data, 'num4'));
        $data5 = array_count_values(array_column($data, 'num5'));
        $data6 = array_count_values(array_column($data, 'num6'));
        $data7 = array_count_values(array_column($data, 'num7'));
        asort($data1);
        asort($data2);
        asort($data3);
        asort($data4);
        asort($data5);
        asort($data6);
        asort($data7);
        print_r($data1);
        print_r($data2);
        print_r($data3);
        print_r($data4);
        print_r($data5);
        print_r($data6);
        print_r($data7);
        die();
        // $rate = (sqrt(5) - 1) / 2;
        $rate = 0.8;
        $result = [];
        $data = [
            $data1,
            $data2,
            $data3,
            $data4,
            $data5,
            $data6,
            $data7,
        ];
        foreach ($data as $key => $value) {
            $tempstr = 'data'.($key + 1);
            $$tempstr = [];
            $tmpArr = array_keys($value);
            $$tempstr[] = end($tmpArr);
            $count = count($value);
            $num = $count * $rate;
            $num = ceil($num);
            $i = 0;
            $prev = '';
            foreach ($value as $k => $v) {
                if ($v > 100) {
                    $$tempstr[] = $k;
                    $$tempstr[] = $prev;
                    break;
                }
                $prev = $k;
            }
            foreach ($value as $k => $v) {
                $i++;
                if ($i == $num || $i == ($num + 1)) {
                    $$tempstr[] = $k;
                }
            }
        }
        $data = [
            $data1,
            $data2,
            $data3,
            $data4,
            $data5,
            $data6,
            $data7,
        ];
        $i = 0;
        while ( $i <= 4) {
            $tempArr = [];
            foreach ($data as $key => $value) {
                if ($key > 4) break;
                $tempArr[] = $value[$i];
            }
            sort($tempArr);
            $temp2Arr = [];
            foreach ($data as $key => $value) {
                if ($key <= 4) continue;;
                $temp2Arr[] = $value[$i];
            }
            sort($temp2Arr);
            echo implode(' ', array_merge($tempArr, $temp2Arr)).PHP_EOL;
            $i ++;
        }
        return false;
    }
}