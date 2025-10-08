import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import Sortable from 'sortablejs';

export default class extends Controller {
    connect() {
        const container = document.getElementById('character-choices-container');
        if (container) {
            this.sortable = Sortable.create(container, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                // Prevent DOM manipulation during drag
                fallbackOnBody: false,
                swapThreshold: 0.65,
                invertSwap: false,
                // Disable some features that cause DOM changes during drag
                removeCloneOnHide: false,
                onStart: () => {
                    // Temporarily disable Stimulus MutationObserver
                    this.disableStimulus();
                },
                onEnd: (evt) => {
                    // Re-enable Stimulus MutationObserver
                    this.enableStimulus();
                    this.updatePriorities();
                }
            });
        }
    }

    disconnect() {
        if (this.sortable) {
            this.sortable.destroy();
        }
    }

    disableStimulus() {
        // Temporarily stop Stimulus from observing DOM changes
        if (this.application && this.application.router) {
            this.application.router.stop();
        }
    }

    enableStimulus() {
        // Re-enable Stimulus DOM observation
        if (this.application && this.application.router) {
            setTimeout(() => {
                this.application.router.start();
            }, 100);
        }
    }

    updatePriorities() {
        $('#character-choices-container .character-choice-item').each(function(index) {
            const priority = index + 1;
            
            // Update visual priority badge
            $(this).find('.priority-badge').text(priority);
            
            // Update hidden priority field
            $(this).find('input[name$="[priority]"]').val(priority);
            
            // Update data attribute
            $(this).attr('data-priority', priority);
            
            // Update header text
            $(this).find('.choice-header h6').text('Character Choice ' + priority);
        });
    }
}