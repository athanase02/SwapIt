<?php
// Quick script to show items table structure
require_once __DIR__ . '/../config/db.php';

header('Content-Type: text/plain');

try {
    echo "=== ITEMS TABLE STRUCTURE ===\n\n";
    $stmt = $conn->query("DESCRIBE items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
    echo "\n=== PROFILES TABLE STRUCTURE ===\n\n";
    $stmt = $conn->query("DESCRIBE profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
    echo "\n=== USERS TABLE STRUCTURE ===\n\n";
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
