<?php
/**
 * Check demo users and reset passwords if needed
 */
session_start();
require_once __DIR__ . '/../config/db.php';

echo "<!DOCTYPE html><html><head><title>Check Demo Users</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:40px auto;padding:20px;background:#f8f9fa}";
echo "h1{color:#667eea}.success{color:#28a745;background:#d4edda;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #28a745}";
echo ".error{color:#dc3545;background:#f8d7da;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #dc3545}";
echo ".info{color:#004085;background:#cce5ff;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #004085}";
echo "table{width:100%;border-collapse:collapse;margin:15px 0}th,td{padding:10px;border:1px solid #ddd;text-align:left}th{background:#667eea;color:white}";
echo ".btn{background:#667eea;color:white;padding:12px 24px;border:none;cursor:pointer;border-radius:8px;font-weight:bold;margin:10px 5px;text-decoration:none;display:inline-block}";
echo ".btn:hover{background:#5568d3}</style></head><body>";

echo "<h1>👥 Demo Users Status</h1>";

try {
    // Check if Sarah and John exist
    $stmt = $conn->prepare("SELECT id, email, full_name, is_verified, created_at FROM users WHERE email IN (?, ?)");
    $stmt->execute(['sarah.student@ashesi.edu.gh', 'john.prof@ashesi.edu.gh']);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "<div class='success'>";
        echo "<h3>✅ Found " . count($users) . " Demo Users</h3>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Email</th><th>Name</th><th>Verified</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['email']}</td>";
            echo "<td>{$user['full_name']}</td>";
            echo "<td>" . ($user['is_verified'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$user['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
        
        // Check items
        $userIds = array_column($users, 'id');
        $placeholders = str_repeat('?,', count($userIds) - 1) . '?';
        $stmt = $conn->prepare("SELECT id, title, user_id FROM items WHERE user_id IN ($placeholders)");
        $stmt->execute($userIds);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<div class='info'>";
        echo "<h3>📦 Items Created: " . count($items) . "</h3>";
        if (count($items) > 0) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Title</th><th>Owner ID</th></tr>";
            foreach ($items as $item) {
                echo "<tr><td>{$item['id']}</td><td>{$item['title']}</td><td>{$item['user_id']}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ No items found! Click button below to create items.</p>";
        }
        echo "</div>";
        
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ Demo users not found!</h3>";
        echo "<p>Sarah and John don't exist in the database yet.</p>";
        echo "</div>";
    }
    
    // Reset password option
    if (isset($_GET['reset_passwords'])) {
        echo "<div class='info'><h3>🔄 Resetting Passwords...</h3></div>";
        
        $newPassword = password_hash('Demo123!', PASSWORD_BCRYPT);
        
        $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
        $stmt->execute([$newPassword, 'sarah.student@ashesi.edu.gh']);
        $updated1 = $stmt->rowCount();
        
        $stmt->execute([$newPassword, 'john.prof@ashesi.edu.gh']);
        $updated2 = $stmt->rowCount();
        
        if ($updated1 > 0 || $updated2 > 0) {
            echo "<div class='success'>";
            echo "<h3>✅ Passwords Reset Successfully!</h3>";
            echo "<p>Both accounts now use password: <strong>Demo123!</strong></p>";
            echo "<p>You can now login with:</p>";
            echo "<p>📧 <strong>Sarah:</strong> sarah.student@ashesi.edu.gh / Demo123!<br>";
            echo "📧 <strong>John:</strong> john.prof@ashesi.edu.gh / Demo123!</p>";
            echo "</div>";
        } else {
            echo "<div class='error'><p>No passwords were updated. Users may not exist.</p></div>";
        }
    }
    
    echo "<div style='margin-top:30px'>";
    echo "<a href='?reset_passwords=yes' class='btn'>🔐 Reset Passwords to Demo123!</a>";
    echo "<a href='/create-demo-users.php' class='btn'>➕ Create Missing Items</a>";
    echo "<a href='/pages/login.html' class='btn' style='background:#28a745'>🔐 Go to Login</a>";
    echo "<a href='/check-database.php' class='btn' style='background:#17a2b8'>🔍 Check Database</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
