# Quick Start: Railway MySQL Setup for SwapIt

## Step 1: Create Railway Account
1. Go to https://railway.app
2. Click "Start a New Project"
3. Sign up with GitHub (recommended for easy deployment)

## Step 2: Create MySQL Database
1. Click "New Project"
2. Select "Provision MySQL"
3. Railway will create a MySQL database instantly

## Step 3: Get Database Credentials
1. Click on your MySQL service
2. Go to "Connect" or "Variables" tab
3. Copy these credentials:
   - **MYSQLHOST** (e.g., `containers-us-west-123.railway.app`)
   - **MYSQLPORT** (usually `3306`)
   - **MYSQLDATABASE** (e.g., `railway`)
   - **MYSQLUSER** (e.g., `root`)
   - **MYSQLPASSWORD** (long random string)

## Step 4: Add to Render Environment Variables

1. Go to https://dashboard.render.com
2. Select your **swapit** web service
3. Click **Environment** tab in the left sidebar
4. Click **Add Environment Variable** button
5. Add these 5 variables (copy values from Railway):

   ```
   DB_HOST = [paste MYSQLHOST from Railway]
   DB_PORT = 3306
   DB_NAME = [paste MYSQLDATABASE from Railway]
   DB_USER = [paste MYSQLUSER from Railway]
   DB_PASSWORD = [paste MYSQLPASSWORD from Railway]
   ```

6. Click **Save Changes**
7. Render will automatically redeploy your app (takes ~2-3 minutes)

## Step 5: Verify Deployment

1. Wait for Render deployment to complete
2. Check Render logs for:
   ```
   SwapIt: Connected to MySQL on Render
   SwapIt: Render MySQL tables initialized
   ```
3. Visit your app URL and test signup/login

## Alternative: Use Aiven Instead

If you prefer Aiven (also has generous free tier):

1. Go to https://aiven.io
2. Sign up and create a new service
3. Select **MySQL** 
4. Choose **Free** plan
5. Wait 5-10 minutes for provisioning
6. Get credentials from the "Connection information" tab
7. Add to Render (same as above)

## Troubleshooting

**If deployment fails:**
- Check all 5 environment variables are set correctly
- Verify no extra spaces in variable values
- Check Render logs for specific error messages

**If you see connection errors:**
- Ensure Railway database is running (check Railway dashboard)
- Verify the host/port are correct
- Test connection from Railway's MySQL client first

---

**Current Status:** Ready to set up Railway MySQL
**Next Action:** Create Railway account and follow steps above
