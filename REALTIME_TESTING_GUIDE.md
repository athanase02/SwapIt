# SwapIt Real-Time System - Complete Setup & Testing Guide

## 🚀 Quick Start Guide

### Step 1: Run Database Migration

1. **Open your browser** and navigate to:
   ```
   http://localhost/public/setup-realtime.php
   ```
   
2. This will create all necessary database tables:
   - `user_online_status` - Tracks who's online
   - `conversations` - Stores message threads
   - `messages` - All user messages
   - `borrow_requests` - Borrow request details
   - `meeting_schedules` - Meeting arrangements
   - `transactions` - Completed borrows
   - `ratings` - User reviews
   - `notifications` - Real-time notifications
   - `user_activities` - Activity logging

3. **Verify Success**: You should see green checkmarks for all tables

### Step 2: Create Test Users

You need **at least 2 users** to test real-time features:

#### Option A: Use Existing Users
If you already have users in your database, you can log in with them.

#### Option B: Create New Users
1. Go to: `http://localhost/pages/signup.html`
2. Create User 1:
   - Email: `user1@test.com`
   - Password: `Test123!`
   - Full Name: `Alice Smith`
3. Create User 2:
   - Email: `user2@test.com`
   - Password: `Test123!`
   - Full Name: `Bob Johnson`

### Step 3: Add Some Items

1. **Log in as User 1** (`user1@test.com`)
2. Go to **Add Listing**: `http://localhost/pages/add-listing.html`
3. Create an item:
   - Title: "MacBook Pro 2020"
   - Description: "15-inch MacBook Pro for programming projects"
   - Category: "Electronics"
   - Price per day: "50"
   - Location: "Ashesi University"
   - Upload an image or use placeholder

4. **Log in as User 2** (`user2@test.com`)
5. Create another item:
   - Title: "DSLR Camera"
   - Description: "Canon DSLR for photography"
   - Category: "Electronics"
   - Price per day: "30"
   - Location: "Accra"

---

## ✅ Testing All Real-Time Features

### Test 1: Online Status Tracking

**What to test**: See who's online in real-time

**Steps**:
1. Open **two different browsers** (e.g., Chrome and Firefox)
2. In Browser 1: Log in as User 1
3. In Browser 2: Log in as User 2
4. Both users should see each other as online
5. Close Browser 2
6. Wait 60 seconds - User 2 should show as offline in Browser 1

**Expected Result**: 
- Green dot = User is online
- Gray dot = User is offline
- Status updates automatically every 30 seconds

---

### Test 2: Browse Items & View Details

**What to test**: Browse items and view detailed information

**Steps** (as User 1):
1. Go to: `http://localhost/pages/browse.html`
2. You should see items from the database (including items added by User 2)
3. Click "View Details" on any item
4. A modal should open showing:
   - Item image
   - Full description
   - Owner name and online status
   - Price calculation
   - Borrow request form

**Expected Result**: 
- All items load dynamically from database
- Modal opens smoothly
- Owner's online status is visible
- Can filter by category, location, price

---

### Test 3: Send Borrow Request

**What to test**: Request to borrow an item

