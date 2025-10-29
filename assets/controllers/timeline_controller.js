import { Controller } from '@hotwired/stimulus';
import { Timeline } from 'vis-timeline';

/**
 * Interactive timeline controller using vis-timeline
 *
 * Features:
 * - Scrollable, zoomable timeline
 * - Click to create events
 * - Drag to reposition events
 * - Visual categorization (Historical/Current/Future)
 *
 * Usage:
 * <div data-controller="timeline"
 *      data-timeline-events-value="[...]"
 *      data-timeline-larp-id-value="123"
 *      data-timeline-is-admin-value="true">
 * </div>
 */
export default class extends Controller {
    static values = {
        events: Array,
        larpId: String,
        isAdmin: { type: Boolean, default: false }
    }

    timeline = null;
    items = null;

    connect() {
        this.renderTimeline();
    }

    disconnect() {
        if (this.timeline) {
            this.timeline.destroy();
        }
    }

    eventsValueChanged() {
        if (this.timeline) {
            this.updateTimelineItems();
        }
    }

    renderTimeline() {
        const container = this.element.querySelector('#timeline-container');
        if (!container) return;

        // Prepare items for vis-timeline
        this.items = this.prepareItems(this.eventsValue || []);

        // Configure timeline options
        const options = {
            height: '600px',
            min: new Date(1900, 0, 1), // Allow historical dates
            max: new Date(2100, 11, 31), // Allow future dates
            zoomMin: 1000 * 60 * 60 * 24 * 7, // Min zoom: 1 week
            zoomMax: 1000 * 60 * 60 * 24 * 365 * 100, // Max zoom: 100 years
            editable: {
                add: this.isAdminValue, // Allow creating by clicking
                updateTime: this.isAdminValue, // Allow dragging in time
                updateGroup: false,
                remove: false
            },
            onAdd: this.onAddItem.bind(this),
            onMove: this.onMoveItem.bind(this),
            snap: null, // Smooth dragging
            stack: true, // Stack items to avoid overlap
            orientation: 'top',
            showCurrentTime: true,
            tooltip: {
                followMouse: true,
                overflowMethod: 'cap'
            },
            format: {
                minorLabels: {
                    millisecond: 'SSS',
                    second: 's',
                    minute: 'HH:mm',
                    hour: 'HH:mm',
                    weekday: 'ddd D',
                    day: 'D',
                    week: 'w',
                    month: 'MMM',
                    year: 'YYYY'
                },
                majorLabels: {
                    millisecond: 'HH:mm:ss',
                    second: 'D MMMM HH:mm',
                    minute: 'ddd D MMMM',
                    hour: 'ddd D MMMM',
                    weekday: 'MMMM YYYY',
                    day: 'MMMM YYYY',
                    week: 'MMMM YYYY',
                    month: 'YYYY',
                    year: ''
                }
            }
        };

        // Create timeline
        this.timeline = new Timeline(container, this.items, options);

        // Handle item double-click for navigation
        this.timeline.on('doubleClick', (properties) => {
            if (properties.item) {
                this.navigateToEvent(properties.item);
            }
        });

        // Auto-fit timeline
        if (this.items.length > 0) {
            setTimeout(() => {
                this.timeline.fit();
            }, 100);
        }
    }

    prepareItems(events) {
        const items = [];

        events.forEach((event) => {
            let start;

            // Determine start time
            if (event.startTime) {
                start = new Date(event.startTime);
            } else if (event.storyTime !== null && event.storyTime !== undefined) {
                // Convert story time to a date (use a base year like 2000)
                start = this.storyTimeToDate(event.storyTime, event.storyTimeUnit);
            } else {
                // Default to now for events without time
                start = new Date();
            }

            // Determine end time if available
            let end = null;
            if (event.endTime) {
                end = new Date(event.endTime);
            }

            // Prepare item
            const item = {
                id: event.id,
                content: this.createItemContent(event),
                start: start,
                type: end ? 'range' : 'box',
                className: `timeline-event-${event.category || 'current'}`,
                title: this.createTooltip(event)
            };

            if (end) {
                item.end = end;
            }

            items.push(item);
        });

        return items;
    }

    createItemContent(event) {
        const categoryBadge = {
            'historical': '<span class="badge bg-secondary">Historical</span>',
            'current': '<span class="badge bg-primary">Current</span>',
            'future': '<span class="badge bg-info">Future</span>'
        }[event.category || 'current'];

        const visibility = event.isPublic
            ? '<i class="bi bi-globe text-success"></i>'
            : '<i class="bi bi-lock text-warning"></i>';

        return `
            <div class="timeline-item-content">
                ${visibility} <strong>${event.title}</strong> ${categoryBadge}
            </div>
        `;
    }

    createTooltip(event) {
        let tooltip = `<strong>${event.title}</strong><br>`;
        tooltip += `Category: ${event.category || 'current'}<br>`;

        if (event.description) {
            const desc = event.description.replace(/<[^>]*>/g, '').substring(0, 100);
            tooltip += `${desc}${event.description.length > 100 ? '...' : ''}<br>`;
        }

        if (event.isPublic) {
            tooltip += 'Visibility: Public<br>';
        } else {
            if (event.involvedFactions && event.involvedFactions.length > 0) {
                tooltip += `Factions: ${event.involvedFactions.length}<br>`;
            }
            if (event.involvedCharacters && event.involvedCharacters.length > 0) {
                tooltip += `Characters: ${event.involvedCharacters.length}<br>`;
            }
        }

        return tooltip;
    }

