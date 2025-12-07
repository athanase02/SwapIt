# SwapIt - Core Features Implementation Guide

## 🎯 Overview

We've successfully implemented the four main functionalities for SwapIt:

1. **Real-time Messaging System** - Users can communicate in real-time
2. **Request Management** - Complete borrow request workflow with approvals/rejections
3. **Meeting Scheduling** - Schedule online or offline meetings
4. **Rating & Review System** - Rate users with automatic profile updates

## 📁 Files Created

### Backend APIs (in `/api/`)
- `messages.php` - Real-time messaging API
- `requests.php` - Request management API (already existed, enhanced)
- `ratings.php` - Rating and review API (already existed, enhanced)

### Frontend JavaScript (in `/public/assets/js/`)
- `messaging.js` - Messaging system frontend
- `request-manager.js` - Request management frontend
- `rating-system.js` - Rating system frontend

### Database
- `db/schema_updates.sql` - New tables and indexes

## 🚀 Quick Start - What to Do Now

### Step 1: Update the Database Schema

**Option A: Using Railway Dashboard (Recommended for your setup)**

1. Go to your Railway dashboard: https://railway.app/
2. Select your MySQL database
3. Go to the "Data" tab
4. Click "Query"
5. Copy and paste the contents of `db/schema_updates.sql`
6. Execute the query

**Option B: Using MySQL Client**

```bash
# Connect to your Railway MySQL database
mysql -h <your-railway-host> -u <username> -p <database_name> < db/schema_updates.sql
```

### Step 2: Verify API Files

Make sure these files exist and are accessible:
- ✅ `/api/messages.php`
- ✅ `/api/requests.php`
- ✅ `/api/ratings.php`

Test by visiting: `https://your-render-url.com/api/test-connection.php`

### Step 3: Update Your Dashboard

Add the following to your `dashboard.html` (or create new pages):

```html
<!-- Add to <head> section -->
<link rel="stylesheet" href="/public/assets/css/messaging.css">
<link rel="stylesheet" href="/public/assets/css/requests.css">
<link rel="stylesheet" href="/public/assets/css/ratings.css">

<!-- Add before closing </body> -->
<script src="/public/assets/js/messaging.js"></script>
<script src="/public/assets/js/request-manager.js"></script>
<script src="/public/assets/js/rating-system.js"></script>
```

### Step 4: Create Supporting Pages

You'll need to create HTML pages for:
1. **Messages Page** - `/public/pages/messages.html`
2. **Requests Page** - `/public/pages/requests.html`
3. **Reviews Page** - Can be integrated into profile page

## 📊 How Each Feature Works

### 1. Real-time Messaging System

**Backend Flow:**
```
User A → messages.php?action=send_message → Database → Notification to User B
User B → messages.php?action=get_messages → Polls every 3 seconds → Displays new messages
```

**Key Features:**
- ✅ One-on-one conversations
- ✅ Real-time updates (polling every 3 seconds)
- ✅ Unread message counts
- ✅ Message history
- ✅ Read receipts

**API Endpoints:**
- `GET /api/messages.php?action=get_conversations` - List all conversations
- `GET /api/messages.php?action=get_messages&conversation_id=X` - Get messages
- `POST /api/messages.php?action=send_message` - Send a message
- `GET /api/messages.php?action=get_unread_count` - Get unread count

### 2. Request Management

**Complete Workflow:**
```
1. Borrower creates request → Status: "pending"
2. Lender receives notification
3. Lender approves/rejects → Status: "accepted"/"rejected"
4. Both parties can schedule meetings
5. Transaction happens
6. Status changes to "completed"
7. Both can leave reviews
```

**Key Features:**
- ✅ Create borrow requests with dates and messages
- ✅ Accept/reject requests
- ✅ Automatic price calculation based on days
- ✅ Item status updates (available → reserved)
- ✅ Notification system

