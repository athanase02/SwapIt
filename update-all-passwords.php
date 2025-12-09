<?php
require 'config/db.php';

echo "=================================\n";
echo "UPDATING ALL USER PASSWORDS\n";
echo "=================================\n\n";

$newPassword = 'Password123';
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);

echo "Generated new password hash for: Password123\n\n";

// Get all users
$stmt = $conn->query("SELECT id, email, full_name FROM users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($users) . " users in database\n\n";

// Update all users
$updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
$successCount = 0;

foreach ($users as $user) {
    try {
        $updateStmt->execute([$newHash, $user['id']]);
        echo "✓ Updated: {$user['full_name']} ({$user['email']})\n";
        $successCount++;
    } catch (Exception $e) {
        echo "✗ Failed to update {$user['email']}: " . $e->getMessage() . "\n";
    }
}

echo "\n=================================\n";
echo "UPDATE COMPLETE!\n";
echo "=================================\n";
echo "Successfully updated: $successCount users\n";
echo "Failed: " . (count($users) - $successCount) . " users\n\n";

// Verify one user to confirm
echo "Verifying password for first user...\n";
$verifyStmt = $conn->prepare("SELECT email, password_hash FROM users LIMIT 1");
$verifyStmt->execute();
$testUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);

if ($testUser && password_verify($newPassword, $testUser['password_hash'])) {
    echo "✅ PASSWORD VERIFICATION: SUCCESS!\n\n";
    echo "=================================\n";
    echo "ALL USERS CAN NOW LOGIN WITH:\n";
    echo "=================================\n";
    echo "Password: Password123\n";
    echo "=================================\n\n";
    
    echo "USER ACCOUNTS:\n";
    echo "---------------------------------\n";
    foreach ($users as $user) {
        echo "• {$user['full_name']}\n";
        echo "  Email: {$user['email']}\n";
        echo "  Password: Password123\n\n";
    }
} else {
    echo "❌ VERIFICATION FAILED\n";
}
