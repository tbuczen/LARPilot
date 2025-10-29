import { Controller } from '@hotwired/stimulus';

/**
 * Timeline controller for displaying lore events
 *
 * This controller creates a visual timeline of events for a LARP
 * Events can be filtered by category, character, or faction
 *
 * Usage:
 * <div data-controller="timeline"
 *      data-timeline-events-value="[...]"
 *      data-timeline-larp-id-value="123"
 *      data-timeline-is-admin-value="false">
 * </div>
 */
export default class extends Controller {
    static values = {
        events: Array,
        larpId: String,
        isAdmin: { type: Boolean, default: false }
    }

    connect() {
        this.renderTimeline();
    }

    eventsValueChanged() {
        this.renderTimeline();
    }

    renderTimeline() {
        const container = this.element.querySelector('#timeline-container');
        if (!container) return;

        // Clear existing content
        container.innerHTML = '';

        if (!this.eventsValue || this.eventsValue.length === 0) {
            container.innerHTML = '<p class="text-muted">No events to display</p>';
            return;
        }

        // Group events by category
        const eventsByCategory = this.groupEventsByCategory(this.eventsValue);

        // Create timeline structure
        const timeline = document.createElement('div');
        timeline.className = 'lore-timeline';

        // Render events in chronological order
        const allEvents = this.sortEvents(this.eventsValue);
        allEvents.forEach((event, index) => {
            const eventElement = this.createEventElement(event, index);
            timeline.appendChild(eventElement);
        });

        container.appendChild(timeline);
    }

    groupEventsByCategory(events) {
        return events.reduce((groups, event) => {
            const category = event.category || 'current';
            if (!groups[category]) {
                groups[category] = [];
            }
            groups[category].push(event);
            return groups;
        }, {});
    }

    sortEvents(events) {
        return [...events].sort((a, b) => {
            // Sort by storyTime first (if available)
            if (a.storyTime !== null && b.storyTime !== null) {
                return a.storyTime - b.storyTime;
            }
            // Then by startTime
            if (a.startTime && b.startTime) {
                return new Date(a.startTime) - new Date(b.startTime);
            }
            // Events without time go last
            if (a.storyTime === null && b.storyTime !== null) return 1;
            if (a.storyTime !== null && b.storyTime === null) return -1;
            return 0;
        });
    }

    createEventElement(event, index) {
        const eventDiv = document.createElement('div');
        eventDiv.className = `timeline-event timeline-event-${event.category}`;
        eventDiv.dataset.eventId = event.id;

        // Timeline connector
        const connector = document.createElement('div');
        connector.className = 'timeline-connector';

        const dot = document.createElement('div');
        dot.className = `timeline-dot bg-${this.getCategoryColor(event.category)}`;
        connector.appendChild(dot);

        if (index < this.eventsValue.length - 1) {
            const line = document.createElement('div');
            line.className = 'timeline-line';
            connector.appendChild(line);
        }

        // Event content
        const content = document.createElement('div');
        content.className = 'timeline-content card mb-3';

        const cardBody = document.createElement('div');
        cardBody.className = 'card-body';

        // Header with title and category badge
        const header = document.createElement('div');
        header.className = 'd-flex justify-content-between align-items-start mb-2';

        const title = document.createElement('h5');
        title.className = 'card-title mb-0';
        if (this.isAdminValue) {
            const link = document.createElement('a');
            link.href = `/backoffice/larp/${this.larpIdValue}/story/event/${event.id}`;
            link.textContent = event.title;
            title.appendChild(link);
        } else {
            title.textContent = event.title;
        }

        const categoryBadge = document.createElement('span');
        categoryBadge.className = `badge bg-${this.getCategoryColor(event.category)}`;
        categoryBadge.textContent = this.getCategoryLabel(event.category);

        header.appendChild(title);
        header.appendChild(categoryBadge);

        // Time information
        const timeInfo = document.createElement('div');
        timeInfo.className = 'text-muted mb-2';
        timeInfo.innerHTML = `<small>${this.formatEventTime(event)}</small>`;

        // Description
        let description = null;
        if (event.description) {
            description = document.createElement('div');
            description.className = 'card-text mb-2';
            description.innerHTML = event.description;
        }

        // Visibility info
        const visibility = document.createElement('div');
        visibility.className = 'text-muted';
        visibility.innerHTML = `<small>${this.getVisibilityIcon(event)} ${this.getVisibilityLabel(event)}</small>`;

        // Assemble card
        cardBody.appendChild(header);
        cardBody.appendChild(timeInfo);
        if (description) {
            cardBody.appendChild(description);
        }
        cardBody.appendChild(visibility);

        content.appendChild(cardBody);

        // Assemble event
        eventDiv.appendChild(connector);
        eventDiv.appendChild(content);

        return eventDiv;
    }

    getCategoryColor(category) {
        const colors = {
            'historical': 'secondary',
            'current': 'primary',
            'future': 'info'
        };
        return colors[category] || 'secondary';
    }

    getCategoryLabel(category) {
        const labels = {
            'historical': 'Historical',
            'current': 'Current',
            'future': 'Future'
        };
        return labels[category] || category;
    }

    formatEventTime(event) {
        if (event.storyTime !== null) {
            const unit = event.storyTimeUnit || '';
            return `${event.storyTime} ${unit}`;
        } else if (event.startTime) {
            const date = new Date(event.startTime);
            return date.toLocaleString();
        }
        return 'Time not specified';
    }

    getVisibilityIcon(event) {
        if (event.isPublic) {
            return '<i class="bi bi-globe"></i>';
        } else if (event.involvedFactions && event.involvedFactions.length > 0) {
            return '<i class="bi bi-people"></i>';
        } else {
            return '<i class="bi bi-person"></i>';
        }
    }

    getVisibilityLabel(event) {
        if (event.isPublic) {
            return 'Public';
        } else if (event.involvedFactions && event.involvedFactions.length > 0) {
            return `Visible to ${event.involvedFactions.length} faction(s)`;
        } else if (event.involvedCharacters && event.involvedCharacters.length > 0) {
            return `Visible to ${event.involvedCharacters.length} character(s)`;
        }
        return 'Visibility not specified';
    }
}
