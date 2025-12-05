<?php
$debugConfig = include __DIR__ . '/conf/debug.php';
define('WIND_DEBUG', isset($debugConfig['WIND_DEBUG']) ? $debugConfig['WIND_DEBUG'] : 0);

require './src/wekit.php';
$components = array('router' => array());
Wekit::run('windidnotify', $components);
?>