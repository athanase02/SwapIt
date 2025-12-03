/**
 * Mobile Navigation Drawer Controller
 */
const navToggle = document.getElementById('navToggle');
const navDrawer = document.getElementById('navDrawer');
if (navToggle && navDrawer) {
  const setOpen = (open) => {
    navDrawer.setAttribute('data-open', open ? 'true' : 'false');
    navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
  };
  setOpen(false);
  navToggle.addEventListener('click', () => {
    const open = navDrawer.getAttribute('data-open') === 'true';
    setOpen(!open);
  });
  navDrawer.querySelectorAll('a').forEach(a => a.addEventListener('click', () => setOpen(false)));
  window.addEventListener('resize', () => { if (window.innerWidth > 900) setOpen(false); });
}

/**
 * Smooth Scroll for Anchor Links
 * Handles smooth scrolling to page sections with header offset
 */
(function smoothAnchor(){
  const header = document.getElementById('siteHeader');
  const links = document.querySelectorAll('a[href^="#"]:not([href="#"])');
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  function topOf(el){
    const rect = el.getBoundingClientRect(), st = window.pageYOffset || document.documentElement.scrollTop;
    return rect.top + st - (header?.offsetHeight||0) - 12;
  }
  links.forEach(link=>{
    link.addEventListener('click', e=>{
      const id = link.getAttribute('href'), target = document.querySelector(id);
      if(!target) return;
      e.preventDefault();
      window.scrollTo({ top: topOf(target), behavior: reduced ? 'auto' : 'smooth' });
      target.setAttribute('tabindex','-1'); target.focus({preventScroll:true}); setTimeout(()=>target.removeAttribute('tabindex'), 1000);
    });
  });
})();

/**
 * FAQ Accordion - Only one item open at a time
 */
document.querySelectorAll('.faq details').forEach(d => {
  d.addEventListener('toggle', () => {
    if (d.open) document.querySelectorAll('.faq details').forEach(o => { if (o !== d) o.open = false; });
  });
});

/**
 * Form Submission Handler
 * Simulates form submission with status feedback
 */
function fakeSubmit(form, statusEl, ok="Thanks! We'll be in touch."){
  form?.addEventListener('submit', (e)=>{
    e.preventDefault();
    statusEl.textContent = 'Sending…';
    setTimeout(()=>{ statusEl.textContent = ok; form.reset(); }, 600);
  });
}
fakeSubmit(document.getElementById('supportForm'), document.getElementById('formStatus'));

/**
 * Account Dropdown Menu Toggle
 * @param {Event} event - Click event object
 */
function toggleAccountDropdown(event) {
  if (event) {
    event.stopPropagation();
  }
  const dropdown = document.querySelector('.account-dropdown');
  if (dropdown) {
    dropdown.classList.toggle('active');
  }
}

/**
 * Close account dropdown when clicking outside of it
 */
document.addEventListener('click', function(event) {
  const dropdown = document.querySelector('.account-dropdown');
  if (dropdown && !dropdown.contains(event.target)) {
    dropdown.classList.remove('active');
  }
});

fakeSubmit(document.getElementById('newsletterForm'), document.getElementById('newsStatus'), 'Subscribed 🎉');

// Update copyright year dynamically
document.getElementById('year').textContent = new Date().getFullYear();

/**
 * Update Navigation Based on Authentication State
 * Dynamically switches between public menu (logged-out) and authenticated menu (logged-in)
 */
