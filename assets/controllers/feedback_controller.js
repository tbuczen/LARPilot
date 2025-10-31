import { Controller } from '@hotwired/stimulus';
import html2canvas from 'html2canvas';

/*
 * Feedback Widget Controller
 *
 * Provides a floating feedback button that allows users to:
 * - Submit bug reports and feature requests to GitHub Issues
 * - Submit questions and general feedback to GitHub Discussions
 * - Take screenshots
 * - Automatically capture context (URL, user info, browser details, LARP context)
 *
 * Usage in base template:
 * <div data-controller="feedback"
 *      data-feedback-api-url-value="{{ path('api_feedback_submit') }}"
 *      data-feedback-user-email-value="{{ app.user ? app.user.contactEmail : '' }}"
 *      data-feedback-user-name-value="{{ app.user ? app.user.username : '' }}"
 *      data-feedback-user-id-value="{{ app.user ? app.user.id : '' }}">
 *   <button data-action="click->feedback#open">Feedback</button>
 * </div>
 *
 * With Twig component modal:
 * {% include 'components/FeedbackModal.html.twig' with { recaptcha_site_key: recaptcha_site_key } %}
 */
export default class extends Controller {
    static values = {
        apiUrl: String,
        userEmail: String,
        userName: String,
        userId: String,
    }

    connect() {
        this.modalElement = document.getElementById('feedbackModal');
        if (!this.modalElement) {
            console.error('Feedback modal not found. Make sure FeedbackModal.html.twig is included.');
            return;
        }
        this.attachEventListeners();
        this.populateContextInfo();
    }

    disconnect() {
        // Modal is managed by Twig, no need to remove
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
            // Get reCAPTCHA token
            const recaptchaToken = grecaptcha.getResponse();
            if (!recaptchaToken) {
                throw new Error('Please complete the reCAPTCHA verification');
            }

            // Submit feedback to backend
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
                    recaptchaToken,
                }),
            });

            const data = await response.json();

            if (response.ok) {
                // Show success message with GitHub link
                const issueType = data.type === 'issue' ? 'issue' : 'discussion';
                this.showSuccess(
                    `Thank you! Your feedback has been submitted to GitHub as ${issueType} #${data.id}. ` +
                    `<a href="${data.url}" target="_blank" class="alert-link">View on GitHub</a>`
                );

                // If screenshot was captured, offer to download it
                if (screenshot) {
                    this.offerScreenshotDownload(screenshot);
                }

                // Close modal after 5 seconds
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(this.modalElement);
                    modal.hide();
                }, 5000);
            } else {
                throw new Error(data.message || 'Failed to submit feedback');
            }

        } catch (error) {
            console.error('Feedback submission error:', error);
            this.showError(error.message || 'Failed to submit feedback. Please try again.');

            // Reset reCAPTCHA
            if (typeof grecaptcha !== 'undefined') {
                grecaptcha.reset();
            }
        } finally {
            this.setSubmitButtonLoading(false);
        }
    }

    offerScreenshotDownload(screenshotData) {
        // Create a download link for the screenshot
        const downloadLink = document.createElement('a');
        downloadLink.href = screenshotData;
        downloadLink.download = `feedback_screenshot_${Date.now()}.png`;
        downloadLink.className = 'btn btn-sm btn-outline-primary ms-2';
        downloadLink.innerHTML = '<i class="bi bi-download"></i> Download Screenshot';
        downloadLink.target = '_blank';

        const successElement = document.getElementById('feedbackSuccess');
        successElement.appendChild(document.createElement('br'));
        successElement.appendChild(document.createTextNode('You can also '));
        successElement.appendChild(downloadLink);
        successElement.appendChild(document.createTextNode(' to attach it manually to the GitHub issue.'));
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
