# Real-Time Transaction System - Implementation Guide

## Overview
This document outlines the comprehensive real-time transaction workflow implemented for SwapIt, enabling users to communicate, request items, approve requests, schedule meetings, exchange items, and rate each other - all within the website.

## Features Implemented

### 1. Real-Time Notification System ✅

**Files Created:**
- `api/notifications.php` - Backend notification service
- `public/assets/js/real-time-notifications.js` - Frontend notification handler
- `public/assets/css/real-time-notifications.css` - Notification UI styles

**Capabilities:**
- Live notification bell with unread count badge
- Dropdown notification panel
- Real-time polling (every 5 seconds) for new notifications
- Toast notifications for important events
- Multiple notification types:
  - New messages
  - Borrow requests
  - Request accepted/rejected
  - Meeting scheduled
  - Payment received
  - Item returned
  - Reviews received
  - Reminders

**Usage:**
```javascript
// Automatically initialized on page load
// Shows notifications in real-time
// Click bell icon to view all notifications
```

### 2. Online Presence Tracking ✅

**Features:**
- Tracks user online/away/offline status
- Updates every 30 seconds
- Visual indicators (green = online, yellow = away, gray = offline)
- Pulse animation for online users
- Automatically updates on page visibility change

**Integration:**
```javascript
// In messaging.js - automatically loads online users
this.loadOnlineUsers();
this.updateOnlineStatusIndicators();
```

### 3. Typing Indicators ✅

**Files Modified:**
- `public/assets/js/messaging.js` - Added typing detection
- `public/assets/css/messaging.css` - Added typing animation

**Features:**
- Shows when other user is typing
- Animated 3-dot indicator
- Auto-hides after 1 second of inactivity
- Works in real-time during conversations

**How it works:**
1. User starts typing → `handleTyping()` triggered
2. Backend notified of typing status
3. Other user sees animated typing indicator
4. Indicator disappears after typing stops

### 4. Transaction Confirmation System ✅

**Files Created:**
- `api/transactions.php` - Transaction management backend
- `public/assets/js/transaction-manager.js` - Frontend transaction handler
- `public/pages/transactions.html` - Transaction tracking UI

**Complete Workflow:**

#### Step 1: Meeting Scheduled
- Lender approves borrow request
- Meeting time/location set
- Both parties notified

#### Step 2: Pickup Confirmation
- Either party confirms item pickup
- Request status → "active"
- Transaction history logged
- Start date recorded

#### Step 3: Active Transaction
- Item is being borrowed
- Transaction tracked in system
- Both parties can view status

#### Step 4: Return Confirmation
- Either party confirms item return
- Lender specifies item condition:
  - Excellent
  - Good
  - Fair
  - Poor
- Request status → "completed"
- End date recorded

#### Step 5: Rating Prompt
- Both parties prompted to rate each other
- Rating system integration
- Transaction complete

### 5. Transaction History Tracking ✅

**Database Table:**
```sql
transaction_history
- request_id
- borrower_id
- lender_id
- item_id
- action_type (pickup_confirmed, return_confirmed, etc.)
- performed_by
- notes
- created_at
```

**Features:**
- Complete audit trail of all actions
- Timestamped entries
- User attribution
- Detailed notes for each action

### 6. Issue Reporting ✅

**Features:**
- Report problems during transactions
- Issue types:
  - Item damaged
  - Item missing
  - Late return
  - Not returned
  - Other
- Detailed description field
- Automatic notification to other party
- Logged in transaction history

### 7. Enhanced Database Schema ✅

**File:** `db/real_time_transactions.sql`

**New Tables:**

1. **meeting_schedules**
   - Stores meeting details for exchanges
   - Location (online/offline)
   - Date/time
   - Status tracking

2. **user_activities**
   - Logs all user actions
   - Activity type and details
   - Timestamp tracking

3. **notifications**
   - Stores all notifications
   - Title, message, type
   - Read/unread status
   - Action URLs

4. **transaction_history**
   - Complete transaction audit log
   - All actions and confirmations
   - User attribution

5. **online_users**
   - Tracks current online status
   - Last activity timestamp
   - Status (online/away/offline)

6. **message_attachments**
   - Support for file sharing
   - Images, documents, etc.
   - File metadata

**Enhanced Tables:**
- `borrow_requests` - Added return_condition field
- `conversations` - Added last_activity timestamp

**New View:**
- `active_transactions` - Quick view of ongoing exchanges

## Implementation Steps

### Step 1: Apply Database Schema
```sql
-- Run this in Railway MySQL
SOURCE db/real_time_transactions.sql;
```

