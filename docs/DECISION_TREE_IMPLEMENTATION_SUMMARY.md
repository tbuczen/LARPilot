# Decision Tree Implementation Summary

## Overview

This document summarizes the implementation of the Decision Tree functionality for Quests and Threads in LARPilot.

### 1. Enhanced Decision Tree Controller (`assets/controllers/decision_tree_controller.js`)

The Stimulus controller was completely rewritten with full editor functionality:

#### Features Implemented:
- **Node Types** (with distinct shapes and colors):
  - Start (green ellipse)
  - Decision (yellow diamond)
  - Outcome (cyan rectangle)
  - Reference (gray octagon) - **Now supports story object references!**
  - End (red ellipse)

- **Edge Types** (with distinct line styles):
  - Choice (green solid line)
  - Consequence (red dashed line)
  - Reference (gray dotted line)

- **Interactive Toolbar**:
  - Add Start/Decision/Outcome/Reference/End nodes
  - Connect nodes (edge creation mode)
  - Auto Layout (breadthfirst hierarchical layout)
  - Delete selected node/edge

- **Context Menus** (right-click):
  - Background: Add any node type, auto layout
  - Nodes: Edit, connect, delete
  - Reference Nodes: **Manage Story Objects** (search and link characters, items, places, etc.)
  - Edges: Edit, delete

- **User Interactions**:
  - Click to select nodes/edges
  - Drag to reposition nodes
  - Double-click to edit inline (no popups!)
  - Right-click for context menus
  - Hover effects for better UX
  - Background click to deselect

- **Story Object Integration** ✨ NEW:
  - Search and link story objects to Reference nodes via modal
  - TomSelect autocomplete using `/api/larp/{larp}/story-object/mention-search`
  - Visual badge count on Reference nodes showing linked objects (e.g., "Reference (3)")
  - Manage multiple references per node with role assignment
  - Remove references individually
  - References persist in node metadata

- **Data Management**:
  - Parse existing tree data (supports both new and legacy formats)
  - Serialize tree to JSON matching the documented schema
  - Auto-save to hidden form input on any change
  - Story object references stored in `node.metadata.storyObjects[]`

#### Code Structure:
```javascript
connect()                      // Initialize Cytoscape, setup handlers, toolbar
parseTreeData()                // Convert JSON to Cytoscape elements
setupEventHandlers()           // Mouse events, selection, deselection
setupToolbar()                 // Create dynamic toolbar with buttons
setupContextMenu()             // Right-click context menus for nodes/edges/background
addNode()                      // Add new node to graph
createEdge()                   // Connect two nodes
editNodeInline()               // Inline text editing (no popups!)
editEdgeInline()               // Inline edge label editing
deleteSelected()               // Remove selected element
applyLayout()                  // Run breadthfirst auto-layout algorithm
serializeTree()                // Convert graph to JSON schema
manageStoryObjectReferences()  // Open modal to manage story object links ✨ NEW
showStoryObjectModal()         // Display modal with TomSelect search ✨ NEW
initializeStoryObjectSearch()  // Initialize TomSelect with AJAX ✨ NEW
addStoryObjectReference()      // Add story object to node metadata ✨ NEW
removeStoryObjectReference()   // Remove story object from node ✨ NEW
saveStoryObjectReferences()    // Save references and update badge ✨ NEW
updateNodeReferenceBadge()     // Show count badge on Reference nodes ✨ NEW
```

### 2. Translations (`translations/messages.en.yaml`)

**Status**: ✅ Complete

Added comprehensive translations for decision tree functionality:

#### Translation Keys Added:
- `backoffice.larp.quest.tree` → "Decision Tree"
- `backoffice.larp.quest.tree_editor` → "Quest Decision Tree Editor"
- `backoffice.larp.thread.tree` → "Decision Tree"
- `backoffice.larp.thread.tree_editor` → "Thread Decision Tree Editor"