**Steps** (as User 1 browsing User 2's item):
1. Browse items and click "View Details" on User 2's camera
2. Fill in the borrow request form:
   - Start Date: Tomorrow's date
   - End Date: 3 days from now
   - Message: "Hi! I need this for a photography project"
   - Pickup Location: "Main Campus"
3. See estimated cost calculate automatically
4. Click "Send Borrow Request"
5. Should see success message

**Expected Result**:
- Request is sent successfully
- User 2 receives notification immediately
- Request appears in User 1's "Sent Requests" tab
- Request appears in User 2's "Received Requests" tab

---

### Test 4: Receive & Accept/Reject Requests

**What to test**: Manage incoming borrow requests

**Steps** (as User 2):
1. Go to: `http://localhost/pages/requests.html`
2. You should see User 1's request in "Received Requests" tab
3. Click "View Details" to see full request information
4. Click "Accept Request"
5. Add optional notes: "Sure! Let's meet at the library"
6. Confirm acceptance

**Expected Result**:
- Request status changes to "Accepted"
- User 1 receives notification instantly
- Item status changes to "Reserved"
- Both users can now schedule a meeting

**Alternative: Reject Request**
- Click "Reject" instead
- Add reason: "Sorry, camera is not available those dates"
- User 1 gets notification
- Item remains available

---

### Test 5: Real-Time Messaging

**What to test**: Send and receive messages in real-time

**Steps**:
1. **Browser 1** (User 1): Go to `http://localhost/pages/messages.html`
2. **Browser 2** (User 2): Go to `http://localhost/pages/messages.html`
3. **User 1**: Click on User 2's conversation (or start new one)
4. **User 1**: Type message: "Hi! Is the camera still available?"
5. **User 1**: Press Enter or click Send
6. **User 2**: Should see message appear INSTANTLY in their messages
7. **User 2**: Reply: "Yes! When do you need it?"
8. **User 1**: Should see reply appear within 5 seconds

**Expected Result**:
- Messages appear in real-time (polling every 5 seconds)
- Unread message count updates automatically
- Message badge shows number of unread messages
- Conversations stay synchronized between users

---

### Test 6: Real-Time Notifications

**What to test**: Receive notifications for all activities

**Steps** (keep both browsers open):
1. **User 1**: Send a borrow request
2. **User 2**: Click the bell icon 🔔 in top nav
3. Should see notification: "New Borrow Request"
4. **User 2**: Accept the request
5. **User 1**: Click bell icon 🔔
6. Should see: "Request Accepted!"

**Expected Result**:
- Notification badge updates in real-time
- Clicking bell shows notification panel
- Notifications include:
  - New messages
  - Borrow requests
  - Request accepted/rejected
  - Meeting scheduled
  - Transaction updates

---

### Test 7: Schedule Meeting

**What to test**: Arrange meetup for item exchange

**Steps** (after request is accepted):
1. **User 2** (lender): Go to request details
2. Click "Schedule Meeting"
3. Choose meeting type:
   - **Offline**: Enter location "Ashesi Library, 2pm"
   - **Online**: Enter Zoom link "https://zoom.us/j/123456"
4. Set date and time
5. Click "Schedule"

**Expected Result**:
- Meeting appears in both users' request details
- Other user receives notification
- Meeting shows date, time, location/link
- Can schedule multiple meetings

---

### Test 8: Request Status Updates

**What to test**: Track request progress in real-time

**Steps**:
1. **Browser 1** (User 1): Stay on requests page
2. **Browser 2** (User 2): Accept a pending request
3. **Browser 1**: Within 10 seconds, see request status change to "Accepted"
4. **User 2**: Reject another request
5. **User 1**: See it update to "Rejected" automatically

**Expected Result**:
- Status updates appear without refreshing page
- Color coding changes (green for accepted, red for rejected)
- Request badges update automatically
- History is preserved

---

### Test 9: Message from Item Details

**What to test**: Quick messaging from browse page

**Steps** (as User 1):
1. Go to browse page
2. Click "View Details" on User 2's item
3. Click "Message Owner" button
4. Should redirect to messages page
5. Conversation with User 2 should open
6. Type and send message

**Expected Result**:
- Redirects to messages with conversation open
- If no existing conversation, creates new one
- Item context is preserved
- Owner receives notification

---

## 🔍 Debugging & Troubleshooting

### Check Browser Console
Open Developer Tools (F12) and look for:
- Error messages in Console tab
- Network requests in Network tab
- Make sure requests return status 200

### Common Issues

#### 1. "Authentication required" errors
**Solution**: Make sure you're logged in
```
Go to login page and sign in again
```

#### 2. Items not loading
**Solution**: Check database connection
```
Go to: http://localhost/api/test-db.php
```

#### 3. Messages not appearing
**Solution**: Verify tables exist
```
Go to: http://localhost/public/setup-realtime.php
```

#### 4. Notifications not showing
**Solution**: Check real-time manager is loaded
```
Open Console (F12)
Type: window.realTimeManager
Should not be undefined
```

#### 5. Online status not updating
**Solution**: Check if polling is active
```
Console should show: "Starting real-time updates..."
```

---

## 📊 Testing Checklist

Print this and check off as you test:

- [ ] Database migration completed successfully
- [ ] Created 2 test users
- [ ] Added 2+ test items
- [ ] Browsed items from database
- [ ] Viewed item details in modal
- [ ] Sent borrow request with form
- [ ] Received request notification
- [ ] Accepted a borrow request
- [ ] Rejected a borrow request
- [ ] Sent real-time message
- [ ] Received real-time message
- [ ] Saw online status (green dot)
- [ ] Saw offline status (gray dot)
- [ ] Received various notifications
- [ ] Scheduled a meeting (offline)
- [ ] Scheduled a meeting (online)
- [ ] Saw request status update in real-time
- [ ] Filtered items by category
- [ ] Filtered items by location
- [ ] Filtered items by price
- [ ] Searched items by keyword
- [ ] Messaged owner from item modal

---

## 🎯 Real-World Usage Scenarios

### Scenario 1: Complete Borrow Flow
1. **User A** browses items
2. **User A** finds User B's camera
3. **User A** sends borrow request
4. **User B** receives notification instantly
5. **User B** accepts request
6. **User A** gets acceptance notification
7. **User B** schedules meeting
8. **Both** receive meeting details
9. **They message** to coordinate
10. **They meet** and exchange item

### Scenario 2: Multiple Users Online
1. Open 3+ browsers with different users
2. All see each other's online status
3. Send messages between any users
4. Create requests between users
5. Watch notifications flow in real-time

### Scenario 3: High Activity Testing
1. Send 10+ messages rapidly
2. Create 5+ borrow requests
3. Accept/reject multiple requests
4. Schedule several meetings
5. Verify everything works smoothly

---

## 🛠️ Development Notes

### Real-Time Architecture

**Polling Mechanism**:
- Every 5 seconds: Check for new messages, requests, notifications
- Every 30 seconds: Update online status
- Every 10 seconds: Refresh requests page

**Key Components**:
- `realtime-manager.js` - Coordinates all real-time updates
- `online-status.js` - Tracks user presence
- `item-details.js` - Item modal and request submission
- `request-manager.js` - Request management
- `messaging.js` - Message handling

**API Endpoints**:
- `/api/messages.php` - Message operations
- `/api/requests.php` - Request operations
- `/api/notifications.php` - Notification system
- `/api/items.php` - Item listings
- `/api/online-status.php` - Presence tracking

---

## 📝 Additional Features to Test

### Filter & Search
- Category dropdown
- Location filter
- Price range (min/max)
- Search by title/description
- Sort by price (low to high, high to low)
- Sort by date (recent first)

### User Experience
- Modal animations
- Loading states
- Error messages
- Success confirmations
- Badge updates
- Notification sounds (if implemented)

---

## 🎉 Success Indicators

Your system is working perfectly when:

1. ✅ Two users can message each other in real-time
2. ✅ Borrow requests appear instantly for the lender
3. ✅ Accepting/rejecting requests updates both users' views
4. ✅ Online status shows accurately (green/gray dots)
5. ✅ Notification badges update automatically
6. ✅ No page refreshes needed for updates
7. ✅ All modals open and close smoothly
8. ✅ Item details load correctly
9. ✅ Forms validate properly
10. ✅ Database stays synchronized

---

## 🚨 Emergency Reset

If everything breaks, reset with:

```sql
-- Run this in your MySQL database
DROP TABLE IF EXISTS user_online_status;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS meeting_schedules;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS borrow_requests;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS user_activities;

-- Then re-run setup:
-- http://localhost/public/setup-realtime.php
```

---

## 📞 Support

If you encounter issues:
1. Check browser console for errors
2. Verify database tables exist
3. Ensure both users are logged in
4. Clear browser cache and reload
5. Try in incognito/private mode
6. Check PHP error logs

---

**Happy Testing! 🎊**

Your SwapIt platform now has fully functional real-time features. Users can browse items, send requests, message each other, and coordinate meetups - all in real-time!
