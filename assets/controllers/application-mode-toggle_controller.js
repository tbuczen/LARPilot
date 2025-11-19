import { Controller } from '@hotwired/stimulus';

/**
 * Application Mode Toggle Controller
 *
 * Manages the visibility of the "Publish Characters Publicly" checkbox
 * based on the selected application mode in LARP settings.
 *
 * - CHARACTER_SELECTION mode: Shows the checkbox
 * - SURVEY mode: Hides the checkbox and unchecks it
 */
export default class extends Controller {
    static targets = ['publishCheckbox'];

    connect() {
        this.updateVisibility();
    }

    /**
     * Called when application mode radio buttons change
     */
    change(event) {
        this.updateVisibility();
    }

    updateVisibility() {
        const selectedMode = this.getSelectedMode();
        const publishContainer = this.findPublishCheckboxContainer();

        if (!publishContainer) {
            return;
        }

        if (selectedMode === 'character_selection') {
            // Show the checkbox for character selection mode
            publishContainer.style.display = 'block';
        } else if (selectedMode === 'survey') {
            // Hide the checkbox for survey mode and uncheck it
            publishContainer.style.display = 'none';

            // Uncheck the checkbox
            const checkbox = publishContainer.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = false;
            }
        }
    }

    getSelectedMode() {
        // Find the checked radio button
        const checkedRadio = this.element.querySelector('input[type="radio"]:checked');
        return checkedRadio ? checkedRadio.value : null;
    }

    findPublishCheckboxContainer() {
        // The checkbox is in a sibling form group
        // Look for the parent form and then find the publish checkbox container
        const form = this.element.closest('form');
        if (!form) {
            return null;
        }

        // Find by data attribute if available
        if (this.hasPublishCheckboxTarget) {
            return this.publishCheckboxTarget.closest('.mb-3, .form-group');
        }

        // Fallback: Find by field name pattern
        const publishField = form.querySelector('input[name*="publishCharactersPublicly"]');
        if (publishField) {
            return publishField.closest('.mb-3, .form-group');
        }

        return null;
    }
}