#### New Translation Section: `decision_tree`
- **node_types**: start, decision, outcome, reference, end
- **edge_types**: choice, consequence, reference
- **toolbar**: add_start, add_decision, etc.
- **prompts**: node_title, edge_label, validation messages
- **metadata**: story_objects, conditions, consequences, duration, location, tags
- **condition_types**: has_item, character_present, faction_reputation, etc.
- **consequence_types**: gain_item, lose_item, relationship_change, etc.
- **roles**: required, involved, mentioned, rewarded, lost
- **modal** ✨ NEW: title, search_label, search_placeholder, search_help, current_references_label, no_references, remove_reference, cancel, save
- **context_menu** ✨ NEW: manage_story_objects, edit_node, connect_to, delete_node, edit_edge, delete_edge, add_*_node, auto_layout

### 3. Documentation (`docs/DECISION_TREE_SYSTEM.md`)

**Status**: ✅ Complete

Created comprehensive documentation including:
- Purpose and architecture
- Complete data model (JSON schema)
- Five detailed user stories
- Implementation recommendations
- Alternative approaches analysis
- Migration path (4 phases)
- API endpoint documentation
- Implementation status tracking

**Key Updates**:
- Documented existing API endpoint (`StoryObjectMentionController`)
- Updated references section with all relevant files
- Added "Implementation Status" section showing what's complete and what's next

### 4. Updated Documentation (`docs/DECISION_TREE_IMPLEMENTATION_SUMMARY.md`)

**Status**: ✅ Complete (this file)

## What Was Completed (Latest Update)

### Story Object Reference Feature ✨ NEW

**Status**: ✅ Complete

Implemented comprehensive story object integration for Reference nodes:

1. **Modal Dialog**:
   - Bootstrap modal with backdrop
   - Clean, professional UI
   - Cancel and Save buttons

2. **TomSelect Autocomplete**:
   - Dynamic import of TomSelect library (lazy loading)
   - AJAX search using `/api/larp/{larp}/story-object/mention-search`
   - Searches across all story object types (Characters, Items, Places, Factions, etc.)
   - Grouped display with type badges
   - Prevents duplicate additions

3. **Reference Management**:
   - Add multiple story objects to a single Reference node
   - Each reference includes: id, title, type, role (default: "involved")
   - Remove individual references
   - Visual list with badges showing object type and role

4. **Visual Badges**:
   - Reference nodes display count: "Reference (3)" when 3 objects are linked
   - Badge updates automatically on save
   - Badge persists on reload

5. **Context Menu Integration**:
   - "Manage Story Objects..." option appears only on Reference nodes
   - Highlighted with bold font weight for visibility

### Updated Templates

- ✅ `templates/backoffice/larp/quest/tree.html.twig` - Added `larpId` value
- ✅ `templates/backoffice/larp/thread/tree.html.twig` - Added `larpId` value

## What Still Needs To Be Done

### Future Enhancements (Phase 2+)

1. **Enhanced Reference Display** (optional):
   - Click on Reference node badge to view linked objects without opening modal
   - Tooltip showing linked object titles on hover
   - Color-coded badges by object type

2. **Role Selection** (optional):
   - Allow changing role when adding reference (required/involved/mentioned/etc.)
   - Role dropdown in modal
   - Role affects badge color

3. **Story Object Validation** (backend):
   - Validate that referenced story objects still exist
   - Warn about deleted/missing references
   - Clean up orphaned references

4. **Backend Validation Service**:
   - Create `src/Service/StoryObject/DecisionTreeValidator.php`
   - Validate tree structure (must have start/end nodes)
   - Check for orphaned nodes
   - Verify story object UUIDs are valid

5. **Enhanced Visualization**:
   - Add legend showing node/edge types
   - Minimap for navigation in large trees
   - Search/filter nodes by title
   - Highlight specific paths through the tree
   - Zoom controls

