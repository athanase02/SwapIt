/**
 * SwapIt Requests Management System
 * Handles borrow requests, approvals, rejections, and meeting scheduling
 * @version 1.0
 */

class RequestManager {
    constructor() {
        this.currentRequestId = null;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadRequests();
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
        try {
            const response = await fetch(
                `/api/requests.php?action=get_my_requests&role=${role}&status=${status}`,
                { credentials: 'include' }
            );

            const data = await response.json();

            if (data.success) {
                this.renderRequests(data.requests);
            } else {
                console.error('Failed to load requests:', data.error);
            }
        } catch (error) {
            console.error('Error loading requests:', error);
        }
    }

    renderRequests(requests) {
        const container = document.getElementById('requests-container');
        if (!container) return;

        if (requests.length === 0) {
            container.innerHTML = `
                <div class="no-requests">
                    <i class="fas fa-clipboard-list"></i>
                    <p>No requests found</p>
                    <small>Your borrow requests will appear here</small>
                </div>
            `;
            return;
        }

        const currentUserId = document.body.dataset.userId || window.currentUserId;

        container.innerHTML = requests.map(req => {
            const isLender = req.lender_id == currentUserId;
            const statusClass = this.getStatusClass(req.status);
            const statusText = this.getStatusText(req.status);

            return `
                <div class="request-card ${statusClass}">
                    <div class="request-header">
                        <div class="item-info">
                            <img src="${req.item_image || '/public/assets/images/placeholder.jpg'}" 
                                 alt="${req.item_title}" class="item-thumbnail">
                            <div>
                                <h3>${req.item_title}</h3>
                                <p class="request-role">${isLender ? 'Lend Request' : 'Borrow Request'}</p>
                            </div>
                        </div>
                        <span class="request-status status-${req.status}">${statusText}</span>
                    </div>

                    <div class="request-details">
                        <div class="detail-row">
                            <i class="fas fa-user"></i>
                            <span>${isLender ? req.borrower_name : req.lender_name}</span>
                        </div>
                        <div class="detail-row">
                            <i class="fas fa-calendar"></i>
                            <span>${this.formatDate(req.borrow_start_date)} - ${this.formatDate(req.borrow_end_date)}</span>
                        </div>
                        <div class="detail-row">
                            <i class="fas fa-dollar-sign"></i>
                            <span>$${parseFloat(req.total_price).toFixed(2)}</span>
                        </div>
                    </div>

                    ${req.borrower_message ? `
                        <div class="request-message">
                            <strong>Message:</strong> ${this.escapeHtml(req.borrower_message)}
                        </div>
                    ` : ''}

                    <div class="request-actions">
                        ${this.getActionButtons(req, isLender)}
                    </div>
                </div>
            `;
        }).join('');
    }

    getActionButtons(request, isLender) {
        const buttons = [];

        // View details button (always available)
        buttons.push(`
            <button class="btn btn-secondary view-request-btn" data-request-id="${request.id}">
                <i class="fas fa-eye"></i> View Details
            </button>
        `);

        // Lender actions for pending requests
        if (isLender && request.status === 'pending') {
            buttons.push(`
                <button class="btn btn-success accept-request-btn" data-request-id="${request.id}">
                    <i class="fas fa-check"></i> Accept
                </button>
                <button class="btn btn-danger reject-request-btn" data-request-id="${request.id}">
                    <i class="fas fa-times"></i> Decline
                </button>
            `);
        }

        // Schedule meeting button for accepted requests
        if (request.status === 'accepted') {
            buttons.push(`
                <button class="btn btn-primary schedule-meeting-btn" data-request-id="${request.id}">
                    <i class="fas fa-calendar-plus"></i> Schedule Meeting
                </button>
            `);
        }

        // Message button
        const otherUserId = isLender ? request.borrower_id : request.lender_id;
        buttons.push(`
            <button class="btn btn-info start-conversation-btn" data-user-id="${otherUserId}">
                <i class="fas fa-comment"></i> Message
            </button>
        `);

        return buttons.join('');
    }

    async submitRequest() {
        const form = document.getElementById('request-form');
        const formData = new FormData(form);
        formData.append('action', 'create_request');

        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        try {
            const response = await fetch('/api/requests.php', {
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
            const response = await fetch('/api/requests.php', {
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
            const response = await fetch('/api/requests.php', {
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
            const response = await fetch('/api/requests.php', {
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
                `/api/requests.php?action=get_request_details&request_id=${requestId}`,
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
