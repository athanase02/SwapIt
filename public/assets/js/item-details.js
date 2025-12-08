/**
 * Item Details Manager
 * Handles displaying item details and sending borrow requests
 */

class ItemDetailsManager {
    constructor() {
        this.currentItem = null;
        this.modal = null;
        this.initModal();
    }
    
    /**
     * Initialize modal HTML
     */
    initModal() {
        // Check if modal already exists
        if (document.getElementById('itemDetailsModal')) {
            this.modal = document.getElementById('itemDetailsModal');
            return;
        }
        
        // Create modal
        const modalHTML = `
            <div id="itemDetailsModal" class="item-modal" style="display: none;">
                <div class="item-modal-overlay" onclick="window.itemDetailsManager.closeModal()"></div>
                <div class="item-modal-content">
                    <button class="item-modal-close" onclick="window.itemDetailsManager.closeModal()">
                        <i class="fas fa-times"></i>
                    </button>
                    
                    <div class="item-modal-body">
                        <div class="item-modal-image">
                            <img id="modalItemImage" src="" alt="Item">
                            <div class="item-modal-status" id="modalItemStatus"></div>
                        </div>
                        
                        <div class="item-modal-info">
                            <h2 id="modalItemTitle">Item Title</h2>
                            <div class="item-owner-info">
                                <img id="modalOwnerAvatar" src="" alt="Owner">
                                <div>
                                    <div class="owner-name" id="modalOwnerName">Owner Name</div>
                                    <div class="owner-status" id="modalOwnerStatus">
                                        <span class="status-dot"></span>
                                        <span class="status-text">Offline</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="item-price" id="modalItemPrice">GHS 0/day</div>
                            
                            <div class="item-details-section">
                                <h3>Description</h3>
                                <p id="modalItemDescription">Item description goes here</p>
                            </div>
                            
                            <div class="item-details-section">
                                <div class="detail-row">
                                    <span class="detail-label">Category:</span>
                                    <span id="modalItemCategory">-</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Location:</span>
                                    <span id="modalItemLocation">-</span>
                                </div>
                                <div class="detail-row">
                                    <span class="detail-label">Condition:</span>
                                    <span id="modalItemCondition">-</span>
                                </div>
                            </div>
                            
                            <div id="borrowRequestForm" class="borrow-request-form">
                                <h3>Request to Borrow</h3>
                                
                                <div class="form-group">
                                    <label>Start Date:</label>
                                    <input type="date" id="borrowStartDate" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>End Date:</label>
                                    <input type="date" id="borrowEndDate" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Pickup Location (optional):</label>
                                    <input type="text" id="pickupLocation" placeholder="e.g., Main Campus">
                                </div>
                                
                                <div class="form-group">
                                    <label>Message to Owner:</label>
                                    <textarea id="borrowMessage" rows="3" placeholder="Tell the owner why you'd like to borrow this item..."></textarea>
                                </div>
                                
                                <div class="estimated-cost" id="estimatedCost" style="display: none;">
                                    <strong>Estimated Cost:</strong> GHS <span id="costAmount">0</span>
                                </div>
                                
                                <button class="btn-primary" onclick="window.itemDetailsManager.submitRequest()">
                                    <i class="fas fa-paper-plane"></i> Send Borrow Request
                                </button>
                                
                                <button class="btn-secondary" onclick="window.itemDetailsManager.contactOwner()">
                                    <i class="fas fa-comment"></i> Message Owner
                                </button>
                            </div>
                            
                            <div id="ownItemMessage" class="own-item-message" style="display: none;">
                                <i class="fas fa-info-circle"></i>
                                <p>This is your own item. You can edit it from your dashboard.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('itemDetailsModal');
        
        // Add event listeners for date calculation
        document.getElementById('borrowStartDate').addEventListener('change', () => this.calculateCost());
        document.getElementById('borrowEndDate').addEventListener('change', () => this.calculateCost());
        
        // Set min date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('borrowStartDate').min = today;
        document.getElementById('borrowEndDate').min = today;
    }
    
    /**
     * Show item details
     */
    async showItem(itemId) {
        try {
            // Fetch item details
            const response = await fetch(`/api/items.php?action=get_item&id=${itemId}`, {
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch item details');
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Failed to load item');
            }
            
            this.currentItem = data.item;
            this.displayItemDetails(data.item);
            this.modal.style.display = 'flex';
            
            // Check if owner is online
            if (window.onlineStatusManager) {
                const isOnline = await window.onlineStatusManager.isUserOnline(data.item.user_id);
                this.updateOwnerStatus(isOnline);
            }
            
        } catch (error) {
            console.error('Error showing item:', error);
            alert('Failed to load item details. Please try again.');
        }
    }
    
    /**
     * Display item details in modal
     */
    displayItemDetails(item) {
        document.getElementById('modalItemImage').src = item.image_url || '../assets/images/placeholder.jpg';
        document.getElementById('modalItemTitle').textContent = item.title;
        document.getElementById('modalItemDescription').textContent = item.description;
        document.getElementById('modalItemPrice').textContent = `GHS ${item.price_per_day}/day`;
        document.getElementById('modalItemCategory').textContent = item.category || '-';
        document.getElementById('modalItemLocation').textContent = item.location || '-';
        document.getElementById('modalItemCondition').textContent = item.condition || 'Good';
        document.getElementById('modalItemStatus').textContent = item.status === 'available' ? 'Available' : 'Not Available';
        document.getElementById('modalItemStatus').className = `item-modal-status ${item.status}`;
        
        // Owner info
        document.getElementById('modalOwnerAvatar').src = item.owner_avatar || '../assets/images/default-avatar.png';
        document.getElementById('modalOwnerName').textContent = item.owner_name || 'Unknown';
        
        // Check if this is user's own item
        const isOwnItem = window.currentUser && window.currentUser.id === item.user_id;
        
        if (isOwnItem) {
            document.getElementById('borrowRequestForm').style.display = 'none';
            document.getElementById('ownItemMessage').style.display = 'block';
        } else {
            document.getElementById('borrowRequestForm').style.display = 'block';
            document.getElementById('ownItemMessage').style.display = 'none';
        }
        
        // Reset form
        this.resetForm();
    }
    
    /**
     * Update owner online status
     */
    updateOwnerStatus(isOnline) {
        const statusDot = document.querySelector('#modalOwnerStatus .status-dot');
        const statusText = document.querySelector('#modalOwnerStatus .status-text');
        
        if (isOnline) {
            statusDot.style.backgroundColor = '#4ade80';
            statusText.textContent = 'Online';
        } else {
            statusDot.style.backgroundColor = '#94a3b8';
            statusText.textContent = 'Offline';
        }
    }
    
    /**
     * Calculate estimated cost
     */
    calculateCost() {
        const startDate = document.getElementById('borrowStartDate').value;
        const endDate = document.getElementById('borrowEndDate').value;
        
        if (!startDate || !endDate || !this.currentItem) {
            document.getElementById('estimatedCost').style.display = 'none';
            return;
        }
        
        const start = new Date(startDate);
        const end = new Date(endDate);
        const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        
        if (days < 1) {
            document.getElementById('estimatedCost').style.display = 'none';
            return;
        }
        
        // Use 'price' field from database (not price_per_day)
        const pricePerDay = parseFloat(this.currentItem.price || this.currentItem.price_per_day || 0);
        const cost = days * pricePerDay;
        document.getElementById('costAmount').textContent = cost.toFixed(2);
        document.getElementById('estimatedCost').style.display = 'block';
    }
    
    /**
     * Submit borrow request
     */
    async submitRequest() {
        if (!this.currentItem) return;
        
        const startDate = document.getElementById('borrowStartDate').value;
        const endDate = document.getElementById('borrowEndDate').value;
        const message = document.getElementById('borrowMessage').value.trim();
        const location = document.getElementById('pickupLocation').value.trim();
        
        // Validation
        if (!startDate || !endDate) {
            alert('Please select start and end dates');
            return;
        }
        
        if (!message) {
            alert('Please add a message to the owner');
            return;
        }
        
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (end < start) {
            alert('End date must be after start date');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('action', 'create_request');
            formData.append('item_id', this.currentItem.id);
            formData.append('owner_id', this.currentItem.owner_id); // Fixed: use owner_id from API
            formData.append('start_date', startDate);
            formData.append('end_date', endDate);
            formData.append('message', message);
            if (location) formData.append('pickup_location', location);
            
            const response = await fetch('/api/requests.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Failed to send request');
            }
            
            const data = await response.json();
            
            if (data.success) {
                alert('Borrow request sent successfully! The owner will be notified.');
                this.closeModal();
                
                // Refresh page or update UI
                if (window.location.href.includes('browse.html')) {
                    // Just close modal, no need to reload
                }
            } else {
                throw new Error(data.error || 'Failed to send request');
            }
            
        } catch (error) {
            console.error('Error submitting request:', error);
            alert('Failed to send request: ' + error.message);
        }
    }
    
    /**
     * Contact owner via message
     */
    async contactOwner() {
        if (!this.currentItem) return;
        
        try {
            const formData = new FormData();
            formData.append('action', 'start_conversation');
            formData.append('user_id', this.currentItem.owner_id); // Fixed: use owner_id from API
            formData.append('item_id', this.currentItem.id);
            
            const response = await fetch('/api/messages.php', {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Redirect to messages page with conversation
                window.location.href = `messages.html?conversation=${data.conversation_id}`;
            } else {
                throw new Error(data.error);
            }
            
        } catch (error) {
            console.error('Error starting conversation:', error);
            alert('Failed to start conversation. Please try again.');
        }
    }
    
    /**
     * Close modal
     */
    closeModal() {
        this.modal.style.display = 'none';
        this.currentItem = null;
        this.resetForm();
    }
    
    /**
     * Reset form
     */
    resetForm() {
        document.getElementById('borrowStartDate').value = '';
        document.getElementById('borrowEndDate').value = '';
        document.getElementById('borrowMessage').value = '';
        document.getElementById('pickupLocation').value = '';
        document.getElementById('estimatedCost').style.display = 'none';
    }
}

// Create global instance
window.itemDetailsManager = new ItemDetailsManager();
