import { Controller } from '@hotwired/stimulus';

/**
 * Delete Confirmation Modal Controller
 *
 * Handles delete confirmation dialogs across the application.
 * Dynamically updates modal content based on button data attributes.
 *
 * Usage in templates:
 *
 * 1. Include the modal template:
 *    {% include 'includes/delete_modal.html.twig' %}
 *
 * 2. Add delete button with data attributes:
 *    <button type="button"
 *            class="btn btn-sm btn-danger"
 *            data-bs-toggle="modal"
 *            data-bs-target="#deleteModal"
 *            data-item-id="{{ item.id }}"
 *            data-item-name="{{ item.title }}"
 *            data-delete-url="{{ path('...', {larp: larp.id, item: item.id}) }}">
 *        {{ 'delete'|trans }}
 *    </button>
 *
 * Optional data attributes:
 * - data-confirm-message: Custom confirmation message (default uses translation)
 * - data-confirm-button: Custom confirm button text
 */
export default class extends Controller {
    static targets = ['title', 'message', 'form'];

    connect() {
        // Listen for modal show event
        const modalElement = this.element;
        modalElement.addEventListener('show.bs.modal', this.handleShow.bind(this));
    }

    disconnect() {
        const modalElement = this.element;
        modalElement.removeEventListener('show.bs.modal', this.handleShow.bind(this));
    }

    /**
     * Handle modal show event
     * Extract data from trigger button and update modal content
     */
    handleShow(event) {
        const button = event.relatedTarget; // Button that triggered the modal

        if (!button) return;

        // Extract data from button
        const itemId = button.getAttribute('data-item-id');
        const itemName = button.getAttribute('data-item-name');
        const deleteUrl = button.getAttribute('data-delete-url');
        const confirmMessage = button.getAttribute('data-confirm-message');
        const confirmButton = button.getAttribute('data-confirm-button');

        // Update modal title
        if (this.hasTitleTarget && itemName) {
            this.titleTarget.textContent = itemName;
        }

        // Update confirmation message if custom message provided
        if (this.hasMessageTarget && confirmMessage) {
            this.messageTarget.textContent = confirmMessage;
        }

        // Update confirm button text if provided
        if (confirmButton && this.hasFormTarget) {
            const submitButton = this.formTarget.querySelector('button[type="submit"]');
            if (submitButton) {
                submitButton.textContent = confirmButton;
            }
        }

        // Update form action
        if (this.hasFormTarget && deleteUrl) {
            this.formTarget.action = deleteUrl;
        }
    }

    /**
     * Cancel and close modal
     */
    cancel(event) {
        event.preventDefault();
        // Use Bootstrap's global instance from CDN
        const modal = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getInstance(this.element) : null;
        if (modal) {
            modal.hide();
        }
    }
}
