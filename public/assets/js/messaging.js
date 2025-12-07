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
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadConversations();
        this.startUnreadCountPolling();
    }

    setupEventListeners() {
        // Send message button
        const sendBtn = document.getElementById('send-message-btn');
        if (sendBtn) {
            sendBtn.addEventListener('click', () => this.sendMessage());
        }

        // Message input - send on Enter
        const messageInput = document.getElementById('message-input');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
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
        try {
            const response = await fetch('/api/messages.php?action=get_conversations', {
                credentials: 'include'
            });

            const data = await response.json();

            if (data.success) {
                this.renderConversations(data.conversations);
            } else {
                console.error('Failed to load conversations:', data.error);
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }

    renderConversations(conversations) {
        const container = document.getElementById('conversations-list');
        if (!container) return;

        if (conversations.length === 0) {
            container.innerHTML = `
                <div class="no-conversations">
                    <i class="fas fa-comments"></i>
                    <p>No conversations yet</p>
                    <small>Start browsing items to connect with other users</small>
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
                `/api/messages.php?action=get_messages&conversation_id=${this.currentConversationId}`,
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
                <div class="no-messages">
                    <p>No messages yet. Start the conversation!</p>
                </div>
            `;
            return;
        }

        // Get current user ID from session or data attribute
        const currentUserId = document.body.dataset.userId || window.currentUserId;

        container.innerHTML = messages.map(msg => `
            <div class="message ${msg.sender_id == currentUserId ? 'sent' : 'received'}">
                <div class="message-avatar">
                    <img src="${msg.sender_avatar || '/public/assets/images/default-avatar.png'}" 
                         alt="${msg.sender_name}">
                </div>
                <div class="message-content">
                    <div class="message-header">
                        <span class="message-sender">${msg.sender_name}</span>
                        <span class="message-time">${this.formatTime(msg.created_at)}</span>
                    </div>
                    <div class="message-text">${this.escapeHtml(msg.message_text)}</div>
                    ${msg.is_read ? '<span class="read-indicator"><i class="fas fa-check-double"></i></span>' : ''}
                </div>
            </div>
        `).join('');

        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    async sendMessage() {
        const input = document.getElementById('message-input');
        if (!input) return;

        const messageText = input.value.trim();
        if (!messageText || !this.currentReceiverId) return;

        try {
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', this.currentReceiverId);
            formData.append('message', messageText);

            const response = await fetch('/api/messages.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                input.value = '';
                this.currentConversationId = data.conversation_id;
                await this.loadMessages();
                await this.loadConversations(); // Refresh conversation list
            } else {
                alert('Failed to send message: ' + data.error);
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert('Failed to send message. Please try again.');
        }
    }

    async startConversation(userId, itemId = null) {
        try {
            const formData = new FormData();
            formData.append('action', 'start_conversation');
            formData.append('user_id', userId);
            if (itemId) formData.append('item_id', itemId);

            const response = await fetch('/api/messages.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to messages page or open conversation
                if (window.location.pathname.includes('messages')) {
                    await this.openConversation(data.conversation_id, userId);
                } else {
                    window.location.href = `/public/pages/messages.html?conversation=${data.conversation_id}`;
                }
            } else {
                alert('Failed to start conversation: ' + data.error);
            }
        } catch (error) {
            console.error('Error starting conversation:', error);
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
            const response = await fetch('/api/messages.php?action=get_unread_count', {
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

    destroy() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
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
