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
        $this->url = 'http://www.ultrong3d.com/';

        //实例化爬取线程
    	$this->oClient = new Client([
            'timeout' => 30,
        ]);
    }

    public function sync()
    {
        $url_arr = [];
        try {
            $oGuzzle = $this->oClient->get($this->url.'sitemap.xml');
            if (200 == $oGuzzle->getStatusCode()) {
                $sGuzzle = $oGuzzle->getBody()->getContents();
                $xml = simplexml_load_string($sGuzzle);
                foreach ($xml->url as $key => $value) {
                    $value = (array) $value;
                    $url_arr[] = $value['loc'];
                }
            }
        } 
        catch (\GuzzleHttp\Exception\ConnectException $e) {}
        catch (\GuzzleHttp\Exception\ClientException $e) {}

        if (empty($url_arr)) {
            exit();
        }
        //获取代理ip
        $page = 10;
        $ipArr = [];
        for ($i = 1; $i <= $page; $i ++) {
            try {
                $oGuzzle = $this->oClient->get('https://www.kuaidaili.com/free/intr/'.$i.'/');
                if (200 == $oGuzzle->getStatusCode()) {
                    $sGuzzle = $oGuzzle->getBody()->getContents();
                    if ($oHtml = HtmlDomParser::str_get_html($sGuzzle))
                    {
                        foreach( $oHtml->find('#list',0)->find('tbody>tr') as $value) {
                            if ($value->find('td', 0) && $value->find('td', 1)) {
                                $ipArr[] = 'http://'.trim($value->find('td', 0)->plaintext).':'.trim($value->find('td', 1)->plaintext);
                            }
                        }
                    }
                }
                sleep(rand(1, 3));
            } 
            catch (\GuzzleHttp\Exception\ConnectException $e) {}
            catch (\GuzzleHttp\Exception\ClientException $e) {}
        }
        if (empty($ipArr)) {
            exit();
        }
        $ipArr = array_unique($ipArr);
        foreach ($ipArr as $key => $value) {
            $status = true;
            foreach ($url_arr as $uk => $ul) {
                if (!$status) {
                    break;
                }
                try {
                    $oGuzzle = $this->oClient->request('GET', $ul, ['proxy' => $value]);
                    if (200 == $oGuzzle->getStatusCode()) {
                        echo $value.' ==> '.$ul.' success'.PHP_EOL;
                    }
                }
                catch (\GuzzleHttp\Exception\ConnectException $e) {
                    echo $value.' failed'.PHP_EOL;
                    $status = false;
                }
                catch (\GuzzleHttp\Exception\ClientException $e) {
                    echo $value.' failed'.PHP_EOL;
                    $status = false;
                }
            }
        }
        exit();
    }
}