**API Endpoints:**
- `POST /api/requests.php?action=create_request` - Create a request
- `POST /api/requests.php?action=accept_request` - Accept request
- `POST /api/requests.php?action=reject_request` - Reject request
- `GET /api/requests.php?action=get_my_requests` - Get all user requests
- `GET /api/requests.php?action=get_request_details&request_id=X` - Get details

### 3. Meeting Scheduling

**Workflow:**
```
1. Request is accepted
2. Either party clicks "Schedule Meeting"
3. Choose: Online (with link) or Offline (with location)
4. Set date/time and optional notes
5. Other party receives notification
6. Meeting details visible in request details
```

**Key Features:**
- ✅ Online meetings (with video call links)
- ✅ Offline meetings (with location)
- ✅ Multiple meetings per request
- ✅ Automatic notifications

**API Endpoint:**
- `POST /api/requests.php?action=schedule_meeting`

### 4. Rating & Review System

**Workflow:**
```
1. Request status = "completed"
2. Both users can leave reviews
3. Submit rating (1-5 stars) + optional comment
4. Rating automatically updates user's profile
5. Statistics recalculated:
   - Average rating
   - Total reviews
   - Items borrowed/lent count
```

**Key Features:**
- ✅ 5-star rating system
- ✅ Written reviews with title and comment
- ✅ Anonymous reviews option
- ✅ Automatic profile updates
- ✅ Rating distribution charts
- ✅ Prevention of duplicate reviews

**API Endpoints:**
- `POST /api/ratings.php?action=submit_review` - Submit review
- `GET /api/ratings.php?action=get_user_reviews&user_id=X` - Get user's reviews
- `GET /api/ratings.php?action=can_review&request_id=X` - Check eligibility
- `GET /api/ratings.php?action=get_rating_stats&user_id=X` - Get statistics

## 🗄️ New Database Tables

### `meeting_schedules`
Stores scheduled meetings between borrowers and lenders.
```sql
- id
- borrow_request_id (FK)
- scheduled_by (FK to users)
- meeting_type (online/offline)
- meeting_date
- meeting_location
- meeting_link
- meeting_status
- notes
```

### `user_activities`
Tracks all user activities for dashboard feed.
```sql
- id
- user_id (FK)
- activity_type (enum)
- related_id
- description
- created_at
```

### Enhanced Tables
- `profiles` - Added rating columns
- `conversations` - Added last_message_at
- Added indexes for better performance

## 🔧 Configuration & Testing

### Test the APIs

1. **Test Messaging:**
```bash
# Get conversations (requires authentication)
curl -X GET https://your-render-url.com/api/messages.php?action=get_conversations \
  --cookie "PHPSESSID=your_session_id"
```

2. **Test Requests:**
```bash
# Get user's requests
curl -X GET https://your-render-url.com/api/requests.php?action=get_my_requests \
  --cookie "PHPSESSID=your_session_id"
```

3. **Test Ratings:**
```bash
# Get rating stats
curl -X GET https://your-render-url.com/api/ratings.php?action=get_rating_stats \
  --cookie "PHPSESSID=your_session_id"
```

### Environment Variables

Make sure these are set in your Render dashboard:
```
DB_HOST=<your-railway-mysql-host>
DB_PORT=3306
DB_NAME=<your-database-name>
DB_USER=<your-database-user>
DB_PASSWORD=<your-database-password>
```

## 📱 Frontend Integration Examples

### Add "Message Seller" Button to Item Pages

```html
<button class="btn btn-primary start-conversation-btn" 
        data-user-id="<?php echo $item['user_id']; ?>"
        data-item-id="<?php echo $item['id']; ?>">
    <i class="fas fa-comment"></i> Message Seller
</button>
```

### Add "Borrow This Item" Button

```html
<button class="btn btn-success" onclick="openBorrowRequestModal(<?php echo $item['id']; ?>)">
    <i class="fas fa-handshake"></i> Borrow This Item
</button>
```

### Display User Rating

