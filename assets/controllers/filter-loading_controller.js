import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['submitButton', 'buttonText', 'spinner'];

    connect() {
        this.boundHandleSubmit = this.handleSubmit.bind(this);
        this.boundResetLoadingState = this.resetLoadingState.bind(this);

        this.element.addEventListener('submit', this.boundHandleSubmit);

        // Listen for calendar events loaded (for calendar view)
        document.addEventListener('calendar:eventsLoaded', this.boundResetLoadingState);

        console.log('Filter loading controller connected');
    }

    handleSubmit(event) {
        console.log('Filter form submitted');
        this.showLoadingState();
    }

    showLoadingState() {
        console.log('Showing loading state');
        this.submitButtonTarget.disabled = true;
        this.buttonTextTarget.classList.add('d-none');
        this.spinnerTarget.classList.remove('d-none');
    }

    resetLoadingState() {
        console.log('Resetting loading state');
        if (this.hasSubmitButtonTarget) {
            this.submitButtonTarget.disabled = false;
            this.buttonTextTarget.classList.remove('d-none');
            this.spinnerTarget.classList.add('d-none');
        }
    }

    disconnect() {
        this.element.removeEventListener('submit', this.boundHandleSubmit);
        document.removeEventListener('calendar:eventsLoaded', this.boundResetLoadingState);
    }
}
