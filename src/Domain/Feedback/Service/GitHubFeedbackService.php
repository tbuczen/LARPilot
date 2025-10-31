<?php

namespace App\Domain\Feedback\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service for submitting feedback to GitHub Issues and Discussions
 *
 * Routes feedback based on type:
 * - Bug reports & feature requests → GitHub Issues
 * - Questions & general feedback → GitHub Discussions
 */
class GitHubFeedbackService
{
    private const FEEDBACK_TYPE_LABELS = [
        'bug_report' => ['label' => '🐛 Bug Report', 'issue_labels' => ['bug'], 'use_issue' => true],
        'feature_request' => ['label' => '💡 Feature Request', 'issue_labels' => ['enhancement'], 'use_issue' => true],
        'question' => ['label' => '❓ Question', 'issue_labels' => [], 'use_issue' => false],
        'general' => ['label' => '💬 General Feedback', 'issue_labels' => [], 'use_issue' => false],
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $githubToken,
        private readonly string $githubRepo,
        private readonly string $githubDiscussionCategoryId,
    ) {
    }

    /**
     * Submit feedback to GitHub
     *
     * @param array $feedbackData Array containing:
     *   - type: string (bug_report, feature_request, question, general)
     *   - subject: string
     *   - message: string
     *   - screenshot: string|null (base64 encoded image)
     *   - context: array (url, userEmail, userName, etc.)
     *
     * @return array Response with url and id
     * @throws \InvalidArgumentException If feedback data is invalid
     * @throws \RuntimeException If API request fails
     */
    public function submitFeedback(array $feedbackData): array
    {
        // Validate feedback type
        if (!isset(self::FEEDBACK_TYPE_LABELS[$feedbackData['type']])) {
            throw new \InvalidArgumentException("Invalid feedback type: {$feedbackData['type']}");
        }

        $typeConfig = self::FEEDBACK_TYPE_LABELS[$feedbackData['type']];
        $context = $feedbackData['context'] ?? [];

        // Build title and body
        $title = $this->buildTitle($feedbackData['type'], $feedbackData['subject']);
        $body = $this->buildBody($feedbackData['message'], $context);

        // Route to Issues or Discussions
        if ($typeConfig['use_issue']) {
            return $this->createIssue($title, $body, $typeConfig['issue_labels'], $feedbackData['screenshot'] ?? null);
        }

        return $this->createDiscussion($title, $body, $feedbackData['screenshot'] ?? null);
    }

    /**
     * Create a GitHub Issue
     *
     * @param string $title Issue title
     * @param string $body Issue body (markdown)
     * @param array $labels Issue labels
     * @param string|null $screenshot Base64 encoded screenshot
     * @return array Response with url and issue number
     */
    private function createIssue(string $title, string $body, array $labels, ?string $screenshot): array
    {
        // Upload screenshot first if provided
        $screenshotUrl = null;
        if ($screenshot) {
            $screenshotUrl = $this->uploadScreenshot($screenshot);
            if ($screenshotUrl) {
                $body .= "\n\n## Screenshot\n\n![Screenshot]({$screenshotUrl})";
            }
        }

        try {
            $response = $this->httpClient->request('POST', "https://api.github.com/repos/{$this->githubRepo}/issues", [
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'Authorization' => "Bearer {$this->githubToken}",
                    'X-GitHub-Api-Version' => '2022-11-28',
                ],
                'json' => [
                    'title' => $title,
                    'body' => $body,
                    'labels' => $labels,
                ],
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode === 201) {
                $data = $response->toArray();

                $this->logger->info('GitHub Issue created successfully', [
                    'issueNumber' => $data['number'],
                    'url' => $data['html_url'],
                ]);

                return [
                    'id' => $data['number'],
                    'url' => $data['html_url'],
                    'type' => 'issue',
                ];
            }

            throw new \RuntimeException("GitHub API returned unexpected status code: {$statusCode}");
        } catch (\Exception $e) {
            $this->logger->error('Failed to create GitHub Issue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to create GitHub Issue', 0, $e);
        }
    }

