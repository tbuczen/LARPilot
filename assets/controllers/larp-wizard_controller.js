import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['step', 'progressStep', 'progressBar', 'nextBtn', 'prevBtn', 'submitBtn', 'maxChoicesField', 'applicationMatcherField', 'applicationModeSelect'];

    currentStep = 0;

    connect() {
        this.showStep(0);
        this.applicationModeSelectTarget.addEventListener('change', () => this.onApplicationModeChange());
        this.onApplicationModeChange();
    }

    showStep(index) {
        this.stepTargets.forEach((step, i) => {
            step.classList.toggle('d-none', i !== index);
        });

        this.progressStepTargets.forEach((pill, i) => {
            const circle = pill.querySelector('[data-wizard-circle]');
            const label = pill.querySelector('small');

            if (i === index) {
                circle.style.background = 'var(--bs-primary)';
                circle.style.borderColor = 'var(--bs-primary)';
                circle.style.color = 'white';
                circle.textContent = i + 1;
                label.classList.add('fw-semibold');
                label.classList.remove('text-muted');
            } else if (i < index) {
                circle.style.background = 'var(--bs-success)';
                circle.style.borderColor = 'var(--bs-success)';
                circle.style.color = 'white';
                circle.textContent = '✓';
                label.classList.remove('fw-semibold');
                label.classList.add('text-muted');
            } else {
                circle.style.background = '#dee2e6';
                circle.style.borderColor = '#dee2e6';
                circle.style.color = '#495057';
                circle.textContent = i + 1;
                label.classList.remove('fw-semibold');
                label.classList.add('text-muted');
            }
        });

        if (this.hasProgressBarTarget) {
            const total = this.stepTargets.length - 1;
            this.progressBarTarget.style.width = total > 0 ? `${(index / total) * 100}%` : '0%';
        }

        this.prevBtnTarget.classList.toggle('d-none', index === 0);
        this.nextBtnTarget.classList.toggle('d-none', index === this.stepTargets.length - 1);
        this.submitBtnTarget.classList.toggle('d-none', index !== this.stepTargets.length - 1);

        this.currentStep = index;
    }

    next() {
        if (!this.validateCurrentStep()) {
            return;
        }

        if (this.currentStep < this.stepTargets.length - 1) {
            this.showStep(this.currentStep + 1);
        }
    }

    prev() {
        if (this.currentStep > 0) {
            this.showStep(this.currentStep - 1);
        }
    }

    validateCurrentStep() {
        const currentStepEl = this.stepTargets[this.currentStep];
        const fields = currentStepEl.querySelectorAll('input, select, textarea');
        let valid = true;

        fields.forEach(field => {
            if (!field.validity.valid) {
                field.reportValidity();
                valid = false;
            }
        });

        return valid;
    }

    onApplicationModeChange() {
        const mode = this.applicationModeSelectTarget.value;
        const isCharacterSelection = mode === 'character_selection';
        const isTicketPurchase = mode === 'ticket_purchase';

        if (this.hasMaxChoicesFieldTarget) {
            this.maxChoicesFieldTarget.closest('.mb-3').classList.toggle('d-none', !isCharacterSelection);
        }

        if (this.hasApplicationMatcherFieldTarget) {
            const wrapper = this.applicationMatcherFieldTarget.closest('.form-check');
            if (wrapper) {
                wrapper.classList.toggle('opacity-50', isTicketPurchase);
                this.applicationMatcherFieldTarget.disabled = isTicketPurchase;
                if (isTicketPurchase) {
                    this.applicationMatcherFieldTarget.checked = false;
                    wrapper.setAttribute('title', 'Not available for ticket purchase mode');
                } else {
                    wrapper.removeAttribute('title');
                }
            }
        }
    }
}
