<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Diagnostic</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border-left: 5px solid #667eea;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        .warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        .info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
        }
        h2 {
            color: #333;
            margin-top: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .code {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        .value {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-family: monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background: #667eea;
            color: white;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin: 10px 5px;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            background: #5568d3;
        }
        .step {
            margin: 15px 0;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }
        .step h4 {
            color: #667eea;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Connection Diagnostic</h1>
        <p class="subtitle">Let's find your Railway MySQL database</p>

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check environment variables
$envVars = [
    'DB_HOST' => getenv('DB_HOST'),
    'DB_PORT' => getenv('DB_PORT'),
    'DB_NAME' => getenv('DB_NAME'),
    'DB_USER' => getenv('DB_USER'),
    'DB_PASSWORD' => getenv('DB_PASSWORD') ? '***' . substr(getenv('DB_PASSWORD'), -4) : null,
    'MYSQL_URL' => getenv('MYSQL_URL') ? 'Set (URL format)' : null,
    'DATABASE_URL' => getenv('DATABASE_URL') ? 'Set (URL format)' : null,
];

echo "<div class='section info'>";
echo "<h2>📊 Current Environment</h2>";
echo "<p><strong>Server:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'Unknown') . "</p>";
echo "<p><strong>Environment:</strong> " . (getenv('RAILWAY_ENVIRONMENT') ?: (getenv('RENDER') ? 'Render' : 'Local')) . "</p>";
echo "</div>";

// Display environment variables
echo "<div class='section'>";
echo "<h2>🔐 Environment Variables</h2>";
echo "<table>";
echo "<tr><th>Variable</th><th>Value</th><th>Status</th></tr>";

$hasRailwayVars = false;
foreach ($envVars as $key => $value) {
    $status = $value ? '✅ Set' : '❌ Not Set';
    $class = $value ? '' : 'style="color:#dc3545"';
    echo "<tr><td><code>$key</code></td><td>" . ($value ?: '<em>Not set</em>') . "</td><td $class>$status</td></tr>";
    if ($value && strpos($key, 'DB_') === 0) {
        $hasRailwayVars = true;
    }
}
echo "</table>";
echo "</div>";

// Try to connect
echo "<div class='section'>";
echo "<h2>🔌 Connection Test</h2>";

try {
    require_once __DIR__ . '/../config/db.php';
    
    if (isset($conn)) {
        echo "<div class='section success'>";
        echo "<h3>✅ Database Connected!</h3>";
        
        // Get connection info
        $dbInfo = $conn->query("SELECT DATABASE() as db_name, VERSION() as version")->fetch();
        echo "<p><strong>Database:</strong> <span class='value'>{$dbInfo['db_name']}</span></p>";
        echo "<p><strong>MySQL Version:</strong> <span class='value'>{$dbInfo['version']}</span></p>";
        
        // Count tables
        $tables = $conn->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p><strong>Tables:</strong> <span class='value'>" . count($tables) . " tables</span></p>";
        
        if (count($tables) > 0) {
            echo "<details style='margin-top:15px'>";
            echo "<summary style='cursor:pointer;color:#667eea;font-weight:600'>📋 Show All Tables (" . count($tables) . ")</summary>";
            echo "<ul>";
            foreach ($tables as $table) {
                $count = $conn->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
                echo "<li><code>$table</code> - <strong>$count rows</strong></li>";
            }
            echo "</ul>";
            echo "</details>";
        }
        
        echo "</div>";
        
    } else {
        throw new Exception("Connection object not created");
    }
    
} catch (Exception $e) {
    echo "<div class='section error'>";
    echo "<h3>❌ Connection Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";

// Railway Setup Instructions
if (!$hasRailwayVars) {
    echo "<div class='section warning'>";
    echo "<h2>⚠️ Railway Database Not Configured</h2>";
    echo "<p>Your environment variables are not set. Here's how to set up Railway MySQL:</p>";
    
    echo "<div class='step'>";
    echo "<h4>Step 1: Go to Railway Dashboard</h4>";
    echo "<p>Visit: <a href='https://railway.app/dashboard' target='_blank'>https://railway.app/dashboard</a></p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h4>Step 2: Select Your Project</h4>";
    echo "<p>Find your SwapIt project and click on it</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h4>Step 3: Add MySQL Database</h4>";
    echo "<p>1. Click <strong>\"+ New\"</strong> button<br>";
    echo "2. Select <strong>\"Database\"</strong><br>";
    echo "3. Choose <strong>\"Add MySQL\"</strong></p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h4>Step 4: Get Connection Details</h4>";
    echo "<p>1. Click on your MySQL service<br>";
    echo "2. Go to <strong>\"Variables\"</strong> tab<br>";
    echo "3. You'll see variables like:</p>";
    echo "<div class='code'>";
    echo "MYSQL_HOST=containers-us-west-xxx.railway.app<br>";
    echo "MYSQL_PORT=6543<br>";
    echo "MYSQL_DATABASE=railway<br>";
    echo "MYSQL_USER=root<br>";
    echo "MYSQL_PASSWORD=xxx<br>";
    echo "MYSQL_URL=mysql://root:xxx@containers-us-west-xxx.railway.app:6543/railway";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h4>Step 5: Connect to Your Web Service</h4>";
    echo "<p>1. Go back to your project view<br>";
    echo "2. Click on your <strong>Web Service</strong> (where SwapIt is deployed)<br>";
    echo "3. Go to <strong>\"Variables\"</strong> tab<br>";
    echo "4. Click <strong>\"+ New Variable\"</strong> and add these (using values from MySQL service):</p>";
    echo "<div class='code'>";
    echo "DB_HOST=containers-us-west-xxx.railway.app<br>";
    echo "DB_PORT=6543<br>";
    echo "DB_NAME=railway<br>";
    echo "DB_USER=root<br>";
    echo "DB_PASSWORD=your_mysql_password_here";
    echo "</div>";
    echo "<p>💡 <strong>Tip:</strong> Copy exact values from your MySQL service's variables</p>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h4>Step 6: Redeploy</h4>";
    echo "<p>1. After adding variables, Railway will automatically redeploy<br>";
    echo "2. Wait 2-3 minutes for deployment to complete<br>";
    echo "3. Refresh this page to see connection status</p>";
    echo "</div>";
    
    echo "</div>";
    
    echo "<div class='section info'>";
    echo "<h2>📝 Alternative: Use Connection String</h2>";
    echo "<p>If you prefer using MYSQL_URL connection string, Railway provides it automatically. Your <code>db.php</code> can parse it like:</p>";
    echo "<div class='code'>";
    echo "\$mysqlUrl = getenv('MYSQL_URL');<br>";
    echo "// Format: mysql://user:password@host:port/database<br>";
    echo "\$parsed = parse_url(\$mysqlUrl);<br>";
    echo "\$host = \$parsed['host'];<br>";
    echo "\$port = \$parsed['port'];<br>";
    echo "\$user = \$parsed['user'];<br>";
    echo "\$password = \$parsed['pass'];<br>";
    echo "\$database = ltrim(\$parsed['path'], '/');";
    echo "</div>";
    echo "</div>";
}

// Quick Actions
echo "<div class='section'>";
echo "<h2>🚀 Quick Actions</h2>";
echo "<a href='/create-demo-users.php' class='btn'>👥 Create Demo Users</a>";
echo "<a href='/complete-workflow-guide.html' class='btn'>📖 Workflow Guide</a>";
echo "<a href='?' class='btn' style='background:#28a745'>🔄 Refresh Page</a>";
echo "</div>";

?>

        <div class="section info">
            <h2>📚 Documentation</h2>
            <p><strong>Railway MySQL Docs:</strong> <a href="https://docs.railway.app/databases/mysql" target="_blank">https://docs.railway.app/databases/mysql</a></p>
            <p><strong>Need Help?</strong> Check Railway's dashboard for your MySQL service connection details</p>
        </div>
    </div>
</body>
</html>
