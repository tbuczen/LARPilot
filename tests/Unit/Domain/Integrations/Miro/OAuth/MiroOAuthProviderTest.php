<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Integrations\Miro\OAuth;

use App\Domain\Integrations\Miro\OAuth\MiroAuthorization;
use App\Domain\Integrations\Miro\OAuth\MiroOAuthProvider;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class MiroOAuthProviderTest extends TestCase
{
    private const REDIRECT_URI = 'https://larpilot.test/backoffice/larp/integration/connect/integration_miro/check';

    /**
     * @var list<array{request: RequestInterface}>
     */
    private array $sentRequests = [];

    public function testAuthorizationUrlAsksMiroForBoardsReadAccess(): void
    {
        $authorizationUrl = $this->createProvider([])->getAuthorizationUrl(['state' => 'signed-state']);

        $this->assertStringStartsWith(MiroOAuthProvider::AUTHORIZE_URL . '?', $authorizationUrl);
        parse_str((string) parse_url($authorizationUrl, PHP_URL_QUERY), $authorizationQuery);
        $this->assertSame('code', $authorizationQuery['response_type']);
        $this->assertSame('miro-client-id', $authorizationQuery['client_id']);
        $this->assertSame(self::REDIRECT_URI, $authorizationQuery['redirect_uri']);
        $this->assertSame('boards:read', $authorizationQuery['scope']);
        $this->assertSame('signed-state', $authorizationQuery['state']);
    }

    public function testAuthorizationCodeExchangeReturnsTheTeamOfTheConnection(): void
    {
        $miroOAuthProvider = $this->createProvider([new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
            'user_id' => '3458764517517852417',
            'refresh_token' => 'miro-refresh-token',
            'access_token' => 'miro-access-token',
            'expires_in' => 3599,
            'team_id' => '3458764517517818867',
            'scope' => 'boards:read',
            'token_type' => 'bearer',
        ]))]);

        $accessToken = $miroOAuthProvider->getAccessToken('authorization_code', ['code' => 'miro-code']);
        $miroAuthorization = MiroAuthorization::fromAccessToken($accessToken);

        $this->assertSame('miro-access-token', $miroAuthorization->accessToken);
        $this->assertSame('miro-refresh-token', $miroAuthorization->refreshToken);
        $this->assertSame('3458764517517818867', $miroAuthorization->teamId);
        $this->assertSame('3458764517517852417', $miroAuthorization->userId);
        $this->assertSame('boards:read', $miroAuthorization->scopes);
        $this->assertGreaterThan(new \DateTimeImmutable('+50 minutes'), $miroAuthorization->expiresAt);

        $tokenRequest = $this->sentRequests[0]['request'];
        $this->assertSame('POST', $tokenRequest->getMethod());
        $this->assertSame(MiroOAuthProvider::TOKEN_URL, (string) $tokenRequest->getUri());
        parse_str((string) $tokenRequest->getBody(), $requestBody);
        $this->assertSame('authorization_code', $requestBody['grant_type']);
        $this->assertSame('miro-code', $requestBody['code']);
        $this->assertSame('miro-client-id', $requestBody['client_id']);
        $this->assertSame('miro-client-secret', $requestBody['client_secret']);
        $this->assertSame(self::REDIRECT_URI, $requestBody['redirect_uri']);
    }

    public function testRejectedAuthorizationCodeIsAnIdentityProviderError(): void
    {
        $miroOAuthProvider = $this->createProvider([new Response(400, ['Content-Type' => 'application/json'], (string) json_encode([
            'status' => 400,
            'code' => 'invalidParameters',
            'message' => 'Authorization code is invalid',
            'type' => 'error',
        ]))]);

        $this->expectException(IdentityProviderException::class);
        $this->expectExceptionMessage('Authorization code is invalid');

        $miroOAuthProvider->getAccessToken('authorization_code', ['code' => 'expired-code']);
    }

    /**
     * @param list<Response> $responses
     */
    private function createProvider(array $responses): MiroOAuthProvider
    {
        $handlerStack = HandlerStack::create(new MockHandler($responses));
        $handlerStack->push(Middleware::history($this->sentRequests));

        return new MiroOAuthProvider([
            'clientId' => 'miro-client-id',
            'clientSecret' => 'miro-client-secret',
            'redirectUri' => self::REDIRECT_URI,
        ], ['httpClient' => new Client(['handler' => $handlerStack])]);
    }
}
