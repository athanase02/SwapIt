# Railway Database Setup & Testing Guide

## 🚀 Your Changes Have Been Pushed!

Your real-time system code has been successfully pushed to GitHub. Render should automatically start redeploying your app.

---

## 📊 Step 1: Check Railway Database Connection

Once Render finishes deploying, test your Railway database connection:

### Visit This URL:
```
https://your-app.up.railway.app/public/test-railway-db.php
```

**Replace `your-app.up.railway.app` with your actual Railway domain**

### What This Test Will Show:
✅ Environment variables status (DB_HOST, DB_PORT, etc.)  
✅ Database connection success/failure  
✅ MySQL version  
✅ List of existing tables  
✅ Table row counts  
✅ Write permission test  

---

## 🔧 Step 2: If Database Connection Fails

### Option A: Fix Existing Railway MySQL Connection

1. **Go to Railway Dashboard**: https://railway.app/dashboard

2. **Check MySQL Service Status**:
   - Click on your MySQL service
   - Verify it's running (green indicator)
   - If stopped, restart it

3. **Get MySQL Connection Details**:
   - In MySQL service, click on "Variables" tab
   - Copy these values:
     - `MYSQL_HOST` or `MYSQLHOST`
     - `MYSQL_PORT` or `MYSQLPORT` (usually 3306)
     - `MYSQL_DATABASE` or `MYSQLDATABASE`
     - `MYSQL_USER` or `MYSQLUSER`
     - `MYSQL_PASSWORD` or `MYSQLPASSWORD`

4. **Set Environment Variables in Web Service**:
   - Go to your web service (not MySQL)
   - Click "Variables" tab
   - Add/Update these variables:
     ```
     DB_HOST = <MySQL host from step 3>
     DB_PORT = <MySQL port, usually 3306>
     DB_NAME = <MySQL database name>
     DB_USER = <MySQL user>
     DB_PASSWORD = <MySQL password>
     ```

5. **Redeploy**:
   - Railway should auto-redeploy when you add variables
   - Or click "Deploy" button manually

### Option B: Create New Railway MySQL Database

If your existing database has issues, create a fresh one:

1. **Add New MySQL Service**:
   ```
   Railway Dashboard → Your Project → "+ New" → Database → Add MySQL
   ```

2. **Wait for Deployment** (1-2 minutes)

3. **Get Connection Details**:
   - Click on new MySQL service
   - Go to "Variables" tab
   - You'll see:
     - `MYSQL_HOST` - Copy this
     - `MYSQL_PORT` - Usually 3306
     - `MYSQL_DATABASE` - Usually "railway"
     - `MYSQL_USER` - Usually "root"
     - `MYSQL_PASSWORD` - Copy this

4. **Update Web Service Variables**:
   - Go to your web service
   - Click "Variables"
   - Update all DB_* variables with new MySQL credentials:
     ```
     DB_HOST = <new MySQL host>
     DB_PORT = 3306
     DB_NAME = railway
     DB_USER = root
     DB_PASSWORD = <new MySQL password>
     ```

5. **Remove Old MySQL Service** (optional):
   - Go to old MySQL service
   - Settings → Remove Service

---

## 🗄️ Step 3: Run Database Migration

After confirming the database connection works:

### Visit Migration URL:
```
https://your-app.up.railway.app/public/setup-realtime.php
```

This will create all necessary tables:
- ✅ users
- ✅ items
- ✅ user_online_status
- ✅ conversations
- ✅ messages
- ✅ borrow_requests
- ✅ meeting_schedules
- ✅ transactions
- ✅ ratings
- ✅ notifications
- ✅ user_activities

### Expected Result:
You should see green checkmarks for all tables indicating successful creation.

---

## ✅ Step 4: Verify Everything Works

### 1. Test the App:
```
https://your-app.up.railway.app/pages/browse.html
```

### 2. Sign Up a Test User:
```
https://your-app.up.railway.app/pages/signup.html
```

### 3. Add a Test Item:
```
https://your-app.up.railway.app/pages/add-listing.html
```

