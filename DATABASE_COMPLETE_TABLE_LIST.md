# SI2025 Database - Complete Table List

## ✅ All Tables Included (28 Total)

### Core User Tables (4)
1. ✅ **users** - User accounts
2. ✅ **profiles** - Extended user profiles  
3. ✅ **verification_tokens** - Email verification
4. ✅ **user_sessions** - Active sessions

### Item & Category Tables (3)
5. ✅ **categories** - Item categories
6. ✅ **items** - Listed items
7. ✅ **item_images** - Item photos

### Transaction & Borrowing Tables (2)
8. ✅ **borrow_requests** - Borrowing requests
9. ✅ **transactions** - Payment transactions

### Real-Time & Status Tables (5) ⭐ YOU ASKED FOR THESE
10. ✅ **user_online_status** - User online/offline status
11. ✅ **online_users** - Currently online users
12. ✅ **meeting_schedules** - Scheduled meetups
13. ✅ **login_attempts** - Failed login tracking (rate limiting)
14. ✅ **transaction_history** - Transaction audit log

### Messaging Tables (3) ⭐ INCLUDING MESSAGE ATTACHMENTS
15. ✅ **conversations** - Chat conversations
16. ✅ **messages** - Chat messages
17. ✅ **message_attachments** - File attachments in messages

### Rating & Review Tables (3) ⭐ INCLUDING RATINGS
18. ✅ **ratings** - User ratings
19. ✅ **reviews** - User reviews
20. ✅ **review_votes** - Review helpful votes

### Activity & Engagement Tables (4)
21. ✅ **user_activities** - User activity tracking
22. ✅ **cart_items** - Shopping cart
23. ✅ **saved_items** - Wishlisted items
24. ✅ **user_follows** - User following system

### Notification & Admin Tables (4)
25. ✅ **notifications** - User notifications
26. ✅ **reports** - Content reports
27. ✅ **activity_logs** - System audit logs
28. ✅ **site_settings** - Platform configuration

---

## 📊 Database Structure Verification

### Tables Specifically Requested:
- ✅ **user_online_status** (Line 200 in SI2025.sql)
- ✅ **online_users** (Line 208)
- ✅ **meeting_schedules** (Line 220)
- ✅ **transaction_history** (Line 256)
- ✅ **message_attachments** (Line 280)
- ✅ **ratings** (Line 293)
- ✅ **login_attempts** (Line 238)

### Additional Features Included:
- ✅ 2 Views: `active_listings`, `user_dashboard_stats`
- ✅ 8 Sample users with profiles
- ✅ 15 Sample items
- ✅ 15 Categories
- ✅ Sample transactions, messages, reviews
- ✅ All foreign key relationships
- ✅ Performance indexes

---

## 🔍 Table Details

### user_online_status
```sql
- user_id (FK → users)
- is_online (BOOLEAN)
- last_seen (TIMESTAMP)
- last_activity (TIMESTAMP)
```

### online_users  
```sql
- user_id (FK → users)
- socket_id (VARCHAR 255)
- connected_at (TIMESTAMP)
```

### meeting_schedules
```sql
- borrow_request_id (FK → borrow_requests)
- scheduled_time (DATETIME)
- location (VARCHAR 500)
- status (pending/confirmed/completed/cancelled)
- notes (TEXT)
```

### login_attempts
```sql
- ip_address (VARCHAR 45)
- email (VARCHAR 255)
- attempted_at (TIMESTAMP)
- success (BOOLEAN)
- user_agent (TEXT)
```

### transaction_history
```sql
- transaction_id (FK → transactions)
- user_id (FK → users)
- action (created/updated/completed/refunded/cancelled)
- old_status, new_status
- changed_at (TIMESTAMP)
```

### message_attachments
```sql
- message_id (FK → messages)
- file_url (VARCHAR 500)
- file_name, file_type, file_size
- uploaded_at (TIMESTAMP)
```

### ratings
```sql
- rater_id (FK → users)
- rated_user_id (FK → users)
- borrow_request_id (FK → borrow_requests)
- rating (1-5)
- review_text (TEXT)
```

---

## ✅ Status: ALL TABLES PRESENT

**No tables are missing!** 

The SI2025.sql file contains:
- ✅ All 28 core tables
- ✅ All requested tables (online status, meeting schedules, transaction history, etc.)
- ✅ All foreign key relationships
- ✅ Sample data for testing
- ✅ Views for common queries
- ✅ Performance indexes

---

## 🚀 To Import

Execute in Railway Query tab:

1. **`db/railway_reset_step1.sql`** (Creates SI2025 database)
2. **`db/SI2025.sql`** (All 28 tables + data)

That's it! All tables will be created including the ones you specifically asked about.
