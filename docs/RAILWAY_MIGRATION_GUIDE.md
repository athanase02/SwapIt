# Railway Database Migration - Complete Guide

## 🚨 Problem
The new real-time tables are not in Railway MySQL database, and messaging is not working.

## ✅ Solution - Step by Step

### Step 1: Access Railway MySQL Console

1. Go to: https://railway.com/project/666f0582-4f82-44be-a962-5943666dde65/service/ff9e43b8-fb28-40f2-b100-8e30fe488e9c/database?environmentId=cd0bf9ba-9189-42f3-b5f4-0d0b8ff01298

2. Click on the **"Query"** tab at the top

3. You'll see a SQL query console

### Step 2: Run the Migration Script

**Option A: Use the pre-made Railway script**

1. Open the file: `db/RAILWAY_MIGRATION.sql` 
2. Copy the ENTIRE contents
3. Paste into Railway Query console
4. Click **"Run"** or press Ctrl+Enter

**Option B: Run commands individually** (if the above fails)

Copy and paste each section one at a time:

```sql
-- 1. Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id INT,
    related_type VARCHAR(50),
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    action_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_type (type),
    KEY idx_is_read (is_read),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

```sql
-- 2. Create transaction_history table
CREATE TABLE IF NOT EXISTS transaction_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    borrower_id INT NOT NULL,
    lender_id INT NOT NULL,
    item_id INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    performed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_request_id (request_id),
    KEY idx_borrower_id (borrower_id),
    KEY idx_lender_id (lender_id),
    KEY idx_item_id (item_id),
    KEY idx_action_type (action_type),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

```sql
-- 3. Create online_users table
CREATE TABLE IF NOT EXISTS online_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    status ENUM('online', 'away', 'offline') DEFAULT 'offline',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_status (status),
    KEY idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

```sql
-- 4. Create user_activities table
CREATE TABLE IF NOT EXISTS user_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    activity_details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user_id (user_id),
    KEY idx_activity_type (activity_type),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

