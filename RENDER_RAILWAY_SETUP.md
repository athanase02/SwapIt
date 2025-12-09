# Render + Railway Database Configuration Guide

## 🎯 Overview

Your Render frontend at **https://swapit-tjoj.onrender.com** is now configured to connect to your Railway MySQL database.

## 📝 Environment Variables for Render

Go to your Render dashboard → Service Settings → Environment and add these variables:

### Required Database Variables (Railway MySQL)

```
DB_HOST=crossover.proxy.rlwy.net
DB_PORT=20980
DB_NAME=railway
DB_USER=root
DB_PASSWORD=nLPPhjVDjtuxSKJiPHYQlxSKkvdGjtQx
```

### Google OAuth Configuration

```
GOOGLE_CLIENT_ID=your-google-client-id-here
GOOGLE_CLIENT_SECRET=your-google-client-secret-here
GOOGLE_REDIRECT_URI=https://your-render-url.onrender.com/api/google-callback.php
```

**Important**: Replace with your actual Google OAuth credentials and Render URL!

## 🔄 Deployment Steps

### 1. Add Environment Variables to Render

1. Go to https://dashboard.render.com
2. Select your **swapit-tjoj** service
3. Click **Environment** in the left sidebar
4. Add each variable listed above
5. Click **Save Changes**

### 2. Redeploy Your Application

After adding the environment variables:
- Render will automatically redeploy
- Or manually trigger: Click **Manual Deploy** → **Deploy latest commit**

### 3. Verify Connection

Once deployed, check your Render logs:
```
SwapIt: Connected to Railway MySQL from Render (crossover.proxy.rlwy.net:20980/railway)
SwapIt: Database has 30 tables
```

## 🧪 Testing the Connection

### Test Endpoint

Create a test file or visit:
```
https://swapit-tjoj.onrender.com/api/test-connection.php
```

### Expected Response
```json
{
  "status": "success",
  "message": "Connected to Railway MySQL",
  "database": "railway",
  "tables": 30,
  "users": 8
}
```

## 📊 Database Information

Your Railway database already contains:

- **30 tables** (complete schema)
- **8 users** with test data
- All required structure for SwapIt functionality

### Sample Users Available

- athanase.abayo@ashesi.edu.gh
- mabinty.mambu@ashesi.edu.gh
- olivier.kwizera@ashesi.edu.gh
- victoria.nyonato@ashesi.edu.gh
- admin@swapit.com

## 🔧 Configuration Details

### Connection Priority (config/db.php)

The application checks for database connection in this order:

1. **Render Environment** (`DB_HOST` set)
   - Uses: crossover.proxy.rlwy.net:20980
   - For production on Render

2. **Railway Environment** (`MYSQLHOST` set)
   - Uses Railway internal connection
   - For apps deployed directly on Railway

3. **Local Development** (fallback)
   - Uses: shinkansen.proxy.rlwy.net:32604
   - For your local machine

### Security Features

- SSL certificate verification disabled (required for Railway proxy)
- Buffered queries enabled for better compatibility
- 15-second timeout for external connections
- PDO with prepared statements for SQL injection protection

## 🚨 Troubleshooting

### Connection Timeout

If you see timeout errors:
1. Check Railway database is running (Railway dashboard)
2. Verify the DB_HOST and DB_PORT are correct
3. Railway proxy requires external connections on specific ports

### "Access Denied" Error

- Double-check DB_PASSWORD matches Railway exactly
- Ensure DB_USER is set to `root`
- Verify DB_NAME is `railway`

### Tables Not Found

If the app can't find tables:
1. The database already has all 30 tables
2. Check logs for "Database has X tables" message
3. If 0 tables, you may need to import schema again

### Google OAuth Issues

Update the redirect URI to match your Render domain:
```
https://swapit-tjoj.onrender.com/api/google-callback.php
```

Also update this in your Google Cloud Console:
1. Go to https://console.cloud.google.com
2. APIs & Services → Credentials
3. Edit your OAuth 2.0 Client ID
4. Add the Render callback URL to Authorized redirect URIs

## 📱 Multiple Environments

Your app now supports:

| Environment | Database Connection |
|------------|-------------------|
| **Local Dev** | Railway (shinkansen.proxy.rlwy.net:32604) |
| **Render Production** | Railway (crossover.proxy.rlwy.net:20980) |
| **Railway Production** | Railway (internal connection) |

All environments connect to the **same Railway database**, ensuring data consistency.

## ✅ Verification Checklist

- [ ] Environment variables added to Render
- [ ] Google redirect URI updated to Render URL
- [ ] Render service redeployed
- [ ] Check Render logs for successful connection
- [ ] Test login functionality
- [ ] Verify data loads correctly

## 🔐 Security Notes

⚠️ **Important**:
- Keep DB_PASSWORD secret
- Never commit credentials to Git
- Use Render's environment variable encryption
- Rotate passwords periodically on Railway

## 📚 Related Files

- `config/db.php` - Database connection logic (updated)
- `render.yaml` - Render service configuration
- `render-start.sh` - Render startup script
- `api/test-connection.php` - Connection test endpoint

## 🎉 Next Steps

Once deployed to Render:

1. **Test the application** at https://swapit-tjoj.onrender.com
2. **Try logging in** with existing test users
3. **Create new items** and test functionality
4. **Monitor Render logs** for any issues
5. **Set up custom domain** (optional)

---

**Status**: ✅ Configuration Complete
**Database**: ✅ Railway MySQL (30 tables, 8 users)
**Frontend**: 🔄 Ready to deploy on Render
**Connection**: ✅ Tested and working locally

Need help? Check Render logs or Railway dashboard for connection status.
