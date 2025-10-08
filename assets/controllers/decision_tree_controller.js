import { Controller } from '@hotwired/stimulus';
import cytoscape from 'cytoscape';

export default class extends Controller {
    static targets = ['input'];
    static values = {
        elements: Array,
    };

    connect() {
        this.cy = cytoscape({
            container: this.element,
            elements: this.elementsValue || [],
            layout: {
                name: 'breadthfirst',
                nodeDimensionsIncludeLabels: true,
            },
            style: [
                {
                    selector: 'node',
                    style: {
                        'label': 'data(label)',
                        'background-color': '#007bff',
                        'color': '#fff',
                        'text-valign': 'center',
                        'text-halign': 'center',
                        'font-size': '10px',
                        'shape': 'roundrectangle'
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
                }
            ]
        });

        this.updateInput();
        this.cy.on('add remove', () => this.updateInput());
    }

    updateInput() {
        if (this.hasInputTarget) {
            this.inputTarget.value = JSON.stringify(this.cy.json().elements);
        }
    }
}
