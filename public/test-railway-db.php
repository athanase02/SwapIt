<?php
/**
 * Railway Database Connection Test
 * Tests the Railway MySQL database connection and displays status
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Railway Database Test</title>
    <style>
        body { 
            font-family: 'Segoe UI', Arial, sans-serif; 
            max-width: 900px; 
            margin: 50px auto; 
            padding: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .container {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #667eea; 
            margin: 0 0 30px 0;
            font-size: 32px;
        }
        .status { 
            padding: 15px 20px; 
            border-radius: 8px; 
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 16px;
        }
        .success { 
            background: #d1fae5; 
            color: #065f46; 
            border-left: 5px solid #10b981;
        }
        .error { 
            background: #fee2e2; 
            color: #991b1b; 
            border-left: 5px solid #ef4444;
        }
        .info { 
            background: #dbeafe; 
            color: #1e40af; 
            border-left: 5px solid #3b82f6;
        }
        .warning {
            background: #fef3c7;
            color: #92400e;
            border-left: 5px solid #f59e0b;
        }
        pre { 
            background: #1f2937; 
            color: #10b981;
            padding: 20px; 
            border-radius: 8px; 
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
        }
        .icon { 
            font-size: 24px; 
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🚀 Railway Database Connection Test</h1>
";

try {
    // Check for environment variables
    echo "<h2>Step 1: Environment Variables</h2>";
    
    $envVars = [
        'DB_HOST' => getenv('DB_HOST'),
        'DB_PORT' => getenv('DB_PORT'),
        'DB_NAME' => getenv('DB_NAME'),
        'DB_USER' => getenv('DB_USER'),
        'DB_PASSWORD' => getenv('DB_PASSWORD') ? '***hidden***' : 'NOT SET'
    ];
    
    $allSet = true;
    foreach ($envVars as $key => $value) {
        if (empty($value) || $value === 'NOT SET') {
            echo "<div class='status error'><span class='icon'>❌</span> <strong>$key:</strong> NOT SET</div>";
            $allSet = false;
        } else {
            if ($key === 'DB_PASSWORD') {
                echo "<div class='status success'><span class='icon'>✅</span> <strong>$key:</strong> $value</div>";
            } else {
                echo "<div class='status success'><span class='icon'>✅</span> <strong>$key:</strong> $value</div>";
            }
        }
    }
    
    if (!$allSet) {
        echo "<div class='status warning'><span class='icon'>⚠️</span> <strong>Action Required:</strong> Set environment variables in Railway dashboard</div>";
        echo "<div class='info' style='margin-top: 20px;'>
            <h3>How to Set Environment Variables in Railway:</h3>
            <ol>
                <li>Go to your Railway project dashboard</li>
                <li>Click on your service</li>
                <li>Go to 'Variables' tab</li>
                <li>Add these variables from your MySQL service:
                    <ul>
                        <li><code>DB_HOST</code> - MySQL host (e.g., containers-us-west-xxx.railway.app)</li>
                        <li><code>DB_PORT</code> - Port number (usually 3306)</li>
                        <li><code>DB_NAME</code> - Database name (usually railway)</li>
                        <li><code>DB_USER</code> - MySQL username (usually root)</li>
                        <li><code>DB_PASSWORD</code> - MySQL password</li>
                    </ul>
                </li>
            </ol>
        </div>";
        exit;
    }
    
    // Try to connect
    echo "<h2>Step 2: Database Connection</h2>";
    
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: '3306';
    $database = getenv('DB_NAME');
    $username = getenv('DB_USER');
    $password = getenv('DB_PASSWORD');
    
    $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
    
    echo "<div class='status info'><span class='icon'>🔄</span> Attempting connection to: <strong>$host:$port/$database</strong></div>";
    
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "<div class='status success'><span class='icon'>✅</span> <strong>Connection Successful!</strong> Connected to Railway MySQL database</div>";
    
    // Test query
    echo "<h2>Step 3: Database Information</h2>";
    
    $stmt = $conn->query("SELECT VERSION() as version");
    $version = $stmt->fetch();
    echo "<div class='status success'><span class='icon'>📊</span> MySQL Version: <strong>{$version['version']}</strong></div>";
    
    // Check if tables exist
    echo "<h2>Step 4: Database Tables</h2>";
    
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<div class='status warning'><span class='icon'>⚠️</span> <strong>No tables found!</strong> You need to run the migration.</div>";
        echo "<div class='info' style='margin-top: 20px;'>
            <p><strong>Next Step:</strong> Run the database migration to create all necessary tables:</p>
            <ol>
                <li>Go to: <code>https://your-app.up.railway.app/public/setup-realtime.php</code></li>
                <li>This will create all required tables automatically</li>
            </ol>
        </div>";
    } else {
        echo "<div class='status success'><span class='icon'>✅</span> Found <strong>" . count($tables) . "</strong> tables in database</div>";
        
        echo "<table>";
        echo "<thead><tr><th>#</th><th>Table Name</th><th>Row Count</th></tr></thead>";
        echo "<tbody>";
        
        $requiredTables = [
            'users', 'items', 'user_online_status', 'conversations', 'messages',
            'borrow_requests', 'meeting_schedules', 'transactions', 'ratings', 'notifications'
        ];
        
        foreach ($tables as $index => $table) {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "<tr><td>" . ($index + 1) . "</td><td><strong>$table</strong></td><td>$count rows</td></tr>";
        }
        
        echo "</tbody></table>";
        
        // Check for missing required tables
        $missingTables = array_diff($requiredTables, $tables);
        if (!empty($missingTables)) {
            echo "<div class='status warning'><span class='icon'>⚠️</span> <strong>Missing tables:</strong> " . implode(', ', $missingTables) . "</div>";
            echo "<div class='info'>
                <p><strong>Action Required:</strong> Run the migration to create missing tables:</p>
                <p>Visit: <code>https://your-app.up.railway.app/public/setup-realtime.php</code></p>
            </div>";
        }
    }
    
    // Test write operation
    echo "<h2>Step 5: Write Test</h2>";
    
    try {
        // Try to create a test table
        $conn->exec("CREATE TABLE IF NOT EXISTS connection_test (
            id INT PRIMARY KEY AUTO_INCREMENT,
            test_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        $conn->exec("INSERT INTO connection_test (test_timestamp) VALUES (NOW())");
        
        $stmt = $conn->query("SELECT COUNT(*) as count FROM connection_test");
        $testCount = $stmt->fetch()['count'];
        
        echo "<div class='status success'><span class='icon'>✅</span> <strong>Write test successful!</strong> Database is writable ($testCount test records)</div>";
        
        // Clean up test table
        $conn->exec("DROP TABLE IF EXISTS connection_test");
        
    } catch (PDOException $e) {
        echo "<div class='status error'><span class='icon'>❌</span> <strong>Write test failed:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // Connection details summary
    echo "<h2>✅ Connection Summary</h2>";
    echo "<div class='status success'><span class='icon'>🎉</span> <strong>Railway database is working perfectly!</strong></div>";
    
    echo "<pre>";
    echo "Database Host: $host\n";
    echo "Database Port: $port\n";
    echo "Database Name: $database\n";
    echo "Database User: $username\n";
    echo "MySQL Version: {$version['version']}\n";
    echo "Total Tables:  " . count($tables) . "\n";
    echo "</pre>";
    
    echo "<a href='/public/setup-realtime.php' class='btn'>Run Database Migration →</a>";
    echo "<a href='/pages/browse.html' class='btn' style='margin-left: 10px;'>Go to App →</a>";
    
} catch (PDOException $e) {
    echo "<div class='status error'><span class='icon'>❌</span> <strong>Connection Failed!</strong></div>";
    echo "<pre>Error: " . htmlspecialchars($e->getMessage()) . "</pre>";
    
    echo "<div class='info' style='margin-top: 30px;'>
        <h3>🔧 Troubleshooting Steps:</h3>
        <ol>
            <li><strong>Check Railway MySQL Service:</strong>
                <ul>
                    <li>Go to Railway dashboard</li>
                    <li>Verify MySQL service is running (green status)</li>
                    <li>Click on MySQL service to see connection details</li>
                </ul>
            </li>
            <li><strong>Verify Environment Variables:</strong>
                <ul>
                    <li>In your web service, go to 'Variables' tab</li>
                    <li>Make sure all DB_* variables are set correctly</li>
                    <li>Variables should match your MySQL service credentials</li>
                </ul>
            </li>
            <li><strong>Check Network:</strong>
                <ul>
                    <li>Ensure MySQL service allows connections from your web service</li>
                    <li>Both services should be in the same Railway project</li>
                </ul>
            </li>
            <li><strong>Create New MySQL Database (if needed):</strong>
                <ol>
                    <li>In Railway dashboard, click '+ New'</li>
                    <li>Select 'Database' → 'Add MySQL'</li>
                    <li>Wait for it to deploy</li>
                    <li>Click on the new MySQL service</li>
                    <li>Copy the connection details to your web service variables</li>
                </ol>
            </li>
        </ol>
    </div>";
}

echo "</div>
</body>
</html>";
