# Quick Integration Guide - Real-Time Features

## Step-by-Step Integration

### 1. Database Setup (5 minutes)

**Connect to Railway MySQL:**
```bash
# Get connection details from Railway dashboard
# Then run:
mysql -h <railway-host> -u root -p <database-name> < db/real_time_transactions.sql
```

**Or use Railway Web Console:**
1. Go to Railway dashboard
2. Open MySQL service
3. Click "Connect"
4. Copy and paste contents of `db/real_time_transactions.sql`
5. Execute

### 2. Add Scripts to Existing Pages (2 minutes per page)

**Find this in your HTML files:**
```html
</head>
<body>
```

**Add BEFORE `</head>`:**
```html
    <!-- Real-Time Features -->
    <link rel="stylesheet" href="../assets/css/real-time-notifications.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
```

**Add BEFORE `</body>`:**
```html
    <!-- Real-Time Scripts -->
    <script src="../assets/js/real-time-notifications.js"></script>
</body>
```

### 3. Update Navigation Bar (10 minutes)

**Find your navigation bar** (usually in each HTML file or a shared header):

**Add this notification bell** (place near login/profile area):
```html
<!-- Add to navigation -->
<div style="position: relative; display: inline-block;">
    <div class="notification-bell-container" id="notificationBell">
        <i class="fas fa-bell notification-bell"></i>
        <span class="notification-badge">0</span>
    </div>
    
    <div class="notification-panel" id="notificationPanel">
        <div class="notification-panel-header">
            <span class="notification-panel-title">Notifications</span>
            <button class="mark-all-read-btn" id="markAllReadBtn">Mark all as read</button>
        </div>
        <div class="notifications-list" id="notificationsList">
            <div class="loading">Loading...</div>
        </div>
    </div>
</div>
```

### 4. Update Messages Page (ALREADY DONE ✅)

Your `pages/messages.html` already has:
- Typing indicators
- Online status
- Real-time messaging

No changes needed!

### 5. Update Requests Page (10 minutes)

**Open:** `public/pages/requests.html`

**Find the section where request details are shown**

**Add these buttons for scheduled/active requests:**
```html
<!-- For scheduled requests -->
<button class="confirm-pickup-btn" data-request-id="<?= $requestId ?>">
    <i class="fas fa-check"></i> Confirm Pickup
</button>

<!-- For active requests -->
<button class="confirm-return-btn" data-request-id="<?= $requestId ?>">
    <i class="fas fa-undo"></i> Confirm Return
</button>

<!-- For any request -->
<button class="report-issue-btn" data-request-id="<?= $requestId ?>">
    <i class="fas fa-exclamation-circle"></i> Report Issue
</button>
```

**Add transaction manager script before `</body>`:**
```html
    <script src="../assets/js/transaction-manager.js"></script>
</body>
```

### 6. Add Transactions Page Link (2 minutes)

**In your dashboard/navigation**, add a link to the new transactions page:

```html
<a href="/pages/transactions.html">
    <i class="fas fa-exchange-alt"></i> Transactions
</a>
```

### 7. Test Everything (15 minutes)

**Testing Checklist:**

1. **Notifications:**
   - [ ] Bell icon appears in navigation
   - [ ] Click bell - panel opens
   - [ ] Badge shows unread count
   - [ ] Notifications load

2. **Messages:**
   - [ ] Typing indicator shows when typing
   - [ ] Online status visible
   - [ ] Messages send/receive

3. **Requests:**
   - [ ] Can create borrow request
   - [ ] Notifications received
   - [ ] Can accept/reject
   - [ ] Can schedule meeting

4. **Transactions:**
   - [ ] Can confirm pickup
   - [ ] Can confirm return
   - [ ] Can report issues
   - [ ] History displays

## Common Integration Points

### A. Messages Badge (in navigation)
```html
<a href="/pages/messages.html">
    Messages <span class="messages-badge">0</span>
</a>
```

### B. Requests Badge (in navigation)
```html
<a href="/pages/requests.html">
    Requests <span class="requests-badge">0</span>
</a>
```

### C. Online Status (next to usernames)
```html
<span class="online-status online"></span> Username
```

### D. Transaction Button (in request details)
```html
<div class="request-actions">
    <button class="confirm-pickup-btn" data-request-id="123">
        Confirm Pickup
    </button>
</div>
```

## File Checklist

Make sure these files exist:

**Backend APIs:**
- [ ] `api/notifications.php`
- [ ] `api/transactions.php`
- [ ] `api/messages.php` (existing, updated)
- [ ] `api/requests.php` (existing, updated)

**Frontend JavaScript:**
- [ ] `public/assets/js/real-time-notifications.js`
- [ ] `public/assets/js/transaction-manager.js`
- [ ] `public/assets/js/messaging.js` (existing, updated)
- [ ] `public/assets/js/request-manager.js` (existing)

**Stylesheets:**
- [ ] `public/assets/css/real-time-notifications.css`
- [ ] `public/assets/css/messaging.css` (existing, updated)

**Pages:**
- [ ] `public/pages/messages.html` (existing, updated)
- [ ] `public/pages/requests.html` (existing)
- [ ] `public/pages/transactions.html` (new)

