# Railway MySQL Import Guide

## Database Setup Complete Guide

Your new Railway MySQL instance is ready. Follow these steps to import the complete SwapIt schema and data.

### Connection Details
```
Host: yamabiko.proxy.rlwy.net
Port: 53608
Database: railway
Username: root
Password: oxkHZYorRjhuudWnSGROQmSiYMSokBqq
Public URL: mysql://root:oxkHZYorRjhuudWnSGROQmSiYMSokBqq@yamabiko.proxy.rlwy.net:53608/railway
```

### Option 1: Railway Web Dashboard (RECOMMENDED)

1. **Access Railway Dashboard**
   - Go to: https://railway.com/project/dcad23ac-3cb1-4384-9f28-67c6e9c6e248/service/5c8d64ec-c56c-46bd-a486-0b053a773342
   - Click on the MySQL service
   - Find the "Query" or "Data" tab

2. **Import Schema**
   - Copy the entire content from `db/SI2025.sql`
   - Paste into the Railway query editor
   - Click Execute
   - Some foreign key errors are expected (we'll fix these next)

3. **Fix Remaining Tables**
   - Copy the entire content from `db/complete_import.sql`
   - Paste into Railway query editor
   - Click Execute
   - This creates any tables that failed due to foreign key ordering

4. **Import Sample Data**
   - Copy the entire content from `db/sample_data.sql`
   - Paste into Railway query editor
   - Click Execute
   - This populates: 8 users, 15 items, 15 categories, messages, transactions, etc.

### Option 2: MySQL Workbench (Alternative)

1. **Connect**
   - Host: `yamabiko.proxy.rlwy.net`
   - Port: `53608`
   - Username: `root`
   - Password: `oxkHZYorRjhuudWnSGROQmSiYMSokBqq`
   - Default Schema: `railway`

2. **Import Files**
   - File → Run SQL Script
   - Select `db/SI2025.sql` → Execute
   - Select `db/complete_import.sql` → Execute
   - Select `db/sample_data.sql` → Execute

### Option 3: Command Line (if Railway CLI works)

```bash
# Make sure you're linked to the new project
railway link

# Import schema
railway run --service MySQL -- mysql railway < db/SI2025.sql

# Import remaining tables
railway run --service MySQL -- mysql railway < db/complete_import.sql

# Import sample data
railway run --service MySQL -- mysql railway < db/sample_data.sql
```

### Expected Results

After successful import:

**Tables (28):**
- users, profiles, categories, items
- borrow_requests, transactions, reviews, ratings
- conversations, messages, notifications
- saved_items, cart_items, user_activities
- And 14 more...

**Sample Data:**
- 8 users (athanase.abayo@ashesi.edu.gh, etc.)
- 15 items (MacBook, PS5, Guitar, Camera, etc.)
- 15 categories (Books, Electronics, Sports, etc.)
- 5 borrow requests
- 6 transactions
- 5 reviews
- 5 messages
- 5 notifications

**Views (2):**
- active_listings
- user_dashboard_stats

### Verification Queries

Run these in Railway Query tab to verify:

```sql
-- Check all tables
SHOW TABLES;

-- Verify data counts
SELECT 'users' as table_name, COUNT(*) as count FROM users
UNION ALL SELECT 'items', COUNT(*) FROM items
UNION ALL SELECT 'categories', COUNT(*) FROM categories
UNION ALL SELECT 'borrow_requests', COUNT(*) FROM borrow_requests
UNION ALL SELECT 'messages', COUNT(*) FROM messages;

-- Check views
SHOW FULL TABLES WHERE Table_type = 'VIEW';

-- Test a view
SELECT * FROM active_listings LIMIT 5;
```

### Update Application Config

Your `config/db.php` is already configured for the new Railway instance. When deployed to Railway, it will automatically use these environment variables:

- `MYSQLHOST`
- `MYSQLPORT`
- `MYSQLDATABASE`
- `MYSQLUSER`
- `MYSQLPASSWORD`

### Next Steps

1. Import database using Railway Web UI (Option 1)
2. Verify data with verification queries
3. Update Railway environment variables if needed
4. Deploy your application to Railway
5. Test the application with the new database

### Troubleshooting

**"MySQL server has gone away"**
- This is due to timeout on external connections
- Use Railway Web UI instead (always works)

**"Table already exists"**
- Normal if re-running scripts
- You can safely ignore these errors

**Foreign key constraint errors**
- These are handled by the import sequence
- SI2025.sql creates most tables
- complete_import.sql fixes the rest
- Both together create all 28 tables

### Sample Login Credentials (After Import)

```
Email: athanase.abayo@ashesi.edu.gh
Password: password (hashed in database)

Email: mabinty.mambu@ashesi.edu.gh
Password: password

Email: olivier.kwizera@ashesi.edu.gh
Password: password
```

---

✅ Configuration files updated
✅ SQL import files ready
⏳ Awaiting database import via Railway Web UI
