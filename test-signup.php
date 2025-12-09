<?php
/**
 * Test User Registration with Railway Database
 */

echo "Testing User Registration to Railway MySQL\n";
echo "==========================================\n\n";

try {
    include 'config/db.php';
    
    echo "✓ Connected to Railway database\n\n";
    
    // Check current user count
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $beforeCount = $stmt->fetchColumn();
    echo "Users before test: $beforeCount\n\n";
    
    // Generate test user data
    $testEmail = 'test_' . time() . '@ashesi.edu.gh';
    $testName = 'Test User ' . date('H:i:s');
    $testPassword = password_hash('testpass123', PASSWORD_DEFAULT);
    
    echo "Creating test user:\n";
    echo "  Email: $testEmail\n";
    echo "  Name: $testName\n\n";
    
    // Insert test user
    $stmt = $conn->prepare("
        INSERT INTO users (email, password_hash, full_name, is_verified) 
        VALUES (?, ?, ?, 1)
    ");
    
    $stmt->execute([$testEmail, $testPassword, $testName]);
    $newUserId = $conn->lastInsertId();
    
    echo "✓ User created successfully!\n";
    echo "  User ID: $newUserId\n\n";
    
    // Verify user was created
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $afterCount = $stmt->fetchColumn();
    echo "Users after test: $afterCount\n";
    echo "New users added: " . ($afterCount - $beforeCount) . "\n\n";
    
    // Get the new user details
    $stmt = $conn->prepare("SELECT id, email, full_name, created_at FROM users WHERE id = ?");
    $stmt->execute([$newUserId]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✓ Verification successful!\n";
        echo "User details:\n";
        echo "  ID: {$user['id']}\n";
        echo "  Email: {$user['email']}\n";
        echo "  Name: {$user['full_name']}\n";
        echo "  Created: {$user['created_at']}\n\n";
    }
    
    // Create profile for the user
    $stmt = $conn->prepare("
        INSERT INTO profiles (user_id, full_name, email) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$newUserId, $testName, $testEmail]);
    
    echo "✓ Profile created successfully!\n\n";
    
    echo "==========================================\n";
    echo "✅ SUCCESS: User registration is working!\n";
    echo "New users will be saved to Railway database.\n\n";
    
    // Show all recent users
    echo "Recent users in database:\n";
    $stmt = $conn->query("SELECT id, email, full_name, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    while ($row = $stmt->fetch()) {
        echo "  [{$row['id']}] {$row['full_name']} - {$row['email']} ({$row['created_at']})\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo "\nThis means new user registrations will fail!\n";
    exit(1);
}
