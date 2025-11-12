import { Controller } from '@hotwired/stimulus';

/**
 * Comments controller for Google Docs-like inline commenting with real-time updates
 * 
 * Features:
 * - Inline comment posting without page reload
 * - Reply to comments inline
 * - Long-polling for real-time updates
 * - Resolve/unresolve comments via AJAX
 * - Optimistic UI updates
 */
export default class extends Controller {
    static targets = ['newCommentInput', 'commentsList', 'emptyState'];
    
    static values = {
        larp: String,
        storyObject: String,
        storyObjectType: String,
        pollInterval: { type: Number, default: 5000 }, // 5 seconds
        showResolved: { type: Boolean, default: false }
    };

    connect() {
        console.log('Comments controller connected');
        this.pollTimer = null;
        this.lastCommentId = this.getLastCommentId();
        this.lastPollTimestamp = null;
        this.startPolling();
    }

    disconnect() {
        this.stopPolling();
    }

    /**
     * Start long-polling for new comments
     */
    startPolling() {
        if (this.pollTimer) return;
        
        this.pollTimer = setInterval(() => {
            this.fetchNewComments();
        }, this.pollIntervalValue);
    }

    /**
     * Stop long-polling
     */
    stopPolling() {
        if (this.pollTimer) {
            clearInterval(this.pollTimer);
            this.pollTimer = null;
        }
    }

    /**
     * Get the last comment ID from the DOM
     */
    getLastCommentId() {
        const comments = this.element.querySelectorAll('[data-comment-id]');
        if (comments.length === 0) return null;
        
        let maxId = null;
        comments.forEach(comment => {
            const id = comment.dataset.commentId;
            if (!maxId || id > maxId) {
                maxId = id;
            }
        });
        
        return maxId;
    }

    /**
     * Fetch new comments from the server
     */
    async fetchNewComments() {
        try {
            const url = `/larp/${this.larpValue}/story/${this.storyObjectValue}/api/comments`;
            const params = new URLSearchParams();
            
            if (this.lastCommentId) {
                params.append('lastCommentId', this.lastCommentId);
            }
            
            if (this.lastPollTimestamp) {
                params.append('since', this.lastPollTimestamp);
            }
            
            const response = await fetch(`${url}?${params}`);
            
            if (!response.ok) {
                throw new Error('Failed to fetch comments');
            }
            
            const data = await response.json();
            
            // Update last comment ID and timestamp
            if (data.lastCommentId) {
                this.lastCommentId = data.lastCommentId;
            }
            
            if (data.timestamp) {
                this.lastPollTimestamp = data.timestamp;
            }
            
            // Add new comments to the DOM
            if (data.comments && data.comments.length > 0) {
                this.addNewComments(data.comments);
            }
            
        } catch (error) {
            console.error('Error fetching comments:', error);
        }
    }

    /**
     * Add new comments to the DOM
     */
    addNewComments(comments) {
        // Hide empty state if visible
        if (this.hasEmptyStateTarget) {
            this.emptyStateTarget.classList.add('d-none');
        }
        
        comments.forEach(comment => {
            // Check if comment already exists
            const existing = this.element.querySelector(`[data-comment-id="${comment.id}"]`);
            if (existing) return;
            
            // Add comment to appropriate location
            if (comment.isTopLevel) {
                this.appendTopLevelComment(comment);
            } else {
                this.appendReply(comment);
            }
        });
    }

