import { Controller } from '@hotwired/stimulus';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

export default class extends Controller {
    static values = {
        enableTime: { type: Boolean, default: false },
    };

    connect() {
        this.picker = flatpickr(this.element, {
            dateFormat: this.enableTimeValue ? 'd-m-Y H:i' : 'd-m-Y',
            enableTime: this.enableTimeValue,
            time_24hr: true,
            allowInput: true,
        });
    }

    disconnect() {
        if (this.picker) {
            this.picker.destroy();
        }
    }
}
