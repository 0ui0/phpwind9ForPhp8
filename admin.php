<?php
$debugConfig = include __DIR__ . '/conf/debug.php';
define('WIND_DEBUG', isset($debugConfig['WIND_DEBUG']) ? $debugConfig['WIND_DEBUG'] : 0);

define('BOOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require './src/wekit.php';

$components = array('router' => array('config' => array('module' => array('default-value' => 'default'), 'routes' => array('admin' => array('class' => 'LIB:route.PwAdminRoute','default' => true)))));
Wekit::run('pwadmin', $components);