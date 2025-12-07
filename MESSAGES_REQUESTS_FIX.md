# Messages and Requests Fix Documentation

## Date: December 7, 2025

## Issues Fixed

### 1. Messages Feature Issues

#### API Action Name Mismatch
- **Problem**: Frontend was calling `action=getConversations` but backend expected `action=get_conversations`
- **Fix**: Updated `messaging.js` to use correct action name `get_conversations`
- **File**: `public/assets/js/messaging.js` (line 58)

#### API Path Issues
- **Problem**: Mixed use of `/api/` and `../api/` paths causing 404 errors
- **Fix**: Standardized all API calls to use `../api/` for consistency
- **Files Modified**: `public/assets/js/messaging.js`
- **Affected Functions**:
  - `loadConversations()` - line 58
  - `loadMessages()` - line 152
  - `sendMessage()` - line 218
  - `startConversation()` - line 247
  - `getUnreadCount()` - line 286

#### HTML Element ID Mismatches
- **Problem**: JavaScript looking for IDs that don't exist in HTML
- **Fixes**:
  - Changed `send-message-btn` to `sendBtn`
  - Changed `message-input` to `messageInput`
- **File**: `public/assets/js/messaging.js` (lines 24, 30, 206)

#### Missing Form Submission Handler
- **Problem**: Message form submission wasn't being handled properly
- **Fix**: Added form submit event listener to handle Enter key and button clicks
- **File**: `public/assets/js/messaging.js` - `setupEventListeners()` method

### 2. Requests Feature Issues

#### API Action Name Mismatch
- **Problem**: Frontend was calling `action=getUserRequests` but backend expected `action=get_my_requests`
- **Fix**: Updated `request-manager.js` to use correct action name `get_my_requests`
- **File**: `public/assets/js/request-manager.js` (line 94)

#### API Path Issues
- **Problem**: Mixed use of `/api/` and `../api/` paths causing 404 errors
- **Fix**: Standardized all API calls to use `../api/` for consistency
- **Files Modified**: `public/assets/js/request-manager.js`
- **Affected Functions**:
  - `loadRequests()` - line 94
  - `submitRequest()` - line 318
  - `acceptRequest()` - line 353
  - `rejectRequest()` - line 383
  - `scheduleMeeting()` - line 413
  - `viewRequestDetails()` - line 440

#### Missing request_type Field
- **Problem**: Backend API wasn't returning `request_type` field needed to differentiate sent/received requests
- **Fix**: Modified SQL query in `getUserRequests()` to calculate and return `request_type` based on user role
- **File**: `api/requests.php` (lines 354-386)
- **Added Fields**:
  - `request_type`: 'sent' or 'received' based on whether user is borrower or lender
  - `other_user_name`: Name of the other party in the request
  - `other_user_avatar`: Avatar of the other party in the request

## Testing Checklist

### Messages Feature
- [ ] Load messages page successfully
- [ ] View list of conversations
- [ ] Click on a conversation to view messages
- [ ] Send a new message
- [ ] See sent message appear in chat
- [ ] Receive messages (test with another user)
- [ ] View unread message count
- [ ] Start new conversation from browse page

### Requests Feature
- [ ] Load requests page successfully
- [ ] View sent requests tab
- [ ] View received requests tab
- [ ] View active borrows tab
- [ ] View completed tab
- [ ] Create new borrow request
- [ ] Accept incoming request (as lender)
- [ ] Reject incoming request (as lender)
- [ ] Schedule meeting for accepted request
- [ ] View request details

## Database Requirements

Ensure the following tables exist in your database:

1. **conversations** - Stores conversation threads between users
2. **messages** - Stores individual messages
3. **borrow_requests** - Stores borrow request details
4. **meeting_schedules** - Stores meeting information (optional)
5. **notifications** - Stores user notifications (optional)

Run `db/create_missing_tables.sql` if tables don't exist.

## Session Requirements

Both features require user authentication:
- User must be logged in with valid session
- `$_SESSION['user_id']` must be set
- Backend APIs will return error if not authenticated

## API Endpoints Reference

### Messages API (`api/messages.php`)

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_conversations` | GET | none | Get all user's conversations |
| `get_messages` | GET | `conversation_id`, `limit` (optional), `offset` (optional) | Get messages for a conversation |
| `send_message` | POST | `receiver_id`, `message`, `item_id` (optional) | Send a new message |
| `mark_as_read` | POST | `conversation_id` | Mark messages as read |
| `get_unread_count` | GET | none | Get unread message count |
| `start_conversation` | POST | `user_id`, `item_id` (optional) | Create/get conversation with user |

### Requests API (`api/requests.php`)

| Action | Method | Parameters | Description |
|--------|--------|------------|-------------|
| `get_my_requests` | GET | `role` (optional), `status` (optional) | Get user's requests |
| `create_request` | POST | `item_id`, `start_date`, `end_date`, `message`, etc. | Create borrow request |
| `accept_request` | POST | `request_id`, `notes` (optional) | Accept a request |
| `reject_request` | POST | `request_id`, `reason` (optional) | Reject a request |
| `schedule_meeting` | POST | `request_id`, `meeting_type`, `meeting_date`, etc. | Schedule meeting |
| `get_request_details` | GET | `request_id` | Get detailed request info |

## Files Modified

1. `public/assets/js/messaging.js` - Fixed API calls, IDs, and form handling
2. `public/assets/js/request-manager.js` - Fixed API calls and paths
3. `api/requests.php` - Added request_type field to response

## Next Steps

1. Test all functionality with real user accounts
2. Check browser console for any remaining errors
3. Verify database tables exist and are properly structured
4. Test with multiple users to verify real-time messaging
5. Test request workflow from creation to completion

## Notes

- All API paths now use relative paths (`../api/`) from the pages directory
- Backend APIs require valid session authentication
- Frontend JavaScript files are loaded from `pages/` directory, so paths are relative to that location
- Forms now properly prevent default submission and handle via AJAX
- Error handling is in place for failed API calls
