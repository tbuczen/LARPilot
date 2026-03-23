import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'messagesContainer',
        'messageInput',
        'sendButton',
        'typingIndicator',
        'errorDisplay',
        'errorMessage',
        'sourcesPanel',
        'sourcesList',
    ];

    static values = {
        queryUrl: String,
        larpTitle: String,
    };

    connect() {
        this.conversationHistory = [];
        this.isProcessing = false;
        this.scrollToBottom();
    }

    async sendMessage(event) {
        event.preventDefault();

        if (this.isProcessing || !this.hasMessageInputTarget) {
            return;
        }

        const query = this.messageInputTarget.value.trim();
        if (!query) {
            return;
        }

        // Clear input and disable
        this.messageInputTarget.value = '';
        this.isProcessing = true;
        this.sendButtonTarget.disabled = true;
        this.hideError();

        // Append user message
        this.appendMessage('user', query);
        this.conversationHistory.push({ role: 'user', content: query });

        // Show typing indicator
        this.typingIndicatorTarget.style.display = 'flex';
        this.scrollToBottom();

        try {
            const response = await fetch(this.queryUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    query: query,
                    history: this.conversationHistory.slice(0, -1), // exclude current query
                }),
            });

            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                throw new Error(errorData.message || `Request failed (${response.status})`);
            }

            const data = await response.json();

            // Append assistant response
            this.appendMessage('assistant', data.response);
            this.conversationHistory.push({ role: 'assistant', content: data.response });

            // Render sources
            if (data.sources && data.sources.length > 0) {
                this.renderSources(data.sources);
            } else {
                this.sourcesPanelTarget.style.display = 'none';
            }
        } catch (error) {
            this.showError(error.message || 'Failed to get a response. Please try again.');
        } finally {
            this.typingIndicatorTarget.style.display = 'none';
            this.isProcessing = false;
            this.sendButtonTarget.disabled = false;
            this.messageInputTarget.focus();
            this.scrollToBottom();
        }
    }

    appendMessage(role, content) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `ai-chat-message ai-chat-message--${role}`;

        const avatarDiv = document.createElement('div');
        avatarDiv.className = 'ai-chat-avatar';

        if (role === 'user') {
            avatarDiv.innerHTML = '<i class="bi bi-person"></i>';
        } else {
            avatarDiv.innerHTML = '<i class="bi bi-robot"></i>';
        }

        const bubbleDiv = document.createElement('div');
        bubbleDiv.className = 'ai-chat-bubble';

        if (role === 'user') {
            bubbleDiv.textContent = content;
        } else {
            bubbleDiv.innerHTML = this.renderMarkdown(content);
        }

        messageDiv.appendChild(avatarDiv);
        messageDiv.appendChild(bubbleDiv);
        this.messagesContainerTarget.appendChild(messageDiv);
        this.scrollToBottom();
    }

    renderMarkdown(text) {
        // Escape HTML first
        let html = this.escapeHtml(text);

        // Code blocks (``` ... ```)
        html = html.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');

        // Inline code
        html = html.replace(/`([^`]+)`/g, '<code>$1</code>');

        // Bold
        html = html.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');

        // Italic
        html = html.replace(/\*(.+?)\*/g, '<em>$1</em>');

        // Line breaks
        html = html.replace(/\n/g, '<br>');

        return html;
    }

    renderSources(sources) {
        this.sourcesListTarget.innerHTML = '';
        sources.forEach(source => {
            const badge = document.createElement('span');
            badge.className = 'badge bg-secondary me-1 mb-1';
            badge.title = source.preview || '';
            badge.textContent = `${source.type}: ${source.title} (${source.similarity}%)`;
            this.sourcesListTarget.appendChild(badge);
        });
        this.sourcesPanelTarget.style.display = 'block';
    }

    clearHistory() {
        this.conversationHistory = [];

        // Remove all messages except the welcome message
        const messages = this.messagesContainerTarget.querySelectorAll('.ai-chat-message');
        messages.forEach(msg => {
            if (!msg.hasAttribute('data-welcome-message')) {
                msg.remove();
            }
        });

        this.sourcesPanelTarget.style.display = 'none';
        this.hideError();
        this.scrollToBottom();
    }

    handleKeyDown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage(event);
        }
    }

    showError(message) {
        this.errorMessageTarget.textContent = message;
        this.errorDisplayTarget.style.display = 'block';
        this.scrollToBottom();
    }

    hideError() {
        this.errorDisplayTarget.style.display = 'none';
    }

    scrollToBottom() {
        if (this.hasMessagesContainerTarget) {
            this.messagesContainerTarget.scrollTop = this.messagesContainerTarget.scrollHeight;
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
