/**
 * Browse Page Filter and Search System
 * Client-side filtering by category, location, price, and search query
 * 
 * @class BrowseFilter
 * @author Athanase Abayo - Core filtering architecture and search logic
 * @author Mabinty Mambu - Category and location filtering
 * @author Olivier Kwizera - Price range filtering and sorting
 * @version 2.0
 */
class BrowseFilter {
    /**
     * Initialize the browse filter
     * Sets up the main grid container where items will be displayed
     * If the grid doesn't exist on the page, stop here (nothing to filter)
     * Otherwise, create an empty array to hold all item cards and start initialization
     * 
     * @constructor
     * @author Athanase Abayo
     */
    constructor() {
        // Find the container where all items are displayed
        this.grid = document.getElementById('itemsGrid');
        if (!this.grid) return; // Exit early if grid doesn't exist
        
        // Array to store all item cards for filtering
        this.cards = [];
        this.init();
    }

    /**
     * Initialize filter system and load items
     * This runs once when the page loads
     * Step 1: Grab all existing item cards from the page
     * Step 2: Load any items saved offline (pending listings)
     * Step 3: Wire up the filter dropdowns and search box
     * Step 4: Show all items on screen (no filters applied yet)
     * 
     * @author Athanase Abayo - Core initialization
     * @author Mabinty Mambu - Event handlers
     */
    async init() {
        // Initialize translation system first
        if (window.swapitTranslation && !window.swapitTranslation.isInitialized()) {
            await window.swapitTranslation.init();
        }
        
        // Collect all existing item cards already on the page
        this.cards = Array.from(this.grid.querySelectorAll('.card'));
        
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
            const response = await fetch('/api/listings.php?action=get_all_items', {
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
                        <div class="card__meta">GHS ${item.price || 0} — ${this.escapeHtml(item.location || 'Location not specified')}</div>
                        <div class="card__actions" style="margin-top: 12px; display: flex; gap: 8px;">
                            <button class="btn btn--primary btn--small" onclick="window.location.href='../pages/cart.html'">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            <button class="btn btn--secondary btn--small" onclick="alert('Wishlist feature coming soon!')">
                                <i class="fas fa-heart"></i>
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
