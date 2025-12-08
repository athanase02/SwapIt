/**
 * SwapIt Real-Time Notification System
 * Handles live notifications, online status, and real-time updates
 * @version 1.0
 */

class RealTimeNotifications {
    constructor() {
        this.lastCheckTime = new Date().toISOString();
        this.pollingInterval = null;
        this.onlineStatusInterval = null;
        this.notifications = [];
        this.init();
    }

    init() {
        this.loadNotifications();
        this.startRealTimePolling();
        this.startOnlineStatusUpdates();
        this.setupEventListeners();
        this.setupBeforeUnload();
    }

    setupEventListeners() {
        // Notification bell click
        const notificationBell = document.getElementById('notificationBell');
        if (notificationBell) {
            notificationBell.addEventListener('click', () => this.toggleNotificationPanel());
        }

        // Mark all as read
        const markAllReadBtn = document.getElementById('markAllReadBtn');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
        }

        // Close notification panel when clicking outside
        document.addEventListener('click', (e) => {
            const panel = document.getElementById('notificationPanel');
            const bell = document.getElementById('notificationBell');
            if (panel && !panel.contains(e.target) && !bell.contains(e.target)) {
                panel.classList.remove('show');
            }
        });
    }

    async loadNotifications() {
        try {
            const response = await fetch('../api/notifications.php?action=get_notifications', {
                credentials: 'include'
            });
            const data = await response.json();

            if (data.success) {
                this.notifications = data.notifications || [];
                this.renderNotifications();
                this.updateNotificationBadge(data.unread_count);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    renderNotifications() {
        const container = document.getElementById('notificationsList');
        if (!container) return;

        if (this.notifications.length === 0) {
            container.innerHTML = `
                <div class="empty-notifications">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications yet</p>
                </div>
            `;
            return;
        }

        container.innerHTML = this.notifications.map(notif => `
            <div class="notification-item ${notif.is_read ? '' : 'unread'}" data-id="${notif.id}">
                <div class="notification-icon ${notif.type}">
                    ${this.getNotificationIcon(notif.type)}
                </div>
                <div class="notification-content" onclick="realTimeNotifications.handleNotificationClick(${notif.id}, '${notif.action_url || ''}')">
                    <div class="notification-title">${notif.title}</div>
                    <div class="notification-message">${notif.message}</div>
                    <div class="notification-time">${this.formatTime(notif.created_at)}</div>
                </div>
                ${!notif.is_read ? '<div class="notification-unread-dot"></div>' : ''}
            </div>
        `).join('');
    }

    getNotificationIcon(type) {
        const icons = {
            'new_message': '<i class="fas fa-comment"></i>',
            'borrow_request': '<i class="fas fa-hand-paper"></i>',
            'request_accepted': '<i class="fas fa-check-circle"></i>',
            'request_rejected': '<i class="fas fa-times-circle"></i>',
            'meeting_scheduled': '<i class="fas fa-calendar-check"></i>',
            'payment_received': '<i class="fas fa-dollar-sign"></i>',
            'item_returned': '<i class="fas fa-undo"></i>',
            'review_received': '<i class="fas fa-star"></i>',
            'reminder': '<i class="fas fa-bell"></i>'
        };
        return icons[type] || '<i class="fas fa-info-circle"></i>';
    }

    toggleNotificationPanel() {
        const panel = document.getElementById('notificationPanel');
        if (panel) {
            panel.classList.toggle('show');
        }
    }

    updateNotificationBadge(count) {
        const badges = document.querySelectorAll('.notification-badge, .notification-count');
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        });
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
                const notifElement = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                if (notifElement) {
                    notifElement.classList.remove('unread');
                    const dot = notifElement.querySelector('.notification-unread-dot');
                    if (dot) dot.remove();
                }
                await this.loadNotifications(); // Refresh to update count
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    async markAllAsRead() {
        try {
            const formData = new FormData();
            formData.append('action', 'mark_all_read');

            const response = await fetch('../api/notifications.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                await this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
        }
    }

    handleNotificationClick(notificationId, actionUrl) {
        this.markAsRead(notificationId);
        if (actionUrl) {
            window.location.href = actionUrl;
        }
    }

    async startRealTimePolling() {
        // Poll every 5 seconds for updates
        this.pollingInterval = setInterval(async () => {
            try {
                const response = await fetch(
                    `../api/notifications.php?action=get_realtime_updates&last_check=${encodeURIComponent(this.lastCheckTime)}`,
                    { credentials: 'include' }
                );
                const data = await response.json();

                if (data.success) {
                    const updates = data.updates;
                    
                    // Update badge counts
                    if (updates.new_messages > 0) {
                        this.showToast('New Message', `You have ${updates.new_messages} new message(s)`, 'info');
                        this.updateMessageBadge(updates.new_messages);
                    }
                    
                    if (updates.new_requests > 0) {
                        this.showToast('New Request', `You have ${updates.new_requests} new borrow request(s)`, 'info');
                        this.updateRequestBadge(updates.new_requests);
                    }
                    
                    if (updates.new_notifications > 0) {
                        await this.loadNotifications();
                    }
                    
                    // Handle status changes
                    if (updates.status_changes && updates.status_changes.length > 0) {
                        updates.status_changes.forEach(change => {
                            this.showToast(
                                'Status Update',
                                `${change.item_title} - Status: ${change.status}`,
                                'success'
                            );
                        });
                    }
                    
                    this.lastCheckTime = data.timestamp;
                }
            } catch (error) {
                console.error('Error polling for updates:', error);
            }
        }, 5000); // 5 seconds
    }

    async startOnlineStatusUpdates() {
        // Update online status every 30 seconds
        this.updateOnlineStatus('online');
        
        this.onlineStatusInterval = setInterval(() => {
            this.updateOnlineStatus('online');
        }, 30000); // 30 seconds
    }

    async updateOnlineStatus(status) {
        try {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('status', status);

            await fetch('../api/notifications.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
        } catch (error) {
            console.error('Error updating online status:', error);
        }
    }

    setupBeforeUnload() {
        window.addEventListener('beforeunload', () => {
            this.updateOnlineStatus('offline');
            this.cleanup();
        });

        // Also update on visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.updateOnlineStatus('away');
            } else {
                this.updateOnlineStatus('online');
            }
        });
    }

    updateMessageBadge(count) {
        const badges = document.querySelectorAll('.messages-badge');
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-block';
            }
        });
    }

    updateRequestBadge(count) {
        const badges = document.querySelectorAll('.requests-badge');
        badges.forEach(badge => {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'inline-block';
            }
        });
    }

    showToast(title, message, type = 'info') {
        const toastContainer = document.getElementById('toastContainer') || this.createToastContainer();
        
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-icon">${this.getToastIcon(type)}</div>
            <div class="toast-content">
                <div class="toast-title">${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        toastContainer.appendChild(toast);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }

    createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
        return container;
    }

    getToastIcon(type) {
        const icons = {
            'success': '<i class="fas fa-check-circle"></i>',
            'error': '<i class="fas fa-exclamation-circle"></i>',
            'warning': '<i class="fas fa-exclamation-triangle"></i>',
            'info': '<i class="fas fa-info-circle"></i>'
        };
        return icons[type] || icons['info'];
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
        if (days < 7) return `${days}d ago`;
        
        return date.toLocaleDateString();
    }

    cleanup() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
        if (this.onlineStatusInterval) {
            clearInterval(this.onlineStatusInterval);
        }
    }
}

// Initialize real-time notifications
let realTimeNotifications;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        realTimeNotifications = new RealTimeNotifications();
    });
} else {
    realTimeNotifications = new RealTimeNotifications();
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (realTimeNotifications) {
        realTimeNotifications.cleanup();
    }
});