    /**
     * Create a GitHub Discussion using GraphQL API
     *
     * @param string $title Discussion title
     * @param string $body Discussion body (markdown)
     * @param string|null $screenshot Base64 encoded screenshot
     * @return array Response with url and discussion id
     */
    private function createDiscussion(string $title, string $body, ?string $screenshot): array
    {
        // Upload screenshot first if provided
        $screenshotUrl = null;
        if ($screenshot) {
            $screenshotUrl = $this->uploadScreenshot($screenshot);
            if ($screenshotUrl) {
                $body .= "\n\n## Screenshot\n\n![Screenshot]({$screenshotUrl})";
            }
        }

        // Get repository ID
        [$owner, $repo] = explode('/', $this->githubRepo);

        // GraphQL mutation to create discussion
        $mutation = <<<GQL
mutation {
  createDiscussion(input: {
    repositoryId: "{$this->getRepositoryId()}",
    categoryId: "{$this->githubDiscussionCategoryId}",
    title: "{$this->escapeGraphQL($title)}",
    body: "{$this->escapeGraphQL($body)}"
  }) {
    discussion {
      id
      number
      url
    }
  }
}
GQL;

        try {
            $response = $this->httpClient->request('POST', 'https://api.github.com/graphql', [
                'headers' => [
                    'Authorization' => "Bearer {$this->githubToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'query' => $mutation,
                ],
            ]);

            $data = $response->toArray();

            if (isset($data['errors'])) {
                throw new \RuntimeException('GraphQL errors: ' . json_encode($data['errors']));
            }

            $discussion = $data['data']['createDiscussion']['discussion'];

            $this->logger->info('GitHub Discussion created successfully', [
                'discussionNumber' => $discussion['number'],
                'url' => $discussion['url'],
            ]);

            return [
                'id' => $discussion['number'],
                'url' => $discussion['url'],
                'type' => 'discussion',
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to create GitHub Discussion', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new \RuntimeException('Failed to create GitHub Discussion', 0, $e);
        }
    }

    /**
     * Upload screenshot to GitHub as an asset
     *
     * Uses GitHub's issue attachment API
     *
     * @param string $screenshotData Base64 encoded image (with data URI prefix)
     * @return string|null URL of uploaded screenshot
     */
    private function uploadScreenshot(string $screenshotData): ?string
    {
        try {
            // Extract base64 data from data URI
            if (!preg_match('/^data:image\/(\w+);base64,(.+)$/', $screenshotData, $matches)) {
                $this->logger->warning('Screenshot data format not recognized');
                return null;
            }

            $imageType = $matches[1];
            $base64Data = $matches[2];
            $binaryData = base64_decode($base64Data);

            // Create a unique filename
            $filename = 'feedback_screenshot_' . date('Y-m-d_His') . '.' . $imageType;

            // Upload to GitHub as release asset (alternative: use external service like imgur)
            // For now, we'll return null and let GitHub handle it via markdown
            // In production, you might want to upload to a CDN or use GitHub's asset upload

            // Since GitHub doesn't have a direct screenshot upload API for issues,
            // we'll need to use an alternative approach:
            // 1. Create a temporary gist with the image
            // 2. Or use an external image hosting service
            // For simplicity, we'll skip this and rely on users uploading manually

            $this->logger->info('Screenshot prepared but not uploaded (GitHub API limitation)');
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Failed to process screenshot', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get GitHub repository ID using GraphQL
     *
     * @return string Repository ID
     */
    private function getRepositoryId(): string
    {
        static $repositoryId = null;

        if ($repositoryId !== null) {
            return $repositoryId;
        }

        [$owner, $repo] = explode('/', $this->githubRepo);

        $query = <<<GQL
query {
  repository(owner: "{$owner}", name: "{$repo}") {
    id
  }
}
GQL;

        try {
            $response = $this->httpClient->request('POST', 'https://api.github.com/graphql', [
                'headers' => [
                    'Authorization' => "Bearer {$this->githubToken}",
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'query' => $query,
                ],
            ]);

            $data = $response->toArray();
            $repositoryId = $data['data']['repository']['id'];

            return $repositoryId;
        } catch (\Exception $e) {
            throw new \RuntimeException('Failed to fetch repository ID', 0, $e);
        }
    }

    /**
     * Build issue/discussion title
     */
    private function buildTitle(string $type, string $subject): string
    {
        $typeLabel = self::FEEDBACK_TYPE_LABELS[$type]['label'];
        return "[{$typeLabel}] {$subject}";
    }

    /**
     * Build issue/discussion body with context
     */
    private function buildBody(string $message, array $context): string
    {
        $body = $message . "\n\n";
        $body .= "---\n\n";
        $body .= "### 📋 Context Information\n\n";

        if (!empty($context['url'])) {
            $body .= "- **Page URL:** {$context['url']}\n";
        }

        if (!empty($context['route'])) {
            $body .= "- **Route:** `{$context['route']}`\n";
        }

        if (!empty($context['larpId']) && !empty($context['larpTitle'])) {
            $body .= "- **LARP:** {$context['larpTitle']} (#{$context['larpId']})\n";
        }

        if (!empty($context['userId'])) {
            $body .= "- **User ID:** {$context['userId']}\n";
        }

        if (!empty($context['userEmail'])) {
            $body .= "- **User Email:** {$context['userEmail']}\n";
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
     * Escape string for GraphQL
     */
    private function escapeGraphQL(string $value): string
    {
        return addslashes($value);
    }
}
