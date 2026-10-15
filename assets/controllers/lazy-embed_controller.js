import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['frame'];

    static values = {
        src: String,
    };

    load() {
        if (!this.element.open || !this.hasFrameTarget || this.frameTarget.getAttribute('src')) {
            return;
        }
        this.frameTarget.setAttribute('src', this.srcValue);
    }
}
