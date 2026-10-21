<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Discord\Client\Dto;

final readonly class DiscordGuildMember
{
    public function __construct(
        public DiscordUser $user,
        public ?string $nick,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $user = $payload['user'] ?? [];

        return new self(
            user: DiscordUser::fromArray(is_array($user) ? $user : []),
            nick: isset($payload['nick']) ? (string) $payload['nick'] : null,
        );
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_values(array_filter([$this->user->username, $this->user->globalName, $this->nick], static fn (?string $name): bool => $name !== null && $name !== ''));
    }
}
