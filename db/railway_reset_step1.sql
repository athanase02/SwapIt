-- ============================================================
-- STEP 1: RAILWAY DATABASE RESET
-- Copy and paste this entire script into Railway Web UI
-- Go to: Railway Dashboard → MySQL Service → Query/Data Tab
-- ============================================================

-- Drop all databases (clean slate)
DROP DATABASE IF EXISTS railway;
DROP DATABASE IF EXISTS si2025;
DROP DATABASE IF EXISTS SI2025;

-- Create fresh SI2025 database
CREATE DATABASE SI2025 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Verify
SHOW DATABASES;

SELECT '✅ Step 1 Complete: SI2025 created!' as Status;
SELECT 'Next: Copy db/SI2025.sql and execute in Railway' as NextStep;
