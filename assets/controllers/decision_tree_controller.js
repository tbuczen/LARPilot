import { Controller } from '@hotwired/stimulus';
import cytoscape from 'cytoscape';

export default class extends Controller {
    static targets = ['input', 'modal'];
    static values = {
        elements: Object, // Decision tree data structure
        larpId: String,
    };

    connect() {
        this.selectedNode = null;
        this.selectedEdge = null;
        this.edgeSourceNode = null;

        // Use breadthfirst layout (dagre has dependency issues with importmap)
        this.dagreAvailable = false;

        // Normalize elements value - handle both Array and Object
        let elementsData = this.elementsValue;

        // If it's an empty array or null, convert to empty object
        if (Array.isArray(elementsData) && elementsData.length === 0) {
            elementsData = {};
        }
        if (!elementsData) {
            elementsData = {};
        }

        // Choose layout based on dagre availability
        const layoutConfig = this.dagreAvailable ? {
            name: 'dagre',
            nodeDimensionsIncludeLabels: true,
            rankDir: 'TB',
            spacingFactor: 1.5,
            animate: false,
        } : {
            name: 'breadthfirst',
            directed: true,
            spacingFactor: 1.5,
            nodeDimensionsIncludeLabels: true,
            animate: false,
        };

        this.cy = cytoscape({
            container: this.element,
            elements: this.parseTreeData(elementsData),
            layout: layoutConfig,
            style: [
                {
                    selector: 'node',
                    style: {
                        'label': 'data(label)',
                        'text-wrap': 'wrap',
                        'text-max-width': '120px',
                        'background-color': '#007bff',
                        'color': '#fff',
                        'text-valign': 'center',
                        'text-halign': 'center',
                        'font-size': '12px',
                        'shape': 'roundrectangle',
                        'width': 'label',
                        'height': 'label',
                        'padding': '12px',
                        'cursor': 'pointer',
                        'border-width': 2,
                        'border-color': '#0056b3',
                    }
                },
                {
                    selector: 'node[nodeType="start"]',
                    style: {
                        'background-color': '#28a745',
                        'border-color': '#1e7e34',
                        'shape': 'ellipse',
                    }
                },
                {
                    selector: 'node[nodeType="decision"]',
                    style: {
                        'background-color': '#ffc107',
                        'border-color': '#d39e00',
                        'color': '#000',
                        'shape': 'diamond',
                    }
                },
                {
                    selector: 'node[nodeType="outcome"]',
                    style: {
                        'background-color': '#17a2b8',
                        'border-color': '#117a8b',
                    }
                },
                {
                    selector: 'node[nodeType="reference"]',
                    style: {
                        'background-color': '#6c757d',
                        'border-color': '#5a6268',
                        'shape': 'octagon',
                    }
                },
                {
                    selector: 'node[nodeType="end"]',
                    style: {
                        'background-color': '#dc3545',
                        'border-color': '#bd2130',
                        'shape': 'ellipse',
                    }
                },
                {
                    selector: 'node:selected',
                    style: {
                        'border-width': 4,
                        'border-color': '#ff6b6b',
                    }
                },
                {
                    selector: 'node.hovered',
                    style: {
                        'border-color': '#ffa500',
                        'border-width': 3,
                    }
                },
                {
                    selector: 'edge',
                    style: {
                        'width': 3,
                        'line-color': '#adb5bd',
                        'target-arrow-color': '#adb5bd',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'bezier',
                        'label': 'data(label)',
                        'text-rotation': 'autorotate',
                        'font-size': '10px',
                        'text-background-color': '#fff',
                        'text-background-opacity': 1,
                        'text-background-padding': 3,
                        'cursor': 'pointer',
                    }
                },
                {
                    selector: 'edge[edgeType="choice"]',
                    style: {
                        'line-color': '#28a745',
                        'target-arrow-color': '#28a745',
                    }
                },
                {
                    selector: 'edge[edgeType="consequence"]',
                    style: {
                        'line-color': '#dc3545',
                        'target-arrow-color': '#dc3545',
                        'line-style': 'dashed',
                    }
                },
                {
                    selector: 'edge[edgeType="reference"]',
                    style: {
                        'line-color': '#6c757d',
                        'target-arrow-color': '#6c757d',
                        'line-style': 'dotted',
                    }
                },
                {
                    selector: 'edge:selected',
                    style: {
                        'width': 5,
                        'line-color': '#ff6b6b',
                        'target-arrow-color': '#ff6b6b',
                    }
                }
            ]
        });

        this.setupEventHandlers();
        this.setupToolbar();
        this.setupContextMenu();
        this.setupFormHandler();
        this.updateInput();

        // Update reference badges for all nodes
        this.cy.nodes().forEach(node => {
            if (node.data('nodeType') === 'reference') {
                this.updateNodeReferenceBadge(node);
            }
        });
    }

