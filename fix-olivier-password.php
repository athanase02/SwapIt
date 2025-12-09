<?php
require 'config/db.php';

echo "=================================\n";
echo "FIXING OLIVIER KWIZERA'S PASSWORD\n";
echo "=================================\n\n";

$email = 'olivier.kwizera@ashesi.edu.gh';
$newPassword = 'password123';

// Check if user exists
$stmt = $conn->prepare("SELECT id, email, full_name, password_hash FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "User found:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Name: {$user['full_name']}\n";
    echo "  Email: {$user['email']}\n\n";
    
    // Generate new password hash
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    // Update password
    $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $updateStmt->execute([$newHash, $user['id']]);
    
    echo "✓ Password updated successfully!\n\n";
    
    // Verify the new password works
    $stmt->execute([$email]);
    $updatedUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (password_verify($newPassword, $updatedUser['password_hash'])) {
        echo "✅ PASSWORD VERIFICATION: SUCCESS!\n\n";
        echo "=================================\n";
        echo "LOGIN CREDENTIALS:\n";
        echo "=================================\n";
        echo "Email: olivier.kwizera@ashesi.edu.gh\n";
        echo "Password: password123\n";
        echo "=================================\n\n";
        echo "You can now login with these credentials!\n";
    } else {
        echo "❌ Verification failed - something went wrong\n";
    }
    
} else {
    echo "❌ User not found in database!\n";
    echo "Creating new user...\n\n";
    
    $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $insertStmt = $conn->prepare("
        INSERT INTO users (email, password_hash, full_name, is_verified, account_type) 
        VALUES (?, ?, ?, TRUE, 'student')
    ");
    $insertStmt->execute([$email, $passwordHash, 'Olivier Kwizera']);
    $newUserId = $conn->lastInsertId();
    
    // Create profile
    $profileStmt = $conn->prepare("
        INSERT INTO profiles (user_id, full_name, email, location, student_id, graduation_year) 
        VALUES (?, ?, ?, 'Berekuso Campus', 'A00034567', 2026)
    ");
    $profileStmt->execute([$newUserId, 'Olivier Kwizera', $email]);
    
    echo "✓ User created successfully!\n\n";
    echo "=================================\n";
    echo "LOGIN CREDENTIALS:\n";
    echo "=================================\n";
    echo "Email: olivier.kwizera@ashesi.edu.gh\n";
    echo "Password: password123\n";
    echo "=================================\n";
}
