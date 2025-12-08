<?php
/**
 * SwapIt Database Seeder - Create Realistic Demo Data
 * This script will populate your database with conversations, messages, requests, and notifications
 * Run once from: https://your-render-url.onrender.com/seed-demo-data.php
 */

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>SwapIt Database Seeder</title>";
echo "<style>
body{font-family:Arial,sans-serif;max-width:1200px;margin:40px auto;padding:20px;background:#0a0b10;color:#ecf1ff}
h1{color:#7ef9ff;border-bottom:3px solid #7ef9ff;padding-bottom:10px}
h2{color:#5b7cfe;margin-top:30px}
.success{color:#7ef9ff;font-weight:bold;padding:10px;background:rgba(126,249,255,0.1);border-left:4px solid #7ef9ff;margin:10px 0}
.error{color:#ff6b6b;font-weight:bold;padding:10px;background:rgba(255,107,107,0.1);border-left:4px solid #ff6b6b;margin:10px 0}
.info{color:#ffd93d;padding:10px;background:rgba(255,217,61,0.1);border-left:4px solid #ffd93d;margin:10px 0}
table{border-collapse:collapse;width:100%;margin:20px 0;background:#151824}
th,td{border:1px solid #1e2330;padding:12px;text-align:left}
th{background:#1a1d2e;color:#7ef9ff}
.btn{background:#7ef9ff;color:#0a0b10;padding:12px 24px;border:none;cursor:pointer;margin:5px;font-weight:bold;border-radius:8px}
.btn:hover{background:#5b7cfe}
.progress{background:#1e2330;height:30px;border-radius:8px;overflow:hidden;margin:20px 0}
.progress-bar{background:linear-gradient(90deg,#7ef9ff,#5b7cfe);height:100%;text-align:center;line-height:30px;color:#0a0b10;font-weight:bold;transition:width 0.3s}
.section{background:#151824;padding:20px;margin:20px 0;border-radius:12px;border:1px solid #1e2330}
</style></head><body>";

echo "<h1>🌟 SwapIt Database Seeder</h1>";
echo "<p class='info'>⚠️ This will create realistic demo data to test your messaging, requests, and notification system!</p>";

require_once __DIR__ . '/config/db.php';

if (!isset($conn)) {
    echo "<p class='error'>❌ Database connection failed!</p></body></html>";
    exit;
}

// Check if seeding was requested
if (!isset($_GET['confirm'])) {
    echo "<div class='section'>";
    echo "<h2>📋 What This Will Create:</h2>";
    echo "<ul>";
    echo "<li>✅ <strong>5-10 realistic items</strong> (laptops, textbooks, calculators, etc.)</li>";
    echo "<li>✅ <strong>10-15 borrow requests</strong> (pending, approved, rejected)</li>";
    echo "<li>✅ <strong>5-8 conversations</strong> between users</li>";
    echo "<li>✅ <strong>30-50 messages</strong> with realistic content</li>";
    echo "<li>✅ <strong>15-20 notifications</strong> for all users</li>";
    echo "<li>✅ <strong>5-8 meeting schedules</strong> with locations and times</li>";
    echo "<li>✅ <strong>User activities</strong> and online status tracking</li>";
    echo "</ul>";
    echo "<p class='info'>💡 Existing data will NOT be deleted. This adds new demo data.</p>";
    echo "<button class='btn' onclick=\"window.location.href='seed-demo-data.php?confirm=yes'\">🚀 Start Seeding Database</button>";
    echo "<button class='btn' onclick=\"window.location.href='dashboard.html'\" style='background:#ff6b6b'>❌ Cancel</button>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

// Start seeding
echo "<div class='section'>";
echo "<h2>🚀 Seeding Database...</h2>";
echo "<div class='progress'><div class='progress-bar' id='progressBar' style='width:0%'>0%</div></div>";
echo "<div id='logOutput'></div>";
echo "</div>";

function logMessage($message, $type = 'info') {
    $class = $type === 'success' ? 'success' : ($type === 'error' ? 'error' : 'info');
    echo "<script>document.getElementById('logOutput').innerHTML += '<p class=\"$class\">$message</p>';</script>";
    flush();
    ob_flush();
}

function updateProgress($percent) {
    echo "<script>document.getElementById('progressBar').style.width='$percent%';document.getElementById('progressBar').textContent='$percent%';</script>";
    flush();
    ob_flush();
}

ob_start();
ob_implicit_flush(true);

// Get all users
$stmt = $conn->query("SELECT id, email, full_name FROM users ORDER BY id");
$users = $stmt->fetchAll();

if (count($users) < 2) {
    logMessage("❌ Need at least 2 users to create conversations!", 'error');
    echo "</body></html>";
    exit;
}

logMessage("✅ Found " . count($users) . " users", 'success');
updateProgress(10);

// Step 1: Create Items
logMessage("<h3>📦 Step 1: Creating Items...</h3>", 'info');

$items = [
    ['name' => 'MacBook Pro 2021', 'description' => 'Excellent condition, 16GB RAM, 512GB SSD. Perfect for programming and design work.', 'category' => 'Electronics', 'condition' => 'Excellent', 'price' => 50],
    ['name' => 'Physics Textbook', 'description' => 'University Physics 14th Edition. Like new condition with minimal highlighting.', 'category' => 'Books', 'condition' => 'Good', 'price' => 15],
    ['name' => 'Scientific Calculator', 'description' => 'TI-84 Plus CE. All functions working perfectly. Includes manual and case.', 'category' => 'Electronics', 'condition' => 'Excellent', 'price' => 20],
    ['name' => 'Bicycle', 'description' => 'Mountain bike, 21-speed. Great for campus commuting. Recently serviced.', 'category' => 'Sports', 'condition' => 'Good', 'price' => 30],
    ['name' => 'Desk Lamp', 'description' => 'LED desk lamp with adjustable brightness. Energy efficient and modern design.', 'category' => 'Furniture', 'condition' => 'Excellent', 'price' => 10],
    ['name' => 'Python Programming Book', 'description' => 'Learn Python the Hard Way. Excellent resource for beginners.', 'category' => 'Books', 'condition' => 'Good', 'price' => 12],
    ['name' => 'Gaming Mouse', 'description' => 'Logitech G502 HERO. RGB lighting, programmable buttons. Like new.', 'category' => 'Electronics', 'condition' => 'Excellent', 'price' => 25],
    ['name' => 'Study Chair', 'description' => 'Ergonomic office chair. Adjustable height and lumbar support.', 'category' => 'Furniture', 'condition' => 'Good', 'price' => 35]
];

$createdItems = [];
foreach ($items as $item) {
    $owner = $users[array_rand($users)];
    try {
        $stmt = $conn->prepare(
            "INSERT INTO items (user_id, title, description, category, item_condition, rental_price, is_available, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, 1, NOW())"
        );
        $stmt->execute([
            $owner['id'],
            $item['name'],
            $item['description'],
            $item['category'],
            $item['condition'],
            $item['price']
        ]);
        $itemId = $conn->lastInsertId();
        $createdItems[] = ['id' => $itemId, 'name' => $item['name'], 'owner_id' => $owner['id'], 'owner_name' => $owner['full_name']];
        logMessage("✅ Created: {$item['name']} (Owner: {$owner['full_name']})", 'success');
    } catch (Exception $e) {
        logMessage("⚠️ Skipped: {$item['name']} - " . $e->getMessage(), 'error');
    }
}

updateProgress(30);

// Step 2: Create Borrow Requests
logMessage("<h3>🔄 Step 2: Creating Borrow Requests...</h3>", 'info');

$statuses = ['pending', 'approved', 'rejected', 'completed'];
$createdRequests = [];

for ($i = 0; $i < 15; $i++) {
    if (empty($createdItems)) break;
    
    $item = $createdItems[array_rand($createdItems)];
    $borrower = $users[array_rand($users)];
    
    // Don't let owner borrow their own item
    if ($borrower['id'] == $item['owner_id']) continue;
    
    $status = $statuses[array_rand($statuses)];
    $startDate = date('Y-m-d', strtotime('+' . rand(1, 30) . ' days'));
    $endDate = date('Y-m-d', strtotime($startDate . ' +' . rand(3, 14) . ' days'));
    
    try {
        $stmt = $conn->prepare(
            "INSERT INTO borrow_requests (item_id, borrower_id, owner_id, start_date, end_date, status, message, created_at) 
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([
            $item['id'],
            $borrower['id'],
            $item['owner_id'],
            $startDate,
            $endDate,
            $status,
            "Hi! I'd like to borrow this item. I'll take good care of it!"
        ]);
        $requestId = $conn->lastInsertId();
        $createdRequests[] = [
            'id' => $requestId,
            'item_name' => $item['name'],
            'borrower_id' => $borrower['id'],
            'borrower_name' => $borrower['full_name'],
            'owner_id' => $item['owner_id'],
            'owner_name' => $item['owner_name'],
            'status' => $status
        ];
        logMessage("✅ Request: {$borrower['full_name']} wants to borrow {$item['name']} (Status: $status)", 'success');
    } catch (Exception $e) {
        logMessage("⚠️ Failed to create request: " . $e->getMessage(), 'error');
    }
}

updateProgress(50);

// Step 3: Create Conversations
logMessage("<h3>💬 Step 3: Creating Conversations...</h3>", 'info');

$createdConversations = [];
$conversationCount = min(8, count($createdRequests));

for ($i = 0; $i < $conversationCount; $i++) {
    $request = $createdRequests[array_rand($createdRequests)];
    
    try {
        $stmt = $conn->prepare(
            "INSERT INTO conversations (user1_id, user2_id, item_id, last_message_at, created_at) 
             VALUES (?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $request['borrower_id'],
            $request['owner_id'],
            $request['id']
        ]);
        $conversationId = $conn->lastInsertId();
        $createdConversations[] = [
            'id' => $conversationId,
            'user1_id' => $request['borrower_id'],
            'user1_name' => $request['borrower_name'],
            'user2_id' => $request['owner_id'],
            'user2_name' => $request['owner_name'],
            'item_name' => $request['item_name']
        ];
        logMessage("✅ Conversation: {$request['borrower_name']} ↔️ {$request['owner_name']} about {$request['item_name']}", 'success');
    } catch (Exception $e) {
        logMessage("⚠️ Failed to create conversation: " . $e->getMessage(), 'error');
    }
}

updateProgress(65);

// Step 4: Create Messages
logMessage("<h3>📨 Step 4: Creating Messages...</h3>", 'info');

$messageTemplates = [
    "Hi! Is this item still available?",
    "Yes, it's available! When do you need it?",
    "I need it starting next week for about 5 days.",
    "That works for me! Where should we meet?",
    "How about the library at 3 PM tomorrow?",
    "Perfect! See you then 😊",
    "Thanks! I'll take good care of it.",
    "No problem! Let me know if you have any questions.",
    "Just wanted to confirm - we're still on for tomorrow?",
    "Yes! Looking forward to it.",
    "Item is in great condition, thank you!",
    "You're welcome! Enjoy using it!"
];

foreach ($createdConversations as $conv) {
    $messageCount = rand(3, 8);
    $currentSender = $conv['user1_id'];
    
    for ($i = 0; $i < $messageCount; $i++) {
        $message = $messageTemplates[array_rand($messageTemplates)];
        $timestamp = date('Y-m-d H:i:s', strtotime('-' . (20 - $i) . ' hours'));
        
        try {
            $stmt = $conn->prepare(
                "INSERT INTO messages (conversation_id, sender_id, message, created_at) 
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $conv['id'],
                $currentSender,
                $message,
                $timestamp
            ]);
            
            // Update conversation last message
            $conn->prepare("UPDATE conversations SET last_message_at = ? WHERE id = ?")->execute([$timestamp, $conv['id']]);
            
            // Alternate sender
            $currentSender = ($currentSender == $conv['user1_id']) ? $conv['user2_id'] : $conv['user1_id'];
        } catch (Exception $e) {
            // Silently skip duplicates
        }
    }
    logMessage("✅ Created {$messageCount} messages in conversation about {$conv['item_name']}", 'success');
}

updateProgress(80);

// Step 5: Create Notifications for All Users
logMessage("<h3>🔔 Step 5: Creating Notifications...</h3>", 'info');

foreach ($users as $user) {
    $notifications = [
        ['type' => 'message', 'title' => 'New Message', 'message' => 'You have a new message about your item listing.'],
        ['type' => 'request', 'title' => 'New Request', 'message' => 'Someone wants to borrow your item!'],
        ['type' => 'request', 'title' => 'Request Approved', 'message' => 'Your borrow request has been approved! 🎉'],
        ['type' => 'meeting', 'title' => 'Meeting Scheduled', 'message' => 'Meeting at Library tomorrow at 3:00 PM'],
        ['type' => 'system', 'title' => 'Welcome to SwapIt!', 'message' => 'Thanks for being part of our community! 🌟']
    ];
    
    foreach ($notifications as $notif) {
        try {
            $stmt = $conn->prepare(
                "INSERT INTO notifications (user_id, type, title, message, related_type, related_id, is_read, created_at) 
                 VALUES (?, ?, ?, ?, 'system', 1, 0, NOW())"
            );
            $stmt->execute([
                $user['id'],
                $notif['type'],
                $notif['title'],
                $notif['message']
            ]);
        } catch (Exception $e) {
            // Skip duplicates
        }
    }
    logMessage("✅ Created notifications for {$user['full_name']}", 'success');
}

updateProgress(95);

// Step 6: Create Meeting Schedules
logMessage("<h3>📅 Step 6: Creating Meeting Schedules...</h3>", 'info');

$locations = ['Library', 'Cafeteria', 'Student Center', 'Main Gate', 'Engineering Building'];
foreach ($createdRequests as $request) {
    if ($request['status'] === 'approved') {
        $meetingTime = date('Y-m-d H:i:s', strtotime('+' . rand(1, 7) . ' days ' . rand(9, 17) . ':00'));
        $location = $locations[array_rand($locations)];
        
        try {
            $stmt = $conn->prepare(
                "INSERT INTO meeting_schedules (request_id, borrower_id, owner_id, meeting_time, location, status, created_at) 
                 VALUES (?, ?, ?, ?, ?, 'scheduled', NOW())"
            );
            $stmt->execute([
                $request['id'],
                $request['borrower_id'],
                $request['owner_id'],
                $meetingTime,
                $location
            ]);
            logMessage("✅ Meeting scheduled at $location", 'success');
        } catch (Exception $e) {
            // Skip
        }
    }
}

updateProgress(100);

// Summary
echo "<div class='section'>";
echo "<h2>✅ Seeding Complete!</h2>";

$stmt = $conn->query("SELECT COUNT(*) as count FROM items");
$itemCount = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM borrow_requests");
$requestCount = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM conversations");
$convCount = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM messages");
$msgCount = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM notifications");
$notifCount = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM meeting_schedules");
$meetingCount = $stmt->fetch()['count'];

echo "<table>";
echo "<tr><th>Category</th><th>Count</th></tr>";
echo "<tr><td>📦 Items</td><td>$itemCount</td></tr>";
echo "<tr><td>🔄 Borrow Requests</td><td>$requestCount</td></tr>";
echo "<tr><td>💬 Conversations</td><td>$convCount</td></tr>";
echo "<tr><td>📨 Messages</td><td>$msgCount</td></tr>";
echo "<tr><td>🔔 Notifications</td><td>$notifCount</td></tr>";
echo "<tr><td>📅 Meeting Schedules</td><td>$meetingCount</td></tr>";
echo "</table>";

echo "<h3>🎉 Now Try This:</h3>";
echo "<ol>";
echo "<li><strong>Login</strong> as any user from your database</li>";
echo "<li><strong>Go to Messages page</strong> - You'll see active conversations!</li>";
echo "<li><strong>Go to Requests page</strong> - You'll see pending/approved requests!</li>";
echo "<li><strong>Check notification bell</strong> - Red badge showing unread count!</li>";
echo "<li><strong>Click notifications</strong> - See all alerts and navigate to relevant pages!</li>";
echo "<li><strong>Send new messages</strong> - Real-time typing indicators work!</li>";
echo "</ol>";

echo "<p class='success'>🚀 Your SwapIt platform is now fully populated with realistic data!</p>";
echo "<button class='btn' onclick=\"window.location.href='dashboard.html'\">📊 Go to Dashboard</button>";
echo "<button class='btn' onclick=\"window.location.href='messages.html'\" style='background:#5b7cfe'>💬 Go to Messages</button>";
echo "</div>";

echo "</body></html>";
?>