    parseTreeData(treeData) {
        if (!treeData || (!treeData.nodes && !treeData.length)) {
            return [];
        }

        // Handle both new format {nodes: [], edges: []} and legacy format
        if (Array.isArray(treeData)) {
            return treeData;
        }

        const elements = [];

        if (treeData.nodes) {
            treeData.nodes.forEach(node => {
                elements.push({
                    group: 'nodes',
                    data: {
                        id: node.id,
                        label: node.title || node.label || 'Untitled',
                        nodeType: node.type || 'outcome',
                        description: node.description || '',
                        metadata: node.metadata || {},
                    },
                    position: node.position || { x: 0, y: 0 }
                });
            });
        }

        if (treeData.edges) {
            treeData.edges.forEach(edge => {
                elements.push({
                    group: 'edges',
                    data: {
                        id: edge.id,
                        source: edge.source,
                        target: edge.target,
                        label: edge.label || '',
                        edgeType: edge.type || 'choice',
                        metadata: edge.metadata || {},
                    }
                });
            });
        }

        return elements;
    }

    setupEventHandlers() {
        // Node selection
        this.cy.on('tap', 'node', (event) => {
            if (this.edgeSourceNode) {
                // Creating edge mode
                this.createEdge(this.edgeSourceNode, event.target);
                this.edgeSourceNode = null;
                this.element.style.cursor = 'default';
            } else {
                this.selectNode(event.target);
            }
        });

        // Double-click to edit node inline
        this.cy.on('dbltap', 'node', (event) => {
            event.preventDefault();
            this.editNodeInline(event.target);
        });

        // Double-click to edit edge inline
        this.cy.on('dbltap', 'edge', (event) => {
            event.preventDefault();
            this.editEdgeInline(event.target);
        });

        // Edge selection
        this.cy.on('tap', 'edge', (event) => {
            this.selectEdge(event.target);
        });

        // Deselect on background tap
        this.cy.on('tap', (event) => {
            if (event.target === this.cy) {
                this.deselectAll();
                this.removeActiveEditor();
            }
        });

        // Close editor when clicking on any node/edge
        this.cy.on('tap', 'node, edge', () => {
            this.removeActiveEditor();
        });

        // Hover effects
        this.cy.on('mouseover', 'node', (event) => {
            event.target.addClass('hovered');
            this.element.style.cursor = 'pointer';
        });

        this.cy.on('mouseout', 'node', (event) => {
            event.target.removeClass('hovered');
            if (!this.edgeSourceNode) {
                this.element.style.cursor = 'default';
            }
        });

        // Track changes
        this.cy.on('add remove data position', () => {
            this.updateInput();
        });
    }