### 4. Check Database Again:
```
https://your-app.up.railway.app/public/test-railway-db.php
```
Now you should see data in the tables!

---

## 🐛 Troubleshooting

### Issue: "Environment variables not set"

**Solution**: 
1. Go to Railway web service
2. Variables tab
3. Add all DB_* variables
4. Make sure to get them from your MySQL service

### Issue: "Connection timeout"

**Solution**:
1. Check if MySQL service is in the same Railway project
2. Verify MySQL service is running
3. Check if there's a network issue (rare)
4. Try creating a new MySQL service

### Issue: "Access denied"

**Solution**:
1. Double-check DB_PASSWORD matches exactly
2. Verify DB_USER is correct (usually "root")
3. Make sure you copied from the Variables tab, not the Connect tab

### Issue: "Database does not exist"

**Solution**:
1. Check DB_NAME variable
2. Usually should be "railway"
3. You can create a custom database name if needed:
   ```sql
   CREATE DATABASE your_database_name;
   ```

### Issue: "Tables not created after migration"

**Solution**:
1. Check error messages in setup-realtime.php
2. Verify write permissions
3. Run test-railway-db.php to confirm write access
4. Try running migration again

---

## 🎯 Quick Checklist

- [ ] Code pushed to GitHub ✅ (Already done!)
- [ ] Render started auto-deployment
- [ ] Railway MySQL service is running
- [ ] Environment variables set in Railway web service
- [ ] test-railway-db.php shows successful connection
- [ ] setup-realtime.php created all tables
- [ ] Can sign up a test user
- [ ] Can add a test item
- [ ] Browse page loads items

---

## 📞 Need Help?

### Check Render Logs:
1. Go to Render dashboard
2. Click on your service
3. Go to "Logs" tab
4. Look for database connection errors

### Check Railway Logs:
1. Go to Railway dashboard
2. Click on your web service
3. Go to "Deployments" tab
4. Click latest deployment
5. View logs for errors

### Common Log Errors:

**"Connection refused"**
- MySQL service not running or wrong host

**"Access denied for user"**
- Wrong password or username

**"Unknown database"**
- Wrong DB_NAME variable

**"Table doesn't exist"**
- Need to run migration

---

## 🔄 If You Need to Start Fresh

### Complete Reset:

1. **Delete Old MySQL**:
   - Railway → Old MySQL Service → Settings → Remove

2. **Create New MySQL**:
   - Railway → "+ New" → Database → Add MySQL

3. **Update Variables**:
   - Copy new MySQL credentials to web service

4. **Redeploy**:
   - Wait for auto-deploy or trigger manually

5. **Run Migration**:
   - Visit setup-realtime.php

6. **Test**:
   - Visit test-railway-db.php

---

## 🎉 Success Indicators

Your Railway database is working perfectly when:

1. ✅ test-railway-db.php shows "Connection Successful"
2. ✅ All environment variables are green
3. ✅ MySQL version is displayed
4. ✅ All 11 tables exist
5. ✅ Write test passes
6. ✅ You can sign up users
7. ✅ You can add items
8. ✅ Browse page loads without errors

---

## 📊 Expected Database Structure

After migration, you should have these tables:

| Table Name | Purpose |
|------------|---------|
| users | User accounts |
| items | Listed items |
| user_online_status | Who's online tracking |
| conversations | Message threads |
| messages | All messages |
| borrow_requests | Request details |
| meeting_schedules | Meeting arrangements |
| transactions | Completed borrows |
| ratings | User reviews |
| notifications | Real-time alerts |
| user_activities | Activity log |

---

## 🚀 Next Steps After Database Works

1. **Test with 2 users** (different browsers)
2. **Send messages** between users
3. **Create borrow requests**
4. **Accept/reject requests**
5. **Check real-time notifications**
6. **Verify online status tracking**

See **QUICK_START.md** for detailed testing instructions!

---

**Good luck! 🍀** Your Railway database should be up and running smoothly!
