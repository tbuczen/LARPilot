import { Controller } from '@hotwired/stimulus';

/**
 * Dark Mode Toggle Controller
 *
 * Manages dark mode state across the application.
 * Persists preference in localStorage.
 *
 * Usage:
 * <div data-controller="dark-mode">
 *   <button data-action="click->dark-mode#toggle">Toggle Dark Mode</button>
 * </div>
 */
export default class extends Controller {
    static values = {
        storageKey: { type: String, default: 'darkMode' }
    }

    connect() {
        // Dark mode class is already applied by inline script in <head>
        // Just update the toggle icon to match the current state
        this.updateToggleIcon();
    }

    toggle() {
        const isDarkMode = document.documentElement.classList.contains('dark-mode');
        this.setDarkMode(!isDarkMode);
    }

    setDarkMode(enable) {
        if (enable) {
            document.documentElement.classList.add('dark-mode');
            localStorage.setItem(this.storageKeyValue, 'true');
        } else {
            document.documentElement.classList.remove('dark-mode');
            localStorage.setItem(this.storageKeyValue, 'false');
        }

        this.updateToggleIcon();
    }

    updateToggleIcon() {
        const isDarkMode = document.documentElement.classList.contains('dark-mode');
        const toggleButtons = document.querySelectorAll('[data-action*="dark-mode#toggle"]');

        toggleButtons.forEach(button => {
            const icon = button.querySelector('i');
            if (icon) {
                if (isDarkMode) {
                    icon.classList.remove('bi-moon-fill');
                    icon.classList.add('bi-sun-fill');
                } else {
                    icon.classList.remove('bi-sun-fill');
                    icon.classList.add('bi-moon-fill');
                }
            }
        });
    }
}
