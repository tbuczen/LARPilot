import { Controller } from '@hotwired/stimulus';
import Sortable from '../vendor/sortable.esm.js';

export default class extends Controller {
    static targets = ['column'];
    static values = { updateUrl: String };

    connect() {
        this.columnTargets.forEach(column => {
            Sortable.create(column.querySelector('ul'), {
                group: 'kanban',
                animation: 150,
                onEnd: event => this._onEnd(event)
            });
        });
    }

    _onEnd(event) {
        const taskId = event.item.dataset.taskId;
        const status = event.to.closest('[data-status]').dataset.status;
        const index = event.newIndex;
        fetch(this.updateUrlValue.replace('ID', taskId), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ status: status, position: index })
        });
    }
}
