export const PICKER_DATE_FORMAT = 'dd-MM-yyyy';

const pad = (value) => String(value).padStart(2, '0');

export const formatDisplayDate = (date) => `${pad(date.getDate())}-${pad(date.getMonth() + 1)}-${date.getFullYear()}`;

export const formatDisplayDateTime = (date) => `${formatDisplayDate(date)} ${pad(date.getHours())}:${pad(date.getMinutes())}`;

const formatIsoDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;

const formatIsoDateTime = (date) => `${formatIsoDate(date)} ${pad(date.getHours())}:${pad(date.getMinutes())}`;

export const parseIsoValue = (value) => {
    if (!value) {
        return null;
    }
    const match = String(value).trim().match(/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}))?/);
    if (!match) {
        return null;
    }
    const [, year, month, day, hours = '0', minutes = '0'] = match;
    return new Date(Number(year), Number(month) - 1, Number(day), Number(hours), Number(minutes));
};

const withTime = (date, time) => {
    const result = new Date(date.getFullYear(), date.getMonth(), date.getDate());
    result.setHours(time.hours, time.minutes, 0, 0);
    return result;
};

const timeOf = (date, fallback) => (date ? { hours: date.getHours(), minutes: date.getMinutes() } : { ...fallback });

const roundToStep = (minutes, step) => Math.min(60 - step, Math.round(minutes / step) * step);

const createElement = (tag, className, text) => {
    const element = document.createElement(tag);
    if (className) {
        element.className = className;
    }
    if (text !== undefined) {
        element.textContent = text;
    }
    return element;
};

const DEFAULT_LABELS = {
    start: 'Start',
    end: 'End',
    time: 'Time',
    done: 'Done',
    clear: 'Clear',
    placeholderDate: 'dd-mm-yyyy',
    placeholderDateTime: 'dd-mm-yyyy hh:mm',
    endBeforeStart: 'End is before start',
};

export class LarpDatePicker {
    constructor(AirDatepicker, options) {
        this.AirDatepicker = AirDatepicker;
        this.startInput = options.startInput;
        this.endInput = options.endInput ?? null;
        this.withTime = Boolean(options.withTime);
        this.required = Boolean(options.required);
        this.minutesStep = options.minutesStep ?? 5;
        this.locale = options.locale;
        this.labels = { ...DEFAULT_LABELS, ...(options.labels ?? {}) };
        this.isMobile = options.isMobile ?? window.matchMedia('(max-width: 575.98px), (pointer: coarse)').matches;
        this.container = options.container ?? null;
        this.defaultStartTime = options.defaultStartTime ?? { hours: 10, minutes: 0 };
        this.defaultEndTime = options.defaultEndTime ?? { hours: 18, minutes: 0 };

        this.start = parseIsoValue(this.startInput.value);
        this.end = this.endInput ? parseIsoValue(this.endInput.value) : null;
        this.startTime = timeOf(this.start, this.defaultStartTime);
        this.endTime = timeOf(this.end, this.defaultEndTime);
        this.timeSelects = {};

        this.render();
        this.initPicker();
        this.sync();
        this.refreshDisplay();
    }

    get isRange() {
        return this.endInput !== null;
    }

