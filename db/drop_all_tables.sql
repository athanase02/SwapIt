-- Drop all tables in Railway database
SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS active_listings;
DROP TABLE IF EXISTS user_dashboard_stats;
DROP TABLE IF EXISTS message_attachments;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS review_votes;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS user_follows;
DROP TABLE IF EXISTS saved_items;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS transaction_history;
DROP TABLE IF EXISTS ratings;
DROP TABLE IF EXISTS user_activities;
DROP TABLE IF EXISTS meeting_schedules;
DROP TABLE IF EXISTS online_users;
DROP TABLE IF EXISTS user_online_status;
DROP TABLE IF EXISTS borrow_requests;
DROP TABLE IF EXISTS item_images;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS verification_tokens;
DROP TABLE IF EXISTS profiles;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS site_settings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS=1;

SELECT 'All tables dropped successfully!' as status;
SHOW TABLES;
