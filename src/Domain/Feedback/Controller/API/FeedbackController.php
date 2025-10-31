<?php

namespace App\Domain\Feedback\Controller\API;

use App\Domain\Feedback\Service\GitHubFeedbackService;
use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * API Controller for handling feedback submissions from the feedback widget
 */
class FeedbackController extends AbstractController
{
    public function __construct(
        private readonly GitHubFeedbackService $githubFeedbackService,
        private readonly LoggerInterface $logger,
        private readonly string $recaptchaSecretKey,
    ) {
    }

    /**
     * Submit feedback from the widget
     *
     * Accepts feedback data including screenshots and context, then creates GitHub Issue or Discussion
     *
     * @param Request $request The HTTP request containing feedback data
     * @return JsonResponse Success/error response
     */
    #[Route('/api/feedback', name: 'api_feedback_submit', methods: ['POST'])]
    public function submit(Request $request): JsonResponse
    {
        try {
            // Parse JSON request body
            $data = json_decode($request->getContent(), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid JSON in request body',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Validate required fields
            $requiredFields = ['type', 'subject', 'message', 'recaptchaToken'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    return new JsonResponse([
                        'success' => false,
                        'message' => "Missing required field: {$field}",
                    ], Response::HTTP_BAD_REQUEST);
                }
            }

            // Verify reCAPTCHA
            $recaptcha = new ReCaptcha($this->recaptchaSecretKey);
            $recaptchaResponse = $recaptcha->verify($data['recaptchaToken'], $request->getClientIp());

            if (!$recaptchaResponse->isSuccess()) {
                $this->logger->warning('reCAPTCHA verification failed', [
                    'errors' => $recaptchaResponse->getErrorCodes(),
                ]);

                return new JsonResponse([
                    'success' => false,
                    'message' => 'reCAPTCHA verification failed. Please try again.',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Extract feedback data
            $feedbackData = [
                'type' => $data['type'],
                'subject' => $data['subject'],
                'message' => $data['message'],
                'screenshot' => $data['screenshot'] ?? null,
                'context' => $data['context'] ?? [],
            ];

            // Submit to GitHub via service
            $result = $this->githubFeedbackService->submitFeedback($feedbackData);

            $this->logger->info('Feedback submitted to GitHub successfully', [
                'type' => $result['type'],
                'id' => $result['id'],
                'url' => $result['url'],
                'userEmail' => $feedbackData['context']['userEmail'] ?? 'anonymous',
            ]);

            return new JsonResponse([
                'success' => true,
                'id' => $result['id'],
                'url' => $result['url'],
                'type' => $result['type'],
                'message' => 'Feedback submitted successfully to GitHub',
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning('Invalid feedback submission', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            $this->logger->error('Failed to submit feedback to GitHub', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return new JsonResponse([
                'success' => false,
                'message' => 'An error occurred while submitting your feedback. Please try again later.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