6. **Export/Import**:
   - Export tree as PNG/SVG image (Cytoscape supports this)
   - Export as PDF for game masters to print
   - Enable quest/thread duplication with decision tree included

7. **Mobile Optimization**:
   - Responsive toolbar layout
   - Touch gesture support
   - Simplified editor for mobile devices
   - Read-only view optimized for tablets

## Testing the Implementation

### Manual Testing Steps:

1. **Navigate to a Quest or Thread**:
   - Go to `/backoffice/larp/{larpId}/story/quest/{questId}`
   - Or: `/backoffice/larp/{larpId}/story/thread/{threadId}`

2. **Access the Decision Tree**:
   - Click on the "Decision Tree" tab or link
   - URL: `.../quest/{questId}/tree` or `.../thread/{threadId}/tree`

3. **Test Node Creation**:
   - Click "+ Start" to create a start node (or right-click background)
   - Click "+ Reference" to create a reference node
   - Observe the node appears with the correct color/shape
   - Double-click on node to edit title inline (no popup!)

4. **Test Story Object References** ✨ NEW:
   - Create a Reference node
   - Right-click the Reference node
   - Click "Manage Story Objects..."
   - Modal opens with search field
   - Type to search for characters, items, etc.
   - Click on a result to add it
   - Observe it appears in "Current References" list
   - Add multiple objects
   - Click "Remove" to remove a reference
   - Click "Save References"
   - Observe the node now shows "Reference (N)" where N is the count
   - Save the tree and reload - badge persists!

5. **Test Edge Creation**:
   - Click on a node to select it (should see red border)
   - Click "+ Connect" button (or use context menu "Connect to...")
   - Click on another node to create an edge
   - Double-click edge to edit label inline

6. **Test Context Menus**:
   - Right-click background: Add any node, auto layout
   - Right-click node: Edit, connect, delete
   - Right-click edge: Edit, delete
   - Right-click Reference node: Shows "Manage Story Objects..." option

7. **Test Auto Layout**:
   - Add several nodes and edges
   - Click "Auto Layout" button (or right-click > Auto Layout)
   - Observe nodes arrange in hierarchical layout

8. **Test Save/Load**:
   - Create a complex tree with multiple nodes/edges and story object references
   - Click "Save Changes" button at bottom of form
   - Refresh the page
   - Verify the tree is loaded correctly with all references preserved

9. **Test Delete**:
   - Right-click a node or edge
   - Click "Delete Node" or "Delete Edge"
   - Verify the element is removed

### Expected Console Output:

If everything is working correctly, you should see:
- No JavaScript errors in browser console
- Cytoscape instance initializes
- Events trigger properly (node clicks, edge creation, etc.)

### Troubleshooting:

**If story object search doesn't work**:
- Verify larpId is passed to the controller in the template: `data-decision-tree-larp-id-value="{{ larp.id }}"`
- Check browser console for AJAX errors
- Test the API endpoint directly: `/api/larp/{larpId}/story-object/mention-search?query=test`
- Ensure you have some characters/items/places created in the LARP

**If nodes don't appear or layout is broken**:
- Check browser console for JavaScript errors
- Verify `decisionTree` field in database contains valid JSON
- Ensure Stimulus controller is loading (check Network tab)

**If toolbar buttons don't work**:
- Verify Bootstrap JavaScript is loaded
- Check for JavaScript errors in console
- Ensure event listeners are attached

## Database Schema

No migration needed - the `decisionTree` field already exists:

- `Quest::$decisionTree` - JSONB field (nullable)
- `Thread::$decisionTree` - JSONB field (nullable)

The controller handles both empty trees (creates new) and existing trees (loads and edits).

## Current Data Format

The serialized tree follows this structure:

