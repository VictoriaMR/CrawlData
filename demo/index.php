<?php
//基础文件
require_once './init.php';

foreach ($aQueue as $sQueue)
{
    vv($oQueue->get($sQueue));
    if ($oQueue->get($sQueue) == 'ok') {
        continue;
    }

    $sResult = $oRobot->$sQueue();
    // 完成步骤记录进程
    vv($sResult );
    $sResult ? $oQueue->add($sQueue, 'ok') : dd();
}