```html
<div class="user-rating">
    <span class="rating-stars">
        <?php echo str_repeat('★', round($user['rating_average'])); ?>
        <?php echo str_repeat('☆', 5 - round($user['rating_average'])); ?>
    </span>
    <span class="rating-text"><?php echo number_format($user['rating_average'], 1); ?> (<?php echo $user['total_reviews']; ?> reviews)</span>
</div>
```

## 🎨 Next Steps - What You Should Do

### Immediate (Do This Now!)

1. **Run the database migration:**
   - Execute `db/schema_updates.sql` on your Railway MySQL database

2. **Test the APIs:**
   - Visit your test pages
   - Create a test conversation
   - Create a test request
   - Verify data is being saved

### Short Term (Next 1-2 Days)

3. **Create the missing HTML pages:**
   - `pages/messages.html` - Messaging interface
   - `pages/requests.html` - Request management interface
   - Update `pages/profile.html` to show reviews

4. **Add CSS styling:**
   - Create `assets/css/messaging.css`
   - Create `assets/css/requests.css`
   - Create `assets/css/ratings.css`

5. **Update navigation:**
   - Add "Messages" link to navbar with unread badge
   - Add "My Requests" link to dashboard
   - Add "Reviews" tab to profile

### Medium Term (Next Week)

6. **Enhance features:**
   - Add image upload to messages
   - Add email notifications for important events
   - Add push notifications
   - Add request filtering and search
   - Add review moderation

7. **Mobile responsiveness:**
   - Ensure all pages work on mobile
   - Add mobile-specific UI for messaging
   - Optimize for touch interfaces

## 🐛 Troubleshooting

### Database Connection Issues
- Verify Railway database credentials in Render environment variables
- Check if Railway MySQL is running
- Test connection with `api/test-db.php`

### Messages Not Appearing
- Check browser console for JavaScript errors
- Verify user is logged in (check session)
- Check that polling is active (should see requests every 3 seconds)

### Requests Not Creating
- Verify item status is "available"
- Check user can't request their own items
- Verify date ranges are valid

### Ratings Not Saving
- Ensure request status is "completed"
- Check user hasn't already reviewed
- Verify user is part of the transaction

## 📚 Code Architecture

### Backend Structure
```
api/
├── messages.php      (MessagingService class)
├── requests.php      (RequestService class)
└── ratings.php       (RatingService class)
```

### Frontend Structure
```
public/assets/js/
├── messaging.js         (MessagingSystem class)
├── request-manager.js   (RequestManager class)
└── rating-system.js     (RatingSystem class)
```

### Database Layer
```
config/
├── db.php            (Main database connection)
├── db_mysql.php      (MySQL-specific connection)
└── db_with_fallback.php
```

## 🔐 Security Features Implemented

- ✅ Session-based authentication
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (HTML escaping)
- ✅ CSRF protection (session validation)
- ✅ Authorization checks (user can only access their data)
- ✅ Input validation and sanitization
- ✅ Rate limiting logs
- ✅ Activity logging for audit trail

## 📈 Performance Optimizations

- ✅ Database indexes on frequently queried columns
- ✅ Efficient polling (3-second intervals for messages)
- ✅ Lazy loading of conversations
- ✅ Pagination for reviews and messages
- ✅ Connection pooling with PDO

## 🎉 What's Been Accomplished

✅ Complete messaging system with real-time updates
✅ Full request workflow (create, approve, reject, schedule)
✅ Meeting scheduling (online & offline)
✅ Rating system with automatic profile updates
✅ Notification system
✅ Activity tracking for dashboard
✅ Comprehensive logging for debugging
✅ Mobile-ready JavaScript code
✅ Secure API endpoints
✅ Database schema with proper relationships

---

**Ready to Deploy!** 🚀

The backend infrastructure is complete. Now focus on:
1. Running the database migration
2. Creating the HTML pages
3. Adding CSS styling
4. Testing with real users

Need help with any specific part? Let me know!
