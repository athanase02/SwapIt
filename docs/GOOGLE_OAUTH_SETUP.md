# Google OAuth Integration Guide

## Overview
SwapIt now supports Google Sign-In for seamless authentication. Users can sign up and log in using their Google accounts.

## Setup Instructions

### 1. Create Google OAuth Credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select an existing one
3. Navigate to **APIs & Services** → **Credentials**
4. Click **Create Credentials** → **OAuth client ID**
5. Configure the OAuth consent screen if prompted
6. Select **Web application** as the application type
7. Add authorized redirect URIs:
   - For local development: `http://localhost:3000/api/google-callback.php`
   - For production: `https://yourdomain.com/api/google-callback.php`
8. Copy the **Client ID** and **Client Secret**

### 2. Configure Server

1. Open `api/google-oauth.php`
2. Replace the placeholder values:
   ```php
   define('GOOGLE_CLIENT_ID', 'YOUR_ACTUAL_CLIENT_ID.apps.googleusercontent.com');
   define('GOOGLE_CLIENT_SECRET', 'YOUR_ACTUAL_CLIENT_SECRET');
   define('GOOGLE_REDIRECT_URI', 'http://localhost:3000/api/google-callback.php');
   ```

### 3. Update Database

Run the migration to add Google OAuth support:

```bash
mysql -u root -p SI2025 < db/add_google_oauth.sql
```

Or manually add the column:

```sql
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER email;
CREATE INDEX idx_google_id ON users(google_id);
```

## How It Works

### User Flow

1. **Login/Signup Page**
   - User clicks "Continue with Google" button
   - Redirected to Google's authentication page
   - User grants permission to SwapIt
   - Google redirects back to SwapIt with authorization code

2. **Backend Processing**
   - SwapIt exchanges authorization code for access token
   - Retrieves user information from Google (email, name, profile picture)
   - Checks if user exists in database:
     - **Existing user**: Logs them in and links Google account
     - **New user**: Creates account automatically and logs them in
   - Redirects to dashboard

### Security Features

- ✅ OAuth 2.0 protocol (industry standard)
- ✅ Secure token exchange
- ✅ HTTPS enforcement (production)
- ✅ CSRF protection via state parameter
- ✅ Automatic session management
- ✅ No password storage needed for Google users

## Files Modified/Created

### Frontend
- `public/pages/login.html` - Added Google Sign-In button
- `public/pages/signup.html` - Added Google Sign-Up button
- `public/assets/js/google-auth.js` - OAuth client-side handler
- `public/assets/js/login.js` - Integrated Google auth
- `public/assets/js/signup.js` - Integrated Google auth

### Backend
- `api/google-oauth.php` - OAuth server-side logic
- `api/google-callback.php` - OAuth callback handler
- `api/auth.php` - Added Google config and login endpoints
- `db/add_google_oauth.sql` - Database migration

## Testing

### Local Testing

1. Ensure your server is running on `localhost:3000`
2. Configure Google OAuth redirect URI to match
3. Click "Continue with Google" on login/signup page
4. Complete Google authentication
5. Verify redirection to dashboard
6. Check that user profile is populated with Google data

### Troubleshooting

**"Google Sign-In is not configured yet"**
- Solution: Update `GOOGLE_CLIENT_ID` in `api/google-oauth.php`

**"Redirect URI mismatch"**
- Solution: Ensure redirect URI in Google Console matches `GOOGLE_REDIRECT_URI` in config

**"Failed to exchange authorization code"**
- Solution: Check `GOOGLE_CLIENT_SECRET` is correct
- Check server error logs for details

**"Failed to get user information from Google"**
- Solution: Ensure OAuth consent screen is properly configured
- Check that email scope is included

## Production Deployment

1. Update Google OAuth credentials for production domain
2. Change redirect URI to production URL:
   ```php
   define('GOOGLE_REDIRECT_URI', 'https://yourdomain.com/api/google-callback.php');
   ```
3. Enable HTTPS (required by Google for OAuth)
4. Update session cookie settings:
   ```php
   ini_set('session.cookie_secure', 1); // Require HTTPS
   ```

## Instagram Removed

Instagram social login has been removed from the platform. Only Google OAuth is supported for social authentication.

## Future Enhancements

- [ ] Add Microsoft OAuth support
- [ ] Add Facebook Login support
- [ ] Add Apple Sign In support
- [ ] Link multiple OAuth providers to one account
- [ ] OAuth account unlinking feature

## Support

For issues with Google OAuth integration, check:
- Google Cloud Console error logs
- SwapIt `logs/security.log` for authentication events
- Browser console for client-side errors
- Network tab to inspect OAuth redirects

---

**Author:** Athanase Abayo  
**Version:** 1.0  
**Last Updated:** November 30, 2025