    setupToolbar() {
        const toolbar = document.createElement('div');
        toolbar.className = 'decision-tree-toolbar btn-group mb-2';
        toolbar.innerHTML = `
            <button type="button" class="btn btn-sm btn-success" data-action="add-start">+ Start</button>
            <button type="button" class="btn btn-sm btn-warning" data-action="add-decision">+ Decision</button>
            <button type="button" class="btn btn-sm btn-info" data-action="add-outcome">+ Outcome</button>
            <button type="button" class="btn btn-sm btn-secondary" data-action="add-reference">+ Reference</button>
            <button type="button" class="btn btn-sm btn-danger" data-action="add-end">+ End</button>
            <button type="button" class="btn btn-sm btn-primary ms-2" data-action="add-edge">+ Connect</button>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-2" data-action="auto-layout">Auto Layout</button>
            <button type="button" class="btn btn-sm btn-outline-danger ms-2" data-action="delete">Delete Selected</button>
        `;

        this.element.parentElement.insertBefore(toolbar, this.element);

        toolbar.addEventListener('click', (e) => {
            const action = e.target.dataset.action;
            if (!action) return;

            switch (action) {
                case 'add-start':
                    this.addNode('start', 'Start');
                    break;
                case 'add-decision':
                    this.addNode('decision', 'Decision?');
                    break;
                case 'add-outcome':
                    this.addNode('outcome', 'Outcome');
                    break;
                case 'add-reference':
                    this.addNode('reference', 'Reference');
                    break;
                case 'add-end':
                    this.addNode('end', 'End');
                    break;
                case 'add-edge':
                    this.startEdgeCreation();
                    break;
                case 'auto-layout':
                    this.applyLayout();
                    break;
                case 'delete':
                    this.deleteSelected();
                    break;
            }
        });
    }

    addNode(nodeType, defaultLabel) {
        const nodeId = `node-${Date.now()}`;
        const position = this.getNewNodePosition();

        this.cy.add({
            group: 'nodes',
            data: {
                id: nodeId,
                label: defaultLabel,
                nodeType: nodeType,
                description: '',
                metadata: {},
            },
            position: position
        });

        const newNode = this.cy.getElementById(nodeId);
        this.selectNode(newNode);
        // Don't auto-edit - user can double-click to edit
    }

    getNewNodePosition() {
        const extent = this.cy.extent();
        const centerX = (extent.x1 + extent.x2) / 2;
        const centerY = (extent.y1 + extent.y2) / 2;

        return {
            x: centerX + (Math.random() - 0.5) * 100,
            y: centerY + (Math.random() - 0.5) * 100
        };
    }

    selectNode(node) {
        this.deselectAll();
        this.selectedNode = node;
        node.select();
    }

    selectEdge(edge) {
        this.deselectAll();
        this.selectedEdge = edge;
        edge.select();
    }

    deselectAll() {
        this.cy.elements().unselect();
        this.selectedNode = null;
        this.selectedEdge = null;
        this.edgeSourceNode = null;
        this.element.style.cursor = 'default';
    }

    startEdgeCreation() {
        if (!this.selectedNode) {
            // Don't show alert, just do nothing
            return;
        }

        this.edgeSourceNode = this.selectedNode;
        this.element.style.cursor = 'crosshair';
        // Visual feedback via cursor change - no alert needed
    }

    createEdge(sourceNode, targetNode) {
        const edgeId = `edge-${Date.now()}`;

        this.cy.add({
            group: 'edges',
            data: {
                id: edgeId,
                source: sourceNode.id(),
                target: targetNode.id(),
                label: 'Choice',
                edgeType: 'choice',
                metadata: {},
            }
        });

        const newEdge = this.cy.getElementById(edgeId);
        this.selectEdge(newEdge);
        // Don't auto-edit - user can double-click to edit
    }

    promptEditNode(node) {
        const currentLabel = node.data('label');
        const currentDescription = node.data('description');

        const newLabel = prompt('Node title:', currentLabel);
        if (newLabel !== null && newLabel.trim() !== '') {
            node.data('label', newLabel);
        }

        const newDescription = prompt('Node description (optional):', currentDescription);
        if (newDescription !== null) {
            node.data('description', newDescription);
        }

        // TODO: Add modal for editing metadata (story object references, conditions, etc.)
    }

