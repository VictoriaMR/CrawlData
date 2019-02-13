<?php
set_time_limit(0);
ini_set('memory_limit', '1024M');

// 配置目录
define('WEB_PATH', __DIR__);
define('APP_PATH', dirname(dirname(WEB_PATH)));
define('VENDOR_PATH', APP_PATH . '/resources/vendor');

// 加载模块
require VENDOR_PATH . '/autoload.php';
$oManager = new Intervention\Image\ImageManager(['driver' => 'gd']);

$sPath = __DIR__. '/old';
$aPath = glob_file($sPath);
foreach ($aPath as $sKey => $sVal)
{
    $sNew = str_replace('/old/', '/new/', $sVal);
    if (!is_file($sNew))
    {
        $s = time();
        try
        {
            $oImage = $oManager->make($sVal);
        } catch (Intervention\Image\Exception\NotReadableException $exception) {
            unlink($sVal);
            continue;
        }
        $sWidth = $oImage->getWidth();
        $sHeight = $oImage->getHeight();
        $sMargin = ($sHeight - $sWidth) / 2;
        $sPadding = $sWidth * 110 / 935;

        for ($sH = $sMargin; $sH < ($sHeight - $sMargin); $sH++)
        {
            $sWidthR = $sWidth - $sH + $sPadding;

            // 计算宽度起始位置
            $sWidthS = floor($sWidthR - 25);
            $sWidthS = ($sWidthS > 0) ? $sWidthS : 0;

            $sWidthE = ceil($sWidthR + 500);
            $sWidthE = ($sWidthE > $sWidth) ? $sWidth : $sWidthE;

            for ($sW = $sWidthS; $sW < $sWidthE; $sW++)
            {
                $aIndex = $oImage->pickColor($sW, $sH, 'array');
                if (($aIndex[0] >= 200) && ($aIndex[1] >= 200) && ($aIndex[2] >= 200))
                {
                    $aIndex[0] = 255;
                    $aIndex[1] = 255;
                    $aIndex[2] = 255;
                    $oImage->pixel($aIndex, $sW, $sH);
                }
            }
        }
        existsOrCreate($sNew);
        $oImage->save($sNew);
        $e = time();

        echo sprintf("%s=%d\r\n", $sVal, $e - $s);
    }
}