    /**
     * Append a top-level comment
     */
    appendTopLevelComment(comment) {
        const html = this.buildCommentHtml(comment);
        this.commentsListTarget.insertAdjacentHTML('beforeend', html);
        
        // Scroll to new comment
        const newComment = this.element.querySelector(`[data-comment-id="${comment.id}"]`);
        if (newComment) {
            newComment.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    /**
     * Append a reply to a parent comment
     */
    appendReply(comment) {
        const parentThread = this.element.querySelector(`[data-comment-id="${comment.parentId}"]`).closest('.comment-thread');
        if (!parentThread) return;
        
        let repliesContainer = parentThread.querySelector('.comment-replies');
        if (!repliesContainer) {
            // Create replies container if it doesn't exist
            const cardBody = parentThread.querySelector('.card-body');
            repliesContainer = document.createElement('div');
            repliesContainer.className = 'comment-replies border-top';
            cardBody.parentElement.appendChild(repliesContainer);
        }
        
        const html = this.buildReplyHtml(comment);
        repliesContainer.insertAdjacentHTML('beforeend', html);
    }

    /**
     * Build HTML for a top-level comment
     */
    buildCommentHtml(comment) {
        const resolvedClass = comment.isResolved ? 'comment-resolved' : '';
        const resolvedBadge = comment.isResolved ? `<span class="badge bg-success"><i class="bi bi-check-circle"></i> Resolved</span>` : '';
        
        return `
            <div class="comment-thread mb-4 ${resolvedClass}" data-comment-id="${comment.id}">
                <div class="card shadow-sm">
                    <div class="card-body p-3">
                        <div class="d-flex gap-3">
                            <div class="flex-shrink-0">
                                <div class="avatar-circle bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                     style="width: 40px; height: 40px; font-size: 1.1rem;">
                                    ${comment.authorInitial}
                                </div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong class="text-dark">${comment.authorName}</strong>
                                        <small class="text-muted ms-2">
                                            <i class="bi bi-clock"></i> ${comment.createdAt}
                                        </small>
                                    </div>
                                    ${resolvedBadge}
                                </div>
                                <div class="comment-content mb-2">
                                    ${this.nl2br(comment.content)}
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" 
                                            class="btn btn-sm btn-link text-decoration-none p-0"
                                            data-action="click->comments#toggleReplyForm"
                                            data-comment-id="${comment.id}">
                                        <i class="bi bi-reply"></i> Reply
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-link text-decoration-none p-0"
                                            data-action="click->comments#toggleResolve"
                                            data-comment-id="${comment.id}"
                                            data-is-resolved="${comment.isResolved}">
                                        <i class="bi bi-${comment.isResolved ? 'arrow-counterclockwise' : 'check-circle'}"></i>
                                        ${comment.isResolved ? 'Unresolve' : 'Resolve'}
                                    </button>
                                </div>
                                <div class="reply-form mt-3 d-none" data-reply-form-for="${comment.id}">
                                    <form data-action="submit->comments#postReply" data-parent-id="${comment.id}" class="d-flex gap-2">
                                        <div class="flex-shrink-0">
                                            <div class="avatar-circle bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                                 style="width: 32px; height: 32px;">
                                                ${comment.authorInitial}
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <textarea class="form-control form-control-sm" rows="2" placeholder="Write a reply..." required></textarea>
                                            <div class="d-flex gap-2 mt-2">
                                                <button type="submit" class="btn btn-primary btn-sm">Reply</button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                                        data-action="click->comments#toggleReplyForm"
                                                        data-comment-id="${comment.id}">Cancel</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Build HTML for a reply
     */
    buildReplyHtml(comment) {
        return `
            <div class="reply-item p-3 border-bottom" data-comment-id="${comment.id}">
                <div class="d-flex gap-2">
                    <div class="flex-shrink-0">
                        <div class="avatar-circle bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                             style="width: 32px; height: 32px;">
                            ${comment.authorInitial}
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <strong class="text-dark">${comment.authorName}</strong>
                                <small class="text-muted ms-2">
                                    <i class="bi bi-clock"></i> ${comment.createdAt}
                                </small>
                            </div>
                        </div>
                        <div class="comment-content small">
                            ${this.nl2br(comment.content)}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Post a new top-level comment
     */
    async postComment(event) {
        event.preventDefault();
        
        const content = this.newCommentInputTarget.value.trim();
        if (!content) return;
        
        try {
            const url = `/larp/${this.larpValue}/story/${this.storyObjectValue}/api/comments`;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    content: content
                })
            });
            
            if (!response.ok) {
                throw new Error('Failed to post comment');
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Clear input
                this.newCommentInputTarget.value = '';
                
                // Add comment to DOM
                this.addNewComments([data.comment]);
                
                // Show success message
                this.showToast('Comment posted successfully', 'success');
            }
            
        } catch (error) {
            console.error('Error posting comment:', error);
            this.showToast('Failed to post comment', 'danger');
        }
    }

    /**
     * Post a reply to a comment
     */
    async postReply(event) {
        event.preventDefault();
        
        const form = event.target;
        const parentId = form.dataset.parentId;
        const textarea = form.querySelector('textarea');
        const content = textarea.value.trim();
        
        if (!content) return;
        
        try {
            const url = `/larp/${this.larpValue}/story/${this.storyObjectValue}/api/comments`;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    content: content,
                    parentId: parentId
                })
            });
            
            if (!response.ok) {
                throw new Error('Failed to post reply');
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Clear input and hide form
                textarea.value = '';
                this.toggleReplyForm(event);
                
                // Add reply to DOM
                this.addNewComments([data.comment]);
                
                // Show success message
                this.showToast('Reply posted successfully', 'success');
            }
            
        } catch (error) {
            console.error('Error posting reply:', error);
            this.showToast('Failed to post reply', 'danger');
        }
    }

    /**
     * Toggle reply form visibility
     */
    toggleReplyForm(event) {
        const commentId = event.target.closest('[data-comment-id]')?.dataset.commentId || 
                         event.target.dataset.commentId;
        const form = this.element.querySelector(`[data-reply-form-for="${commentId}"]`);
        
        if (form) {
            form.classList.toggle('d-none');
            
            // Focus textarea if showing
            if (!form.classList.contains('d-none')) {
                const textarea = form.querySelector('textarea');
                textarea?.focus();
            }
        }
    }

    /**
     * Toggle comment resolved status
     */
    async toggleResolve(event) {
        event.preventDefault();
        
        const button = event.target.closest('button');
        const commentId = button.dataset.commentId;
        const isResolved = button.dataset.isResolved === 'true';
        
        try {
            const url = `/larp/${this.larpValue}/story/${this.storyObjectValue}/api/comments/${commentId}/resolve`;
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                throw new Error('Failed to toggle resolve status');
            }
            
            const data = await response.json();
            
            if (data.success) {
                // Update UI
                const thread = this.element.querySelector(`[data-comment-id="${commentId}"]`).closest('.comment-thread');
                
                if (data.isResolved) {
                    thread.classList.add('comment-resolved');
                    button.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i> Unresolve';
                    button.dataset.isResolved = 'true';
                    
                    // Add resolved badge
                    const header = thread.querySelector('.d-flex.justify-content-between.align-items-start');
                    if (!header.querySelector('.badge')) {
                        header.insertAdjacentHTML('beforeend', '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Resolved</span>');
                    }
                    
                    // Hide the thread if showResolved is false
                    if (!this.showResolvedValue) {
                        thread.style.display = 'none';
                    }
                } else {
                    thread.classList.remove('comment-resolved');
                    button.innerHTML = '<i class="bi bi-check-circle"></i> Resolve';
                    button.dataset.isResolved = 'false';
                    
                    // Remove resolved badge
                    const badge = thread.querySelector('.badge.bg-success');
                    badge?.remove();
                    
                    // Show the thread (in case it was hidden)
                    thread.style.display = '';
                }
                
                this.showToast(data.message, 'success');
            }
            
        } catch (error) {
            console.error('Error toggling resolve:', error);
            this.showToast('Failed to update comment status', 'danger');
        }
    }

    /**
     * Clear input field
     */
    clearInput() {
        this.newCommentInputTarget.value = '';
        this.newCommentInputTarget.focus();
    }

    /**
     * Convert newlines to <br> tags
     */
    nl2br(str) {
        return str.replace(/\n/g, '<br>');
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Check if Bootstrap toast container exists
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        container.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast, { autohide: true, delay: 3000 });
        bsToast.show();
        
        // Remove toast from DOM after it's hidden
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
}
