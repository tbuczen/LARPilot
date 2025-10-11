# Decision Tree System for Quests and Threads

## Overview

The Decision Tree system provides LARP organizers with a visual, interactive tool to design branching narratives for Quests and Threads. It enables mapping out player choices, consequences, and story flows in a graph-based interface.

## Purpose

LARP events often involve complex, branching storylines where player decisions affect outcomes. The Decision Tree system helps organizers:

1. **Visualize narrative flow**: See the complete story structure at a glance
2. **Plan consequences**: Map out what happens based on player choices
3. **Track dependencies**: Identify which story objects (characters, items, places) are involved at each decision point
4. **Ensure consistency**: Verify that all story branches connect logically
5. **Document gameplay**: Create a reference for game masters during the event

## Architecture

### Current State

**Database Schema**:
- `Quest` entity has `decisionTree` field (JSONB, nullable) - see `src/Entity/StoryObject/Quest.php:36-37`
- `Thread` entity has `decisionTree` field (JSONB, nullable) - see `src/Entity/StoryObject/Thread.php:39-40`

**Backend Routes**:
- `backoffice_larp_story_quest_tree`: `/larp/{larp}/story/quest/{quest}/tree`
- `backoffice_larp_story_thread_tree`: `/larp/{larp}/story/thread/{thread}/tree`

**Controllers**:
- `QuestController::tree()` - handles GET/POST for quest decision trees (`src/Controller/Backoffice/Story/QuestController.php:81-99`)
- `ThreadController::tree()` - handles GET/POST for thread decision trees (`src/Controller/Backoffice/Story/ThreadController.php:87-105`)

**Frontend**:
- Templates use placeholder Stimulus controller `decision-tree` (not yet implemented)
- JSON data passed from backend via `data-decision-tree-elements-value` attribute
- Hidden form input stores serialized tree data for submission

**Existing Graph Visualization**:
- `story_graph_controller.js` uses Cytoscape.js to render story object relationships
- Supports all StoryObject types: Character, Thread, Quest, Event, Item, Place, Faction
- Color-coded nodes by type with interactive features

## Proposed Design

### Data Model

The `decisionTree` JSONB field should store a graph structure:

```json
{
  "nodes": [
    {
      "id": "node-uuid",
      "type": "start|decision|outcome|reference|end",
      "title": "Node title/question",
      "description": "Detailed description of this decision point",
      "position": { "x": 100, "y": 200 },
      "metadata": {
        "storyObjects": [
          {
            "type": "character",
            "id": "uuid",
            "title": "Character Name",
            "role": "involved|mentioned|required"
          },
          {
            "type": "item",
            "id": "uuid",
            "title": "Magic Sword",
            "role": "required"
          }
        ],
        "conditions": [
          {
            "type": "has_item|character_present|faction_allied|quest_completed",
            "target": "uuid",
            "value": true
          }
        ],
        "duration": "15 minutes",
        "location": "uuid of Place",
        "tags": ["combat", "diplomacy"]
      }
    }
  ],
  "edges": [
    {
      "id": "edge-uuid",
      "source": "node-uuid",
      "target": "node-uuid",
      "label": "Player chooses to...",
      "type": "choice|consequence|reference",
      "metadata": {
        "consequences": [
          {
            "type": "gain_item|lose_item|relationship_change|quest_update",
            "target": "uuid",
            "value": "+1 reputation"
          }
        ]
      }
    }
  ],
  "metadata": {
    "version": "1.0",
    "lastModified": "2025-10-11T12:00:00Z",
    "layout": "dagre"
  }
}
```

### Node Types

1. **Start Node**: Entry point for the quest/thread (single node)
2. **Decision Node**: Player must make a choice (branches to multiple outcomes)
3. **Outcome Node**: Result of a decision (may lead to more decisions)
4. **Reference Node**: Links to another StoryObject (Quest, Thread, Event)
5. **End Node**: Terminal point for a story branch (success, failure, continuation)

### Edge Types

1. **Choice Edge**: Represents a player decision ("Attack", "Negotiate", "Flee")
2. **Consequence Edge**: Automatic outcome based on conditions
3. **Reference Edge**: Connection to external story object

### Story Object Integration

Each node can reference multiple story objects with roles:

- **Required**: Must be present for this branch to occur
- **Involved**: Directly participates in this decision point
- **Mentioned**: Referenced in the narrative but not actively involved
- **Rewarded**: Gained as consequence
- **Lost**: Removed as consequence

**Supported StoryObject Types**:
- Character
- Item
- Place
- Faction
- Quest (for branching/linking)
- Thread (for cross-thread dependencies)
- Event (for timeline dependencies)

### Conditions & Consequences

