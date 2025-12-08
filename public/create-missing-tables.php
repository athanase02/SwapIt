<?php
/**
 * Quick Fix: Create Missing Core Tables
 * Run this after run-migration.php to create items, categories, and ratings tables
 */

set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head><title>Create Missing Tables</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#f5f5f5;}
.container{max-width:800px;margin:0 auto;background:white;padding:30px;border-radius:8px;}
h1{color:#333;}.success{color:#27ae60;font-weight:bold;}.error{color:#e74c3c;font-weight:bold;}
table{width:100%;border-collapse:collapse;margin:20px 0;}
th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd;}
th{background:#3498db;color:white;}</style></head><body><div class='container'>";

echo "<h1>🔧 Create Missing Core Tables</h1>";

require_once __DIR__ . '/../config/db.php';

if (!isset($conn)) {
    echo "<p class='error'>❌ Database connection failed</p></div></body></html>";
    exit;
}

echo "<p class='success'>✅ Connected to database</p>";

// Create items table
$sql = "CREATE TABLE IF NOT EXISTS items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT,
    image_url VARCHAR(500),
    condition_status ENUM('new', 'like_new', 'good', 'fair', 'poor') DEFAULT 'good',
    availability_status ENUM('available', 'borrowed', 'unavailable') DEFAULT 'available',
    location VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_category_id (category_id),
    INDEX idx_availability (availability_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    if ($conn instanceof PDO) {
        $conn->exec($sql);
    } else {
        $conn->query($sql);
    }
    echo "<p class='success'>✅ Items table created</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Items table: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Create categories table
$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    if ($conn instanceof PDO) {
        $conn->exec($sql);
    } else {
        $conn->query($sql);
    }
    echo "<p class='success'>✅ Categories table created</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Categories table: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Create ratings table
$sql = "CREATE TABLE IF NOT EXISTS ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    reviewer_id INT NOT NULL,
    reviewee_id INT NOT NULL,
    request_id INT,
    rating INT NOT NULL,
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_reviewer (reviewer_id),
    INDEX idx_reviewee (reviewee_id),
    INDEX idx_request (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    if ($conn instanceof PDO) {
        $conn->exec($sql);
    } else {
        $conn->query($sql);
    }
    echo "<p class='success'>✅ Ratings table created</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Ratings table: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Insert default categories
$categories = [
    ['Electronics', 'Phones, laptops, tablets, cameras', 'fa-laptop'],
    ['Books', 'Textbooks, novels, reference books', 'fa-book'],
    ['Sports', 'Sports equipment, gear, accessories', 'fa-football-ball'],
    ['Clothing', 'Clothes, shoes, accessories', 'fa-tshirt'],
    ['Tools', 'Hand tools, power tools, equipment', 'fa-wrench'],
    ['Music', 'Instruments, audio equipment', 'fa-music'],
    ['Gaming', 'Video games, consoles, accessories', 'fa-gamepad'],
    ['Kitchen', 'Appliances, cookware, utensils', 'fa-utensils'],
    ['Furniture', 'Tables, chairs, shelves', 'fa-couch'],
    ['Other', 'Miscellaneous items', 'fa-box']
];

echo "<h2>Adding Default Categories</h2>";
$added = 0;

foreach ($categories as $cat) {
    try {
        if ($conn instanceof PDO) {
            $stmt = $conn->prepare("INSERT IGNORE INTO categories (name, description, icon) VALUES (?, ?, ?)");
            $stmt->execute($cat);
            if ($stmt->rowCount() > 0) $added++;
        } else {
            $stmt = $conn->prepare("INSERT IGNORE INTO categories (name, description, icon) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $cat[0], $cat[1], $cat[2]);
            $stmt->execute();
            if ($stmt->affected_rows > 0) $added++;
        }
    } catch (Exception $e) {
        // Category might already exist
    }
}

echo "<p class='success'>✅ Added {$added} categories</p>";

// Verify all tables
echo "<h2>Final Verification</h2>";
echo "<table><tr><th>Table</th><th>Status</th><th>Rows</th></tr>";

$tables = ['users', 'profiles', 'items', 'categories', 'borrow_requests', 'conversations', 
           'messages', 'ratings', 'notifications', 'transaction_history', 'online_users', 
           'user_activities', 'meeting_schedules', 'message_attachments'];

$allExist = true;
foreach ($tables as $table) {
    try {
        if ($conn instanceof PDO) {
            $result = $conn->query("SELECT COUNT(*) as cnt FROM {$table}");
            $row = $result->fetch(PDO::FETCH_ASSOC);
        } else {
            $result = $conn->query("SELECT COUNT(*) as cnt FROM {$table}");
            $row = $result->fetch_assoc();
        }
        echo "<tr><td>{$table}</td><td class='success'>✅ EXISTS</td><td>{$row['cnt']}</td></tr>";
    } catch (Exception $e) {
        echo "<tr><td>{$table}</td><td class='error'>❌ MISSING</td><td>N/A</td></tr>";
        $allExist = false;
    }
}
echo "</table>";

if ($allExist) {
    echo "<h2 class='success'>🎉 All Tables Created Successfully!</h2>";
    echo "<p>Your database is now complete with all required tables.</p>";
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Test messaging at <a href='/pages/messages.html'>/pages/messages.html</a></li>";
    echo "<li>Test requests at <a href='/pages/requests.html'>/pages/requests.html</a></li>";
    echo "<li>Add items at <a href='/pages/add-listing.html'>/pages/add-listing.html</a></li>";
    echo "</ol>";
} else {
    echo "<p class='error'>⚠️ Some tables are still missing. Contact support.</p>";
}

echo "</div></body></html>";
?>
