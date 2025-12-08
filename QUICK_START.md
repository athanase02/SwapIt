# 🚀 QUICK START - Get Your Real-Time System Running in 5 Minutes

## Step 1: Setup Database (1 minute)

Open your browser and go to:
```
http://localhost/public/setup-realtime.php
```

✅ You should see green checkmarks for all database tables.

---

## Step 2: Create Two Test Users (2 minutes)

### User 1:
1. Go to: `http://localhost/pages/signup.html`
2. Fill in:
   - Email: `alice@test.com`
   - Password: `Test123!`
   - Full Name: `Alice Smith`
3. Click Sign Up

### User 2:
1. **Open a different browser** (Chrome AND Firefox, or use Incognito)
2. Go to: `http://localhost/pages/signup.html`
3. Fill in:
   - Email: `bob@test.com`
   - Password: `Test123!`
   - Full Name: `Bob Johnson`
4. Click Sign Up

---

## Step 3: Add Items (2 minutes)

### As Alice (Browser 1):
1. Go to: `http://localhost/pages/add-listing.html`
2. Create item:
   - Title: "MacBook Pro"
   - Description: "Laptop for projects"
   - Category: "Electronics"
   - Price: "50"
   - Location: "Ashesi University"
3. Click Submit

### As Bob (Browser 2):
1. Go to: `http://localhost/pages/add-listing.html`
2. Create item:
   - Title: "DSLR Camera"
   - Description: "Canon camera"
   - Category: "Electronics"
   - Price: "30"
   - Location: "Accra"
3. Click Submit

---

## Step 4: Test Real-Time Features (5 minutes)

Keep both browsers open side-by-side!

### Test 1: Online Status (30 seconds)
- **Browser 1 (Alice)**: Look at the top navigation
- **Browser 2 (Bob)**: Look at the top navigation
- Both should see each other's names with **green dots** (online)

### Test 2: Browse & Request (1 minute)
**As Alice (Browser 1):**
1. Go to Browse: `http://localhost/pages/browse.html`
2. You should see Bob's camera
3. Click "View Details"
4. Fill in borrow form:
   - Start Date: Tomorrow
   - End Date: 3 days later
   - Message: "Hi! I need this for a project"
5. Click "Send Borrow Request"
6. You'll see "Request sent successfully!"

### Test 3: Receive Request (1 minute)
**As Bob (Browser 2):**
1. Look at the **bell icon** 🔔 - it should show "1"
2. Click the bell - you'll see: "New Borrow Request"
3. Go to Requests: `http://localhost/pages/requests.html`
4. You'll see Alice's request under "Received Requests"
5. Click "Accept Request"
6. Add note: "Sure! Let's meet at the library"

### Test 4: Real-Time Notification (30 seconds)
**As Alice (Browser 1):**
1. Look at the bell icon - it now shows "1"
2. Click it - you'll see: "Request Accepted!"
3. Go to Requests page - status is now "Accepted"
4. **No page refresh needed!**

### Test 5: Real-Time Messaging (2 minutes)
**As Alice (Browser 1):**
1. Go to Messages: `http://localhost/pages/messages.html`
2. Click on Bob's name
3. Type: "Hi Bob! Thanks for accepting!"
4. Press Enter

**As Bob (Browser 2):**
1. Go to Messages: `http://localhost/pages/messages.html`
2. Within 5 seconds, Alice's message appears!
3. Type back: "No problem! When do you need it?"
4. Press Enter

**As Alice:**
- Bob's reply appears within 5 seconds
- This is real-time messaging!

---

## 🎉 Success!

If you completed all steps above, your system is working perfectly!

You now have:
- ✅ Two real users
- ✅ Items in database
- ✅ Real-time online status
- ✅ Working borrow requests
- ✅ Live notifications
- ✅ Instant messaging

---

## 🧪 More Things to Try

### Schedule a Meeting:
1. In request details, click "Schedule Meeting"
2. Choose Online or Offline
3. Set date and time
4. Both users get notified!

### Browse with Filters:
1. Go to browse page
2. Filter by Category: "Electronics"
3. Filter by Location: "Ashesi"
4. Set price range
5. See results update instantly

### Check Who's Online:
1. Close Browser 2 (Bob)
2. Wait 60 seconds
3. In Browser 1, Bob's dot turns gray
4. Open Browser 2 again
5. Bob's dot turns green again

---

## 🐛 Troubleshooting

### Problem: "Authentication required" error
**Solution**: Log in again
```
http://localhost/pages/login.html
```

### Problem: Items not showing
**Solution**: Make sure you added items after migration
```
Go to Add Listing page and create items
```

### Problem: Real-time not working
**Solution**: Open browser console (F12)
```
Look for "Starting real-time updates..."
If not there, refresh the page
```

### Problem: Database errors
**Solution**: Re-run migration
```
http://localhost/public/setup-realtime.php
```

---

## 📖 Full Testing Guide

For comprehensive testing of ALL features, see:
```
REALTIME_TESTING_GUIDE.md
```

---

## 🎊 Enjoy!

Your real-time borrowing platform is now live and ready to use!

**Pro Tip**: Test with 3 or more users by opening multiple browsers/incognito windows!

---

**Need help?** Check the browser console (F12) for any errors.
