<?php
/**
 * Create Demo Users for SwapIt Workflow Testing
 * Creates Sarah (student/borrower) and John (professor/owner)
 */

session_start();
require_once __DIR__ . '/../config/db.php';

echo "<!DOCTYPE html><html><head><title>Create Demo Users</title>";
echo "<style>body{font-family:Arial;max-width:800px;margin:40px auto;padding:20px;background:#f8f9fa}";
echo "h1{color:#667eea}.success{color:#28a745;background:#d4edda;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #28a745}";
echo ".error{color:#dc3545;background:#f8d7da;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #dc3545}";
echo ".info{color:#004085;background:#cce5ff;padding:15px;margin:10px 0;border-radius:8px;border-left:4px solid #004085}";
echo ".btn{background:#667eea;color:white;padding:12px 24px;border:none;cursor:pointer;border-radius:8px;font-weight:bold;margin:10px 5px}";
echo ".btn:hover{background:#5568d3}</style></head><body>";

echo "<h1>👥 Create Demo Users</h1>";

if (!isset($_GET['create'])) {
    echo "<div class='info'>";
    echo "<h3>This will create:</h3>";
    echo "<strong>1. Sarah (Student - Borrower)</strong><br>";
    echo "• Email: sarah.student@ashesi.edu.gh<br>";
    echo "• Password: Demo123!<br>";
    echo "• Role: Borrower looking for items<br><br>";
    echo "<strong>2. John (Professor - Owner)</strong><br>";
    echo "• Email: john.prof@ashesi.edu.gh<br>";
    echo "• Password: Demo123!<br>";
    echo "• Role: Owner with items to lend<br>";
    echo "</div>";
    echo "<button class='btn' onclick=\"window.location.href='?create=yes'\">✅ Create Demo Users</button>";
    echo "<button class='btn' style='background:#6c757d' onclick=\"window.location.href='complete-workflow-guide.html'\">← Back</button>";
    echo "</body></html>";
    exit;
}

