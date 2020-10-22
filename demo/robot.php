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
        $this->url = 'https://www.taobao.com/';

        //实例化爬取线程
    	$this->oClient = new Client([
            'timeout' => 30,
        ]);
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
                // $str = $oHtml->find('.qrcode-text',0)->plaintext;
                // echo trim($str);
            }
        }
        return true;
    }
}