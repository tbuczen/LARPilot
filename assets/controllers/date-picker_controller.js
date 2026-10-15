import { Controller } from '@hotwired/stimulus';
import AirDatepicker from 'air-datepicker';
import { LarpDatePicker } from '../utils/larpDatePicker.js';
import { datePickerLabels, datePickerLocales } from '../utils/datePickerLocales.js';

export default class extends Controller {
    static values = {
        end: String,
        time: Boolean,
        required: Boolean,
    };

    connect() {
        const language = (document.documentElement.lang || 'en').slice(0, 2).toLowerCase();
        const locale = datePickerLocales[language] ? language : 'en';
        const endInput = this.endValue ? document.getElementById(this.endValue) : null;
        const modal = this.element.closest('.modal');

        if (endInput) {
            this.hideEndRow(endInput);
        }

        this.picker = new LarpDatePicker(AirDatepicker, {
            startInput: this.element,
            endInput,
            withTime: this.timeValue,
            required: this.requiredValue,
            locale: datePickerLocales[locale],
            labels: datePickerLabels[locale],
            container: modal,
            isMobile: modal ? false : undefined,
        });
    }

    disconnect() {
        this.picker?.destroy();
        this.picker = null;
        if (this.endRow) {
            this.endRow.hidden = false;
        }
    }

    hideEndRow(endInput) {
        let row = endInput.closest('.mb-3, .form-group') ?? endInput.parentElement;
        const column = row?.parentElement;
        if (column && /(^|\s)col(-|\s|$)/.test(column.className) && column.children.length === 1) {
            row = column;
        }
        if (!row || row.contains(this.element)) {
            return;
        }
        this.endRow = row;
        row.hidden = true;
        row.querySelectorAll('.invalid-feedback, .form-error-message').forEach((error) => {
            error.classList.add('d-block');
            this.element.closest('.mb-3, .form-group')?.append(error);
        });
    }
}
