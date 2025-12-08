/**
 * Browse Page - Dynamic Item Loading with Real-Time Features
 * 
 * @class BrowseFilter
 * @author Athanase Abayo - Core filtering architecture and search logic
 * @author Mabinty Mambu - Category and location filtering
 * @author Olivier Kwizera - Price range filtering and sorting
 * @version 3.0 - Real-time database integration
 */
class BrowseFilter {
    /**
     * Initialize the browse filter
     */
    constructor() {
        this.grid = document.getElementById('itemsGrid');
        if (!this.grid) return;
        
        this.cards = [];
        this.items = []; // Store item data
        this.init();
    }

    /**
     * Initialize filter system and load items from database
     */
    async init() {
        // Initialize translation system first
        if (window.swapitTranslation && !window.swapitTranslation.isInitialized()) {
            await window.swapitTranslation.init();
        }
        
        // Load items from database
        await this.loadItemsFromDatabase();
        
        // Add unique IDs to cards if they don't have them
        this.cards.forEach((card, index) => {
            if (!card.dataset.id) {
                card.dataset.id = 'item-' + (index + 1);
            }
        });
        
        // Load items from database
        await this.loadItemsFromDatabase();
        
        // Load any items user created while offline (stored in browser)
        this.loadPendingListings();
        
        // Attach listeners to filter dropdowns (category, location, price, etc.)
        this.setupFilterControls();
        
        // Display all items on initial load (no filtering yet)
        this.render();
    }

    /**
     * Load items from database via API
     * Fetches all available items from the server and displays them on the browse page
     * Each item becomes a card with image, title, description, price, and location
     * If fetch fails, we continue with existing items (graceful degradation)
     * 
     * @author Athanase Abayo
     */
    async loadItemsFromDatabase() {
        try {
            const response = await fetch('/api/items.php?action=get_all', {
                credentials: 'include'
            });
            
            if (!response.ok) {
                throw new Error('Failed to fetch items');
            }
            
            const data = await response.json();
            
            if (data.success && data.items && data.items.length > 0) {
                data.items.forEach(item => {
                    // Create a new article element for this item
                    const art = document.createElement('article');
                    art.className = 'card';
                    
                    // Store item data in element attributes for filtering
                    art.dataset.id = 'item-' + item.id;
                    art.dataset.category = item.category_slug || item.category_name || '';
                    art.dataset.price = String(item.price || 0);
                    art.dataset.location = item.location || '';
                    art.dataset.title = item.title || '';
                    
                    // Determine image URL - use item image or placeholder
                    const imageUrl = item.image_url || 'https://placehold.co/400x300?text=' + encodeURIComponent(item.title || 'Item');
                    
                    // Build the card HTML
                    art.innerHTML = `
                        <img class="card__thumb" src="${imageUrl}" alt="${this.escapeHtml(item.title || 'Item')}" onerror="this.src='https://placehold.co/400x300?text=Item'">
                        <h3>${this.escapeHtml(item.title || 'Item')}</h3>
                        <p>${this.escapeHtml((item.description || '').substring(0, 100))}${item.description && item.description.length > 100 ? '...' : ''}</p>
                        <div class="card__meta">GHS ${item.price || 0}/day — ${this.escapeHtml(item.location || 'Location not specified')}</div>
                        <div class="card__owner" style="margin-top: 8px; color: #666; font-size: 0.9rem;">
                            <i class="fas fa-user"></i> ${this.escapeHtml(item.owner_name || 'Owner')}
                        </div>
                        <div class="card__actions" style="margin-top: 12px; display: flex; gap: 8px;">
                            <button class="btn btn--primary btn--small" onclick="showItemDetails(${item.id})">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            <button class="btn btn--secondary btn--small" onclick="quickRequest(${item.id}, '${this.escapeHtml(item.title)}')">
                                <i class="fas fa-paper-plane"></i> Request
                            </button>
                        </div>
                    `;
                    
                    // Add card to the grid and to our cards array
                    this.grid.appendChild(art);
                    this.cards.push(art);
                });
            }
        } catch (error) {
            console.warn('Could not load items from database:', error);
            // Continue with existing items - don't break the page
        }
    }