    render() {
        this.wrapper = createElement('div', 'input-group larp-date-input');
        const icon = createElement('span', 'input-group-text');
        icon.innerHTML = `<i class="bi ${this.withTime ? 'bi-calendar-event' : 'bi-calendar3'}" aria-hidden="true"></i>`;

        this.display = createElement('input', 'form-control larp-date-display');
        this.display.type = 'text';
        this.display.readOnly = true;
        this.display.inputMode = 'none';
        this.display.autocomplete = 'off';
        this.display.placeholder = this.withTime ? this.labels.placeholderDateTime : this.labels.placeholderDate;
        if (this.startInput.id) {
            this.display.id = `${this.startInput.id}_display`;
            const label = document.querySelector(`label[for="${this.startInput.id}"]`);
            if (label) {
                label.htmlFor = this.display.id;
            }
        }
        if (this.startInput.classList.contains('is-invalid') || this.endInput?.classList.contains('is-invalid')) {
            this.display.classList.add('is-invalid');
        }

        this.wrapper.append(icon, this.display);

        if (!this.required) {
            this.clearButton = createElement('button', 'btn btn-outline-secondary larp-date-clear');
            this.clearButton.type = 'button';
            this.clearButton.title = this.labels.clear;
            this.clearButton.setAttribute('aria-label', this.labels.clear);
            this.clearButton.innerHTML = '<i class="bi bi-x-lg" aria-hidden="true"></i>';
            this.clearButton.addEventListener('click', () => this.clear());
            this.wrapper.append(this.clearButton);
        }

        this.startInput.insertAdjacentElement('beforebegin', this.wrapper);
        this.startInput.type = 'hidden';
        if (this.endInput) {
            this.endInput.type = 'hidden';
        }
    }

    initPicker() {
        const selectedDates = [this.start, this.end].filter(Boolean);
        const buttons = [
            { content: this.labels.clear, className: 'larp-dp-button', onClick: () => this.clear() },
            { content: this.labels.done, className: 'larp-dp-button larp-dp-button-primary', onClick: (picker) => this.finish(picker) },
        ];

        this.picker = new this.AirDatepicker(this.display, {
            locale: this.locale,
            range: this.isRange,
            dynamicRange: true,
            toggleSelected: false,
            multipleDatesSeparator: ' → ',
            dateFormat: PICKER_DATE_FORMAT,
            selectedDates,
            startDate: this.start ?? new Date(),
            isMobile: this.isMobile,
            autoClose: !this.withTime && !this.isRange,
            keyboardNav: true,
            position: 'bottom left',
            classes: 'larp-dp',
            container: this.container ?? '',
            buttons: this.withTime || this.isRange ? buttons : false,
            onSelect: ({ date }) => this.onSelect(date),
            onShow: (isFinished) => {
                if (!isFinished) {
                    this.mountTimePanel();
                }
            },
            onHide: (isFinished) => {
                if (isFinished) {
                    this.sync();
                    this.refreshDisplay();
                }
            },
        });
    }

    onSelect(date) {
        const dates = Array.isArray(date) ? date : [date].filter(Boolean);
        if (this.isRange) {
            this.start = dates[0] ? withTime(dates[0], this.startTime) : null;
            this.end = dates[1] ? withTime(dates[1], this.endTime) : null;
        } else {
            this.start = dates[0] ? withTime(dates[0], this.startTime) : null;
        }
        this.writeInputs();
        this.updateTimePanel();
        this.refreshDisplay();
    }

    mountTimePanel() {
        if (!this.withTime || this.timePanel) {
            return;
        }
        this.timePanel = createElement('div', 'larp-dp-time');
        this.timePanel.append(this.buildTimeRow('start', this.isRange ? this.labels.start : this.labels.time, this.startTime));
        if (this.isRange) {
            this.timePanel.append(this.buildTimeRow('end', this.labels.end, this.endTime));
        }
        this.warning = createElement('div', 'larp-dp-warning', this.labels.endBeforeStart);
        this.warning.hidden = true;
        this.timePanel.append(this.warning);

        const buttonsArea = this.picker.$datepicker.querySelector('.air-datepicker--buttons');
        this.picker.$datepicker.insertBefore(this.timePanel, buttonsArea);
        this.updateTimePanel();
    }

