<?php
require BASE_PATH.'/app.php';
if (is_file(APP_PATH.'/config.php')) {
	$config = require APP_PATH.'/config.php';
}
if (empty($config)) {
	exit('set config first');
}
if (is_file(APP_PATH.'/schema.php')) {
	require APP_PATH.'/schema.php';
	// 创库
	$oScheme = new Schema($config['database']);
	$oScheme->createDatabase();
}
if (is_file(APP_PATH.'/robot.php')) {
	require APP_PATH.'/robot.php';
	$oRobot = new Robot($config['robot']);
}
if (empty($oRobot)) {
	exit('set robot first');
}
$oQueue = new Huluo\Extend\Queue($config['robot']['website']);
$aQueue = get_class_methods($oRobot);
if (!empty($aQueue)) {
	unset($aQueue[array_search('__construct', $aQueue)]);
	$aQueue = array_values($aQueue);
	$oQueue = new Huluo\Extend\Queue($config['robot']['website']);
}