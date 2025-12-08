# Login Attempts Tracking - Database Migration

## Overview
Login fail attempts are now tracked in the **database** instead of flat files for better reliability, scalability, and data integrity.

## Changes Made

### 1. Database Table
Created `login_attempts` table with the following structure:
- `id` - Auto-increment primary key
- `identifier_hash` - SHA-256 hash of email + IP (for privacy)
- `email` - User email address
- `ip_address` - IP address of the attempt
- `attempt_time` - Timestamp of the attempt
- `locked_until` - Timestamp when account lock expires (NULL if not locked)
- `success` - Boolean flag (FALSE for failed, TRUE for successful login)
- `user_agent` - Browser/client user agent string
- Indexes on key fields for performance

### 2. Updated RateLimiter Class
Location: `api/auth.php` (lines 110+)

**Changes:**
- Removed file-based storage (`logs/rate_limit.json`)
- Now uses database queries for all operations
- `check()` - Queries database for recent attempts and lock status
- `recordAttempt()` - Inserts failed login attempts into database
- `reset()` - Records successful login and clears lock status

**Features:**
- Automatic cleanup of old attempts (outside 15-minute window)
- Locks account for 15 minutes after 5 failed attempts
- Tracks both failed AND successful login attempts
- Records user agent for security audit trail

### 3. Migration Scripts
- `db/create_login_attempts_table.sql` - SQL schema
- `public/apply-login-attempts-migration.php` - Web-based migration tool
- `public/test-login-attempts.php` - Comprehensive testing script

## Setup Instructions

### Step 1: Apply Migration
Visit: `http://localhost:8080/apply-login-attempts-migration.php`

This will:
- Create the `login_attempts` table
- Show table structure
- Verify creation

### Step 2: Run Tests
Visit: `http://localhost:8080/test-login-attempts.php`

This will test:
- Initial state (should allow login)
- Recording 5 failed attempts
- Account locking after 5 attempts
- Lock message and timing
- Reset on successful login
- Viewing all records in database

## Usage

The system works automatically with existing login code. No changes needed to login flow.

### Rate Limiting Rules
- **Max Attempts:** 5 per 15 minutes
- **Lock Duration:** 15 minutes
- **Identifier:** Hash of (email + IP address)

### Example Flow
1. User attempts login with wrong password → `recordAttempt()` called
2. After 5 failed attempts → Account locked for 15 minutes
3. User enters correct password → `reset()` called, attempts cleared
4. Successful login recorded in database

## Database Queries

### View All Failed Attempts
```sql
SELECT email, ip_address, attempt_time, locked_until
FROM login_attempts
WHERE success = FALSE
ORDER BY attempt_time DESC;
```

### View Locked Accounts
```sql
SELECT DISTINCT email, ip_address, locked_until
FROM login_attempts
WHERE locked_until > NOW()
ORDER BY locked_until DESC;
```

### View Login History for User
```sql
SELECT email, ip_address, attempt_time, success
FROM login_attempts
WHERE email = 'user@example.com'
ORDER BY attempt_time DESC
LIMIT 20;
```

### Clean Up Old Records (30+ days)
```sql
DELETE FROM login_attempts 
WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

## Benefits

### Before (File-based)
- ❌ Single point of failure (file corruption)
- ❌ Race conditions with concurrent requests
- ❌ No audit trail for successful logins
- ❌ Limited query capabilities
- ❌ Manual cleanup required

### After (Database)
- ✅ ACID compliance and data integrity
- ✅ Handles concurrent requests safely
- ✅ Full audit trail (failed + successful)
- ✅ Easy querying and reporting
- ✅ Automatic old data cleanup
- ✅ Indexed for performance
- ✅ Scales with application

## Security Features

1. **Privacy:** Email + IP hashed with SHA-256
2. **Audit Trail:** All attempts logged with timestamps
3. **Brute Force Protection:** 5 attempts per 15 minutes
4. **Account Locking:** Temporary 15-minute lock
5. **User Agent Tracking:** Detect bot attacks
6. **Indexed Queries:** Fast lookups even with millions of records

## Maintenance

### Regular Cleanup (Recommended)
Add to cron job or scheduled task:
```sql
DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Monitor Suspicious Activity
```sql
SELECT email, COUNT(*) as attempts, MAX(attempt_time) as last_attempt
FROM login_attempts
WHERE success = FALSE 
AND attempt_time > DATE_SUB(NOW(), INTERVAL 1 DAY)
GROUP BY email
HAVING attempts > 10
ORDER BY attempts DESC;
```

## Testing Checklist
- [x] Table created successfully
- [x] RateLimiter class updated
- [x] Failed attempts recorded
- [x] Account locking works
- [x] Lock duration correct
- [x] Reset clears attempts
- [x] Successful logins tracked
- [ ] Test with real login form
- [ ] Verify on production server

## Next Steps
1. ✅ Run migration: `apply-login-attempts-migration.php`
2. ✅ Run tests: `test-login-attempts.php`
3. Test with actual login form at `pages/login.html`
4. Monitor database for first week
5. Set up automated cleanup job
