import { Controller } from '@hotwired/stimulus';
import Sortable from '../vendor/sortable.esm.js';
// import {values} from "../../public/assets/vendor/lodash-es/lodash-es.index-BAEpLOo";

export default class extends Controller {
    static targets = ['column', 'taskList', 'task', 'modalOverlay', 'modalTitle', 'modalBody', 'modalFooter'];
    static values = {
        updateUrl: String,
        assignUrl: String,
        createUrl: String,
        detailUrl: String,
        editUrl: String,
        deleteUrl: String
    };

    connect() {
        console.log('Kanban controller connected');
        
        this.taskListTargets.forEach(taskList => {
            Sortable.create(taskList, {
                group: 'kanban',
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                onStart: event => this._onStart(event),
                onEnd: event => this._onEnd(event),
                onMove: event => this._onMove(event)
            });
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (event) => {
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.assignment-dropdown.show').forEach(d => {
                    d.classList.remove('show');
                });
            }
        });
    }

    // Modal Methods
    showCreateModal() {
        console.log('showCreateModal called');
        
        if (!this.hasModalTitleTarget || !this.hasModalOverlayTarget) {
            console.error('Modal targets not found');
            return;
        }
        
        this.modalTitleTarget.textContent = 'Create New Task';
        this._showModal();
        this._loadContent(this.createUrlValue);
    }

    showTaskDetail(event) {
        if (!this.hasModalTitleTarget || !this.hasModalOverlayTarget) {
            console.error('Modal targets not found');
            return;
        }
        
        const taskId = event.target.closest('[data-task-id]').dataset.taskId;
        this.modalTitleTarget.textContent = 'Task Details';
        this._showModal();
        this._loadContent(this.detailUrlValue.replace('TASK_ID', taskId));
    }

    editTask(event) {
        if (!this.hasModalTitleTarget || !this.hasModalBodyTarget) {
            console.error('Modal targets not found');
            return;
        }

        const taskId = event.target.dataset.taskId;
        this.modalTitleTarget.textContent = 'Edit Task';

        // Show loader while loading content
        this.modalBodyTarget.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';

        this._loadContent(this.editUrlValue.replace('TASK_ID', taskId));
    }

    deleteTask(event) {
        const taskId = event.target.dataset.taskId;
        
        if (confirm('Are you sure you want to delete this task?')) {
            fetch(this.deleteUrlValue.replace('TASK_ID', taskId), {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    this.closeModal();
                    this._showSuccessMessage('Task deleted successfully');
                    // Remove task from DOM
                    const taskElement = document.querySelector(`[data-task-id="${taskId}"]`);
                    if (taskElement) {
                        taskElement.remove();
                    }
                } else {
                    this._showErrorMessage('Failed to delete task');
                }
            })
            .catch(error => {
                console.error('Error deleting task:', error);
                this._showErrorMessage('Network error occurred');
            });
        }
    }

    closeModal() {
        if (!this.hasModalOverlayTarget) {
            console.error('modalOverlay target not found');
            return;
        }
        
        this.modalOverlayTarget.classList.remove('show');
        
        setTimeout(() => {
            if (this.hasModalBodyTarget) {
                this.modalBodyTarget.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
            }
            if (this.hasModalFooterTarget) {
                this.modalFooterTarget.innerHTML = '';
            }
        }, 300);
    }

    stopPropagation(event) {
        event.stopPropagation();
    }

    handleFormSubmit(event) {
        event.preventDefault();
        const form = event.target;
        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return response.json();
            } else {
                // If not JSON, it's likely an error response with HTML
                return response.text().then(text => ({
                    success: false,
                    html: text
                }));
            }
        })
        .then(data => {
            if (data.success) {
                this.closeModal();
                this._showSuccessMessage(data.message);
                // Reload page to show changes
                window.location.reload();
            } else {
                if (this.hasModalBodyTarget) {
                    this.modalBodyTarget.innerHTML = data.html;
                }
            }
        })
        .catch(error => {
            console.error('Error submitting form:', error);
            this._showErrorMessage('Network error occurred');
        });
    }

    // Assignment Methods
    toggleAssignmentDropdown(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const taskId = event.target.dataset.taskId;
        const dropdown = document.querySelector(`.assignment-dropdown[data-task-id="${taskId}"]`);
        
        if (dropdown) {
            dropdown.classList.toggle('show');

            // Close other dropdowns
            document.querySelectorAll('.assignment-dropdown.show').forEach(d => {
                if (d !== dropdown) d.classList.remove('show');
            });
        }
    }

    assignTask(event) {
        event.preventDefault();
        event.stopPropagation();
        
        const taskId = event.target.dataset.taskId;
        const participantId = event.target.dataset.participantId;
        
        const assignUrl = this.assignUrlValue.replace('TASK_ID', taskId);

        fetch(assignUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ participantId: participantId || null })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                this._showSuccessMessage('Task assigned successfully');
                // Update the dropdown button text
                const button = document.querySelector(`button[data-task-id="${taskId}"]`);
                if (button) {
                    button.textContent = event.target.textContent;
                }
            } else {
                this._showErrorMessage('Failed to assign task');
            }
        })
        .catch(error => {
            console.error('Error assigning task:', error);
            this._showErrorMessage('Network error occurred');
        });

        // Close dropdown
        document.querySelectorAll('.assignment-dropdown.show').forEach(d => {
            d.classList.remove('show');
        });
    }

    // Private Methods
    _showModal() {
        if (this.hasModalOverlayTarget) {
            this.modalOverlayTarget.classList.add('show');
        }
    }

    _loadContent(url) {
        console.log('Loading content from:', url);
        
        if (!this.hasModalBodyTarget) {
            console.error('modalBody target not found');
            return;
        }
        
        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            this.modalBodyTarget.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading content:', error);
            this.modalBodyTarget.innerHTML = '<p class="text-danger">Error loading content</p>';
        });
    }

    // Drag and Drop Methods
    _onStart(event) {
        event.item.classList.add('dragging');
        this.taskListTargets.forEach(list => {
            list.parentElement.classList.add('drop-zone-active');
        });
    }

    _onEnd(event) {
        event.item.classList.remove('dragging');
        this.taskListTargets.forEach(list => {
            list.parentElement.classList.remove('drop-zone-active');
        });

        if (event.from !== event.to || event.oldIndex !== event.newIndex) {
            this._updateTask(event);
        }
    }

    _onMove(event) {
        return true;
    }

    _updateTask(event) {
        const taskId = event.item.dataset.taskId;
        const newStatus = event.to.closest('[data-status]').dataset.status;
        const newPosition = event.newIndex;

        event.item.style.opacity = '0.6';

        const updateUrl = this.updateUrlValue.replace('TASK_ID', taskId);

        fetch(updateUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                status: newStatus,
                position: newPosition
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'ok') {
                event.item.style.opacity = '1';
                this._showSuccessMessage('Task updated successfully');
                this._updateTaskDisplay(event.item, data.task);
            } else {
                this._revertMove(event);
                this._showErrorMessage('Failed to update task');
            }
        })
        .catch(error => {
            console.error('Error updating task:', error);
            this._revertMove(event);
            this._showErrorMessage('Network error occurred');
        });
    }

    _updateTaskDisplay(taskElement, taskData) {
        const titleElement = taskElement.querySelector('.task-title');
        if (titleElement) {
            titleElement.textContent = taskData.title;
        }

        const assigneeButton = taskElement.querySelector('.dropdown-toggle');
        if (assigneeButton && taskData.assignedTo) {
            assigneeButton.textContent = taskData.assignedTo.name;
        }
    }

    _revertMove(event) {
        if (event.from !== event.to) {
            event.from.insertBefore(event.item, event.from.children[event.oldIndex]);
        } else {
            const items = Array.from(event.to.children);
            event.to.insertBefore(event.item, items[event.oldIndex]);
        }
        event.item.style.opacity = '1';
    }

    _showSuccessMessage(message) {
        this._showNotification(message, 'success');
    }

    _showErrorMessage(message) {
        this._showNotification(message, 'error');
    }

    _showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }
}