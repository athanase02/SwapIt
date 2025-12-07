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

    // Create conversations with messages for each user pair
    for ($i = 0; $i < min(5, count($users) - 1); $i++) {
        $user1_id = $users[0]['id']; // Main user
        $user2_id = $users[($i + 1) % count($users)]['id'];
        $item_id = isset($items[$i % count($items)]['id']) ? $items[$i % count($items)]['id'] : null;

        // Ensure user1_id < user2_id for consistency
        if ($user1_id > $user2_id) {
            $temp = $user1_id;
            $user1_id = $user2_id;
            $user2_id = $temp;
        }

        // Check if conversation exists
        $stmt = $conn->prepare("SELECT id FROM conversations WHERE user1_id = ? AND user2_id = ?");
        $stmt->execute([$user1_id, $user2_id]);
        $existingConv = $stmt->fetch();

        if ($existingConv) {
            $conversation_id = $existingConv['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO conversations (user1_id, user2_id, item_id, created_at, updated_at, last_message_at) VALUES (?, ?, ?, NOW(), NOW(), NOW())");
            $stmt->execute([$user1_id, $user2_id, $item_id]);
            $conversation_id = $conn->lastInsertId();
            $conversationsCreated++;
        }

        // Create 5 messages per conversation
        for ($m = 0; $m < 5; $m++) {
            $sender_id = ($m % 2 === 0) ? $users[0]['id'] : $users[($i + 1) % count($users)]['id'];
            $receiver_id = ($m % 2 === 0) ? $users[($i + 1) % count($users)]['id'] : $users[0]['id'];
            $message_text = $sampleMessages[($i * 5 + $m) % count($sampleMessages)];
            $is_read = ($m < 3 ? 1 : 0);
            $created_at = date('Y-m-d H:i:s', strtotime('-' . (5 - $m) . ' hours'));
            
            $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, receiver_id, message_text, item_id, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$conversation_id, $sender_id, $receiver_id, $message_text, $item_id, $is_read, $created_at]);
            $messagesCreated++;
        }

        // Update conversation last message time
        $stmt = $conn->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = ?");
        $stmt->execute([$conversation_id]);
    }

    $results['conversations_created'] = $conversationsCreated;
    $results['messages_created'] = $messagesCreated;

    // Create borrow requests
    $requestsCreated = 0;
    $userIds = array_column($users, 'id');
    
    // 5 SENT requests (you are the borrower)
    for ($i = 0; $i < 5; $i++) {
        $borrower_id = $userIds[0];
        $item = $items[$i % count($items)];
        $lender_id = isset($item['user_id']) && $item['user_id'] != $borrower_id ? $item['user_id'] : $userIds[(1 + $i) % count($userIds)];
        $item_price = isset($item['price']) ? floatval($item['price']) : 50.00;
        $item_id = isset($item['id']) ? $item['id'] : ($i + 1);
        
        $start_date = date('Y-m-d', strtotime('+' . (7 + $i) . ' days'));
        $end_date = date('Y-m-d', strtotime('+' . (12 + $i) . ' days'));
        $total_price = $item_price * 5; // 5 days
        $created_at = date('Y-m-d H:i:s', strtotime('-' . $i . ' days'));
        
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$item_id, $borrower_id, $lender_id, $start_date, $end_date, $total_price, 20.00, 'Ashesi University Campus', "Hi! I'd like to borrow this for a few days. Is it available?", 'pending', $created_at]);
        $requestsCreated++;
    }

    // 5 RECEIVED requests (you are the lender)
    for ($i = 0; $i < 5; $i++) {
        $lender_id = $userIds[0];
        $borrower_id = $userIds[(1 + $i) % count($userIds)];
        if ($borrower_id == $lender_id) {
            $borrower_id = $userIds[(2 + $i) % count($userIds)];
        }
        $item = $items[(5 + $i) % count($items)];
        $item_price = isset($item['price']) ? floatval($item['price']) : 40.00;
        $item_id = isset($item['id']) ? $item['id'] : (6 + $i);
        
        $start_date = date('Y-m-d', strtotime('+' . (3 + $i) . ' days'));
        $end_date = date('Y-m-d', strtotime('+' . (8 + $i) . ' days'));
        $total_price = $item_price * 5; // 5 days
        $created_at = date('Y-m-d H:i:s', strtotime('-' . ($i * 6) . ' hours'));
        
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$item_id, $borrower_id, $lender_id, $start_date, $end_date, $total_price, 15.00, 'Campus Library', "Hello! Interested in borrowing this. Is it available?", 'pending', $created_at]);
        $requestsCreated++;
    }

    // 2 ACTIVE requests with meetings
    for ($i = 0; $i < 2; $i++) {
        $borrower_id = $userIds[0];
        $item = $items[(10 + $i) % count($items)];
        $lender_id = isset($item['user_id']) && $item['user_id'] != $borrower_id ? $item['user_id'] : $userIds[(1 + $i) % count($userIds)];
        $item_price = isset($item['price']) ? floatval($item['price']) : 60.00;
        $item_id = isset($item['id']) ? $item['id'] : (11 + $i);
        
        $start_date = date('Y-m-d', strtotime('-' . (2 + $i) . ' days'));
        $end_date = date('Y-m-d', strtotime('+' . (3 + $i) . ' days'));
        $total_price = $item_price * 6; // 6 days
        $created_at = date('Y-m-d H:i:s', strtotime('-' . (7 + $i) . ' days'));
        $updated_at = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$item_id, $borrower_id, $lender_id, $start_date, $end_date, $total_price, 25.00, 'Student Center', "Need this for a project. Thanks!", 'active', $created_at, $updated_at]);
        $requestId = $conn->lastInsertId();
        $requestsCreated++;

        // Add meeting if meeting_schedules table exists
        try {
            $meeting_date = date('Y-m-d H:i:s', strtotime('-' . (2 + $i) . ' days +14 hours'));
            $stmt = $conn->prepare("INSERT INTO meeting_schedules (borrow_request_id, scheduled_by, meeting_type, meeting_date, meeting_location, notes, meeting_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$requestId, $lender_id, 'offline', $meeting_date, 'Student Center Main Entrance', 'Pickup confirmed', 'completed']);
        } catch (Exception $e) {
            // Ignore if meeting_schedules doesn't exist
        }
    }

    // 3 COMPLETED requests with reviews
    for ($i = 0; $i < 3; $i++) {
        $borrower_id = $userIds[0];
        $item = $items[(15 + $i) % count($items)];
        $lender_id = isset($item['user_id']) && $item['user_id'] != $borrower_id ? $item['user_id'] : $userIds[(1 + $i) % count($userIds)];
        $item_price = isset($item['price']) ? floatval($item['price']) : 70.00;
        $item_id = isset($item['id']) ? $item['id'] : (16 + $i);
        
        $start_date = date('Y-m-d', strtotime('-' . (20 + $i * 2) . ' days'));
        $end_date = date('Y-m-d', strtotime('-' . (15 + $i * 2) . ' days'));
        $total_price = $item_price * 5; // 5 days
        $created_at = date('Y-m-d H:i:s', strtotime('-' . (25 + $i * 2) . ' days'));
        $updated_at = date('Y-m-d H:i:s', strtotime('-' . (14 + $i * 2) . ' days'));
        
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$item_id, $borrower_id, $lender_id, $start_date, $end_date, $total_price, 30.00, 'Library', "Great item! Would love to borrow it.", 'completed', $created_at, $updated_at]);
        $requestId = $conn->lastInsertId();
        $requestsCreated++;

        // Add review if reviews table exists
        try {
            $rating = (4 + ($i % 2));
            $review_date = date('Y-m-d H:i:s', strtotime('-' . (14 + $i * 2) . ' days'));
            $stmt = $conn->prepare("INSERT INTO reviews (reviewer_id, reviewed_user_id, borrow_request_id, rating, review_type, title, comment, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$borrower_id, $lender_id, $requestId, $rating, 'borrower_to_lender', 'Great Experience!', 'Item was in excellent condition. Lender was very responsive and professional.', $review_date]);
        } catch (Exception $e) {
            // Ignore if reviews doesn't exist
        }
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