    buildTimeRow(key, labelText, time) {
        const row = createElement('div', 'larp-dp-time-row');
        const label = createElement('span', 'larp-dp-time-label', labelText);
        const date = createElement('span', 'larp-dp-time-date');
        const hours = createElement('select', 'form-select form-select-sm larp-dp-hours');
        const minutes = createElement('select', 'form-select form-select-sm larp-dp-minutes');
        hours.setAttribute('aria-label', `${labelText} – h`);
        minutes.setAttribute('aria-label', `${labelText} – min`);

        for (let hour = 0; hour < 24; hour++) {
            hours.append(new Option(pad(hour), String(hour)));
        }
        for (let minute = 0; minute < 60; minute += this.minutesStep) {
            minutes.append(new Option(pad(minute), String(minute)));
        }
        hours.value = String(time.hours);
        minutes.value = String(roundToStep(time.minutes, this.minutesStep));

        const onChange = () => this.onTimeChange(key, Number(hours.value), Number(minutes.value));
        hours.addEventListener('change', onChange);
        minutes.addEventListener('change', onChange);

        const separator = createElement('span', 'larp-dp-time-separator', ':');
        row.append(label, date, hours, separator, minutes);
        this.timeSelects[key] = { hours, minutes, date };
        return row;
    }

    onTimeChange(key, hours, minutes) {
        const time = { hours, minutes };
        if (key === 'start') {
            this.startTime = time;
            this.start = this.start ? withTime(this.start, time) : null;
        } else {
            this.endTime = time;
            this.end = this.end ? withTime(this.end, time) : null;
        }
        this.writeInputs();
        this.updateTimePanel();
        this.updateDisplay();
    }

    updateTimePanel() {
        if (!this.timePanel) {
            return;
        }
        const startRow = this.timeSelects.start;
        startRow.date.textContent = this.start ? formatDisplayDate(this.start) : '—';
        if (this.timeSelects.end) {
            const effectiveEnd = this.end ?? (this.start ? withTime(this.start, this.endTime) : null);
            this.timeSelects.end.date.textContent = effectiveEnd ? formatDisplayDate(effectiveEnd) : '—';
        }
        this.warning.hidden = !this.isEndBeforeStart();
    }

    isEndBeforeStart() {
        if (!this.isRange || !this.start) {
            return false;
        }
        const effectiveEnd = this.end ?? withTime(this.start, this.endTime);
        return effectiveEnd < this.start;
    }

    finish(picker) {
        if (this.isRange && this.start && !this.end) {
            this.end = withTime(this.start, this.endTime);
            this.writeInputs();
        }
        this.updateDisplay();
        picker.hide();
    }

    clear() {
        this.start = null;
        this.end = null;
        this.picker.clear({ silent: true });
        this.writeInputs();
        this.updateTimePanel();
        this.refreshDisplay();
    }

    sync() {
        if (this.isRange && this.start && !this.end) {
            this.end = withTime(this.start, this.endTime);
            this.picker.selectDate([this.start, this.end], { silent: true });
            this.writeInputs();
        }
        this.updateDisplay();
    }

    formatValue(date) {
        return this.withTime ? formatDisplayDateTime(date) : formatDisplayDate(date);
    }

    refreshDisplay() {
        this.updateDisplay();
        window.setTimeout(() => this.updateDisplay(), 0);
    }

    displayText() {
        if (!this.start) {
            return '';
        }
        const startText = this.formatValue(this.start);
        if (!this.isRange) {
            return startText;
        }
        if (!this.end) {
            return `${startText} → …`;
        }
        const endText = this.formatValue(this.end);
        return startText === endText && !this.withTime ? startText : `${startText} → ${endText}`;
    }

    updateDisplay() {
        const text = this.displayText();
        this.display.value = text;
        this.display.title = text;
        if (this.clearButton) {
            this.clearButton.hidden = !this.start;
        }
    }

    writeInputs() {
        const format = this.withTime ? formatIsoDateTime : formatIsoDate;
        this.setInput(this.startInput, this.start ? format(this.start) : '');
        if (this.endInput) {
            this.setInput(this.endInput, this.end ? format(this.end) : '');
        }
    }

    setInput(input, value) {
        if (input.value === value) {
            return;
        }
        input.value = value;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    destroy() {
        this.picker?.destroy();
        this.wrapper?.remove();
        this.startInput.type = 'text';
        if (this.endInput) {
            this.endInput.type = 'text';
        }
    }
}
