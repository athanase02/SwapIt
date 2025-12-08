# Add login_attempts Table to Railway Database

## Quick Steps

### Option 1: Copy & Paste SQL (Fastest)

1. Open Railway dashboard: https://railway.app
2. Select your project → MySQL database
3. Click on "Query" tab
4. Copy the entire content from `db/railway_add_login_attempts.sql`
5. Paste and click "Run Query"
6. ✅ Done! Table created

### Option 2: Run Full Migration

If you haven't run the Railway migration yet:

1. Open Railway MySQL Query tab
2. Copy the entire content from `db/RAILWAY_MIGRATION.sql`
3. Paste and click "Run Query"
4. This includes `login_attempts` and all other tables

## SQL to Copy (Quick Version)

```sql
USE railway;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier_hash VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash of email + IP',
    email VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL COMMENT 'Supports IPv4 and IPv6',
    attempt_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    locked_until TIMESTAMP NULL DEFAULT NULL,
    success BOOLEAN DEFAULT FALSE,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_identifier_hash (identifier_hash),
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verify
DESCRIBE login_attempts;
```

## Verify Table Exists

After running the SQL, verify with:

```sql
SHOW TABLES LIKE 'login_attempts';
DESCRIBE login_attempts;
```

You should see:
- ✅ Table name: `login_attempts`
- ✅ 9 columns: id, identifier_hash, email, ip_address, attempt_time, locked_until, success, user_agent, created_at
- ✅ 5 indexes for performance

## What This Does

The `login_attempts` table tracks:
- Failed login attempts (for rate limiting)
- Successful logins (for audit trail)
- Account lock status (15 minutes after 5 failed attempts)
- IP addresses and user agents (for security)

Rate limiting: **5 attempts per 15 minutes** per email+IP combination.

## Files Reference

- `db/railway_add_login_attempts.sql` - Quick add table only
- `db/RAILWAY_MIGRATION.sql` - Full migration with all tables
- `db/create_login_attempts_table.sql` - Standard SQL (no Railway-specific)
