import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['query', 'results'];

    static values = {
        url: String,
        empty: String,
        error: String,
        delay: { type: Number, default: 250 },
    };

    connect() {
        this.debounceTimer = null;
        this.abortController = null;
        this.loaded = false;
        this.boundLoad = this.load.bind(this);
        this.element.addEventListener('show.bs.modal', this.boundLoad);
    }

    disconnect() {
        this.element.removeEventListener('show.bs.modal', this.boundLoad);
        clearTimeout(this.debounceTimer);
        this.abortController?.abort();
    }

    load() {
        if (this.loaded) {
            return;
        }
        this.loaded = true;
        this.fetchResults('');
    }

    search() {
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => this.fetchResults(this.queryTarget.value), this.delayValue);
    }

    preventSubmit(event) {
        event.preventDefault();
        clearTimeout(this.debounceTimer);
        this.fetchResults(this.queryTarget.value);
    }

    async fetchResults(searchTerm) {
        this.abortController?.abort();
        this.abortController = new AbortController();

        const url = new URL(this.urlValue, window.location.origin);
        url.searchParams.set('q', searchTerm.trim());

        try {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                signal: this.abortController.signal,
            });
            if (!response.ok) {
                this.renderMessage(this.errorValue);
                return;
            }
            const payload = await response.json();
            this.renderItems(payload.items || []);
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }
            this.renderMessage(this.errorValue);
        }
    }

    renderItems(items) {
        if (items.length === 0) {
            this.renderMessage(this.emptyValue);
            return;
        }

        const buttons = items.map((item) => {
            const button = document.createElement('button');
            button.type = 'submit';
            button.name = 'item';
            button.value = item.id;
            button.className = 'list-group-item list-group-item-action d-flex flex-column align-items-start gap-1';

            const title = document.createElement('span');
            title.className = 'fw-semibold text-break';
            title.textContent = item.title;

            const meta = document.createElement('small');
            meta.className = 'text-muted text-break';
            meta.textContent = [item.provider, item.source, item.kind].filter(Boolean).join(' · ');

            button.append(title, meta);
            return button;
        });

        this.resultsTarget.replaceChildren(...buttons);
    }

    renderMessage(message) {
        const paragraph = document.createElement('p');
        paragraph.className = 'text-muted small mb-0 py-2';
        paragraph.textContent = message;
        this.resultsTarget.replaceChildren(paragraph);
    }
}