**Condition Types**:
- `has_item`: Player possesses specific item
- `character_present`: Character is in scene
- `character_state`: Character has specific status (alive, allied, etc.)
- `faction_reputation`: Player's standing with faction meets threshold
- `quest_completed`: Previous quest finished
- `time_constraint`: Must occur within time window
- `location_match`: Must be at specific place

**Consequence Types**:
- `gain_item`: Add item to inventory
- `lose_item`: Remove item from inventory
- `relationship_change`: Modify faction/character relationship (+/-reputation)
- `quest_update`: Trigger/complete related quest
- `character_state_change`: Modify character status
- `information_reveal`: Unlock lore/clues

## User Stories

### Story 1: Quest Designer Creates Branching Quest

**As a** LARP organizer
**I want to** design a multi-path quest with player choices
**So that** players experience different outcomes based on their decisions

**Acceptance Criteria**:
1. Open quest edit page and navigate to "Decision Tree" tab
2. Create start node with quest introduction
3. Add decision node: "The village elder asks you to retrieve the stolen artifact"
4. Add three choice branches:
   - "Accept the quest" → leads to investigation phase
   - "Refuse and walk away" → quest ends
   - "Demand payment first" → leads to negotiation outcome
5. For "Accept" branch, add reference to required Character (Village Elder) and Place (Village Square)
6. For "Investigation" node, add conditions: must have Item (Magnifying Glass) or Character skill (Investigation)
7. Add consequence: completing successfully gives Item (Ancient Artifact) and +2 faction reputation
8. Save and preview the decision tree visually

### Story 2: Game Master Views Decision Tree During Event

**As a** game master running the LARP
**I want to** quickly reference the decision tree during gameplay
**So that** I can properly adjudicate player actions and consequences

**Acceptance Criteria**:
1. Access quest/thread from mobile device during event
2. View decision tree in read-only mode
3. See current "active" node based on player progress
4. Quickly identify required story objects (characters, items, locations)
5. View conditions and consequences for each choice
6. Export tree as PDF for offline reference

### Story 3: Thread Designer Links Multiple Quests

**As a** LARP organizer
**I want to** create a thread that connects multiple quests
**So that** I can design overarching storylines across the event

**Acceptance Criteria**:
1. Open thread edit page and navigate to "Decision Tree" tab
2. Create start node for thread introduction
3. Add reference nodes linking to 3 related quests
4. Define conditions: Quest A must complete before Quest B becomes available
5. Add decision point: "Based on Quest A outcome, players can choose Quest B or Quest C"
6. Add consequence: Completing all three quests triggers Thread completion and unlocks final Event
7. Visualize how quests interconnect within the thread timeline

### Story 4: Organizer Identifies Story Dependencies

**As a** LARP organizer
**I want to** see all story objects referenced in a decision tree
**So that** I can ensure consistency and avoid plot holes

**Acceptance Criteria**:
1. View decision tree for complex quest
2. See sidebar listing all referenced story objects grouped by type
3. Identify that Character "John the Merchant" is required in 3 decision nodes
4. Verify that Item "Magic Key" appears as a consequence before it's required as a condition
5. Detect orphaned references (story objects that were deleted)
6. Click on story object reference to open that object's detail page

### Story 5: Organizer Duplicates Quest Template

**As a** LARP organizer
**I want to** duplicate a quest with its decision tree
**So that** I can create similar quests with minor variations

**Acceptance Criteria**:
1. View existing quest with complex decision tree
2. Click "Duplicate Quest" button
3. System copies quest data including full decision tree structure
4. Update quest title and some character references
5. Decision tree maintains structure but references new characters
6. Save as new quest without affecting original

## Implementation Recommendations

### Frontend: Decision Tree Editor

**Technology Stack**:
- **Cytoscape.js** (already in use for story graphs) - provides robust graph rendering
- **Stimulus Controller**: `decision-tree_controller.js`
- **UI Library**: Consider **React Flow** or **Cytoscape.js with extensions** for advanced features

**Key Features**:
1. **Node Creation**: Drag-and-drop interface or right-click menu
2. **Node Editing**: Modal dialog with form fields for title, description, metadata
3. **Edge Creation**: Click source node, then target node
4. **Edge Editing**: Double-click edge to set label and conditions
5. **StoryObject Search**: Autocomplete dropdown (reuse existing TomSelect integration)
6. **Visual Layout**: Auto-layout with Dagre algorithm, allow manual repositioning
7. **Zoom & Pan**: Navigate large trees
8. **Undo/Redo**: Track changes for user convenience
9. **Validation**: Ensure no orphaned nodes, at least one end node, etc.

