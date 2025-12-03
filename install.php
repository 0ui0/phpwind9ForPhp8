<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 'On');

require './src/wekit.php';
Wekit::run('install');