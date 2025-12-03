# Security Features Testing Guide

## How to Test the New Security Features

### 1. Rate Limiting & Account Lockout

**Test Steps:**
1. Go to the login page
2. Enter a valid email but wrong password
3. Try to login 5 times with wrong password
4. On the 6th attempt, you should see: "Account locked due to too many failed attempts. Please try again in 15 minute(s)."
5. Wait 15 minutes or manually delete `logs/rate_limit.json` to reset
6. Try logging in with correct credentials - should work

**Expected Behavior:**
- First 5 attempts: "Invalid email or password (X attempt(s) remaining before account lock)"
- 6th attempt: Account locked for 15 minutes
- After successful login: Counter resets

---

### 2. Enhanced Password Validation

**Test Steps:**
1. Go to signup page
2. Try password: "pass" - Should fail (too short)
3. Try password: "password" - Should fail (no numbers)
4. Try password: "123456" - Should fail (no letters)
5. Try password: "pass123" - Should succeed (6+ chars, has letters and numbers)

**Expected Messages:**
- ❌ "pass" → "Password must be at least 6 characters"
- ❌ "password" → "Password must contain both letters and numbers"
- ❌ "123456" → "Password must contain both letters and numbers"
- ✅ "pass123" → Registration successful

---

### 3. Image Upload Validation

**Test Steps:**
1. Login to your account
2. Go to Profile page
3. Try uploading a non-image file (rename .txt to .jpg)
4. Should see: "Invalid image type. Allowed: JPEG, PNG, GIF, WebP"
5. Try uploading a valid image over 5MB
6. Should see: "Image too large (max 5MB)"
7. Upload a valid JPEG/PNG under 5MB - Should succeed

**Expected Behavior:**
- Only allows: JPEG, PNG, GIF, WebP
- Maximum file size: 5MB
- Validates actual file content, not just extension
- Logs invalid upload attempts

---

### 4. Security Logging

**Check Logs:**
1. Navigate to `SwapIt/logs/` directory
2. Open `security.log` file
3. You should see entries like:

```
[2025-11-29 14:30:45] EVENT: LOGIN_FAILED | USER: guest | IP: 127.0.0.1 | MESSAGE: Invalid credentials | CONTEXT: {"email":"test@example.com","remaining_attempts":4}

[2025-11-29 14:31:20] EVENT: ACCOUNT_LOCKED | USER: guest | IP: 127.0.0.1 | MESSAGE: Account temporarily locked: test@example.com127.0.0.1 | CONTEXT: {"attempts":5}

[2025-11-29 14:35:00] EVENT: LOGIN_SUCCESS | USER: guest | IP: 127.0.0.1 | MESSAGE: User logged in successfully | CONTEXT: {"user_id":"1","email":"test@ashesi.edu.gh"}
```

**Events Logged:**
- ✅ login_success
- ✅ login_failed
- ✅ account_locked
- ✅ rate_limit_exceeded
- ✅ signup_success
- ✅ signup_failed
- ✅ profile_updated
- ✅ invalid_upload
- ✅ suspicious_activity
- ✅ logout

---

### 5. Session Hijacking Detection

**Test Steps:**
1. Login to your account
2. Check `logs/security.log` - note your IP address
3. *In theory*, if your session was accessed from a different IP, it would log:
   ```
   EVENT: SUSPICIOUS_ACTIVITY | MESSAGE: Possible session hijacking detected
   ```

**Note:** This is logged but doesn't terminate the session automatically (to avoid false positives from legitimate IP changes like mobile switching between WiFi and cellular)

---

### 6. Security Headers

**Test Steps:**
1. Start the PHP server: `php -S localhost:8080 -t public`
2. Open browser Developer Tools (F12)
3. Go to Network tab
4. Visit any page (e.g., login.html)
5. Click on the auth.php request
6. Check Response Headers - you should see:

```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; ...
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
```

**What These Do:**
- **CSP**: Prevents loading malicious scripts from external sources
- **X-Content-Type-Options**: Prevents MIME type sniffing attacks
- **X-Frame-Options**: Prevents clickjacking by blocking iframe embedding
- **X-XSS-Protection**: Browser's built-in XSS filter

---

### 7. XSS Prevention

**Test Steps:**
1. Go to Add Listing page
2. In the title field, try: `<script>alert('XSS')</script>`
3. In the description, try: `<img src=x onerror=alert('XSS')>`
4. Save the listing
5. Go to Browse page
6. The malicious code should be displayed as plain text, not executed

**Expected Behavior:**
- Scripts are escaped: `&lt;script&gt;alert('XSS')&lt;/script&gt;`
- No JavaScript execution
- Safe display of user content

---

### 8. SQL Injection Prevention

**Test Steps:**
1. Go to login page
2. Try email: `admin' OR '1'='1`
3. Try password: `' OR '1'='1`
4. Should fail with "Invalid email or password"

**Why It's Safe:**
- All queries use prepared statements
- Input is parameterized, not concatenated
- SQL special characters are escaped automatically

---

## Quick Verification Checklist

After running the PHP server, verify:

- [ ] Can create account with strong password
- [ ] Cannot create account with weak password
- [ ] Account locks after 5 failed login attempts
- [ ] Security logs are being written to `logs/security.log`
- [ ] Rate limit data is stored in `logs/rate_limit.json`
- [ ] Can only upload valid image files (JPEG, PNG, GIF, WebP)
- [ ] Cannot upload files over 5MB
- [ ] XSS attempts are escaped and displayed as text
- [ ] SQL injection attempts are blocked
- [ ] Security headers are present in HTTP responses
- [ ] Session persists across page navigation
- [ ] Can logout successfully

---

## Troubleshooting

### Logs not being created?
**Solution:** Ensure `logs/` directory has write permissions:
```bash
chmod 755 logs/
```

### Rate limiting not working?
**Solution:** Check if `logs/rate_limit.json` exists and is writable

### Cannot upload any images?
**Solution:** 
1. Check PHP `upload_max_filesize` and `post_max_size` settings
2. Ensure GD library is installed: `php -m | grep gd`

### Getting CORS errors?
**Solution:** Make sure you're accessing via `http://localhost:8080`, not `file://`

---

## Security Best Practices Demonstrated

✅ **Defense in Depth** - Multiple layers of security (client + server validation)
✅ **Least Privilege** - Users can only access their own data
✅ **Fail Securely** - Errors don't expose sensitive information
✅ **Secure by Default** - Security features enabled automatically
✅ **Complete Mediation** - Every request is validated
✅ **Psychological Acceptability** - Security doesn't hinder usability

---

## Production Deployment Notes

Before going to production:

1. **Enable HTTPS:**
   ```php
   ini_set('session.cookie_secure', 1);
   ```

2. **Set stronger rate limits:**
   ```php
   RateLimiter::check($identifier, 3, 1800); // 3 attempts per 30 mins
   ```

3. **Configure log rotation:**
   - Prevent log files from growing too large
   - Archive old logs securely

4. **Set up monitoring:**
   - Alert on multiple account lockouts
   - Monitor for suspicious activity patterns

5. **Regular security audits:**
   - Review logs weekly
   - Update dependencies monthly
   - Penetration test quarterly

---

*Happy Testing! Your website is now significantly more secure! 🔒*
