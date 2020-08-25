<?php
//设置最大执行时间为不限时
set_time_limit(0);
ini_set('memory_limit', '2048M');
header('Content-type:text/html;charset=UTF-8');

// 配置目录
define('WEB_PATH', __DIR__);
define('APP_PATH', dirname(WEB_PATH));
define('VENDOR_PATH', APP_PATH . '/resources/vendor');

// 加载扩展模块
require VENDOR_PATH . '/autoload.php';
//加载数据库模块
require WEB_PATH . '/schema.php';
//加载线程模块
require WEB_PATH . '/robot.php';
//加载并发请求
@include WEB_PATH . '/guzzle.php';

// 加载配置文件
$aRobot = require WEB_PATH . '/config.php';

// 创库
$oScheme = new Schema($aRobot['database']);
$oScheme->createDatabase();

// 爬虫
$oRobot = new Robot($aRobot['robot']);

// 进程
$aQueue = ['sync'];
$oQueue = new Huluo\Extend\Queue($aRobot['robot']['website']);
foreach ($aQueue as $sQueue)
{
    if ($oQueue->get($sQueue) == 'ok') {
        continue;
    }

    switch ($sQueue)
    {
        case 'sync':
            $sResult = $oRobot->sync();
            break;
    }

    // 完成步骤记录进程
    $sResult ? $oQueue->add($sQueue, 'ok') : dd();
}