    promptEditEdge(edge) {
        const currentLabel = edge.data('label');
        const currentType = edge.data('edgeType');

        const newLabel = prompt('Edge label (player choice/consequence):', currentLabel);
        if (newLabel !== null && newLabel.trim() !== '') {
            edge.data('label', newLabel);
        }

        const newType = prompt('Edge type (choice/consequence/reference):', currentType);
        if (newType !== null && ['choice', 'consequence', 'reference'].includes(newType)) {
            edge.data('edgeType', newType);
        }

        // TODO: Add modal for editing metadata (consequences, conditions, etc.)
    }

    deleteSelected() {
        if (this.selectedNode) {
            this.selectedNode.remove();
            this.selectedNode = null;
        } else if (this.selectedEdge) {
            this.selectedEdge.remove();
            this.selectedEdge = null;
        }
        // If nothing selected, just do nothing - no alert needed
    }

    applyLayout() {
        const layoutConfig = this.dagreAvailable ? {
            name: 'dagre',
            nodeDimensionsIncludeLabels: true,
            rankDir: 'TB',
            spacingFactor: 1.5,
            animate: true,
            animationDuration: 500,
        } : {
            name: 'breadthfirst',
            directed: true,
            spacingFactor: 1.5,
            nodeDimensionsIncludeLabels: true,
            animate: true,
            animationDuration: 500,
        };

        this.cy.layout(layoutConfig).run();
    }

    updateInput() {
        if (!this.hasInputTarget) return;

        const treeData = this.serializeTree();
        this.inputTarget.value = JSON.stringify(treeData);
    }

    serializeTree() {
        const nodes = [];
        const edges = [];

        this.cy.nodes().forEach(node => {
            nodes.push({
                id: node.id(),
                type: node.data('nodeType'),
                title: node.data('label'),
                description: node.data('description') || '',
                position: node.position(),
                metadata: node.data('metadata') || {},
            });
        });

        this.cy.edges().forEach(edge => {
            edges.push({
                id: edge.id(),
                source: edge.data('source'),
                target: edge.data('target'),
                label: edge.data('label') || '',
                type: edge.data('edgeType'),
                metadata: edge.data('metadata') || {},
            });
        });

        return {
            nodes: nodes,
            edges: edges,
            metadata: {
                version: '1.0',
                lastModified: new Date().toISOString(),
                layout: 'dagre',
            }
        };
    }

