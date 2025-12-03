/**
 * SwapIt Authentication Client
 * Handles user authentication, session management, and API communication
 * 
 * @class SwapItAuth
 * @author Athanase Abayo - Core authentication architecture and session management
 * @author Mabinty Mambu - API integration and error handling
 * @author Olivier Kwizera - Security enhancements and token management
 */
class SwapItAuth {
    /**
     * Initialize the authentication client
     * @constructor
     * @author Athanase Abayo
     */
    constructor() {
        // API is at parent directory level - use relative path
        this.apiBase = '/api';
        this.user = null;
        this.init();
    }

    /**
     * Initialize authentication system and verify existing session
     * @author Athanase Abayo
     */
    async init() {
        // Verify user session on page load
        await this.checkSession();
    }

    /**
     * Check if user has an active session with the server
     * @returns {Promise<Object|null>} User object if authenticated, null otherwise
     * @author Athanase Abayo - Core implementation
     * @author Mabinty Mambu - Error handling
     */
    async checkSession() {
        try {
            const response = await fetch(`${this.apiBase}/auth.php?action=check_auth`, {
                credentials: 'include'
            });
            const data = await response.json();
            if (data.success && data.user) {
                this.user = data.user;
                return data.user;
            }
            return null;
        } catch (error) {
            console.error('Session check failed:', error);
            return null;
        }
    }

    /**
     * Register a new user account
     * @param {string} email - User's email address
     * @param {string} password - User's password
     * @param {string} fullName - User's full name
     * @returns {Promise<Object>} Result object with success status and user data
     * @author Mabinty Mambu - User registration implementation
     * @author Victoria Ama Nyonato - Input validation
     */
    async signUp(email, password, fullName) {
        try {
            const formData = new FormData();
            formData.append('action', 'signup');
            formData.append('email', email);
            formData.append('password', password);
            formData.append('full_name', fullName);

            const response = await fetch(`${this.apiBase}/auth.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            // Check if response is OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Get response text first to check if it's valid JSON
            const text = await response.text();
            if (!text || text.trim() === '') {
                throw new Error('Server returned empty response');
            }

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text.substring(0, 500));
                throw new Error('Server returned invalid response. Please check server logs.');
            }

            if (data.success) {
                this.user = data.user;
            }
            return data;
        } catch (error) {
            console.error('Signup error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Authenticate user with email and password
     * OWASP #7: Authentication - Client-side rate limit feedback
     * 
     * @param {string} email - User's email address
     * @param {string} password - User's password
     * @returns {Promise<Object>} Result object with success status and user data
     * @author Athanase Abayo - Core login implementation
     * @author Mabinty Mambu - Session handling
     * @author Olivier Kwizera - Rate limit handling
     */
    async signIn(email, password) {
        try {
            const formData = new FormData();
            formData.append('action', 'login');
            formData.append('email', email);
            formData.append('password', password);

            const response = await fetch(`${this.apiBase}/auth.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            // Check if response is OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Get response text first to check if it's valid JSON
            const text = await response.text();
            if (!text || text.trim() === '') {
                throw new Error('Server returned empty response');
            }

            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text.substring(0, 500));
                throw new Error('Server returned invalid response. Please check server logs.');
            }

            if (data.success) {
                this.user = data.user;
                console.log('Login successful');
            } else if (data.locked) {
                // Account temporarily locked due to too many attempts
                const minutesRemaining = Math.ceil((data.retry_after - Math.floor(Date.now() / 1000)) / 60);
                data.message = `Account locked due to too many failed attempts. Please try again in ${minutesRemaining} minute(s).`;
            } else if (data.remaining_attempts !== undefined) {
                // Show remaining attempts warning
                if (data.remaining_attempts <= 2 && data.remaining_attempts > 0) {
                    data.message += ` (${data.remaining_attempts} attempt(s) remaining before account lock)`;
                }
            }
            return data;
        } catch (error) {
            console.error('Login error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Log out the current user and clear session
     * @returns {Promise<Object>} Result object with success status
     * @author Olivier Kwizera - Logout implementation and session cleanup
     */
    async signOut() {
        try {
            const formData = new FormData();
            formData.append('action', 'logout');

            const response = await fetch(`${this.apiBase}/auth.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            const data = await response.json();
            if (data.success) {
                this.user = null;
            }
            return data;
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Initiate password reset process for user
     * @param {string} email - User's email address
     * @returns {Promise<Object>} Result object with success status
     * @author Mabinty Mambu - Password reset functionality
     */
    async resetPassword(email) {
        try {
            const formData = new FormData();
            formData.append('action', 'reset_password');
            formData.append('email', email);

            const response = await fetch(`${this.apiBase}/auth.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            return await response.json();
        } catch (error) {
            return { success: false, message: error.message };
        }
    }

    /**
     * Update user profile information
     * @param {Object} updates - Object containing fields to update (e.g., avatar_url, full_name)
     * @returns {Promise<Object>} Result object with success status and updated user data
     * @author Victoria Ama Nyonato - Profile update implementation
     * @author Athanase Abayo - Data persistence
     */
    async updateProfile(updates) {
        try {
            const formData = new FormData();
            formData.append('action', 'update_profile');
            Object.keys(updates).forEach(key => {
                formData.append(key, updates[key]);
            });

            const response = await fetch(`${this.apiBase}/profile.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            // Check if response is OK
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            // Try to parse JSON
            const text = await response.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                console.error('Invalid JSON response:', text.substring(0, 200));
                throw new Error('Server returned invalid response. Please check your image size and try again.');
            }

            if (data.success && data.user) {
                this.user = data.user;
            }
            return data;
        } catch (error) {
            console.error('Update profile error:', error);
            return { success: false, message: error.message };
        }
    }

    /**
     * Get current user object
     * @returns {Object|null} Current user data or null if not authenticated
     * @author Athanase Abayo
     */
    getUser() {
        return this.user;
    }

    /**
     * Check if user is currently authenticated
     * @returns {boolean} True if user is logged in, false otherwise
     * @author Athanase Abayo
     */
    isAuthenticated() {
        return this.user !== null;
    }
}

/**
 * Initialize global authentication client instance
 */
window.swapitAuth = new SwapItAuth();
window.SWAPIT_AUTH_READY = true;
