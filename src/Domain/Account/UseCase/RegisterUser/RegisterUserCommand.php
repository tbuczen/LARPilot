<?php

namespace App\Domain\Account\UseCase\RegisterUser;

use App\Enum\SocialAccountProvider;

readonly class RegisterUserCommand
{
    public function __construct(
        public SocialAccountProvider  $provider,
        public string  $providerUserId,
        public string  $email,
        public ?string $username = null,
        public ?string $displayName = null
    ) {}
}