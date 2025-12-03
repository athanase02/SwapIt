/**
 * SwapIt Translation System
 * Supports: English, French
 * @author SwapIt Team
 */

class TranslationManager {
    constructor() {
        this.currentLanguage = 'en';
        this.translations = {};
        this.supportedLanguages = {
            'en': { name: 'English', flag: '🇬🇧' },
            'fr': { name: 'Français', flag: '🇫🇷' }
        };
        this.initialized = false;
    }

    /**
     * Initialize translation system
     * Loads saved language preference and translations
     */
    async init() {
        try {
            // Get saved language preference
            const savedLang = localStorage.getItem('swapit_language') || 'en';
            
            // Load translation file for saved language
            await this.loadLanguage(savedLang);
            
            this.initialized = true;
            console.log(`Translation system initialized: ${savedLang}`);
        } catch (error) {
            console.error('Failed to initialize translations:', error);
            // Fallback to English
            await this.loadLanguage('en');
        }
    }

    /**
     * Load translation file for specified language
     * @param {string} langCode - Language code (en, fr)
     */
    async loadLanguage(langCode) {
        try {
            console.log(`Loading language: ${langCode}`);
            
            // Validate language code
            if (!this.supportedLanguages[langCode]) {
                console.warn(`Unsupported language: ${langCode}, falling back to English`);
                langCode = 'en';
            }

            // Load translation JSON file
            const url = `/assets/js/translations/${langCode}.json`;
            console.log(`Fetching translations from: ${url}`);
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`Failed to load ${langCode}.json: ${response.status}`);
            }

            this.translations = await response.json();
            this.currentLanguage = langCode;
            
            console.log(`Loaded ${Object.keys(this.translations).length} translation categories for ${langCode}`);
            
            // Save preference
            localStorage.setItem('swapit_language', langCode);
            
            // Update HTML lang attribute
            document.documentElement.setAttribute('lang', langCode);
            
            // Apply translations to page
            this.applyTranslations();
            
            // Dispatch event for other components
            window.dispatchEvent(new CustomEvent('languageChanged', { 
                detail: { language: langCode } 
            }));
            
            console.log(`Language ${langCode} loaded and applied successfully`);
            
        } catch (error) {
            console.error(`Error loading language ${langCode}:`, error);
            
            // If not English and it failed, try English as fallback
            if (langCode !== 'en') {
                console.log('Attempting to load English as fallback...');
                await this.loadLanguage('en');
            }
        }
    }

    /**
     * Switch to a different language
     * @param {string} langCode - Target language code
     */
    async switchLanguage(langCode) {
        if (langCode === this.currentLanguage) {
            return; // Already using this language
        }

        await this.loadLanguage(langCode);
    }

    /**
     * Get translation for a key
     * @param {string} key - Translation key (e.g., 'nav.home')
     * @param {object} params - Optional parameters for interpolation
     * @returns {string} Translated text
     */
    t(key, params = {}) {
        const keys = key.split('.');
        let value = this.translations;

        // Navigate through nested object
        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                console.warn(`Translation key not found: ${key}`);
                return key; // Return key if translation not found
            }
        }

        // Handle parameter interpolation
        if (typeof value === 'string' && Object.keys(params).length > 0) {
            return value.replace(/\{(\w+)\}/g, (match, param) => {
                return params[param] !== undefined ? params[param] : match;
            });
        }

        return value;
    }

    /**
     * Apply translations to all elements with data-i18n attribute
     */
    applyTranslations() {
        console.log('=== APPLYING TRANSLATIONS ===');
        console.log('Current language:', this.currentLanguage);
        console.log('Available translations:', Object.keys(this.translations).length > 0);
        console.log('Elements with data-i18n:', document.querySelectorAll('[data-i18n]').length);
        
        let translatedCount = 0;
        
        // Translate elements with data-i18n attribute
        document.querySelectorAll('[data-i18n]').forEach(element => {
            const key = element.getAttribute('data-i18n');
            const translation = this.t(key);
            
            if (translation && translation !== key) {
                // Simply replace textContent - icons should be in separate elements
                element.textContent = translation;
                translatedCount++;
            } else {
                console.warn(`No translation for key: ${key}`);
            }
        });

        // Translate placeholders
        document.querySelectorAll('[data-i18n-placeholder]').forEach(element => {
            const key = element.getAttribute('data-i18n-placeholder');
            const translation = this.t(key);
            
            if (translation && translation !== key) {
                element.setAttribute('placeholder', translation);
                translatedCount++;
            }
        });

        // Translate titles
        document.querySelectorAll('[data-i18n-title]').forEach(element => {
            const key = element.getAttribute('data-i18n-title');
            const translation = this.t(key);
            
            if (translation && translation !== key) {
                element.setAttribute('title', translation);
                translatedCount++;
            }
        });

        // Translate aria-labels
        document.querySelectorAll('[data-i18n-aria]').forEach(element => {
            const key = element.getAttribute('data-i18n-aria');
            const translation = this.t(key);
            
            if (translation && translation !== key) {
                element.setAttribute('aria-label', translation);
                translatedCount++;
            }
        });

        // Update page title if data-i18n-page-title exists
        const pageTitleElement = document.querySelector('[data-i18n-page-title]');
        if (pageTitleElement) {
            const key = pageTitleElement.getAttribute('data-i18n-page-title');
            const translation = this.t(key);
            if (translation && translation !== key) {
                document.title = translation;
                translatedCount++;
            }
        }
        
        console.log(`Translations applied: ${translatedCount} elements translated`);
        console.log(`Language: ${this.currentLanguage}, Elements found: ${document.querySelectorAll('[data-i18n]').length}`);
    }

    /**
     * Get current language code
     * @returns {string} Current language code
     */
    getCurrentLanguage() {
        return this.currentLanguage;
    }

    /**
     * Get current language info
     * @returns {object} Language info with name and flag
     */
    getCurrentLanguageInfo() {
        return this.supportedLanguages[this.currentLanguage];
    }

    /**
     * Get all supported languages
     * @returns {object} Object with all supported languages
     */
    getSupportedLanguages() {
        return this.supportedLanguages;
    }

    /**
     * Check if system is initialized
     * @returns {boolean}
     */
    isInitialized() {
        return this.initialized;
    }
}

// Create global instance
window.swapitTranslation = new TranslationManager();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.swapitTranslation.init();
    });
} else {
    window.swapitTranslation.init();
}
