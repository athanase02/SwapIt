# SwapIt - Quick Deployment Guide
# Run this after implementing the core features

Write-Host "SwapIt - Core Features Deployment" -ForegroundColor Green
Write-Host "===================================" -ForegroundColor Green
Write-Host ""

# Check if we're in the right directory
if (-Not (Test-Path "api/messages.php")) {
    Write-Host "Error: Please run this script from the project root directory" -ForegroundColor Red
    exit 1
}

Write-Host "✓ Project files detected" -ForegroundColor Green
Write-Host ""

# Step 1: Check API files
Write-Host "Step 1: Checking API files..." -ForegroundColor Yellow
$apiFiles = @("api/messages.php", "api/requests.php", "api/ratings.php")
$allPresent = $true

foreach ($file in $apiFiles) {
    if (Test-Path $file) {
        Write-Host "  ✓ $file" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $file - MISSING!" -ForegroundColor Red
        $allPresent = $false
    }
}

if (-Not $allPresent) {
    Write-Host ""
    Write-Host "Some API files are missing. Please ensure all files are created." -ForegroundColor Red
    exit 1
}

Write-Host ""

# Step 2: Check JavaScript files
Write-Host "Step 2: Checking JavaScript files..." -ForegroundColor Yellow
$jsFiles = @(
    "public/assets/js/messaging.js",
    "public/assets/js/request-manager.js",
    "public/assets/js/rating-system.js"
)

foreach ($file in $jsFiles) {
    if (Test-Path $file) {
        Write-Host "  ✓ $file" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $file - MISSING!" -ForegroundColor Red
    }
}

Write-Host ""

# Step 3: Check database migration file
Write-Host "Step 3: Checking database migration..." -ForegroundColor Yellow
if (Test-Path "db/schema_updates.sql") {
    Write-Host "  ✓ db/schema_updates.sql" -ForegroundColor Green
} else {
    Write-Host "  ✗ db/schema_updates.sql - MISSING!" -ForegroundColor Red
}

Write-Host ""
Write-Host "===================================" -ForegroundColor Green
Write-Host "NEXT STEPS:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Update Database Schema (CRITICAL)" -ForegroundColor Yellow
Write-Host "   - Go to Railway Dashboard: https://railway.app/" -ForegroundColor White
Write-Host "   - Select your MySQL database" -ForegroundColor White
Write-Host "   - Run the SQL from: db/schema_updates.sql" -ForegroundColor White
Write-Host ""

Write-Host "2. Test the APIs" -ForegroundColor Yellow
Write-Host "   Visit: https://your-render-url.com/api/test-connection.php" -ForegroundColor White
Write-Host ""

Write-Host "3. Create HTML Pages (TODO)" -ForegroundColor Yellow
Write-Host "   - pages/messages.html" -ForegroundColor White
Write-Host "   - pages/requests.html" -ForegroundColor White
Write-Host "   - Update pages/profile.html for reviews" -ForegroundColor White
Write-Host ""

Write-Host "4. Add CSS Styling (TODO)" -ForegroundColor Yellow
Write-Host "   - assets/css/messaging.css" -ForegroundColor White
Write-Host "   - assets/css/requests.css" -ForegroundColor White
Write-Host "   - assets/css/ratings.css" -ForegroundColor White
Write-Host ""

Write-Host "5. Deploy to Render" -ForegroundColor Yellow
Write-Host "   git add ." -ForegroundColor White
Write-Host "   git commit -m 'Add core features: messaging, requests, ratings'" -ForegroundColor White
Write-Host "   git push origin master" -ForegroundColor White
Write-Host ""

Write-Host "===================================" -ForegroundColor Green
Write-Host ""
Write-Host "Would you like to:" -ForegroundColor Cyan
Write-Host "  [1] View the database migration SQL" -ForegroundColor White
Write-Host "  [2] Create git commit for deployment" -ForegroundColor White
Write-Host "  [3] View implementation documentation" -ForegroundColor White
Write-Host "  [4] Exit" -ForegroundColor White
Write-Host ""

$choice = Read-Host "Enter your choice (1-4)"

switch ($choice) {
    "1" {
        Write-Host ""
        Write-Host "Opening db/schema_updates.sql..." -ForegroundColor Green
        if (Get-Command code -ErrorAction SilentlyContinue) {
            code db/schema_updates.sql
        } else {
            notepad db/schema_updates.sql
        }
    }
    "2" {
        Write-Host ""
        Write-Host "Preparing git commit..." -ForegroundColor Green
        git add .
        Write-Host ""
        Write-Host "Files staged. Now run:" -ForegroundColor Yellow
        Write-Host "  git commit -m 'feat: Add core features - messaging, requests, ratings'" -ForegroundColor White
        Write-Host "  git push origin master" -ForegroundColor White
    }
    "3" {
        Write-Host ""
        Write-Host "Opening documentation..." -ForegroundColor Green
        if (Get-Command code -ErrorAction SilentlyContinue) {
            code docs/CORE_FEATURES_IMPLEMENTATION.md
        } else {
            notepad docs/CORE_FEATURES_IMPLEMENTATION.md
        }
    }
    "4" {
        Write-Host ""
        Write-Host "Done! Good luck with your deployment! 🚀" -ForegroundColor Green
    }
    default {
        Write-Host ""
        Write-Host "Invalid choice. Exiting..." -ForegroundColor Red
    }
}

Write-Host ""
