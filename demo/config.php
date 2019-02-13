<?php
$aConfig = require APP_PATH . '/resources/common.config.php';

// 数据库配件demo
$aDatabase = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'information_schema',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => ''
];


//数据建表名称 小写
$webSite = 'demosite';


return [
    'database' => [
        //数据库链接集合
        'master' => $aDatabase,
        'default' => array_merge($aDatabase, ['database' => $webSite.$aConfig['version']]),
    ],
    'robot' => [
        'version' => $aConfig['version'],
        'website' => $webSite.$aConfig['version'],
        'storage' => sprintf('%s/%s/%s', $aConfig['storage'], $webSite, $aConfig['version']),
    ]
];