<?php
/**
 * Test Requests API - Debug what's being returned
 */

session_start();
header('Content-Type: application/json');

// Set test user session
$_SESSION['user_id'] = 1; // Use first user ID

require_once __DIR__ . '/../config/db.php';

$results = [
    'test' => 'requests_api',
    'user_id' => $_SESSION['user_id'],
    'sent_requests' => [],
    'received_requests' => [],
    'active_requests' => [],
    'completed_requests' => [],
    'error' => null
];

try {
    // Get sent requests (where user is borrower)
    $stmt = $conn->prepare("
        SELECT br.*, 
               lender.full_name as lender_name
        FROM borrow_requests br
        JOIN users lender ON br.lender_id = lender.id
        WHERE br.borrower_id = ? AND br.status = 'pending'
        ORDER BY br.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $results['sent_requests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get received requests (where user is lender)
    $stmt = $conn->prepare("
        SELECT br.*, 
               borrower.full_name as borrower_name
        FROM borrow_requests br
        JOIN users borrower ON br.borrower_id = borrower.id
        WHERE br.lender_id = ? AND br.status = 'pending'
        ORDER BY br.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $results['received_requests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get active requests
    $stmt = $conn->prepare("
        SELECT br.*, 
               borrower.full_name as borrower_name,
               lender.full_name as lender_name
        FROM borrow_requests br
        JOIN users borrower ON br.borrower_id = borrower.id
        JOIN users lender ON br.lender_id = lender.id
        WHERE (br.borrower_id = ? OR br.lender_id = ?) AND br.status = 'active'
        ORDER BY br.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $results['active_requests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get completed requests
    $stmt = $conn->prepare("
        SELECT br.*, 
               borrower.full_name as borrower_name,
               lender.full_name as lender_name
        FROM borrow_requests br
        JOIN users borrower ON br.borrower_id = borrower.id
        JOIN users lender ON br.lender_id = lender.id
        WHERE (br.borrower_id = ? OR br.lender_id = ?) AND br.status = 'completed'
        ORDER BY br.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $results['completed_requests'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
