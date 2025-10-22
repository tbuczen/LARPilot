import { Controller } from '@hotwired/stimulus';
import html2canvas from 'html2canvas';

/*
 * Feedback Widget Controller
 *
 * Provides a floating feedback button that allows users to:
 * - Submit bug reports, feature requests, questions, or general feedback
 * - Take screenshots with annotations
 * - Automatically capture context (URL, user info, browser details, LARP context)
 *
 * Usage:
 * <div data-controller="feedback"
 *      data-feedback-api-url-value="/api/feedback"
 *      data-feedback-user-email-value="{{ app.user ? app.user.email : '' }}"
 *      data-feedback-user-name-value="{{ app.user ? app.user.displayName : '' }}"
 *      data-feedback-user-id-value="{{ app.user ? app.user.id : '' }}">
 * </div>
 */
export default class extends Controller {
    static values = {
        apiUrl: String,
        userEmail: String,
        userName: String,
        userId: String,
    }

    connect() {
        this.createModal();
    }

    disconnect() {
        if (this.modalElement) {
            this.modalElement.remove();
        }
    }

    createModal() {
        // Create modal HTML
        const modalHTML = `
            <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="feedbackModalLabel">
                                <i class="bi bi-chat-left-text me-2"></i>
                                Send Feedback
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="feedbackForm">
                                <!-- Feedback Type -->
                                <div class="mb-3">
                                    <label for="feedbackType" class="form-label">Feedback Type</label>
                                    <select class="form-select" id="feedbackType" required>
                                        <option value="">Select type...</option>
                                        <option value="bug_report">🐛 Bug Report</option>
                                        <option value="feature_request">💡 Feature Request</option>
                                        <option value="question">❓ Question</option>
                                        <option value="general">💬 General Feedback</option>
                                    </select>
                                </div>

                                <!-- Subject -->
                                <div class="mb-3">
                                    <label for="feedbackSubject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="feedbackSubject"
                                           placeholder="Brief description of your feedback" required>
                                </div>

                                <!-- Description -->
                                <div class="mb-3">
                                    <label for="feedbackDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="feedbackDescription" rows="5"
                                              placeholder="Provide detailed information..." required></textarea>
                                </div>

                                <!-- Screenshot Section -->
                                <div class="mb-3">
                                    <label class="form-label">Screenshot (optional)</label>
                                    <div class="d-flex gap-2 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="captureScreenshot">
                                            <i class="bi bi-camera"></i> Capture Screenshot
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm d-none" id="removeScreenshot">
                                            <i class="bi bi-trash"></i> Remove Screenshot
                                        </button>
                                    </div>
                                    <div id="screenshotPreview" class="border rounded p-2 d-none">
                                        <img id="screenshotImage" class="img-fluid" alt="Screenshot preview">
                                    </div>
                                    <input type="hidden" id="screenshotData">
                                </div>

                                <!-- Context Info (collapsed by default) -->
                                <div class="accordion mb-3" id="contextAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header">
                                            <button class="accordion-button collapsed" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#contextCollapse">
                                                <i class="bi bi-info-circle me-2"></i>
                                                Automatically Captured Context
                                            </button>
                                        </h2>
                                        <div id="contextCollapse" class="accordion-collapse collapse"
                                             data-bs-parent="#contextAccordion">
                                            <div class="accordion-body">
                                                <small class="text-muted" id="contextInfo">
                                                    <!-- Context will be populated here -->
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Error Message -->
                                <div id="feedbackError" class="alert alert-danger d-none" role="alert"></div>

                                <!-- Success Message -->
                                <div id="feedbackSuccess" class="alert alert-success d-none" role="alert"></div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" id="submitFeedback">
                                <span id="submitButtonText">
                                    <i class="bi bi-send"></i> Submit Feedback
                                </span>
                                <span id="submitButtonSpinner" class="d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Submitting...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert modal into DOM
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modalElement = document.getElementById('feedbackModal');

        // Attach event listeners
        this.attachEventListeners();

        // Populate context info
        this.populateContextInfo();
    }

    attachEventListeners() {
        // Screenshot capture
        document.getElementById('captureScreenshot').addEventListener('click', () => {
            this.captureScreenshot();
        });

        // Screenshot removal
        document.getElementById('removeScreenshot').addEventListener('click', () => {
            this.removeScreenshot();
        });

        // Form submission
        document.getElementById('submitFeedback').addEventListener('click', () => {
            this.submitFeedback();
        });

        // Reset form when modal is closed
        this.modalElement.addEventListener('hidden.bs.modal', () => {
            this.resetForm();
        });
    }

    async captureScreenshot() {
        const button = document.getElementById('captureScreenshot');
        const originalText = button.innerHTML;

        try {
            // Show loading state
            button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Capturing...';
            button.disabled = true;

            // Hide modal temporarily to capture full page
            const modal = bootstrap.Modal.getInstance(this.modalElement);
            this.modalElement.style.display = 'none';

            // Small delay to let modal fade out
            await new Promise(resolve => setTimeout(resolve, 300));

            // Capture screenshot
            const canvas = await html2canvas(document.body, {
                logging: false,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
            });

            // Show modal again
            this.modalElement.style.display = 'block';

            // Convert to base64
            const screenshotData = canvas.toDataURL('image/png');

            // Store screenshot data
            document.getElementById('screenshotData').value = screenshotData;

            // Show preview
            const previewImg = document.getElementById('screenshotImage');
            previewImg.src = screenshotData;
            document.getElementById('screenshotPreview').classList.remove('d-none');
            document.getElementById('removeScreenshot').classList.remove('d-none');

            // Restore button
            button.innerHTML = '<i class="bi bi-check-circle"></i> Screenshot Captured';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-success');

        } catch (error) {
            console.error('Screenshot capture failed:', error);
            this.showError('Failed to capture screenshot. You can still submit feedback without it.');

            // Restore button
            button.innerHTML = originalText;
            button.disabled = false;

            // Show modal again if hidden
            this.modalElement.style.display = 'block';
        }
    }

    removeScreenshot() {
        document.getElementById('screenshotData').value = '';
        document.getElementById('screenshotPreview').classList.add('d-none');
        document.getElementById('removeScreenshot').classList.add('d-none');

        const captureButton = document.getElementById('captureScreenshot');
        captureButton.innerHTML = '<i class="bi bi-camera"></i> Capture Screenshot';
        captureButton.classList.remove('btn-success');
        captureButton.classList.add('btn-outline-primary');
        captureButton.disabled = false;
    }

    populateContextInfo() {
        const context = this.gatherContext();
        const contextElement = document.getElementById('contextInfo');

        contextElement.innerHTML = `
            <strong>Page:</strong> ${context.url}<br>
            <strong>Route:</strong> ${context.route}<br>
            <strong>Browser:</strong> ${context.browser}<br>
            <strong>Viewport:</strong> ${context.viewport}<br>
            <strong>Screen:</strong> ${context.screenResolution}<br>
            ${context.larpId ? `<strong>LARP:</strong> ${context.larpTitle} (#${context.larpId})<br>` : ''}
            ${context.userEmail ? `<strong>User:</strong> ${context.userName} (${context.userEmail})<br>` : ''}
            <strong>Timestamp:</strong> ${context.timestamp}
        `;
    }

    gatherContext() {
        // Extract LARP context from URL if present
        const urlMatch = window.location.pathname.match(/\/larp\/(\d+)/);
        const larpId = urlMatch ? urlMatch[1] : null;

        // Try to get LARP title from page (if available)
        const larpTitleElement = document.querySelector('[data-larp-title]');
        const larpTitle = larpTitleElement ? larpTitleElement.dataset.larpTitle : null;

        return {
            url: window.location.href,
            route: window.location.pathname,
            userEmail: this.userEmailValue || null,
            userName: this.userNameValue || null,
            userId: this.userIdValue || null,
            larpId: larpId,
            larpTitle: larpTitle,
            browser: navigator.userAgent,
            viewport: `${window.innerWidth}x${window.innerHeight}`,
            screenResolution: `${window.screen.width}x${window.screen.height}`,
            timestamp: new Date().toISOString(),
        };
    }

    async submitFeedback() {
        // Validate form
        const form = document.getElementById('feedbackForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // Gather form data
        const type = document.getElementById('feedbackType').value;
        const subject = document.getElementById('feedbackSubject').value;
        const message = document.getElementById('feedbackDescription').value;
        const screenshot = document.getElementById('screenshotData').value;
        const context = this.gatherContext();

        // Show loading state
        this.setSubmitButtonLoading(true);
        this.hideMessages();

        try {
            // Submit feedback
            const response = await fetch(this.apiUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type,
                    subject,
                    message,
                    screenshot,
                    context,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                this.showSuccess('Thank you! Your feedback has been submitted successfully.');

                // Close modal after 2 seconds
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(this.modalElement);
                    modal.hide();
                }, 2000);
            } else {
                throw new Error(data.message || 'Failed to submit feedback');
            }

        } catch (error) {
            console.error('Feedback submission error:', error);
            this.showError(error.message || 'Failed to submit feedback. Please try again.');
        } finally {
            this.setSubmitButtonLoading(false);
        }
    }

    setSubmitButtonLoading(loading) {
        const button = document.getElementById('submitFeedback');
        const buttonText = document.getElementById('submitButtonText');
        const buttonSpinner = document.getElementById('submitButtonSpinner');

        if (loading) {
            button.disabled = true;
            buttonText.classList.add('d-none');
            buttonSpinner.classList.remove('d-none');
        } else {
            button.disabled = false;
            buttonText.classList.remove('d-none');
            buttonSpinner.classList.add('d-none');
        }
    }

    showError(message) {
        const errorElement = document.getElementById('feedbackError');
        errorElement.textContent = message;
        errorElement.classList.remove('d-none');
    }

    showSuccess(message) {
        const successElement = document.getElementById('feedbackSuccess');
        successElement.textContent = message;
        successElement.classList.remove('d-none');
    }

    hideMessages() {
        document.getElementById('feedbackError').classList.add('d-none');
        document.getElementById('feedbackSuccess').classList.add('d-none');
    }

    resetForm() {
        document.getElementById('feedbackForm').reset();
        this.removeScreenshot();
        this.hideMessages();
    }

    // Public method to open modal (can be called from outside)
    open() {
        const modal = new bootstrap.Modal(this.modalElement);
        modal.show();
    }
}
