/**
 * Enhanced Real-Time Notifications System
 * Fixes loading issues and provides better error handling
 * @version 2.0
 */

class EnhancedNotifications {
    constructor() {
        this.notifications = [];
        this.unreadCount = 0;
        this.pollingInterval = null;
        this.lastCheckTime = new Date().toISOString();
        this.retryCount = 0;
        this.maxRetries = 3;
    }

    async init() {
        console.log('Initializing Enhanced Notifications...');
        await this.loadNotifications();
        this.startPolling();
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Mark all as read button
        const markAllBtn = document.getElementById('markAllRead');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', async () => {
                await this.markAllAsRead();
            });
        }

        // Notification bell click
        const notificationBell = document.querySelector('.notification-bell, #notificationBell');
        if (notificationBell) {
            notificationBell.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.toggleNotificationPanel();
            });
        }

        // Close notification panel when clicking outside
        document.addEventListener('click', (e) => {
            const panel = document.getElementById('notificationPanel');
            const bell = document.querySelector('.notification-bell, #notificationBell');
            
            if (panel && !panel.contains(e.target) && !bell?.contains(e.target)) {
                panel.classList.remove('show');
            }
        });
    }

    async loadNotifications() {
        try {
            const response = await fetch('../api/notifications.php?action=get_notifications&limit=50', {
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('Notifications loaded:', data);

            if (data.success) {
                this.notifications = data.notifications || [];
                this.unreadCount = data.unread_count || 0;
                this.renderNotifications();
                this.updateBadge();
                this.retryCount = 0; // Reset on success
            } else {
                throw new Error(data.error || 'Failed to load notifications');
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.handleLoadError(error);
        }
    }

    handleLoadError(error) {
        const container = document.getElementById('notificationsList');
        
        if (this.retryCount < this.maxRetries) {
            this.retryCount++;
            if (container) {
                container.innerHTML = `
                    <div style="padding: 20px; text-align: center; color: #f59e0b;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 8px;"></i>
                        <p style="margin: 0; font-size: 14px;">Connection issue. Retrying... (${this.retryCount}/${this.maxRetries})</p>
                    </div>
                `;
            }
            setTimeout(() => this.loadNotifications(), 2000);
        } else {
            if (container) {
                container.innerHTML = `
                    <div style="padding: 20px; text-align: center;">
                        <i class="fas fa-exclamation-circle" style="font-size: 32px; color: #ef4444; margin-bottom: 12px;"></i>
                        <p style="color: #ef4444; margin: 0 0 8px; font-weight: 500;">Unable to load notifications</p>
                        <p style="color: #94a3b8; margin: 0 0 16px; font-size: 13px;">${error.message}</p>
                        <button onclick="window.location.reload()" style="padding: 8px 16px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; font-size: 14px;">
                            <i class="fas fa-redo"></i> Refresh Page
                        </button>
                    </div>
                `;
            }
        }
    }

    renderNotifications() {
        const container = document.getElementById('notificationsList');
        if (!container) return;

        if (!this.notifications || this.notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-notifications" style="padding: 40px 20px; text-align: center;">
                    <i class="fas fa-bell-slash" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                    <p style="color: #94a3b8; margin: 0; font-size: 14px;">No notifications yet</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.notifications.map(notif => this.renderNotification(notif)).join('');
    }

    renderNotification(notif) {
        const isUnread = !notif.is_read;
        const iconClass = this.getNotificationIcon(notif.type);
        const actionUrl = notif.action_url || this.getDefaultActionUrl(notif);
        
        return `
            <div class="notification-item ${isUnread ? 'unread' : ''}" 
                 data-notification-id="${notif.id}"
                 onclick="enhancedNotifications.handleNotificationClick(${notif.id}, '${actionUrl}')">
                <div class="notification-icon ${notif.type}">
                    <i class="${iconClass}"></i>
                </div>
                <div class="notification-content">
                    <h4>${this.escapeHtml(notif.title)}</h4>
                    <p>${this.escapeHtml(notif.message)}</p>
                    <span class="notification-time">${this.formatTime(notif.created_at)}</span>
                </div>
                ${isUnread ? '<div class="unread-indicator"></div>' : ''}
            </div>
        `;
    }

    getNotificationIcon(type) {
        const icons = {
            'message': 'fas fa-comment',
            'request': 'fas fa-hand-holding',
            'request_approved': 'fas fa-check-circle',
            'request_rejected': 'fas fa-times-circle',
            'request_cancelled': 'fas fa-ban',
            'transaction': 'fas fa-exchange-alt',
            'return': 'fas fa-undo',
            'meeting': 'fas fa-calendar-check',
            'review': 'fas fa-star',
            'system': 'fas fa-bell'
        };
        return icons[type] || 'fas fa-bell';
    }

    getDefaultActionUrl(notif) {
        switch (notif.type) {
            case 'message':
                return `messages.html?conversation=${notif.related_id}`;
            case 'request':
            case 'request_approved':
            case 'request_rejected':
                return `requests.html?request=${notif.related_id}`;
            case 'transaction':
                return `transactions.html?id=${notif.related_id}`;
            case 'meeting':
                return `transactions.html?meeting=${notif.related_id}`;
            case 'review':
                return `profile.html#reviews`;
            default:
                return 'dashboard.html';
        }
    }

    async handleNotificationClick(notificationId, actionUrl) {
        console.log('Notification clicked:', { notificationId, actionUrl });
        
        // Mark as read
        await this.markAsRead(notificationId);
        
        // Navigate to action URL if provided
        if (actionUrl && actionUrl !== 'undefined' && actionUrl !== 'null') {
            // Check if URL is relative or absolute
            if (actionUrl.startsWith('http')) {
                window.location.href = actionUrl;
            } else {
                // Handle relative URLs
                window.location.href = actionUrl.startsWith('/') ? actionUrl : `../${actionUrl}`;
            }
        }
    }

    async markAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_as_read');
            formData.append('notification_id', notificationId);

            const response = await fetch('../api/notifications.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Update UI
                const notifElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notifElement) {
                    notifElement.classList.remove('unread');
                    const indicator = notifElement.querySelector('.unread-indicator');
                    if (indicator) indicator.remove();
                }
                
                // Update unread count
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateBadge();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_all_as_read');

            const response = await fetch('../api/notifications.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Update UI
                document.querySelectorAll('.notification-item').forEach(item => {
                    item.classList.remove('unread');
                    const indicator = item.querySelector('.unread-indicator');
                    if (indicator) indicator.remove();
                });
                
                // Update unread count
                this.unreadCount = 0;
                this.updateBadge();
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    }

    updateBadge() {
        const badges = document.querySelectorAll('.notification-badge, .notification-count, #notificationCount');
        badges.forEach(badge => {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'inline-block';
                badge.classList.add('has-notifications');
            } else {
                badge.style.display = 'none';
                badge.classList.remove('has-notifications');
            }
        });
    }

    async toggleNotificationPanel() {
        const panel = document.getElementById('notificationPanel');
        if (!panel) return;

        const isVisible = panel.classList.contains('show');
        
        if (!isVisible) {
            // Load fresh notifications when opening
            await this.loadNotifications();
            panel.classList.add('show');
        } else {
            panel.classList.remove('show');
        }
    }

    startPolling() {
        // Initial load
        this.loadNotifications();

        // Poll every 30 seconds
        this.pollingInterval = setInterval(async () => {
            await this.loadNotifications();
        }, 30000);

        // Also check for real-time updates every 10 seconds
        setInterval(async () => {
            await this.checkForUpdates();
        }, 10000);
    }

    async checkForUpdates() {
        try {
            const response = await fetch(
                `../api/notifications.php?action=get_realtime_updates&last_check=${this.lastCheckTime}`,
                { credentials: 'include' }
            );

            const data = await response.json();

            if (data.success && data.updates) {
                const updates = data.updates;
                
                // Show toast for new notifications
                if (updates.new_notifications > 0) {
                    this.showToast(`You have ${updates.new_notifications} new notification(s)`);
                    await this.loadNotifications();
                }
                
                // Update last check time
                this.lastCheckTime = data.timestamp;
            }
        } catch (error) {
            console.error('Error checking for updates:', error);
        }
    }

    showToast(message, type = 'info') {
        // Create toast element if it doesn't exist
        let toast = document.getElementById('notification-toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'notification-toast';
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #ffffff;
                padding: 16px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                min-width: 300px;
                opacity: 0;
                transform: translateY(-20px);
                transition: all 0.3s ease;
            `;
            document.body.appendChild(toast);
        }

        // Set content
        const iconClass = type === 'success' ? 'fa-check-circle' : 
                         type === 'error' ? 'fa-exclamation-circle' : 
                         'fa-info-circle';
        const color = type === 'success' ? '#10b981' : 
                     type === 'error' ? '#ef4444' : 
                     '#3b82f6';

        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fas ${iconClass}" style="color: ${color}; font-size: 20px;"></i>
                <span style="color: #374151; flex: 1;">${message}</span>
            </div>
        `;

        // Show toast
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
        }, 10);

        // Hide toast after 5 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-20px)';
        }, 5000);
    }

    formatTime(timestamp) {
        if (!timestamp) return '';

        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);

        if (seconds < 60) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days === 1) return 'Yesterday';
        if (days < 7) return `${days}d ago`;

        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    destroy() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
    }
}

// Initialize enhanced notifications
let enhancedNotifications;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', async () => {
        enhancedNotifications = new EnhancedNotifications();
        await enhancedNotifications.init();
    });
} else {
    enhancedNotifications = new EnhancedNotifications();
    enhancedNotifications.init();
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (enhancedNotifications) {
        enhancedNotifications.destroy();
    }
});
