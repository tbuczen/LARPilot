
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        larp: String,
        searchUrl: { type: String, default: '/backoffice/larp/__larp__/story-object/mention-search' },
    };

    connect() {
        this.textarea = this.element;
        if (!this.hasLarpValue) {
            const match = window.location.pathname.match(/\/larp\/([^/]+)/);
            if (match) {
                this.larpValue = match[1];
            }
        }
        this.searchUrl = this.searchUrlValue.replace('__larp__', this.larpValue);
        this.editor = document.createElement('div');
        this.editor.classList.add('wysiwyg-editor');
        this.editor.contentEditable = true;
        this.editor.innerHTML = this.textarea.value;
        this.textarea.style.display = 'none';
        this.textarea.parentNode.insertBefore(this.editor, this.textarea);
        this._createDropdown();
        this.editor.addEventListener('keyup', (e) => this._onKeyUp(e));
        this.editor.addEventListener('input', () => this._onInput());
        this.textarea.form?.addEventListener('submit', (e) => this._onSubmit(e));
    }

    disconnect() {
        this.editor.removeEventListener('keyup', (e) => this._onKeyUp(e));
        this.editor.removeEventListener('input', () => this._onInput());
        this.textarea.form?.removeEventListener('submit', (e) => this._onSubmit(e));
        this.textarea.style.display = '';
        this._syncContent();
        this.dropdown.remove();
    }

    _createDropdown() {
        this.dropdown = document.createElement('div');
        this.dropdown.classList.add('mention-dropdown');
        this.dropdown.style.display = 'none';
        document.body.appendChild(this.dropdown);
    }

    _onKeyUp(event) {
        const sel = window.getSelection();
        if (!sel || sel.rangeCount === 0) {
            return;
        }
        if (!this.larpValue) {
            return;
        }
        const range = sel.getRangeAt(0);
        const prefix = range.startContainer.textContent?.slice(0, range.startOffset) || '';
        const match = prefix.match(/@([\w\s]{2,})$/);
        if (!match) {
            this.dropdown.style.display = 'none';
            return;
        }
        const query = match[1];
        fetch(`${this.searchUrl}?query=${encodeURIComponent(query)}`)
            .then(r => r.json())
            .then(list => {
                this.dropdown.innerHTML = '';
                list.forEach(item => {
                    const div = document.createElement('div');
                    div.textContent = item.name;
                    div.addEventListener('mousedown', e => {
                        e.preventDefault();
                        this._insertMention(item);
                    });
                    this.dropdown.appendChild(div);
                });
                const rect = range.getBoundingClientRect();
                this.dropdown.style.top = `${rect.bottom + window.scrollY}px`;
                this.dropdown.style.left = `${rect.left + window.scrollX}px`;
                this.dropdown.style.display = 'block';
            });
    }

    _insertMention(item) {
        const span = document.createElement('span');
        span.dataset.storyObjectId = item.id;
        span.textContent = '@' + item.name;
        span.contentEditable = 'false';
        const sel = window.getSelection();
        const range = sel.getRangeAt(0);
        range.deleteContents();
        range.insertNode(span);
        range.setStartAfter(span);
        range.setEndAfter(span);
        sel.removeAllRanges();
        sel.addRange(range);
        this.dropdown.style.display = 'none';
    }

    _onInput() {
        this._syncContent();
    }

    _onSubmit(event) {
        this._syncContent();

        // Check if the textarea is required and empty
        if (this.textarea.hasAttribute('required') && this.textarea.value.trim() === '') {
            event.preventDefault();
            // Show the textarea temporarily to allow focusing
            this.textarea.style.display = 'block';
            this.textarea.focus();
            this.textarea.style.display = 'none';
            // Focus the editor instead
            this.editor.focus();
            return false;
        }
    }

    _syncContent() {
        this.textarea.value = this.editor.innerHTML;
    }
}