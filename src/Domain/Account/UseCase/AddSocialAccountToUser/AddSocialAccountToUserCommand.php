<?php

namespace App\Domain\Account\UseCase\AddSocialAccountToUser;

use App\Enum\SocialAccountProvider;

readonly class AddSocialAccountToUserCommand
{
    public function __construct(
        public SocialAccountProvider  $provider,
        public string  $providerUserId,
        public string  $email,
        public string  $userId,
        public ?string $username = null,
        public ?string $displayName = null
    ) {}
}