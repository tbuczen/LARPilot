<?php

namespace App\Domain\Feedback\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for submitting feedback to FreeScout helpdesk system
 *
 * Converts feedback widget submissions into FreeScout tickets via REST API
 */
class FeedbackService
{
    private const FEEDBACK_TYPE_LABELS = [
        'bug_report' => '🐛 Bug Report',
        'feature_request' => '💡 Feature Request',
        'question' => '❓ Question',
        'general' => '💬 General Feedback',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $freescoutApiUrl,
        private readonly string $freescoutApiToken,
        private readonly int $freescoutMailboxId,
    ) {
    }

    /**
     * Submit feedback to FreeScout and create a ticket
     *
     * @param array $feedbackData Array containing:
     *   - type: string (bug_report, feature_request, question, general)
     *   - subject: string
     *   - message: string
     *   - screenshot: string|null (base64 encoded image)
     *   - context: array (url, userEmail, userName, etc.)
     *
     * @return int|null The created ticket ID, or null if creation failed
     * @throws \InvalidArgumentException If feedback data is invalid
     * @throws \RuntimeException If API request fails
     */
    public function submitFeedback(array $feedbackData): ?int
    {
        // Validate feedback type
        if (!isset(self::FEEDBACK_TYPE_LABELS[$feedbackData['type']])) {
            throw new \InvalidArgumentException("Invalid feedback type: {$feedbackData['type']}");
        }

        $context = $feedbackData['context'] ?? [];

        // Build ticket subject
        $typeLabel = self::FEEDBACK_TYPE_LABELS[$feedbackData['type']];
        $subject = "[{$typeLabel}] {$feedbackData['subject']}";

        // Build ticket body with context
        $body = $this->buildTicketBody($feedbackData['message'], $context);

        // Extract customer info
        $customerEmail = $context['userEmail'] ?? 'anonymous@larpilot.com';
        $customerName = $context['userName'] ?? 'Anonymous User';
        [$firstName, $lastName] = $this->parseCustomerName($customerName);

        // Prepare ticket data
        $ticketData = [
            'mailboxId' => $this->freescoutMailboxId,
            'type' => 'email',
            'status' => 'active',
            'subject' => $subject,
            'customer' => [
                'email' => $customerEmail,
                'firstName' => $firstName,
                'lastName' => $lastName,
            ],
            'threads' => [
                [
                    'type' => 'customer',
                    'customer' => [
                        'email' => $customerEmail,
                    ],
                    'text' => $body,
                ],
            ],
        ];

        // Add screenshot attachment if provided
        if (!empty($feedbackData['screenshot'])) {
            $ticketData['threads'][0]['attachments'] = [
                $this->prepareScreenshotAttachment($feedbackData['screenshot']),
            ];
        }

        // Send to FreeScout API
        try {
            $response = $this->httpClient->request('POST', "{$this->freescoutApiUrl}/conversations", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-FreeScout-API-Key' => $this->freescoutApiToken,
                ],
                'json' => $ticketData,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 201) {
                $responseData = $response->toArray();
                $ticketId = $responseData['id'] ?? null;

                $this->logger->info('Feedback ticket created in FreeScout', [
                    'ticketId' => $ticketId,
                    'subject' => $subject,
                ]);

                return $ticketId;
            }

            $this->logger->error('Unexpected response from FreeScout API', [
                'statusCode' => $statusCode,
                'response' => $response->getContent(false),
            ]);

            throw new \RuntimeException("FreeScout API returned unexpected status code: {$statusCode}");
        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            $this->logger->error('Failed to connect to FreeScout API', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException('Failed to connect to FreeScout helpdesk system', 0, $e);
        } catch (\Exception $e) {
            $this->logger->error('Error submitting feedback to FreeScout', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to create feedback ticket', 0, $e);
        }
    }

    /**
     * Build the ticket body text with context information
     *
     * @param string $message The main feedback message
     * @param array $context Context information (URL, browser, LARP, etc.)
     * @return string Formatted ticket body
     */
    private function buildTicketBody(string $message, array $context): string
    {
        $body = $message . "\n\n";
        $body .= "---\n";
        $body .= "**Context Information:**\n\n";

        if (!empty($context['url'])) {
            $body .= "- **Page URL:** {$context['url']}\n";
        }

        if (!empty($context['route'])) {
            $body .= "- **Route:** {$context['route']}\n";
        }

        if (!empty($context['larpId']) && !empty($context['larpTitle'])) {
            $body .= "- **LARP:** {$context['larpTitle']} (#{$context['larpId']})\n";
        }

        if (!empty($context['userId'])) {
            $body .= "- **User ID:** {$context['userId']}\n";
        }

        if (!empty($context['browser'])) {
            $body .= "- **Browser:** {$context['browser']}\n";
        }

        if (!empty($context['viewport'])) {
            $body .= "- **Viewport:** {$context['viewport']}\n";
        }

        if (!empty($context['screenResolution'])) {
            $body .= "- **Screen Resolution:** {$context['screenResolution']}\n";
        }

        if (!empty($context['timestamp'])) {
            $body .= "- **Timestamp:** {$context['timestamp']}\n";
        }

        return $body;
    }

    /**
     * Parse customer name into first and last name
     *
     * @param string $fullName Full name string
     * @return array [firstName, lastName]
     */
    private function parseCustomerName(string $fullName): array
    {
        $parts = explode(' ', trim($fullName), 2);

        return [
            $parts[0] ?? 'Anonymous',
            $parts[1] ?? 'User',
        ];
    }

    /**
     * Prepare screenshot attachment for FreeScout API
     *
     * @param string $screenshotData Base64 encoded image data (with data URI prefix)
     * @return array Attachment data for FreeScout API
     */
    private function prepareScreenshotAttachment(string $screenshotData): array
    {
        // Extract base64 data from data URI (format: data:image/png;base64,...)
        if (preg_match('/^data:image\/(\w+);base64,(.+)$/', $screenshotData, $matches)) {
            $imageType = $matches[1]; // png, jpeg, etc.
            $base64Data = $matches[2];

            return [
                'fileName' => 'screenshot_' . date('Y-m-d_His') . '.' . $imageType,
                'mimeType' => 'image/' . $imageType,
                'data' => $base64Data,
            ];
        }

        // Fallback if format doesn't match (shouldn't happen with html2canvas)
        $this->logger->warning('Screenshot data format not recognized, using default PNG');

        return [
            'fileName' => 'screenshot_' . date('Y-m-d_His') . '.png',
            'mimeType' => 'image/png',
            'data' => base64_encode($screenshotData),
        ];
    }
}
