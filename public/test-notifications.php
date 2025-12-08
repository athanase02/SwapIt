<?php
/**
 * Test Railway Database Connection and Create Sample Notifications
 * Run this from: https://your-render-url.onrender.com/test-notifications.php
 */

session_start();

// Display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔧 Railway Database Test & Notification Generator</h1>";
echo "<style>body{font-family:Arial,sans-serif;max-width:1200px;margin:40px auto;padding:20px}
.success{color:green;font-weight:bold}.error{color:red;font-weight:bold}
table{border-collapse:collapse;width:100%;margin:20px 0}
th,td{border:1px solid #ddd;padding:12px;text-align:left}
th{background:#4CAF50;color:white}.btn{background:#007bff;color:white;padding:10px 20px;border:none;cursor:pointer;margin:5px}
.btn:hover{background:#0056b3}</style>";

// Step 1: Check database connection
echo "<h2>📡 Step 1: Testing Railway Database Connection</h2>";

require_once __DIR__ . '/config/db.php';

if (isset($conn)) {
    echo "<p class='success'>✅ Database connected successfully!</p>";
    
    // Get database info
    $stmt = $conn->query("SELECT DATABASE() as db_name");
    $dbInfo = $stmt->fetch();
    echo "<p><strong>Database:</strong> " . htmlspecialchars($dbInfo['db_name']) . "</p>";
    
    // Step 2: Check if notification table exists
    echo "<h2>📋 Step 2: Checking Tables</h2>";
    
    $tables = ['users', 'notifications', 'messages', 'borrow_requests', 'conversations'];
    echo "<table><tr><th>Table Name</th><th>Status</th><th>Row Count</th></tr>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            $count = $result['count'];
            echo "<tr><td>$table</td><td class='success'>✅ Exists</td><td>$count rows</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td>$table</td><td class='error'>❌ Missing</td><td>N/A</td></tr>";
        }
    }
    echo "</table>";
    
    // Step 3: Get user list
    echo "<h2>👥 Step 3: Available Users</h2>";
    
    try {
        $stmt = $conn->query("SELECT id, email, full_name FROM users LIMIT 10");
        $users = $stmt->fetchAll();
        
        if (count($users) > 0) {
            echo "<table><tr><th>User ID</th><th>Email</th><th>Full Name</th><th>Action</th></tr>";
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
                echo "<td><button class='btn' onclick=\"createTestNotifications(" . $user['id'] . ")\">Create Test Notifications</button></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ No users found in database!</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error fetching users: " . $e->getMessage() . "</p>";
    }
    
    // Step 4: Show existing notifications
    echo "<h2>🔔 Step 4: Current Notifications</h2>";
    
    try {
        $stmt = $conn->query("SELECT n.*, u.email as user_email 
                              FROM notifications n 
                              LEFT JOIN users u ON n.user_id = u.id 
                              ORDER BY n.created_at DESC 
                              LIMIT 20");
        $notifications = $stmt->fetchAll();
        
        if (count($notifications) > 0) {
            echo "<table><tr><th>ID</th><th>User</th><th>Type</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>";
            foreach ($notifications as $notif) {
                $readStatus = $notif['is_read'] ? '✅ Read' : '🔴 Unread';
                echo "<tr>";
                echo "<td>" . htmlspecialchars($notif['id']) . "</td>";
                echo "<td>" . htmlspecialchars($notif['user_email']) . "</td>";
                echo "<td>" . htmlspecialchars($notif['type']) . "</td>";
                echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
                echo "<td>$readStatus</td>";
                echo "<td>" . htmlspecialchars($notif['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ No notifications found! Let's create some test data.</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error fetching notifications: " . $e->getMessage() . "</p>";
    }
    
    // Step 5: Create test notifications if requested
    if (isset($_GET['create_for_user'])) {
        echo "<h2>✨ Step 5: Creating Test Notifications</h2>";
        
        $targetUserId = (int)$_GET['create_for_user'];
        
        $testNotifications = [
            [
                'type' => 'message',
                'title' => 'New Message',
                'message' => 'John Doe sent you a message: "Hey, is your laptop still available?"',
                'related_type' => 'conversation',
                'related_id' => 1
            ],
            [
                'type' => 'request',
                'title' => 'Request Approved',
                'message' => 'Your borrow request for "Physics Textbook" has been approved!',
                'related_type' => 'borrow_request',
                'related_id' => 1
            ],
            [
                'type' => 'transaction',
                'title' => 'Transaction Confirmed',
                'message' => 'Sarah confirmed receiving your item. Transaction complete!',
                'related_type' => 'transaction',
                'related_id' => 1
            ],
            [
                'type' => 'meeting',
                'title' => 'Meeting Reminder',
                'message' => 'You have a meeting scheduled tomorrow at 3:00 PM at the Library.',
                'related_type' => 'meeting',
                'related_id' => 1
            ],
            [
                'type' => 'rating',
                'title' => 'New Rating',
                'message' => 'Mike gave you 5 stars! "Great borrower, highly recommended!"',
                'related_type' => 'rating',
                'related_id' => 1
            ]
        ];
        
        echo "<p>Creating test notifications for User ID: $targetUserId</p>";
        
        $created = 0;
        foreach ($testNotifications as $notif) {
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO notifications (user_id, type, title, message, related_type, related_id, is_read, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, 0, NOW())"
                );
                $stmt->execute([
                    $targetUserId,
                    $notif['type'],
                    $notif['title'],
                    $notif['message'],
                    $notif['related_type'],
                    $notif['related_id']
                ]);
                $created++;
                echo "<p class='success'>✅ Created: " . htmlspecialchars($notif['title']) . "</p>";
            } catch (Exception $e) {
                echo "<p class='error'>❌ Failed to create notification: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p class='success'><strong>✅ Successfully created $created test notifications!</strong></p>";
        echo "<p><a href='test-notifications.php'><button class='btn'>Refresh Page</button></a></p>";
        echo "<p><strong>Now try these:</strong></p>";
        echo "<ol>";
        echo "<li>Login as User ID $targetUserId</li>";
        echo "<li>Go to Dashboard or Messages page</li>";
        echo "<li>Look at the notification bell in top-right corner</li>";
        echo "<li>You should see a red badge with '5' (5 unread notifications)</li>";
        echo "<li>Click the bell to see the notification panel</li>";
        echo "</ol>";
    }
    
} else {
    echo "<p class='error'>❌ Failed to connect to database!</p>";
}

// JavaScript for button clicks
echo "<script>
function createTestNotifications(userId) {
    if (confirm('Create 5 test notifications for User ID ' + userId + '?')) {
        window.location.href = 'test-notifications.php?create_for_user=' + userId;
    }
}
</script>";

echo "<hr>";
echo "<h2>📖 Instructions</h2>";
echo "<ol>";
echo "<li><strong>Database Connection:</strong> Check if Railway MySQL is connected</li>";
echo "<li><strong>Table Status:</strong> Verify all required tables exist</li>";
echo "<li><strong>Create Test Data:</strong> Click button to generate sample notifications</li>";
echo "<li><strong>Test Frontend:</strong> Login and check notification bell</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>💡 Tip:</strong> After creating test notifications, go to your dashboard and you'll see the notification bell with a red badge showing the unread count!</p>";
?>