    /**
     * HTML escape helper for security
     * Protects against XSS attacks by converting dangerous characters into safe HTML codes
     * For example: converts "<script>" to "&lt;script&gt;" so it displays as text instead of running
     * This prevents malicious code from being injected into the page
     * 
     * @param {string} str - String to escape
     * @returns {string} Escaped string safe to display in HTML
     * @author Victoria Ama Nyonator
     */
    escapeHtml(str) {
        // Replace special characters with their HTML entity equivalents
        return String(str || '').replace(/[&<>"']/g, s => ({
            '&': '&amp;',   // Ampersand
            '<': '&lt;',    // Less than
            '>': '&gt;',    // Greater than
            '"': '&quot;',  // Double quote
            "'": '&#39;'    // Single quote
        })[s]);
    }

    /**
     * Load pending listings from localStorage (items added offline)
     * When users create listings, they're saved in the browser until synced to server
     * This function pulls those saved listings and displays them on the browse page
     * Each listing becomes a card with image, title, description, price, and location
     * If something goes wrong, we just skip it and keep the page working
     * 
     * @author Athanase Abayo
     */
    loadPendingListings() {
        try {
            // Get pending items from browser storage (returns empty array if none exist)
            const pending = JSON.parse(localStorage.getItem('swapit_pending_items') || '[]');
            
            // If we have any pending items, turn each one into a card
            if (pending && pending.length) {
                pending.forEach(p => {
                    // Create a new article element for this listing
                    const art = document.createElement('article');
                    art.className = 'card';
                    
                    // Store item data in element attributes so we can filter by them later
                    art.dataset.category = p.category || '';
                    art.dataset.price = String(p.price || 0);
                    art.dataset.location = p.location || '';
                    art.dataset.title = p.title || '';
                    
                    // Build the card HTML with image, title, description, and metadata
                    art.innerHTML = `<img class="card__thumb" src="${p.image_url || 'https://placehold.co/400x300?text=Listing'}" alt=""><h3>${this.escapeHtml(p.title || 'Listing')}</h3><p>${this.escapeHtml(p.description || '')}</p><div class="card__meta">GHS ${p.price || 0} — ${p.location || ''}</div>`;
                    
                    // Add card to the grid and to our cards array
                    this.grid.appendChild(art);
                    this.cards.push(art);
                });
            }
        } catch (e) {
            // If something fails (bad data, storage issues), just log it and continue
            console.warn('Could not load pending items', e);
        }
    }

    /**
     * Setup filter control event handlers
     * Connects all the filter dropdowns and search boxes to the filtering logic
     * Whenever user changes a filter, we re-render the items to show only matches
     * Search has a delay so we don't filter on every single keystroke (saves performance)
     * Also syncs the navigation search bar with the page search box
     * 
     * @author Mabinty Mambu - Filter controls
     * @author Victoria Ama Nyonato - Search functionality
     */
    setupFilterControls() {
        // Grab references to all filter controls on the page
        const filterCategory = document.getElementById('filterCategory');
        const filterLocation = document.getElementById('filterLocation');
        const filterMin = document.getElementById('filterMin');
        const filterMax = document.getElementById('filterMax');
        const sortBy = document.getElementById('sortBy');
        const pageSearch = document.getElementById('pageSearch');

        // For each filter dropdown, listen for changes and re-filter items
        [filterCategory, filterLocation, filterMin, filterMax, sortBy].forEach(el => {
            if (!el) return; // Skip if element doesn't exist
            // Listen for both 'change' (dropdown) and 'input' (typing in number fields)
            el.addEventListener('change', () => this.render());
            el.addEventListener('input', () => this.render());
        });

        // For search box, wait 250ms after user stops typing before filtering
        // This prevents filtering on every keystroke which would be slow
        if (pageSearch) {
            pageSearch.addEventListener('input', this.debounce(() => this.render(), 250));
        }

        // Sync the navigation search bar with the main page search
        const navSearch = document.querySelector('#navSearch');
        if (navSearch && pageSearch) {
            // When user types in nav search, copy value to page search and filter
            navSearch.addEventListener('input', this.debounce(() => {
                pageSearch.value = navSearch.value;
                this.render();
            }, 250));
            
            // When user hits Enter in nav search, immediately filter
            navSearch.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault(); // Don't submit form
                    pageSearch.value = navSearch.value;
                    this.render();
                }
            });
        }
    }

    /**
     * Parse price from card dataset
     * Extracts the price from a card's data attribute and converts it to a number
     * Returns NaN (Not a Number) if price is missing or invalid
     * This is used for price filtering and sorting
     * 
     * @param {HTMLElement} card - Card element
     * @returns {number} Price value or NaN if not found
     * @author Olivier Kwizera
     */
    parsePrice(card) {
        // Get the price stored in the card's data attribute
        const v = card.dataset.price;
        // Convert to number if exists, otherwise return NaN
        return v ? parseFloat(v) : NaN;
    }

    /**
     * Check if card matches current filter criteria
     * This is the heart of the filtering system - tests if an item should be shown
     * Goes through each filter one by one:
     * - Category: if selected, item must match exactly
     * - Location: if selected, item must match exactly
     * - Min price: if set, item price must be >= minimum
     * - Max price: if set, item price must be <= maximum
     * - Search: if typed, search term must appear in title or description
     * Returns true only if item passes ALL active filters
     * 
     * @param {HTMLElement} card - Card element to check
     * @returns {boolean} True if card matches filters, false to hide it
     * @author Athanase Abayo - Core matching logic
     * @author Mabinty Mambu - Category and location filters
     * @author Olivier Kwizera - Price and search filters
     */
    matches(card) {
        // Get current values from all filter controls
        const filterCategory = document.getElementById('filterCategory');
        const filterLocation = document.getElementById('filterLocation');
        const filterMin = document.getElementById('filterMin');
        const filterMax = document.getElementById('filterMax');
        const pageSearch = document.getElementById('pageSearch');

        // Extract the actual filter values (empty string if not set)
        const cat = filterCategory ? filterCategory.value : '';
        const loc = filterLocation ? filterLocation.value : '';
        const min = filterMin ? parseFloat(filterMin.value) : NaN;
        const max = filterMax ? parseFloat(filterMax.value) : NaN;
        const q = pageSearch ? pageSearch.value.trim().toLowerCase() : '';

        // If category filter is active and card doesn't match, hide it
        if (cat && card.dataset.category !== cat) return false;
        
        // If location filter is active and card doesn't match, hide it
        if (loc && card.dataset.location !== loc) return false;

        // Check price filters (only if user entered a min/max)
        const price = this.parsePrice(card);
        if (!isNaN(min) && price < min) return false; // Item too cheap
        if (!isNaN(max) && price > max) return false; // Item too expensive

        // If user typed a search query, check if it appears in title or description
        if (q) {
            const title = (card.dataset.title || '').toLowerCase();
            const desc = (card.querySelector('p')?.textContent || '').toLowerCase();
            // Hide if search term not found in either title or description
            if (!title.includes(q) && !desc.includes(q)) return false;
        }

        // If we got here, item passed all filters - show it!
        return true;
    }

    /**
     * Render filtered and sorted items
     * This runs every time a filter changes - it's the main display update function
     * Step 1: Filter - only keep cards that match current filter settings
     * Step 2: Sort - arrange cards by price if user selected a sort option
     * Step 3: Display - clear the grid and show filtered/sorted results
     * If no items match, show a friendly "no results" message
     * 
     * @author Athanase Abayo - Rendering logic
     * @author Olivier Kwizera - Sorting implementation
     */
    render() {
        // Make a copy of all cards, then filter to only matching ones
        let visible = this.cards.slice().filter(card => this.matches(card));

        // Get the sort option user selected (if any)
        const sortBy = document.getElementById('sortBy');
        const sortVal = sortBy ? sortBy.value : '';
        
        // Sort by price low to high (cheapest first)
        if (sortVal === 'price-low') {
            visible.sort((a, b) => this.parsePrice(a) - this.parsePrice(b));
        }
        
        // Sort by price high to low (most expensive first)
        if (sortVal === 'price-high') {
            visible.sort((a, b) => this.parsePrice(b) - this.parsePrice(a));
        }

        // Clear the grid completely
        this.grid.innerHTML = '';
        
        // If no items match filters, show "no results" message
        if (visible.length === 0) {
            const noResultsText = window.swapitTranslation?.translations?.browse?.noResults || 'No items found';
            this.grid.innerHTML = `<div style="padding:24px;color:#cbd6ff">${noResultsText}</div>`;
            return;
        }
        
        // Add each visible card back to the grid in order
        visible.forEach(c => this.grid.appendChild(c));
    }

    /**
     * Debounce Helper - Delays function execution until user stops typing
     * Prevents performance issues from running expensive operations on every keystroke
     * Example: If user types "laptop" quickly, we don't want to filter 6 times
     * Instead, wait until they pause typing, then filter once
     * Each new keystroke resets the timer, so function only runs after silence
     * 
     * @param {Function} fn - Function to debounce (delay)
     * @param {number} wait - Milliseconds to wait after last keystroke
     * @returns {Function} Debounced version of the function
     * @author Victoria Ama Nyonato
     */
    debounce(fn, wait) {
        let t; // Timer variable
        return (...args) => {
            clearTimeout(t); // Cancel previous timer if user is still typing
            t = setTimeout(() => fn(...args), wait); // Start new timer
        };
    }
}