```sql
-- 5. Create meeting_schedules table
CREATE TABLE IF NOT EXISTS meeting_schedules (
    id INT PRIMARY KEY AUTO_INCREMENT,
    borrow_request_id INT NOT NULL,
    scheduled_by INT NOT NULL,
    meeting_type ENUM('online', 'offline') DEFAULT 'offline',
    meeting_date DATETIME NOT NULL,
    meeting_location VARCHAR(255),
    meeting_link VARCHAR(500),
    notes TEXT,
    meeting_status ENUM('scheduled', 'confirmed', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_borrow_request_id (borrow_request_id),
    KEY idx_scheduled_by (scheduled_by),
    KEY idx_meeting_date (meeting_date),
    KEY idx_meeting_status (meeting_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

```sql
-- 6. Create message_attachments table
CREATE TABLE IF NOT EXISTS message_attachments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    file_size INT NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_message_id (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Step 3: Verify Tables Were Created

Run this query in Railway console:

```sql
SHOW TABLES;
```

**You should see these NEW tables:**
- ✅ notifications
- ✅ transaction_history
- ✅ online_users
- ✅ user_activities
- ✅ meeting_schedules
- ✅ message_attachments

**Plus your EXISTING tables:**
- users
- profiles
- items
- categories
- borrow_requests
- conversations
- messages
- ratings

### Step 4: Check Table Structure

Verify each table was created correctly:

```sql
DESCRIBE notifications;
DESCRIBE transaction_history;
DESCRIBE online_users;
```

### Step 5: Deploy Updated Code to Render

Now that database is ready, push your code:

```bash
cd C:\Users\Athanase\OneDrive\Desktop\Group_5_activity_04_Final_Project\activity_04_Final_Project

git add .
git commit -m "Add real-time features with database migration"
git push origin master
```

Render will auto-deploy (takes 2-3 minutes).

### Step 6: Test on Render

1. Go to your Render URL: https://swap-it-something.onrender.com
2. Login as a user
3. Check browser console (F12) for any errors
4. Try sending a message
5. Check if notifications appear

---

## 🔧 Troubleshooting Common Issues

### Issue 1: "Table already exists" error

**Solution:** This is fine! It means the table was already created. The script is safe to run multiple times.

### Issue 2: "Syntax error" in Railway console

**Solution:** 
1. Make sure you copied the ENTIRE script
2. Try running tables one at a time (see Option B above)
3. Check there are no special characters in the query

### Issue 3: Messaging still not working after migration

**Check these:**

1. **Database connection on Render:**
   ```
   Go to Render Dashboard → Environment Variables
   Verify these exist:
   - DB_HOST (Railway host)
   - DB_PORT (usually 3306)
   - DB_NAME (railway)
   - DB_USER (root)
   - DB_PASSWORD (your Railway password)
   ```

2. **Test database connection:**
   - Visit: `https://your-render-url.onrender.com/api/test-connection.php`
   - Should show: "Connected successfully"

3. **Check API endpoint:**
   - Visit: `https://your-render-url.onrender.com/api/messages.php?action=get_conversations`
   - Should return JSON (even if empty)

4. **Check browser console:**
   - Press F12 on your site
   - Look for red errors
   - Common errors:
     - 404: File not found → Check file paths
     - 500: Server error → Check PHP errors
     - 401: Not authenticated → Login first

### Issue 4: Tables created but no data appears

**Solution:** Run the populate script:

```bash
# On Render, visit:
https://your-render-url.onrender.com/populate-sample-data.php
```

Or locally:
```bash
http://localhost/SwapIt/public/populate-sample-data.php
```

### Issue 5: "Column not found" errors in API

**Solution:** Add missing columns to borrow_requests:

```sql
ALTER TABLE borrow_requests ADD COLUMN IF NOT EXISTS return_condition VARCHAR(50);
ALTER TABLE borrow_requests ADD COLUMN IF NOT EXISTS start_date TIMESTAMP NULL;
ALTER TABLE borrow_requests ADD COLUMN IF NOT EXISTS end_date TIMESTAMP NULL;
```

---

## 📊 Quick Verification Checklist

Run these queries in Railway to verify everything:

```sql
-- 1. Check all tables exist
SHOW TABLES;

-- 2. Count records in each table
SELECT 'users' as tbl, COUNT(*) as cnt FROM users
UNION ALL SELECT 'conversations', COUNT(*) FROM conversations
UNION ALL SELECT 'messages', COUNT(*) FROM messages
UNION ALL SELECT 'borrow_requests', COUNT(*) FROM borrow_requests
UNION ALL SELECT 'notifications', COUNT(*) FROM notifications
UNION ALL SELECT 'transaction_history', COUNT(*) FROM transaction_history;

-- 3. Check if conversations table has data
SELECT * FROM conversations LIMIT 5;

-- 4. Check if messages table has data
SELECT * FROM messages LIMIT 5;

-- 5. Check users table
SELECT id, username, email FROM users LIMIT 5;
```

**Expected Results:**
- All tables should exist
- users, conversations, messages should have data (if you've used the app)
- New tables (notifications, transaction_history, etc.) will be empty initially

---

## 🎯 Alternative: Use PHP Migration Script

If Railway console is giving you trouble, use the PHP script:

1. **Upload `public/run-migration.php` to Render**

2. **Visit the URL:**
   ```
   https://your-render-url.onrender.com/run-migration.php
   ```

3. **The script will:**
   - Connect to Railway database
   - Create all tables
   - Show detailed status report
   - Verify everything works

4. **You'll see a nice HTML page showing:**
   - ✅ Which tables were created
   - ⚠️ Any warnings
   - ❌ Any errors
   - Summary of what was done

---

## 🚀 After Migration - Enable Real-Time Features

Once tables are created and verified:

### 1. Add notification bell to navigation

Edit your main navigation HTML file and add:

```html
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
```

### 2. Add scripts to all pages

Before closing `</head>` tag:
```html
<link rel="stylesheet" href="../assets/css/real-time-notifications.css">
```

Before closing `</body>` tag:
```html
<script src="../assets/js/real-time-notifications.js"></script>
```

### 3. Test the workflow

1. Login as User A
2. Open another browser (or incognito)
3. Login as User B
4. User B: Send message to User A
5. User A: Should see notification

---

## 📝 Quick Reference Commands

**Check Railway database name:**
```sql
SELECT DATABASE();
```

**Check MySQL version:**
```sql
SELECT VERSION();
```

**See all tables:**
```sql
SHOW TABLES;
```

**Delete a table (if needed):**
```sql
DROP TABLE IF EXISTS notifications;
```

**Check table structure:**
```sql
DESCRIBE tablename;
```

**See recent data:**
```sql
SELECT * FROM tablename ORDER BY id DESC LIMIT 10;
```

---

## 💡 Pro Tips

1. **Always use Railway Query console** - It's the most reliable way
2. **Run one table at a time** if you get errors
3. **Save your Railway credentials** - You'll need them
4. **Check Render logs** for PHP errors after deployment
5. **Use browser DevTools** (F12) to see JavaScript errors
6. **Test locally first** before deploying to production

---

## ❓ Still Having Issues?

### Check these in order:

1. ✅ Railway database has all tables
2. ✅ Render environment variables are correct
3. ✅ Files are pushed to GitHub
4. ✅ Render has deployed latest version
5. ✅ Browser cache is cleared (Ctrl+Shift+Delete)
6. ✅ User is logged in (session active)
7. ✅ API endpoints return valid JSON
8. ✅ No errors in browser console

### Get detailed error info:

**In Render dashboard:**
- Click "Logs" tab
- Look for PHP errors
- Check database connection errors

**In browser:**
- Press F12
- Go to Console tab
- Look for red errors
- Go to Network tab
- Check API calls (should be 200 status)

---

## ✅ Success Indicators

You'll know everything is working when:

1. ✅ Railway shows all 14 tables
2. ✅ Render deploys without errors
3. ✅ Messages page loads without console errors
4. ✅ You can send messages between users
5. ✅ Notification bell appears in navigation
6. ✅ Notifications show up when actions happen
7. ✅ No red errors in browser console
8. ✅ API endpoints return JSON data

---

## 🎉 Final Notes

The migration script is **safe to run multiple times**. If a table already exists, it will skip it. So you can run it as many times as needed without breaking anything.

After migration is complete and verified, you can delete these temporary files from production:
- `public/run-migration.php`
- `public/populate-sample-data.php`
- `public/test-*.php`

Keep them locally for testing, but add to `.renderignore` so they don't deploy to production.
