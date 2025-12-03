/**
 * Google OAuth Authentication Handler
 * @author Athanase Abayo - Google Sign-In integration
 */

class GoogleAuthHandler {
    constructor() {
        this.clientId = ''; // Will be loaded from config
        this.redirectUri = window.location.origin + '/api/google-callback.php';
        this.scope = 'email profile';
        this.initialized = false;
    }

    /**
     * Initialize Google OAuth
     */
    async init() {
        if (this.initialized) return;

        try {
            // Load Google OAuth client ID from config
            const response = await fetch('/api/auth.php?action=get_google_config');
            const config = await response.json();
            
            if (config.success && config.clientId) {
                this.clientId = config.clientId;
                this.initialized = true;
                console.log('Google OAuth initialized');
            } else {
                console.warn('Google OAuth not configured on server');
            }
        } catch (error) {
            console.error('Failed to initialize Google OAuth:', error);
        }
    }

    /**
     * Start Google OAuth flow
     */
    signIn() {
        if (!this.clientId) {
            this.showMessage('Google Sign-In is not configured yet. Please use email/password login.', 'error');
            return;
        }

        // Build OAuth URL
        const params = new URLSearchParams({
            client_id: this.clientId,
            redirect_uri: this.redirectUri,
            response_type: 'code',
            scope: this.scope,
            access_type: 'offline',
            prompt: 'consent'
        });

        const authUrl = `https://accounts.google.com/o/oauth2/v2/auth?${params.toString()}`;
        
        // Redirect to Google OAuth
        window.location.href = authUrl;
    }

    /**
     * Handle OAuth callback
     */
    async handleCallback(code) {
        try {
            const response = await fetch('../api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'google_login',
                    code: code
                })
            });

            const result = await response.json();

            if (result.success) {
                this.showMessage('Successfully signed in with Google!', 'success');
                // Redirect to dashboard after short delay
                setTimeout(() => {
                    window.location.href = '/pages/dashboard.html';
                }, 1000);
            } else {
                this.showMessage(result.message || 'Google sign-in failed', 'error');
            }
        } catch (error) {
            console.error('Google OAuth callback error:', error);
            this.showMessage('An error occurred during Google sign-in', 'error');
        }
    }

    /**
     * Show message to user
     */
    showMessage(message, type) {
        const errorDiv = document.getElementById('errorMessage');
        const successDiv = document.getElementById('successMessage');

        if (type === 'error' && errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            if (successDiv) successDiv.style.display = 'none';
        } else if (type === 'success' && successDiv) {
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            if (errorDiv) errorDiv.style.display = 'none';
        }
    }
}

// Export for use in login/signup pages
window.GoogleAuthHandler = GoogleAuthHandler;
