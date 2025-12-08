<?php
/**
 * Environment Variables Test
 * Tests if Railway environment variables are properly set
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Environment Test - SwapIt</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #0a0b10;
            color: #fff;
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
        }
        h1 { color: #7ef9ff; }
        .test-section {
            background: #1a1d2e;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            border: 1px solid #223050;
        }
        .success { color: #7ef9ff; }
        .error { color: #ff7df2; }
        .warning { color: #ffd700; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        td, th {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #223050;
        }
        th {
            color: #7ef9ff;
            font-weight: 600;
        }
        .status-ok { color: #7ef9ff; }
        .status-missing { color: #ff7df2; }
    </style>
</head>
<body>
    <h1>🔍 Environment Variables Test</h1>
    
    <div class="test-section">
        <h2>Railway MySQL Configuration</h2>
        <table>
            <tr>
                <th>Variable</th>
                <th>Status</th>
                <th>Value</th>
            </tr>
            <?php
            $requiredVars = [
                'RAILWAY_DB_HOST' => 'Database Host',
                'RAILWAY_DB_PORT' => 'Database Port',
                'RAILWAY_DB_USER' => 'Database User',
                'RAILWAY_DB_PASSWORD' => 'Database Password',
                'RAILWAY_DB_NAME' => 'Database Name'
            ];
            
            $allPresent = true;
            foreach ($requiredVars as $var => $label) {
                $value = getenv($var);
                $isSet = !empty($value);
                $allPresent = $allPresent && $isSet;
                
                echo '<tr>';
                echo '<td>' . htmlspecialchars($label) . '<br><small style="color: #666;">' . $var . '</small></td>';
                echo '<td class="' . ($isSet ? 'status-ok' : 'status-missing') . '">';
                echo $isSet ? '✅ Set' : '❌ Missing';
                echo '</td>';
                echo '<td>';
                if ($isSet) {
                    // Mask password
                    if ($var === 'RAILWAY_DB_PASSWORD') {
                        echo str_repeat('*', min(strlen($value), 20));
                    } else {
                        echo htmlspecialchars($value);
                    }
                } else {
                    echo '<em>Not set</em>';
                }
                echo '</td>';
                echo '</tr>';
            }
            ?>
        </table>
        
        <?php if ($allPresent): ?>
            <p class="success">✅ All Railway environment variables are configured!</p>
        <?php else: ?>
            <p class="error">❌ Some environment variables are missing. Please set them in Render dashboard.</p>
        <?php endif; ?>
    </div>

    <div class="test-section">
        <h2>Database Connection Test</h2>
        <?php
        if ($allPresent) {
            try {
                require_once dirname(__DIR__) . '/config/db.php';
                echo '<p class="success">✅ Database connection successful!</p>';
                
                // Test tables
                $tables = ['users', 'items', 'categories', 'borrow_requests', 'messages'];
                echo '<table>';
                echo '<tr><th>Table</th><th>Status</th><th>Count</th></tr>';
                
                foreach ($tables as $table) {
                    try {
                        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($table) . '</td>';
                        echo '<td class="status-ok">✅ Exists</td>';
                        echo '<td>' . $result['count'] . ' rows</td>';
                        echo '</tr>';
                    } catch (Exception $e) {
                        echo '<tr>';
                        echo '<td>' . htmlspecialchars($table) . '</td>';
                        echo '<td class="status-missing">❌ Missing</td>';
                        echo '<td>-</td>';
                        echo '</tr>';
                    }
                }
                echo '</table>';
                
            } catch (Exception $e) {
                echo '<p class="error">❌ Database connection failed!</p>';
                echo '<p class="error">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
        } else {
            echo '<p class="warning">⚠️ Cannot test database connection - environment variables not set.</p>';
        }
        ?>
    </div>

    <div class="test-section">
        <h2>Items API Test</h2>
        <div id="apiTest">
            <button onclick="testAPI()" style="background: linear-gradient(135deg, #7ef9ff, #5b7cfe); color: #0a0b10; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600;">
                Test Items API
            </button>
            <div id="apiResult" style="margin-top: 20px;"></div>
        </div>
    </div>

    <script>
        async function testAPI() {
            const resultDiv = document.getElementById('apiResult');
            resultDiv.innerHTML = '<p>Testing API endpoint...</p>';
            
            try {
                const response = await fetch('/api/items.php?action=get_all');
                const data = await response.json();
                
                if (data.success) {
                    resultDiv.innerHTML = `
                        <p class="success">✅ API is working!</p>
                        <p>Found <strong>${data.items.length}</strong> items in database</p>
                        <details style="margin-top: 15px;">
                            <summary style="cursor: pointer; color: #7ef9ff;">View sample item data</summary>
                            <pre style="background: #0f1117; padding: 15px; border-radius: 8px; overflow-x: auto; margin-top: 10px;">${JSON.stringify(data.items[0] || {}, null, 2)}</pre>
                        </details>
                    `;
                } else {
                    resultDiv.innerHTML = `<p class="error">❌ API Error: ${data.error || 'Unknown error'}</p>`;
                }
            } catch (error) {
                resultDiv.innerHTML = `<p class="error">❌ Fetch Error: ${error.message}</p>`;
            }
        }
    </script>

    <div class="test-section">
        <h2>Next Steps</h2>
        <?php if ($allPresent): ?>
            <p class="success">✅ Your environment is configured correctly!</p>
            <ul>
                <li>Visit <a href="/pages/browse.html" style="color: #7ef9ff;">Browse Page</a> to see items</li>
                <li>Open browser console (F12) to see debug logs</li>
                <li>Items should load automatically from Railway database</li>
            </ul>
        <?php else: ?>
            <p class="warning">⚠️ Configuration needed:</p>
            <ol style="line-height: 1.8;">
                <li>Go to your Render dashboard</li>
                <li>Select your SwapIt web service</li>
                <li>Click "Environment" in the sidebar</li>
                <li>Add these variables:
                    <ul style="margin-top: 10px; color: #7ef9ff; font-family: monospace;">
                        <li>RAILWAY_DB_HOST = turntable.proxy.rlwy.net</li>
                        <li>RAILWAY_DB_PORT = 57424</li>
                        <li>RAILWAY_DB_USER = root</li>
                        <li>RAILWAY_DB_PASSWORD = vgaxWyQewCgALyZTUSKazZyJbgykdJjF</li>
                        <li>RAILWAY_DB_NAME = railway</li>
                    </ul>
                </li>
                <li>Click "Save Changes"</li>
                <li>Wait for Render to redeploy (2-3 minutes)</li>
                <li>Refresh this page to verify</li>
            </ol>
        <?php endif; ?>
    </div>
</body>
</html>
