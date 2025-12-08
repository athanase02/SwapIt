/**
 * Navigation Authentication State Manager for Topnav Pages
 * Dynamically updates navigation menu based on user authentication status
 * Used by: wishlist.html, cart.html, dashboard.html
 */

/**
 * Update Topnav Navigation Based on Authentication State
 */
async function updateTopnavAuthState() {
  try {
    // Wait for auth client to be ready
    if (!window.swapitAuth) {
      console.log('Auth client not ready yet');
      return;
    }

    const user = await window.swapitAuth.checkSession();
    const mainNavMenu = document.getElementById('mainNavMenu');
    
    if (!mainNavMenu) {
      console.log('Main nav menu not found');
      return;
    }

    if (user) {
      // User is LOGGED IN - show authenticated menu
      // Check if menu already has items to avoid overwriting badges
      const hasMenuItems = mainNavMenu.querySelectorAll('li').length > 0;
      
      if (!hasMenuItems) {
        mainNavMenu.innerHTML = `
          <li><a href="browse.html"><i class="fas fa-search"></i> <span data-i18n="nav.browse">Browse</span></a></li>
          <li><a href="messages.html"><i class="fas fa-comments"></i> <span data-i18n="nav.messages">Messages</span> <span class="messages-badge">0</span></a></li>
          <li><a href="requests.html"><i class="fas fa-exchange-alt"></i> <span data-i18n="nav.requests">Requests</span> <span class="requests-badge">0</span></a></li>
          <li><a href="add-listing.html"><i class="fas fa-plus-circle"></i> <span data-i18n="nav.addListing">Add Listing</span></a></li>
          <li><a href="cart.html"><i class="fas fa-shopping-cart"></i> <span data-i18n="nav.cart">Cart</span></a></li>
          <li><a href="dashboard.html"><i class="fas fa-tachometer-alt"></i> <span data-i18n="nav.dashboard">Dashboard</span></a></li>
        `;
        // Apply translations to the newly inserted navigation
        window.swapitTranslation?.applyTranslations();
      }
    } else {
      // User is LOGGED OUT - redirect to home page
      // These pages are for authenticated users only
      console.log('User not authenticated, redirecting to home...');
      window.location.href = '../home.html';
    }
  } catch (error) {
    console.error('Failed to update topnav auth state:', error);
    // On error, redirect to home page for safety
    window.location.href = '/home.html';
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', updateTopnavAuthState);
} else {
  updateTopnavAuthState();
}
