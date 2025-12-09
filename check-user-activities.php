<?php
require 'config/db.php';
echo "Checking user_activities table structure:\n\n";
$result = $conn->query("DESCRIBE user_activities")->fetchAll();
foreach ($result as $column) {
    echo "Column: {$column['Field']}\n";
    echo "  Type: {$column['Type']}\n";
    echo "  Null: {$column['Null']}\n";
    echo "  Default: {$column['Default']}\n\n";
}
