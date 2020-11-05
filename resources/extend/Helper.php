<?php
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

if (!function_exists('dd'))
{
    function dd(...$arg) {
        foreach ($arg as $value) {
            print_r($value);
            echo PHP_EOL;
        }
        exit();
    }
}

if (!function_exists('vv'))
{
    function vv(...$arg) {
        foreach ($arg as $value) {
            var_dump($value);
            echo PHP_EOL;
        }
        exit();
    }
}

if (!function_exists('js_json'))
{
    function js_json($string) {
        $string = json_decode($string, true); 
        return json_last_error() == JSON_ERROR_NONE ? $string : false;
    }
}