/**
 * Initialize browse filter when DOM is ready
 * Wait for the page to fully load before starting the filter system
 * This ensures all HTML elements exist before we try to find and use them
 * Creates a new BrowseFilter instance which handles all filtering logic
 * 
 * @author Athanase Abayo
 */
document.addEventListener('DOMContentLoaded', () => {
    // Page is loaded, start the filtering system
    new BrowseFilter();
});

// Global functions for item interactions

/**
 * Show item details in a modal
 * @param {number} itemId - ID of the item to display
 */
async function showItemDetails(itemId) {
    try {
        const response = await fetch(`/api/listings.php?action=get_all_items`, {
            credentials: 'include'
        });
        
        const data = await response.json();
        if (!data.success) throw new Error('Failed to load item');
        
        const item = data.items.find(i => i.id === itemId);
        if (!item) throw new Error('Item not found');
        
        // Create modal
        const modal = document.createElement('div');
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:9999;padding:20px;';
        modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
        
        const imageUrl = item.image_url || 'https://placehold.co/600x400?text=' + encodeURIComponent(item.title);
        
        modal.innerHTML = `
            <div style="background:white;border-radius:20px;max-width:800px;width:100%;max-height:90vh;overflow-y:auto;position:relative;">
                <button onclick="this.closest('div').parentElement.remove()" style="position:absolute;top:15px;right:15px;background:#ff4757;color:white;border:none;width:40px;height:40px;border-radius:50%;cursor:pointer;font-size:1.5rem;z-index:1;box-shadow:0 4px 12px rgba(0,0,0,0.3);">×</button>
                
                <img src="${imageUrl}" style="width:100%;height:400px;object-fit:cover;border-radius:20px 20px 0 0;" onerror="this.src='https://placehold.co/600x400?text=Item'">
                
                <div style="padding:30px;">
                    <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:20px;">
                        <div>
                            <h2 style="margin:0;font-size:2rem;color:#333;">${escapeHtml(item.title)}</h2>
                            <p style="color:#666;margin:8px 0;"><i class="fas fa-tag"></i> ${escapeHtml(item.category_name || 'Uncategorized')}</p>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-size:2rem;font-weight:bold;color:#667eea;">GHS ${item.price}</div>
                            <div style="color:#666;font-size:0.9rem;">per day</div>
                        </div>
                    </div>
                    
                    <div style="background:#f8f9fa;padding:15px;border-radius:12px;margin-bottom:20px;">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                            <div><i class="fas fa-user" style="color:#667eea;margin-right:8px;"></i><strong>Owner:</strong> ${escapeHtml(item.owner_name || 'Unknown')}</div>
                            <div><i class="fas fa-map-marker-alt" style="color:#667eea;margin-right:8px;"></i><strong>Location:</strong> ${escapeHtml(item.location || 'Not specified')}</div>
                            <div><i class="fas fa-check-circle" style="color:#28a745;margin-right:8px;"></i><strong>Status:</strong> ${item.status === 'available' ? '<span style="color:#28a745">Available</span>' : '<span style="color:#dc3545">Unavailable</span>'}</div>
                            <div><i class="fas fa-calendar" style="color:#667eea;margin-right:8px;"></i><strong>Listed:</strong> ${new Date(item.created_at).toLocaleDateString()}</div>
                        </div>
                    </div>
                    
                    <div style="margin-bottom:25px;">
                        <h3 style="color:#333;margin-bottom:10px;">Description</h3>
                        <p style="color:#666;line-height:1.6;">${escapeHtml(item.description || 'No description provided.')}</p>
                    </div>
                    
                    <div style="display:flex;gap:12px;margin-top:25px;">
                        <button onclick="requestItem(${item.id}, '${escapeHtml(item.title)}', ${item.price}, '${escapeHtml(item.owner_name)}')" style="flex:1;padding:15px;background:#667eea;color:white;border:none;border-radius:12px;font-size:1.1rem;font-weight:600;cursor:pointer;transition:all 0.3s;">
                            <i class="fas fa-paper-plane"></i> Send Borrow Request
                        </button>
                        <button onclick="addToWishlist(${item.id})" style="padding:15px 25px;background:white;color:#667eea;border:2px solid #667eea;border-radius:12px;font-size:1.1rem;cursor:pointer;transition:all 0.3s;">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
    } catch (error) {
        console.error('Error showing item details:', error);
        alert('Failed to load item details. Please try again.');
    }
}

/**
 * Quick request function
 * @param {number} itemId - ID of the item
 * @param {string} itemTitle - Title of the item
 */
function quickRequest(itemId, itemTitle) {
    requestItem(itemId, itemTitle, 0, 'Owner');
}

/**
 * Request an item with date selection
 * @param {number} itemId - ID of the item
 * @param {string} itemTitle - Title of the item
 * @param {number} price - Daily price
 * @param {string} ownerName - Owner's name
 */
function requestItem(itemId, itemTitle, price, ownerName) {
    // Create request modal
    const modal = document.createElement('div');
    modal.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);display:flex;align-items:center;justify-content:center;z-index:10000;padding:20px;';
    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
    
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dayAfter = new Date();
    dayAfter.setDate(dayAfter.getDate() + 2);
    
    modal.innerHTML = `
        <div style="background:white;border-radius:20px;max-width:600px;width:100%;padding:30px;position:relative;">
            <button onclick="this.closest('div').parentElement.remove()" style="position:absolute;top:15px;right:15px;background:#ff4757;color:white;border:none;width:40px;height:40px;border-radius:50%;cursor:pointer;font-size:1.5rem;">×</button>
            
            <h2 style="margin:0 0 10px 0;color:#333;"><i class="fas fa-paper-plane" style="color:#667eea;"></i> Borrow Request</h2>
            <p style="color:#666;margin-bottom:25px;">Request to borrow: <strong>${escapeHtml(itemTitle)}</strong> from ${escapeHtml(ownerName)}</p>
            
            <form id="requestForm" onsubmit="submitRequest(event, ${itemId}, ${price})">
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-weight:600;margin-bottom:8px;color:#333;">Start Date</label>
                    <input type="date" id="start_date" required style="width:100%;padding:12px;border:2px solid #e9ecef;border-radius:8px;font-size:1rem;" min="${tomorrow.toISOString().split('T')[0]}">
                </div>
                
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-weight:600;margin-bottom:8px;color:#333;">End Date</label>
                    <input type="date" id="end_date" required style="width:100%;padding:12px;border:2px solid #e9ecef;border-radius:8px;font-size:1rem;" min="${dayAfter.toISOString().split('T')[0]}">
                </div>
                
                <div style="margin-bottom:20px;">
                    <label style="display:block;font-weight:600;margin-bottom:8px;color:#333;">Message to Owner</label>
                    <textarea id="request_message" rows="4" required style="width:100%;padding:12px;border:2px solid #e9ecef;border-radius:8px;font-size:1rem;resize:vertical;" placeholder="Hi! I would like to borrow this item. I'll take good care of it!"></textarea>
                </div>
                
                <div id="priceEstimate" style="background:#f8f9fa;padding:15px;border-radius:8px;margin-bottom:20px;display:none;">
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="color:#666;">Estimated Total:</span>
                        <span style="font-size:1.5rem;font-weight:bold;color:#667eea;">GHS <span id="totalPrice">0</span></span>
                    </div>
                    <small style="color:#999;"><span id="dayCount">0</span> days × GHS ${price}/day</small>
                </div>
                
                <button type="submit" style="width:100%;padding:15px;background:#667eea;color:white;border:none;border-radius:12px;font-size:1.1rem;font-weight:600;cursor:pointer;transition:all 0.3s;">
                    <i class="fas fa-check"></i> Send Request
                </button>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Calculate price estimate when dates change
    if (price > 0) {
        const startInput = modal.querySelector('#start_date');
        const endInput = modal.querySelector('#end_date');
        const priceEstimate = modal.querySelector('#priceEstimate');
        const totalPriceSpan = modal.querySelector('#totalPrice');
        const dayCountSpan = modal.querySelector('#dayCount');
        
        function updateEstimate() {
            const start = new Date(startInput.value);
            const end = new Date(endInput.value);
            if (start && end && end > start) {
                const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
                const total = days * price;
                dayCountSpan.textContent = days;
                totalPriceSpan.textContent = total.toFixed(2);
                priceEstimate.style.display = 'block';
            } else {
                priceEstimate.style.display = 'none';
            }
        }
        
        startInput.addEventListener('change', updateEstimate);
        endInput.addEventListener('change', updateEstimate);
    }
}

/**
 * Submit borrow request
 * @param {Event} event - Form submit event
 * @param {number} itemId - ID of the item
 * @param {number} price - Daily price
 */
async function submitRequest(event, itemId, price) {
    event.preventDefault();
    
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;
    const message = document.getElementById('request_message').value;
    
    const formData = new FormData();
    formData.append('action', 'create_borrow_request');
    formData.append('item_id', itemId);
    formData.append('start_date', startDate);
    formData.append('end_date', endDate);
    formData.append('message', message);
    
    try {
        const response = await fetch('/api/listings.php', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Close modal
            event.target.closest('div').parentElement.remove();
            
            // Show success notification
            showSuccessNotification('Request sent successfully! The owner will be notified.');
            
            // Optionally redirect to requests page
            setTimeout(() => {
                window.location.href = 'requests.html';
            }, 2000);
        } else {
            alert('Error: ' + (data.error || 'Failed to send request'));
        }
    } catch (error) {
        console.error('Error submitting request:', error);
        alert('Failed to send request. Please try again.');
    }
}

/**
 * Add item to wishlist
 * @param {number} itemId - ID of the item
 */
async function addToWishlist(itemId) {
    const formData = new FormData();
    formData.append('action', 'add_to_wishlist');
    formData.append('item_id', itemId);
    
    try {
        const response = await fetch('/api/listings.php', {
            method: 'POST',
            credentials: 'include',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccessNotification('Added to wishlist!');
        } else {
            alert('Error: ' + (data.error || 'Failed to add to wishlist'));
        }
    } catch (error) {
        console.error('Error adding to wishlist:', error);
        alert('Failed to add to wishlist. Please try again.');
    }
}

/**
 * Show success notification
 * @param {string} message - Message to display
 */
function showSuccessNotification(message) {
    const notif = document.createElement('div');
    notif.style.cssText = 'position:fixed;top:20px;right:20px;background:#28a745;color:white;padding:20px 30px;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.3);z-index:10001;animation:slideIn 0.3s;';
    notif.innerHTML = `<i class="fas fa-check-circle"></i> ${escapeHtml(message)}`;
    document.body.appendChild(notif);
    
    setTimeout(() => {
        notif.style.animation = 'slideOut 0.3s';
        setTimeout(() => notif.remove(), 300);
    }, 3000);
}

/**
 * HTML escape helper
 * @param {string} str - String to escape
 * @returns {string} Escaped string
 */
function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, s => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    })[s]);
}

// Initialize browse filter when page loads
if (document.getElementById('itemsGrid')) {
    new BrowseFilter();
}

/**
 * Show item details in modal
 * @param {number} itemId - ID of item to show
 */
window.showItemDetails = function(itemId) {
    if (window.itemDetailsManager) {
        window.itemDetailsManager.showItem(itemId);
    } else {
        console.error('Item details manager not loaded');
        alert('Item details unavailable. Please refresh the page.');
    }
};

/**
 * Quick request function - opens modal at request form
 * @param {number} itemId - ID of item
 * @param {string} itemTitle - Title of item
 */
window.quickRequest = function(itemId, itemTitle) {
    if (window.itemDetailsManager) {
        window.itemDetailsManager.showItem(itemId);
        // Scroll to form after modal opens
        setTimeout(() => {
            const form = document.getElementById('borrowRequestForm');
            if (form) {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 300);
    } else {
        console.error('Item details manager not loaded');
        alert('Request feature unavailable. Please refresh the page.');
    }
};

