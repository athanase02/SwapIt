/**
 * Reset Password Page Script
 * Handles password reset token validation and new password submission
 */
document.addEventListener('DOMContentLoaded', async () => {
    // Wait for auth to be ready
    while (!window.swapitAuth) {
        await new Promise(resolve => setTimeout(resolve, 50));
    }
    
    const resetForm = document.getElementById('resetForm');

    resetForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const submitBtn = resetForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Sending...';

        try {
            const email = document.getElementById('resetEmail').value.trim();

            if (!email) {
                throw new Error('Please enter your email address');
            }

            // Show success modal
            document.getElementById('successModal').classList.add('show');
            
            // Reset button state
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        } catch (error) {
            alert(error.message || 'Could not send reset email. Please try again.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
});
