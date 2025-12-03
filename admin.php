<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

define('BOOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require './src/wekit.php';

$components = array('router' => array('config' => array('module' => array('default-value' => 'default'), 'routes' => array('admin' => array('class' => 'LIB:route.PwAdminRoute','default' => true)))));
Wekit::run('pwadmin', $components);