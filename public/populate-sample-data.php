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

    // Get existing items (trying different possible table names)
    $items = [];
    try {
        // Try 'active_listings' table first
        $stmt = $conn->query("SELECT id, title, price_per_day as price, user_id FROM active_listings LIMIT 20");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // If that fails, create dummy items for the first user
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

    // ==================== CREATE CONVERSATIONS & MESSAGES ====================
    
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
        $stmt = $conn->prepare("
            SELECT id FROM conversations 
            WHERE (user1_id = ? AND user2_id = ?) 
               OR (user1_id = ? AND user2_id = ?)
        ");
        $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
        $existingConv = $stmt->fetch();

        if ($existingConv) {
            $conversation_id = $existingConv['id'];
        } else {
            // Create conversation
            $stmt = $conn->prepare("
                INSERT INTO conversations (user1_id, user2_id, item_id, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([$user1_id, $user2_id, $item_id]);
            $conversation_id = $conn->lastInsertId();
            $conversationsCreated++;
        }

        // Create 5 messages for this conversation
        for ($m = 0; $m < 5; $m++) {
            $sender_id = ($m % 2 === 0) ? $user1_id : $user2_id;
            $receiver_id = ($m % 2 === 0) ? $user2_id : $user1_id;
            $message_text = $sampleMessages[($i * 5 + $m) % count($sampleMessages)];
            
            $stmt = $conn->prepare("
                INSERT INTO messages (
                    conversation_id, sender_id, receiver_id, message_text, 
                    item_id, is_read, created_at
                )
                VALUES (?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))
            ");
            $stmt->execute([
                $conversation_id, 
                $sender_id, 
                $receiver_id, 
                $message_text,
                $item_id,
                ($m < 3 ? 1 : 0), // First 3 messages are read
                (5 - $m) // Spread messages over last 5 hours
            ]);
            $messagesCreated++;
        }

        // Update conversation last_message_at
        $stmt = $conn->prepare("
            UPDATE conversations 
            SET last_message_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$conversation_id]);
    }

    $results['conversations_created'] = $conversationsCreated;
    $results['messages_created'] = $messagesCreated;

    // ==================== CREATE BORROW REQUESTS ====================
    
    $requestsCreated = 0;
    
    // Get user IDs for creating requests
    $userIds = array_column($users, 'id');
    
    // Create 5 SENT requests (pending status)
    for ($i = 0; $i < 5; $i++) {
        $borrower_id = $userIds[0]; // First user sends requests
        $item = $items[$i % count($items)];
        $lender_id = isset($item['user_id']) && $item['user_id'] != $borrower_id ? $item['user_id'] : $userIds[1];
        $item_price = isset($item['price']) ? $item['price'] : 50.00;
        $item_id = isset($item['id']) ? $item['id'] : ($i + 1);
        
        $stmt = $conn->prepare("
            INSERT INTO borrow_requests (
                item_id, borrower_id, lender_id, 
                borrow_start_date, borrow_end_date, 
                total_price, security_deposit, pickup_location,
                borrower_message, status, created_at
            )
            VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY), 
                    ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))
        ");
        $stmt->execute([
            $item_id, $borrower_id, $lender_id,
            (7 + $i), // Start in 7-11 days
            (12 + $i), // End in 12-16 days
            $item_price * 0.2, // 20% of item price
            20.00,
            'Ashesi University Campus',
            "Hi! I'd like to borrow this for a few days. Available?",
            'pending',
            $i // Created 0-4 days ago
        ]);
        $requestsCreated++;
    }

    // Create 5 RECEIVED requests (pending status, to first user)
    for ($i = 0; $i < 5; $i++) {
        $lender_id = $userIds[0]; // First user receives requests
        $borrower_id = $userIds[($i + 1) % count($userIds)];
        $item = $items[(5 + $i) % count($items)];
        $item_price = isset($item['price']) ? $item['price'] : 40.00;
        $item_id = isset($item['id']) ? $item['id'] : (6 + $i);
        
        $stmt = $conn->prepare("
            INSERT INTO borrow_requests (
                item_id, borrower_id, lender_id, 
                borrow_start_date, borrow_end_date, 
                total_price, security_deposit, pickup_location,
                borrower_message, status, created_at
            )
            VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY), 
                    ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))
        ");
        $stmt->execute([
            $item_id, $borrower_id, $lender_id,
            (3 + $i), // Start in 3-7 days
            (8 + $i), // End in 8-12 days
            $item_price * 0.15,
            15.00,
            'Campus Library',
            "Hello! Interested in borrowing this. Is it available?",
            'pending',
            ($i * 6) // Created 0-24 hours ago
        ]);
        $requestsCreated++;
    }

    // Create 5 ACTIVE requests (accepted and ongoing)
    for ($i = 0; $i < 5; $i++) {
        $borrower_id = $userIds[0];
        $item = $items[(10 + $i) % count($items)];
        $lender_id = isset($item['user_id']) && $item['user_id'] != $borrower_id ? $item['user_id'] : $userIds[1];
        $item_price = isset($item['price']) ? $item['price'] : 60.00;
        $item_id = isset($item['id']) ? $item['id'] : (11 + $i);
        
        $stmt = $conn->prepare("
            INSERT INTO borrow_requests (
                item_id, borrower_id, lender_id, 
                borrow_start_date, borrow_end_date, 
                total_price, security_deposit, pickup_location,
                borrower_message, status, created_at, updated_at
            )
            VALUES (?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), DATE_ADD(CURDATE(), INTERVAL ? DAY), 
                    ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), NOW())
        ");
        $stmt->execute([
            $item_id, $borrower_id, $lender_id,
            (2 + $i), // Started 2-6 days ago
            (3 + $i), // Ends in 3-7 days
            $item_price * 0.25,
            25.00,
            'Student Center',
            "Need this for a project. Thanks!",
            'active',
            (7 + $i) // Created 7-11 days ago
        ]);
        $requestId = $conn->lastInsertId();
        $requestsCreated++;

        // Create meeting schedule for active requests
        $stmt = $conn->prepare("
            INSERT INTO meeting_schedules (
                borrow_request_id, scheduled_by, meeting_type,
                meeting_date, meeting_location, notes, meeting_status
            )
            VALUES (?, ?, 'offline', 
                    DATE_SUB(NOW(), INTERVAL ? DAY) + INTERVAL 14 HOUR, 
                    'Student Center Main Entrance', 'Pickup confirmed', 'completed')
        ");
        $stmt->execute([$requestId, $lender_id, (2 + $i)]);
    }

    // Create 5 COMPLETED requests
    for ($i = 0; $i < 5; $i++) {
        $borrower_id = $userIds[0];
        $item = $items[(15 + $i) % count($items)];
        $lender_id = isset($item['user_id']) && $item['user_id'] != $borrower_id ? $item['user_id'] : $userIds[1];
        $item_price = isset($item['price']) ? $item['price'] : 70.00;
        $item_id = isset($item['id']) ? $item['id'] : (16 + $i);
        
        $stmt = $conn->prepare("
            INSERT INTO borrow_requests (
                item_id, borrower_id, lender_id, 
                borrow_start_date, borrow_end_date, 
                total_price, security_deposit, pickup_location,
                borrower_message, status, created_at, updated_at
            )
            VALUES (?, ?, ?, DATE_SUB(CURDATE(), INTERVAL ? DAY), DATE_SUB(CURDATE(), INTERVAL ? DAY), 
                    ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY), DATE_SUB(NOW(), INTERVAL ? DAY))
        ");
        $stmt->execute([
            $item_id, $borrower_id, $lender_id,
            (20 + $i * 2), // Started 20-28 days ago
            (15 + $i * 2), // Ended 15-23 days ago
            $item_price * 0.3,
            30.00,
            'Library',
            "Great item! Would love to borrow it.",
            'completed',
            (25 + $i * 2) // Created 25-33 days ago
        ]);
        $requestId = $conn->lastInsertId();
        $requestsCreated++;

        // Add review for completed requests
        $stmt = $conn->prepare("
            INSERT INTO reviews (
                reviewer_id, reviewed_user_id, borrow_request_id,
                rating, review_type, title, comment, created_at
            )
            VALUES (?, ?, ?, ?, 'borrower_to_lender', ?, ?, DATE_SUB(NOW(), INTERVAL ? DAY))
        ");
        $stmt->execute([
            $borrower_id,
            $lender_id,
            $requestId,
            (4 + ($i % 2)), // Rating 4 or 5
            'Great Experience!',
            'Item was in excellent condition. Lender was very responsive and professional.',
            (14 + $i * 2) // Review created after return
        ]);
    }

    $results['requests_created'] = $requestsCreated;

    // Update user activity
    $stmt = $conn->prepare("
        INSERT INTO user_activities (user_id, activity_type, description, created_at)
        VALUES (?, 'sample_data', 'Sample data populated for testing', NOW())
    ");
    $stmt->execute([$userIds[0]]);

    $results['success'] = true;
    $results['message'] = 'Sample data created successfully!';

} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
