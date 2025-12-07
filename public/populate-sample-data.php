<?php
/**
 * Populate Sample Data for SwapIt
 * Creates sample messages and requests for testing
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$results = [
    'success' => false,
    'messages_created' => 0,
    'conversations_created' => 0,
    'requests_created' => 0,
    'errors' => []
];

try {
    if (!isset($conn)) {
        throw new Exception("Database connection not available");
    }

    // Get existing users
    $stmt = $conn->query("SELECT id, full_name, email FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) < 2) {
        throw new Exception("Need at least 2 users in database. Please create users first.");
    }

    // Get existing items
    $items = [];
    try {
        $stmt = $conn->query("SELECT id, title, price_per_day as price, user_id FROM active_listings LIMIT 20");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $items = [
            ['id' => 1, 'title' => 'Laptop', 'price' => 50.00, 'user_id' => $users[0]['id']],
            ['id' => 2, 'title' => 'Camera', 'price' => 30.00, 'user_id' => $users[1 % count($users)]['id']],
            ['id' => 3, 'title' => 'Textbook', 'price' => 10.00, 'user_id' => $users[0]['id']],
            ['id' => 4, 'title' => 'Bicycle', 'price' => 20.00, 'user_id' => $users[1 % count($users)]['id']],
            ['id' => 5, 'title' => 'Projector', 'price' => 40.00, 'user_id' => $users[0]['id']],
        ];
    }

    $results['users_found'] = count($users);
    $results['items_found'] = count($items);

    // Sample messages
    $sampleMessages = [
        "Hi! I'm interested in borrowing this item.",
        "Sure! When would you need it?",
        "I was thinking next week, from Monday to Friday.",
        "That works for me! Let's arrange a meeting time.",
        "Great! How about Monday at 2 PM?",
        "Perfect! See you then.",
        "Thanks for lending this to me!",
        "No problem! Enjoy using it.",
        "Just wanted to confirm our meeting tomorrow.",
        "Yes, still on for 2 PM at the campus library.",
        "Is this still available?",
        "Yes, it's available! What dates do you need it?",
        "I need it for the weekend.",
        "That should work. Let me check my schedule.",
        "Any update on availability?",
        "Sorry for the delay. Yes, it's available!",
        "Awesome! I'll send a request.",
        "Looking forward to it!",
        "Can I see more pictures of this item?",
        "Sure, I'll send them shortly."
    ];

    $conversationsCreated = 0;
    $messagesCreated = 0;

    // Create 5 conversations with 5 messages each
    for ($i = 0; $i < 5; $i++) {
        $user1_id = $users[$i % count($users)]['id'];
        $user2_id = $users[($i + 1) % count($users)]['id'];
        $item_id = isset($items[$i % count($items)]['id']) ? $items[$i % count($items)]['id'] : null;

        // Check if conversation exists
        $stmt = $conn->prepare("SELECT id FROM conversations WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
        $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
        $existingConv = $stmt->fetch();

        if ($existingConv) {
            $conversation_id = $existingConv['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO conversations (user1_id, user2_id, item_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
            $stmt->execute([$user1_id, $user2_id, $item_id]);
            $conversation_id = $conn->lastInsertId();
            $conversationsCreated++;
        }

        // Create 5 messages
        for ($m = 0; $m < 5; $m++) {
            $sender_id = ($m % 2 === 0) ? $user1_id : $user2_id;
            $receiver_id = ($m % 2 === 0) ? $user2_id : $user1_id;
            $message_text = $sampleMessages[($i * 5 + $m) % count($sampleMessages)];
            $is_read = ($m < 3 ? 1 : 0);
            $hours_ago = (5 - $m);
            
            $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, receiver_id, message_text, item_id, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))");
            $stmt->execute([$conversation_id, $sender_id, $receiver_id, $message_text, $item_id, $is_read, $hours_ago]);
            $messagesCreated++;
        }

        $stmt = $conn->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = ?");
        $stmt->execute([$conversation_id]);
    }

    $results['conversations_created'] = $conversationsCreated;
    $results['messages_created'] = $messagesCreated;

    // Create borrow requests
    $requestsCreated = 0;
    $userIds = array_column($users, 'id');
    
    // 5 SENT requests
    for ($i = 0; $i < 5; $i++) {
        $borrower_id = $userIds[0];
        $item = $items[$i % count($items)];
        $lender_id = isset($item['user_id']) && $item['user_id'] != $borrower_id ? $item['user_id'] : $userIds[1];
        $item_price = isset($item['price']) ? $item['price'] : 50.00;
        $item_id = isset($item['id']) ? $item['id'] : ($i + 1);
        
        $start_days = (7 + $i);
        $end_days = (12 + $i);
        $total_price = $item_price * 0.2;
        $created_days = $i;
        
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status, created_at) VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))");
        $stmt->execute([$item_id, $borrower_id, $lender_id, $start_days, $end_days, $total_price, 20.00, 'Ashesi University Campus', "Hi! I'd like to borrow this for a few days. Available?", 'pending', $created_days]);
        $requestsCreated++;
    }

    // 5 RECEIVED requests
    for ($i = 0; $i < 5; $i++) {
        $lender_id = $userIds[0];
        $borrower_id = $userIds[($i + 1) % count($userIds)];
        $item = $items[(5 + $i) % count($items)];
        $item_price = isset($item['price']) ? $item['price'] : 40.00;
        $item_id = isset($item['id']) ? $item['id'] : (6 + $i);
        
        $start_days = (3 + $i);
        $end_days = (8 + $i);
        $total_price = $item_price * 0.15;
        $created_hours = ($i * 6);
        
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status, created_at) VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))");
        $stmt->execute([$item_id, $borrower_id, $lender_id, $start_days, $end_days, $total_price, 15.00, 'Campus Library', "Hello! Interested in borrowing this. Is it available?", 'pending', $created_hours]);
        $requestsCreated++;
    }

    // 5 ACTIVE requests with meetings
    for ($i = 0; $i < 5; $i++) {
        $borrower_id = $userIds[0];
        $item = $items[(10 + $i) % count($items)];
        $lender_id = isset($item['user_id']) && $item['user_id'] != $borrower_id ? $item['user_id'] : $userIds[1];
        $item_price = isset($item['price']) ? $item['price'] : 60.00;
        $item_id = isset($item['id']) ? $item['id'] : (11 + $i);
        
        $start_days_ago = (2 + $i);
        $end_days_future = (3 + $i);
        $total_price = $item_price * 0.25;
        $created_days = (7 + $i);
        
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status, created_at, updated_at) VALUES (?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY), ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), NOW())");
        $stmt->execute([$item_id, $borrower_id, $lender_id, $start_days_ago, $end_days_future, $total_price, 25.00, 'Student Center', "Need this for a project. Thanks!", 'active', $created_days]);
        $requestId = $conn->lastInsertId();
        $requestsCreated++;

        $meeting_days_ago = (2 + $i);
        $stmt = $conn->prepare("INSERT INTO meeting_schedules (borrow_request_id, scheduled_by, meeting_type, meeting_date, meeting_location, notes, meeting_status) VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY) + INTERVAL 14 HOUR, ?, ?, ?)");
        $stmt->execute([$requestId, $lender_id, 'offline', $meeting_days_ago, 'Student Center Main Entrance', 'Pickup confirmed', 'completed']);
    }

    // 5 COMPLETED requests with reviews
    for ($i = 0; $i < 5; $i++) {
        $borrower_id = $userIds[0];
        $item = $items[(15 + $i) % count($items)];
        $lender_id = isset($item['user_id']) && $item['user_id'] != $borrower_id ? $item['user_id'] : $userIds[1];
        $item_price = isset($item['price']) ? $item['price'] : 70.00;
        $item_id = isset($item['id']) ? $item['id'] : (16 + $i);
        
        $start_days_ago = (20 + $i * 2);
        $end_days_ago = (15 + $i * 2);
        $total_price = $item_price * 0.3;
        $created_days = (25 + $i * 2);
        
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status, created_at, updated_at) VALUES (?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), DATE_SUB(CURDATE(), INTERVAL ? DAY), ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), DATE_SUB(NOW(), INTERVAL ? DAY))");
        $stmt->execute([$item_id, $borrower_id, $lender_id, $start_days_ago, $end_days_ago, $total_price, 30.00, 'Library', "Great item! Would love to borrow it.", 'completed', $created_days]);
        $requestId = $conn->lastInsertId();
        $requestsCreated++;

        $rating = (4 + ($i % 2));
        $review_days = (14 + $i * 2);
        $stmt = $conn->prepare("INSERT INTO reviews (reviewer_id, reviewed_user_id, borrow_request_id, rating, review_type, title, comment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))");
        $stmt->execute([$borrower_id, $lender_id, $requestId, $rating, 'borrower_to_lender', 'Great Experience!', 'Item was in excellent condition. Lender was very responsive and professional.', $review_days]);
    }

    $results['requests_created'] = $requestsCreated;

    // Log activity
    try {
        $stmt = $conn->prepare("INSERT INTO user_activities (user_id, activity_type, description, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$userIds[0], 'sample_data', 'Sample data populated for testing']);
    } catch (Exception $e) {
        // Ignore if user_activities fails
    }

    $results['success'] = true;
    $results['message'] = 'Sample data created successfully!';

} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
