<?php
// 测试URI修复逻辑
require_once 'src/bootstrap.php';

// 模拟不同的URI场景
$testCases = [
    // 正常情况：相对路径
    '/index.php?c=design&a=data&m=run',
    // 错误情况：包含完整URL
    'http://localhost:9999/index.php?c=localhost%3A9999&a=index.php&m=bbs',
    // 其他可能的错误格式
    'localhost:9999/index.php?c=design&a=data',
    'http://localhost:9999/design/data/run',
];

echo "测试URI修复逻辑：\n\n";

foreach ($testCases as $testUri) {
    echo "测试URI: {$testUri}\n";

    // 模拟修复逻辑
    $hostInfo = 'http://localhost:9999';
    $httpHost = 'localhost:9999';

    if (preg_match('/^https?:\/\//i', $testUri)) {
        $result = $testUri;
        echo "  - 检测到完整URL，直接使用\n";
    } else {
        if ($httpHost && strpos($testUri, $httpHost) !== false) {
            echo "  - 检测到包含主机名，尝试清理\n";
            $pattern = '/^https?:\/\/[^\/]+/i';
            $cleanUri = preg_replace($pattern, '', $testUri);
            if ($cleanUri && $cleanUri !== $testUri) {
                $result = $hostInfo . $cleanUri;
                echo "  - 清理成功: {$cleanUri}\n";
            } else {
                $result = $testUri;
                echo "  - 清理失败，直接使用\n";
            }
        } else {
            $result = $hostInfo . $testUri;
            echo "  - 相对路径，拼接主机信息\n";
        }
    }

    echo "  - 最终结果: {$result}\n";
    echo "  - URL编码后: " . urlencode($result) . "\n";
    echo "\n";
}

echo "测试完成！\n";
?>