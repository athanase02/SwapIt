<?php
/**
 * Test Multiple Railway Hosts
 */

echo "Testing Different Railway MySQL Hosts\n";
echo "=====================================\n\n";

$hosts = [
    'primary' => [
        'host' => 'shinkansen.proxy.rlwy.net',
        'port' => '32604',
        'password' => 'psMDOMvbOfBoWmHXkhNkhbRLpnPjpcVV'
    ],
    'crossover' => [
        'host' => 'crossover.proxy.rlwy.net',
        'port' => '20980',
        'password' => 'nLPPhjVDjtuxSKJiPHYQlxSKkvdGjtQx'
    ],
    'turntable' => [
        'host' => 'turntable.proxy.rlwy.net',
        'port' => '57424',
        'password' => 'vgaxWyQewCgALyZTUSKazZyJbgykdJjF'
    ],
    'shinkansen-alt' => [
        'host' => 'shinkansen.proxy.rlwy.net',
        'port' => '56904',
        'password' => 'JJJKhMufpprtiSlcREMoPfpjHwivYjnd'
    ]
];

$successCount = 0;
$workingHosts = [];

foreach ($hosts as $name => $config) {
    echo "Testing $name ({$config['host']}:{$config['port']})...\n";
    
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname=railway;charset=utf8mb4";
        
        $conn = new PDO($dsn, 'root', $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT => 5,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
        ]);
        
        // Test query
        $result = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
        
        echo "  ✓ SUCCESS - Connected! Found $result users\n";
        $successCount++;
        $workingHosts[$name] = $config;
        
    } catch (Exception $e) {
        echo "  ✗ FAILED - " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "=====================================\n";
echo "Summary: $successCount/" . count($hosts) . " hosts working\n\n";

if (!empty($workingHosts)) {
    echo "✓ Working configurations:\n\n";
    foreach ($workingHosts as $name => $config) {
        echo "Configuration: $name\n";
        echo "  DB_HOST={$config['host']}\n";
        echo "  DB_PORT={$config['port']}\n";
        echo "  DB_NAME=railway\n";
        echo "  DB_USER=root\n";
        echo "  DB_PASSWORD={$config['password']}\n";
        echo "\n";
    }
    
    // Recommend the best one
    $recommended = array_key_first($workingHosts);
    echo "🎯 RECOMMENDED for Render:\n";
    echo "Use the '$recommended' configuration above\n";
} else {
    echo "✗ No working hosts found!\n";
    echo "Check:\n";
    echo "- Railway database is running\n";
    echo "- Credentials are current\n";
    echo "- Network connectivity\n";
}
