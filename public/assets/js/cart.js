/**
 * Cart Management System
 * Handles shopping cart and wishlist functionality using localStorage
 * 
 * @class CartManager
 * @author Athanase Abayo - Core cart functionality and storage management
 * @author Mabinty Mambu - Wishlist integration and badge counters
 * @author Victoria Ama Nyonator - UI notifications and cart rendering
 * @version 2.0
 */
class CartManager {
    /**
     * Initialize the cart manager
     * @constructor
     * @author Athanase Abayo
     */
    constructor() {
        this.cartKey = 'swapit_cart';
        this.wishlistKey = 'swapit_wishlist';
    }

    /**
     * Get cart items from localStorage
     * @returns {Array} Array of cart items
     * @author Athanase Abayo
     */
    getCart() {
        return JSON.parse(localStorage.getItem(this.cartKey) || '[]');
    }

    /**
     * Get wishlist items from localStorage
     * @returns {Array} Array of wishlist items
     * @author Mabinty Mambu
     */
    getWishlist() {
        return JSON.parse(localStorage.getItem(this.wishlistKey) || '[]');
    }

    /**
     * Save cart items to localStorage
     * @param {Array} cart - Array of cart items to save
     * @author Athanase Abayo
     */
    saveCart(cart) {
        localStorage.setItem(this.cartKey, JSON.stringify(cart));
    }

    /**
     * Save wishlist items to localStorage
     * @param {Array} wishlist - Array of wishlist items to save
     * @author Mabinty Mambu
     */
    saveWishlist(wishlist) {
        localStorage.setItem(this.wishlistKey, JSON.stringify(wishlist));
    }

    /**
     * Add item to cart or increment quantity if already exists
     * @param {Object} item - Item object to add to cart
     * @returns {Object} Updated cart item
     * @author Athanase Abayo - Core implementation
     * @author Victoria Ama Nyonato - Quantity management
     */
    addToCart(item) {
        const cart = this.getCart();
        const exists = cart.find(c => c.id === item.id);
        
        if (exists) {
            exists.qty = (exists.qty || 1) + 1;
        } else {
            item.qty = 1;
            cart.push(item);
        }
        
        this.saveCart(cart);
        this.updateCartCount();
        return item;
    }

    /**
     * Remove item from cart
     * @param {number} index - Index of item to remove
     * @returns {Object} Removed item
     * @author Victoria Ama Nyonato
     */
    removeFromCart(index) {
        const cart = this.getCart();
        const removedItem = cart.splice(index, 1)[0];
        this.saveCart(cart);
        this.updateCartCount();
        return removedItem;
    }

    /**
     * Update item quantity in cart
     * @param {number} index - Index of item to update
     * @param {number} quantity - New quantity value
     * @author Victoria Ama Nyonato
     */
    updateCartQuantity(index, quantity) {
        const cart = this.getCart();
        if (cart[index]) {
            cart[index].qty = parseInt(quantity) || 1;
            this.saveCart(cart);
        }
    }

    /**
     * Toggle item in wishlist (add if not exists, remove if exists)
     * @param {Object} item - Item object to toggle
     * @returns {boolean} True if added, false if removed
     * @author Mabinty Mambu - Core implementation
     * @author Athanase Abayo - Storage optimization
     */
    toggleWishlist(item) {
        let wishlist = this.getWishlist();
        const exists = wishlist.find(w => w.id === item.id);
        
        if (exists) {
            wishlist = wishlist.filter(w => w.id !== item.id);
            this.saveWishlist(wishlist);
            this.updateWishlistCount();
            return false;
        } else {
            wishlist.push(item);
            this.saveWishlist(wishlist);
            this.updateWishlistCount();
            return true;
        }
    }

    /**
     * Check if item is in wishlist
     * @param {string} itemId - Item ID to check
     * @returns {boolean} True if item is in wishlist
     * @author Mabinty Mambu
     */
    isInWishlist(itemId) {
        const wishlist = this.getWishlist();
        return wishlist.some(w => w.id === itemId);
    }

    /**
     * Calculate total cart value
     * @returns {number} Total price of all items in cart
     * @author Athanase Abayo
     */
    calculateTotal() {
        const cart = this.getCart();
        return cart.reduce((sum, item) => {
            const qty = item.qty || 1;
            return sum + (item.price * qty);
        }, 0);
    }

