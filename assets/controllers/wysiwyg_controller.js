import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        larp: String,
        searchUrl: { type: String, default: '/api/larp/__larp__/story-object/mention-search' },
        debounceDelay: { type: Number, default: 500 }, // 300ms debounce
    };

    connect() {
        this.textarea = this.element;
        this.currentMentionRange = null;
        this.currentMentionText = '';
        this.searchTimeout = null;
        this.currentRequest = null;
        this.lastSearchQuery = '';
        this.searchCache = new Map(); // Cache search results
        
            // Check if the CSRF token is available in the page
            this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            // Get the LARP ID from the data attribute or from the URL
            if (!this.hasLarpValue) {
                const match = window.location.pathname.match(/\/larp\/([^/]+)/);
                if (match) {
                    this.larpValue = match[1];
                } else {
                    console.error('WYSIWYG Editor: No LARP ID provided');
                    return; // Don't initialize if no LARP ID is available
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
        this.editor.addEventListener('keydown', (e) => this._onKeyDown(e));
        this.textarea.form?.addEventListener('submit', (e) => this._onSubmit(e));
        
        // Hide dropdown when clicking outside
        document.addEventListener('click', (e) => this._onDocumentClick(e));
    }

    disconnect() {
        this.editor.removeEventListener('keyup', (e) => this._onKeyUp(e));
        this.editor.removeEventListener('input', () => this._onInput());
        this.editor.removeEventListener('keydown', (e) => this._onKeyDown(e));
        this.textarea.form?.removeEventListener('submit', (e) => this._onSubmit(e));
        document.removeEventListener('click', (e) => this._onDocumentClick(e));
        
        // Clean up timeouts and requests
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        if (this.currentRequest) {
            this.currentRequest.abort();
        }
        
        this.textarea.style.display = '';
        this._syncContent();
        this.dropdown.remove();
    }

    _createDropdown() {
        this.dropdown = document.createElement('div');
        this.dropdown.classList.add('mention-dropdown');
        this.dropdown.style.display = 'none';
        this.dropdown.style.position = 'absolute';
        this.dropdown.style.zIndex = '1000';
        this.dropdown.style.maxHeight = '200px';
        this.dropdown.style.overflowY = 'auto';
        document.body.appendChild(this.dropdown);
    }

    _onKeyDown(event) {
        if (this.dropdown.style.display !== 'block') return;
        
        const items = this.dropdown.querySelectorAll('.mention-item');
        if (items.length === 0) return;
        
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            this._navigateDropdown(event.key === 'ArrowDown' ? 1 : -1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const selected = this.dropdown.querySelector('.mention-item.selected');
            if (selected) {
                selected.click();
            }
        } else if (event.key === 'Escape') {
            this.dropdown.style.display = 'none';
            this._cancelSearch();
        }
    }

    _navigateDropdown(direction) {
        const items = this.dropdown.querySelectorAll('.mention-item');
        const currentIndex = Array.from(items).findIndex(item => item.classList.contains('selected'));
        
        // Remove current selection
        items.forEach(item => item.classList.remove('selected'));
        
        // Calculate new index
        let newIndex = currentIndex + direction;
        if (newIndex < 0) newIndex = items.length - 1;
        if (newIndex >= items.length) newIndex = 0;
        
        // Add selection to new item
        items[newIndex].classList.add('selected');
    }

    _onDocumentClick(event) {
        if (!this.dropdown.contains(event.target) && !this.editor.contains(event.target)) {
            this.dropdown.style.display = 'none';
            this._cancelSearch();
        }
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
        const textNode = range.startContainer;
        
        // Make sure we're in a text node
        if (textNode.nodeType !== Node.TEXT_NODE) {
            this.dropdown.style.display = 'none';
            this._cancelSearch();
            return;
        }
        
        const text = textNode.textContent || '';
        const cursorPos = range.startOffset;
        
        // Find the @ symbol before cursor
        let atIndex = -1;
        for (let i = cursorPos - 1; i >= 0; i--) {
            if (text[i] === '@') {
                atIndex = i;
                break;
            }
            if (text[i] === ' ' || text[i] === '\n') {
                break; // Stop at whitespace
            }
        }
        
        if (atIndex === -1) {
            this.dropdown.style.display = 'none';
            this._cancelSearch();
            return;
        }
        
        // Extract the search query
        const query = text.substring(atIndex + 1, cursorPos);
        
        // Don't search for empty or very short queries
        if (query.length < 1) {
            this.dropdown.style.display = 'none';
            this._cancelSearch();
            return;
        }
        
        // Store the mention range for later use
        this.currentMentionRange = range.cloneRange();
        this.currentMentionRange.setStart(textNode, atIndex);
        this.currentMentionRange.setEnd(textNode, cursorPos);
        this.currentMentionText = '@' + query;
        
        // Use debounced search
        this._debouncedSearchMentions(query);
    }

    _debouncedSearchMentions(query) {
        // Cancel previous search timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Cancel current request if it's still pending
        if (this.currentRequest) {
            this.currentRequest.abort();
            this.currentRequest = null;
        }
        
        // Don't search if the query is the same as the last one
        if (query === this.lastSearchQuery) {
            return;
        }
        
        // Check cache first
        const cacheKey = query.toLowerCase();
        if (this.searchCache.has(cacheKey)) {
            this._displayMentions(this.searchCache.get(cacheKey));
            return;
        }
        
        // Set a debounced timeout
        this.searchTimeout = setTimeout(() => {
            this._searchMentions(query);
        }, this.debounceDelayValue);
    }

    _searchMentions(query) {
        // Create an AbortController for this request
        const controller = new AbortController();
        this.currentRequest = controller;
        
        this.lastSearchQuery = query;
        
        // Ensure the URL has the backoffice prefix and correct LARP ID
        const secureUrl = this.searchUrlValue
            .replace('__larp__', this.larpValue);

        fetch(`${secureUrl}?query=${encodeURIComponent(query)}`, {
            signal: controller.signal,
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                ...(this.csrfToken ? { 'X-CSRF-TOKEN': this.csrfToken } : {})
            },
            credentials: 'same-origin' // Send cookies for authentication
        })
            .then(r => {
                if (!r.ok) {
                    // Handle specific HTTP status codes
                    if (r.status === 401) {
                        throw new Error('You need to log in to search for mentions');
                    } else if (r.status === 403) {
                        throw new Error('You don\'t have permission to access this LARP');
                    } else {
                        throw new Error(`Server error: ${r.status}`);
                    }
                }
                return r.json();
            })
            .then(list => {
                // Check if the response contains an error message
                if (list.error) {
                    throw new Error(list.error);
                }

                // Only process if this is still the current request
                if (this.currentRequest === controller) {
                    // Cache the result
                    const cacheKey = query.toLowerCase();
                    this.searchCache.set(cacheKey, list);

                    // Limit cache size to prevent memory issues
                    if (this.searchCache.size > 50) {
                        const firstKey = this.searchCache.keys().next().value;
                        this.searchCache.delete(firstKey);
                    }

                    this._displayMentions(list);
                    this.currentRequest = null;
                }
            })
            .catch(error => {
                // Only log error if it's not an abort
                if (error.name !== 'AbortError') {
                    console.error('Error searching mentions:', error);
                    this.dropdown.style.display = 'none';

                    // Show error message in dropdown for user feedback
                    this._showErrorInDropdown(error.message || 'Error searching for mentions');
                }
                this.currentRequest = null;
            });
    }

    _cancelSearch() {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = null;
        }
        if (this.currentRequest) {
            this.currentRequest.abort();
            this.currentRequest = null;
        }
    }

    _showErrorInDropdown(message) {
        this.dropdown.innerHTML = '';

        const errorDiv = document.createElement('div');
        errorDiv.classList.add('mention-item', 'mention-error');
        errorDiv.textContent = message;
        errorDiv.style.color = '#dc3545';
        this.dropdown.appendChild(errorDiv);

        this._positionDropdown();
        this.dropdown.style.display = 'block';

        // Hide error after 3 seconds
        setTimeout(() => {
            if (this.dropdown.contains(errorDiv)) {
                this.dropdown.style.display = 'none';
            }
        }, 3000);
    }

    _displayMentions(list) {
        this.dropdown.innerHTML = '';
        
        if (list.length === 0) {
            this.dropdown.style.display = 'none';
            return;
        }
        
        list.forEach((item, index) => {
            const div = document.createElement('div');
            div.classList.add('mention-item');
            div.textContent = item.name;
            div.dataset.id = item.id;
            div.dataset.name = item.name;
            div.dataset.type = item.type;
            
            if (index === 0) {
                div.classList.add('selected');
            }
            
            div.addEventListener('mousedown', e => {
                e.preventDefault();
                this._insertMention(item);
            });
            
            div.addEventListener('mouseenter', () => {
                this.dropdown.querySelectorAll('.mention-item').forEach(i => i.classList.remove('selected'));
                div.classList.add('selected');
            });
            
            this.dropdown.appendChild(div);
        });
        
        this._positionDropdown();
        this.dropdown.style.display = 'block';
    }

    _positionDropdown() {
        if (!this.currentMentionRange) return;
        
        const rect = this.currentMentionRange.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
        
        this.dropdown.style.top = `${rect.bottom + scrollTop}px`;
        this.dropdown.style.left = `${rect.left + scrollLeft}px`;
        
        // Ensure dropdown doesn't go off-screen
        const dropdownRect = this.dropdown.getBoundingClientRect();
        if (dropdownRect.right > window.innerWidth) {
            this.dropdown.style.left = `${window.innerWidth - dropdownRect.width - 10}px`;
        }
        if (dropdownRect.bottom > window.innerHeight) {
            this.dropdown.style.top = `${rect.top + scrollTop - dropdownRect.height}px`;
        }
    }

    _insertMention(item) {
        if (!this.currentMentionRange) return;
        
        const span = document.createElement('span');
        span.dataset.storyObjectId = item.id;
        span.textContent = '@' + item.name;
        span.contentEditable = 'false';
        span.classList.add('mention');
        
        // Delete the @ and search text
        this.currentMentionRange.deleteContents();
        this.currentMentionRange.insertNode(span);
        
        // Position cursor after the mention
        const range = document.createRange();
        range.setStartAfter(span);
        range.setEndAfter(span);
        
        const sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
        
        this.dropdown.style.display = 'none';
        this.currentMentionRange = null;
        this.currentMentionText = '';
        
        // Cancel any pending searches
        this._cancelSearch();
        
        this._syncContent();
    }

    _onInput() {
        this._syncContent();
    }

    _onSubmit(event) {
        this._syncContent();

        // Check if the textarea is required and empty
        if (this.textarea.hasAttribute('required') && this.textarea.value.trim() === '') {
            event.preventDefault();
            this.editor.focus();
            return false;
        }
    }

    _syncContent() {
        this.textarea.value = this.editor.innerHTML;
    }
}