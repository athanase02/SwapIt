/**
 * Online Status Manager
 * Real-time user presence tracking
 */

class OnlineStatusManager {
    constructor() {
        this.updateInterval = 30000; // Update every 30 seconds
        this.intervalId = null;
        this.onlineUsers = new Map();
    }
    
    /**
     * Start tracking current user's online status
     */
    start() {
        // Update immediately
        this.updateOwnStatus();
        
        // Set up recurring updates
        this.intervalId = setInterval(() => {
            this.updateOwnStatus();
        }, this.updateInterval);
        
        // Update before page unload
        window.addEventListener('beforeunload', () => {
            // Send beacon to ensure status update even if page is closing
            if (navigator.sendBeacon) {
                const formData = new FormData();
                formData.append('action', 'update');
                navigator.sendBeacon('/api/online-status.php?action=update', formData);
            }
        });
    }
    
    /**
     * Stop tracking
     */
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }
    
    /**
     * Update current user's status
     */
    async updateOwnStatus() {
        try {
            const response = await fetch('/api/online-status.php?action=update', {
                method: 'POST',
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error('Failed to update status');
            }
        } catch (error) {
            console.error('Error updating online status:', error);
        }
    }
    
    /**
     * Get list of online users
     */
    async getOnlineUsers() {
        try {
            const response = await fetch('/api/online-status.php?action=get_online_users', {
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch online users');
            }
            
            const data = await response.json();
            
            if (data.success) {
                this.onlineUsers.clear();
                data.online_users.forEach(user => {
                    this.onlineUsers.set(user.id, user);
                });
            }
            
            return data;
        } catch (error) {
            console.error('Error fetching online users:', error);
            return { success: false, error: error.message };
        }
    }
    
    /**
     * Check if specific user is online
     */
    async isUserOnline(userId) {
        try {
            const response = await fetch(`/api/online-status.php?action=check_user&user_id=${userId}`, {
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error('Failed to check user status');
            }
            
            const data = await response.json();
            return data.success ? data.is_online : false;
        } catch (error) {
            console.error('Error checking user status:', error);
            return false;
        }
    }
    
    /**
     * Check status for multiple users
     */
    async checkMultipleUsers(userIds) {
        try {
            const response = await fetch(`/api/online-status.php?action=check_multiple&user_ids=${userIds.join(',')}`, {
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error('Failed to check users status');
            }
            
            const data = await response.json();
            return data.success ? data.statuses : {};
        } catch (error) {
            console.error('Error checking users status:', error);
            return {};
        }
    }
}

// Create global instance
window.onlineStatusManager = new OnlineStatusManager();