async function updateNavAuthState() {
  try {
    // Wait for auth client to be ready
    if (!window.swapitAuth) {
      return;
    }

    const user = await window.swapitAuth.checkSession();
    const accountDropdown = document.querySelector('.account-dropdown');
    const accountMenu = document.querySelector('.account-dropdown-menu');
    const mainNavMenu = document.getElementById('mainNavMenu');
    const navDrawer = document.getElementById('navDrawer');
    
    if (!accountDropdown || !accountMenu) return;

    if (user) {
      // User is LOGGED IN - show authenticated menu
      
      // Update main navigation menu
      if (mainNavMenu) {
        mainNavMenu.innerHTML = `
          <a href="pages/dashboard.html"><i class="fas fa-tachometer-alt"></i> <span data-i18n="nav.dashboard">Dashboard</span></a>
          <a href="pages/browse.html"><i class="fas fa-search"></i> <span data-i18n="nav.browse">Browse Items</span></a>
          <a href="pages/add-listing.html"><i class="fas fa-plus-circle"></i> <span data-i18n="nav.addListing">Add Listing</span></a>
          <a href="pages/wishlist.html"><i class="fas fa-heart"></i> <span data-i18n="nav.wishlist">Wishlist</span></a>
          <a href="pages/cart.html"><i class="fas fa-shopping-cart"></i> <span data-i18n="nav.cart">Cart</span></a>
        `;
        window.swapitTranslation?.applyTranslations();
      }

      // Update mobile drawer menu
      if (navDrawer) {
        navDrawer.innerHTML = `
          <a href="pages/dashboard.html"><i class="fas fa-tachometer-alt"></i> <span data-i18n="nav.dashboard">Dashboard</span></a>
          <a href="pages/browse.html"><i class="fas fa-search"></i> <span data-i18n="nav.browse">Browse Items</span></a>
          <a href="pages/add-listing.html"><i class="fas fa-plus-circle"></i> <span data-i18n="nav.addListing">Add Listing</span></a>
          <a href="pages/wishlist.html"><i class="fas fa-heart"></i> <span data-i18n="nav.wishlist">Wishlist</span></a>
          <a href="pages/cart.html"><i class="fas fa-shopping-cart"></i> <span data-i18n="nav.cart">Cart</span></a>
          <hr style="margin: 16px 0; border: none; border-top: 1px solid #1e2434;">
          <a href="pages/profile.html"><i class="fas fa-user"></i> <span data-i18n="nav.profile">Profile</span></a>
          <a href="#" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt"></i> <span data-i18n="nav.logout">Logout</span></a>
        `;
        window.swapitTranslation?.applyTranslations();
      }

      // Update account dropdown
      accountMenu.innerHTML = `
        <a href="pages/dashboard.html"><i class="fas fa-tachometer-alt"></i> <span data-i18n="nav.dashboard">Dashboard</span></a>
        <a href="pages/profile.html"><i class="fas fa-user"></i> <span data-i18n="nav.profile">Profile</span></a>
        <a href="pages/add-listing.html"><i class="fas fa-plus"></i> <span data-i18n="nav.addListing">Add Listing</span></a>
        <hr style="margin: 8px 0; border: none; border-top: 1px solid #1e2434;">
        <a href="#" onclick="handleLogout(event)"><i class="fas fa-sign-out-alt"></i> <span data-i18n="nav.logout">Logout</span></a>
      `;
      window.swapitTranslation?.applyTranslations();
    } else {
      // User is LOGGED OUT - show public menu
      
      // Update main navigation menu
      if (mainNavMenu) {
        mainNavMenu.innerHTML = `
          <a href="#home" data-i18n="nav.home">Home</a>
          <a href="#how" data-i18n="nav.how">How it works</a>
          <a href="#stories" data-i18n="nav.stories">Stories</a>
          <a href="#about-us" data-i18n="nav.about">About Us</a>
          <a href="#pricing" data-i18n="nav.pricing">Plans</a>
          <a href="#news" data-i18n="nav.news">News</a>
          <a href="#faq" data-i18n="nav.faq">FAQ</a>
          <a href="#contact" data-i18n="nav.contact">Contact</a>
        `;
        window.swapitTranslation?.applyTranslations();
      }

      // Update mobile drawer menu
      if (navDrawer) {
        navDrawer.innerHTML = `
          <a href="#home" data-i18n="nav.home">Home</a>
          <a href="#how" data-i18n="nav.how">How it works</a>
          <a href="#stories" data-i18n="nav.stories">Stories</a>
          <a href="#about-us" data-i18n="nav.about">About Us</a>
          <a href="#pricing" data-i18n="nav.pricing">Plans</a>
          <a href="#news" data-i18n="nav.news">News</a>
          <a href="#faq" data-i18n="nav.faq">FAQ</a>
          <a href="#contact" data-i18n="nav.contact">Contact</a>
          <hr style="margin: 16px 0; border: none; border-top: 1px solid #1e2434;">
          <div class="nav__auth">
              <a class="btn btn--outline w-100 mb-2" href="pages/login.html" data-i18n="nav.login">Log in</a>
              <a class="btn btn--primary w-100" href="pages/signup.html" data-i18n="nav.signup">Sign up</a>
          </div>
        `;
        window.swapitTranslation?.applyTranslations();
      }

      // Update account dropdown
        accountMenu.innerHTML = `
          <a href="pages/login.html"><i class="fas fa-sign-in-alt"></i> <span data-i18n="nav.login">Login</span></a>
          <a href="pages/signup.html"><i class="fas fa-user-plus"></i> <span data-i18n="nav.signup">Sign Up</span></a>
        `;
        window.swapitTranslation?.applyTranslations();
    }
  } catch (error) {
    console.error('Failed to update nav auth state:', error);
  }
}

/**
 * Handle Logout
 * Logs out user and redirects to public home page
 */
async function handleLogout(event) {
  event.preventDefault();
  try {
    await window.swapitAuth.signOut();
    // Redirect to public home page after logout
    window.location.href = '/home.html';
  } catch (error) {
    console.error('Logout failed:', error);
    if (window.showToast) {
      window.showToast('Failed to logout. Please try again.', 'error', 'Logout Failed');
    } else {
      alert('Failed to logout. Please try again.');
    }
  }
}

// Update nav when auth is ready
if (window.swapitAuth) {
  updateNavAuthState();
} else {
  // Wait for auth to load
  window.addEventListener('load', () => {
    setTimeout(updateNavAuthState, 100);
  });
}