### Step 2: Add Scripts to HTML Pages

**For all pages (in `<head>`):**
```html
<link rel="stylesheet" href="../assets/css/real-time-notifications.css">
<script src="../assets/js/real-time-notifications.js"></script>
```

**For messages page:**
```html
<link rel="stylesheet" href="../assets/css/messaging.css">
<script src="../assets/js/messaging.js"></script>
```

**For transactions page:**
```html
<script src="../assets/js/transaction-manager.js"></script>
```

### Step 3: Add Notification Bell to Navigation

**Add to your navigation bar:**
```html
<div class="notification-bell-container" id="notificationBell">
    <i class="fas fa-bell notification-bell"></i>
    <span class="notification-badge">0</span>
</div>

<div class="notification-panel" id="notificationPanel">
    <div class="notification-panel-header">
        <span class="notification-panel-title">Notifications</span>
        <button class="mark-all-read-btn" id="markAllReadBtn">Mark all as read</button>
    </div>
    <div class="notifications-list" id="notificationsList">
        <!-- Notifications populate here -->
    </div>
</div>
```

### Step 4: Update Existing Pages

**Messages Page (`pages/messages.html`):**
- Already updated with typing indicators
- Online status shows automatically
- No additional changes needed

**Requests Page (`pages/requests.html`):**
- Add transaction confirmation buttons
- Integrate with transaction-manager.js

**Dashboard:**
- Add link to new transactions page
- Show active transaction count

## API Endpoints

### Notifications API (`api/notifications.php`)

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_notifications` | GET | - | Get all user notifications |
| `get_unread_count` | GET | - | Get count of unread notifications |
| `mark_as_read` | POST | `notification_id` | Mark single notification as read |
| `mark_all_read` | POST | - | Mark all notifications as read |
| `get_realtime_updates` | GET | `last_check` | Get new updates since last check |
| `update_status` | POST | `status` | Update online status |
| `get_online_users` | GET | - | Get list of online users |

### Transactions API (`api/transactions.php`)

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `confirm_pickup` | POST | `request_id` | Confirm item pickup |
| `confirm_return` | POST | `request_id`, `condition` | Confirm item return |
| `get_history` | GET | `request_id` | Get transaction history |
| `get_my_transactions` | GET | `status` | Get user's transactions |
| `report_issue` | POST | `request_id`, `issue_type`, `description` | Report transaction issue |

## User Flow Example

### Complete Transaction Workflow:

1. **User A lists an item**
   - Item appears in browse page
   - Status: Available

2. **User B requests to borrow**
   ```
   User A receives notification: "New borrow request from User B"
   ```

3. **User A reviews and approves**
   ```
   User B receives notification: "Your request was accepted!"
   Meeting scheduling form appears
   ```

4. **User A schedules meeting**
   ```
   Both users receive notification: "Meeting scheduled for [date/time] at [location]"
   Calendar reminders set
   ```

5. **Meeting happens - Pickup confirmation**
   ```
   User A or B clicks "Confirm Pickup"
   Other user receives notification: "Pickup confirmed for [item]"
   Transaction status → Active
   Timer starts
   ```

6. **During borrow period**
   ```
   Both users can:
   - Send messages
   - View transaction status
   - Report issues if needed
   - See online status
   ```

7. **Return time - Return confirmation**
   ```
   User A or B clicks "Confirm Return"
   Modal appears: "Select item condition"
   User selects: Excellent/Good/Fair/Poor
   Other user receives notification: "Item returned"
   Transaction status → Completed
   ```

8. **Rating prompt**
   ```
   Both users prompted: "Rate your experience"
   Navigate to rating page
   Leave reviews
   Transaction fully complete
   ```

## Real-Time Features Summary

✅ **Live Notifications** - No page refresh needed
✅ **Online Presence** - See who's online
✅ **Typing Indicators** - Know when someone is responding
✅ **Transaction Tracking** - Complete audit trail
✅ **Instant Updates** - 5-second polling interval
✅ **Toast Alerts** - Non-intrusive notifications
✅ **Issue Reporting** - Built-in dispute handling
✅ **History Viewing** - Full transaction timeline
✅ **Condition Tracking** - Item condition at return
✅ **Meeting Scheduling** - Coordinated exchanges

## Testing the System

### Local Testing:

1. **Start XAMPP** (Apache + MySQL)

2. **Run SQL schema:**
   ```sql
   mysql -u root -p SI2025 < db/real_time_transactions.sql
   ```

3. **Open two browser windows:**
   - Window 1: Login as User A
   - Window 2: Login as User B

4. **Test messaging:**
   - Send messages between users
   - Watch typing indicators
   - Check online status

5. **Test transaction flow:**
   - User B requests item from User A
   - User A approves and schedules meeting
   - Both confirm pickup
   - Both confirm return
   - Check notifications throughout

6. **Verify notifications:**
   - Check bell icon badge
   - Open notification panel
   - Click notifications to navigate
   - Mark as read

### Production Testing (Render):

1. **Apply schema to Railway MySQL:**
   - Login to Railway dashboard
   - Open MySQL console
   - Run `real_time_transactions.sql`

2. **Push code to GitHub:**
   ```bash
   git add .
   git commit -m "Add real-time transaction system"
   git push origin master
   ```

3. **Deploy on Render:**
   - Render auto-deploys from GitHub
   - Wait for build to complete
   - Test on live site

4. **Monitor logs:**
   - Check Render logs for errors
   - Monitor Railway MySQL connections
   - Verify API responses

## Configuration

### Polling Intervals:

```javascript
// Notifications (real-time-notifications.js)
pollingInterval: 5000  // 5 seconds

