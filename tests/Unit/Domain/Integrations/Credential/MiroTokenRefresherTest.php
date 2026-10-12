<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Credential;

use App\Domain\Integrations\Miro\Credential\MiroTokenRefresher;
use App\Domain\Integrations\Service\Exceptions\InvalidGrantException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class MiroTokenRefresherTest extends TestCase
{
    public function testRefreshReturnsTheRotatedRefreshToken(): void
    {
        $tokenResponse = new MockResponse((string) json_encode([
            'access_token' => 'new-miro-access-token',
            'refresh_token' => 'rotated-miro-refresh-token',
            'expires_in' => 3599,
            'token_type' => 'bearer',
        ]), ['http_code' => 200, 'response_headers' => ['content-type' => 'application/json']]);
        $httpClient = new MockHttpClient($tokenResponse);

        $refreshedToken = (new MiroTokenRefresher($httpClient, 'miro-client-id', 'miro-client-secret'))->refresh('old-miro-refresh-token');

        $this->assertSame('new-miro-access-token', $refreshedToken->accessToken);
        $this->assertSame('rotated-miro-refresh-token', $refreshedToken->refreshToken);
        $this->assertGreaterThan(new \DateTimeImmutable('+50 minutes'), $refreshedToken->expiresAt);
        $this->assertSame('POST', $tokenResponse->getRequestMethod());
        $this->assertSame(MiroTokenRefresher::TOKEN_URL, $tokenResponse->getRequestUrl());
        parse_str((string) $tokenResponse->getRequestOptions()['body'], $requestBody);
        $this->assertSame([
            'grant_type' => 'refresh_token',
            'client_id' => 'miro-client-id',
            'client_secret' => 'miro-client-secret',
            'refresh_token' => 'old-miro-refresh-token',
        ], $requestBody);
    }

    public function testRejectedRefreshTokenIsAnInvalidGrant(): void
    {
        $httpClient = new MockHttpClient(new MockResponse((string) json_encode([
            'status' => 400,
            'code' => 'invalidRefreshToken',
            'message' => 'Refresh token is invalid',
        ]), ['http_code' => 400, 'response_headers' => ['content-type' => 'application/json']]));

        $this->expectException(InvalidGrantException::class);
        $this->expectExceptionMessage('Refresh token is invalid');

        (new MiroTokenRefresher($httpClient, 'miro-client-id', 'miro-client-secret'))->refresh('revoked-miro-refresh-token');
    }

    public function testServerErrorIsNotTreatedAsAnInvalidGrant(): void
    {
        $httpClient = new MockHttpClient(new MockResponse('{"message":"Service unavailable"}', ['http_code' => 503]));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('HTTP 503');

        (new MiroTokenRefresher($httpClient, 'miro-client-id', 'miro-client-secret'))->refresh('miro-refresh-token');
    }
}
