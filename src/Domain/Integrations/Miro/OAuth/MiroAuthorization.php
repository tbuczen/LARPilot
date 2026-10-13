<?php

namespace App\Domain\Integrations\Miro\OAuth;

use League\OAuth2\Client\Token\AccessTokenInterface;

final readonly class MiroAuthorization
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public ?\DateTimeImmutable $expiresAt,
        public ?string $teamId,
        public ?string $userId,
        public ?string $scopes,
    ) {
    }

    public static function fromAccessToken(AccessTokenInterface $accessToken): self
    {
        $tokenValues = $accessToken->getValues();
        $expiresTimestamp = $accessToken->getExpires();

        return new self(
            $accessToken->getToken(),
            $accessToken->getRefreshToken(),
            $expiresTimestamp === null ? null : (new \DateTimeImmutable())->setTimestamp($expiresTimestamp),
            self::stringValue($tokenValues, 'team_id'),
            self::stringValue($tokenValues, 'user_id'),
            self::stringValue($tokenValues, 'scope'),
        );
    }

    /**
     * @param array<mixed> $tokenValues
     */
    private static function stringValue(array $tokenValues, string $key): ?string
    {
        $value = $tokenValues[$key] ?? null;
        if (!is_scalar($value) || (string) $value === '') {
            return null;
        }

        return (string) $value;
    }
}
