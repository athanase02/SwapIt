# How to Populate Sample Data

## Method 1: Using Your Browser

1. **Make sure your local server is running** (XAMPP, WAMP, or similar)

2. **Open this URL in your browser**:
   ```
   http://localhost/activity_04_Final_Project/public/populate-sample-data.php
   ```
   
   Or if your path is different:
   ```
   http://localhost/your-path/public/populate-sample-data.php
   ```

3. **You should see a JSON response** like:
   ```json
   {
       "success": true,
       "messages_created": 25,
       "conversations_created": 5,
       "requests_created": 20,
       "users_found": 5,
       "items_found": 5,
       "message": "Sample data created successfully!"
   }
   ```

## Method 2: Using cURL (PowerShell)

Run this command in PowerShell:

```powershell
Invoke-WebRequest -Uri "http://localhost/activity_04_Final_Project/public/populate-sample-data.php" -UseBasicParsing | Select-Object -ExpandProperty Content
```

## Method 3: Using PHP CLI

If you have PHP in your PATH:

```powershell
php public/populate-sample-data.php
```

## What This Script Creates

For the **first user** in your database:

### Messages (25 total)
- 5 conversations with different users
- 5 messages in each conversation
- Some messages marked as read, others as unread
- Messages with realistic timestamps (last 5 hours)

### Requests (20 total)

#### Sent Requests (5)
- Status: Pending
- You are the borrower
- Various items from other users
- Future borrow dates

#### Received Requests (5)
- Status: Pending
- You are the lender
- Other users want to borrow from you
- Need your approval

#### Active Borrows (2)
- Status: Active
- Currently ongoing borrows
- Includes meeting schedules
- Mix of you as borrower/lender

#### Completed Borrows (3)
- Status: Completed
- Past transactions
- Includes reviews
- Historical data for testing

## Troubleshooting

### Error: "Database connection not available"
- Check your database configuration in `config/db.php`
- Make sure MySQL/MariaDB is running

### Error: "Need at least 2 users in database"
- You need to create user accounts first
- Sign up at least 2 users on your website

### Error: Parameter binding issues
- This has been fixed in the updated script
- Make sure you're using the latest version

### No items found
- The script will create sample items automatically if none exist
- Or you can add items through your website first

## After Running

1. **Login to your account** (the first user in the database)

2. **Check Messages**:
   - Go to: `http://localhost/your-path/public/pages/messages.html`
   - You should see 5 conversations
   - Click on any to see messages

3. **Check Requests**:
   - Go to: `http://localhost/your-path/public/pages/requests.html`
   - You should see:
     - 5 Sent Requests
     - 5 Received Requests
     - 2 Active Borrows
     - 3 Completed

## Running Multiple Times

- The script checks for existing conversations to avoid duplicates
- You can run it multiple times safely
- New data will be added each time

## Cleaning Up

To remove sample data and start fresh:

```sql
-- Delete sample messages
DELETE FROM messages WHERE conversation_id IN (
    SELECT id FROM conversations WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
);

-- Delete sample conversations
DELETE FROM conversations WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);

-- Delete sample requests
DELETE FROM borrow_requests WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR);
```

Or just run:
```sql
TRUNCATE TABLE messages;
TRUNCATE TABLE conversations;
TRUNCATE TABLE borrow_requests;
```
