import { Controller } from '@hotwired/stimulus';
import $ from 'jquery';
import Sortable from 'sortablejs';

export default class extends Controller {
    static targets = ['questionsContainer', 'addQuestionButton'];

    connect() {
        this.initializeSortable();
        this.initializeQuestionTypeHandlers();
    }

    disconnect() {
        if (this.sortable) {
            this.sortable.destroy();
        }
    }

    initializeSortable() {
        const container = document.getElementById('survey-questions-container');
        if (container) {
            this.sortable = Sortable.create(container, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                fallbackOnBody: false,
                swapThreshold: 0.65,
                invertSwap: false,
                removeCloneOnHide: false,
                onStart: () => {
                    this.disableStimulus();
                },
                onEnd: (evt) => {
                    this.enableStimulus();
                    this.updateOrderPositions();
                }
            });
        }
    }

    initializeQuestionTypeHandlers() {
        // Listen for question type changes to show/hide options
        $(document).on('change', 'select[id$="_questionType"]', (e) => {
            const select = $(e.target);
            const questionCard = select.closest('.question-item');
            const optionsContainer = questionCard.find('.question-options-container');
            const questionType = select.val();

            // Show options container only for single_choice and multiple_choice
            if (questionType === 'single_choice' || questionType === 'multiple_choice') {
                optionsContainer.show();
            } else {
                optionsContainer.hide();
            }
        });
    }

    disableStimulus() {
        if (this.application && this.application.router) {
            this.application.router.stop();
        }
    }

    enableStimulus() {
        if (this.application && this.application.router) {
            setTimeout(() => {
                this.application.router.start();
            }, 100);
        }
    }

    updateOrderPositions() {
        $('#survey-questions-container .question-item').each(function(index) {
            const position = index;

            // Update visual position badge
            $(this).find('.position-badge').text('Question ' + (position + 1));

            // Update hidden orderPosition field
            $(this).find('input[name$="[orderPosition]"]').val(position);

            // Update data attribute
            $(this).attr('data-position', position);
        });

        // Also update option positions within each question
        $('.question-options-container').each(function() {
            $(this).find('.option-item').each(function(index) {
                $(this).find('input[name$="[orderPosition]"]').val(index);
            });
        });
    }

    addQuestion(event) {
        event.preventDefault();

        const container = document.getElementById('survey-questions-container');
        const prototype = container.dataset.prototype;
        const index = container.dataset.index || 0;

        // Replace __name__ placeholder with index
        const newForm = prototype.replace(/__name__/g, index);

        // Increment index
        container.dataset.index = parseInt(index) + 1;

        // Add new question
        $(container).append(newForm);

        // Update order positions
        this.updateOrderPositions();
    }

    removeQuestion(event) {
        event.preventDefault();

        const questionCard = $(event.target).closest('.question-item');

        // Confirm deletion
        if (confirm('Are you sure you want to remove this question?')) {
            questionCard.remove();
            this.updateOrderPositions();
        }
    }

    addOption(event) {
        event.preventDefault();

        const button = $(event.target);
        const questionCard = button.closest('.question-item');
        const optionsContainer = questionCard.find('.question-options-list');
        const prototype = optionsContainer.data('prototype');
        const index = optionsContainer.data('index') || 0;

        // Replace __name__ placeholder
        const newForm = prototype.replace(/__name__/g, index);

        // Increment index
        optionsContainer.data('index', parseInt(index) + 1);

        // Add new option
        optionsContainer.append(newForm);

        // Update order positions
        this.updateOrderPositions();
    }

    removeOption(event) {
        event.preventDefault();

        const optionItem = $(event.target).closest('.option-item');
        optionItem.remove();

        this.updateOrderPositions();
    }
}
