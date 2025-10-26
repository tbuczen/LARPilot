import { Controller } from '@hotwired/stimulus';
import 'fullcalendar';
import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPlugin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';

export default class extends Controller {
    static values = {
        larpId: String,
        eventsUrl: String,
        createEventUrl: String,
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
            plugins: [dayGridPlugin, timeGridPlugin, listPlugin, interactionPlugin],
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
            editable: true,
            selectable: true,
            selectMirror: true,
            dayMaxEvents: true,

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
            },

            // Drag-to-create handler
            select: (info) => {
                this.createEventFromSelection(info);
            },

            // Drag-and-drop handler for rescheduling
            eventDrop: (info) => {
                this.updateEventTimes(info);
            },

            // Resize handler
            eventResize: (info) => {
                this.updateEventTimes(info);
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

    async createEventFromSelection(info) {
        // Prompt for event title
        const title = prompt('Enter event title:', 'New Event');

        if (!title) {
            // User cancelled
            this.calendar.unselect();
            return;
        }

        try {
            const response = await fetch(this.createEventUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    title: title,
                    start: info.startStr,
                    end: info.endStr
                })
            });

            const data = await response.json();

            if (data.success) {
                // Add the new event to the calendar
                this.calendar.addEvent(data.event);

                // Show success message
                this.showNotification('Event created successfully!', 'success');

                // Navigate to edit page after a short delay
                setTimeout(() => {
                    window.location.href = data.event.url;
                }, 1000);
            } else {
                this.showNotification('Failed to create event: ' + data.message, 'error');
            }
        } catch (error) {
            console.error('Error creating event:', error);
            this.showNotification('An error occurred while creating the event', 'error');
        } finally {
            this.calendar.unselect();
        }
    }

    async updateEventTimes(info) {
        const event = info.event;

        try {
            const response = await fetch(this.createEventUrlValue.replace('/create', `/${event.id}`), {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    start: event.start.toISOString(),
                    end: event.end ? event.end.toISOString() : null
                })
            });

            const data = await response.json();

            if (!data.success) {
                // Revert the change
                info.revert();
                this.showNotification('Failed to update event: ' + data.message, 'error');
            } else {
                this.showNotification('Event updated successfully!', 'success');
            }
        } catch (error) {
            console.error('Error updating event:', error);
            info.revert();
            this.showNotification('An error occurred while updating the event', 'error');
        }
    }

    showNotification(message, type = 'info') {
        // Create a simple Bootstrap alert
        const alert = document.createElement('div');
        alert.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alert.style.zIndex = '9999';
        alert.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(alert);

        // Auto-remove after 3 seconds
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }

    disconnect() {
        if (this.calendar) {
            this.calendar.destroy();
        }
    }
}
