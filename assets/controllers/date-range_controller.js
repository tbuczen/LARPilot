import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        errorMessage: { type: String, default: 'End date must be after start date.' },
    };

    connect() {
        const pickers = this.element.querySelectorAll('[data-controller~="datepicker"]');
        if (pickers.length < 2) {
            return;
        }

        this.startInput = pickers[0];
        this.endInput = pickers[1];

        this.onStartChange = () => this.syncEndMinDate();
        this.onEndChange = () => this.syncStartMaxDate();

        this.startInput.addEventListener('change', this.onStartChange);
        this.endInput.addEventListener('change', this.onEndChange);
    }

    disconnect() {
        if (this.startInput) {
            this.startInput.removeEventListener('change', this.onStartChange);
        }
        if (this.endInput) {
            this.endInput.removeEventListener('change', this.onEndChange);
        }
    }

    get startDate() {
        return this.startInput._flatpickr?.selectedDates[0] ?? null;
    }

    get endDate() {
        return this.endInput._flatpickr?.selectedDates[0] ?? null;
    }

    syncEndMinDate() {
        const endPicker = this.endInput._flatpickr;
        if (!endPicker) {
            return;
        }

        endPicker.set('minDate', this.startDate ?? null);

        if (this.startDate && this.endDate && this.endDate <= this.startDate) {
            endPicker.clear();
        }

        this.validate();
    }

    syncStartMaxDate() {
        const startPicker = this.startInput._flatpickr;
        if (!startPicker) {
            return;
        }

        startPicker.set('maxDate', this.endDate ?? null);

        if (this.startDate && this.endDate && this.startDate >= this.endDate) {
            startPicker.clear();
        }

        this.validate();
    }

    validate() {
        const invalid = this.startDate && this.endDate && this.endDate <= this.startDate;
        this.endInput.setCustomValidity(invalid ? this.errorMessageValue : '');
    }
}