**Stimulus Controller Structure**:
```javascript
// assets/controllers/decision_tree_controller.js
import { Controller } from '@hotwired/stimulus';
import cytoscape from 'cytoscape';
import dagre from 'cytoscape-dagre';

export default class extends Controller {
  static targets = ['input', 'canvas'];
  static values = { elements: Object, larpId: String };

  connect() {
    this.initCytoscape();
    this.setupEventHandlers();
    this.loadTreeData();
  }

  initCytoscape() {
    // Initialize Cytoscape instance with decision tree styles
  }

  addNode(type) {
    // Add new node to graph
  }

  editNode(nodeId) {
    // Open modal to edit node properties
  }

  deleteNode(nodeId) {
    // Remove node and connected edges
  }

  addEdge(sourceId, targetId) {
    // Create edge between nodes
  }

  searchStoryObjects(query, type) {
    // AJAX search for story objects within this LARP
    // Endpoint: /api/larp/{larpId}/story-objects?q={query}&type={type}
  }

  save() {
    // Serialize graph to JSON and populate hidden input
    const treeData = this.serializeTree();
    this.inputTarget.value = JSON.stringify(treeData);
  }
}
```

### Backend: API Endpoints

**Story Object Search API** (Already Implemented):

The application already has a story object search endpoint that can be reused for decision tree story object references:

```php
// src/Controller/API/StoryObjectMentionController.php
#[Route('/larp/{larp}/story-object/mention-search', name: 'backoffice_story_object_mention_search', methods: ['GET'])]
```

**Usage**:
- Endpoint: `/api/larp/{larpId}/story-object/mention-search?query={searchTerm}`
- Returns: Grouped results by type with caching (120s TTL)
- Response format:
```json
[
  {
    "type": "character",
    "items": [
      {
        "id": "uuid",
        "name": "Character Name",
        "type": "character",
        "url": "/backoffice/larp/{larpId}/story/character/{characterId}"
      }
    ]
  },
  {
    "type": "item",
    "items": [...]
  }
]
```

This endpoint is already used by the WYSIWYG editor's mention feature (`wysiwyg_controller.js`) and can be reused for decision tree story object autocomplete.

**Decision Tree Validation**:
```php
// src/Service/StoryObject/DecisionTreeValidator.php
class DecisionTreeValidator
{
    public function validate(array $treeData, Larp $larp): array
    {
        $errors = [];

        // Check for orphaned nodes
        // Validate referenced story objects exist
        // Ensure at least one start and end node
        // Verify edge connections are valid

        return $errors;
    }

    public function resolveStoryObjectReferences(array $treeData, Larp $larp): array
    {
        // Populate full story object data for references
    }
}
```

### Permissions & Access Control

- **Edit Decision Tree**: Requires `ROLE_USER` + ownership or admin rights on LARP
- **View Decision Tree**: Same as edit (organizer-only feature)
- **Future: Read-only access**: Could enable for game masters during event

### Mobile Considerations

- Responsive layout: Vertical stacking on small screens
- Touch gestures: Tap to select, long-press for menu
- Simplified editor: Reduced feature set for mobile editing
- Read-only mode: Optimized for quick reference during gameplay

## Migration Path

### Phase 1: Basic Editor (MVP)
1. Create `decision_tree_controller.js` Stimulus controller
2. Implement basic node/edge creation with Cytoscape.js
3. Add simple node editing modal (title, description, type)
4. Implement save/load functionality
5. Deploy to production for early feedback

### Phase 2: Story Object Integration
1. Add story object search autocomplete
2. Implement node metadata for references
3. Create validation service
4. Add dependency visualization (sidebar showing all references)

### Phase 3: Conditions & Consequences
1. Add condition editor to nodes
2. Add consequence editor to edges
3. Implement validation for logical consistency
4. Add visual indicators for conditional branches

### Phase 4: Advanced Features
1. Auto-layout improvements (better Dagre configuration)
2. Export to PDF/image
3. Quest/Thread duplication with tree
4. Real-time collaboration (future: multiple organizers editing)
5. Version history (leverage existing Loggable extension)

## Alternative Approaches Considered

### Approach 1: Custom Node Editor (Higher Complexity)
Build a fully custom drag-and-drop node editor without graph library dependency.

**Pros**:
- Full control over UI/UX
- Can tailor specifically to LARP workflow

**Cons**:
- Significant development time
- Need to implement graph algorithms from scratch
- Harder to maintain
- Reinventing the wheel when Cytoscape.js already exists

**Recommendation**: Not optimal given Cytoscape.js is already in use

### Approach 2: Text-Based Tree (YAML/Markdown)
Store decision tree as structured text file (YAML or Markdown with special syntax).

**Pros**:
- Version control friendly
- Can be edited in text editor
- Export/import easy

**Cons**:
- Not visual - defeats purpose of decision tree
- Steep learning curve for non-technical organizers
- Hard to see big picture

**Recommendation**: Could be used as export format but not primary interface

### Approach 3: Separate Decision Tree Entity
Create a `DecisionTreeNode` and `DecisionTreeEdge` entity with separate database tables.

