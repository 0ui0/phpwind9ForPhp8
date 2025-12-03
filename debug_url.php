<?php
define('WEKIT_VERSION', '9.0');
require 'wind/Wind.php';
Wind::init();
require 'src/bootstrap/bootstrap.php';

// Try to replicate bootstrap logic
$consts = include 'conf/publish.php';
foreach ($consts as $const => $value) {
    if (!defined($const)) define($const, $value);
}

// Mock request component
class MockRequest {
    public function getBaseUrl($full = false) {
        return 'http://localhost:9999';
    }
}
Wind::registeComponent(new MockRequest(), 'request');

include 'wind/utility/WindUrlHelper.php';

$base = 'http://localhost:9999';
$themes = WindUrlHelper::checkUrl(PUBLIC_THEMES, $base);

echo "PUBLIC_THEMES: " . PUBLIC_THEMES . "\n";
echo "Themes URL: " . $themes . "\n";
