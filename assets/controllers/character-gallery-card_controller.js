import { Controller } from '@hotwired/stimulus';

/**
 * Character Gallery Card Controller
 *
 * Handles interactions for character cards in the public gallery.
 * Currently provides hover effects and could be extended for lazy loading,
 * favoriting, or other interactive features.
 */
export default class extends Controller {
    static targets = ['image', 'card'];

    connect() {
        this.addHoverEffects();
    }

    addHoverEffects() {
        if (this.hasCardTarget) {
            this.cardTarget.addEventListener('mouseenter', () => {
                this.cardTarget.classList.add('shadow-lg');
            });

            this.cardTarget.addEventListener('mouseleave', () => {
                this.cardTarget.classList.remove('shadow-lg');
            });
        }
    }

    /**
     * Navigate to character detail page
     * Can be used for card click handling if needed
     */
    viewDetails(event) {
        // Prevent navigation if clicking on a link inside the card
        if (event.target.tagName === 'A' || event.target.closest('a')) {
            return;
        }

        const detailUrl = this.element.dataset.detailUrl;
        if (detailUrl) {
            window.location.href = detailUrl;
        }
    }

    /**
     * Lazy load image when card enters viewport
     * Usage: data-action="intersection@window->character-gallery-card#lazyLoadImage"
     */
    lazyLoadImage() {
        if (this.hasImageTarget && this.imageTarget.dataset.src) {
            this.imageTarget.src = this.imageTarget.dataset.src;
            delete this.imageTarget.dataset.src;
        }
    }
}
