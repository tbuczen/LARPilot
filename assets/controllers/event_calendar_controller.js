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
                fetch(`${this.eventsUrlValue}?start=${info.startStr}&end=${info.endStr}`)
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

    disconnect() {
        if (this.calendar) {
            this.calendar.destroy();
        }
    }
}
