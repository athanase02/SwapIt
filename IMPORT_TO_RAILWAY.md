# Import SI2025.sql to Railway MySQL Database

## Method 1: Using Railway's Connect Feature (Easiest)

1. **Click the "Connect" button** in your Railway MySQL Database tab
2. Railway will provide you with connection credentials
3. **Copy the connection command** (it looks like):
   ```bash
   mysql -h containers-us-west-xyz.railway.app -u root -p
   ```
4. **Run the command** in your terminal and enter the password when prompted
5. **Import the database**:
   ```bash
   mysql -h containers-us-west-xyz.railway.app -u root -p < db/SI2025.sql
   ```

## Method 2: Using Railway's Variables Tab

1. Go to your Railway MySQL service → **Variables** tab
2. Copy these connection details:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLUSER`
   - `MYSQLPASSWORD`
   - `MYSQLDATABASE`

3. **Open PowerShell in your project folder** and run:
   ```powershell
   # Replace with your actual values
   mysql -h <MYSQLHOST> -P <MYSQLPORT> -u <MYSQLUSER> -p<MYSQLPASSWORD> < db\SI2025.sql
   ```

   Example:
   ```powershell
   mysql -h containers-us-west-123.railway.app -P 3306 -u root -pYourPassword123 < db\SI2025.sql
   ```

## Method 3: Using MySQL Workbench (GUI Option)

1. **Download MySQL Workbench**: https://dev.mysql.com/downloads/workbench/
2. **Create new connection** with Railway credentials
3. **Click Server** → **Data Import**
4. Select **"Import from Self-Contained File"**
5. Browse to `db/SI2025.sql`
6. Click **Start Import**

## Method 4: Copy-Paste via Railway's Query Tab (If available)

Some Railway MySQL services have a Query interface:

1. Go to Railway MySQL service → **Data** tab
2. Look for a "Query" or "Execute SQL" option
3. Open `db/SI2025.sql` in VS Code
4. Copy ALL contents (Ctrl+A, Ctrl+C)
5. Paste into Railway's query interface
6. Execute

## After Import: Update Render Environment Variables

Once the database is imported, update your Render web service with Railway credentials:

1. Go to Render.com → Your SwapIt web service
2. Go to **Environment** tab
3. Add/Update these variables:
   ```
   RAILWAY_DB_HOST=<your-railway-host>
   RAILWAY_DB_USER=root
   RAILWAY_DB_PASSWORD=<your-railway-password>
   RAILWAY_DB_NAME=SI2025
   RAILWAY_DB_PORT=3306
   ```
4. Click **Save Changes** (Render will auto-redeploy)

## Verify the Import

After import, check if tables exist:

```sql
USE SI2025;
SHOW TABLES;
```

You should see 28 tables including:
- users
- profiles
- items
- borrow_requests
- messages
- conversations
- notifications
- user_online_status
- meeting_schedules
- ratings
- user_activities
- And 17 more...

## Troubleshooting

**Error: "mysql: command not found"**
- Install MySQL Client: https://dev.mysql.com/downloads/mysql/
- Or use MySQL Workbench instead (Method 3)

**Error: "Access denied"**
- Double-check your password (no space between -p and password)
- Verify credentials in Railway's Variables tab

**Error: "Database doesn't exist"**
- The SI2025.sql file creates the database automatically
- Make sure you're running the full file import, not individual queries

**Connection timeout**
- Check Railway service is running
- Verify your IP isn't blocked (Railway usually allows all IPs)
- Try again in a few minutes

## Sample Data Included

The SI2025.sql includes:
- ✅ 8 demo users (Athanase, Mabinty, Olivier, Victoria, Admin, Kwame, Ama, Kofi)
- ✅ 15 categories
- ✅ 15 sample items
- ✅ Sample borrow requests, messages, reviews
- ✅ All tables and indexes properly configured

**Password for all demo users**: `password` (hashed: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`)

You can log in with any of these emails and test the platform immediately!
