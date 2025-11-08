import { Controller } from '@hotwired/stimulus';

/**
 * Real-time comments chat controller
 * Uses AJAX polling to fetch new comments and update the UI in real-time
 */
export default class extends Controller {
    static targets = [
        'commentsList',
        'messageInput',
        'sendButton',
        'typingIndicator',
        'countBadge',
        'unresolvedBadge',
        'chatForm',
    ];

    static values = {
        apiUrl: String,
        postUrl: String,
        pollInterval: { type: Number, default: 3000 }, // Poll every 3 seconds
        storyObjectId: Number,
        currentUserId: Number,
    };

    connect() {
        console.log('Real-time chat controller connected');
        this.lastCommentId = 0;
        this.isTyping = false;
        this.typingTimeout = null;
        this.pollTimer = null;

        // Initialize last comment ID from existing comments
        this.initializeLastCommentId();

        // Start polling for new comments
        this.startPolling();

        // Add beforeunload to stop polling
        this.boundStopPolling = this.stopPolling.bind(this);
        window.addEventListener('beforeunload', this.boundStopPolling);
    }

    disconnect() {
        this.stopPolling();
        window.removeEventListener('beforeunload', this.boundStopPolling);
    }

    /**
     * Initialize lastCommentId from existing comments in the DOM
     */
    initializeLastCommentId() {
        const commentElements = this.element.querySelectorAll('[data-comment-id]');
        commentElements.forEach(el => {
            const id = parseInt(el.dataset.commentId);
            if (id > this.lastCommentId) {
                this.lastCommentId = id;
            }
        });
        console.log('Initial lastCommentId:', this.lastCommentId);
    }

    /**
     * Start polling for new comments
     */
    startPolling() {
        if (this.pollTimer) {
            return; // Already polling
        }

        console.log('Starting real-time polling...');
        this.poll(); // Poll immediately
        this.pollTimer = setInterval(() => this.poll(), this.pollIntervalValue);
    }