**Pros**:
- More normalized database structure
- Can query/analyze tree structure with SQL
- Easier to enforce referential integrity

**Cons**:
- Increases database complexity
- More entities to manage
- Slower to load/save (multiple queries)
- JSONB already supports advanced querying with PostgreSQL

**Recommendation**: Current JSONB approach is more pragmatic for MVP; can migrate later if needed

## Current Recommendation

**Use Cytoscape.js with Dagre Layout** for the following reasons:

1. **Already Integrated**: `story_graph_controller.js` uses Cytoscape.js, so team is familiar
2. **Proven Library**: Battle-tested for graph visualization
3. **Extensible**: Rich plugin ecosystem (Dagre for auto-layout, editable-extension for editing)
4. **Performance**: Handles large graphs efficiently
5. **Customizable**: Full control over node/edge styling
6. **Mobile Support**: Works on touch devices with proper configuration

**JSONB Storage** is appropriate because:
1. **Flexibility**: Schema can evolve without migrations
2. **Performance**: PostgreSQL JSONB is fast and supports indexing
3. **Simplicity**: Single column stores entire tree structure
4. **Querying**: Can use JSONB operators to find references if needed

## Next Steps

1. **Create Decision Tree Controller**: Implement `decision_tree_controller.js` based on existing `story_graph_controller.js`
2. **Build Node Editor Modal**: Form for editing node properties (title, description, type, story object references)
3. **Implement Save/Load**: Serialize Cytoscape graph to JSON format and save to `decisionTree` field
4. **Add Story Object Search**: AJAX autocomplete for referencing story objects within nodes
5. **Create Validation Service**: Backend validation for tree structure and references
6. **Design Read-Only View**: Optimized layout for viewing during gameplay
7. **Documentation**: Update CLAUDE.md with decision tree patterns and conventions

## Open Questions

1. **Should decision trees be versioned?** (Already have Loggable on Quest/Thread entities, could extend to track tree changes)
2. **How to handle deleted story objects?** (Soft delete with warnings, or prevent deletion if referenced?)
3. **Should threads and quests share decision tree logic?** (Yes, via shared service/controller)
4. **Mobile editing priority?** (Start with desktop, optimize for mobile in Phase 2)
5. **Export formats needed?** (PDF for printing, JSON for backup, PNG for presentations)
6. **Real-time collaboration?** (Future feature, use Mercure for WebSocket updates)

## Implementation Status

### ✅ Completed
1. **Decision Tree Controller**: Full implementation in `assets/controllers/decision_tree_controller.js`
   - Node types: start, decision, outcome, reference, end (color-coded with different shapes)
   - Edge types: choice, consequence, reference (styled with colors and line styles)
   - Interactive toolbar with add/edit/delete operations
   - Auto-layout with Dagre algorithm
   - Serialization to JSON format matching documentation schema

2. **Translations**: Added comprehensive translations in `translations/messages.en.yaml`
   - Node type labels
   - Edge type labels
   - Toolbar button labels
   - Metadata field labels
   - Condition and consequence type labels
   - Story object role labels

3. **API Endpoint**: Reusing existing `StoryObjectMentionController`
   - Endpoint: `/api/larp/{larp}/story-object/mention-search`
   - Grouped results by story object type
   - Cached for performance (120s TTL)
   - Already integrated with WYSIWYG editor

### 🔄 Next Steps (Future Enhancements)
1. **Advanced Node Editor Modal**:
   - Replace `prompt()` with Bootstrap modal
   - Add story object autocomplete (integrate with mention-search API)
   - Edit conditions and consequences
   - Add tags and metadata fields

2. **Story Object Integration**:
   - Fetch story objects via AJAX from mention-search API
   - Display story object references in node details
   - Validate story object references on save
   - Show warnings for deleted/missing references

3. **Backend Validation Service**:
   - Create `DecisionTreeValidator` service
   - Validate tree structure (at least one start/end node)
   - Check for orphaned nodes
   - Verify story object references exist

4. **Enhanced Visualization**:
   - Legend showing node/edge types
   - Minimap for large trees
   - Search/filter nodes
   - Highlight paths through tree

5. **Export/Import**:
   - Export tree as PNG/SVG image
   - Export as PDF for printing
   - Quest/Thread duplication with tree

## References

- Cytoscape.js Documentation: https://js.cytoscape.org/
- Dagre Layout: https://github.com/cytoscape/cytoscape.js-dagre
- Existing Story Graph Controller: `assets/controllers/story_graph_controller.js`
- Quest Entity: `src/Entity/StoryObject/Quest.php:36-37`
- Thread Entity: `src/Entity/StoryObject/Thread.php:39-40`
- Story Object Mention API: `src/Controller/API/StoryObjectMentionController.php`
- Decision Tree Controller: `assets/controllers/decision_tree_controller.js`
- Translations: `translations/messages.en.yaml:455-511`
