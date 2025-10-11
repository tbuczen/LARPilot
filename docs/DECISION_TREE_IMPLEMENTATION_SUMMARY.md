# Decision Tree Implementation Summary

## Overview

This document summarizes the implementation of the Decision Tree functionality for Quests and Threads in LARPilot.

## What Was Done

### 1. Enhanced Decision Tree Controller (`assets/controllers/decision_tree_controller.js`)

**Status**: ✅ Complete

The Stimulus controller was completely rewritten with full editor functionality:

#### Features Implemented:
- **Node Types** (with distinct shapes and colors):
  - Start (green ellipse)
  - Decision (yellow diamond)
  - Outcome (cyan rectangle)
  - Reference (gray octagon)
  - End (red ellipse)

- **Edge Types** (with distinct line styles):
  - Choice (green solid line)
  - Consequence (red dashed line)
  - Reference (gray dotted line)

- **Interactive Toolbar**:
  - Add Start/Decision/Outcome/Reference/End nodes
  - Connect nodes (edge creation mode)
  - Auto Layout (Dagre hierarchical layout)
  - Delete selected node/edge

- **User Interactions**:
  - Click to select nodes/edges
  - Drag to reposition nodes
  - Click selected node + "Connect" button + click target to create edge
  - Hover effects for better UX
  - Background click to deselect

- **Data Management**:
  - Parse existing tree data (supports both new and legacy formats)
  - Serialize tree to JSON matching the documented schema
  - Auto-save to hidden form input on any change