    storyTimeToDate(storyTime, unit = 'year') {
        // Convert story time to a date representation
        // Base year: 2000 represents time 0 in the story
        const baseYear = 2000;
        let date = new Date(baseYear, 0, 1);

        switch (unit) {
            case 'era':
                date.setFullYear(baseYear + (storyTime * 100));
                break;
            case 'year':
                date.setFullYear(baseYear + storyTime);
                break;
            case 'month':
                date.setMonth(storyTime);
                break;
            case 'week':
                date.setDate(date.getDate() + (storyTime * 7));
                break;
            case 'day':
                date.setDate(date.getDate() + storyTime);
                break;
            case 'hour':
                date.setHours(date.getHours() + storyTime);
                break;
            default:
                date.setFullYear(baseYear + storyTime);
        }

        return date;
    }

    updateTimelineItems() {
        if (!this.timeline) return;

        const newItems = this.prepareItems(this.eventsValue || []);
        this.items = newItems;
        this.timeline.setItems(newItems);
    }

    async onAddItem(item, callback) {
        // Show modal for event creation
        const modal = await this.showCreateModal(item.start);

        if (modal) {
            // Create event via API
            const newEvent = await this.createEventAPI(modal);
            if (newEvent) {
                // Add to timeline with visual feedback
                const newItem = {
                    id: newEvent.id,
                    content: this.createItemContent(newEvent),
                    start: new Date(newEvent.startTime || item.start),
                    type: 'box',
                    className: `timeline-event-${newEvent.category}`,
                    title: this.createTooltip(newEvent)
                };

                callback(newItem);

                // Focus on the newly created item
                setTimeout(() => {
                    this.timeline.setSelection(newEvent.id);
                    this.timeline.focus(newEvent.id, {
                        animation: {
                            duration: 500,
                            easingFunction: 'easeInOutQuad'
                        }
                    });
                }, 100);
            } else {
                callback(null); // Cancel creation
            }
        } else {
            callback(null); // User cancelled
        }
    }

    async onMoveItem(item, callback) {
        // Update event time via API
        try {
            const data = {
                startTime: item.start.toISOString()
            };

            // Include end time if it exists (for range events)
            if (item.end) {
                data.endTime = item.end.toISOString();
            }

            const response = await fetch(
                `/backoffice/larp/${this.larpIdValue}/story/event/${item.id}/api/update-time`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                }
            );

            if (!response.ok) {
                throw new Error('Failed to update event time');
            }

            const result = await response.json();

            // Accept the move with updated data from server
            callback({
                ...item,
                start: new Date(result.event.startTime),
                end: result.event.endTime ? new Date(result.event.endTime) : null
            });
        } catch (error) {
            console.error('Error updating event time:', error);
            alert('Failed to update event time. Please try again.');
            callback(null); // Cancel the move on error
        }
    }

    async showCreateModal(clickedDate) {
        return new Promise((resolve) => {
            // Create modal
            const modalHtml = `
                <div class="modal fade" id="createEventModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Create New Event</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <form id="createEventForm">
                                    <div class="mb-3">
                                        <label for="eventTitle" class="form-label">Title *</label>
                                        <input type="text" class="form-control" id="eventTitle" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="eventCategory" class="form-label">Category</label>
                                        <select class="form-select" id="eventCategory">
                                            <option value="historical">Historical/Lore Event</option>
                                            <option value="current" selected>Current Event</option>
                                            <option value="future">Future/Planned Event</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="eventDescription" class="form-label">Description</label>
                                        <textarea class="form-control" id="eventDescription" rows="3"></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Time: ${clickedDate.toLocaleString()}</label>
                                        <input type="hidden" id="eventStartTime" value="${clickedDate.toISOString()}">
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="saveEventBtn">Create</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Append to body
            const div = document.createElement('div');
            div.innerHTML = modalHtml;
            document.body.appendChild(div);

            const modalElement = document.getElementById('createEventModal');
            const modal = new bootstrap.Modal(modalElement);

            // Handle save
            document.getElementById('saveEventBtn').addEventListener('click', () => {
                const title = document.getElementById('eventTitle').value;
                const category = document.getElementById('eventCategory').value;
                const description = document.getElementById('eventDescription').value;
                const startTime = document.getElementById('eventStartTime').value;

                if (title) {
                    modal.hide();
                    resolve({ title, category, description, startTime });
                }
            });

            // Handle cancel
            modalElement.addEventListener('hidden.bs.modal', () => {
                if (!modalElement.classList.contains('event-created')) {
                    resolve(null);
                }
                modalElement.remove();
            });

            modal.show();
        });
    }

    async createEventAPI(data) {
        try {
            const response = await fetch(`/backoffice/larp/${this.larpIdValue}/story/event/api/create`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Failed to create event');
            }

            const result = await response.json();
            return result.event;
        } catch (error) {
            console.error('Error creating event:', error);
            alert('Failed to create event. Please try again.');
            return null;
        }
    }

    navigateToEvent(eventId) {
        if (this.isAdminValue) {
            window.location.href = `/backoffice/larp/${this.larpIdValue}/story/event/${eventId}`;
        }
    }
}
