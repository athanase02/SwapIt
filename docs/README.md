# SwapIt - Setup and Installation Guide

**SwapIt** is a peer-to-peer item sharing platform for the Ashesi University community.

---

## Quick Start

### Requirements
- PHP 8.0 or higher
- Modern web browser (Chrome, Firefox, Edge, Safari)
- Git (optional, for cloning)

Check PHP version:
```bash
php -v
```

---

### Installation

**Option A: Download ZIP**
1. Download project ZIP from GitHub
2. Extract to your desired location
3. Navigate to SwapIt folder

**Option B: Git Clone**
```bash
git clone https://github.com/athanase02/group5-swapit.git
cd group5-swapit
git checkout feature-group5-swapit
cd SwapIt
```

---

### Running the Server

**Windows PowerShell:**
```powershell
cd path\to\SwapIt
php -S localhost:3000 -t public
```

**Mac/Linux Terminal:**
```bash
cd path/to/SwapIt
php -S localhost:3000 -t public
```

You should see:
```
PHP Development Server (http://localhost:3000) started
```

Open your browser and navigate to: http://localhost:3000/home.html

---

## Using the Application

### Test Account (Mock Database)
If you don't have MySQL installed, use this test account:
- Email: test@ashesi.edu.gh
- Password: password123

### Main Pages

| Page | URL | Access |
|------|-----|--------|
| Home | /home.html | Public |
| Login | /pages/login.html | Public |
| Signup | /pages/signup.html | Public |
| Dashboard | /pages/dashboard.html | Requires login |
| Browse Items | /pages/browse.html | Requires login |
| Add Listing | /pages/add-listing.html | Requires login |
| Profile | /pages/profile.html | Requires login |
| Cart | /pages/cart.html | Requires login |

---

## Database Setup (Optional)

**Without MySQL:** Application uses mock database automatically (no setup needed)

**With MySQL:**

Create database:
```sql
CREATE DATABASE SI2025;
USE SI2025;
SOURCE path/to/SwapIt/db/schema.sql;
```

Configure connection in `config/db_with_fallback.php`:
```php
$host = "localhost";
$username = "root";
$password = "";
$database = "SI2025";
```

---

## Key Features

- User authentication and sessions
- Browse and filter items by category, location, and price
- Shopping cart and wishlist functionality
- Create and manage listings
- User dashboard with statistics
- Profile management with avatar upload
- Bilingual support (English/French)
- Security features (rate limiting, encryption, logging)

---

## Alternative Server Setup

### Using XAMPP/WAMP

1. Copy SwapIt folder to:
   - XAMPP: C:\xampp\htdocs\
   - WAMP: C:\wamp64\www\

2. Start Apache from control panel

3. Visit: http://localhost/SwapIt/public/home.html

---

## Troubleshooting

### Port Already in Use
```bash
# Use different port
php -S localhost:8000 -t public
```

### Session Not Working
- Access via http://localhost:3000, NOT file://
- Enable cookies in browser
- Clear browser cache

### Cannot Write to Logs
```bash
# Windows
icacls logs /grant Everyone:(OI)(CI)F

# Mac/Linux
chmod 755 logs/
```

### MySQL Connection Failed
- App automatically uses mock database as fallback
- To use MySQL: Start MySQL service and check credentials

---

## Project Structure

```
SwapIt/
├── api/              Backend APIs (auth, data)
├── config/           Database configuration
├── db/               SQL schemas
├── docs/             Documentation
├── logs/             Security and error logs
└── public/           Frontend files
    ├── home.html     Landing page
    ├── assets/       CSS, JS, images
    └── pages/        Application pages
```

---

## Security Features

- Password hashing (Bcrypt)
- Rate limiting (5 attempts per 15 minutes)
- SQL injection prevention
- XSS protection
- Session security (HttpOnly, SameSite cookies)
- Image validation (type and size checks)
- Security logging

See `docs/SECURITY_IMPLEMENTATION.md` for details

---

## Team

- Athanase Abayo - Team Lead, Backend Architecture
- Mabinty Mambu - Backend Developer
- Olivier Kwizera - Security Engineer
- Victoria Ama Nyonato - Frontend Developer

---

## Support

**Repository:** https://github.com/athanase02/group5-swapit  
**Branch:** feature-group5-swapit

**Contact:** Team Lead - Athanase Abayo

---

## Quick Commands

