import { Controller } from '@hotwired/stimulus';

/*
 * Cookie Consent Controller
 *
 * Manages GDPR/RODO compliant cookie consent banner
 * Stores user preferences in localStorage and cookies
 *
 * Usage:
 * <div data-controller="cookie-consent">...</div>
 */
export default class extends Controller {
    static targets = ['banner', 'preferences'];
    static values = {
        privacyUrl: String,
        termsUrl: String
    };

    connect() {
        // Check if user has already made a choice
        const consent = this.getConsent();

        if (!consent) {
            // Show banner after a short delay for better UX
            setTimeout(() => {
                this.showBanner();
            }, 1000);
        } else {
            // Apply stored preferences
            this.applyConsent(consent);
        }
    }

    showBanner() {
        if (this.hasBannerTarget) {
            this.bannerTarget.classList.add('show');
            this.bannerTarget.setAttribute('aria-hidden', 'false');
        }
    }

    hideBanner() {
        if (this.hasBannerTarget) {
            this.bannerTarget.classList.remove('show');
            this.bannerTarget.setAttribute('aria-hidden', 'true');
        }
    }

    showPreferences() {
        if (this.hasPreferencesTarget) {
            this.preferencesTarget.classList.add('show');
            this.preferencesTarget.setAttribute('aria-hidden', 'false');
        }
        this.hideBanner();
    }

    hidePreferences() {
        if (this.hasPreferencesTarget) {
            this.preferencesTarget.classList.remove('show');
            this.preferencesTarget.setAttribute('aria-hidden', 'true');
        }
    }

    acceptAll(event) {
        event.preventDefault();
        const consent = {
            necessary: true,
            preferences: true,
            analytics: false, // Set to true if you add analytics
            marketing: false  // Set to true if you add marketing cookies
        };
        this.saveConsent(consent);
        this.applyConsent(consent);
        this.hideBanner();
        this.hidePreferences();
    }

    acceptNecessary(event) {
        event.preventDefault();
        const consent = {
            necessary: true,
            preferences: false,
            analytics: false,
            marketing: false
        };
        this.saveConsent(consent);
        this.applyConsent(consent);
        this.hideBanner();
        this.hidePreferences();
    }

    savePreferences(event) {
        event.preventDefault();
        const form = event.target.closest('form');
        const consent = {
            necessary: true, // Always true
            preferences: form.querySelector('#cookie-preferences')?.checked || false,
            analytics: form.querySelector('#cookie-analytics')?.checked || false,
            marketing: form.querySelector('#cookie-marketing')?.checked || false
        };
        this.saveConsent(consent);
        this.applyConsent(consent);
        this.hidePreferences();
        this.hideBanner();
    }

    saveConsent(consent) {
        // Store in localStorage for persistence
        localStorage.setItem('cookieConsent', JSON.stringify(consent));

        // Store timestamp
        localStorage.setItem('cookieConsentDate', new Date().toISOString());

        // Set a cookie to indicate consent was given (expires in 1 year)
        const expiryDate = new Date();
        expiryDate.setFullYear(expiryDate.getFullYear() + 1);
        document.cookie = `cookieConsent=true; expires=${expiryDate.toUTCString()}; path=/; SameSite=Lax`;
    }

    getConsent() {
        const stored = localStorage.getItem('cookieConsent');
        return stored ? JSON.parse(stored) : null;
    }

    applyConsent(consent) {
        // Apply consent preferences
        // This is where you would enable/disable different tracking scripts

        if (consent.preferences) {
            this.enablePreferenceCookies();
        } else {
            this.disablePreferenceCookies();
        }

        if (consent.analytics) {
            this.enableAnalytics();
        } else {
            this.disableAnalytics();
        }

        if (consent.marketing) {
            this.enableMarketing();
        } else {
            this.disableMarketing();
        }

        // Dispatch event for other parts of the application
        window.dispatchEvent(new CustomEvent('cookieConsentUpdated', { detail: consent }));
    }

    enablePreferenceCookies() {
        // Enable preference cookies (language, theme, etc.)
        // These are already enabled in LARPilot for locale switching
        console.log('Preference cookies enabled');
    }

    disablePreferenceCookies() {
        // Can't really disable these as they're essential for site functionality
        // but we log it for compliance
        console.log('Preference cookies disabled (note: locale cookie remains for functionality)');
    }

    enableAnalytics() {
        // Placeholder for analytics initialization
        // Example: Google Analytics, Matomo, etc.
        console.log('Analytics cookies enabled');
        // window.gtag && gtag('consent', 'update', { 'analytics_storage': 'granted' });
    }

    disableAnalytics() {
        console.log('Analytics cookies disabled');
        // window.gtag && gtag('consent', 'update', { 'analytics_storage': 'denied' });
    }

    enableMarketing() {
        console.log('Marketing cookies enabled');
        // window.gtag && gtag('consent', 'update', { 'ad_storage': 'granted' });
    }

    disableMarketing() {
        console.log('Marketing cookies disabled');
        // window.gtag && gtag('consent', 'update', { 'ad_storage': 'denied' });
    }

    // Method to revoke consent (callable from privacy settings page)
    revokeConsent() {
        localStorage.removeItem('cookieConsent');
        localStorage.removeItem('cookieConsentDate');
        document.cookie = 'cookieConsent=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        this.showBanner();
    }
}
