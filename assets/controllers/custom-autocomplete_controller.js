import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static values = {
        url: String,
        type: String,
    };
    initialize() {
        this._onPreConnect = this._onPreConnect.bind(this);
    }

    connect() {
        this.element.addEventListener('autocomplete:pre-connect', this._onPreConnect);
    }

    disconnect() {
        // You should always remove listeners when the controller is disconnected to avoid side-effects
        this.element.removeEventListener('autocomplete:pre-connect', this._onPreConnect);
    }

    _onPreConnect(event) {
        const { urlValue, typeValue } = this;
        event.detail.options.create = function (input, callback) {
            const data = new FormData();
            data.append('title', input);
            data.append('type', typeValue);
            fetch(urlValue, {
                method: 'POST',
                body:data,
            })
                .then(response => response.json())
                .then(data => callback({value: data.id, text: data.title}));
        }
    }
}