```bash
# Start server
php -S localhost:3000 -t public

# Check PHP version
php -v

# View security logs (Mac/Linux)
cat logs/security.log

# View security logs (Windows)
type logs\security.log

# Clear rate limiting (Mac/Linux)
rm logs/rate_limit.json

# Clear rate limiting (Windows)
del logs\rate_limit.json
```

---

**Last Updated:** November 30, 2025  
**Version:** 2.0  
**Status:** Production Ready

*Created for Ashesi University Web Technologies Course - Final Deliverables*

---

## Configuration

### Security Configuration

#### Session Settings (api/auth.php):

```php
// Production settings
ini_set('session.cookie_secure', 1);      // Requires HTTPS
ini_set('session.cookie_httponly', 1);    // Prevent XSS
ini_set('session.cookie_samesite', 'Strict'); // CSRF protection
```

#### Rate Limiting Settings:

Default: 5 login attempts per 15 minutes

To adjust, edit `api/auth.php`:
```php
// More strict: 3 attempts per 30 minutes
$rateLimitCheck = RateLimiter::check($email . $ip, 3, 1800);
```

### File Upload Limits

Maximum image size: 5MB

To change, edit `api/auth.php`:
```php
if ($size > 5 * 1024 * 1024) { // Change 5 to desired MB
```

Also update PHP settings in `php.ini`:
```ini
upload_max_filesize = 10M
post_max_size = 10M
```

---

## Testing the Application

### Basic Functionality Tests:

#### 1. User Registration
```
1. Go to: http://localhost:3000/pages/signup.html
2. Fill in form:
   - Email: yourname@ashesi.edu.gh
   - Password: test123 (min 6 chars, letters + numbers)
   - Full Name: Your Name
3. Click "Sign Up"
4. Expected: Redirect to login page
```

#### 2. User Login
```
1. Go to: http://localhost:3000/pages/login.html
2. Enter credentials from registration
3. Click "Login"
4. Expected: Redirect to dashboard
```

#### 3. Browse Items
```
1. Go to: http://localhost:3000/pages/browse.html
2. Try filters:
   - Category: Books
   - Location: Ashesi
   - Price: Min 0, Max 100
3. Expected: Filtered results display
```

#### 4. Add to Cart
```
1. On browse page, click "Add to Cart" on any item
2. Expected: Notification appears, cart badge updates
3. Go to: http://localhost:3000/pages/cart.html
4. Expected: Item appears in cart
```

#### 5. Create Listing
```
1. Go to: http://localhost:3000/pages/add-listing.html
2. Fill in all fields
3. Upload an image (JPEG/PNG, < 5MB)
4. Click "Submit"
5. Expected: Success message, listing saved
```

### Security Features Testing:

#### Rate Limiting Test:
```
1. Go to login page
2. Enter wrong password 6 times
3. Expected: "Account locked for 15 minutes" message
4. Reset: Delete SwapIt/logs/rate_limit.json
```

#### Password Validation Test:
```
Test these passwords on signup:
- "pass" → Error: "Too short"
- "password" → Error: "Must contain numbers"
- "123456" → Error: "Must contain letters"
- "pass123" → Success
```

#### Image Upload Test:
```
1. Go to profile page
2. Try uploading:
   - Text file → Error: "Invalid image type"
   - 10MB image → Error: "Image too large"
   - Valid JPEG → Success
```

### Checking Security Logs:

```bash
# View security log
cat SwapIt/logs/security.log

# Or on Windows
type SwapIt\logs\security.log
```

Expected entries:
```
[2025-11-29 14:30:45] EVENT: LOGIN_SUCCESS | USER: 1 | IP: 127.0.0.1 | MESSAGE: User logged in successfully
[2025-11-29 14:31:20] EVENT: LOGIN_FAILED | USER: guest | IP: 127.0.0.1 | MESSAGE: Invalid credentials
```

---

## Troubleshooting

### Common Issues and Solutions:

#### 1. "Address already in use" Error

**Problem:** Port 3000 is already in use

**Solution:**
```bash
# Try a different port
php -S localhost:8000 -t public

# Or on Windows, find and kill the process
Get-Process -Name php | Stop-Process
```

#### 2. "Session not persisting" / "Always logged out"

**Problem:** Session cookies not being set

**Solution:**
- Ensure you're accessing via `http://localhost:3000`, not `file://`
- Check browser allows cookies
- Clear browser cache and cookies
- Verify `session_start()` is called in `api/auth.php`

