/**
 * SwapIt Transaction Management
 * Handles pickup/return confirmations and transaction tracking
 * @version 1.0
 */

class TransactionManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadActiveTransactions();
    }

    setupEventListeners() {
        // Confirm pickup button
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('confirm-pickup-btn')) {
                const requestId = e.target.dataset.requestId;
                this.confirmPickup(requestId);
            }
            
            if (e.target.classList.contains('confirm-return-btn')) {
                const requestId = e.target.dataset.requestId;
                this.showReturnConfirmation(requestId);
            }
            
            if (e.target.classList.contains('report-issue-btn')) {
                const requestId = e.target.dataset.requestId;
                this.showIssueReport(requestId);
            }
            
            if (e.target.classList.contains('view-history-btn')) {
                const requestId = e.target.dataset.requestId;
                this.viewTransactionHistory(requestId);
            }
        });
    }

    async loadActiveTransactions() {
        try {
            const response = await fetch('../api/transactions.php?action=get_my_transactions&status=active', {
                credentials: 'include'
            });
            const data = await response.json();

            if (data.success) {
                this.renderActiveTransactions(data.transactions);
            }
        } catch (error) {
            console.error('Error loading transactions:', error);
        }
    }

    renderActiveTransactions(transactions) {
        const container = document.getElementById('activeTransactionsContainer');
        if (!container) return;

        if (transactions.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-exchange-alt"></i>
                    <p>No active transactions</p>
                </div>
            `;
            return;
        }

        container.innerHTML = transactions.map(transaction => {
            const isLender = transaction.user_role === 'lender';
            const otherUser = isLender ? transaction.borrower_name : transaction.lender_name;
            const otherAvatar = isLender ? transaction.borrower_avatar : transaction.lender_avatar;
            
            return `
                <div class="transaction-card" data-request-id="${transaction.request_id}">
                    <div class="transaction-header">
                        <img src="${transaction.item_image || '../assets/images/default-item.png'}" 
                             alt="${transaction.item_title}" 
                             class="transaction-item-image">
                        <div class="transaction-info">
                            <h3>${transaction.item_title}</h3>
                            <p class="transaction-role">
                                You are the ${transaction.user_role}
                            </p>
                            <p class="transaction-other-user">
                                <img src="${otherAvatar || '../assets/images/default-avatar.png'}" 
                                     alt="${otherUser}" class="small-avatar">
                                ${otherUser}
                            </p>
                        </div>
                    </div>
                    
                    <div class="transaction-timeline">
                        <div class="timeline-item completed">
                            <i class="fas fa-calendar-check"></i>
                            <span>Meeting Scheduled</span>
                        </div>
                        <div class="timeline-item ${transaction.action_type === 'pickup_confirmed' ? 'completed' : 'pending'}">
                            <i class="fas fa-handshake"></i>
                            <span>Item Picked Up</span>
                        </div>
                        <div class="timeline-item pending">
                            <i class="fas fa-undo"></i>
                            <span>Item Returned</span>
                        </div>
                    </div>
                    
                    <div class="transaction-actions">
                        ${this.getTransactionActions(transaction)}
                    </div>
                    
                    <div class="transaction-meta">
                        <button class="btn-link view-history-btn" data-request-id="${transaction.request_id}">
                            <i class="fas fa-history"></i> View History
                        </button>
                        <button class="btn-link report-issue-btn" data-request-id="${transaction.request_id}">
                            <i class="fas fa-exclamation-circle"></i> Report Issue
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }

    getTransactionActions(transaction) {
        const actions = [];
        
        // If pickup not confirmed yet
        if (transaction.action_type !== 'pickup_confirmed') {
            actions.push(`
                <button class="btn btn-primary confirm-pickup-btn" data-request-id="${transaction.request_id}">
                    <i class="fas fa-check"></i> Confirm Pickup
                </button>
            `);
        } 
        // If pickup confirmed but not returned
        else if (transaction.action_type === 'pickup_confirmed') {
            actions.push(`
                <button class="btn btn-primary confirm-return-btn" data-request-id="${transaction.request_id}">
                    <i class="fas fa-undo"></i> Confirm Return
                </button>
            `);
        }
        
        return actions.length > 0 ? actions.join('') : '<p class="text-muted">Waiting for other party...</p>';
    }

    async confirmPickup(requestId) {
        if (!confirm('Confirm that you have picked up this item?')) {
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'confirm_pickup');
            formData.append('request_id', requestId);

            const response = await fetch('../api/transactions.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (window.realTimeNotifications) {
                    window.realTimeNotifications.showToast('Success', 'Pickup confirmed successfully', 'success');
                }
                this.loadActiveTransactions();
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error confirming pickup:', error);
            if (window.realTimeNotifications) {
                window.realTimeNotifications.showToast('Error', error.message || 'Failed to confirm pickup', 'error');
            }
        }
    }

    showReturnConfirmation(requestId) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Confirm Item Return</h2>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Please confirm the condition of the returned item:</p>
                    <div class="condition-options">
                        <label class="radio-option">
                            <input type="radio" name="condition" value="excellent" checked>
                            <span>Excellent - Like new condition</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="condition" value="good">
                            <span>Good - Normal wear and tear</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="condition" value="fair">
                            <span>Fair - Some damage or issues</span>
                        </label>
                        <label class="radio-option">
                            <input type="radio" name="condition" value="poor">
                            <span>Poor - Significant damage</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary modal-cancel">Cancel</button>
                    <button class="btn btn-primary modal-confirm">Confirm Return</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const closeModal = () => modal.remove();
        modal.querySelector('.modal-close').addEventListener('click', closeModal);
        modal.querySelector('.modal-cancel').addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        modal.querySelector('.modal-confirm').addEventListener('click', () => {
            const condition = modal.querySelector('input[name="condition"]:checked').value;
            this.confirmReturn(requestId, condition);
            closeModal();
        });
    }

    async confirmReturn(requestId, condition) {
        try {
            const formData = new FormData();
            formData.append('action', 'confirm_return');
            formData.append('request_id', requestId);
            formData.append('condition', condition);

            const response = await fetch('../api/transactions.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (window.realTimeNotifications) {
                    window.realTimeNotifications.showToast('Success', 'Return confirmed successfully', 'success');
                }
                this.loadActiveTransactions();
                
                // Show rating prompt
                setTimeout(() => {
                    this.showRatingPrompt(requestId);
                }, 1000);
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error confirming return:', error);
            if (window.realTimeNotifications) {
                window.realTimeNotifications.showToast('Error', error.message || 'Failed to confirm return', 'error');
            }
        }
    }

    showIssueReport(requestId) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Report an Issue</h2>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Issue Type</label>
                        <select id="issueType" class="form-control">
                            <option value="damaged">Item Damaged</option>
                            <option value="missing">Item Missing</option>
                            <option value="late">Late Return</option>
                            <option value="not_returned">Not Returned</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="issueDescription" class="form-control" rows="4" 
                                  placeholder="Please provide details about the issue..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary modal-cancel">Cancel</button>
                    <button class="btn btn-danger modal-submit">Submit Report</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const closeModal = () => modal.remove();
        modal.querySelector('.modal-close').addEventListener('click', closeModal);
        modal.querySelector('.modal-cancel').addEventListener('click', closeModal);
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });

        modal.querySelector('.modal-submit').addEventListener('click', () => {
            const issueType = modal.querySelector('#issueType').value;
            const description = modal.querySelector('#issueDescription').value;
            
            if (!description.trim()) {
                alert('Please provide a description of the issue');
                return;
            }
            
            this.reportIssue(requestId, issueType, description);
            closeModal();
        });
    }

    async reportIssue(requestId, issueType, description) {
        try {
            const formData = new FormData();
            formData.append('action', 'report_issue');
            formData.append('request_id', requestId);
            formData.append('issue_type', issueType);
            formData.append('description', description);

            const response = await fetch('../api/transactions.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                if (window.realTimeNotifications) {
                    window.realTimeNotifications.showToast('Success', 'Issue reported successfully', 'success');
                }
            } else {
                throw new Error(data.error);
            }
        } catch (error) {
            console.error('Error reporting issue:', error);
            if (window.realTimeNotifications) {
                window.realTimeNotifications.showToast('Error', error.message || 'Failed to report issue', 'error');
            }
        }
    }

    async viewTransactionHistory(requestId) {
        try {
            const response = await fetch(`../api/transactions.php?action=get_history&request_id=${requestId}`, {
                credentials: 'include'
            });
            const data = await response.json();

            if (data.success) {
                this.showHistoryModal(data.history);
            }
        } catch (error) {
            console.error('Error loading history:', error);
        }
    }

    showHistoryModal(history) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal-content modal-large">
                <div class="modal-header">
                    <h2>Transaction History</h2>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="history-timeline">
                        ${history.map(item => `
                            <div class="history-item">
                                <div class="history-icon">
                                    ${this.getHistoryIcon(item.action_type)}
                                </div>
                                <div class="history-content">
                                    <div class="history-action">${this.formatActionType(item.action_type)}</div>
                                    <div class="history-user">By ${item.performed_by_name}</div>
                                    <div class="history-notes">${item.notes}</div>
                                    <div class="history-time">${this.formatDateTime(item.created_at)}</div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary modal-close">Close</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        const closeModal = () => modal.remove();
        modal.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', closeModal);
        });
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal();
        });
    }

    getHistoryIcon(actionType) {
        const icons = {
            'pickup_confirmed': '<i class="fas fa-handshake"></i>',
            'return_confirmed': '<i class="fas fa-undo"></i>',
            'issue_reported': '<i class="fas fa-exclamation-triangle"></i>',
            'payment_made': '<i class="fas fa-dollar-sign"></i>',
            'rating_given': '<i class="fas fa-star"></i>'
        };
        return icons[actionType] || '<i class="fas fa-info-circle"></i>';
    }

    formatActionType(actionType) {
        return actionType.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }

    formatDateTime(timestamp) {
        const date = new Date(timestamp);
        return date.toLocaleString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    showRatingPrompt(requestId) {
        if (window.realTimeNotifications) {
            window.realTimeNotifications.showToast(
                'Rate Your Experience',
                'Please rate your transaction experience',
                'info'
            );
        }
        
        // Redirect to rating page or open rating modal
        setTimeout(() => {
            window.location.href = `/pages/requests.html?id=${requestId}&action=rate`;
        }, 2000);
    }
}

// Initialize transaction manager
let transactionManager;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        transactionManager = new TransactionManager();
    });
} else {
    transactionManager = new TransactionManager();
}
