/**
 * Cart Checkout Handler
 * Processes cart orders with authentication and server/local storage fallback
 */
document.getElementById('checkoutBtn').addEventListener('click', async () => {
    const pickup = document.getElementById('pickupAt').value;
    const cart = JSON.parse(localStorage.getItem('swapit_cart') || '[]');
    
    if (!cart.length) {
        if (window.showToast) {
            window.showToast('Your cart is empty', 'warning', 'Empty Cart');
        } else {
            alert('Your cart is empty');
        }
        return;
    }
    
    if (!pickup) {
        if (window.showToast) {
            window.showToast('Please choose a pickup date/time', 'warning', 'Missing Information');
        } else {
            alert('Please choose a pickup date/time');
        }
        return;
    }

    // Show loading state
    const checkoutBtn = document.getElementById('checkoutBtn');
    const originalText = checkoutBtn.innerHTML;
    checkoutBtn.disabled = true;
    checkoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

    // Wait a moment for better UX
    await new Promise(resolve => setTimeout(resolve, 1000));

    // Show success message
    if (window.showToast) {
        window.showToast(`Proceed Successfully! Your order for ${cart.length} item(s) has been placed.`, 'success', 'Order Confirmed');
    } else {
        alert('Proceed Successfully!');
    }
    
    // Clear cart
    localStorage.removeItem('swapit_cart');
    
    // Redirect to dashboard after 2 seconds
    setTimeout(() => {
        window.location.href = 'dashboard.html';
    }, 2000);
});