#### 3. "Cannot write to logs directory"

**Problem:** Insufficient permissions

**Solution:**
```bash
# Windows
icacls logs /grant Everyone:(OI)(CI)F

# Linux/Mac
chmod 755 logs/
```

#### 4. "MySQL connection failed"

**Problem:** MySQL not running or wrong credentials

**Solution:**
- The app will automatically use mock database fallback
- To use MySQL: Start MySQL service and update credentials in `config/db_with_fallback.php`
- Verify MySQL is running: `mysql -u root -p`

#### 5. "Image upload fails"

**Problem:** PHP GD extension not installed

**Solution:**
```bash
# Check if GD is installed
php -m | grep gd

# On Windows with XAMPP: Edit php.ini and uncomment:
extension=gd

# Restart PHP server
```

#### 6. "Rate limit not working"

**Problem:** Logs directory not writable

**Solution:**
- Check permissions on `logs/` directory
- Ensure `rate_limit.json` can be created
- Check error log for details

#### 7. "Security headers not showing"

**Problem:** Headers already sent

**Solution:**
- Ensure no output before `header()` calls in `api/auth.php`
- Check for BOM characters in PHP files
- Remove any whitespace before `<?php`

---

## Project Structure

### Directory Organization:

```
SwapIt/
│
├── api/                          # Backend API endpoints
│   ├── auth.php                 # Authentication API (login, signup, logout)
│   └── php-info.php             # PHP configuration info
│
├── config/                       # Configuration files
│   ├── db.php                   # Direct MySQL connection
│   └── db_with_fallback.php     # MySQL with mock fallback
│
├── db/                          # Database schemas and migrations
│   ├── schema.sql               # Main database schema
│   ├── cart.sql                 # Cart table schema
│   └── migrate_avatar.php       # Avatar column migration
│
├── docs/                        # Documentation
│   ├── Final_D1_Individual_Reports.md
│   ├── SECURITY_IMPLEMENTATION.md
│   ├── SECURITY_TESTING_GUIDE.md
│   ├── Architecture.md
│   └── ERD_Documentation.md
│
├── logs/                        # Security and application logs
│   ├── security.log             # Security events log
│   ├── rate_limit.json          # Rate limiting data
│   ├── .gitignore              # Prevent committing logs
│   └── README.md               # Logging documentation
│
└── public/                      # Public web files
    ├── home.html               # Landing page
    ├── router.php              # Request router
    │
    ├── assets/                 # Static assets
    │   ├── css/               # Stylesheets
    │   │   ├── styles.css
    │   │   ├── auth.css
    │   │   └── nav-auth.css
    │   │
    │   ├── js/                # JavaScript files
    │   │   ├── auth-client.js       # Authentication client
    │   │   ├── cart.js              # Cart management
    │   │   ├── dashboard.js         # Dashboard functionality
    │   │   ├── browse.js            # Browse and filter
    │   │   ├── profile.js           # Profile management
    │   │   ├── login.js             # Login handler
    │   │   └── signup.js            # Signup handler
    │   │
    │   └── images/            # Image assets
    │
    └── pages/                 # Application pages
        ├── login.html         # Login page
        ├── signup.html        # Registration page
        ├── dashboard.html     # User dashboard
        ├── browse.html        # Browse items
        ├── profile.html       # User profile
        ├── cart.html          # Shopping cart
        ├── wishlist.html      # User wishlist
        └── add-listing.html   # Create listing
```

---

## Security Features

### Implemented Security Controls (OWASP Top 10):

| # | Vulnerability | Implementation | Status |
|---|--------------|----------------|--------|
| 1 | **Broken Access Control** | Session-based auth, IP tracking | ✅ |
| 2 | **Cryptographic Failures** | Bcrypt hashing, secure cookies | ✅ |
| 3 | **Injection** | Prepared statements, input sanitization | ✅ |
| 4 | **Insecure Design** | Rate limiting, account lockout | ✅ |
| 5 | **Security Misconfiguration** | Security headers, proper config | ✅ |
| 6 | **Vulnerable Components** | Manual dependency management | ⚠️ |
| 7 | **Authentication Failures** | Enhanced validation, rate limiting | ✅ |
| 8 | **Data Integrity** | Image validation, integrity checks | ✅ |
| 9 | **Logging & Monitoring** | Comprehensive security logging | ✅ |
| 10 | **SSRF** | Not applicable (no URL fetching) | N/A |