    /**
     * Stop polling
     */
    stopPolling() {
        if (this.pollTimer) {
            console.log('Stopping real-time polling');
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    }

    /**
     * Poll for new comments
     */
    async poll() {
        try {
            const url = new URL(this.apiUrlValue, window.location.origin);
            url.searchParams.set('lastCommentId', this.lastCommentId);

            const response = await fetch(url.toString(), {
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                console.error('Failed to fetch comments:', response.status);
                return;
            }

            const data = await response.json();

            // Update badges
            if (this.hasCountBadgeTarget) {
                this.countBadgeTarget.textContent = data.count;
            }
            if (this.hasUnresolvedBadgeTarget) {
                this.unresolvedBadgeTarget.textContent = data.unresolvedCount;
            }

            // Add new comments to the UI
            if (data.comments && data.comments.length > 0) {
                console.log(`Received ${data.comments.length} new comments`);
                data.comments.forEach(comment => {
                    this.addComment(comment);
                });

                // Update lastCommentId
                this.lastCommentId = data.lastCommentId;

                // Scroll to bottom if new messages
                this.scrollToBottom();

                // Play notification sound (optional)
                this.playNotificationSound();
            }

        } catch (error) {
            console.error('Error polling for comments:', error);
        }
    }

    /**
     * Add a comment to the UI
     */
    addComment(comment) {
        // Check if comment already exists
        const existing = this.element.querySelector(`[data-comment-id="${comment.id}"]`);
        if (existing) {
            return; // Already displayed
        }

        const isReply = !comment.isTopLevel;

        if (isReply) {
            this.addReply(comment);
        } else {
            this.addTopLevelComment(comment);
        }

        // Animate the new comment
        const element = this.element.querySelector(`[data-comment-id="${comment.id}"]`);
        if (element) {
            element.classList.add('new-comment-animation');
            setTimeout(() => {
                element.classList.remove('new-comment-animation');
            }, 2000);
        }
    }

    /**
     * Add a top-level comment to the list
     */
    addTopLevelComment(comment) {
        const html = this.renderComment(comment);

        if (this.hasCommentsListTarget) {
            // Check if there's an empty state message
            const emptyState = this.commentsListTarget.querySelector('.text-center.py-5');
            if (emptyState) {
                emptyState.remove();
            }

            this.commentsListTarget.insertAdjacentHTML('beforeend', html);
        }
    }

    /**
     * Add a reply to an existing comment thread
     */
    addReply(comment) {
        const parentElement = this.element.querySelector(`[data-comment-id="${comment.parentId}"]`);
        if (!parentElement) {
            console.warn('Parent comment not found for reply:', comment.parentId);
            return;
        }

        // Find or create replies container
        const threadContainer = parentElement.closest('.comment-thread');
        let repliesContainer = threadContainer.querySelector('.replies-container');

        if (!repliesContainer) {
            repliesContainer = document.createElement('div');
            repliesContainer.className = 'ms-4 mt-3 replies-container';
            threadContainer.querySelector('.flex-grow-1').appendChild(repliesContainer);
        }

        const html = this.renderReply(comment);
        repliesContainer.insertAdjacentHTML('beforeend', html);
    }

    /**
     * Render a top-level comment as HTML
     */
    renderComment(comment) {
        const resolvedBadge = comment.isResolved
            ? '<span class="badge bg-success ms-2">Resolved</span>'
            : '';

        const editedBadge = comment.isEdited
            ? '<small class="text-muted">(edited)</small>'
            : '';

        return `
            <div class="comment-thread mb-4 pb-4 border-bottom" data-comment-id="${comment.id}">
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <div class="avatar-circle bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 40px; height: 40px;">
                            ${comment.authorInitial}
                        </div>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <div class="card ${comment.isResolved ? 'bg-light' : ''}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>${this.escapeHtml(comment.authorName)}</strong>
                                        <small class="text-muted ms-2">
                                            ${comment.createdAt} ${editedBadge}
                                        </small>
                                        ${resolvedBadge}
                                    </div>
                                </div>
                                <div class="comment-content">
                                    ${this.escapeHtml(comment.content).replace(/\n/g, '<br>')}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Render a reply as HTML
     */
    renderReply(comment) {
        return `
            <div class="d-flex mb-3" data-comment-id="${comment.id}">
                <div class="flex-shrink-0">
                    <div class="avatar-circle bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                         style="width: 32px; height: 32px; font-size: 0.875rem;">
                        ${comment.authorInitial}
                    </div>
                </div>
                <div class="flex-grow-1 ms-3">
                    <div class="card border">
                        <div class="card-body py-2 px-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <strong class="small">${this.escapeHtml(comment.authorName)}</strong>
                                    <small class="text-muted ms-2">
                                        ${comment.createdAt}
                                    </small>
                                </div>
                            </div>
                            <div class="comment-content small">
                                ${this.escapeHtml(comment.content).replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Send a new message
     */
    async sendMessage(event) {
        event.preventDefault();

        if (!this.hasMessageInputTarget) {
            return;
        }

        const content = this.messageInputTarget.value.trim();
        if (!content) {
            return;
        }

        // Disable send button
        if (this.hasSendButtonTarget) {
            this.sendButtonTarget.disabled = true;
        }

        try {
            const formData = new FormData();
            formData.append('content', content);

            const response = await fetch(this.postUrlValue, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!response.ok) {
                throw new Error('Failed to send message');
            }

            const data = await response.json();

            if (data.success) {
                // Clear input
                this.messageInputTarget.value = '';

                // Add comment to UI immediately
                this.addComment(data.comment);
                this.lastCommentId = data.comment.id;

                // Scroll to bottom
                this.scrollToBottom();
            }

        } catch (error) {
            console.error('Error sending message:', error);
            alert('Failed to send message. Please try again.');
        } finally {
            // Re-enable send button
            if (this.hasSendButtonTarget) {
                this.sendButtonTarget.disabled = false;
            }
        }
    }

    /**
     * Handle typing indicator
     */
    handleTyping() {
        if (this.hasTypingIndicatorTarget) {
            this.typingIndicatorTarget.style.display = 'block';
        }

        // Clear previous timeout
        if (this.typingTimeout) {
            clearTimeout(this.typingTimeout);
        }

        // Hide typing indicator after 3 seconds of inactivity
        this.typingTimeout = setTimeout(() => {
            if (this.hasTypingIndicatorTarget) {
                this.typingIndicatorTarget.style.display = 'none';
            }
        }, 3000);
    }

    /**
     * Scroll to bottom of comments list
     */
    scrollToBottom() {
        if (this.hasCommentsListTarget) {
            const lastComment = this.commentsListTarget.lastElementChild;
            if (lastComment) {
                lastComment.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }

    /**
     * Play notification sound for new messages
     */
    playNotificationSound() {
        // Optional: Play a subtle notification sound
        // You can add a sound file and uncomment this
        // const audio = new Audio('/sounds/notification.mp3');
        // audio.volume = 0.3;
        // audio.play().catch(e => console.log('Could not play sound:', e));
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Allow sending message with Enter key (Shift+Enter for new line)
     */
    handleKeyDown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            this.sendMessage(event);
        }
    }
}
