/**
 * Login Page Script
 * Handles user authentication and session management
 * 
 * @class LoginHandler
 * @author Athanase Abayo - Core authentication logic and form handling
 * @author Olivier Kwizera - UI updates and error messaging
 * @author Victoria Ama Nyonator - Password toggle functionality
 * @version 2.0
 */
class LoginHandler {
    /**
     * Initialize the login handler
     * @constructor
     * @author Athanase Abayo
     */
    constructor() {
        this.init();
    }

    /**
     * Initialize form handlers and UI elements
     * @author Victoria Ama Nyonator - Password toggle
     * @author Athanase Abayo - Form submission
     */
    init() {
        const loginForm = document.getElementById('loginForm');

        /**
         * Password toggle functionality
         * @author Team Member 3
         */
        this.setupPasswordToggle();

        /**
         * Initialize Google OAuth
         */
        this.setupGoogleAuth();

        /**
         * Handle login form submission
         * @author Athanase Nsabimana - Core authentication
         * @author Team Member 2 - Error handling and UI feedback
         */
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleLogin();
        });
    }

    /**
     * Setup Google OAuth integration
     * @author Athanase Abayo
     */
    setupGoogleAuth() {
        // Load Google auth handler script
        const script = document.createElement('script');
        script.src = '../assets/js/google-auth.js';
        script.onload = () => {
            const googleAuth = new GoogleAuthHandler();
            googleAuth.init();

            // Setup Google login button
            const googleBtn = document.getElementById('googleLoginBtn');
            if (googleBtn) {
                googleBtn.addEventListener('click', () => {
                    googleAuth.signIn();
                });
            }
        };
        document.head.appendChild(script);
    }

    /**
     * Setup password visibility toggle buttons
     * @author Victoria Ama Nyonator
     */
    setupPasswordToggle() {
        document.querySelectorAll('.password-toggle').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const input = document.getElementById(targetId);
                input.type = input.type === 'password' ? 'text' : 'password';
            });
        });
    }

    /**
     * Handle login form submission
     * @returns {Promise<void>}
     * @author Athanase Abayo - Authentication flow
     * @author Olivier Kwizera - Validation and error handling
     */
    async handleLogin() {
        const loginForm = document.getElementById('loginForm');
        const errorMessage = document.getElementById('errorMessage');
        const submitBtn = loginForm.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Hide any previous errors
        errorMessage.style.display = 'none';
        
        submitBtn.disabled = true;
        submitBtn.textContent = 'Logging in...';

        try {
            const email = document.getElementById('loginEmail').value.trim();
            const password = document.getElementById('loginPassword').value;

            // Input validation
            if (!email) {
                throw new Error('Please enter your email address');
            }

            if (!password) {
                throw new Error('Please enter your password');
            }

            // Use our custom auth client
            const result = await window.swapitAuth.signIn(email, password);

            if (!result.success) {
                throw new Error(result.message || 'Failed to log in');
            }

            // Redirect to dashboard on success
            window.location.href = 'dashboard.html';
        } catch (error) {
            errorMessage.textContent = error.message || 'Failed to log in. Please check your credentials.';
            errorMessage.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    }
}

/**
 * Initialize login handler when DOM is ready
 * @author Athanase Abayo
 */
document.addEventListener('DOMContentLoaded', () => {
    new LoginHandler();
});
