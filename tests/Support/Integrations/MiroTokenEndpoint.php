<?php

declare(strict_types=1);

namespace Tests\Support\Integrations;

use App\Domain\Integrations\Miro\OAuth\MiroOAuthProvider;
use Symfony\Component\HttpClient\Response\MockResponse;

final class MiroTokenEndpoint
{
    public const REVOKED_REFRESH_TOKEN_PREFIX = 'revoked-';

    /**
     * @var list<array<string, mixed>>
     */
    private array $tokenRequests = [];

    /**
     * @param array<string, mixed> $options
     */
    public function __invoke(string $method, string $url, array $options = []): MockResponse
    {
        if ($method !== 'POST' || $url !== MiroOAuthProvider::TOKEN_URL) {
            throw new \LogicException(sprintf('Unexpected Miro OAuth request %s %s', $method, $url));
        }

        parse_str((string) ($options['body'] ?? ''), $requestBody);
        $this->tokenRequests[] = $requestBody;

        $refreshToken = (string) ($requestBody['refresh_token'] ?? '');
        if (str_starts_with($refreshToken, self::REVOKED_REFRESH_TOKEN_PREFIX)) {
            return $this->jsonResponse([
                'status' => 400,
                'code' => 'invalidRefreshToken',
                'message' => 'Refresh token is invalid or revoked',
                'type' => 'error',
            ], 400);
        }

        $requestNumber = count($this->tokenRequests);

        return $this->jsonResponse([
            'user_id' => FakeMiroOAuthGateway::USER_ID,
            'team_id' => FakeMiroOAuthGateway::TEAM_ID,
            'access_token' => self::accessToken($requestNumber),
            'refresh_token' => self::rotatedRefreshToken($requestNumber),
            'expires_in' => 3599,
            'scope' => MiroOAuthProvider::BOARDS_READ_SCOPE,
            'token_type' => 'bearer',
        ], 200);
    }

    public static function accessToken(int $requestNumber): string
    {
        return 'miro-refreshed-access-token-' . $requestNumber;
    }

    public static function rotatedRefreshToken(int $requestNumber): string
    {
        return 'miro-rotated-refresh-token-' . $requestNumber;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function tokenRequests(): array
    {
        return $this->tokenRequests;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonResponse(array $payload, int $statusCode): MockResponse
    {
        return new MockResponse((string) json_encode($payload), [
            'http_code' => $statusCode,
            'response_headers' => ['content-type' => 'application/json'],
        ]);
    }
}
