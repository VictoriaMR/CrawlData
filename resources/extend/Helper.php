<?php
use GuzzleHttp\Client;
use Sunra\PhpSimple\HtmlDomParser;
/**
 * @desc 压缩html,清除换行符,清除制表符,去掉注释标记
 */
if (!function_exists('minify'))
{
    function minify($html)
    {
        // 清除换行符制表符
        $html = str_replace(['<?','?>'], ['<','>'], $html);
        $pattern = array("/\s{2,}|\t/","/\r{2,}/", "/\n{2,}/");
        $replace = array(" ");
        return preg_replace($pattern, $replace, $html);
    }
}

/**
 * @author huluo
 * @desc 解析URL参数
 */
if (!function_exists('parseUrlParam'))
{
    function parseUrlParam($sQuery)
    {
        $aParam = [];
        $aParse = parse_url($sQuery);
        if (isset($aParse['query']))
        {
            $aQuery = explode('&', $aParse['query']);
            if ($aQuery[0] !== '')
            {
                foreach ($aQuery as $sParam)
                {
                    list($sLabel, $sValue) = explode('=', $sParam);
                    $aParam[urldecode($sLabel)] = urldecode($sValue);
                }
            }
        }
        return $aParam;
    }
}

/**
 * @author huluo
 * @desc 设置URL参数数组
 */
if (!function_exists('setUrlParams'))
{
    function setUrlParams($cparams, $url = '')
    {
        $parse_url = $url === '' ? parse_url($_SERVER["REQUEST_URI"]) : parse_url($url);
        $query = isset($parse_url['query']) ? $parse_url['query'] : '';
        $params = parseUrlParam($query);
        foreach ($cparams as $key => $value)
        {
            $params[$key] = $value;
        }
        return $parse_url['path'].'?'.http_build_query($params);
    }
}

/**
 * 字符串截取
 */
if (!function_exists('cut'))
{
    function cut($sHtml, $sCutS, $sCutE)
    {
        if (false !== strpos($sHtml, $sCutS))
        {
            $sHtml = explode($sCutS, $sHtml)['1'];
            $sHtml = explode($sCutE, $sHtml)['0'];
            return $sHtml;
        }
        return false;
    }
}

/**
 * 保留指定字符串
 */
if (!function_exists('onlyStr'))
{
    function onlyStr($aText, $sGet = '')
    {
        $aPattern = [];
        $sPattern = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'. $sGet;
        for ($sRun = 0; $sRun < strlen($aText); $sRun++)
        {
            if (false === strpos($sPattern, $aText[$sRun])) {
                $aPattern[] = $aText[$sRun];
            }
        }
        if (!empty($aPattern)) {
            $aText = str_replace($aPattern, '', $aText);
        }
        return strtoupper($aText);
    }
}

/**
 * 保存数组
 */
if (!function_exists('array_save'))
{
    function array_save($aValue, $sFile, $sVar = false)
    {
        $sValue = var_export($aValue, true);
        $sValue = !$sVar
            ? sprintf("<?php\n return %s;\n?>", $sValue)
            : sprintf("<?php\n %s =\n%s;\n?>", $sVar, $sValue);
        file_put_contents($sFile, $sValue);
    }
}

/**
 * 对象转数组
 */
if (!function_exists('array_object'))
{
    function array_object($oValue)
    {
        return $aTable = $oValue->transform(function($aItem) {
            return (array) $aItem;
        })->toArray();
    }
}

/**
 * 创建提交地址目录
 */
if (!function_exists('existsOrCreate'))
{
    function existsOrCreate($sPath)
    {
        $sPath = pathinfo($sPath, PATHINFO_DIRNAME);
        if (!is_dir($sPath)) {
            mkdir($sPath, 0777, true);
        }
        return true;
    }
}

/**
 * 获取文件列表
 */
if (!function_exists('glob_file'))
{
    function glob_file($sPath, $aReturn = [])
    {
        $sPath = rtrim($sPath, '/');
        foreach(glob($sPath . '/*') as $sDir)
        {
            if (is_dir($sDir)) {
                $aReturn = array_merge($aReturn, glob_file($sDir));
            } else {
                $aReturn[] = $sDir;
            }
        }
        return $aReturn;
    }
}

/*
 * 代理ip
 */
