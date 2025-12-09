/**
 * Enhanced Messaging System with Better Error Handling
 * Interactive real-time messaging like workflow demo
 * @version 2.0
 */

class EnhancedMessagingSystem {
    constructor() {
        this.currentConversationId = null;
        this.currentReceiverId = null;
        this.currentReceiverName = null;
        this.pollingInterval = null;
        this.unreadCount = 0;
        this.lastMessageId = 0;
        this.retryCount = 0;
        this.maxRetries = 3;
    }

    async init() {
        console.log('Initializing Enhanced Messaging System...');
        this.setupEventListeners();
        await this.loadConversations();
        this.startUnreadCountPolling();
        
        // Check URL for conversation parameter
        const urlParams = new URLSearchParams(window.location.search);
        const conversationId = urlParams.get('conversation');
        const userId = urlParams.get('user');
        
        if (conversationId) {
            // If we have conversationId, find it in loaded conversations and open it
            setTimeout(() => {
                const convElement = document.querySelector(`.conversation-item[data-conversation-id="${conversationId}"]`);
                if (convElement) {
                    convElement.click();
                } else if (userId) {
                    // Fallback: open with conversation ID and user ID
                    this.openConversation(parseInt(conversationId), parseInt(userId));
                }
            }, 500);
        } else if (userId) {
            // Only user ID provided, start new conversation
            await this.openConversation(null, parseInt(userId));
        }
    }