**Security Score: 9/10 Implemented**

### Security Features Details:

- ✅ **Rate Limiting:** 5 attempts per 15 minutes
- ✅ **Account Lockout:** 15-minute automatic lockout
- ✅ **Password Requirements:** Min 6 chars, letters + numbers
- ✅ **Session Security:** HttpOnly, SameSite=Strict cookies
- ✅ **Input Sanitization:** XSS prevention on all inputs
- ✅ **SQL Injection Prevention:** Prepared statements everywhere
- ✅ **Image Validation:** Type, size, integrity checks
- ✅ **Security Logging:** All events logged with context
- ✅ **CSP Headers:** Content Security Policy enabled
- ✅ **Session Hijacking Detection:** IP address tracking

For detailed security documentation, see:
- `docs/SECURITY_IMPLEMENTATION.md`
- `docs/SECURITY_TESTING_GUIDE.md`

---

## Team Information

### Team Members and Roles:

| Name | Role | Primary Contributions |
|------|------|----------------------|
| **Athanase Abayo** | Team Lead & Backend Architect | Authentication system, database architecture, session management, routing |
| **Mabinty Mambu** | Backend Developer | User registration, profile management, wishlist, cart features |
| **Olivier Kwizera** | Security Engineer & Frontend Dev | Security implementation, rate limiting, logging, profile page, image validation |
| **Victoria Ama Nyonato** | Frontend Developer & UI/UX | Add listing page, login/signup UI, notifications, avatar management |

### Contact Information:

**Repository:** https://github.com/athanase02/ashesi-webtech-2025-peercoding-athanase-abayo

**Branch:** activity-4

**Primary Contact:** Athanase Abayo

### VM Server Information:

**VM Contact Person:** Athanase Abayo  
**Server Status Check:** Contact team lead if VM is not running  
**Faculty Evaluation:** VM will be started upon request for evaluation

---

## Support and Contact

### Getting Help:

1. **Check Documentation:**
   - `docs/SECURITY_IMPLEMENTATION.md` - Security details
   - `docs/SECURITY_TESTING_GUIDE.md` - Testing instructions
   - `logs/README.md` - Logging information

2. **Review Logs:**
   ```bash
   # Check security logs
   cat logs/security.log
   
   # Check PHP errors
   tail -f /path/to/php_errors.log
   ```

3. **GitHub Issues:**
   - Report bugs on GitHub repository
   - Check existing issues first

4. **Contact Team:**
   - Team Lead: Athanase Abayo
   - Check repository README for latest contact info

### Reporting Security Issues:

If you discover a security vulnerability:

1. **DO NOT** create a public GitHub issue
2. Contact the team lead directly
3. Provide details: steps to reproduce, impact, suggested fix
4. Allow reasonable time for fix before public disclosure

---

## Additional Resources

### Useful Commands:

```bash
# Start server
php -S localhost:3000 -t public

# Check PHP version
php -v

# Check PHP extensions
php -m

# View security logs
cat logs/security.log

# Clear rate limiting
rm logs/rate_limit.json

# Check for syntax errors
php -l api/auth.php

# Run in background (Linux/Mac)
nohup php -S localhost:3000 -t public > server.log 2>&1 &
```

### File Locations:

- **Configuration:** `config/db_with_fallback.php`
- **Main API:** `api/auth.php`
- **Security Logs:** `logs/security.log`
- **Rate Limiting:** `logs/rate_limit.json`
- **Session Data:** Managed by PHP in temp directory

---

## Quick Start Summary

For the impatient:

```bash
# 1. Clone repository
git clone https://github.com/athanase02/group5-swapit.git
cd group5-swapit
git checkout feature-group5-swapit
cd SwapIt

# 2. Start server
php -S localhost:3000 -t public

# 3. Open browser
# Navigate to: http://localhost:3000/home.html

# 4. Test with default account (if using mock database)
# Email: test@ashesi.edu.gh
# Password: password123
```

---

## License

This project is created for educational purposes as part of Ashesi University's Web Technologies course.

---

## Acknowledgments

- **Ashesi University** - For providing the learning environment
- **Faculty Team** - For guidance and support
- **OWASP** - For security guidelines and best practices
- **PHP Community** - For excellent documentation

---

**Last Updated:** November 29, 2025  
**Version:** 2.0  
**Status:** Production Ready

*For detailed technical documentation, please refer to the `docs/` directory.*