try {
    // Check if users already exist
    $stmt = $conn->prepare("SELECT id, email FROM users WHERE email IN (?, ?)");
    $stmt->execute(['sarah.student@ashesi.edu.gh', 'john.prof@ashesi.edu.gh']);
    $existing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existingEmails = array_column($existing, 'email');
    
    // Create Sarah if not exists
    if (!in_array('sarah.student@ashesi.edu.gh', $existingEmails)) {
        $password = password_hash('Demo123!', PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (email, password_hash, full_name, phone, is_verified) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'sarah.student@ashesi.edu.gh',
            $password,
            'Sarah Student',
            '+233501234567',
            1
        ]);
        $sarahId = $conn->lastInsertId();
        
        // Create profile for Sarah
        $stmt = $conn->prepare("INSERT INTO profiles (user_id, full_name, email, bio, avatar_url, location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $sarahId,
            'Sarah Student',
            'sarah.student@ashesi.edu.gh',
            'Computer Science student at Ashesi University. Looking for items to borrow for projects and events.',
            'https://ui-avatars.com/api/?name=Sarah+Student&background=667eea&color=fff&size=200',
            'Ashesi University Campus'
        ]);
        
        echo "<div class='success'>✅ Created Sarah Student (ID: $sarahId)</div>";
    } else {
        echo "<div class='info'>ℹ️ Sarah Student already exists</div>";
        $sarahId = $existing[array_search('sarah.student@ashesi.edu.gh', $existingEmails)]['id'];
    }
    
    // Create John if not exists
    if (!in_array('john.prof@ashesi.edu.gh', $existingEmails)) {
        $password = password_hash('Demo123!', PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (email, password_hash, full_name, phone, is_verified) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            'john.prof@ashesi.edu.gh',
            $password,
            'John Professor',
            '+233507654321',
            1
        ]);
        $johnId = $conn->lastInsertId();
        
        // Create profile for John
        $stmt = $conn->prepare("INSERT INTO profiles (user_id, full_name, email, bio, avatar_url, location) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $johnId,
            'John Professor',
            'john.prof@ashesi.edu.gh',
            'Professor at Ashesi University. Happy to lend equipment to students for academic projects.',
            'https://ui-avatars.com/api/?name=John+Professor&background=764ba2&color=fff&size=200',
            'Ashesi University Campus'
        ]);
        
        echo "<div class='success'>✅ Created John Professor (ID: $johnId)</div>";
    } else {
        echo "<div class='info'>ℹ️ John Professor already exists</div>";
        $johnId = $existing[array_search('john.prof@ashesi.edu.gh', $existingEmails)]['id'];
    }
    
    // Create John's projector item if not exists
    $stmt = $conn->prepare("SELECT id FROM items WHERE user_id = ? AND title LIKE '%Projector%'");
    $stmt->execute([$johnId]);
    $projector = $stmt->fetch();
    
    if (!$projector) {
        // Get Electronics category ID
        $stmt = $conn->query("SELECT id FROM categories WHERE name LIKE '%Electronics%' LIMIT 1");
        $category = $stmt->fetch();
        $categoryId = $category ? $category['id'] : 1;
        
        $stmt = $conn->prepare("INSERT INTO items (title, description, category_id, condition_status, price, rental_period, location, user_id, status, image_urls) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            'Epson EX3280 Projector',
            'High-quality projector perfect for presentations and events. 3LCD technology, 3300 lumens brightness, WXGA resolution (1280x800). Includes HDMI cable, remote control, and carrying case. Great condition, well-maintained.',
            $categoryId,
            'Excellent',
            25.00,
            'daily',
            'Ashesi University - Main Library, 2nd Floor',
            $johnId,
            'available',
            json_encode(['https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=600&h=400&fit=crop'])
        ]);
        $projectorId = $conn->lastInsertId();
        echo "<div class='success'>✅ Created Epson EX3280 Projector listing (ID: $projectorId) for John</div>";
    } else {
        echo "<div class='info'>ℹ️ John already has a projector listed</div>";
    }
    
    // Create more items for John
    $johnItems = [
        [
            'title' => 'Scientific Calculator (TI-84 Plus)',
            'description' => 'Texas Instruments TI-84 Plus graphing calculator. Perfect for math, statistics, and engineering courses. All functions working perfectly.',
            'price' => 5.00,
            'condition' => 'Good',
            'image' => 'https://images.unsplash.com/photo-1611224923853-80b023f02d71?w=600&h=400&fit=crop'
        ],
        [
            'title' => 'DSLR Camera - Canon EOS Rebel',
            'description' => 'Canon EOS Rebel T7 with 18-55mm lens. Great for photography projects, events, and content creation. Includes memory card and battery charger.',
            'price' => 40.00,
            'condition' => 'Excellent',
            'image' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&h=400&fit=crop'
        ]
    ];
    
    foreach ($johnItems as $item) {
        $stmt = $conn->prepare("SELECT id FROM items WHERE user_id = ? AND title = ?");
        $stmt->execute([$johnId, $item['title']]);
        if (!$stmt->fetch()) {
            $stmt = $conn->prepare("INSERT INTO items (title, description, category_id, condition_status, price, rental_period, location, user_id, status, image_urls) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $item['title'],
                $item['description'],
                $categoryId,
                $item['condition'],
                $item['price'],
                'daily',
                'Ashesi University Campus',
                $johnId,
                'available',
                json_encode([$item['image']])
            ]);
            echo "<div class='success'>✅ Created " . $item['title'] . " for John</div>";
        }
    }
    
    echo "<div class='success'>";
    echo "<h3>✅ Setup Complete!</h3>";
    echo "<p><strong>You can now login with these accounts:</strong></p>";
    echo "<p>📧 <strong>Sarah:</strong> sarah.student@ashesi.edu.gh / Demo123!<br>";
    echo "📧 <strong>John:</strong> john.prof@ashesi.edu.gh / Demo123!</p>";
    echo "</div>";
    
    echo "<button class='btn' onclick=\"window.location.href='/pages/login.html'\">🔐 Go to Login</button>";
    echo "<button class='btn' onclick=\"window.location.href='/complete-workflow-guide.html'\">📖 View Workflow Guide</button>";
    echo "<button class='btn' onclick=\"window.location.href='/seed-demo-data.php'\">💬 Seed Conversations</button>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "</body></html>";
?>
