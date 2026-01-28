import { Controller } from '@hotwired/stimulus';

/**
 * Speech-to-Text Controller
 *
 * Uses the Web Speech API to transcribe voice input and insert it into a target element.
 * Supports both regular textareas and Quill WYSIWYG editors.
 *
 * Usage:
 *   <div data-controller="speech-to-text"
 *        data-speech-to-text-target-value="#my-textarea"
 *        data-speech-to-text-language-value="en-US">
 *       <button data-action="speech-to-text#toggle" data-speech-to-text-target="button">
 *           <i class="bi bi-mic"></i>
 *       </button>
 *       <select data-action="speech-to-text#changeLanguage" data-speech-to-text-target="languageSelect">
 *           <option value="en-US">English</option>
 *           <option value="pl-PL">Polski</option>
 *       </select>
 *   </div>
 */
export default class extends Controller {
    static targets = ['button', 'languageSelect', 'status'];

    static values = {
        target: String,           // CSS selector for target input/textarea
        language: { type: String, default: 'en-US' },
        continuous: { type: Boolean, default: true },
        interimResults: { type: Boolean, default: true },
    };

    connect() {
        this.isListening = false;
        this.recognition = null;
        this.finalTranscript = '';

        // Check browser support
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) {
            this.handleUnsupported();
            return;
        }

        this.SpeechRecognition = SpeechRecognition;
        this.initRecognition();

