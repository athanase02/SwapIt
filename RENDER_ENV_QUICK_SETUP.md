# 🚀 Quick Setup: Render Environment Variables

## Copy these to Render Dashboard → Environment

### Railway Database Connection
```
RAILWAY_DB_HOST=shinkansen.proxy.rlwy.net
RAILWAY_DB_PORT=32604
RAILWAY_DB_NAME=railway
RAILWAY_DB_USER=root
RAILWAY_DB_PASSWORD=psMDOMvbOfBoWmHXkhNkhbRLpnPjpcVV
```

### Fallback DB Connection
```
DB_HOST=shinkansen.proxy.rlwy.net
DB_PORT=32604
DB_NAME=railway
DB_USER=root
DB_PASSWORD=psMDOMvbOfBoWmHXkhNkhbRLpnPjpcVV
```

### Google OAuth
```
GOOGLE_CLIENT_ID=your-client-id-from-google-console.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret-from-google-console
GOOGLE_REDIRECT_URI=https://swapit-tjoj.onrender.com/api/google-callback.php
```

**Get your Google OAuth credentials from:**
https://console.cloud.google.com → APIs & Services → Credentials

---

## Steps:
1. Go to https://dashboard.render.com
2. Select "swapit-tjoj" service
3. Click "Environment" tab
4. Add each variable above
5. Click "Save Changes"
6. Wait for redeploy (~2-3 minutes)

---

**IMPORTANT**: Delete this file after adding variables (contains sensitive data)

```bash
# After setup, remove this file:
rm RENDER_ENV_QUICK_SETUP.md
```
