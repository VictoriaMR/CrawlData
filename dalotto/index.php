<?php
//基础文件
require './init.php';
foreach ($aQueue as $sQueue)
{
    if ($oQueue->get($sQueue) == 'ok') {
        continue;
    }
    $sResult = $oRobot->$sQueue();
    // 完成步骤记录进程
    $sResult ? $oQueue->add($sQueue, 'ok') : dd();
}