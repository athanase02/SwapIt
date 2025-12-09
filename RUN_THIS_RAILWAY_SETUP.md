# Railway MySQL Reset - Complete Guide

## Fresh Start: SI2025 Only

All configuration files have been updated to use **SI2025** database exclusively.

---

## Execute These Steps in Railway Web Dashboard

### 🔗 Railway MySQL Dashboard
**URL:** https://railway.com/project/dcad23ac-3cb1-4384-9f28-67c6e9c6e248/service/5c8d64ec-c56c-46bd-a486-0b053a773342

**Credentials:**
- Host: `yamabiko.proxy.rlwy.net:53608`
- Username: `root`
- Password: `oxkHZYorRjhuudWnSGROQmSiYMSokBqq`

---

## Step-by-Step Import Process

### ✅ STEP 1: Reset and Create SI2025
1. Open Railway Dashboard → MySQL service → **Query** tab
2. Copy entire content from: **`db/railway_reset_step1.sql`**
3. Paste and **Execute**
4. Verify: Should see "SI2025" in databases list

**What this does:**
- Drops: railway, si2025, SI2025 (all lowercase/mixed variations)
- Creates: Fresh SI2025 database with UTF8MB4

---

### ✅ STEP 2: Import Complete Schema + Data
1. In Railway Query tab, copy entire content from: **`db/SI2025.sql`** (797 lines)
2. Paste and **Execute**

**What this imports:**
- ✅ 28 core tables (users, items, categories, transactions, messages, etc.)
- ✅ 8 sample users
- ✅ 15 sample items
- ✅ 15 categories
- ✅ 5 borrow requests
- ✅ 6 transactions
- ✅ 5 reviews
- ✅ 5 messages
- ✅ Site settings
- ✅ Activity logs
- ✅ 2 views (active_listings, user_dashboard_stats)
- ✅ Performance indexes

**Expected output:**
```
SwapIt Database (SI2025) created successfully!
total_users: 8
total_items: 15
total_requests: 5
total_reviews: 5
total_categories: 15
```

---

### ✅ STEP 3: Verify Complete Import

Run these queries in Railway to verify:

```sql
-- Use SI2025 database
USE SI2025;

-- Check all tables (should be 28)
SHOW TABLES;

-- Verify data counts
SELECT 
    (SELECT COUNT(*) FROM users) as users,
    (SELECT COUNT(*) FROM items) as items,
    (SELECT COUNT(*) FROM categories) as categories,
    (SELECT COUNT(*) FROM borrow_requests) as requests,
    (SELECT COUNT(*) FROM messages) as messages,
    (SELECT COUNT(*) FROM transactions) as transactions,
    (SELECT COUNT(*) FROM reviews) as reviews,
    (SELECT COUNT(*) FROM notifications) as notifications;

-- Check views exist
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Test a view
SELECT * FROM active_listings LIMIT 3;
```

**Expected Results:**
- Tables: 28
- Users: 8
- Items: 15
- Categories: 15
- Borrow requests: 5
- Messages: 5
- Transactions: 6
- Reviews: 5
- Notifications: 5
- Views: 2 (active_listings, user_dashboard_stats)

---

## Configuration Updates ✅

All files now use **SI2025**:

- ✅ `config/db.php` → Default database: SI2025
- ✅ `db/sample_data.sql` → USE SI2025
- ✅ `import-sample-data.php` → dbname: SI2025

---

## Sample User Accounts (After Import)

```
Email: athanase.abayo@ashesi.edu.gh
Password: password (hashed: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi)

Email: mabinty.mambu@ashesi.edu.gh
Password: password

Email: olivier.kwizera@ashesi.edu.gh
Password: password

Email: victoria.nyonato@ashesi.edu.gh
Password: password

Admin Account:
Email: admin@swapit.com
Password: password
```

---

## Railway Environment Variables

Set these in Railway Dashboard → MySQL Service → Variables:

```bash
MYSQLHOST=yamabiko.proxy.rlwy.net
MYSQLPORT=53608
MYSQLDATABASE=SI2025
MYSQLUSER=root
MYSQLPASSWORD=oxkHZYorRjhuudWnSGROQmSiYMSokBqq
```

Or use the connection string:
```
MYSQL_URL=mysql://root:oxkHZYorRjhuudWnSGROQmSiYMSokBqq@yamabiko.proxy.rlwy.net:53608/SI2025
```

---

## Troubleshooting

### "MySQL server has gone away"
- **Solution:** Use Railway Web UI (not command line)
- Railway web interface has no timeout issues

### "Table already exists"
- **Solution:** Run Step 1 first to drop everything
- Or manually: `DROP DATABASE SI2025;` then recreate

### "Foreign key constraint fails"
- **Solution:** SI2025.sql has correct table order
- Tables are created in proper dependency sequence
- If error occurs, run `db/complete_import.sql` after

### Views not showing
- **Solution:** SI2025.sql includes CREATE VIEW statements
- Check with: `SHOW FULL TABLES WHERE Table_type = 'VIEW';`

---

## Summary

**Before:** Multiple databases (railway, si2025, SI2025) causing confusion
**After:** Single clean **SI2025** database with complete schema + data

**Total Import Time:** ~30 seconds via Railway Web UI

**Files to Import (in order):**
1. `db/railway_reset_step1.sql` (create SI2025)
2. `db/SI2025.sql` (complete schema + data)

That's it! ✅
