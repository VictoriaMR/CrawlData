<?php
//设置最大执行时间为不限时
set_time_limit(0);
ini_set('memory_limit', '2048M');
header('Content-type:text/html;charset=UTF-8');
//根路径
define('ROOT_PATH', strtr(__DIR__, '\\', '/').'/');

if (is_file(ROOT_PATH.'resources/vendor/autoload.php')) {
	require_once ROOT_PATH.'resources/vendor/autoload.php';
}