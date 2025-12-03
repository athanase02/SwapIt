/**
 * Add Listing Form Handler
 * Creates new item listings with image upload and server/local storage fallback
 */

// Initialize translations when page loads
(async function initializeAddListingPage() {
    if (window.swapitTranslation && !window.swapitTranslation.isInitialized()) {
        await window.swapitTranslation.init();
    }
})();

document.getElementById('listingForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const category = document.getElementById('category').value;
    const price = parseFloat(document.getElementById('price').value) || 0;
    const location = document.getElementById('location').value.trim() || 'Unknown';

    // Retrieve selected image file
    const f = document.getElementById('imageInput').files[0];
    let imageUrl = null;

    // Convert image file to base64 data URL
    if (f) {
        try {
            imageUrl = await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => resolve(reader.result);
                reader.onerror = reject;
                reader.readAsDataURL(f);
            });
        } catch (err) {
            console.error('Failed to read image file', err);
            imageUrl = null;
        }
    }

    try {
        // Wait for auth to be fully initialized with retry logic
        let retries = 0;
        while (!window.swapitAuth && retries < 10) {
            await new Promise(resolve => setTimeout(resolve, 100));
            retries++;
        }

        if (!window.swapitAuth) {
            throw new Error('Authentication client not available');
        }

        // Verify user authentication before creating listing
        const user = await window.swapitAuth.checkSession();
        if (!user) {
            if (window.showToast) {
                window.showToast('You must be logged in to create a listing.', 'warning', 'Authentication Required');
            } else {
                alert('You must be logged in to create a listing.');
            }
            setTimeout(() => window.location.href = 'login.html', 2000);
            return;
        }

        // Show loading state
        const submitBtn = document.getElementById('submitBtn');
        const form = document.getElementById('listingForm');
        const originalBtnText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Listing...';
        submitBtn.classList.add('loading');
        submitBtn.disabled = true;

        // Prepare listing data for server submission
        const formData = new FormData();
        formData.append('action', 'create_listing');
        formData.append('title', title);
        formData.append('description', description);
        formData.append('category', category);
        formData.append('price', price);
        formData.append('location', location);
        if (imageUrl) {
            formData.append('image_url', imageUrl);
        }

        const response = await fetch('/api/listings.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to create listing');
        }

        if (window.showToast) {
            window.showToast('Your listing has been created successfully!', 'success', 'Listing Created');
        } else {
            alert('Your listing has been created successfully!');
        }
        setTimeout(() => window.location.href = '/dashboard', 1500);
    } catch (err) {
        console.error('Create listing error:', err);
        
        // Remove loading state
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-plus-circle"></i> Create Listing';
            submitBtn.classList.remove('loading');
            submitBtn.disabled = false;
        }
        
        // Fallback: save to localStorage if server fails
        const pending = JSON.parse(localStorage.getItem('swapit_pending_items') || '[]');
        pending.push({ 
            id: 'local-' + Date.now(), 
            title, 
            description, 
            category, 
            price, 
            location, 
            image_url: imageUrl,
            created_at: new Date().toISOString()
        });
        localStorage.setItem('swapit_pending_items', JSON.stringify(pending));
        
        if (window.showToast) {
            window.showToast('Could not save to server. Saved locally for this session.', 'warning', 'Offline Mode');
        } else {
            alert('Could not save to server. Saved locally for this session.');
        }
        setTimeout(() => window.location.href = '/dashboard', 2000);
    }
});
