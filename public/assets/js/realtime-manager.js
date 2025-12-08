/**
 * Real-Time Updates Manager
 * Coordinates all real-time features: messages, requests, notifications, online status
 */

class RealTimeManager {
    constructor() {
        this.updateInterval = 5000; // Poll every 5 seconds
        this.intervalId = null;
        this.lastMessageCheck = Date.now();
        this.lastRequestCheck = Date.now();
        this.lastNotificationCheck = Date.now();
        this.callbacks = {
            onNewMessage: [],
            onNewRequest: [],
            onNewNotification: [],
            onRequestUpdate: []
        };
    }
    
    /**
     * Start real-time updates
     */
    start() {
        console.log('Starting real-time updates...');
        
        // Start online status tracking
        if (window.onlineStatusManager) {
            window.onlineStatusManager.start();
        }
        
        // Initial load
        this.checkForUpdates();
        
        // Set up polling
        this.intervalId = setInterval(() => {
            this.checkForUpdates();
        }, this.updateInterval);
    }
    
    /**
     * Stop real-time updates
     */
    stop() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
        
        if (window.onlineStatusManager) {
            window.onlineStatusManager.stop();
        }
    }
    
    /**
     * Check for all types of updates
     */
    async checkForUpdates() {
        try {
            // Check messages, requests, and notifications in parallel
            await Promise.all([
                this.checkMessages(),
                this.checkRequests(),
                this.checkNotifications()
            ]);
        } catch (error) {
            console.error('Error checking for updates:', error);
        }
    }
    
    /**
     * Check for new messages
     */
    async checkMessages() {
        try {
            const response = await fetch('/api/messages.php?action=get_unread_count', {
                credentials: 'include'
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            
            if (data.success) {
                // Update message badge
                this.updateBadge('.messages-badge', data.unread_count);
                
                // Trigger callbacks if there are new messages
                if (data.unread_count > 0) {
                    this.callbacks.onNewMessage.forEach(cb => cb(data.unread_count));
                }
            }
        } catch (error) {
            console.error('Error checking messages:', error);
        }
    }
    
    /**
     * Check for new requests
     */
    async checkRequests() {
        try {
            const response = await fetch('/api/requests.php?action=get_my_requests&status=pending', {
                credentials: 'include'
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            
            if (data.success) {
                const pendingCount = data.requests.filter(r => 
                    r.request_type === 'received' && r.status === 'pending'
                ).length;
                
                // Update requests badge
                this.updateBadge('.requests-badge', pendingCount);
                
                // Trigger callbacks
                if (pendingCount > 0) {
                    this.callbacks.onNewRequest.forEach(cb => cb(pendingCount, data.requests));
                }
                
                // Check for request updates
                this.callbacks.onRequestUpdate.forEach(cb => cb(data.requests));
            }
        } catch (error) {
            console.error('Error checking requests:', error);
        }
    }
    
    /**
     * Check for new notifications
     */
    async checkNotifications() {
        try {
            const response = await fetch('/api/notifications.php?action=get_unread_count', {
                credentials: 'include'
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            
            if (data.success) {
                // Update notification badge
                this.updateBadge('.notification-badge', data.unread_count);
                
                // Trigger callbacks
                if (data.unread_count > 0) {
                    this.callbacks.onNewNotification.forEach(cb => cb(data.unread_count));
                }
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }
    
    /**
     * Update badge display
     */
    updateBadge(selector, count) {
        const badges = document.querySelectorAll(selector);
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        });
    }
    
    /**
     * Register callback for new messages
     */
    onNewMessage(callback) {
        this.callbacks.onNewMessage.push(callback);
    }
    
    /**
     * Register callback for new requests
     */
    onNewRequest(callback) {
        this.callbacks.onNewRequest.push(callback);
    }
    
    /**
     * Register callback for new notifications
     */
    onNewNotification(callback) {
        this.callbacks.onNewNotification.push(callback);
    }
    
    /**
     * Register callback for request updates
     */
    onRequestUpdate(callback) {
        this.callbacks.onRequestUpdate.push(callback);
    }
}

// Create global instance
window.realTimeManager = new RealTimeManager();

// Auto-start on authenticated pages
document.addEventListener('DOMContentLoaded', () => {
    // Check if user is authenticated by looking for auth elements
    const authElements = document.querySelector('.account-dropdown, #mainNavMenu');
    if (authElements) {
        window.realTimeManager.start();
    }
});
