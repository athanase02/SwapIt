/**
 * SwapIt Messaging System
 * Real-time messaging functionality
 * @version 1.0
 */

class MessagingSystem {
    constructor() {
        this.currentConversationId = null;
        this.currentReceiverId = null;
        this.pollingInterval = null;
        this.unreadCount = 0;
        this.typingTimeout = null;
        this.isTyping = false;
        this.otherUserTyping = false;
        this.onlineUsers = new Set();
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadConversations();
        this.startUnreadCountPolling();
        this.loadOnlineUsers();
        
        // Poll online users every 30 seconds
        setInterval(() => this.loadOnlineUsers(), 30000);
    }

    setupEventListeners() {
        // Message form submission
        const messageForm = document.getElementById('messageForm');
        if (messageForm) {
            messageForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
        }

        // Send message button
        const sendBtn = document.getElementById('sendBtn');
        if (sendBtn) {
            sendBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.sendMessage();
            });
        }

        // Message input - send on Enter
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                } else {
                    this.handleTyping();
                }
            });
            
            // Detect typing
            messageInput.addEventListener('input', () => {
                this.handleTyping();
            });
        }

        // Start conversation buttons (on browse/profile pages)
        document.querySelectorAll('.start-conversation-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const userId = e.target.dataset.userId;
                const itemId = e.target.dataset.itemId;
                this.startConversation(userId, itemId);
            });
        });
    }

    async loadConversations() {
        const container = document.getElementById('conversationsList');
        if (!container) return;

        try {
            // Show loading state
            container.innerHTML = '<div class="loading">Loading conversations...</div>';

            const response = await fetch('../api/messages.php?action=get_conversations', {
                credentials: 'include'
            });

            const data = await response.json();

            if (data.success) {
                this.renderConversations(data.conversations || []);
            } else {
                // Show error or empty state
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-comments" style="font-size: 48px; color: #6b7280; margin-bottom: 16px;"></i>
                        <p style="color: #6b7280; margin: 0;">No conversations yet</p>
                        <small style="color: #9ca3af;">Start browsing items to connect with other users</small>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444; margin-bottom: 16px;"></i>
                    <p style="color: #ef4444; margin: 0;">Error loading conversations</p>
                    <small style="color: #9ca3af;">Please try refreshing the page</small>
                </div>
            `;
        }
    }

    renderConversations(conversations) {
        const container = document.getElementById('conversationsList');
        if (!container) return;

        if (!conversations || conversations.length === 0) {
            container.innerHTML = `
                <div class="empty-state" style="padding: 40px 20px; text-align: center;">
                    <i class="fas fa-comments" style="font-size: 48px; color: #6b7280; margin-bottom: 16px;"></i>
                    <h3 style="color: #374151; margin: 0 0 8px;">No conversations yet</h3>
                    <p style="color: #6b7280; margin: 0; font-size: 14px;">Start browsing items to connect with other users</p>
                    <a href="browse.html" class="btn btn-primary" style="margin-top: 20px; display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px;">
                        <i class="fas fa-search"></i> Browse Items
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = conversations.map(conv => `
            <div class="conversation-item ${conv.unread_count > 0 ? 'unread' : ''}" 
                 data-conversation-id="${conv.id}"
                 data-user-id="${conv.other_user_id}"
                 onclick="messagingSystem.openConversation(${conv.id}, ${conv.other_user_id})">
                <div class="conversation-avatar">
                    <img src="${conv.other_user_avatar || '/public/assets/images/default-avatar.png'}" 
                         alt="${conv.other_user_name}">
                    ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                </div>
                <div class="conversation-info">
                    <div class="conversation-header">
                        <h4>${conv.other_user_name}</h4>
                        <span class="conversation-time">${this.formatTime(conv.last_message_time)}</span>
                    </div>
                    <p class="last-message">${conv.last_message || 'No messages yet'}</p>
                </div>
            </div>
        `).join('');
    }

    async openConversation(conversationId, userId) {
        this.currentConversationId = conversationId;
        this.currentReceiverId = userId;

        // Highlight selected conversation
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-conversation-id="${conversationId}"]`)?.classList.add('active');

        // Load messages
        await this.loadMessages();

        // Start polling for new messages
        this.startMessagePolling();

        // Show message panel
        document.getElementById('message-panel')?.classList.add('active');
    }

    async loadMessages() {
        if (!this.currentConversationId) return;

        try {
            const response = await fetch(
                `../api/messages.php?action=get_messages&conversation_id=${this.currentConversationId}`,
                { credentials: 'include' }
            );

            const data = await response.json();

            if (data.success) {
                this.renderMessages(data.messages);
            } else {
                console.error('Failed to load messages:', data.error);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }

    renderMessages(messages) {
        const container = document.getElementById('messages-container');
        if (!container) return;

        if (messages.length === 0) {
            container.innerHTML = `
                <div class="no-messages" style="text-align: center; padding: 40px; color: #6b7280;">
                    <p style="font-size: 16px;">No messages yet. Start the conversation!</p>
                </div>
            `;
            return;
        }

        // Get current user ID from session or data attribute
        const currentUserId = document.body.dataset.userId || window.currentUserId;

        container.innerHTML = messages.map(msg => `
            <div class="message ${msg.sender_id == currentUserId ? 'sent' : 'received'}">
                <div class="message-bubble">
                    <div class="message-text">${this.escapeHtml(msg.message_text)}</div>
                    <div class="message-time">${this.formatTime(msg.created_at)}</div>
                </div>
            </div>
        `).join('');

        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    async sendMessage() {
        const input = document.getElementById('messageInput');
        if (!input) return;

        const messageText = input.value.trim();
        if (!messageText || !this.currentReceiverId) return;

        try {
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', this.currentReceiverId);
            formData.append('message', messageText);
            if (this.currentConversationId) {
                formData.append('conversation_id', this.currentConversationId);
            }

            const response = await fetch('../api/messages.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                input.value = '';
                this.currentConversationId = data.conversation_id;
                // Immediately add the message to UI for instant feedback
                await this.loadMessages();
                await this.loadConversations(); // Refresh conversation list
            } else {
                console.error('Send message failed:', data.error);
                alert('Failed to send message: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('Failed to send message: ' + error.message);
        }
    }

    async startConversation(userId, itemId = null) {
        try {
            const formData = new FormData();
            formData.append('action', 'start_conversation');
            formData.append('user_id', userId);
            if (itemId) formData.append('item_id', itemId);

            const response = await fetch('../api/messages.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                // Redirect to messages page or open conversation
                if (window.location.pathname.includes('messages')) {
                    await this.openConversation(data.conversation_id, userId);
                } else {
                    window.location.href = `pages/messages.html?conversation=${data.conversation_id}`;
                }
            } else {
                console.error('Start conversation failed:', data.error);
                alert('Failed to start conversation: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error starting conversation:', error);
            alert('Failed to start conversation: ' + error.message);
        }
    }

    startMessagePolling() {
        // Clear existing interval
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }

        // Poll every 3 seconds
        this.pollingInterval = setInterval(() => {
            if (this.currentConversationId) {
                this.loadMessages();
            }
        }, 3000);
    }

    async getUnreadCount() {
        try {
            const response = await fetch('../api/messages.php?action=get_unread_count', {
                credentials: 'include'
            });

            const data = await response.json();

            if (data.success) {
                this.unreadCount = data.unread_count;
                this.updateUnreadBadge();
            }
        } catch (error) {
            console.error('Error getting unread count:', error);
        }
    }

    updateUnreadBadge() {
        const badges = document.querySelectorAll('.messages-badge, .unread-messages-count');
        badges.forEach(badge => {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        });
    }

    startUnreadCountPolling() {
        // Initial load
        this.getUnreadCount();

        // Poll every 10 seconds
        setInterval(() => {
            this.getUnreadCount();
        }, 10000);
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

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Typing indicator methods
    handleTyping() {
        if (!this.isTyping) {
            this.isTyping = true;
            this.sendTypingStatus(true);
        }

        clearTimeout(this.typingTimeout);
        this.typingTimeout = setTimeout(() => {
            this.isTyping = false;
            this.sendTypingStatus(false);
        }, 1000);
    }

    async sendTypingStatus(isTyping) {
        if (!this.currentConversationId) return;

        try {
            const formData = new FormData();
            formData.append('action', 'typing_status');
            formData.append('conversation_id', this.currentConversationId);
            formData.append('is_typing', isTyping ? '1' : '0');

            await fetch('../api/messages.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
        } catch (error) {
            console.error('Error sending typing status:', error);
        }
    }

    showTypingIndicator() {
        const messagesContainer = document.getElementById('messagesContainer');
        if (!messagesContainer) return;

        const existingIndicator = document.querySelector('.typing-indicator-wrapper');
        if (existingIndicator) return;

        const typingHtml = `
            <div class="message received typing-indicator-wrapper">
                <div class="typing-indicator">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        messagesContainer.insertAdjacentHTML('beforeend', typingHtml);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }

    hideTypingIndicator() {
        const indicator = document.querySelector('.typing-indicator-wrapper');
        if (indicator) {
            indicator.remove();
        }
    }

    // Online status methods
    async loadOnlineUsers() {
        try {
            const response = await fetch('../api/notifications.php?action=get_online_users', {
                credentials: 'include'
            });
            const data = await response.json();

            if (data.success) {
                this.onlineUsers = new Set(data.online_users.map(u => u.user_id));
                this.updateOnlineStatusIndicators();
            }
        } catch (error) {
            console.error('Error loading online users:', error);
        }
    }

    updateOnlineStatusIndicators() {
        document.querySelectorAll('[data-user-id]').forEach(elem => {
            const userId = parseInt(elem.dataset.userId);
            const statusIndicator = elem.querySelector('.online-status');
            
            if (statusIndicator) {
                if (this.onlineUsers.has(userId)) {
                    statusIndicator.className = 'online-status online';
                } else {
                    statusIndicator.className = 'online-status offline';
                }
            }
        });
    }

    destroy() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }
    }
}

// Initialize messaging system
let messagingSystem;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        messagingSystem = new MessagingSystem();
    });
} else {
    messagingSystem = new MessagingSystem();
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (messagingSystem) {
        messagingSystem.destroy();
    }
});
