import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['toggle', 'item'];

    toggle() {
        this.itemTargets.forEach((item) => {
            item.checked = this.toggleTarget.checked;
        });
    }
}
