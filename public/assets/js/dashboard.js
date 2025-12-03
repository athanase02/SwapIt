/**
 * Dashboard Page Functionality
 * User dashboard with orders and listings management - PERSONALIZED FOR LOGGED-IN USERS ONLY
 * 
 * @class DashboardManager
 * @author Athanase Abayo - Core dashboard architecture and user session handling
 * @author Mabinty Mambu - Listings management and display
 * @author Victoria Ama Nyonator - User stats calculation and UI updates
 * @version 2.0
 */
class DashboardManager {
    /**
     * Initialize the dashboard manager
     * @constructor
     * @author Athanase Abayo
     */
    constructor() {
        this.user = null;
        this.init();
    }

    /**
     * Initialize dashboard and verify user authentication
     * @author Athanase Abayo - Authentication flow
     * @author Mabinty Mambu - User interface setup
     */
    async init() {
    try {
        // Initialize translation system first
        if (window.swapitTranslation && !window.swapitTranslation.isInitialized()) {
            await window.swapitTranslation.init();
        }
        
        // Wait for auth to be fully initialized
        await new Promise(resolve => {
            if (window.SWAPIT_AUTH_READY && window.swapitAuth) {
                resolve();
            } else {
                const checkAuth = setInterval(() => {
                    if (window.SWAPIT_AUTH_READY && window.swapitAuth) {
                        clearInterval(checkAuth);
                        resolve();
                    }
                }, 50);
            }
        });

        // Check session with server - REQUIRED FOR DASHBOARD ACCESS
        const user = await window.swapitAuth.checkSession();
        
        if (!user) {
            // Redirect to login if not authenticated - Dashboard is PRIVATE
            if (window.showToast) {
                window.showToast('Please login to access your dashboard', 'warning', 'Authentication Required');
                setTimeout(() => window.location.href = 'login.html', 2000);
            } else {
                alert('Please login to access your dashboard');
                window.location.href = 'login.html';
            }
            return;
        }

        // Personalize dashboard with user data
        const userName = user.full_name || user.email.split('@')[0];
        
        // Get translated welcome message
        const welcomeText = window.swapitTranslation?.translations?.dashboard?.welcome || 'Welcome back';
        document.getElementById('welcomeTitle').textContent = `${welcomeText}, ${userName}!`;
        
        // Activity text is already translated via data-i18n, just ensure it's applied
        window.swapitTranslation?.applyTranslations();

        // Update user avatar if available
        const userAvatar = document.getElementById('userAvatar');
        if (user.avatar_url) {
            userAvatar.src = user.avatar_url;
        } else {
            // Show default initials avatar if no profile picture
            const initials = userName.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
            userAvatar.style.display = 'none';
            const avatarContainer = userAvatar.parentElement;
            avatarContainer.innerHTML = `
                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #7ef9ff, #5b7cfe); display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0a0b10; font-size: 0.9rem;">
                    ${initials}
                </div>
            `;
        }

        // Load user's personal data
        await this.loadUserListings();
        this.loadUserStats();
        this.loadUserActivities();

        // Setup logout button
        document.getElementById('logoutBtn').addEventListener('click', async () => {
            try {
                await window.swapitAuth.signOut();
                if (window.showToast) {
                    window.showToast('You have been logged out successfully', 'success', 'Logged Out');
                    setTimeout(() => window.location.href = '/home.html', 1500);
                } else {
                    alert('Logged out successfully');
                    window.location.href = '/home.html';
                }
            } catch (error) {
                console.error('Logout error:', error);
                if (window.showToast) {
                    window.showToast('Failed to logout. Please try again.', 'error', 'Logout Failed');
                } else {
                    alert('Failed to logout. Please try again.');
                }
            }
        });
    } catch (err) {
        console.error('Dashboard initialization error:', err);
        window.location.href = 'login.html';
    }
}

