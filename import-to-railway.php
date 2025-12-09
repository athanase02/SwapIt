<?php
/**
 * Import Database Schema to Railway MySQL
 */

echo "Importing SwapIt Database to Railway...\n";
echo "========================================\n\n";

try {
    // Connect to Railway
    include 'config/db.php';
    
    echo "✓ Connected to Railway MySQL\n\n";
    
    // Read the SQL file
    $sqlFile = 'db/SI2025.sql';
    
    if (!file_exists($sqlFile)) {
        die("✗ Error: SQL file not found at $sqlFile\n");
    }
    
    echo "Reading SQL file: $sqlFile\n";
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        die("✗ Error: Could not read SQL file\n");
    }
    
    echo "File size: " . strlen($sql) . " bytes\n\n";
    
    // Remove USE statements (Railway database name is 'railway')
    $sql = preg_replace('/USE\s+`?SI2025`?;/i', '', $sql);
    $sql = preg_replace('/CREATE\s+DATABASE.*SI2025.*;/i', '', $sql);
    $sql = preg_replace('/DROP\s+DATABASE.*SI2025.*;/i', '', $sql);
    
    // Split SQL into statements properly
    // This handles multi-line statements and comments
    $statements = [];
    $currentStatement = '';
    $lines = explode("\n", $sql);
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip empty lines and comment-only lines
        if (empty($line) || substr($line, 0, 2) === '--') {
            continue;
        }
        
        // Remove inline comments
        $line = preg_replace('/--.*$/', '', $line);
        $line = trim($line);
        
        if (empty($line)) {
            continue;
        }
        
        $currentStatement .= ' ' . $line;
        
        // Check if statement is complete (ends with semicolon)
        if (substr($line, -1) === ';') {
            $stmt = trim($currentStatement);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $currentStatement = '';
        }
    }
    
    // Add any remaining statement
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }
    
    echo "Executing " . count($statements) . " SQL statements...\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    // Disable foreign key checks temporarily
    $conn->exec('SET FOREIGN_KEY_CHECKS = 0');
    
    foreach ($statements as $index => $statement) {
        try {
            if (trim($statement)) {
                $conn->exec($statement);
                $successCount++;
                
                // Show progress for table creation
                if (preg_match('/CREATE\s+TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                    echo "  ✓ Created table: {$matches[1]}\n";
                }
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = "Statement " . ($index + 1) . ": " . $e->getMessage();
            $errors[] = $errorMsg;
            
            // Only show first part of failed statement
            $preview = substr($statement, 0, 100);
            echo "  ✗ Error: " . $errorMsg . "\n";
            echo "    Statement preview: " . $preview . "...\n";
        }
    }
    
    // Re-enable foreign key checks
    $conn->exec('SET FOREIGN_KEY_CHECKS = 1');
    
    echo "\n========================================\n";
    echo "Import Summary:\n";
    echo "  ✓ Successful: $successCount statements\n";
    echo "  ✗ Errors: $errorCount statements\n\n";
    
    // List tables in database
    echo "Tables in database:\n";
    try {
        $stmt = $conn->query('SHOW TABLES');
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt->closeCursor(); // Close cursor to free up connection
        
        if (empty($tables)) {
            echo "  (No tables found)\n";
        } else {
            foreach ($tables as $table) {
                // Get row count
                try {
                    $countStmt = $conn->query("SELECT COUNT(*) FROM `$table`");
                    $count = $countStmt->fetchColumn();
                    $countStmt->closeCursor();
                    echo "  - $table ($count rows)\n";
                } catch (Exception $e) {
                    echo "  - $table (error getting count)\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "  Error listing tables: " . $e->getMessage() . "\n";
    }
    
    echo "\n✓ Import completed!\n";
    
    if ($errorCount > 0) {
        echo "\nNote: Some errors occurred. This is usually okay if tables already existed.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
