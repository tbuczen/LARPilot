# Event Planning System - POC Implementation

## Overview

This Proof of Concept (POC) implements the core functionality of the Event Planning & Resource Management system for LARPilot. The system helps LARP organizers schedule events, manage resources (NPCs, staff, props), and detect scheduling conflicts.

## 📋 Usage Guide


### Access the System
1. Navigate to any LARP in backoffice
2. Click "Event Planner" dropdown in menu
3. Choose:
   - **Calendar** - Visual week view of all events
   - **Scheduled Events** - List/create/edit events
   - **Resources** - Manage NPCs, staff, props

### Create Resources
1. Go to Resources → Create Resource
2. Fill in:
   - Name (e.g., "NPC Guard #1")
   - Type (NPC, Staff, Prop, Equipment, etc.)
   - Quantity available
   - Shareable (yes/no)
   - Availability window (optional)
   - Link to Character/Item/Participant (optional)

### Schedule Events
1. Go to Events → Create Event
2. Fill in:
   - Title and description
   - Start/End time
   - Setup/Cleanup buffer times
   - Location (from GameMap locations)
   - Story links (Quest/Thread/Event)
   - Status and visibility

### View Conflicts
- **Automatic Detection**: Conflicts shown on save
- **Visual Indicators**:
  - Red badge on event list
  - Yellow row highlighting
  - Detailed conflict descriptions
- **Calendar**: Events with conflicts shown in red