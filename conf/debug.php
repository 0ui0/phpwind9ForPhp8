<?php
// 调试模式配置文件
// 将 WIND_DEBUG 改为 3 开启调试 (1:Window, 2:Log, 3:Both)
// 将 WIND_DEBUG 改为 0 关闭调试

$debugMode = 3; // 开启调试

if ($debugMode) {
	error_reporting(E_ALL);
	ini_set('display_errors', '1');
} else {
	error_reporting(E_ERROR | E_PARSE);
	ini_set('display_errors', '0');
}

return array(
	'WIND_DEBUG' => $debugMode,
);