    /**
     * Updates the cart badge counter in the navigation
     * @author Mabinty Mambu - Badge implementation
     * @author Victoria Ama Nyonato - UI updates
     */
    updateCartCount() {
        const cart = this.getCart();
        const countEl = document.getElementById('cartCount');
        if (countEl) {
            const total = cart.reduce((sum, item) => sum + (item.qty || 1), 0);
            countEl.textContent = total;
            countEl.style.display = total > 0 ? 'flex' : 'none';
        }
    }

    /**
     * Updates the wishlist badge counter in the navigation
     * @author Mabinty Mambu
     */
    updateWishlistCount() {
        const wishlist = this.getWishlist();
        const countEl = document.getElementById('wishlistCount');
        if (countEl) {
            countEl.textContent = wishlist.length;
            countEl.style.display = wishlist.length > 0 ? 'flex' : 'none';
        }
    }

    /**
     * Displays a toast notification message to the user
     * @param {string} message - The message to display
     * @author Victoria Ama Nyonato - Notification system implementation
     */
    showNotification(message) {
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.cssText = 'position:fixed;top:20px;right:20px;background:#7ef9ff;color:#0a0b10;padding:12px 24px;border-radius:8px;font-weight:600;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.3);animation:slideIn 0.3s ease';
        document.body.appendChild(notification);
        
        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = '@keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }';
        document.head.appendChild(style);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * Render cart items on cart page
     * @param {HTMLElement} container - Container element to render cart items
     * @author Athanase Abayo - Cart rendering logic
     * @author Victoria Ama Nyonato - UI structure and styling
     */
    renderCart(container) {
        const cart = this.getCart();
        container.innerHTML = '';
        
        if (!cart.length) {
            container.innerHTML = '<p>Your cart is empty.</p>';
            return;
        }
        
        cart.forEach((item, i) => {
            const row = document.createElement('div');
            row.style.display = 'flex';
            row.style.alignItems = 'center';
            row.style.gap = '12px';
            row.style.marginBottom = '12px';
            row.innerHTML = `<img src="${item.img}" style="width:84px;height:64px;object-fit:cover;border-radius:6px" alt=""><div style="flex:1"><strong>${item.title}</strong><div>GHS ${item.price} / day</div></div>`;
            
            const qty = document.createElement('input');
            qty.type = 'number';
            qty.min = 1;
            qty.value = item.qty || 1;
            qty.style.width = '64px';
            qty.addEventListener('change', () => {
                this.updateCartQuantity(i, qty.value);
                this.renderCart(container);
                this.updateCartSummary();
            });
            
            const remove = document.createElement('button');
            remove.className = 'btn';
            remove.textContent = 'Remove';
            remove.addEventListener('click', () => {
                const removedItem = this.removeFromCart(i);
                this.showNotification(removedItem.title + ' removed from cart');
                window.dispatchEvent(new Event('storage'));
                this.renderCart(container);
                this.updateCartSummary();
            });
            
            row.appendChild(qty);
            row.appendChild(remove);
            container.appendChild(row);
        });
        
        this.updateCartSummary();
    }

    /**
     * Calculates and displays the cart summary with individual item totals
     * @author Athanase Abayo - Calculation logic
     * @author Victoria Ama Nyonato - Summary display
     */
    updateCartSummary() {
        const cart = this.getCart();
        const summaryEl = document.getElementById('cartSummary');
        if (!summaryEl) return;
        
        let html = '<h3 style="margin-bottom:1rem">Order Summary</h3>';
        let total = 0;
        
        cart.forEach(item => {
            const qty = item.qty || 1;
            const itemTotal = item.price * qty;
            total += itemTotal;
            html += `
                <div class="summary-row">
                    <span>${item.title} (${qty} day${qty > 1 ? 's' : ''})</span>
                    <span>GHS ${itemTotal.toFixed(2)}</span>
                </div>
            `;
        });
        
        html += `
            <div class="summary-row summary-total">
                <span>Total</span>
                <span>GHS ${total.toFixed(2)}</span>
            </div>
        `;
        
        summaryEl.innerHTML = html;
    }
}

// Initialize global cart manager instance
const cartManager = new CartManager();

/**
 * Legacy function exports for backward compatibility
 * @author Mabinty Mambu - Compatibility layer
 */
function updateCartCount() {
    cartManager.updateCartCount();
}

function updateWishlistCount() {
    cartManager.updateWishlistCount();
}

function showNotification(message) {
    cartManager.showNotification(message);
}

window.cartManager = cartManager;
window.updateCartCount = updateCartCount;
window.updateWishlistCount = updateWishlistCount;
window.showNotification = showNotification;

/**
 * Initialize cart functionality when DOM is ready
 * @author Mabinty Mambu - Dynamic button generation
 * @author Victoria Ama Nyonato - Event handling and interaction
 */
document.addEventListener('DOMContentLoaded', () => {
  /**
   * Initialize badge counters on page load
   */
  cartManager.updateCartCount();
  cartManager.updateWishlistCount();
  
  const grid = document.getElementById('itemsGrid');
  
  /**
   * Dynamically add cart and wishlist buttons to item cards
   * @author Mabinty Mambu - Button generation logic
   * @author Athanase Abayo - Cart integration
   */
  if (grid) {
    const cards = Array.from(grid.querySelectorAll('.card'));
    cards.forEach((card, idx) => {
      if (!card.dataset.id) {
        card.dataset.id = 'item-' + idx + '-' + (card.dataset.title || '').replace(/\s+/g, '-').toLowerCase();
      }
      
      let footer = card.querySelector('.card__meta');
      if (!footer) {
        footer = document.createElement('div');
        footer.className = 'card__meta';
        card.appendChild(footer);
      }
      
      /**
       * Create container for action buttons
       */
      const btnContainer = document.createElement('div');
      btnContainer.style.display = 'flex';
      btnContainer.style.gap = '8px';
      btnContainer.style.marginTop = '8px';
      
      /**
       * Create "Add to Cart" button with click handler
       * @author Athanase Abayo - Cart addition logic
       */
      const cartBtn = document.createElement('button');
      const addToCartText = window.swapitTranslation?.translations?.wishlist?.addToCart || 
                           window.swapitTranslation?.translations?.browse?.addToCart || 
                           'Add to cart';
      cartBtn.textContent = addToCartText;
      cartBtn.className = 'btn';
      cartBtn.style.flex = '1';
      cartBtn.addEventListener('click', () => {
        const item = {
          id: card.dataset.id,
          title: card.dataset.title || card.querySelector('h3')?.textContent || 'Item',
          price: parseFloat(card.dataset.price) || 0,
          location: card.dataset.location || '',
          img: card.querySelector('img')?.src || ''
        };
        
        cartManager.addToCart(item);
        cartManager.showNotification(item.title + ' added to cart!');
        window.dispatchEvent(new Event('storage'));
      });
      
      /**
       * Create "Wishlist" button with heart icon and toggle functionality
       * @author Mabinty Mambu - Wishlist button implementation
       */
      const likeBtn = document.createElement('button');
      likeBtn.innerHTML = '♡';
      likeBtn.className = 'btn btn--icon';
      likeBtn.style.padding = '8px 12px';
      likeBtn.style.fontSize = '1.2rem';
      likeBtn.dataset.itemId = card.dataset.id;
      
      /**
       * Check if item is already in wishlist and update button state
       */
      if (cartManager.isInWishlist(card.dataset.id)) {
        likeBtn.innerHTML = '♥';
        likeBtn.style.color = '#ff7df2';
      }
      
      likeBtn.addEventListener('click', () => {
        const item = {
          id: card.dataset.id,
          title: card.dataset.title || card.querySelector('h3')?.textContent || 'Item',
          price: parseFloat(card.dataset.price) || 0,
          location: card.dataset.location || '',
          img: card.querySelector('img')?.src || ''
        };
        
        const added = cartManager.toggleWishlist(item);
        
        if (added) {
          likeBtn.innerHTML = '♥';
          likeBtn.style.color = '#ff7df2';
          cartManager.showNotification(item.title + ' added to wishlist!');
        } else {
          likeBtn.innerHTML = '♡';
          likeBtn.style.color = '';
          cartManager.showNotification(item.title + ' removed from wishlist');
        }
        
        window.dispatchEvent(new Event('storage'));
      });
      
      btnContainer.appendChild(cartBtn);
      btnContainer.appendChild(likeBtn);
      footer.appendChild(btnContainer);
    });
  }

  /**
   * Render cart items if on cart page
   * @author Athanase Abayo
   */
  const cartList = document.getElementById('cartList');
  if (cartList) {
    cartManager.renderCart(cartList);
  }

  /**
   * Initialize cart summary on page load
   * @author Victoria Ama Nyonato
   */
  if (document.getElementById('cartSummary')) {
    cartManager.updateCartSummary();
  }
});
