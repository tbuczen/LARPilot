import { Controller } from '@hotwired/stimulus';
import 'fullcalendar';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';

export default class extends Controller {
    static values = {
        larpId: String,
        eventsUrl: String,
        initialStart: String,
        initialEnd: String
    };

    connect() {
        this.initializeCalendar();
        this.setupFilterHandling();
    }

    initializeCalendar() {
        const calendarEl = this.element;

        this.calendar = new Calendar(calendarEl, {
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin],
            initialView: 'timeGridWeek',
            initialDate: this.initialStartValue,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay,listWeek'
            },
            slotMinTime: '08:00:00',
            slotMaxTime: '23:00:00',
            slotDuration: '00:30:00',
            allDaySlot: false,
            height: 'auto',
            nowIndicator: true,

            // Event source
            events: (info, successCallback, failureCallback) => {
                const url = this.buildEventsUrl(info.startStr, info.endStr);
                fetch(url)
                    .then(response => response.json())
                    .then(data => successCallback(data))
                    .catch(error => {
                        console.error('Error loading events:', error);
                        failureCallback(error);
                    });
            },

            // Event styling
            eventDidMount: (info) => {
                // Add conflict indicator
                if (info.event.extendedProps.hasConflicts) {
                    const icon = document.createElement('i');
                    icon.className = 'bi bi-exclamation-triangle ms-1';
                    info.el.querySelector('.fc-event-title').appendChild(icon);
                }

                // Tooltip with location
                if (info.event.extendedProps.location) {
                    info.el.setAttribute('title', info.event.extendedProps.location);
                }
            },

            // Click handler
            eventClick: (info) => {
                // Navigate to event detail page
                if (info.event.url) {
                    window.location.href = info.event.url;
                    info.jsEvent.preventDefault();
                }
            }
        });

        this.calendar.render();
    }

    buildEventsUrl(start, end) {
        const url = new URL(this.eventsUrlValue, window.location.origin);
        url.searchParams.set('start', start);
        url.searchParams.set('end', end);

        // Get filter values from form
        const filterParams = this.getFilterParams();
        for (const [key, value] of Object.entries(filterParams)) {
            if (value) {
                url.searchParams.set(key, value);
            }
        }

        return url.toString();
    }

    getFilterParams() {
        const params = {};
        const form = document.querySelector('form[name="scheduled_event_filter"]');

        if (!form) return params;

        const formData = new FormData(form);
        for (const [key, value] of formData.entries()) {
            // Extract field name from form field name (e.g., "scheduled_event_filter[title]" -> "title")
            const match = key.match(/\[([^\]]+)\]$/);
            if (match && value) {
                params[match[1]] = value;
            }
        }

        return params;
    }

    setupFilterHandling() {
        const form = document.querySelector('form[name="scheduled_event_filter"]');
        if (!form) return;

        // Reload calendar events when filter is submitted
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            if (this.calendar) {
                this.calendar.refetchEvents();
            }
        });

        // Also reload when filter is cleared
        const clearButton = form.querySelector('[data-clear-filters]');
        if (clearButton) {
            clearButton.addEventListener('click', () => {
                setTimeout(() => {
                    if (this.calendar) {
                        this.calendar.refetchEvents();
                    }
                }, 100);
            });
        }
    }

    disconnect() {
        if (this.calendar) {
            this.calendar.destroy();
        }
    }
}
