import { Controller } from '@hotwired/stimulus';
import cytoscape from 'cytoscape';

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
            ],
            layout: {
                name: 'cose',
                animate: false,
                nodeDimensionsIncludeLabels: true,
            }
        });
    }
}