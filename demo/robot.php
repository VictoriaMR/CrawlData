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
    public $aConfig;
    public $oClient;
    public $oCookie;
    public $aHeader;

    public function __construct($aConfig)
    {
        
    	// setting
        $this->aConfig = $aConfig;
        $this->url = 'https://www.taobao.com/';

    	$this->aHeader = [
			
    	];

        //实例化爬取线程
    	$this->oClient = new Client([
            'timeout' => 30,
            'cookies' => true,
            // 'headers' => $this->aHeader
        ]);

        //自动获取cookie demo
        // $this->oCookie = new \GuzzleHttp\Cookie\CookieJar();

        //设置cookie
        // if(!empty($this->oCookie));
            // $this->oClient->get($this->url, ['cookies' => $this->oCookie]);
        
        
    }

    function test()
    {
        $data = array_object(Capsule::connection('data')->table('fac_product')->get());
        $getArr = [];
        foreach ($data as $key => $value) 
        {
            $value['pro_name'] = substr($value['pro_name'], strrpos($value['pro_name'], '-')+1);
            $getArr[] = $value;
            // print_r($value);dd();
        }
        Capsule::connection('data')->table('fac_product')->truncate();
        $arr = array_chunk($getArr, 2999);
        foreach ($arr as $key => $value) 
        {
            Capsule::connection('data')->table('fac_product')->insert($value);
        }
        dd();
    }

    public function sync()
    {
        echo __FUNCTION__.' start !!!'.PHP_EOL;

        $sFile = sprintf('%s/%s/%s.html', $this->aConfig['storage'], __FUNCTION__, __FUNCTION__);
        for ($sRun = 0; $sRun < 9; $sRun ++)
        { 
            if (!is_file($sFile))
            {
                try
                {
                	$oGuzzle = $this->oClient->get($this->url);
                    $this->oCookie = new \GuzzleHttp\Cookie\CookieJar();
                    if (200 == $oGuzzle->getStatusCode())
                    {
                        $sGuzzle = $oGuzzle->getBody()->getContents();
                        existsOrCreate($sFile);
                        file_put_contents($sFile, $sGuzzle);
                        echo $sFile.' downloaded !!'.PHP_EOL;exit;
                    }else
                    {
                        echo 'file download fail!!'.PHP_EOL;
                    } 
                } catch (\GuzzleHttp\Exception\ConnectException $e) {
                    echo 'connection error'.PHP_EOL;
                } catch (\GuzzleHttp\Exception\ClientException $e) {
                    echo 'guzzle error'.PHP_EOL;
                } 
            }
        }
        //解析HTML文件
        if (is_file($sFile))
        {
            if ($oHtml = HtmlDomParser::str_get_html(file_get_contents($sFile)))
            {
                $str = $oHtml->find('.qrcode-text',0)->plaintext;
                echo trim($str);
            }
        }
        
        if (!empty($imgArr))
        {
            Capsule::table('product_pictures')->insert($imgArr);
            echo 'product_pictures had inserted'.PHP_EOL;
            $imgArr = [];
        }
    }
    public function sync2()
    {
        echo __FUNCTION__.' start !!!'.PHP_EOL;

        $getArr = [];
        for ($i=0; $i < 1000; $i++) 
        { 
            $sFile = sprintf('%s/%s/%s.html', $this->aConfig['storage'], __FUNCTION__, $i);
            if (!is_file($sFile))
            {
                $getArr[] = [
                    'url'=>$this->url,
                    'file'=>$sFile,
                    'title'=>$sFile,
                ];
            }
        }

        if (!empty($getArr))
        {
            $guzzle = new guzzle();
            $guzzle->poolGet($getArr, 20, 100, 9, true, ['verify'=>false, 'timeout'=>3]);
        }
    }
}