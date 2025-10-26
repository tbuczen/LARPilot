import { Controller } from '@hotwired/stimulus';
import Quill from 'quill';
import {Mention, MentionBlot} from "quill-mention";

export default class extends Controller {
    static values = {
        larp: String,
        searchUrl: { type: String, default: '/api/larp/__larp__/story-object/mention-search' },
        debounceDelay: { type: Number, default: 200 }, // 200ms debounce
    };

    async connect() {
        this.textarea = this.element;

        // Store original required state and remove it to prevent HTML5 validation on hidden field
        this.wasRequired = this.textarea.hasAttribute('required');
        if (this.wasRequired) {
            this.textarea.removeAttribute('required');
        }

        if (!this.hasLarpValue) {
            const match = window.location.pathname.match(/\/larp\/([^/]+)/);
            this.larpValue = match ? match[1] : null;
        }
        const url = this.searchUrlValue.replace('__larp__', this.larpValue || '');

        Quill.register({ 'blots/mention': MentionBlot, 'modules/mention': Mention });

        let controller = null;
        let debounceId = null;
        const debounceMs = this.debounceDelayValue ?? 200;
        const cache = new Map();

        // Render a loader row via renderList (reliable for quill-mention)
        const renderLoader = (renderList, searchTerm) => {
            renderList(
                [{ id: '__loading__', value: 'Searching…', type: '', __loading: true }],
                searchTerm
            );
        };

        const fetchSuggestions = async (q) => {
            const res = await fetch(`${url}?query=${encodeURIComponent(q)}`, {
                method: 'GET',
                signal: controller.signal,
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            });
            if (!res.ok) return [];
            const data = await res.json();
            // Accept grouped or flat
            return Array.isArray(data) && data.length && data[0]?.items
                ? data.flatMap(group => (group.items || []).map(i => ({
                    id: i.id, value: i.name, type: i.type, url: i.url, group: group.type
                })))
                : (data || []).map(i => ({ id: i.id, value: i.name, type: i.type, url: i.url }));
        };

        // IMPORTANT: debounce inside source; do not fetch before the timeout
        const debouncedSuggest = (searchTerm, renderList) => {
            if (debounceId) clearTimeout(debounceId);

            const q = (searchTerm || '').trim();

            // If empty -> clear list immediately (no network)
            if (q.length === 0) {
                renderList([], searchTerm);
                return;
            }

            renderLoader(renderList, searchTerm);

            debounceId = setTimeout(async () => {
                if (controller) controller.abort();
                controller = new AbortController();

                const key = q.toLowerCase();
                if (cache.has(key)) {
                    renderList(cache.get(key), searchTerm);
                    return;
                }

                try {
                    const items = await fetchSuggestions(q);
                    cache.set(key, items);
                    if (cache.size > 150) cache.delete(cache.keys().next().value);
                    renderList(items, searchTerm);
                } catch (e) {
                    if (e.name !== 'AbortError') console.error('mention fetch error', e);
                    renderList([], searchTerm);
                }
            }, debounceMs);
        };

        this.quill = new Quill(this._ensureEditorHost(), {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['font', 'size'],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link'],
                    ['clean'],
                ],
                mention: {
                    mentionDenotationChars: ['@'],
                    showDenotationChar: true,
                    source: function(searchTerm, renderList) {
                        debouncedSuggest(searchTerm, renderList);
                    },
                    renderItem(item) {
                        // Special rendering for loader row
                        if (item.__loading) {
                            const li = document.createElement('div');
                            li.className = 'ql-mention-item ql-mention-item-loading';
                            li.innerHTML = `<span class="spinner" aria-hidden="true"></span><span>Searching…</span>`;
                            return li;
                        }

                        const container = document.createElement('div');
                        container.className = 'ql-mention-item';

                        const label = document.createElement('div');
                        label.className = 'ql-mention-item-label';
                        label.textContent = item.value;

                        const meta = document.createElement('div');
                        meta.className = 'ql-mention-item-meta';
                        meta.textContent = item.type || '';

                        container.appendChild(label);
                        container.appendChild(meta);
                        return container;
                    },
                }
            },
            //.editorholder {
            //   height: 500px;
            //   display: flex;
            //   flex-flow: column;
            // }
        });

        if (this.textarea.value) {
            try {
                this.quill.clipboard.dangerouslyPasteHTML(this.textarea.value);
            } catch {
                this.editorEl.innerHTML = this.textarea.value;
            }
        }

        this.quill.on('text-change', () => this._syncToTextarea());

        // Add form validation handler
        if (this.textarea.form) {
            this.formSubmitHandler = (e) => {
                this._syncToTextarea();

                // Custom validation if field was originally required
                if (this.wasRequired) {
                    const text = this.quill.getText().trim();
                    if (!text) {
                        e.preventDefault();
                        this.wrapper.classList.add('is-invalid');

                        // Show validation message
                        let feedback = this.wrapper.nextElementSibling;
                        if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                            feedback = document.createElement('div');
                            feedback.className = 'invalid-feedback';
                            feedback.style.display = 'block';
                            this.wrapper.parentNode.insertBefore(feedback, this.wrapper.nextSibling);
                        }
                        feedback.textContent = 'This field is required.';
                        return false;
                    } else {
                        this.wrapper.classList.remove('is-invalid');
                        const feedback = this.wrapper.nextElementSibling;
                        if (feedback && feedback.classList.contains('invalid-feedback')) {
                            feedback.remove();
                        }
                    }
                }
            };
            this.textarea.form.addEventListener('submit', this.formSubmitHandler);
        }
    }

    _ensureEditorHost() {
        if (!this.wrapper) {
            this.wrapper = document.createElement('div');
            this.editorEl = document.createElement('div');
            this.wrapper.appendChild(this.editorEl);
            this.textarea.style.display = 'none';
            this.textarea.parentNode.insertBefore(this.wrapper, this.textarea);
        }
        return this.editorEl;
    }

    disconnect() {
        this._syncToTextarea();

        // Remove form submit handler
        if (this.textarea.form && this.formSubmitHandler) {
            this.textarea.form.removeEventListener('submit', this.formSubmitHandler);
        }

        // Restore required attribute if it was originally there
        if (this.wasRequired) {
            this.textarea.setAttribute('required', 'required');
        }

        if (this.wrapper && this.wrapper.parentNode) {
            this.wrapper.parentNode.removeChild(this.wrapper);
        }
        this.textarea.style.display = '';
        this.quill = null;
    }

    _syncToTextarea() {
        const html = this.editorEl.querySelector('.ql-editor')?.innerHTML ?? '';
        this.textarea.value = html;
    }
}