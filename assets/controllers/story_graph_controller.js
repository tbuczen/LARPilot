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
                        'label': 'data(label)',
                        'background-color': '#007bff',
                        'color': '#fff',
                        'text-valign': 'center',
                        'text-halign': 'center',
                        'font-size': '10px',
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
            ],
            layout: {
                name: 'cose',
                animate: false
            }
        });
    }
}