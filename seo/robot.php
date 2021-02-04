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
        $page = 30;
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
                                $ipArr[] = trim($value->find('td', 0)->plaintext).':'.trim($value->find('td', 1)->plaintext);
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
                if ($this->curl_via_proxy($ul, $value)) {
                    echo $value.' ==> '.$ul.' success'.PHP_EOL;
                } else {
                    echo $value.' failed'.PHP_EOL;
                    break;
                }
            }
        }
        exit();
    }

    public function curl_via_proxy($url,$proxy_ip, $method = 'GET')
    {
        $arr_ip = explode(':',$proxy_ip);

        $ch = curl_init($url); //创建CURL对象  
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HEADER, 0); //返回头部  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //返回信息  
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); //连接超时时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); //读取超时时间
        curl_setopt($ch, CURLOPT_PROXY, $arr_ip[0]); //代理服务器地址
        curl_setopt($ch, CURLOPT_PROXYPORT, $arr_ip[1]); //代理服务器端口
        $res = curl_exec($ch);
        $curl_errno = curl_errno($ch);
        if ($curl_errno) {
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return true;
    }
}