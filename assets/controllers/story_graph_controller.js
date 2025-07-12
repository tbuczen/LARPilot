import { Controller } from '@hotwired/stimulus';
import cytoscape from 'cytoscape';
import { applyFactionGroupLayout } from '../utils/factionGroupLayout.js';

export default class extends Controller {
    static values = {
        graph: Object,
    };

    connect() {
        this.cy = cytoscape({
            container: this.element,
            elements: this.graphValue,
            style: [
                {
                    selector: 'node',
                    style: {
                        'label': 'data(title)',
                        'text-wrap': 'wrap',
                        'text-max-width': '100px',
                        'background-color': '#007bff',
                        'color': '#fff',
                        'text-valign': 'center',
                        'text-halign': 'center',
                        'font-size': '10px',
                        'shape': 'roundrectangle',
                        'cursor': 'pointer',
                        'width': 'label',
                        'height': 'label',
                        'padding': '8px',
                    }
                },
                {
                    selector: ':parent',
                    style: {
                        'background-opacity': 0.2,
                        'padding': '10px',
                    }
                },
                {
                    selector: 'node[type="character"]',
                    style: {
                        'background-color': '#0d6efd',
                    }
                },
                {
                    selector: 'node[type="faction"]',
                    style: {
                        'background-color': '#6c757d',
                    }
                },
                {
                    selector: 'node[type="thread"]',
                    style: {
                        'background-color': '#198754',
                    }
                },
                {
                    selector: 'node[type="event"]',
                    style: {
                        'background-color': '#20c997',
                    }
                },
                {
                    selector: 'node[type="quest"]',
                    style: {
                        'background-color': '#ffc107',
                    }
                },
                {
                    selector: 'node[type="item"]',
                    style: {
                        'background-color': '#6610f2',
                    }
                },
                {
                    selector: 'node[type="place"]',
                    style: {
                        'background-color': '#d63384',
                    }
                },

                {
                    selector: 'node[type="factionGroup"]',
                    style: {
                        'background-color': 'rgba(173,181,189,0.71)',
                    }
                },
                {
                    selector: 'node[type="threadGroup"]',
                    style: {
                        'background-color': '#4A976EFF',
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
                        'width': 2,
                        'line-color': '#ccc',
                        'target-arrow-color': '#ccc',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'bezier'
                    }
                },
                {
                    selector: 'edge[title]',
                    style: {
                        'label': 'data(title)',
                        'text-rotation': 'autorotate',
                        'font-size': '8px',
                        'text-background-color': '#fff',
                        'text-background-opacity': 1,
                        'text-background-padding': 2,
                    }
                },
                {
                    selector: 'edge[type="relation"]',
                    style: {
                        'width': 3,
                        'line-color': '#ff6b6b',
                        'target-arrow-color': '#ff6b6b',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'bezier',
                        'line-style': 'solid'
                    }
                },
                {
                    selector: 'edge[type="related"]',
                    style: {
                        'width': 1,
                        'line-color': '#ccc',
                        'target-arrow-color': '#ccc',
                        'target-arrow-shape': 'triangle',
                        'curve-style': 'bezier',
                        'line-style': 'dashed'
                    }
                }
            ],
            layout: {
                animate: false,
                nodeDimensionsIncludeLabels: true,
            }
        });

        this.cy.once('layoutstop', () => {
            applyFactionGroupLayout(this.cy);
        });

        this.cy.layout({
            name: 'breadthfirst',
            orientation: 'vertical',
            nodeDimensionsIncludeLabels: true,
            spacingFactor: 1.2,
        }).run();

        this.cy.on('tap', 'node', (event) => {
            const node = event.target;
            console.log(`Clicked node type=${node.data('type')} id=${node.id()}`);
        });

        this.cy.on('mouseover', 'node', (event) => {
            event.target.addClass('hovered');
            this.cy.container().style.cursor = 'pointer';
        });

        this.cy.on('mouseout', 'node', (event) => {
            event.target.removeClass('hovered');
            this.cy.container().style.cursor = 'default';
        });
    }
}