#### Code Structure:
```javascript
connect()           // Initialize Cytoscape, setup handlers, toolbar
parseTreeData()     // Convert JSON to Cytoscape elements
setupEventHandlers() // Mouse events, selection, deselection
setupToolbar()      // Create dynamic toolbar with buttons
addNode()           // Add new node to graph
createEdge()        // Connect two nodes
promptEditNode()    // Edit node properties (currently uses prompt)
promptEditEdge()    // Edit edge properties (currently uses prompt)
deleteSelected()    // Remove selected element
applyLayout()       // Run Dagre auto-layout algorithm
serializeTree()     // Convert graph to JSON schema
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

## What Still Needs To Be Done

### Immediate: Install Dependencies

**Required Package**: `cytoscape-dagre`

The decision tree controller imports `cytoscape-dagre` for the hierarchical layout algorithm, but this package is not yet in the importmap.

**Installation Command**:
```bash
php bin/console importmap:require cytoscape-dagre
```

This will add the package to `importmap.php` and download the necessary files.

### Future Enhancements (Phase 2+)

1. **Advanced Node Editor Modal**:
   - Replace `prompt()` dialogs with Bootstrap modals
   - Add story object autocomplete using the existing `/api/larp/{larp}/story-object/mention-search` endpoint
   - Allow editing conditions and consequences
   - Add metadata fields (duration, location, tags)

2. **Story Object Integration**:
   - Fetch and display story objects in node editor
   - Show story object references as badges on nodes
   - Validate that referenced story objects exist
   - Warn about deleted/missing references

3. **Backend Validation Service**:
   - Create `src/Service/StoryObject/DecisionTreeValidator.php`
   - Validate tree structure (must have start/end nodes)
   - Check for orphaned nodes
   - Verify story object UUIDs are valid

4. **Enhanced Visualization**:
   - Add legend showing node/edge types
   - Minimap for navigation in large trees
   - Search/filter nodes by title
   - Highlight specific paths through the tree
   - Zoom controls

5. **Export/Import**:
   - Export tree as PNG/SVG image (Cytoscape supports this)
   - Export as PDF for game masters to print
   - Enable quest/thread duplication with decision tree included

6. **Mobile Optimization**:
   - Responsive toolbar layout
   - Touch gesture support
   - Simplified editor for mobile devices
   - Read-only view optimized for tablets

## Testing the Implementation

### Manual Testing Steps:

1. **Install the missing dependency**:
   ```bash
   php bin/console importmap:require cytoscape-dagre
   ```

2. **Navigate to a Quest or Thread**:
   - Go to `/backoffice/larp/{larpId}/story/quest/{questId}`
   - Or: `/backoffice/larp/{larpId}/story/thread/{threadId}`

3. **Access the Decision Tree**:
   - Click on the "Decision Tree" tab or link
   - URL: `.../quest/{questId}/tree` or `.../thread/{threadId}/tree`

4. **Test Node Creation**:
   - Click "+ Start" to create a start node
   - Click "+ Decision" to create a decision node
   - Edit the node title and description when prompted
   - Observe the node appears with the correct color/shape

5. **Test Edge Creation**:
   - Click on a node to select it (should see red border)
   - Click "+ Connect" button
   - Click on another node to create an edge
   - Edit the edge label when prompted

6. **Test Auto Layout**:
   - Add several nodes and edges
   - Click "Auto Layout" button
   - Observe nodes arrange in hierarchical layout

7. **Test Save/Load**:
   - Create a complex tree with multiple nodes/edges
   - Click "Save Changes" button at bottom of form
   - Refresh the page
   - Verify the tree is loaded correctly

8. **Test Delete**:
   - Select a node or edge (click on it)
   - Click "Delete Selected" button
   - Verify the element is removed

### Expected Console Output:

If everything is working correctly, you should see:
- No JavaScript errors in browser console
- Cytoscape instance initializes
- Events trigger properly (node clicks, edge creation, etc.)

### Troubleshooting:

**If you see "Cannot find module 'cytoscape-dagre'"**:
- Run: `php bin/console importmap:require cytoscape-dagre`
- Clear Symfony cache: `php bin/console cache:clear`
- Reload the page

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
      "metadata": {}
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
- ✅ Uses existing `StoryObjectMentionController` for future story object search
- ✅ Follows existing Stimulus controller patterns
- ✅ Uses existing Cytoscape.js library (same as `story_graph_controller`)
- ✅ Integrates with existing form system (hidden input + POST)

### Future Integrations:
- Story object autocomplete (when implementing advanced editor modal)
- Version history (Gedmo Loggable already tracks Quest/Thread changes)
- Google Docs export (when implementing PDF export feature)

## Files Modified/Created

### Created:
- ✅ `docs/DECISION_TREE_SYSTEM.md` - Full design documentation
- ✅ `docs/DECISION_TREE_IMPLEMENTATION_SUMMARY.md` - This file

### Modified:
- ✅ `assets/controllers/decision_tree_controller.js` - Complete rewrite
- ✅ `translations/messages.en.yaml` - Added decision tree translations

### Existing (no changes needed):
- `src/Entity/StoryObject/Quest.php` - Already has `decisionTree` field
- `src/Entity/StoryObject/Thread.php` - Already has `decisionTree` field
- `src/Controller/Backoffice/Story/QuestController.php` - Already has `tree()` method
- `src/Controller/Backoffice/Story/ThreadController.php` - Already has `tree()` method
- `templates/backoffice/larp/quest/tree.html.twig` - Already references controller
- `templates/backoffice/larp/thread/tree.html.twig` - Already references controller
- `importmap.php` - Already has `cytoscape`, needs `cytoscape-dagre`

## Next Steps

1. **Immediate** (required for functionality):
   ```bash
   php bin/console importmap:require cytoscape-dagre
   ```

2. **Test the basic editor** (see "Testing the Implementation" above)

3. **Gather user feedback** on the basic editor before building advanced features

4. **Phase 2** (if basic editor is approved):
   - Implement advanced node editor modal with story object autocomplete
   - Add backend validation service
   - Implement export to PNG/PDF

5. **Phase 3** (future enhancements):
   - Minimap and enhanced visualization
   - Mobile optimization
   - Real-time collaboration (if needed)

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
