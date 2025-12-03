/**
 * Signup Page Script
 * Handles user registration with validation and authentication
 */
document.addEventListener('DOMContentLoaded', () => {
    const signupForm = document.getElementById('signupForm');

    // Password toggle functionality
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            input.type = input.type === 'password' ? 'text' : 'password';
        });
    });

    // Setup Google OAuth
    const script = document.createElement('script');
    script.src = '../assets/js/google-auth.js';
    script.onload = () => {
        const googleAuth = new GoogleAuthHandler();
        googleAuth.init();

        // Setup Google signup button
        const googleBtn = document.getElementById('googleSignupBtn');
        if (googleBtn) {
            googleBtn.addEventListener('click', () => {
                googleAuth.signIn();
            });
        }
    };
    document.head.appendChild(script);

    // Handle signup form submission
    signupForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const errorMessage = document.getElementById('errorMessage');
        const successMessage = document.getElementById('successMessage');
        const submitBtn = signupForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Hide any previous messages
        errorMessage.style.display = 'none';
        successMessage.style.display = 'none';
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creating Account...';

        try {
            const firstName = document.getElementById('signupFullName').value.trim();
            const lastName = document.getElementById('signupLastName').value.trim();
            const email = document.getElementById('signupEmail').value.trim();
            const password = document.getElementById('signupPassword').value;
            const fullName = lastName ? `${firstName} ${lastName}` : firstName;

            // Validate inputs
            if (!firstName) {
                throw new Error('Please enter your first name');
            }

            if (!email) {
                throw new Error('Please enter your email address');
            }
            
            if (!email.includes('@')) {
                throw new Error('Please enter a valid email address');
            }

            if (password.length < 6) {
                throw new Error('Password must be at least 6 characters');
            }

            const agreeCheckbox = document.getElementById('agree');
            if (!agreeCheckbox.checked) {
                throw new Error('Please agree to the Terms & Conditions');
            }

            // Use our custom auth client
            const result = await window.swapitAuth.signUp(email, password, fullName);

            if (!result.success) {
                throw new Error(result.message || 'Failed to create account');
            }

            successMessage.textContent = 'Account created successfully! Redirecting to login...';
            successMessage.style.display = 'block';
            
            setTimeout(() => {
                window.location.href = 'login.html';
            }, 2000);
        } catch (error) {
            errorMessage.textContent = error.message || 'Failed to create account. Please try again.';
            errorMessage.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    });
});