if (!function_exists('my_proxy'))
{
    function my_proxy($num = 1)
    {
        $file = 'D:/proxy/proxy.php';
        $arr = @include $file;

        $arr = is_array($arr) ? $arr : [];

        if (count($arr) < $num)
            getMyProxy();

        $arr = require $file;

        $arr1 = array_slice($arr, 0, $num);
        array_splice($arr, 0, $num);
        array_save($arr, $file);
        return $arr1;
    }

    function getMyProxy()
    {
        $oClient = new Client([
            'timeout' => 5,
            'verify'=>false
        ]);
        $nowtime = str_replace(['-',' '], '', date('Y-m-d H'));
        $page = 10;
        $url = 'https://www.xicidaili.com/nn/%s';
        for ($i=1; $i <= $page; $i++) 
        { 
            $sFile = sprintf('%s/%s/xicidaili-%s.html', 'D:/proxy', $nowtime, $i);
            for ($sRun = 0; $sRun < 30; $sRun ++)
            {
                if (!is_file($sFile))
                {
                    try
                    {
                        $geturl = sprintf($url, $i);
                        $oGuzzle = $oClient->get($geturl);
                        if (200 == $oGuzzle->getStatusCode()) 
                        {
                            $sGuzzle = $oGuzzle->getBody()->getContents();
                            if (!empty($sGuzzle))
                            {
                                existsOrCreate($sFile);
                                file_put_contents($sFile, $sGuzzle);
                                echo $sFile.' download!!'.PHP_EOL;
                            }
                        }
                        sleep(rand(4,5));
                    } 
                    catch (\GuzzleHttp\Exception\ConnectException $e) {echo "ConnectException".PHP_EOL;} 
                    catch (\GuzzleHttp\Exception\ClientException $e)  {echo "ClientException".PHP_EOL;} 
                    catch (\GuzzleHttp\Exception\RequestException $e) {echo "RequestException".PHP_EOL;}
                }
            }
        }
        $arr= [];
        for ($i=1; $i <= $page; $i++) 
        {
            $sFile = sprintf('%s/%s/xicidaili-%s.html', 'D:/proxy', $nowtime, $i);
            if (is_file($sFile))
            {
                if($dom = HtmlDomParser::str_get_html(file_get_contents($sFile)))
                {
                    foreach ($dom->find('#ip_list tr') as $key => $value) 
                    {
                        // print_r($sFile);dd();
                        if ($key < 1) continue;
                        $time = trim($value->find('td',7)->find('.bar',0)->title);
                        $day = trim($value->find('td',8)->plaintext);
                        $time = substr($time, 0,-1);
                        if ($time > 3 || strpos($time, '天') === false) continue;
                        $http = strtolower(trim($value->find('td',5)->plaintext));
                        $arr[] = $http.'://'.trim($value->find('td',1)->plaintext).':'.trim($value->find('td',2)->plaintext);
                    }
                }
            }
        }
        array_save(array_unique($arr),'D:/proxy/proxy.php');
    }
}

if (!function_exists('formatNum'))
{
    function formatNum( $string, $b = '')
    {
        $strmat = GetSemiangle($string);
        if($b == '')
        {
            $strmat = preg_replace('/[^0-9a-zA-Z]/', '', $strmat);
        }
        else
        {
            $strTemp = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ". $b;
            for ($i=0; $i<strlen($string); $i++)
            {
                $c = substr($string,$i,1);
                $pos = strpos($strTemp, $c); 
                if ($pos === false) {
                    $strmat = str_replace($c,"", $strmat);
                }
            }
        }
        return strtoupper($strmat);
    }

    function GetSemiangle($str)
    {
        $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4','５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9', 
        'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E','Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J', 'Ｋ' => 'K', 
        'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O','Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T','Ｕ' => 'U', 'Ｖ' => 'V', 
        'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y','Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd','ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 
        'ｈ' => 'h', 'ｉ' => 'i','ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n','ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 
        'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z', '～'=>'~', '·'=>'`', '！'=>'!',
        '＠'=>'@', '＃'=>'#', '￥'=>'$', '％'=>'%', '……'=>'^', '＆'=>'&', '×'=>'*', '（'=>'(', '）'=>')', '——'=>'_', '－'=>'-', '＋'=>'+', '＝'=>'=', 
        '｛'=>'{', '｝'=>'}', '【'=>'[', '】'=>']', '｜'=>'|', '＼'=>'\\', '：'=>':', '；'=>';', '”'=>'"', '’'=>'\'', '《'=>'<', '，'=>',', '》'=>'>',
        '。'=>'.', '？'=>'?', '、'=>'/'); 
        return strtr($str, $arr); 
    }
}