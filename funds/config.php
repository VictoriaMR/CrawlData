<?php
$aConfig = require_once ROOT_PATH . '/resources/common.config.php';
//数据建表名称 小写
$webSite = 'funds';
return [
    'database' => [
        //数据库链接集合
        'master' => $aConfig['database'],
        'default' => array_merge($aConfig['database'], ['database' => $webSite]),
    ],
    'robot' => [
        'website' => $webSite.$aConfig['version'],
        'version' => $aConfig['version'],
        'storage' => sprintf('%s/%s', $aConfig['storage'], $webSite),
    ]
];