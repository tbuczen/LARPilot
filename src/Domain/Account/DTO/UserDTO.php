<?php


namespace App\Domain\Account\DTO;

readonly class UserDTO
{
    public function __construct(
        public string $userId,
        public string $email,
        public array  $roles // e.g. list of role strings or enum values
    ) {
    }
}