    setupEventListeners() {
        // Message form submission
        const messageForm = document.getElementById('messageForm');
        if (messageForm) {
            messageForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.sendMessage();
            });
        }

        // Message input - Enter to send
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keydown', async (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    await this.sendMessage();
                }
            });
            
            // Auto-resize textarea
            messageInput.addEventListener('input', () => {
                messageInput.style.height = 'auto';
                messageInput.style.height = Math.min(messageInput.scrollHeight, 120) + 'px';
            });
        }

        // New conversation button
        const newConvBtn = document.getElementById('newConversationBtn');
        if (newConvBtn) {
            newConvBtn.addEventListener('click', () => {
                // Could implement user search modal here
                alert('User search feature coming soon! For now, start conversations from item listings.');
            });
        }

        // Conversation search
        const searchInput = document.getElementById('conversationSearch');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterConversations(e.target.value);
            });
        }
    }

    async loadConversations() {
        const container = document.getElementById('conversationsList');
        if (!container) return;

        try {
            container.innerHTML = '<div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading conversations...</div>';

            const response = await fetch('../api/messages.php?action=get_conversations', {
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('Conversations loaded:', data);

            if (data.success && data.conversations) {
                if (data.conversations.length === 0) {
                    this.showEmptyConversationsState(container);
                } else {
                    this.renderConversations(data.conversations);
                }
                this.retryCount = 0; // Reset retry count on success
            } else {
                throw new Error(data.error || 'Failed to load conversations');
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
            this.handleConversationsError(container, error);
        }
    }

    showEmptyConversationsState(container) {
        container.innerHTML = `
            <div class="empty-state" style="padding: 40px 20px; text-align: center;">
                <i class="fas fa-comments" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                <h3 style="color: #475569; margin: 0 0 8px; font-size: 18px;">No conversations yet</h3>
                <p style="color: #94a3b8; margin: 0 0 20px; font-size: 14px;">
                    Start browsing items to connect with other users
                </p>
                <a href="browse.html" class="btn-primary" style="display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 8px; font-size: 14px;">
                    <i class="fas fa-search"></i> Browse Items
                </a>
            </div>
        `;
    }

    handleConversationsError(container, error) {
        if (this.retryCount < this.maxRetries) {
            this.retryCount++;
            container.innerHTML = `
                <div class="error-state" style="padding: 20px; text-align: center; color: #f59e0b;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 32px; margin-bottom: 12px;"></i>
                    <p style="margin: 0 0 12px;">Connection issue. Retrying... (${this.retryCount}/${this.maxRetries})</p>
                </div>
            `;
            setTimeout(() => this.loadConversations(), 2000);
        } else {
            container.innerHTML = `
                <div class="error-state" style="padding: 20px; text-align: center;">
                    <i class="fas fa-exclamation-circle" style="font-size: 32px; color: #ef4444; margin-bottom: 12px;"></i>
                    <p style="color: #ef4444; margin: 0 0 8px; font-weight: 500;">Unable to load conversations</p>
                    <p style="color: #94a3b8; margin: 0 0 16px; font-size: 13px;">${error.message}</p>
                    <button onclick="window.location.reload()" class="btn-secondary" style="padding: 8px 16px; background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; border-radius: 6px; cursor: pointer; font-size: 14px;">
                        <i class="fas fa-redo"></i> Refresh Page
                    </button>
                </div>
            `;
        }
    }

    renderConversations(conversations) {
        const container = document.getElementById('conversationsList');
        if (!container) return;

        container.innerHTML = conversations.map(conv => {
            const hasUnread = conv.unread_count > 0;
            const avatarUrl = conv.other_user_avatar || '../assets/images/default-avatar.png';
            
            return `
                <div class="conversation-item ${hasUnread ? 'unread' : ''}" 
                     data-conversation-id="${conv.id}"
                     data-user-id="${conv.other_user_id}"
                     data-user-name="${this.escapeHtml(conv.other_user_name)}"
                     onclick="enhancedMessaging.openConversation(${conv.id}, ${conv.other_user_id}, '${this.escapeHtml(conv.other_user_name)}')">
                    <div class="conversation-avatar">
                        <img src="${avatarUrl}" 
                             alt="${conv.other_user_name}"
                             onerror="this.src='../assets/images/default-avatar.png'">
                        ${hasUnread ? `<span class="unread-badge">${conv.unread_count > 99 ? '99+' : conv.unread_count}</span>` : ''}
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-header">
                            <h4>${this.escapeHtml(conv.other_user_name)}</h4>
                            <span class="conversation-time">${this.formatTime(conv.last_message_time)}</span>
                        </div>
                        <p class="last-message ${hasUnread ? 'unread-text' : ''}">${this.truncate(conv.last_message || 'No messages yet', 50)}</p>
                    </div>
                </div>
            `;
        }).join('');
    }

    async openConversation(conversationId, userId, userName = '') {
        this.currentConversationId = conversationId;
        this.currentReceiverId = userId;
        this.currentReceiverName = userName;

        console.log('Opening conversation:', { conversationId, userId, userName });

        // Highlight selected conversation
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        const selectedItem = document.querySelector(`[data-conversation-id="${conversationId}"]`);
        if (selectedItem) {
            selectedItem.classList.add('active');
            // Get name from data attribute if not provided
            if (!userName) {
                this.currentReceiverName = selectedItem.dataset.userName || 'User';
            }
        }

        // Update chat header
        const chatUsername = document.getElementById('chatUsername');
        const chatAvatar = document.getElementById('chatAvatar');
        const chatAvatarInitial = document.getElementById('chatAvatarInitial');
        
        if (chatUsername) chatUsername.textContent = this.currentReceiverName;
        if (chatAvatarInitial) {
            chatAvatarInitial.textContent = (this.currentReceiverName || 'U')[0].toUpperCase();
        }

        // Hide "no conversation" message, show chat container
        const noConvSelected = document.getElementById('noConversationSelected');
        const chatContainer = document.getElementById('chatContainer');
        
        if (noConvSelected) noConvSelected.style.display = 'none';
        if (chatContainer) chatContainer.style.display = 'flex';

        // Load messages
        await this.loadMessages();

        // Mark messages as read
        await this.markAsRead();

        // Start polling for new messages
        this.startMessagePolling();

        // Focus message input
        const messageInput = document.getElementById('messageInput');
        if (messageInput) messageInput.focus();
    }

    async loadMessages() {
        if (!this.currentConversationId) return;

        const container = document.getElementById('messagesContainer');
        if (!container) return;

        try {
            const response = await fetch(
                `../api/messages.php?action=get_messages&conversation_id=${this.currentConversationId}&limit=100`,
                { credentials: 'include' }
            );

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            console.log('Messages loaded:', data);

            if (data.success) {
                this.renderMessages(data.messages || []);
            } else {
                console.error('Failed to load messages:', data.error);
                container.innerHTML = `
                    <div style="text-align: center; padding: 40px; color: #ef4444;">
                        <i class="fas fa-exclamation-circle" style="font-size: 32px; margin-bottom: 12px;"></i>
                        <p>${data.error || 'Failed to load messages'}</p>
                    </div>
                `;
            }
        } catch (error) {
            console.error('Error loading messages:', error);
            container.innerHTML = `
                <div style="text-align: center; padding: 40px; color: #f59e0b;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 32px; margin-bottom: 12px;"></i>
                    <p>Connection error. Retrying...</p>
                </div>
            `;
            // Retry after 2 seconds
            setTimeout(() => this.loadMessages(), 2000);
        }
    }

    renderMessages(messages) {
        const container = document.getElementById('messagesContainer');
        if (!container) return;

        if (!messages || messages.length === 0) {
            container.innerHTML = `
                <div class="no-messages" style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; text-align: center; color: #94a3b8;">
                    <i class="fas fa-comments" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                    <p style="font-size: 16px; margin: 0;">No messages yet</p>
                    <p style="font-size: 14px; margin: 8px 0 0;">Start the conversation!</p>
                </div>
            `;
            return;
        }

        // Store current scroll position
        const isAtBottom = container.scrollHeight - container.scrollTop === container.clientHeight;

        // Get current user ID from body or window
        const currentUserId = document.body.dataset.userId || window.currentUserId;

        container.innerHTML = messages.map(msg => {
            const isSent = msg.sender_id == currentUserId;
            const senderName = isSent ? 'You' : (msg.sender_name || this.currentReceiverName || 'User');
            
            return `
                <div class="message ${isSent ? 'sent' : 'received'}" data-message-id="${msg.id}">
                    <div class="message-wrapper">
                        <div class="message-sender-name">${this.escapeHtml(senderName)}</div>
                        <div class="message-bubble">
                            <div class="message-text">${this.escapeHtml(msg.message_text)}</div>
                            <div class="message-meta">
                                <span class="message-time">${this.formatTime(msg.created_at)}</span>
                                ${isSent && msg.is_read ? '<i class="fas fa-check-double" style="color: #ffffff; margin-left: 4px;" title="Read"></i>' : ''}
                                ${isSent && !msg.is_read ? '<i class="fas fa-check" style="color: #ffffff; margin-left: 4px; opacity: 0.7;" title="Sent"></i>' : ''}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Scroll to bottom if was already at bottom or on first load
        if (isAtBottom || this.lastMessageId === 0) {
            container.scrollTop = container.scrollHeight;
        }

        // Update last message ID
        if (messages.length > 0) {
            this.lastMessageId = Math.max(...messages.map(m => m.id));
        }
    }

    async sendMessage() {
        const input = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        
        if (!input || !this.currentReceiverId) return;

        const messageText = input.value.trim();
        if (!messageText) return;

        // Disable input and button while sending
        input.disabled = true;
        if (sendBtn) sendBtn.disabled = true;

        try {
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('receiver_id', this.currentReceiverId);
            formData.append('message', messageText);

            const response = await fetch('../api/messages.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const data = await response.json();
            console.log('Message sent:', data);

            if (data.success) {
                // Clear input
                input.value = '';
                input.style.height = 'auto';
                
                // Update conversation ID if new
                if (data.conversation_id && !this.currentConversationId) {
                    this.currentConversationId = data.conversation_id;
                }
                
                // Reload messages immediately for instant feedback
                await this.loadMessages();
                
                // Refresh conversations list to update last message
                await this.loadConversations();
                
                // Reselect current conversation
                const selectedItem = document.querySelector(`[data-conversation-id="${this.currentConversationId}"]`);
                if (selectedItem) selectedItem.classList.add('active');
            } else {
                throw new Error(data.error || 'Failed to send message');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            alert(`Failed to send message: ${error.message}\n\nPlease check your connection and try again.`);
        } finally {
            // Re-enable input and button
            input.disabled = false;
            if (sendBtn) sendBtn.disabled = false;
            input.focus();
        }
    }

    async markAsRead() {
        if (!this.currentConversationId) return;

        try {
            const formData = new FormData();
            formData.append('action', 'mark_as_read');
            formData.append('conversation_id', this.currentConversationId);

            await fetch('../api/messages.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            // Update unread count
            await this.getUnreadCount();
        } catch (error) {
            console.error('Error marking messages as read:', error);
        }
    }

    startMessagePolling() {
        // Clear existing interval
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }

        // Poll every 3 seconds
        this.pollingInterval = setInterval(async () => {
            if (this.currentConversationId) {
                await this.loadMessages();
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
                this.unreadCount = data.unread_count || 0;
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
        setInterval(() => this.getUnreadCount(), 10000);
    }

    filterConversations(searchTerm) {
        const items = document.querySelectorAll('.conversation-item');
        const term = searchTerm.toLowerCase();

        items.forEach(item => {
            const name = item.querySelector('h4').textContent.toLowerCase();
            const message = item.querySelector('.last-message').textContent.toLowerCase();
            
            if (name.includes(term) || message.includes(term)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
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

    truncate(text, maxLength) {
        if (!text || text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    }

    destroy() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
    }
}

// Initialize enhanced messaging system
let enhancedMessaging;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', async () => {
        enhancedMessaging = new EnhancedMessagingSystem();
        await enhancedMessaging.init();
    });
} else {
    enhancedMessaging = new EnhancedMessagingSystem();
    enhancedMessaging.init();
}

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (enhancedMessaging) {
        enhancedMessaging.destroy();
    }
});
