# 🚀 GitHub Push & Render Auto-Deploy Guide

## ✅ Pre-Deployment Checklist

### 1. Railway Database Credentials
Your Railway database credentials have been configured in `config/db.php`. Verify these match your Railway dashboard:

**Current Configuration:**
- Host: `shinkansen.proxy.rlwy.net`
- Port: `56904`
- Database: `railway`
- User: `root`
- Password: `JJJKhMufpprtiSlcREMoPfpjHwivYjnd`

### 2. Render Environment Variables Required

Add these to your Render dashboard (https://dashboard.render.com):

#### Railway Database Connection
```bash
# Get these from Railway Dashboard: https://railway.app
RAILWAY_DB_HOST=turntable.proxy.rlwy.net
RAILWAY_DB_PORT=57424
RAILWAY_DB_NAME=railway
RAILWAY_DB_USER=root
RAILWAY_DB_PASSWORD=your-railway-password-from-dashboard
```

#### Alternative: Use DB_* prefix (fallback)
```bash
DB_HOST=turntable.proxy.rlwy.net
DB_PORT=57424
DB_NAME=railway
DB_USER=root
DB_PASSWORD=your-railway-password-from-dashboard
```

#### Google OAuth
```bash
# Get these from Google Cloud Console: https://console.cloud.google.com
GOOGLE_CLIENT_ID=your-google-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=https://swapit-tjoj.onrender.com/api/google-callback.php
```

## 📝 Steps to Push and Deploy

### Step 1: Check Git Status
```bash
git status
```

### Step 2: Stage All Changes
```bash
git add .
```

### Step 3: Commit Changes
```bash
git commit -m "Configure Railway database connection for Render deployment"
```

### Step 4: Push to GitHub
```bash
git push origin master
```

### Step 5: Render Auto-Deploy
Render will automatically detect the push and start deploying. Watch the logs at:
https://dashboard.render.com/web/your-service-id

## 🔧 Configuration Details

### Database Connection Priority

The `config/db.php` file checks for database connections in this order:

1. **Render Environment** (if `DB_HOST` or `RAILWAY_DB_HOST` is set)
   - Uses Railway database via public URL
   - Primary: `turntable.proxy.rlwy.net:57424`
   - Fallback to DB_* variables

2. **Railway Environment** (if `MYSQLHOST` or `RAILWAY_DB_HOST` is set)
   - Uses Railway internal connection
   - For apps deployed on Railway

3. **Local Development** (fallback)
   - Uses: `shinkansen.proxy.rlwy.net:56904`
   - Hardcoded for local testing

### Security Features
- ✅ SSL verification disabled for Railway proxy
- ✅ Buffered queries enabled
- ✅ 15-second timeout for external connections
- ✅ PDO prepared statements for SQL injection protection

## 🎯 Render Dashboard Setup

### A. Add Environment Variables

1. Go to https://dashboard.render.com
2. Select your **swapit-tjoj** service
3. Click **Environment** in left sidebar
4. Click **Add Environment Variable**
5. Add each variable from the list above
6. Click **Save Changes**

### B. Configure Build Settings

Ensure these are set:
- **Build Command**: `./render-build.sh` (or leave empty)
- **Start Command**: `./render-start.sh`
- **Environment**: `Docker` or `PHP`

### C. Auto-Deploy Settings

Enable auto-deploy from GitHub:
1. Go to **Settings** tab
2. Find **Auto-Deploy** section
3. Ensure it's set to **Yes**
4. Branch: `master`

## 🧪 Testing After Deployment

### 1. Check Render Logs
Watch for this message:
```
SwapIt: Connected to Railway MySQL from Render (turntable.proxy.rlwy.net:57424/railway)
SwapIt: Database has 30 tables
```

### 2. Test the Application
Visit: https://swapit-tjoj.onrender.com

### 3. Test Database Connection
Create a test endpoint or check existing users load correctly

### 4. Test Login
Try logging in with:
- Email: `athanase.abayo@ashesi.edu.gh`
- Password: (your test password)

## ⚠️ Important Notes

### Railway Database Status
If Railway database shows as "sleeping" or "stopped":
1. Go to https://railway.app/dashboard
2. Select your MySQL service
3. Check status and restart if needed
4. Wait 30-60 seconds for it to wake up

### Credentials Verification
Before pushing, verify in Railway dashboard:
1. Go to your MySQL service
2. Click **Variables** tab
3. Confirm these match:
   - `MYSQLHOST`
   - `MYSQLPORT`
   - `MYSQLPASSWORD`

### Update Credentials if Changed
If Railway credentials change:
1. Update local: `config/db.php` (lines 60-65)
2. Update Render: Environment variables
3. Commit and push changes
4. Render will auto-deploy

## 🔍 Troubleshooting

### "MySQL server has gone away"
- Railway database is sleeping (wait 30s, try again)
- Wrong credentials (verify in Railway dashboard)
- Network timeout (increase timeout in db.php)

### Render Build Fails
- Check Render logs for specific error
- Verify `render-build.sh` has execute permissions
- Check `render.yaml` configuration

### Can't Connect to Database
- Verify environment variables in Render
- Check Railway database is running
- Test with: `https://swapit-tjoj.onrender.com/api/test-connection.php`

### Google OAuth Not Working
- Update `GOOGLE_REDIRECT_URI` to match Render URL
- Update Google Cloud Console with Render callback URL
- Verify `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET`

## 📋 Files Modified

- ✅ `config/db.php` - Database connection logic
- ✅ `render.yaml` - Render service configuration
- ✅ `render-build.sh` - Build script
- ✅ `render-start.sh` - Start script
- ✅ Test files created for verification

## 🎉 Ready to Push!

Run these commands now:

```bash
# Check what will be committed
git status

# Add all changes
git add .

# Commit with descriptive message
git commit -m "Configure Railway MySQL for Render deployment

- Updated database connection to use Railway credentials
- Added support for RAILWAY_DB_* environment variables
- Configured Render auto-deploy settings
- Updated connection timeouts and SSL settings"

# Push to GitHub (triggers Render auto-deploy)
git push origin master
```

After pushing, monitor Render deployment at:
https://dashboard.render.com

---

**Status**: ✅ Ready for GitHub Push
**Render**: 🔄 Will auto-deploy on push
**Database**: 🗄️ Railway MySQL configured
**Environment**: ⚙️ Variables documented above

Need help? Check Render logs or Railway dashboard for connection status.