```json
{
  "nodes": [
    {
      "id": "node-1234567890",
      "type": "start|decision|outcome|reference|end",
      "title": "Node title",
      "description": "Optional description",
      "position": { "x": 100, "y": 200 },
      "metadata": {
        "storyObjects": [
          {
            "id": "uuid-of-character",
            "title": "John the Warrior",
            "type": "character",
            "role": "involved"
          }
        ]
      }
    }
  ],
  "edges": [
    {
      "id": "edge-1234567890",
      "source": "node-123",
      "target": "node-456",
      "label": "Choice label",
      "type": "choice|consequence|reference",
      "metadata": {}
    }
  ],
  "metadata": {
    "version": "1.0",
    "lastModified": "2025-10-11T12:34:56.789Z",
    "layout": "dagre"
  }
}
```

## Integration Points

### Existing Integrations:
- ✅ Uses existing `StoryObjectMentionController` for story object search (**NOW ACTIVE!**)
- ✅ Follows existing Stimulus controller patterns
- ✅ Uses existing Cytoscape.js library (same as `story_graph_controller`)
- ✅ Integrates with existing form system (hidden input + POST)
- ✅ Uses TomSelect for autocomplete (same as other forms)
- ✅ Dynamic import for lazy loading (only loads TomSelect when needed)

### Future Integrations:
- Version history (Gedmo Loggable already tracks Quest/Thread changes)
- Google Docs export (when implementing PDF export feature)
- Notification system (notify players when referenced in a quest/thread decision tree)

## Files Modified/Created

### Created:
- ✅ `docs/DECISION_TREE_SYSTEM.md` - Full design documentation
- ✅ `docs/DECISION_TREE_IMPLEMENTATION_SUMMARY.md` - This file

### Modified:
- ✅ `assets/controllers/decision_tree_controller.js` - Complete rewrite with story object integration
- ✅ `translations/messages.en.yaml` - Added decision tree translations + modal/context menu translations
- ✅ `templates/backoffice/larp/quest/tree.html.twig` - Added larpId value for API calls
- ✅ `templates/backoffice/larp/thread/tree.html.twig` - Added larpId value for API calls

### Existing (no changes needed):
- `src/Entity/StoryObject/Quest.php` - Already has `decisionTree` field
- `src/Entity/StoryObject/Thread.php` - Already has `decisionTree` field
- `src/Controller/Backoffice/Story/QuestController.php` - Already has `tree()` method
- `src/Controller/Backoffice/Story/ThreadController.php` - Already has `tree()` method
- `src/Controller/API/StoryObjectMentionController.php` - Already provides search API
- `importmap.php` - Already has `cytoscape` and `tom-select`

## Next Steps

1. **Test the story object reference feature** (see "Testing the Implementation" above)
   - Create a Reference node
   - Search for and add story objects
   - Verify badge count appears
   - Save and reload to confirm persistence

2. **Gather user feedback** on the reference feature:
   - Is the modal UX intuitive?
   - Should we add role selection (required/involved/mentioned)?
   - Would tooltips on badges be helpful?

3. **Optional enhancements** (based on feedback):
   - Role dropdown when adding references
   - Click badge to view references without opening modal
   - Tooltips showing linked object titles on hover
   - Color-coded badges by object type

4. **Phase 3** (future enhancements):
   - Backend validation (verify referenced UUIDs exist)
   - Export to PNG/PDF with reference annotations
   - Minimap and enhanced visualization
   - Mobile optimization

## Questions?

If you encounter issues or have questions about the implementation:

1. Check `docs/DECISION_TREE_SYSTEM.md` for detailed design decisions
2. Review the controller code in `assets/controllers/decision_tree_controller.js`
3. Check browser console for JavaScript errors
4. Verify the API endpoint is accessible: `/api/larp/{larpId}/story-object/mention-search?query=test`

## Related Documentation

- Main Documentation: `docs/DECISION_TREE_SYSTEM.md`
- Project Instructions: `CLAUDE.md`
- Story Graph Reference: `assets/controllers/story_graph_controller.js`
- API Controller: `src/Controller/API/StoryObjectMentionController.php`
