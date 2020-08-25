<?php
$aConfig = require APP_PATH . '/resources/common.config.php';

// 数据库配件demo
$aDatabase = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'port' => '3306',
    'database' => 'information_schema',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => ''
];


//数据建表名称 小写
$webSite = 'dalotto';

return [
    'database' => [
        //数据库链接集合
        'master' => $aDatabase,
        'default' => array_merge($aDatabase, ['database' => $webSite]),
    ]
];