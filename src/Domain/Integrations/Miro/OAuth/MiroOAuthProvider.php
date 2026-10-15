<?php

namespace App\Domain\Integrations\Miro\OAuth;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use Psr\Http\Message\ResponseInterface;

class MiroOAuthProvider extends GenericProvider
{
    public const AUTHORIZE_URL = 'https://miro.com/oauth/authorize';
    public const TOKEN_URL = 'https://api.miro.com/v1/oauth/token';
    public const TOKEN_CONTEXT_URL = 'https://api.miro.com/v1/oauth-token';
    public const BOARDS_READ_SCOPE = 'boards:read';

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        parent::__construct(array_merge([
            'urlAuthorize' => self::AUTHORIZE_URL,
            'urlAccessToken' => self::TOKEN_URL,
            'urlResourceOwnerDetails' => self::TOKEN_CONTEXT_URL,
            'scopes' => [self::BOARDS_READ_SCOPE],
            'scopeSeparator' => ' ',
            'accessTokenResourceOwnerId' => 'user_id',
        ], $options), $collaborators);
    }

    /**
     * @param array<mixed>|string $data
     */
    protected function checkResponse(ResponseInterface $response, $data): void
    {
        $statusCode = $response->getStatusCode();
        $payload = is_array($data) ? $data : [];
        if ($statusCode < 400 && !isset($payload['error'])) {
            return;
        }

        $errorMessage = $payload['message'] ?? $payload['error_description'] ?? $payload['error'] ?? $response->getReasonPhrase();

        throw new IdentityProviderException(is_string($errorMessage) ? $errorMessage : 'Miro rejected the request.', $statusCode, $data);
    }
}