        // Set initial language in select if present
        if (this.hasLanguageSelectTarget) {
            this.languageSelectTarget.value = this.languageValue;
        }
    }

    disconnect() {
        this.stop();
    }

    initRecognition() {
        this.recognition = new this.SpeechRecognition();
        this.recognition.continuous = this.continuousValue;
        this.recognition.interimResults = this.interimResultsValue;
        this.recognition.lang = this.languageValue;

        this.recognition.onresult = (event) => this.handleResult(event);
        this.recognition.onerror = (event) => this.handleError(event);
        this.recognition.onend = () => this.handleEnd();
        this.recognition.onstart = () => this.handleStart();
    }

    toggle() {
        if (this.isListening) {
            this.stop();
        } else {
            this.start();
        }
    }

    start() {
        if (!this.recognition) {
            return;
        }

        this.finalTranscript = '';

        try {
            this.recognition.start();
        } catch (e) {
            // Recognition might already be running
            console.warn('Speech recognition start error:', e);
        }
    }

    stop() {
        if (this.recognition && this.isListening) {
            this.recognition.stop();
        }
    }

    changeLanguage(event) {
        const newLanguage = event.target.value;
        this.languageValue = newLanguage;

        // Restart recognition with new language if currently listening
        const wasListening = this.isListening;
        if (wasListening) {
            this.stop();
        }

        this.initRecognition();

        if (wasListening) {
            // Small delay to allow the previous recognition to fully stop
            setTimeout(() => this.start(), 100);
        }
    }

    handleResult(event) {
        let interimTranscript = '';

        for (let i = event.resultIndex; i < event.results.length; i++) {
            const transcript = event.results[i][0].transcript;

            if (event.results[i].isFinal) {
                this.finalTranscript += transcript;
                this.insertText(transcript);
            } else {
                interimTranscript += transcript;
            }
        }

        // Update status with interim results
        if (this.hasStatusTarget && interimTranscript) {
            this.statusTarget.textContent = interimTranscript;
            this.statusTarget.classList.remove('d-none');
        }
    }

    handleError(event) {
        console.error('Speech recognition error:', event.error);

        let message = '';
        switch (event.error) {
            case 'not-allowed':
                message = 'Microphone access denied. Please allow microphone access.';
                break;
            case 'no-speech':
                message = 'No speech detected. Try again.';
                break;
            case 'network':
                message = 'Network error. Check your connection.';
                break;
            default:
                message = `Error: ${event.error}`;
        }

        this.showStatus(message, 'error');
        this.updateButtonState(false);
    }

    handleEnd() {
        this.isListening = false;
        this.updateButtonState(false);

        // Clear interim status
        if (this.hasStatusTarget) {
            setTimeout(() => {
                this.statusTarget.classList.add('d-none');
                this.statusTarget.textContent = '';
            }, 1000);
        }
    }

    handleStart() {
        this.isListening = true;
        this.updateButtonState(true);
        this.showStatus('Listening...', 'listening');
    }

    handleUnsupported() {
        if (this.hasButtonTarget) {
            this.buttonTarget.disabled = true;
            this.buttonTarget.title = 'Speech recognition not supported in this browser. Try Chrome or Edge.';
        }
        console.warn('Speech Recognition API not supported');
    }

    updateButtonState(isListening) {
        if (!this.hasButtonTarget) return;

        const button = this.buttonTarget;
        const icon = button.querySelector('i');

        if (isListening) {
            button.classList.add('btn-danger', 'recording');
            button.classList.remove('btn-outline-secondary');
            if (icon) {
                icon.classList.remove('bi-mic');
                icon.classList.add('bi-mic-fill');
            }
            button.setAttribute('aria-pressed', 'true');
        } else {
            button.classList.remove('btn-danger', 'recording');
            button.classList.add('btn-outline-secondary');
            if (icon) {
                icon.classList.remove('bi-mic-fill');
                icon.classList.add('bi-mic');
            }
            button.setAttribute('aria-pressed', 'false');
        }
    }

    showStatus(message, type) {
        if (!this.hasStatusTarget) return;

        this.statusTarget.textContent = message;
        this.statusTarget.classList.remove('d-none', 'text-danger', 'text-muted');

        if (type === 'error') {
            this.statusTarget.classList.add('text-danger');
        } else {
            this.statusTarget.classList.add('text-muted');
        }
    }

    insertText(text) {
        const targetSelector = this.targetValue;
        if (!targetSelector) return;

        const targetElement = document.querySelector(targetSelector);
        if (!targetElement) {
            console.warn('Speech-to-text target not found:', targetSelector);
            return;
        }

        // Check if target has a Quill editor (look for the wrapper)
        const quillWrapper = targetElement.previousElementSibling;
        const quillEditor = quillWrapper?.querySelector('.ql-editor');

        if (quillEditor) {
            // Insert into Quill editor
            this.insertIntoQuill(quillWrapper, text);
        } else if (targetElement.tagName === 'TEXTAREA' || targetElement.tagName === 'INPUT') {
            // Insert into regular textarea/input
            this.insertIntoTextarea(targetElement, text);
        } else {
            // Fallback: set innerText
            targetElement.innerText += text;
        }
    }

    insertIntoQuill(wrapper, text) {
        // Find the Quill instance - it's stored on the controller
        // We need to find the textarea and get its controller
        const textarea = wrapper.nextElementSibling;
        if (!textarea) return;

        // Try to get the Quill instance from Stimulus controller
        const controller = this.application.getControllerForElementAndIdentifier(textarea, 'wysiwyg');

        if (controller && controller.quill) {
            const quill = controller.quill;
            const length = quill.getLength();
            // Insert at the end, before the trailing newline
            const insertPosition = length > 0 ? length - 1 : 0;

            // Add a space before if there's existing content
            const existingText = quill.getText();
            const needsSpace = existingText.trim().length > 0 && !existingText.endsWith(' ') && !existingText.endsWith('\n');
            const textToInsert = (needsSpace ? ' ' : '') + text;

            quill.insertText(insertPosition, textToInsert);

            // Move cursor to end
            quill.setSelection(insertPosition + textToInsert.length);
        } else {
            // Fallback: directly manipulate the editor div
            const editor = wrapper.querySelector('.ql-editor');
            if (editor) {
                // Check if we need a space
                const existingText = editor.innerText;
                const needsSpace = existingText.trim().length > 0 && !existingText.endsWith(' ') && !existingText.endsWith('\n');

                // Create a text node and append
                const textNode = document.createTextNode((needsSpace ? ' ' : '') + text);
                const lastParagraph = editor.querySelector('p:last-child') || editor;
                lastParagraph.appendChild(textNode);

                // Trigger input event to sync with textarea
                editor.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }
    }

    insertIntoTextarea(textarea, text) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const currentValue = textarea.value;

        // Check if we need a space
        const needsSpace = currentValue.length > 0
            && start === currentValue.length
            && !currentValue.endsWith(' ')
            && !currentValue.endsWith('\n');

        const textToInsert = (needsSpace ? ' ' : '') + text;

        // Insert at cursor position
        textarea.value = currentValue.substring(0, start) + textToInsert + currentValue.substring(end);

        // Move cursor to end of inserted text
        const newPosition = start + textToInsert.length;
        textarea.setSelectionRange(newPosition, newPosition);

        // Trigger input event for any listeners
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        textarea.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
