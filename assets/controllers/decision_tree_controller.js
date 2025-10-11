import { Controller } from '@hotwired/stimulus';
import cytoscape from 'cytoscape';

export default class extends Controller {
    static targets = ['input'];
    static values = {
        elements: Array, // Accept both Array and Object
        larpId: { type: String, default: '' },
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
        this.updateInput();
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

        // Edge selection
        this.cy.on('tap', 'edge', (event) => {
            this.selectEdge(event.target);
        });

        // Deselect on background tap
        this.cy.on('tap', (event) => {
            if (event.target === this.cy) {
                this.deselectAll();
            }
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
        this.promptEditNode(newNode);
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
            alert('Please select a source node first');
            return;
        }

        this.edgeSourceNode = this.selectedNode;
        this.element.style.cursor = 'crosshair';
        alert('Click on a target node to create connection');
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
        this.promptEditEdge(newEdge);
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
        } else {
            alert('Please select a node or edge to delete');
        }
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

    disconnect() {
        if (this.cy) {
            this.cy.destroy();
        }
    }
}
