# Event Planning & Resource Management System

## Table of Contents
1. [Business Overview](#business-overview)
2. [User Stories](#user-stories)
3. [Technical Architecture](#technical-architecture)
4. [Database Schema](#database-schema)
5. [Frontend Implementation](#frontend-implementation)
6. [Resource Conflict Detection](#resource-conflict-detection)
7. [Third-Party Libraries](#third-party-libraries)
8. [API Specifications](#api-specifications)
9. [Implementation Phases](#implementation-phases)

---

## Business Overview

### Problem Statement
LARP organizers need to schedule multiple events happening simultaneously across different locations, while managing limited resources (NPCs, technicians, props, items). Manual planning leads to:
- Double-booking of NPCs/staff
- Resource conflicts
- Location overlaps
- Timeline confusion
- Last-minute logistics issues

### Solution
A visual planning system that allows organizers to:
1. Upload a venue map and define game locations via a configurable grid
2. Schedule events with specific time, location, and resource requirements
3. Automatically detect conflicts and resource overlaps
4. Visualize the schedule in calendar and timeline views
5. Export schedules for staff and participants

---

## User Stories

### Epic 1: Venue Map Management

**US-1.1: Upload Venue Map**
```
As a LARP organizer
I want to upload an image of my venue/game location
So that I can visually plan where events will take place
```

**Acceptance Criteria:**
- Support image formats: JPG, PNG, WebP
- Max file size: 10MB
- Preview image before saving
- Ability to replace/update map
- Multiple maps per LARP (indoor/outdoor)

**US-1.2: Configure Map Grid**
```
As a LARP organizer
I want to overlay a customizable grid on my venue map
So that I can define distinct game zones/locations
```

**Acceptance Criteria:**
- Configurable rows (1-50)
- Configurable columns (1-50)
- Grid cells automatically labeled (A1, B2, etc.)
- Toggle grid visibility
- Adjust grid opacity
- Save grid configuration

**US-1.3: Define Game Locations**
```
As a LARP organizer
I want to click on grid cells to create named locations
So that I can reference specific places when planning events
```

**Acceptance Criteria:**
- Click cell to create location
- Name the location (e.g., "Tavern", "Forest Clearing")
- Multi-cell selection for large areas
- Link to existing Place entities
- Color-code locations by type
- Add capacity limits
- Add location descriptions

### Epic 2: Resource Management

**US-2.1: Define Resource Types**
```
As a LARP organizer
I want to categorize different types of resources
So that I can track what's needed for events
```

**Resource Types:**
- **NPCs/Characters**: Long-term NPCs, short-term NPCs, specific character roles
- **Staff**: Game Masters, technicians, safety marshals, photographers
- **Props/Items**: Swords, scrolls, quest items, decorations
- **Equipment**: Sound systems, lights, smoke machines
- **Vehicles**: For transportation or in-game use

**Acceptance Criteria:**
- Create custom resource categories
- Define resource availability periods
- Set resource capacity (how many concurrent uses)
- Mark resources as shareable/exclusive
- Link items to Item entity
- Link NPCs to Character entity

**US-2.2: Resource Pool Management**
```
As a LARP organizer
I want to maintain a pool of available resources
So that I can assign them to events efficiently
```

**Acceptance Criteria:**
- List all resources by category
- Filter by availability
- Set resource schedules (available 10am-4pm)
- Mark resources as reserved
- Track resource usage statistics

### Epic 3: Event Scheduling

**US-3.1: Create Scheduled Event**
```
As a story writer
I want to schedule an event at a specific time and location
So that players know when and where story moments happen
```

**Acceptance Criteria:**
- Link to existing Event/Quest/Thread entities
- Set start time and duration
- Select location from map grid
- Specify required resources
- Add preparation time (setup before event)
- Add cleanup time (teardown after event)
- Set event status (draft, confirmed, in-progress, completed)
- Add organizer notes (not visible to players)

**US-3.2: Resource Assignment**
```
As a LARP organizer
I want to assign resources to scheduled events
So that I ensure everything needed is available
```

**Acceptance Criteria:**
- Drag-and-drop resource assignment
- Auto-suggest available resources
- Highlight conflicts in real-time
- Override conflict warnings with justification
- Bulk assign resources to multiple events
- Clone resource assignments from similar events

**US-3.3: Timeline View**
```
As a LARP organizer
I want to view all scheduled events on a timeline
So that I can see the overall schedule at a glance
```

**Acceptance Criteria:**
- Hourly timeline view
- Filter by location
- Filter by resource
- Filter by thread/quest
- Color-code by event type
- Zoom in/out (15min, 30min, 1hr intervals)
- Export as PDF/image

**US-3.4: Calendar View**
```
As a LARP organizer
I want to view events in a calendar format
So that I can see daily/multi-day schedules
```

**Acceptance Criteria:**
- Day/week/month views
- Click event to see details
- Drag events to reschedule
- Resource utilization overlay
- Export to Google Calendar/iCal

### Epic 4: Conflict Detection

**US-4.1: Real-time Conflict Detection**
```
As a LARP organizer
I want to be warned about scheduling conflicts
So that I can fix issues before they cause problems
```

**Conflict Types:**
- Resource double-booking
- Location capacity exceeded
- Character in multiple places
- Staff overload (too many assignments)
- Timeline impossibilities

**Acceptance Criteria:**
- Real-time validation on save
- Visual indicators (red/yellow warnings)
- Detailed conflict descriptions
- Suggested resolutions
- Conflict history tracking

**US-4.2: Resource Availability Check**
```
As a LARP organizer
I want to see resource availability before assignment
So that I don't create conflicts
```

**Acceptance Criteria:**
- Show available resources for time slot
- Show partially available resources
- Display resource schedule/calendar
- Warning for tight schedules
- Buffer time recommendations

### Epic 5: Export & Communication

**US-5.1: Staff Schedules**
```
As a LARP organizer
I want to generate individual staff schedules
So that everyone knows their assignments
```

**Acceptance Criteria:**
- Per-person schedule
- Per-role schedule
- Include location details
- Include preparation notes
- Export as PDF/printable format
- Send via email

**US-5.2: Location Guides**
```
As a LARP organizer
I want to generate location-based schedules
So that location managers know what's happening in their area
```

**Acceptance Criteria:**
- All events for specific location
- Required resources for location
- Setup/teardown schedules
- Map with highlighted area

---

## Technical Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                    Frontend Layer                            │
├─────────────┬──────────────┬──────────────┬─────────────────┤
│ Map Editor  │ Timeline     │ Calendar     │ Conflict        │
│ (Leaflet)   │ (FullCalendar)│ View        │ Detector        │
│             │              │              │                 │
│ - Grid      │ - Resource   │ - Day/Week   │ - Real-time     │
│ - Locations │   Schedule   │ - Drag/Drop  │ - Validation    │
│ - Upload    │ - Gantt View │ - Filters    │ - Warnings      │
└─────────────┴──────────────┴──────────────┴─────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                    Backend API Layer                         │
├─────────────┬──────────────┬──────────────┬─────────────────┤
│ Map API     │ Schedule API │ Resource API │ Conflict API    │
│             │              │              │                 │
│ - CRUD maps │ - CRUD events│ - CRUD       │ - Detect        │
│ - Grid mgmt │ - Validation │   resources  │ - Resolve       │
│ - Locations │ - Timeline   │ - Assignment │ - Report        │
└─────────────┴──────────────┴──────────────┴─────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                    Service Layer                             │
├─────────────┬──────────────┬──────────────┬─────────────────┤
│ Map Service │ Schedule     │ Resource     │ Conflict        │
│             │ Service      │ Service      │ Service         │
│             │              │              │                 │
│ - Image     │ - Constraint │ - Allocation │ - Algorithms    │
│   storage   │   validation │ - Capacity   │ - Optimization  │
│ - Grid calc │ - Timeline   │   check      │ - Suggestions   │
└─────────────┴──────────────┴──────────────┴─────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                    Data Layer                                │
├─────────────┬──────────────┬──────────────┬─────────────────┤
│ GameMap     │ ScheduledEvent│ Resource    │ ResourceBooking │
│ MapLocation │ EventResource │ ResourceType│ ConflictLog     │
└─────────────┴──────────────┴──────────────┴─────────────────┘
```

---

## Database Schema

### New Entities

#### GameMap
```php
class GameMap
{
    private Uuid $id;
    private Larp $larp;
    private string $name;
    private string $imageFile; // Stored in filesystem
    private int $gridRows;
    private int $gridColumns;
    private float $gridOpacity = 0.5;
    private bool $gridVisible = true;
    private ?string $description;
    private DateTime $createdAt;
    private DateTime $updatedAt;

    /** @var Collection<MapLocation> */
    private Collection $locations;
}
```

#### MapLocation
```php
class MapLocation
{
    private Uuid $id;
    private GameMap $map;
    private ?Place $place; // Link to existing Place entity
    private string $name;
    private string $gridCoordinates; // JSON: ["A1", "A2", "B1", "B2"]
    private ?string $color; // Hex color for visualization
    private ?int $capacity; // Max concurrent events
    private ?string $description;
    private LocationType $type; // indoor, outdoor, special

    /** @var Collection<ScheduledEvent> */
    private Collection $scheduledEvents;
}
```

#### ResourceType (Enum)
```php
enum ResourceType: string
{
    case NPC = 'npc';
    case STAFF_GM = 'staff_gm';
    case STAFF_TECH = 'staff_tech';
    case STAFF_SAFETY = 'staff_safety';
    case STAFF_PHOTO = 'staff_photo';
    case PROP = 'prop';
    case EQUIPMENT = 'equipment';
    case VEHICLE = 'vehicle';
    case OTHER = 'other';
}
```

#### Resource
```php
class Resource
{
    private Uuid $id;
    private Larp $larp;
    private string $name;
    private ResourceType $type;
    private ?string $description;
    private int $quantity = 1; // How many of this resource exist
    private bool $shareable = false; // Can be used by multiple events simultaneously
    private ?DateTime $availableFrom; // When resource becomes available
    private ?DateTime $availableUntil; // When resource is no longer available

    // Optional links to existing entities
    private ?Character $character; // If resource is an NPC character
    private ?Item $item; // If resource is a game item
    private ?LarpParticipant $participant; // If resource is a person

    /** @var Collection<ResourceBooking> */
    private Collection $bookings;
}
```

#### ScheduledEvent
```php
class ScheduledEvent
{
    private Uuid $id;
    private Larp $larp;
    private string $title;
    private ?string $description;
    private DateTime $startTime;
    private DateTime $endTime;
    private ?int $setupMinutes = 0; // Preparation time before event
    private ?int $cleanupMinutes = 0; // Teardown time after event
    private EventStatus $status; // draft, confirmed, in_progress, completed, cancelled
    private ?string $organizerNotes; // Private notes for organizers
    private bool $visibleToPlayers = true;

    // Links to story elements
    private ?Event $event; // Link to existing Event entity
    private ?Quest $quest; // Link to existing Quest entity
    private ?Thread $thread; // Link to existing Thread entity

    // Location
    private ?MapLocation $location;

    // Resources
    /** @var Collection<ResourceBooking> */
    private Collection $resourceBookings;

    /** @var Collection<ScheduledEventConflict> */
    private Collection $conflicts;
}
```

#### ResourceBooking
```php
class ResourceBooking
{
    private Uuid $id;
    private ScheduledEvent $scheduledEvent;
    private Resource $resource;
    private int $quantityNeeded = 1;
    private bool $required = true; // vs. optional/nice-to-have
    private ?string $notes;
    private BookingStatus $status; // pending, confirmed, conflict, cancelled
}
```

#### ScheduledEventConflict
```php
class ScheduledEventConflict
{
    private Uuid $id;
    private ScheduledEvent $event1;
    private ScheduledEvent $event2;
    private ConflictType $type; // resource, location, timeline, character
    private ConflictSeverity $severity; // critical, warning, info
    private string $description;
    private ?string $resolution; // How it was resolved
    private bool $resolved = false;
    private ?User $resolvedBy;
    private ?DateTime $resolvedAt;
    private DateTime $detectedAt;
}
```

#### Supporting Enums
```php
enum LocationType: string
{
    case INDOOR = 'indoor';
    case OUTDOOR = 'outdoor';
    case SPECIAL = 'special';
    case TRANSITION = 'transition';
}

enum EventStatus: string
{
    case DRAFT = 'draft';
    case CONFIRMED = 'confirmed';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CONFLICT = 'conflict';
    case CANCELLED = 'cancelled';
}

enum ConflictType: string
{
    case RESOURCE_DOUBLE_BOOKING = 'resource_double_booking';
    case LOCATION_CAPACITY = 'location_capacity';
    case CHARACTER_IMPOSSIBLE = 'character_impossible';
    case TIMELINE_OVERLAP = 'timeline_overlap';
    case STAFF_OVERLOAD = 'staff_overload';
}

enum ConflictSeverity: string
{
    case CRITICAL = 'critical'; // Must be fixed
    case WARNING = 'warning';   // Should be reviewed
    case INFO = 'info';         // Informational only
}
```

### Database Relationships

```
Larp
 ├─ GameMap (1:N)
 │   └─ MapLocation (1:N)
 │       └─ ScheduledEvent (1:N)
 ├─ Resource (1:N)
 │   └─ ResourceBooking (1:N)
 └─ ScheduledEvent (1:N)
     ├─ ResourceBooking (1:N)
     └─ ScheduledEventConflict (1:N)
```

---

## Frontend Implementation

### Technology Stack

#### Map Editor Component
**Library: Leaflet.js** (MIT License)
- **Why:** Industry standard for custom image overlays, lightweight, excellent documentation
- **Features:** Image overlay, custom grid layer, click handling, zoom/pan
- **CDN/NPM:** `leaflet@1.9.4`

**Additional Plugin: Leaflet.draw** (MIT License)
- **Why:** Draw polygons for multi-cell locations
- **Package:** `leaflet-draw@1.0.4`

#### Calendar/Timeline Component
**Library: FullCalendar** (MIT License)
- **Why:** Professional resource scheduling, timeline view, drag-and-drop built-in
- **Features:** Resource timeline, scheduler, conflict visualization
- **Package:** `@fullcalendar/core@6.1.0` + resource plugins

**Alternative: Timeline.js** (MIT License)
- **Why:** Lightweight, Gantt-style timeline
- **Package:** `vis-timeline@7.7.3`

#### Drag & Drop
**Library: Interact.js** (MIT License)
- **Why:** Smooth drag-and-drop, touch support, constraint validation
- **Package:** `interactjs@1.10.27`

### Map Editor Implementation

```javascript
// assets/controllers/game_map_controller.js
import { Controller } from '@hotwired/stimulus';
import L from 'leaflet';
import 'leaflet-draw';

export default class extends Controller {
    static targets = ['map', 'rows', 'columns', 'opacity'];
    static values = {
        imageUrl: String,
        rows: Number,
        columns: Number,
        locations: Array,
        larpId: String
    };

    connect() {
        this.initializeMap();
        this.loadMapImage();
        this.drawGrid();
        this.loadLocations();
    }

    initializeMap() {
        // Initialize Leaflet map with custom image bounds
        const bounds = [[0, 0], [1000, 1000]]; // Adjust based on image
        this.map = L.map(this.mapTarget, {
            crs: L.CRS.Simple,
            minZoom: -2,
            maxZoom: 2
        });

        this.gridLayer = L.layerGroup().addTo(this.map);
        this.locationLayer = L.layerGroup().addTo(this.map);
    }

    loadMapImage() {
        const imageOverlay = L.imageOverlay(
            this.imageUrlValue,
            [[0, 0], [1000, 1000]]
        );
        imageOverlay.addTo(this.map);
        this.map.fitBounds([[0, 0], [1000, 1000]]);
    }

    drawGrid() {
        this.gridLayer.clearLayers();

        const rows = this.rowsValue;
        const columns = this.columnsValue;
        const cellWidth = 1000 / columns;
        const cellHeight = 1000 / rows;

        // Draw vertical lines
        for (let i = 0; i <= columns; i++) {
            const x = i * cellWidth;
            L.polyline([[0, x], [1000, x]], {
                color: '#333',
                weight: 1,
                opacity: 0.5
            }).addTo(this.gridLayer);
        }

        // Draw horizontal lines
        for (let i = 0; i <= rows; i++) {
            const y = i * cellHeight;
            L.polyline([[y, 0], [y, 1000]], {
                color: '#333',
                weight: 1,
                opacity: 0.5
            }).addTo(this.gridLayer);
        }

        // Add cell labels
        this.addCellLabels(rows, columns, cellWidth, cellHeight);
    }

    addCellLabels(rows, columns, cellWidth, cellHeight) {
        for (let row = 0; row < rows; row++) {
            for (let col = 0; col < columns; col++) {
                const label = this.getCellLabel(row, col);
                const x = col * cellWidth + cellWidth / 2;
                const y = row * cellHeight + cellHeight / 2;

                L.marker([y, x], {
                    icon: L.divIcon({
                        html: `<div class="grid-label">${label}</div>`,
                        className: 'grid-cell-label',
                        iconSize: [40, 20]
                    })
                }).addTo(this.gridLayer);
            }
        }
    }

    getCellLabel(row, col) {
        // Convert to Excel-style labels: A1, B2, etc.
        const colLabel = String.fromCharCode(65 + col); // A, B, C...
        const rowLabel = row + 1;
        return `${colLabel}${rowLabel}`;
    }

    // Handle cell click to create location
    onMapClick(e) {
        const cell = this.getCellFromCoords(e.latlng);
        this.openLocationDialog(cell);
    }

    getCellFromCoords(latlng) {
        const cellWidth = 1000 / this.columnsValue;
        const cellHeight = 1000 / this.rowsValue;
        const col = Math.floor(latlng.lng / cellWidth);
        const row = Math.floor(latlng.lat / cellHeight);
        return this.getCellLabel(row, col);
    }

    // Location management
    loadLocations() {
        this.locationsValue.forEach(location => {
            this.renderLocation(location);
        });
    }

    renderLocation(location) {
        const cells = JSON.parse(location.gridCoordinates);
        const bounds = this.getCellsBounds(cells);

        const polygon = L.rectangle(bounds, {
            color: location.color || '#3388ff',
            fillOpacity: 0.3,
            weight: 2
        });

        polygon.bindPopup(`
            <b>${location.name}</b><br>
            ${location.description || ''}<br>
            <a href="/larp/${this.larpIdValue}/location/${location.id}/edit">Edit</a>
        `);

        polygon.addTo(this.locationLayer);
    }

    updateGridRows() {
        this.rowsValue = parseInt(this.rowsTarget.value);
        this.drawGrid();
    }

    updateGridColumns() {
        this.columnsValue = parseInt(this.columnsTarget.value);
        this.drawGrid();
    }

    updateOpacity() {
        const opacity = parseFloat(this.opacityTarget.value);
        this.gridLayer.setStyle({ opacity });
    }
}
```

### Timeline View Implementation

```javascript
// assets/controllers/event_timeline_controller.js
import { Controller } from '@hotwired/stimulus';
import { Calendar } from '@fullcalendar/core';
import resourceTimelinePlugin from '@fullcalendar/resource-timeline';
import interactionPlugin from '@fullcalendar/interaction';

export default class extends Controller {
    static targets = ['calendar'];
    static values = {
        events: Array,
        resources: Array,
        larpId: String
    };

    connect() {
        this.initializeCalendar();
    }

    initializeCalendar() {
        this.calendar = new Calendar(this.calendarTarget, {
            plugins: [resourceTimelinePlugin, interactionPlugin],
            initialView: 'resourceTimelineDay',
            resourceAreaHeaderContent: 'Resources',
            resources: this.resourcesValue,
            events: this.eventsValue,

            // Drag and drop
            editable: true,
            droppable: true,
            eventDrop: this.handleEventDrop.bind(this),
            eventResize: this.handleEventResize.bind(this),

            // Event rendering
            eventContent: this.renderEvent.bind(this),

            // Conflict detection
            eventOverlap: (stillEvent, movingEvent) => {
                return this.checkOverlapAllowed(stillEvent, movingEvent);
            },

            // Custom views
            views: {
                resourceTimelineDay: {
                    slotDuration: '00:15:00',
                    slotLabelInterval: '01:00'
                }
            },

            // Styling
            height: 'auto',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'resourceTimelineDay,resourceTimelineWeek'
            }
        });

        this.calendar.render();
    }

    renderEvent(info) {
        const event = info.event;
        const conflicts = event.extendedProps.conflicts || [];
        const hasConflicts = conflicts.length > 0;

        return {
            html: `
                <div class="fc-event-content ${hasConflicts ? 'has-conflicts' : ''}">
                    <div class="fc-event-title">${event.title}</div>
                    ${hasConflicts ? '<i class="fas fa-exclamation-triangle"></i>' : ''}
                    <div class="fc-event-location">${event.extendedProps.location}</div>
                </div>
            `
        };
    }

    handleEventDrop(info) {
        // Update event via AJAX
        this.updateEvent(info.event);
        this.checkConflicts(info.event);
    }

    handleEventResize(info) {
        this.updateEvent(info.event);
        this.checkConflicts(info.event);
    }

    async updateEvent(event) {
        const response = await fetch(`/api/larp/${this.larpIdValue}/scheduled-events/${event.id}`, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                startTime: event.start,
                endTime: event.end,
                resourceId: event.getResources()[0]?.id
            })
        });

        if (!response.ok) {
            info.revert();
            alert('Failed to update event');
        }
    }

    async checkConflicts(event) {
        const response = await fetch(
            `/api/larp/${this.larpIdValue}/scheduled-events/${event.id}/conflicts`
        );
        const conflicts = await response.json();

        if (conflicts.length > 0) {
            this.showConflictWarning(event, conflicts);
        }
    }

    checkOverlapAllowed(stillEvent, movingEvent) {
        // Check if events share resources
        const stillResources = stillEvent.getResources();
        const movingResources = movingEvent.getResources();

        const sharedResources = stillResources.filter(r1 =>
            movingResources.some(r2 => r2.id === r1.id)
        );

        if (sharedResources.length > 0) {
            // Check if resources are shareable
            return sharedResources.every(r => r.extendedProps.shareable);
        }

        return true; // No shared resources, allow overlap
    }

    showConflictWarning(event, conflicts) {
        // Show modal or notification about conflicts
        const message = conflicts.map(c => c.description).join('\n');
        alert(`Conflict detected:\n${message}`);
    }
}
```

---

## Resource Conflict Detection

### Conflict Detection Service

```php
// src/Service/EventPlanning/ConflictDetectionService.php

namespace App\Service\EventPlanning;

use App\Domain\EventPlanning\Entity\Enum\ConflictSeverity;use App\Domain\EventPlanning\Entity\Enum\ConflictType;use App\Domain\EventPlanning\Entity\ScheduledEvent;use App\Domain\EventPlanning\Entity\ScheduledEventConflict;use App\Entity\Resource;

class ConflictDetectionService
{
    /**
     * Detect all conflicts for a scheduled event
     */
    public function detectConflicts(ScheduledEvent $event): array
    {
        $conflicts = [];

        $conflicts = array_merge($conflicts, $this->detectResourceConflicts($event));
        $conflicts = array_merge($conflicts, $this->detectLocationConflicts($event));
        $conflicts = array_merge($conflicts, $this->detectCharacterConflicts($event));
        $conflicts = array_merge($conflicts, $this->detectTimelineConflicts($event));

        return $conflicts;
    }

    /**
     * Detect resource double-booking
     */
    private function detectResourceConflicts(ScheduledEvent $event): array
    {
        $conflicts = [];

        foreach ($event->getResourceBookings() as $booking) {
            $resource = $booking->getResource();

            // Skip if resource is shareable
            if ($resource->isShareable()) {
                continue;
            }

            // Find overlapping events using the same resource
            $overlappingBookings = $this->findOverlappingResourceBookings(
                $resource,
                $event->getEffectiveStartTime(), // Includes setup time
                $event->getEffectiveEndTime()    // Includes cleanup time
            );

            foreach ($overlappingBookings as $otherBooking) {
                if ($otherBooking->getScheduledEvent()->getId() !== $event->getId()) {
                    $conflicts[] = new ScheduledEventConflict(
                        event1: $event,
                        event2: $otherBooking->getScheduledEvent(),
                        type: ConflictType::RESOURCE_DOUBLE_BOOKING,
                        severity: $booking->isRequired()
                            ? ConflictSeverity::CRITICAL
                            : ConflictSeverity::WARNING,
                        description: sprintf(
                            'Resource "%s" is already booked for "%s" from %s to %s',
                            $resource->getName(),
                            $otherBooking->getScheduledEvent()->getTitle(),
                            $otherBooking->getScheduledEvent()->getStartTime()->format('H:i'),
                            $otherBooking->getScheduledEvent()->getEndTime()->format('H:i')
                        )
                    );
                }
            }
        }

        return $conflicts;
    }

    /**
     * Detect location capacity exceeded
     */
    private function detectLocationConflicts(ScheduledEvent $event): array
    {
        $conflicts = [];
        $location = $event->getLocation();

        if (!$location || !$location->getCapacity()) {
            return [];
        }

        // Count concurrent events at same location
        $concurrentEvents = $this->findConcurrentEventsAtLocation(
            $location,
            $event->getStartTime(),
            $event->getEndTime()
        );

        if (count($concurrentEvents) >= $location->getCapacity()) {
            $conflicts[] = new ScheduledEventConflict(
                event1: $event,
                event2: $concurrentEvents[0], // Reference one of the concurrent events
                type: ConflictType::LOCATION_CAPACITY,
                severity: ConflictSeverity::CRITICAL,
                description: sprintf(
                    'Location "%s" capacity (%d) exceeded. %d events scheduled at same time.',
                    $location->getName(),
                    $location->getCapacity(),
                    count($concurrentEvents) + 1
                )
            );
        }

        return $conflicts;
    }

    /**
     * Detect character timeline impossibilities
     */
    private function detectCharacterConflicts(ScheduledEvent $event): array
    {
        $conflicts = [];

        // Get all characters involved in this event
        $characters = $this->getInvolvedCharacters($event);

        foreach ($characters as $character) {
            // Find other events involving this character at overlapping times
            $overlappingEvents = $this->findOverlappingEventsForCharacter(
                $character,
                $event->getStartTime(),
                $event->getEndTime()
            );

            foreach ($overlappingEvents as $otherEvent) {
                if ($otherEvent->getId() !== $event->getId()) {
                    // Check if events are at different locations
                    if ($this->areLocationsConflicting($event, $otherEvent)) {
                        $conflicts[] = new ScheduledEventConflict(
                            event1: $event,
                            event2: $otherEvent,
                            type: ConflictType::CHARACTER_IMPOSSIBLE,
                            severity: ConflictSeverity::CRITICAL,
                            description: sprintf(
                                'Character "%s" cannot be in two places at once: "%s" and "%s"',
                                $character->getTitle(),
                                $event->getLocation()?->getName() ?? 'Unknown',
                                $otherEvent->getLocation()?->getName() ?? 'Unknown'
                            )
                        );
                    }
                }
            }
        }

        return $conflicts;
    }

    /**
     * Detect timeline overlaps and dependencies
     */
    private function detectTimelineConflicts(ScheduledEvent $event): array
    {
        $conflicts = [];

        // Check if event has dependencies (requires another event to complete first)
        // This would require adding a dependencies relationship to ScheduledEvent

        // Check if setup time overlaps with another event's active time
        if ($event->getSetupMinutes() > 0) {
            $setupStart = (clone $event->getStartTime())
                ->modify('-' . $event->getSetupMinutes() . ' minutes');

            $setupConflicts = $this->findEventsAtLocation(
                $event->getLocation(),
                $setupStart,
                $event->getStartTime()
            );

            foreach ($setupConflicts as $conflictEvent) {
                $conflicts[] = new ScheduledEventConflict(
                    event1: $event,
                    event2: $conflictEvent,
                    type: ConflictType::TIMELINE_OVERLAP,
                    severity: ConflictSeverity::WARNING,
                    description: sprintf(
                        'Setup time conflicts with "%s" at location "%s"',
                        $conflictEvent->getTitle(),
                        $event->getLocation()->getName()
                    )
                );
            }
        }

        return $conflicts;
    }

    /**
     * Helper: Get all characters involved in an event
     */
    private function getInvolvedCharacters(ScheduledEvent $event): array
    {
        $characters = [];

        // From linked Event entity
        if ($event->getEvent()) {
            $characters = array_merge(
                $characters,
                $event->getEvent()->getInvolvedCharacters()->toArray()
            );
        }

        // From linked Quest entity
        if ($event->getQuest()) {
            $characters = array_merge(
                $characters,
                $event->getQuest()->getInvolvedCharacters()->toArray()
            );
        }

        // From resource bookings (NPC characters)
        foreach ($event->getResourceBookings() as $booking) {
            if ($booking->getResource()->getCharacter()) {
                $characters[] = $booking->getResource()->getCharacter();
            }
        }

        return array_unique($characters);
    }

    /**
     * Check if two events are at conflicting locations
     */
    private function areLocationsConflicting(
        ScheduledEvent $event1,
        ScheduledEvent $event2
    ): bool {
        $loc1 = $event1->getLocation();
        $loc2 = $event2->getLocation();

        if (!$loc1 || !$loc2) {
            return false; // Can't determine, assume no conflict
        }

        // Same location = conflict
        if ($loc1->getId() === $loc2->getId()) {
            return true;
        }

        // Different map = no conflict
        if ($loc1->getMap()->getId() !== $loc2->getMap()->getId()) {
            return false;
        }

        // Check if locations are adjacent (might allow quick travel)
        // For now, different locations = conflict
        return true;
    }
}
```

### Conflict Resolution Suggestions

```php
// src/Service/EventPlanning/ConflictResolutionService.php

class ConflictResolutionService
{
    /**
     * Suggest resolutions for a conflict
     */
    public function suggestResolutions(ScheduledEventConflict $conflict): array
    {
        return match ($conflict->getType()) {
            ConflictType::RESOURCE_DOUBLE_BOOKING =>
                $this->suggestResourceResolutions($conflict),
            ConflictType::LOCATION_CAPACITY =>
                $this->suggestLocationResolutions($conflict),
            ConflictType::CHARACTER_IMPOSSIBLE =>
                $this->suggestCharacterResolutions($conflict),
            ConflictType::TIMELINE_OVERLAP =>
                $this->suggestTimelineResolutions($conflict),
            default => []
        };
    }

    private function suggestResourceResolutions(ScheduledEventConflict $conflict): array
    {
        $suggestions = [];

        // Find alternative resources
        $conflictedResource = $this->findConflictedResource($conflict);
        $alternativeResources = $this->findAlternativeResources(
            $conflictedResource,
            $conflict->getEvent1()->getStartTime(),
            $conflict->getEvent1()->getEndTime()
        );

        foreach ($alternativeResources as $alternative) {
            $suggestions[] = [
                'type' => 'replace_resource',
                'description' => sprintf(
                    'Use "%s" instead of "%s"',
                    $alternative->getName(),
                    $conflictedResource->getName()
                ),
                'action' => [
                    'resource_id' => $alternative->getId()
                ]
            ];
        }

        // Suggest rescheduling
        $availableSlots = $this->findAvailableTimeSlots(
            $conflictedResource,
            $conflict->getEvent1()->getStartTime()->format('Y-m-d')
        );

        foreach ($availableSlots as $slot) {
            $suggestions[] = [
                'type' => 'reschedule',
                'description' => sprintf(
                    'Move event to %s - %s',
                    $slot['start']->format('H:i'),
                    $slot['end']->format('H:i')
                ),
                'action' => [
                    'start_time' => $slot['start'],
                    'end_time' => $slot['end']
                ]
            ];
        }

        return $suggestions;
    }

    private function suggestLocationResolutions(ScheduledEventConflict $conflict): array
    {
        $suggestions = [];

        // Find alternative locations
        $location = $conflict->getEvent1()->getLocation();
        $alternativeLocations = $this->findAlternativeLocations(
            $location,
            $conflict->getEvent1()->getStartTime(),
            $conflict->getEvent1()->getEndTime()
        );

        foreach ($alternativeLocations as $altLocation) {
            $suggestions[] = [
                'type' => 'change_location',
                'description' => sprintf(
                    'Move to "%s" instead',
                    $altLocation->getName()
                ),
                'action' => [
                    'location_id' => $altLocation->getId()
                ]
            ];
        }

        return $suggestions;
    }

    private function suggestCharacterResolutions(ScheduledEventConflict $conflict): array
    {
        return [
            [
                'type' => 'reschedule',
                'description' => 'Schedule events at different times',
                'action' => []
            ],
            [
                'type' => 'merge_events',
                'description' => 'Combine into single event if story allows',
                'action' => []
            ]
        ];
    }
}
```

---

## Third-Party Libraries

### Required NPM Packages

```json
{
  "dependencies": {
    "leaflet": "^1.9.4",
    "leaflet-draw": "^1.0.4",
    "@fullcalendar/core": "^6.1.10",
    "@fullcalendar/daygrid": "^6.1.10",
    "@fullcalendar/timegrid": "^6.1.10",
    "@fullcalendar/resource-timeline": "^6.1.10",
    "@fullcalendar/interaction": "^6.1.10",
    "interactjs": "^1.10.27"
  }
}
```

### CSS Files to Import

```scss
// assets/styles/event_planning.scss

@import 'leaflet/dist/leaflet.css';
@import 'leaflet-draw/dist/leaflet.draw.css';
@import '@fullcalendar/core/main.css';
@import '@fullcalendar/daygrid/main.css';
@import '@fullcalendar/timegrid/main.css';
@import '@fullcalendar/resource-timeline/main.css';

.game-map-container {
    height: 600px;
    border: 1px solid #ddd;
    border-radius: 4px;

    .grid-cell-label {
        background: rgba(255, 255, 255, 0.8);
        padding: 2px 4px;
        border-radius: 3px;
        font-size: 12px;
        pointer-events: none;
    }
}

.event-timeline-container {
    .fc-event-content {
        padding: 2px 4px;

        &.has-conflicts {
            border-left: 3px solid #dc3545;
            background: #fff3cd;
        }

        .fc-event-location {
            font-size: 0.85em;
            color: #666;
        }
    }
}

.conflict-warning {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 12px;
    margin: 8px 0;

    &.critical {
        background: #f8d7da;
        border-color: #dc3545;
    }
}
```

---

## API Specifications

### REST Endpoints

#### Game Maps

```
GET    /api/larp/{larpId}/maps
POST   /api/larp/{larpId}/maps
GET    /api/larp/{larpId}/maps/{mapId}
PATCH  /api/larp/{larpId}/maps/{mapId}
DELETE /api/larp/{larpId}/maps/{mapId}
POST   /api/larp/{larpId}/maps/{mapId}/upload-image
```

#### Map Locations

```
GET    /api/larp/{larpId}/maps/{mapId}/locations
POST   /api/larp/{larpId}/maps/{mapId}/locations
GET    /api/larp/{larpId}/locations/{locationId}
PATCH  /api/larp/{larpId}/locations/{locationId}
DELETE /api/larp/{larpId}/locations/{locationId}
```

#### Resources

```
GET    /api/larp/{larpId}/resources
POST   /api/larp/{larpId}/resources
GET    /api/larp/{larpId}/resources/{resourceId}
PATCH  /api/larp/{larpId}/resources/{resourceId}
DELETE /api/larp/{larpId}/resources/{resourceId}
GET    /api/larp/{larpId}/resources/{resourceId}/availability?start=...&end=...
```

#### Scheduled Events

```
GET    /api/larp/{larpId}/scheduled-events
POST   /api/larp/{larpId}/scheduled-events
GET    /api/larp/{larpId}/scheduled-events/{eventId}
PATCH  /api/larp/{larpId}/scheduled-events/{eventId}
DELETE /api/larp/{larpId}/scheduled-events/{eventId}
GET    /api/larp/{larpId}/scheduled-events/{eventId}/conflicts
POST   /api/larp/{larpId}/scheduled-events/{eventId}/conflicts/{conflictId}/resolve
```

#### Resource Bookings

```
POST   /api/larp/{larpId}/scheduled-events/{eventId}/bookings
PATCH  /api/larp/{larpId}/scheduled-events/{eventId}/bookings/{bookingId}
DELETE /api/larp/{larpId}/scheduled-events/{eventId}/bookings/{bookingId}
```

### Example API Responses

#### GET /api/larp/{larpId}/scheduled-events

```json
{
  "data": [
    {
      "id": "uuid",
      "title": "Tavern Fight Scene",
      "description": "Main conflict between rival factions",
      "startTime": "2025-10-15T14:00:00Z",
      "endTime": "2025-10-15T14:30:00Z",
      "setupMinutes": 15,
      "cleanupMinutes": 10,
      "status": "confirmed",
      "location": {
        "id": "uuid",
        "name": "Main Tavern",
        "gridCoordinates": ["A1", "A2", "B1", "B2"]
      },
      "resourceBookings": [
        {
          "id": "uuid",
          "resource": {
            "id": "uuid",
            "name": "Bartender NPC",
            "type": "npc"
          },
          "quantityNeeded": 1,
          "required": true
        }
      ],
      "conflicts": [
        {
          "id": "uuid",
          "type": "resource_double_booking",
          "severity": "critical",
          "description": "Bartender NPC already booked for Market Scene",
          "resolved": false
        }
      ],
      "thread": {
        "id": "uuid",
        "title": "Faction Rivalry"
      }
    }
  ],
  "meta": {
    "total": 45,
    "page": 1,
    "perPage": 20
  }
}
```

---

## Implementation Phases

### Phase 1: Core Map & Location System (Week 1-2)

**Goals:**
- Upload and display venue maps
- Configure grid overlay
- Define locations on grid

**Tasks:**
1. Create database entities: `GameMap`, `MapLocation`
2. Create migration
3. Build map upload form
4. Implement Leaflet map controller
5. Grid configuration UI
6. Location creation UI
7. CRUD controllers for maps and locations

**Deliverables:**
- Organizers can upload maps
- Grid can be configured and displayed
- Locations can be created by clicking cells
- Basic map view page

### Phase 2: Resource Management (Week 3)

**Goals:**
- Define resource types and create resource pool
- Link resources to existing entities

**Tasks:**
1. Create entities: `Resource`, `ResourceType` enum
2. Create migration
3. Build resource CRUD interface
4. Link resources to Characters/Items/Participants
5. Resource availability tracking

**Deliverables:**
- Resource library with types
- Ability to define resource availability
- Resource listing and filtering

### Phase 3: Event Scheduling (Week 4-5)

**Goals:**
- Create scheduled events
- Assign resources to events
- Basic timeline view

**Tasks:**
1. Create entities: `ScheduledEvent`, `ResourceBooking`, enums
2. Create migration
3. Build event creation form
4. Resource assignment UI
5. Implement FullCalendar timeline
6. Link to existing Event/Quest/Thread entities

**Deliverables:**
- Event scheduling interface
- Resource assignment
- Timeline visualization
- Drag-and-drop rescheduling

### Phase 4: Conflict Detection (Week 6)

**Goals:**
- Detect resource and location conflicts
- Display warnings to users

**Tasks:**
1. Implement `ConflictDetectionService`
2. Create entities: `ScheduledEventConflict`, enums
3. Real-time conflict checking on save
4. Visual conflict indicators
5. Conflict dashboard

**Deliverables:**
- Automatic conflict detection
- Conflict warnings in UI
- Conflict dashboard with all issues

### Phase 5: Advanced Features (Week 7-8)

**Goals:**
- Conflict resolution suggestions
- Export schedules
- Character timeline validation

**Tasks:**
1. Implement `ConflictResolutionService`
2. Character timeline tracking
3. Export to PDF/Calendar
4. Staff schedule generation
5. Location schedule generation

**Deliverables:**
- Automatic resolution suggestions
- Schedule exports in multiple formats
- Character timeline validation
- Comprehensive staff schedules

### Phase 6: Polish & Optimization (Week 9)

**Goals:**
- Performance optimization
- UI/UX improvements
- Testing

**Tasks:**
1. Database query optimization
2. Caching strategy
3. Frontend performance tuning
4. User testing and feedback
5. Documentation

**Deliverables:**
- Fast, responsive interface
- Comprehensive user documentation
- Admin guide
- Video tutorials

---

## Testing Strategy

### Unit Tests

```php
// tests/Service/ConflictDetectionServiceTest.php

class ConflictDetectionServiceTest extends KernelTestCase
{
    public function testDetectsResourceDoubleBooking(): void
    {
        $service = $this->getContainer()->get(ConflictDetectionService::class);

        // Create two events with same resource at overlapping times
        $event1 = $this->createScheduledEvent(
            startTime: new DateTime('2025-10-15 14:00'),
            endTime: new DateTime('2025-10-15 15:00')
        );
        $event2 = $this->createScheduledEvent(
            startTime: new DateTime('2025-10-15 14:30'),
            endTime: new DateTime('2025-10-15 15:30')
        );

        $resource = $this->createResource('NPC Guard');
        $this->addResourceBooking($event1, $resource);
        $this->addResourceBooking($event2, $resource);

        $conflicts = $service->detectConflicts($event2);

        $this->assertCount(1, $conflicts);
        $this->assertEquals(
            ConflictType::RESOURCE_DOUBLE_BOOKING,
            $conflicts[0]->getType()
        );
    }

    public function testAllowsShareableResources(): void
    {
        $service = $this->getContainer()->get(ConflictDetectionService::class);

        $event1 = $this->createScheduledEvent(
            startTime: new DateTime('2025-10-15 14:00'),
            endTime: new DateTime('2025-10-15 15:00')
        );
        $event2 = $this->createScheduledEvent(
            startTime: new DateTime('2025-10-15 14:30'),
            endTime: new DateTime('2025-10-15 15:30')
        );

        $shareableResource = $this->createResource('Sound System', shareable: true);
        $this->addResourceBooking($event1, $shareableResource);
        $this->addResourceBooking($event2, $shareableResource);

        $conflicts = $service->detectConflicts($event2);

        $this->assertCount(0, $conflicts);
    }
}
```

### Integration Tests

```php
// tests/Controller/ScheduledEventControllerTest.php

class ScheduledEventControllerTest extends WebTestCase
{
    public function testCreateScheduledEvent(): void
    {
        $client = static::createClient();
        $client->loginUser($this->createUser());

        $crawler = $client->request('GET', '/larp/test-larp-id/scheduled-events/create');

        $form = $crawler->selectButton('Save')->form([
            'scheduled_event[title]' => 'Test Event',
            'scheduled_event[startTime]' => '2025-10-15 14:00',
            'scheduled_event[endTime]' => '2025-10-15 15:00',
            'scheduled_event[location]' => 'location-id',
        ]);

        $client->submit($form);

        $this->assertResponseRedirects();
        $this->assertNotNull(
            $this->entityManager->getRepository(ScheduledEvent::class)
                ->findOneBy(['title' => 'Test Event'])
        );
    }
}
```

---

## Future Enhancements

### Mobile App
- Native mobile app for staff
- Real-time notifications
- Offline mode for schedules
- GPS-based location check-ins

### AI-Powered Scheduling
- Automatic optimal scheduling
- Machine learning for conflict prediction
- Resource optimization algorithms
- Suggest best event order for story flow

### Player-Facing Features
- Player schedule view (their character's events only)
- Event check-in QR codes
- Push notifications for upcoming events
- Map navigation for players

### Advanced Analytics
- Resource utilization reports
- Location heat maps
- Staff workload balance
- Event success metrics

### Integration Enhancements
- Sync with Google Calendar
- Export to scheduling software (When2Meet, Doodle)
- Import from spreadsheets
- Webhook notifications

---

## Security Considerations

### Access Control
- Only organizers can edit maps and schedules
- Story writers can view and suggest changes
- Players see limited information
- Resource managers have specific permissions

### Data Privacy
- Organizer notes never visible to players
- Resource personal information protected
- Audit logs for schedule changes
- GDPR compliance for participant data

### File Upload Security
- Image validation (magic bytes check)
- Size limits enforced
- Virus scanning
- Secure file storage (outside web root)

---

## Performance Considerations

### Database Optimization
- Index on `scheduledEvent.startTime` and `endTime`
- Index on `resourceBooking.resource_id`
- Materialized view for resource availability
- Query result caching

### Frontend Optimization
- Lazy load map images
- Virtual scrolling for large resource lists
- Debounce drag operations
- Progressive enhancement

### Caching Strategy
- Cache resource availability calculations
- Cache map grid calculations
- Redis for real-time conflict checks
- Invalidate on schedule changes

---

## Conclusion

This Event Planning & Resource Management system provides comprehensive tools for LARP organizers to:

1. **Visualize** their venue with custom maps and configurable grids
2. **Schedule** events with precise timing and location
3. **Manage** resources (NPCs, staff, props) efficiently
4. **Detect** conflicts automatically before they become problems
5. **Export** schedules for all stakeholders

By leveraging free, open-source libraries like Leaflet.js and FullCalendar, this system can be implemented without licensing costs while maintaining professional quality and flexibility.

The phased implementation approach ensures that core features are delivered quickly, with advanced features added incrementally based on user feedback and priorities.