**Database:**
- [ ] `db/real_time_transactions.sql`

## Quick Test Script

**Create a test file:** `public/test-realtime.php`

```php
<?php
session_start();
require_once '../config/db.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

echo "<h1>Real-Time System Test</h1>";

// Test 1: Notifications table exists
$result = $conn->query("SHOW TABLES LIKE 'notifications'");
echo "<p>✓ Notifications table: " . ($result->num_rows > 0 ? "EXISTS" : "MISSING") . "</p>";

// Test 2: Transaction history table exists  
$result = $conn->query("SHOW TABLES LIKE 'transaction_history'");
echo "<p>✓ Transaction history table: " . ($result->num_rows > 0 ? "EXISTS" : "MISSING") . "</p>";

// Test 3: Online users table exists
$result = $conn->query("SHOW TABLES LIKE 'online_users'");
echo "<p>✓ Online users table: " . ($result->num_rows > 0 ? "EXISTS" : "MISSING") . "</p>";

// Test 4: Notifications API
$test_url = '../api/notifications.php?action=get_notifications';
$test_result = file_get_contents($test_url);
echo "<p>✓ Notifications API: " . (strpos($test_result, 'success') !== false ? "WORKING" : "ERROR") . "</p>";

// Test 5: Transactions API
$test_url = '../api/transactions.php?action=get_my_transactions&status=all';
$test_result = file_get_contents($test_url);
echo "<p>✓ Transactions API: " . (strpos($test_result, 'success') !== false ? "WORKING" : "ERROR") . "</p>";

echo "<h2>If all tests show 'EXISTS' or 'WORKING', you're good to go!</h2>";
?>
```

**Run test:**
```
http://localhost/SwapIt/public/test-realtime.php
```

## Troubleshooting Quick Fixes

### Problem: Notifications not loading
**Fix:**
```javascript
// Open browser console, run:
fetch('../api/notifications.php?action=get_notifications', {credentials: 'include'})
    .then(r => r.json())
    .then(console.log);
```

### Problem: Online status not showing
**Fix:**
```sql
-- Check if online_users table has data
SELECT * FROM online_users WHERE user_id = YOUR_USER_ID;
```

### Problem: Transaction buttons not working
**Fix:**
```javascript
// Check if script loaded
console.log(typeof transactionManager);
// Should output: "object"
```

### Problem: Typing indicator not appearing
**Fix:**
```javascript
// Check if messaging system loaded
console.log(typeof messagingSystem);
// Should output: "object"
```

## Production Deployment (Render)

### 1. Push to GitHub
```bash
cd C:\Users\Athanase\OneDrive\Desktop\Group_5_activity_04_Final_Project\activity_04_Final_Project

git add .
git commit -m "Add real-time transaction system with notifications"
git push origin master
```

### 2. Apply Database Schema to Railway
1. Login to Railway: https://railway.app
2. Select your MySQL database
3. Click "Connect"
4. Click "Query" tab
5. Paste contents of `db/real_time_transactions.sql`
6. Click "Run"

### 3. Verify Render Deployment
1. Go to Render dashboard
2. Wait for auto-deploy (triggered by GitHub push)
3. Check build logs
4. Visit your live site
5. Test notifications

### 4. Test Live Site
Open two different browsers (or incognito + normal):
- Browser 1: Login as User A
- Browser 2: Login as User B
- Test messaging, requests, transactions

## Minimum Integration (If Short on Time)

**Just want notifications working quickly?**

1. **Run SQL:**
   ```bash
   mysql -h railway-host -u root -p dbname < db/real_time_transactions.sql
   ```

2. **Add to every HTML page head:**
   ```html
   <link rel="stylesheet" href="../assets/css/real-time-notifications.css">
   ```

3. **Add to every HTML page body (before </body>):**
   ```html
   <script src="../assets/js/real-time-notifications.js"></script>
   ```

4. **Add notification bell to navigation** (copy from step 3 above)

5. **Done!** Notifications will work automatically.

## Next Actions

**Priority Order:**

1. ✅ **Apply database schema** (10 min)
2. ✅ **Add notification bell to navigation** (5 min)  
3. ✅ **Test notifications** (5 min)
4. ✅ **Add transaction buttons to requests page** (10 min)
5. ✅ **Test full workflow** (15 min)
6. ✅ **Deploy to production** (10 min)

**Total Time: ~1 hour**

## Support Commands

```bash
# Check if database tables exist
mysql -h host -u user -p -e "USE dbname; SHOW TABLES;"

# Test API locally
curl http://localhost/SwapIt/api/notifications.php?action=get_notifications

# View recent notifications
mysql -h host -u user -p -e "USE dbname; SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5;"

# Check online users
mysql -h host -u user -p -e "USE dbname; SELECT * FROM online_users WHERE status='online';"

# View transaction history
mysql -h host -u user -p -e "USE dbname; SELECT * FROM transaction_history ORDER BY created_at DESC LIMIT 5;"
```

## You're All Set! 🎉

The real-time system is now ready to use. Users can:
- Get instant notifications
- See online status
- Track transactions
- Confirm pickups/returns
- Report issues
- View complete history

All in real-time, within your website!
