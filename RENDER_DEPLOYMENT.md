# SwapIt - Render Deployment Guide

## Prerequisites
- A [Render](https://render.com) account
- Your SwapIt repository on GitHub
- Google OAuth credentials (Client ID and Secret)

## Deployment Steps

### 1. Create a PostgreSQL Database (or use MySQL add-on)
1. Go to Render Dashboard
2. Click "New +" → "PostgreSQL"
3. Name it: `swapit-db`
4. Choose the free plan
5. Click "Create Database"
6. Note down the connection details

### 2. Deploy the Web Service
1. Go to Render Dashboard
2. Click "New +" → "Web Service"
3. Connect your GitHub repository: `https://github.com/athanase02/SwapIt`
4. Configure the service:
   - **Name**: `swapit`
   - **Environment**: `Docker`
   - **Build Command**: `bash render-build.sh`
   - **Start Command**: `bash render-start.sh`
   
Alternatively, Render will auto-detect the `render.yaml` file and configure everything automatically.

### 3. Configure Environment Variables
Add the following environment variables in your Render service settings:

#### Database Configuration
```
DB_HOST=<your-database-host>
DB_USERNAME=<your-database-user>
DB_PASSWORD=<your-database-password>
DB_NAME=swapit_db
```

#### Google OAuth Configuration
```
GOOGLE_CLIENT_ID=<your-google-client-id>
GOOGLE_CLIENT_SECRET=<your-google-client-secret>
GOOGLE_REDIRECT_URI=https://your-app-name.onrender.com/api/google-callback.php
```

**Important**: Update `GOOGLE_REDIRECT_URI` with your actual Render URL after deployment.

### 4. Update Google OAuth Credentials
1. Go to [Google Cloud Console](https://console.cloud.google.com)
2. Select your project
3. Navigate to "APIs & Services" → "Credentials"
4. Edit your OAuth 2.0 Client ID
5. Add to "Authorized redirect URIs":
   ```
   https://your-app-name.onrender.com/api/google-callback.php
   ```
6. Add to "Authorized JavaScript origins":
   ```
   https://your-app-name.onrender.com
   ```

### 5. Import Database Schema
After deployment, import your database schema:

1. Connect to your database using the connection string from Render
2. Import the SQL file:
   ```bash
   mysql -h <host> -u <user> -p <database> < db/SI2025.sql
   ```

Or use a database management tool like phpMyAdmin or DBeaver.

### 6. Test Your Deployment
1. Visit your Render URL: `https://your-app-name.onrender.com`
2. Test user registration and login
3. Test Google OAuth login
4. Verify all features are working

## Troubleshooting

### Common Issues

**Database Connection Failed**
- Verify environment variables are set correctly
- Check database credentials
- Ensure database is running and accessible

**Google OAuth Not Working**
- Verify redirect URIs match exactly in Google Console
- Check that GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET are set
- Ensure GOOGLE_REDIRECT_URI uses HTTPS (not HTTP)

**404 Errors**
- Check that start command is correct
- Verify files are in the correct directory structure
- Check Render logs for errors

### View Logs
Go to your service in Render Dashboard → "Logs" tab to see application logs and errors.

## Production Checklist

- [ ] Database is created and schema is imported
- [ ] All environment variables are configured
- [ ] Google OAuth redirect URIs are updated
- [ ] Application is accessible via HTTPS
- [ ] User registration works
- [ ] User login works
- [ ] Google OAuth login works
- [ ] All API endpoints are functional
- [ ] Images and assets load correctly

## Security Notes

1. **Never commit** your `.env` file or real credentials to git
2. **Always use** environment variables for sensitive data
3. **Enable** HTTPS (Render does this automatically)
4. **Regenerate** Google OAuth credentials if they were ever exposed
5. **Use strong** database passwords
6. **Regularly update** dependencies and PHP version

## Support

For issues related to:
- **Render**: Check [Render Documentation](https://render.com/docs)
- **Google OAuth**: Check [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- **Application Issues**: Check application logs and error messages

## Useful Commands

### Test Database Connection
```bash
php api/test-db.php
```

### Test Google OAuth Configuration
```bash
php api/test-google-oauth.php
```

## Next Steps After Deployment

1. Set up custom domain (optional)
2. Configure email notifications (if needed)
3. Set up monitoring and alerts
4. Configure backup strategy
5. Test all functionality thoroughly
6. Update documentation with production URL

---

**Deployed Application URL**: `https://your-app-name.onrender.com`

Replace `your-app-name` with your actual Render service name.
