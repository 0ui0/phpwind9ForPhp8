<?php
error_reporting(E_ERROR | E_PARSE);
define('BOOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);
require '.././src/wekit.php';

Wekit::run('windidadmin', array('router' => array()));
?>