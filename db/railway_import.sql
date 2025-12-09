-- Railway Import Script - Complete SwapIt Database
-- Instructions: Copy this entire file and paste into Railway's MySQL interface
-- Location: Railway Dashboard → MySQL Service → Data tab → Click on any table → Find SQL editor

-- Step 1: Clean existing tables
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS active_listings, user_dashboard_stats, message_attachments, messages, conversations, review_votes, reviews, user_follows, saved_items, cart_items, transactions, transaction_history, ratings, user_activities, meeting_schedules, online_users, user_online_status, borrow_requests, item_images, items, categories, user_sessions, verification_tokens, profiles, login_attempts, notifications, reports, activity_logs, site_settings, users;
SET FOREIGN_KEY_CHECKS=1;

-- Step 2: Create all tables (copy from SI2025.sql starting from line 20)
