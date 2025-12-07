<?php
/**
 * Complete Database Setup & Sample Data
 * Creates all missing tables and populates sample data
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';

$results = [
    'success' => false,
    'tables_created' => [],
    'tables_existed' => [],
    'data_created' => [],
    'errors' => []
];

try {
    if (!isset($conn)) {
        throw new Exception("Database connection not available");
    }

    // ==================== CREATE TABLES ====================
    
    // 1. Conversations table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS conversations (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user1_id INT NOT NULL,
                user2_id INT NOT NULL,
                item_id INT,
                last_message_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user1 (user1_id),
                INDEX idx_user2 (user2_id),
                INDEX idx_item (item_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'conversations';
    } catch (Exception $e) {
        $results['tables_existed'][] = 'conversations';
    }

    // 2. Messages table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS messages (
                id INT PRIMARY KEY AUTO_INCREMENT,
                conversation_id INT NOT NULL,
                sender_id INT NOT NULL,
                receiver_id INT NOT NULL,
                message_text TEXT NOT NULL,
                item_id INT,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
                INDEX idx_conversation (conversation_id),
                INDEX idx_sender (sender_id),
                INDEX idx_receiver (receiver_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'messages';
    } catch (Exception $e) {
        $results['tables_existed'][] = 'messages';
    }

    // 3. Borrow Requests table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS borrow_requests (
                id INT PRIMARY KEY AUTO_INCREMENT,
                item_id INT NOT NULL,
                borrower_id INT NOT NULL,
                lender_id INT NOT NULL,
                borrow_start_date DATE NOT NULL,
                borrow_end_date DATE NOT NULL,
                total_price DECIMAL(10, 2) NOT NULL,
                security_deposit DECIMAL(10, 2),
                pickup_location VARCHAR(255),
                borrower_message TEXT,
                lender_response TEXT,
                rejection_reason TEXT,
                status ENUM('pending', 'accepted', 'rejected', 'active', 'completed', 'cancelled') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_item (item_id),
                INDEX idx_borrower (borrower_id),
                INDEX idx_lender (lender_id),
                INDEX idx_status (status),
                INDEX idx_dates (borrow_start_date, borrow_end_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'borrow_requests';
    } catch (Exception $e) {
        $results['tables_existed'][] = 'borrow_requests';
    }

    // 4. Reviews table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INT PRIMARY KEY AUTO_INCREMENT,
                reviewer_id INT NOT NULL,
                reviewed_user_id INT NOT NULL,
                borrow_request_id INT,
                rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                review_type ENUM('borrower_to_lender', 'lender_to_borrower') NOT NULL,
                title VARCHAR(255),
                comment TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_reviewer (reviewer_id),
                INDEX idx_reviewed (reviewed_user_id),
                INDEX idx_request (borrow_request_id),
                INDEX idx_rating (rating)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'reviews';
    } catch (Exception $e) {
        $results['tables_existed'][] = 'reviews';
    }

    // 5. Notifications table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS notifications (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                related_id INT,
                is_read TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_read (is_read),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'notifications';
    } catch (Exception $e) {
        $results['tables_existed'][] = 'notifications';
    }

    // 6. Meeting Schedules table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS meeting_schedules (
                id INT PRIMARY KEY AUTO_INCREMENT,
                borrow_request_id INT NOT NULL,
                scheduled_by INT NOT NULL,
                meeting_type ENUM('online', 'offline') NOT NULL,
                meeting_date DATETIME NOT NULL,
                meeting_location VARCHAR(255),
                meeting_link TEXT,
                notes TEXT,
                meeting_status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_request (borrow_request_id),
                INDEX idx_date (meeting_date),
                INDEX idx_status (meeting_status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'meeting_schedules';
    } catch (Exception $e) {
        $results['tables_existed'][] = 'meeting_schedules';
    }

    // 7. User Activities table
    try {
        $conn->exec("
            CREATE TABLE IF NOT EXISTS user_activities (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                activity_type VARCHAR(50) NOT NULL,
                description TEXT NOT NULL,
                related_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $results['tables_created'][] = 'user_activities';
    } catch (Exception $e) {
        $results['tables_existed'][] = 'user_activities';
    }

    // ==================== POPULATE SAMPLE DATA ====================

    // Get users
    $stmt = $conn->query("SELECT id, full_name FROM users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($users) < 2) {
        throw new Exception("Need at least 2 users. Found: " . count($users));
    }

    // Get items
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

    $messages = [
        "Hi! I'm interested in this item.", "Sure! When do you need it?",
        "Next week works for me.", "Perfect! Let's schedule a meeting.",
        "How about Monday at 2 PM?", "That works! See you then.",
        "Is this still available?", "Yes! What dates?",
        "This weekend please.", "Sounds good!",
        "Can I see more pictures?", "I'll send them now.",
        "Thanks for lending this!", "No problem, enjoy!",
        "Just confirming tomorrow.", "Yes, 2 PM at library.",
        "How much is the deposit?", "Just $20 security.",
        "When can I pick it up?", "Anytime after 3 PM.",
        "Great, thank you!", "You're welcome!"
    ];

    $conversationsCreated = 0;
    $messagesCreated = 0;

    // Create 5 conversations with 5 messages each
    for ($i = 0; $i < 5; $i++) {
        $u1 = $users[$i % count($users)]['id'];
        $u2 = $users[($i + 1) % count($users)]['id'];
        $itemId = isset($items[$i % count($items)]['id']) ? $items[$i % count($items)]['id'] : null;

        $stmt = $conn->prepare("INSERT INTO conversations (user1_id, user2_id, item_id) VALUES (?, ?, ?)");
        $stmt->execute([$u1, $u2, $itemId]);
        $convId = $conn->lastInsertId();
        $conversationsCreated++;

        for ($m = 0; $m < 5; $m++) {
            $sender = ($m % 2 === 0) ? $u1 : $u2;
            $receiver = ($m % 2 === 0) ? $u2 : $u1;
            $text = $messages[($i * 5 + $m) % count($messages)];
            
            $stmt = $conn->prepare("INSERT INTO messages (conversation_id, sender_id, receiver_id, message_text, item_id, is_read) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$convId, $sender, $receiver, $text, $itemId, ($m < 3 ? 1 : 0)]);
            $messagesCreated++;
        }
    }

    $results['data_created']['conversations'] = $conversationsCreated;
    $results['data_created']['messages'] = $messagesCreated;

    // Create borrow requests
    $requestsCreated = 0;
    $userIds = array_column($users, 'id');

    // 5 pending sent
    for ($i = 0; $i < 5; $i++) {
        $item = $items[$i % count($items)];
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status) VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), ?, 20.00, 'Campus', 'Hi! Need this item.', 'pending')");
        $stmt->execute([
            $item['id'] ?? 1,
            $userIds[0],
            $item['user_id'] ?? $userIds[1],
            ($item['price'] ?? 50) * 0.2
        ]);
        $requestsCreated++;
    }

    // 5 pending received
    for ($i = 0; $i < 5; $i++) {
        $item = $items[(5 + $i) % count($items)];
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status) VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY), ?, 15.00, 'Library', 'Interested!', 'pending')");
        $stmt->execute([
            $item['id'] ?? (6 + $i),
            $userIds[($i + 1) % count($userIds)],
            $userIds[0],
            ($item['price'] ?? 40) * 0.15
        ]);
        $requestsCreated++;
    }

    // 5 active
    for ($i = 0; $i < 5; $i++) {
        $item = $items[(10 + $i) % count($items)];
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status) VALUES (?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), ?, 25.00, 'Student Center', 'Thanks!', 'active')");
        $stmt->execute([
            $item['id'] ?? (11 + $i),
            $userIds[0],
            $item['user_id'] ?? $userIds[1],
            ($item['price'] ?? 60) * 0.25
        ]);
        $requestsCreated++;
    }

    // 5 completed with reviews
    for ($i = 0; $i < 5; $i++) {
        $item = $items[(15 + $i) % count($items)];
        $stmt = $conn->prepare("INSERT INTO borrow_requests (item_id, borrower_id, lender_id, borrow_start_date, borrow_end_date, total_price, security_deposit, pickup_location, borrower_message, status) VALUES (?, ?, ?, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY), ?, 30.00, 'Library', 'Great!', 'completed')");
        $stmt->execute([
            $item['id'] ?? (16 + $i),
            $userIds[0],
            $item['user_id'] ?? $userIds[1],
            ($item['price'] ?? 70) * 0.3
        ]);
        $reqId = $conn->lastInsertId();
        $requestsCreated++;

        $stmt = $conn->prepare("INSERT INTO reviews (reviewer_id, reviewed_user_id, borrow_request_id, rating, review_type, title, comment) VALUES (?, ?, ?, ?, 'borrower_to_lender', 'Great!', 'Excellent experience.')");
        $stmt->execute([$userIds[0], $item['user_id'] ?? $userIds[1], $reqId, (4 + ($i % 2))]);
    }

    $results['data_created']['requests'] = $requestsCreated;
    $results['success'] = true;

} catch (Exception $e) {
    $results['error'] = $e->getMessage();
    $results['trace'] = $e->getTraceAsString();
}

echo json_encode($results, JSON_PRETTY_PRINT);
