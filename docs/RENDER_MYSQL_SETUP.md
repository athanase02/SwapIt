# Render MySQL Database Setup

This guide explains how to connect your SwapIt app to a MySQL database on Render.

## Option 1: External MySQL Database (Recommended)

Since Render doesn't offer managed MySQL databases on the free tier, you can use external MySQL providers:

### Free MySQL Database Providers:
1. **Railway** (500 hours free/month): https://railway.app
2. **PlanetScale** (Free tier available): https://planetscale.com
3. **Aiven** (Free tier available): https://aiven.io
4. **FreeSQLDatabase** (Free tier): https://www.freesqldatabase.com

### Setup Steps:

1. **Create a MySQL database** on one of the providers above
2. **Get your database credentials**:
   - Host (e.g., `containers-us-west-123.railway.app`)
   - Port (usually `3306`)
   - Database Name
   - Username
   - Password

3. **Add environment variables in Render Dashboard**:
   - Go to your Render web service
   - Navigate to **Environment** tab
   - Add these variables:
     ```
     DB_HOST=your-database-host
     DB_PORT=3306
     DB_NAME=your-database-name
     DB_USER=your-username
     DB_PASSWORD=your-password
     ```

4. **Deploy**: Render will automatically redeploy with the new configuration

## Option 2: Use Render's PostgreSQL (Already Set Up)

You already have a PostgreSQL database on Render. If you want to use it instead:

1. Revert the database configuration to use `DATABASE_URL`
2. The PostgreSQL database is already provisioned and working
3. Just ensure the Docker image has `pdo_pgsql` extension (already included)

## Verification

After deployment, check the logs for:
```
SwapIt: Connected to MySQL on Render (your-host:3306/your-db)
SwapIt: Render MySQL tables initialized
```

## Current Configuration

The app is now configured to:
- ✅ Use **MySQL locally** (localhost, root, no password)
- ✅ Use **MySQL on Render** (via `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD` env vars)
- ✅ Auto-create `users` and `profiles` tables on first connection
- ✅ Use PDO for database abstraction (works with both MySQL and PostgreSQL)

## Troubleshooting

If you see "could not find driver" errors:
- Check that `pdo_mysql` extension is installed in Dockerfile
- Verify environment variables are set correctly in Render
- Check logs for specific connection errors

## Testing Locally

To test MySQL connection locally:
```powershell
php -S localhost:3000 -t public
```

The app will automatically use your local MySQL (root@localhost).
