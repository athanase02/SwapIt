/**
 * Language Switcher Component
 * Creates and manages the language dropdown in navigation
 */

class LanguageSwitcher {
    constructor() {
        this.currentLang = 'en';
        this.dropdownVisible = false;
        this.container = null;
    }

    /**
     * Initialize the language switcher
     * @param {string} containerId - ID of the container element
     */
    async init(containerId = 'languageSwitcher') {
        this.container = document.getElementById(containerId);
        
        if (!this.container) {
            console.warn(`Language switcher container #${containerId} not found`);
            return;
        }

        // Wait for translation manager to be ready
        if (window.swapitTranslation) {
            // Wait for initialization if not ready
            let retries = 0;
            while (!window.swapitTranslation.isInitialized() && retries < 50) {
                await new Promise(resolve => setTimeout(resolve, 100));
                retries++;
            }
            
            this.currentLang = window.swapitTranslation.getCurrentLanguage();
        }

        // Render the switcher
        this.render();

        // Listen for language changes
        window.addEventListener('languageChanged', (e) => {
            this.currentLang = e.detail.language;
            this.render();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (this.container && !this.container.contains(e.target)) {
                this.hideDropdown();
            }
        });
    }

    /**
     * Render the language switcher UI
     */
    render() {
        if (!this.container) return;

        const languages = window.swapitTranslation ? 
            window.swapitTranslation.getSupportedLanguages() : {};
        
        const currentLangInfo = languages[this.currentLang] || { name: 'English', flag: '🇬🇧' };

        this.container.innerHTML = `
            <div class="language-switcher">
                <button class="language-switcher__button" data-action="toggle" aria-label="Change language" aria-expanded="${this.dropdownVisible}">
                    <span class="language-switcher__flag">${currentLangInfo.flag}</span>
                    <span class="language-switcher__name">${currentLangInfo.name}</span>
                    <i class="fas fa-chevron-down language-switcher__icon"></i>
                </button>
                <div class="language-switcher__dropdown ${this.dropdownVisible ? 'visible' : ''}">
                    ${Object.entries(languages).map(([code, info]) => `
                        <button 
                            class="language-switcher__option ${code === this.currentLang ? 'active' : ''}"
                            data-action="select"
                            data-lang="${code}"
                            aria-label="Switch to ${info.name}"
                        >
                            <span class="language-switcher__flag">${info.flag}</span>
                            <span class="language-switcher__name">${info.name}</span>
                            ${code === this.currentLang ? '<i class="fas fa-check"></i>' : ''}
                        </button>
                    `).join('')}
                </div>
            </div>
        `;

        // Attach event listeners after rendering
        this.attachEventListeners();
    }

    /**
     * Attach event listeners to the rendered elements
     */
    attachEventListeners() {
        if (!this.container) return;

        // Toggle button
        const toggleBtn = this.container.querySelector('[data-action="toggle"]');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });
        }

        // Language option buttons
        const optionBtns = this.container.querySelectorAll('[data-action="select"]');
        optionBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const langCode = btn.getAttribute('data-lang');
                if (langCode) {
                    this.selectLanguage(langCode);
                }
            });
        });
    }

    /**
     * Toggle dropdown visibility
     */
    toggleDropdown() {
        console.log('Toggle dropdown clicked, current state:', this.dropdownVisible);
        this.dropdownVisible = !this.dropdownVisible;
        this.render();
    }

    /**
     * Hide dropdown
     */
    hideDropdown() {
        if (this.dropdownVisible) {
            this.dropdownVisible = false;
            this.render();
        }
    }

    /**
     * Select a language
     * @param {string} langCode - Language code to switch to
     */
    async selectLanguage(langCode) {
        console.log('Selecting language:', langCode);
        
        if (!window.swapitTranslation) {
            console.error('Translation manager not available');
            return;
        }

        try {
            await window.swapitTranslation.switchLanguage(langCode);
            console.log('Language switched successfully to:', langCode);
        } catch (error) {
            console.error('Error switching language:', error);
        }
        
        this.hideDropdown();
    }
}

// Create global instance
window.languageSwitcher = new LanguageSwitcher();

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.languageSwitcher.init();
    });
} else {
    window.languageSwitcher.init();
}