    /**
     * Load user's listings (personalized content)
     * @author Mabinty Mambu - Listings loading and rendering
     * @author Victoria Ama Nyonato - Empty state handling
     */
    async loadUserListings() {
        const container = document.getElementById('listingsContainer');
        
        if (!container) return;
        
        try {
            // Fetch user's listings from API
            const response = await fetch('/api/listings.php?action=getUserListings', {
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                
                if (data.success && data.items && data.items.length > 0) {
                    // Display items from database
                    container.innerHTML = data.items.map(item => `
                        <div class="listing-card">
                            <img src="${item.image_url || 'https://placehold.co/200x150?text=Item'}" 
                                 alt="${item.title || 'Item'}"
                                 onerror="this.src='https://placehold.co/200x150?text=Item'">
                            <div class="listing-info">
                                <h4>${item.title || 'Untitled'}</h4>
                                <p>${(item.description || '').substring(0, 80)}${item.description && item.description.length > 80 ? '...' : ''}</p>
                                <div class="listing-meta">
                                    <span class="price">GHS ${item.price || 0}</span>
                                    <span class="status status--${item.status || 'available'}">${item.status || 'available'}</span>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    return;
                }
            }
        } catch (error) {
            console.warn('Could not load listings from API:', error);
        }
        
        // Fallback to localStorage if API fails
        const pendingItems = JSON.parse(localStorage.getItem('swapit_pending_items') || '[]');
        
        if (pendingItems.length === 0) {
            container.innerHTML = `
          <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <p data-i18n="dashboard.noListings">You don't have any listings yet</p>
            <a href="/pages/add-listing.html" class="btn btn--primary" style="margin-top: 1rem;">
              <i class="fas fa-plus"></i>
              <span data-i18n="dashboard.createFirstListing">Create Your First Listing</span>
            </a>
          </div>
        `;
        // Apply translations to the new content
        window.swapitTranslation?.applyTranslations();
        return;
    }

    container.innerHTML = '';
    pendingItems.forEach((item, index) => {
        const listingCard = document.createElement('div');
        listingCard.className = 'listing-card';
        listingCard.innerHTML = `
          <div class="listing-card-image">
            <img src="${item.image_url || 'https://placehold.co/400x300?text=No+Image'}" alt="${item.title}">
          </div>
          <div class="listing-card-content">
            <div class="listing-card-title">${item.title}</div>
            <div class="listing-card-meta">
              ${item.price > 0 ? 'GHS ' + item.price + '/day' : 'Free'} • ${item.location || 'No location'}
            </div>
            <span class="listing-status active">Active</span>
          </div>
          <div class="listing-card-actions">
            <button class="btn btn--secondary btn--sm" onclick="editListing(${index})">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn--danger btn--sm" onclick="deleteListing(${index})">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        `;
        container.appendChild(listingCard);
    });
}

    /**
     * Load user stats (personalized metrics)
     * @author Victoria Ama Nyonato - Stats calculation and display
     * @author Athanase Abayo - Data aggregation
     */
    async loadUserStats() {
        try {
            // Fetch stats from server
            const response = await fetch('../api/profile.php?action=get_stats', {
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    const stats = data.stats;
                    document.getElementById('activeListings').textContent = stats.active_listings || 0;
                    document.getElementById('totalBorrows').textContent = stats.active_borrows || 0;
                    document.getElementById('itemsLent').textContent = stats.items_lent || 0;
                    document.getElementById('userRating').textContent = '5.0';
                    return;
                }
            }
        } catch (error) {
            console.error('Failed to load stats from server:', error);
        }
        
        // Fallback to localStorage
        const pendingItems = JSON.parse(localStorage.getItem('swapit_pending_items') || '[]');
        const cart = JSON.parse(localStorage.getItem('swapit_cart') || '[]');
        const wishlist = JSON.parse(localStorage.getItem('swapit_wishlist') || '[]');
        
        document.getElementById('activeListings').textContent = pendingItems.length;
        document.getElementById('totalBorrows').textContent = cart.length;
        document.getElementById('itemsLent').textContent = wishlist.length;
        document.getElementById('userRating').textContent = '5.0';
    }
    
    /**
     * Load user's recent activities
     * @author Athanase Abayo - Activity tracking and display
     */
    async loadUserActivities() {
        const activityContainer = document.getElementById('activityContainer');
        
        if (!activityContainer) {
            console.log('Activity container not found');
            return;
        }
        
        try {
            const response = await fetch('../api/profile.php?action=get_activities&limit=10', {
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success) {
                if (data.activities && data.activities.length > 0) {
                    this.displayActivities(data.activities);
                } else {
                    // No activities yet
                    activityContainer.innerHTML = `
                        <div class="empty-state" style="padding: 2rem 1rem;">
                            <i class="fas fa-inbox" style="font-size: 2rem; opacity: 0.5; margin-bottom: 0.5rem;"></i>
                            <p data-i18n="dashboard.noActivity" style="color: #9aa5c3;">No activity yet</p>
                            <p data-i18n="dashboard.startActivity" style="color: #9aa5c3; font-size: 0.85rem; margin-top: 0.5rem;">Start creating listings or browsing items!</p>
                        </div>
                    `;
                    // Apply translations to the new content
                    window.swapitTranslation?.applyTranslations();
                }
            } else {
                throw new Error(data.error || 'Failed to load activities');
            }
        } catch (error) {
            console.error('Failed to load activities:', error);
            // Show error state
            activityContainer.innerHTML = `
                <div class="empty-state" style="padding: 2rem 1rem;">
                    <i class="fas fa-exclamation-circle" style="font-size: 2rem; opacity: 0.5; margin-bottom: 0.5rem; color: #ff6b6b;"></i>
                    <p style="color: #9aa5c3;">Could not load activities</p>
                </div>
            `;
        }
    }
    
    /**
     * Display user activities in a list
     * @param {Array} activities - Array of activity objects
     * @author Athanase Abayo
     */
    displayActivities(activities) {
        const activityContainer = document.getElementById('activityContainer');
        
        if (!activityContainer) {
            console.error('Activity container not found');
            return;
        }
        
        if (activities.length === 0) {
            activityContainer.innerHTML = `
                <div class="empty-state" style="padding: 2rem 1rem;">
                    <i class="fas fa-clock" style="font-size: 2rem; opacity: 0.5; margin-bottom: 0.5rem;"></i>
                    <p style="color: #9aa5c3;">No recent activity</p>
                </div>
            `;
            return;
        }
        
        activityContainer.innerHTML = activities.map(activity => {
            const icon = this.getActivityIcon(activity.action);
            const iconClass = this.getActivityIconClass(activity.action);
            const description = this.getActivityDescription(activity);
            const timeAgo = this.getTimeAgo(activity.created_at);
            
            return `
                <div class="activity-item">
                    <div class="activity-icon ${iconClass}">
                        <i class="${icon}"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">${description}</div>
                        <div class="activity-time">${timeAgo}</div>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    /**
     * Get icon for activity type
     * @param {string} action - Action type
     * @returns {string} FontAwesome icon class
     */
    getActivityIcon(action) {
        const icons = {
            'create_listing': 'fas fa-plus-circle',
            'add_to_cart': 'fas fa-shopping-cart',
            'add_to_wishlist': 'fas fa-heart',
            'create_borrow_request': 'fas fa-hand-holding',
            'update_profile': 'fas fa-user-edit',
            'login': 'fas fa-sign-in-alt'
        };
        return icons[action] || 'fas fa-circle';
    }
    
    /**
     * Get CSS class for activity icon styling
     * @param {string} action - Action type
     * @returns {string} CSS class name
     */
    getActivityIconClass(action) {
        const classes = {
            'create_listing': 'success',
            'add_to_cart': 'info',
            'add_to_wishlist': 'star',
            'create_borrow_request': 'success',
            'update_profile': 'info',
            'login': 'success'
        };
        return classes[action] || 'info';
    }
    
    /**
     * Get human-readable description for activity
     * @param {object} activity - Activity object
     * @returns {string} Description text
     */
    getActivityDescription(activity) {
        const details = activity.details || {};
        
        switch (activity.action) {
            case 'create_listing':
                return `Created a new listing: "${details.title || activity.entity_name || 'New item'}"`;
            case 'add_to_cart':
                return `Added "${activity.entity_name || 'an item'}" to your cart`;
            case 'add_to_wishlist':
                return `Saved "${activity.entity_name || 'an item'}" to your wishlist`;
            case 'create_borrow_request':
                return `Sent a request to borrow an item`;
            case 'update_profile':
                return 'Updated your profile information';
            case 'login':
                return 'Logged in to your account';
            default:
                return activity.action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
        }
    }
    
    /**
     * Convert timestamp to relative time
     * @param {string} timestamp - ISO timestamp
     * @returns {string} Relative time string
     */
    getTimeAgo(timestamp) {
        const now = new Date();
        const past = new Date(timestamp);
        const diffMs = now - past;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins} minute${diffMins > 1 ? 's' : ''} ago`;
        if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
        if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
        return past.toLocaleDateString();
    }

    /**
     * Load user stats (personalized metrics)
     * @author Victoria Ama Nyonato - Stats calculation and display
     * @author Athanase Abayo - Data aggregation
     */
    loadUserStats() {
    const pendingItems = JSON.parse(localStorage.getItem('swapit_pending_items') || '[]');
    const cart = JSON.parse(localStorage.getItem('swapit_cart') || '[]');
    const wishlist = JSON.parse(localStorage.getItem('swapit_wishlist') || '[]');
    
    document.getElementById('activeListings').textContent = pendingItems.length;
    document.getElementById('totalBorrows').textContent = cart.length;
    document.getElementById('itemsLent').textContent = wishlist.length;
    document.getElementById('userRating').textContent = '5.0';
}

    /**
     * Edit listing
     * @param {number} index - Index of listing to edit
     * @author Mabinty Mambu
     */
    editListing(index) {
    if (window.showToast) {
        window.showToast('Edit functionality coming soon!', 'info', 'Coming Soon');
    } else {
        alert('Edit functionality coming soon!');
    }
};

    /**
     * Delete listing
     * @param {number} index - Index of listing to delete
     * @author Mabinty Mambu - Deletion logic
     * @author Athanase Abayo - Data persistence
     */
    deleteListing(index) {
    if (confirm('Are you sure you want to delete this listing?')) {
        const items = JSON.parse(localStorage.getItem('swapit_pending_items') || '[]');
        const deletedItem = items[index];
        items.splice(index, 1);
        localStorage.setItem('swapit_pending_items', JSON.stringify(items));
        
        if (window.showToast) {
            window.showToast(deletedItem.title + ' deleted successfully', 'success', 'Listing Deleted');
        } else {
            alert('Listing deleted');
        }
        
        this.loadUserListings();
        this.loadUserStats();
    }
};

}

// Initialize global dashboard manager instance
const dashboardManager = new DashboardManager();

// Export for global access
window.editListing = (index) => dashboardManager.editListing(index);
window.deleteListing = (index) => dashboardManager.deleteListing(index);