    setupFormHandler() {
        // Find the form and ensure data is serialized before submission
        const form = this.element.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                // Force update the input value right before submission
                const treeData = this.serializeTree();
                const jsonString = JSON.stringify(treeData);
                this.inputTarget.value = jsonString;

                console.log('Form submitting with tree data:', jsonString);
                console.log('Input element:', this.inputTarget);
                console.log('Input name:', this.inputTarget.name);
                console.log('Input value length:', this.inputTarget.value.length);
            });
        }
    }

    editNodeInline(node) {
        // Remove any existing editor
        this.removeActiveEditor();

        const position = node.renderedPosition();
        const label = node.data('label');

        // Get container position to calculate absolute positioning
        const containerRect = this.element.getBoundingClientRect();

        // Create input element
        const input = document.createElement('input');
        input.type = 'text';
        input.value = label;
        input.className = 'graph-text-editor';
        input.style.cssText = `
            position: fixed;
            left: ${containerRect.left + position.x - 60}px;
            top: ${containerRect.top + position.y - 10}px;
            width: 120px;
            padding: 4px 8px;
            border: 2px solid #007bff;
            border-radius: 4px;
            font-size: 12px;
            z-index: 9999;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        `;

        // Store reference
        this.activeEditor = { input, node };

        // Add to body for better positioning
        document.body.appendChild(input);
        input.focus();
        input.select();

        // Save on enter or blur
        const save = () => {
            const newLabel = input.value.trim();
            if (newLabel) {
                node.data('label', newLabel);
            }
            this.removeActiveEditor();
        };

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                save();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.removeActiveEditor();
            }
        });

        input.addEventListener('blur', save);
    }

    editEdgeInline(edge) {
        // Remove any existing editor
        this.removeActiveEditor();

        const midpoint = edge.renderedMidpoint();
        const label = edge.data('label');

        // Get container position to calculate absolute positioning
        const containerRect = this.element.getBoundingClientRect();

        // Create input element
        const input = document.createElement('input');
        input.type = 'text';
        input.value = label;
        input.className = 'graph-text-editor';
        input.style.cssText = `
            position: fixed;
            left: ${containerRect.left + midpoint.x - 60}px;
            top: ${containerRect.top + midpoint.y - 10}px;
            width: 120px;
            padding: 4px 8px;
            border: 2px solid #28a745;
            border-radius: 4px;
            font-size: 10px;
            z-index: 9999;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        `;

        // Store reference
        this.activeEditor = { input, edge };

        // Add to body for better positioning
        document.body.appendChild(input);
        input.focus();
        input.select();

        // Save on enter or blur
        const save = () => {
            const newLabel = input.value.trim();
            if (newLabel) {
                edge.data('label', newLabel);
            }
            this.removeActiveEditor();
        };

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                save();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.removeActiveEditor();
            }
        });

        input.addEventListener('blur', save);
    }

    removeActiveEditor() {
        if (this.activeEditor) {
            if (this.activeEditor.input.parentElement) {
                this.activeEditor.input.parentElement.removeChild(this.activeEditor.input);
            }
            this.activeEditor = null;
        }
    }

    setupContextMenu() {
        // Context menu on background (right-click)
        this.cy.on('cxttap', (event) => {
            if (event.target === this.cy) {
                this.showBackgroundContextMenu(event);
            }
        });

        // Context menu on nodes
        this.cy.on('cxttap', 'node', (event) => {
            event.preventDefault();
            this.showNodeContextMenu(event);
        });

        // Context menu on edges
        this.cy.on('cxttap', 'edge', (event) => {
            event.preventDefault();
            this.showEdgeContextMenu(event);
        });

        // Close context menu on any click
        this.cy.on('tap', () => {
            this.hideContextMenu();
        });
    }

    showBackgroundContextMenu(event) {
        this.hideContextMenu();

        const menu = document.createElement('div');
        menu.className = 'context-menu';
        menu.style.cssText = `
            position: fixed;
            left: ${event.originalEvent.pageX}px;
            top: ${event.originalEvent.pageY}px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10000;
            padding: 4px 0;
            min-width: 150px;
        `;

        const items = [
            { label: 'Add Start Node', action: () => this.addNodeAtPosition('start', 'Start', event.position) },
            { label: 'Add Decision Node', action: () => this.addNodeAtPosition('decision', 'Decision?', event.position) },
            { label: 'Add Outcome Node', action: () => this.addNodeAtPosition('outcome', 'Outcome', event.position) },
            { label: 'Add Reference Node', action: () => this.addNodeAtPosition('reference', 'Reference', event.position) },
            { label: 'Add End Node', action: () => this.addNodeAtPosition('end', 'End', event.position) },
            { type: 'separator' },
            { label: 'Auto Layout', action: () => this.applyLayout() },
        ];

        items.forEach(item => {
            if (item.type === 'separator') {
                const separator = document.createElement('hr');
                separator.style.cssText = 'margin: 4px 0; border: none; border-top: 1px solid #eee;';
                menu.appendChild(separator);
            } else {
                const menuItem = document.createElement('div');
                menuItem.textContent = item.label;
                menuItem.style.cssText = 'padding: 8px 16px; cursor: pointer; user-select: none;';
                menuItem.addEventListener('mouseenter', () => {
                    menuItem.style.backgroundColor = '#f0f0f0';
                });
                menuItem.addEventListener('mouseleave', () => {
                    menuItem.style.backgroundColor = 'white';
                });
                menuItem.addEventListener('click', () => {
                    item.action();
                    this.hideContextMenu();
                });
                menu.appendChild(menuItem);
            }
        });

        document.body.appendChild(menu);
        this.currentContextMenu = menu;
    }

    showNodeContextMenu(event) {
        this.hideContextMenu();
        const node = event.target;

        const menu = document.createElement('div');
        menu.className = 'context-menu';
        menu.style.cssText = `
            position: fixed;
            left: ${event.originalEvent.pageX}px;
            top: ${event.originalEvent.pageY}px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10000;
            padding: 4px 0;
            min-width: 150px;
        `;

        const items = [
            { label: 'Edit Node', action: () => this.editNodeInline(node) },
            { label: 'Connect to...', action: () => { this.edgeSourceNode = node; this.element.style.cursor = 'crosshair'; } },
        ];

        // Add "Manage References" for reference nodes
        if (node.data('nodeType') === 'reference') {
            items.push({ label: 'Manage Story Objects...', action: () => this.manageStoryObjectReferences(node), style: 'font-weight: 600;' });
        }

        items.push({ type: 'separator' });
        items.push({ label: 'Delete Node', action: () => node.remove(), style: 'color: #dc3545;' });

        items.forEach(item => {
            if (item.type === 'separator') {
                const separator = document.createElement('hr');
                separator.style.cssText = 'margin: 4px 0; border: none; border-top: 1px solid #eee;';
                menu.appendChild(separator);
            } else {
                const menuItem = document.createElement('div');
                menuItem.textContent = item.label;
                menuItem.style.cssText = `padding: 8px 16px; cursor: pointer; user-select: none; ${item.style || ''}`;
                menuItem.addEventListener('mouseenter', () => {
                    menuItem.style.backgroundColor = '#f0f0f0';
                });
                menuItem.addEventListener('mouseleave', () => {
                    menuItem.style.backgroundColor = 'white';
                });
                menuItem.addEventListener('click', () => {
                    item.action();
                    this.hideContextMenu();
                });
                menu.appendChild(menuItem);
            }
        });

        document.body.appendChild(menu);
        this.currentContextMenu = menu;
    }

    showEdgeContextMenu(event) {
        this.hideContextMenu();
        const edge = event.target;

        const menu = document.createElement('div');
        menu.className = 'context-menu';
        menu.style.cssText = `
            position: fixed;
            left: ${event.originalEvent.pageX}px;
            top: ${event.originalEvent.pageY}px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 10000;
            padding: 4px 0;
            min-width: 150px;
        `;

        const items = [
            { label: 'Edit Edge', action: () => this.editEdgeInline(edge) },
            { type: 'separator' },
            { label: 'Delete Edge', action: () => edge.remove(), style: 'color: #dc3545;' },
        ];

        items.forEach(item => {
            if (item.type === 'separator') {
                const separator = document.createElement('hr');
                separator.style.cssText = 'margin: 4px 0; border: none; border-top: 1px solid #eee;';
                menu.appendChild(separator);
            } else {
                const menuItem = document.createElement('div');
                menuItem.textContent = item.label;
                menuItem.style.cssText = `padding: 8px 16px; cursor: pointer; user-select: none; ${item.style || ''}`;
                menuItem.addEventListener('mouseenter', () => {
                    menuItem.style.backgroundColor = '#f0f0f0';
                });
                menuItem.addEventListener('mouseleave', () => {
                    menuItem.style.backgroundColor = 'white';
                });
                menuItem.addEventListener('click', () => {
                    item.action();
                    this.hideContextMenu();
                });
                menu.appendChild(menuItem);
            }
        });

        document.body.appendChild(menu);
        this.currentContextMenu = menu;
    }

    hideContextMenu() {
        if (this.currentContextMenu) {
            this.currentContextMenu.remove();
            this.currentContextMenu = null;
        }
    }

    addNodeAtPosition(nodeType, defaultLabel, position) {
        const nodeId = `node-${Date.now()}`;

        this.cy.add({
            group: 'nodes',
            data: {
                id: nodeId,
                label: defaultLabel,
                nodeType: nodeType,
                description: '',
                metadata: {},
            },
            position: position
        });

        const newNode = this.cy.getElementById(nodeId);
        this.selectNode(newNode);
        // Don't auto-edit - user can double-click to edit
    }

    manageStoryObjectReferences(node) {
        // Get current references from node metadata
        const metadata = node.data('metadata') || {};
        const storyObjects = metadata.storyObjects || [];

        // Create modal
        this.showStoryObjectModal(node, storyObjects);
    }

    showStoryObjectModal(node, currentReferences) {
        // Remove existing modal if any
        this.hideStoryObjectModal();

        // Create modal backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.style.cssText = 'z-index: 10040;';

        // Create modal
        const modal = document.createElement('div');
        modal.className = 'modal fade show';
        modal.style.cssText = 'display: block; z-index: 10050;';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Manage Story Object References</h5>
                        <button type="button" class="btn-close" data-action="close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Search and Add Story Objects:</label>
                            <select id="story-object-search" class="form-select" multiple></select>
                            <small class="form-text text-muted">
                                Search for Characters, Items, Places, Factions, and other story objects to reference.
                            </small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current References:</label>
                            <div id="current-references" class="border rounded p-3" style="min-height: 100px; background-color: #f8f9fa;">
                                ${this.renderReferenceList(currentReferences)}
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-action="close">Cancel</button>
                        <button type="button" class="btn btn-primary" data-action="save">Save References</button>
                    </div>
                </div>
            </div>
        `;

        // Store references
        this.currentModal = { modal, backdrop, node, references: [...currentReferences] };

        // Add to DOM
        document.body.appendChild(backdrop);
        document.body.appendChild(modal);

        // Setup event handlers
        modal.querySelector('[data-action="close"]').addEventListener('click', () => this.hideStoryObjectModal());
        modal.querySelectorAll('[data-action="close"]').forEach(btn => {
            btn.addEventListener('click', () => this.hideStoryObjectModal());
        });
        modal.querySelector('[data-action="save"]').addEventListener('click', () => this.saveStoryObjectReferences());

        // Initialize TomSelect for search (dynamic import to avoid loading if not needed)
        this.initializeStoryObjectSearch();
    }

    renderReferenceList(references) {
        if (references.length === 0) {
            return '<p class="text-muted mb-0">No story objects referenced yet.</p>';
        }

        return references.map((ref, index) => `
            <div class="d-flex align-items-center justify-content-between mb-2 p-2 bg-white border rounded">
                <div>
                    <strong>${this.escapeHtml(ref.title || 'Untitled')}</strong>
                    <span class="badge bg-secondary ms-2">${this.escapeHtml(ref.type || 'unknown')}</span>
                    ${ref.role ? `<span class="badge bg-info ms-1">${this.escapeHtml(ref.role)}</span>` : ''}
                </div>
                <button type="button" class="btn btn-sm btn-danger" data-remove-index="${index}">
                    <i class="bi bi-trash"></i> Remove
                </button>
            </div>
        `).join('');
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    async initializeStoryObjectSearch() {
        if (!this.larpIdValue) {
            console.warn('No larpId provided, story object search disabled');
            return;
        }

        const selectElement = document.getElementById('story-object-search');
        if (!selectElement) return;

        // Dynamically import TomSelect
        const TomSelect = (await import('tom-select')).default;

        // Initialize TomSelect with AJAX search
        const tomSelect = new TomSelect(selectElement, {
            valueField: 'id',
            labelField: 'title',
            searchField: ['title'],
            placeholder: 'Type to search for story objects...',
            load: (query, callback) => {
                if (!query.length) {
                    callback();
                    return;
                }

                // Fetch from API
                const apiUrl = `/api/larp/${this.larpIdValue}/story-object/mention-search?query=${encodeURIComponent(query)}`;
                console.log('Fetching story objects from:', apiUrl);

                fetch(apiUrl)
                    .then(response => {
                        console.log('API response status:', response.status);
                        return response.json();
                    })
                    .then(data => {
                        console.log('API response data:', data);

                        // Flatten grouped results - API returns array of {type, items:[]}
                        const allObjects = [];

                        if (Array.isArray(data)) {
                            data.forEach(group => {
                                if (group.items && Array.isArray(group.items)) {
                                    group.items.forEach(obj => {
                                        allObjects.push({
                                            id: obj.id,
                                            title: obj.name || obj.title || 'Untitled',
                                            type: obj.type || group.type,
                                        });
                                    });
                                }
                            });
                        }

                        console.log('Processed objects:', allObjects);
                        callback(allObjects);
                    })
                    .catch(error => {
                        console.error('Story object search failed:', error);
                        callback();
                    });
            },
            render: {
                option: (data, escape) => {
                    return `<div>
                        <strong>${escape(data.title)}</strong>
                        <span class="badge bg-secondary ms-2">${escape(data.type)}</span>
                    </div>`;
                },
                item: (data, escape) => {
                    return `<div>
                        ${escape(data.title)} <span class="badge bg-secondary">${escape(data.type)}</span>
                    </div>`;
                }
            },
            onChange: (value) => {
                if (value) {
                    const selectedData = tomSelect.options[value];
                    if (selectedData) {
                        this.addStoryObjectReference(selectedData);
                        tomSelect.clear();
                    }
                }
            }
        });

        this.currentModal.tomSelect = tomSelect;
    }

    addStoryObjectReference(storyObject) {
        if (!this.currentModal) return;

        // Check if already added
        const exists = this.currentModal.references.find(ref => ref.id === storyObject.id);
        if (exists) {
            return; // Already added
        }

        // Add to references
        this.currentModal.references.push({
            id: storyObject.id,
            title: storyObject.title,
            type: storyObject.type,
            role: 'involved', // Default role
        });

        // Re-render reference list
        this.updateReferenceList();
    }

    updateReferenceList() {
        if (!this.currentModal) return;

        const container = document.getElementById('current-references');
        if (container) {
            container.innerHTML = this.renderReferenceList(this.currentModal.references);

            // Attach remove handlers
            container.querySelectorAll('[data-remove-index]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const index = parseInt(e.currentTarget.dataset.removeIndex, 10);
                    this.removeStoryObjectReference(index);
                });
            });
        }
    }

    removeStoryObjectReference(index) {
        if (!this.currentModal) return;

        this.currentModal.references.splice(index, 1);
        this.updateReferenceList();
    }

    saveStoryObjectReferences() {
        if (!this.currentModal) return;

        const node = this.currentModal.node;
        const references = this.currentModal.references;

        // Update node metadata
        const metadata = node.data('metadata') || {};
        metadata.storyObjects = references;
        node.data('metadata', metadata);

        // Update node visual to show badge count
        this.updateNodeReferenceBadge(node);

        // Close modal
        this.hideStoryObjectModal();
    }

    updateNodeReferenceBadge(node) {
        const metadata = node.data('metadata') || {};
        const storyObjects = metadata.storyObjects || [];

        // Update node label to include badge
        const baseLabel = node.data('label').replace(/\s+\(\d+\)$/, ''); // Remove existing count
        if (storyObjects.length > 0) {
            node.data('label', `${baseLabel} (${storyObjects.length})`);
        } else {
            node.data('label', baseLabel);
        }
    }

    hideStoryObjectModal() {
        if (this.currentModal) {
            // Destroy TomSelect
            if (this.currentModal.tomSelect) {
                this.currentModal.tomSelect.destroy();
            }

            // Remove modal and backdrop
            if (this.currentModal.modal.parentElement) {
                this.currentModal.modal.parentElement.removeChild(this.currentModal.modal);
            }
            if (this.currentModal.backdrop.parentElement) {
                this.currentModal.backdrop.parentElement.removeChild(this.currentModal.backdrop);
            }

            this.currentModal = null;
        }
    }

    disconnect() {
        this.removeActiveEditor();
        this.hideContextMenu();
        this.hideStoryObjectModal();
        if (this.cy) {
            this.cy.destroy();
        }
    }
}