// Online status (real-time-notifications.js)
onlineStatusInterval: 30000  // 30 seconds

// Messages (messaging.js)
messagePolling: 3000  // 3 seconds (existing)

// Online users (messaging.js)
onlinePolling: 30000  // 30 seconds
```

### Notification Types:

```javascript
const notificationTypes = {
    'new_message': 'New Message',
    'borrow_request': 'Borrow Request',
    'request_accepted': 'Request Accepted',
    'request_rejected': 'Request Rejected',
    'meeting_scheduled': 'Meeting Scheduled',
    'payment_received': 'Payment Received',
    'item_returned': 'Item Returned',
    'review_received': 'Review Received',
    'reminder': 'Reminder'
};
```

## Deployment Checklist

- [ ] Apply `real_time_transactions.sql` to database
- [ ] Add notification scripts to all pages
- [ ] Update navigation with notification bell
- [ ] Link transactions page in dashboard
- [ ] Test notification system
- [ ] Test typing indicators
- [ ] Test online status
- [ ] Test complete transaction workflow
- [ ] Test issue reporting
- [ ] Verify transaction history
- [ ] Check mobile responsiveness
- [ ] Test with multiple users
- [ ] Monitor performance
- [ ] Check error handling

## Performance Considerations

### Optimizations:
1. **Polling** - Uses efficient queries with indexes
2. **Caching** - Client-side caching of user data
3. **Batch Updates** - Single request for multiple updates
4. **Debouncing** - Typing indicators debounced to 1 second
5. **Lazy Loading** - Transaction history loaded on demand

### Database Indexes:
```sql
-- Already included in schema
INDEX idx_user_id ON notifications(user_id)
INDEX idx_created_at ON notifications(created_at)
INDEX idx_request_id ON transaction_history(request_id)
INDEX idx_online_status ON online_users(user_id, status)
```

## Troubleshooting

### Notifications not appearing:
1. Check browser console for errors
2. Verify `notifications.php` API is accessible
3. Check session authentication
4. Verify database table exists

### Typing indicators not working:
1. Check `messaging.js` for errors
2. Verify event listeners attached
3. Check network tab for API calls
4. Test with two browsers

### Transaction confirmations failing:
1. Check `transactions.php` for errors
2. Verify request_id is valid
3. Check user permissions
4. Review transaction status

### Online status not updating:
1. Check visibility change listeners
2. Verify beforeunload event
3. Check polling interval
4. Review `online_users` table

## Next Steps

### Future Enhancements:
1. **WebSocket Integration** - Replace polling with WebSockets
2. **Push Notifications** - Browser push notifications
3. **Mobile App** - Native mobile notifications
4. **Video Chat** - WebRTC for virtual meetings
5. **Payment Integration** - Secure payment processing
6. **Calendar Integration** - Google Calendar sync
7. **Map Integration** - Location-based meeting points
8. **Photo Upload** - Item condition photos
9. **Dispute Resolution** - Admin intervention system
10. **Analytics Dashboard** - Transaction statistics

## Support

For issues or questions:
- Check browser console for errors
- Review API responses in Network tab
- Check database tables and data
- Verify all files are uploaded
- Test with incognito/private browsing
- Clear cache and cookies

## Conclusion

The real-time transaction system provides a complete, seamless experience for users to:
- Communicate instantly
- Track transactions
- Exchange items safely
- Rate experiences
- Resolve issues

All interactions happen within the website with live updates, creating a professional, modern user experience.
