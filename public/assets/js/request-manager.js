/**
 * SwapIt Requests Management System
 * Handles borrow requests, approvals, rejections, and meeting scheduling
 * @version 1.0
 */

class RequestManager {
    constructor() {
        this.currentRequestId = null;
        this.autoRefreshInterval = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadRequests();
        this.startRealTimeUpdates();
    }
    
    /**
     * Start real-time updates for requests
     */
    startRealTimeUpdates() {
        // Register callback with real-time manager
        if (window.realTimeManager) {
            window.realTimeManager.onRequestUpdate((requests) => {
                // Refresh the display when requests are updated
                this.refreshRequestsDisplay(requests);
            });
        }
        
        // Also refresh every 10 seconds
        this.autoRefreshInterval = setInterval(() => {
            this.loadRequests();
        }, 10000);
    }
    
    /**
     * Stop real-time updates (when leaving page)
     */
    stopRealTimeUpdates() {
        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
        }
    }
    
    /**
     * Refresh requests display without full reload
     */
    refreshRequestsDisplay(requests) {
        if (!requests || requests.length === 0) return;
        
        // Update badges for pending counts
        const pendingReceived = requests.filter(r => 
            r.request_type === 'received' && r.status === 'pending'
        ).length;
        
        const pendingSent = requests.filter(r => 
            r.request_type === 'sent' && r.status === 'pending'
        ).length;
        
        // Update badges if they exist
        const receivedBadge = document.querySelector('[data-tab="received"] .badge');
        if (receivedBadge) receivedBadge.textContent = pendingReceived;
        
        const sentBadge = document.querySelector('[data-tab="sent"] .badge');
        if (sentBadge) sentBadge.textContent = pendingSent;
    }

    setupEventListeners() {
        // Create request button
        const createRequestBtn = document.getElementById('create-request-btn');
        if (createRequestBtn) {
            createRequestBtn.addEventListener('click', () => this.showRequestModal());
        }

        // Request form submission
        const requestForm = document.getElementById('request-form');
        if (requestForm) {
            requestForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitRequest();
            });
        }

        // Accept/Reject buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('accept-request-btn')) {
                const requestId = e.target.dataset.requestId;
                this.acceptRequest(requestId);
            }
            if (e.target.classList.contains('reject-request-btn')) {
                const requestId = e.target.dataset.requestId;
                this.rejectRequest(requestId);
            }
            if (e.target.classList.contains('schedule-meeting-btn')) {
                const requestId = e.target.dataset.requestId;
                this.showScheduleMeetingModal(requestId);
            }
            if (e.target.classList.contains('view-request-btn')) {
                const requestId = e.target.dataset.requestId;
                this.viewRequestDetails(requestId);
            }
        });

        // Schedule meeting form
        const scheduleMeetingForm = document.getElementById('schedule-meeting-form');
        if (scheduleMeetingForm) {
            scheduleMeetingForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.scheduleMeeting();
            });
        }

        // Meeting type toggle
        const meetingTypeSelect = document.getElementById('meeting-type');
        if (meetingTypeSelect) {
            meetingTypeSelect.addEventListener('change', (e) => {
                this.toggleMeetingFields(e.target.value);
            });
        }

        // Filter buttons
        document.querySelectorAll('.request-filter-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const role = e.target.dataset.role;
                const status = e.target.dataset.status;
                this.filterRequests(role, status);
            });
        });
    }

    async loadRequests(role = 'all', status = 'all') {
        const sentList = document.getElementById('sentRequestsList');
        const receivedList = document.getElementById('receivedRequestsList');
        const activeList = document.getElementById('activeRequestsList');
        const completedList = document.getElementById('completedRequestsList');

        // Show loading in all tabs
        [sentList, receivedList, activeList, completedList].forEach(list => {
            if (list) list.innerHTML = '<div class="loading">Loading requests...</div>';
        });

        try {
            const response = await fetch(
                `../api/requests.php?action=get_my_requests`,
                { credentials: 'include' }
            );

            const data = await response.json();

            if (data.success) {
                const requests = data.requests || [];
                this.renderRequestsByTab(requests);
                this.updateTabCounts(requests);
            } else {
                // Show empty state
                this.renderEmptyState();
            }
        } catch (error) {
            console.error('Error loading requests:', error);
            this.renderErrorState();
        }
    }

    renderRequestsByTab(requests) {
        const sent = requests.filter(r => r.request_type === 'sent');
        const received = requests.filter(r => r.request_type === 'received');
        const active = requests.filter(r => r.status === 'active');
        const completed = requests.filter(r => r.status === 'completed');

        this.renderRequestList('sentRequestsList', sent, 'sent');
        this.renderRequestList('receivedRequestsList', received, 'received');
        this.renderRequestList('activeRequestsList', active, 'active');
        this.renderRequestList('completedRequestsList', completed, 'completed');
    }

    updateTabCounts(requests) {
        const counts = {
            sent: requests.filter(r => r.request_type === 'sent').length,
            received: requests.filter(r => r.request_type === 'received').length,
            active: requests.filter(r => r.status === 'active').length,
            completed: requests.filter(r => r.status === 'completed').length
        };

        document.getElementById('sentCount').textContent = counts.sent;
        document.getElementById('receivedCount').textContent = counts.received;
        document.getElementById('activeCount').textContent = counts.active;
        document.getElementById('completedCount').textContent = counts.completed;
    }

    renderRequestList(containerId, requests, type) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (requests.length === 0) {
            const messages = {
                sent: 'You haven\'t sent any requests yet',
                received: 'No one has requested to borrow from you yet',
                active: 'No active borrows at the moment',
                completed: 'No completed transactions yet'
            };

            container.innerHTML = `
                <div class="empty-state" style="padding: 60px 20px; text-align: center;">
                    <i class="fas fa-inbox" style="font-size: 48px; color: #6b7280; margin-bottom: 16px;"></i>
                    <h3 style="color: #374151; margin: 0 0 8px;">${messages[type]}</h3>
                    <p style="color: #6b7280; margin: 0; font-size: 14px;">Start browsing items to make requests</p>
                    <a href="browse.html" class="btn btn-primary" style="margin-top: 20px; display: inline-block; padding: 10px 20px; background: #3b82f6; color: white; text-decoration: none; border-radius: 6px;">
                        <i class="fas fa-search"></i> Browse Items
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = requests.map(req => this.renderRequestCard(req)).join('');
    }

    renderEmptyState() {
        [document.getElementById('sentRequestsList'), 
         document.getElementById('receivedRequestsList'),
         document.getElementById('activeRequestsList'),
         document.getElementById('completedRequestsList')].forEach(list => {
            if (list) {
                list.innerHTML = `
                    <div class="empty-state" style="padding: 60px 20px; text-align: center;">
                        <i class="fas fa-inbox" style="font-size: 48px; color: #6b7280; margin-bottom: 16px;"></i>
                        <h3 style="color: #374151; margin: 0 0 8px;">No requests yet</h3>
                        <p style="color: #6b7280; margin: 0; font-size: 14px;">Your requests will appear here</p>
                    </div>
                `;
            }
        });
    }

    renderErrorState() {
        [document.getElementById('sentRequestsList'), 
         document.getElementById('receivedRequestsList'),
         document.getElementById('activeRequestsList'),
         document.getElementById('completedRequestsList')].forEach(list => {
            if (list) {
                list.innerHTML = `
                    <div class="empty-state" style="padding: 60px 20px; text-align: center;">
                        <i class="fas fa-exclamation-circle" style="font-size: 48px; color: #ef4444; margin-bottom: 16px;"></i>
                        <h3 style="color: #ef4444; margin: 0 0 8px;">Error loading requests</h3>
                        <p style="color: #6b7280; margin: 0; font-size: 14px;">Please try refreshing the page</p>
                    </div>
                `;
            }
        });
    }

    renderRequestCard(req) {
        const statusBadgeClass = {
            'pending': 'status-badge pending',
            'accepted': 'status-badge accepted',
            'rejected': 'status-badge rejected',
            'active': 'status-badge active',
            'completed': 'status-badge completed',
            'cancelled': 'status-badge cancelled'
        };

        return `
            <div class="request-card">
                <div class="request-header">
                    <div class="request-item-info">
                        <img src="${req.item_image || '../assets/images/placeholder.jpg'}" 
                             alt="${req.item_title}" class="item-image">
                        <div class="item-details">
                            <h3>${req.item_title}</h3>
                            <p><i class="fas fa-user"></i> ${req.other_user_name}</p>
                            <p><i class="fas fa-map-marker-alt"></i> ${req.pickup_location || 'Location not specified'}</p>
                        </div>
                    </div>
                    <span class="${statusBadgeClass[req.status]}">${this.getStatusText(req.status)}</span>
                </div>

                <div class="request-body">
                    <div class="request-dates">
                        <div class="date-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>${this.formatDate(req.borrow_start_date)} - ${this.formatDate(req.borrow_end_date)}</span>
                        </div>
                    </div>
                    <div class="request-price">$${parseFloat(req.total_price).toFixed(2)}</div>
                    ${req.borrower_message ? `
                        <div class="request-message">
                            <strong>Message:</strong>
                            ${req.borrower_message}
                        </div>
                    ` : ''}
                </div>

                <div class="request-footer">
                    <div class="user-info">
                        <div class="user-avatar">${req.other_user_name.charAt(0).toUpperCase()}</div>
                        <span class="user-name">${req.other_user_name}</span>
                    </div>
                    <div class="request-actions">
                        ${this.renderRequestActions(req)}
                    </div>
                </div>
            </div>
        `;
    }

    renderRequestActions(req) {
        const buttons = [];

        // View details button
        buttons.push(`
            <button class="btn btn-outline" onclick="requestManager.viewRequest(${req.id})">
                <i class="fas fa-eye"></i> View
            </button>
        `);

        // Actions based on request type and status
        if (req.request_type === 'received' && req.status === 'pending') {
            buttons.push(`
                <button class="btn btn-success" onclick="requestManager.acceptRequest(${req.id})">
                    <i class="fas fa-check"></i> Accept
                </button>
                <button class="btn btn-danger" onclick="requestManager.rejectRequest(${req.id})">
                    <i class="fas fa-times"></i> Reject
                </button>
            `);
        }

        // Schedule meeting for accepted requests
        if (req.status === 'accepted' || req.status === 'active') {
            buttons.push(`
                <button class="btn btn-primary" onclick="requestManager.scheduleMeeting(${req.id})">
                    <i class="fas fa-calendar-plus"></i> Meeting
                </button>
            `);
        }

        return buttons.join('');
    }

    getStatusText(status) {
        const texts = {
            'pending': 'Pending',
            'accepted': 'Accepted',
            'rejected': 'Rejected',
            'active': 'Active',
            'completed': 'Completed',
            'cancelled': 'Cancelled'
        };
        return texts[status] || status;
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    async submitRequest() {
        const form = document.getElementById('request-form');
        const formData = new FormData(form);
        formData.append('action', 'create_request');

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        try {
            const response = await fetch('../api/requests.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Request sent successfully!', 'success');
                this.closeModal('request-modal');
                form.reset();
                await this.loadRequests();
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            console.error('Error submitting request:', error);
            this.showNotification('Failed to send request. Please try again.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send Request';
        }
    }

    async acceptRequest(requestId) {
        const notes = prompt('Add a note for the borrower (optional):');
        if (notes === null) return; // User cancelled

        const formData = new FormData();
        formData.append('action', 'accept_request');
        formData.append('request_id', requestId);
        if (notes) formData.append('notes', notes);

        try {
            const response = await fetch('../api/requests.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Request accepted!', 'success');
                await this.loadRequests();
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            console.error('Error accepting request:', error);
            this.showNotification('Failed to accept request.', 'error');
        }
    }

    async rejectRequest(requestId) {
        const reason = prompt('Reason for declining (optional):');
        if (reason === null) return; // User cancelled

        const formData = new FormData();
        formData.append('action', 'reject_request');
        formData.append('request_id', requestId);
        if (reason) formData.append('reason', reason);

        try {
            const response = await fetch('../api/requests.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Request declined', 'info');
                await this.loadRequests();
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            console.error('Error rejecting request:', error);
            this.showNotification('Failed to decline request.', 'error');
        }
    }

    async scheduleMeeting() {
        const form = document.getElementById('schedule-meeting-form');
        const formData = new FormData(form);
        formData.append('action', 'schedule_meeting');
        formData.append('request_id', this.currentRequestId);

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        try {
            const response = await fetch('../api/requests.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Meeting scheduled successfully!', 'success');
                this.closeModal('schedule-meeting-modal');
                form.reset();
                await this.viewRequestDetails(this.currentRequestId);
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            console.error('Error scheduling meeting:', error);
            this.showNotification('Failed to schedule meeting.', 'error');
        } finally {
            submitBtn.disabled = false;
        }
    }

    async viewRequestDetails(requestId) {
        try {
            const response = await fetch(
                `../api/requests.php?action=get_request_details&request_id=${requestId}`,
                { credentials: 'include' }
            );

            const data = await response.json();

            if (data.success) {
                this.showRequestDetailsModal(data.request, data.meetings);
            } else {
                this.showNotification(data.error, 'error');
            }
        } catch (error) {
            console.error('Error loading request details:', error);
            this.showNotification('Failed to load request details.', 'error');
        }
    }

    showRequestDetailsModal(request, meetings) {
        const modal = document.getElementById('request-details-modal');
        if (!modal) return;

        const currentUserId = document.body.dataset.userId || window.currentUserId;
        const isLender = request.lender_id == currentUserId;

        const meetingsHtml = meetings.length > 0 ? `
            <div class="meetings-section">
                <h3><i class="fas fa-calendar-alt"></i> Scheduled Meetings</h3>
                ${meetings.map(m => `
                    <div class="meeting-card">
                        <div class="meeting-type ${m.meeting_type}">
                            <i class="fas fa-${m.meeting_type === 'online' ? 'video' : 'map-marker-alt'}"></i>
                            ${m.meeting_type === 'online' ? 'Online' : 'In-Person'}
                        </div>
                        <p><strong>Date:</strong> ${this.formatDateTime(m.meeting_date)}</p>
                        ${m.meeting_location ? `<p><strong>Location:</strong> ${m.meeting_location}</p>` : ''}
                        ${m.meeting_link ? `<p><strong>Link:</strong> <a href="${m.meeting_link}" target="_blank">${m.meeting_link}</a></p>` : ''}
                        ${m.notes ? `<p><strong>Notes:</strong> ${m.notes}</p>` : ''}
                        <small>Scheduled by ${m.scheduled_by_name}</small>
                    </div>
                `).join('')}
            </div>
        ` : '';

        modal.querySelector('.modal-content').innerHTML = `
            <div class="request-details-full">
                <h2>${request.item_title}</h2>
                <img src="${request.item_image || '/public/assets/images/placeholder.jpg'}" 
                     alt="${request.item_title}" class="detail-item-image">
                
                <div class="detail-section">
                    <h3>Request Information</h3>
                    <p><strong>Status:</strong> <span class="status-${request.status}">${this.getStatusText(request.status)}</span></p>
                    <p><strong>${isLender ? 'Borrower' : 'Lender'}:</strong> ${isLender ? request.borrower_name : request.lender_name}</p>
                    <p><strong>Period:</strong> ${this.formatDate(request.borrow_start_date)} - ${this.formatDate(request.borrow_end_date)}</p>
                    <p><strong>Total Price:</strong> $${parseFloat(request.total_price).toFixed(2)}</p>
                    ${request.security_deposit > 0 ? `<p><strong>Security Deposit:</strong> $${parseFloat(request.security_deposit).toFixed(2)}</p>` : ''}
                </div>

                ${request.borrower_message ? `
                    <div class="detail-section">
                        <h3>Borrower's Message</h3>
                        <p>${this.escapeHtml(request.borrower_message)}</p>
                    </div>
                ` : ''}

                ${request.lender_notes ? `
                    <div class="detail-section">
                        <h3>Lender's Notes</h3>
                        <p>${this.escapeHtml(request.lender_notes)}</p>
                    </div>
                ` : ''}

                ${meetingsHtml}

                <div class="detail-section">
                    <h3>Contact Information</h3>
                    <p><strong>Email:</strong> ${isLender ? request.borrower_email : request.lender_email}</p>
                    ${(isLender ? request.borrower_phone : request.lender_phone) ? 
                        `<p><strong>Phone:</strong> ${isLender ? request.borrower_phone : request.lender_phone}</p>` : ''}
                </div>

                <div class="modal-actions">
                    <button class="btn btn-secondary" onclick="requestManager.closeModal('request-details-modal')">Close</button>
                </div>
            </div>
        `;

        modal.style.display = 'block';
    }

    showRequestModal() {
        const modal = document.getElementById('request-modal');
        if (modal) modal.style.display = 'block';
    }

    showScheduleMeetingModal(requestId) {
        this.currentRequestId = requestId;
        const modal = document.getElementById('schedule-meeting-modal');
        if (modal) modal.style.display = 'block';
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.style.display = 'none';
    }

    toggleMeetingFields(type) {
        const locationField = document.getElementById('meeting-location-field');
        const linkField = document.getElementById('meeting-link-field');

        if (type === 'online') {
            locationField.style.display = 'none';
            linkField.style.display = 'block';
        } else {
            locationField.style.display = 'block';
            linkField.style.display = 'none';
        }
    }

    filterRequests(role, status) {
        // Update active button
        document.querySelectorAll('.request-filter-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // Load filtered requests
        this.loadRequests(role, status);
    }

    getStatusClass(status) {
        const classes = {
            'pending': 'request-pending',
            'accepted': 'request-accepted',
            'rejected': 'request-rejected',
            'active': 'request-active',
            'completed': 'request-completed',
            'cancelled': 'request-cancelled'
        };
        return classes[status] || '';
    }

    getStatusText(status) {
        const texts = {
            'pending': 'Pending',
            'accepted': 'Accepted',
            'rejected': 'Declined',
            'active': 'Active',
            'completed': 'Completed',
            'cancelled': 'Cancelled'
        };
        return texts[status] || status;
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    formatDateTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit'
        });
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        // Remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('fade-out');
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
}

// Initialize request manager
let requestManager;
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        requestManager = new RequestManager();
    });
} else {
    requestManager = new RequestManager();
}
