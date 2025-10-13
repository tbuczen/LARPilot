<?php

namespace App\Domain\Application\DTO;

readonly class CharacterMatchDTO
{
    /**
     * @param ApplicationChoiceDTO[] $choices
     */
    public function __construct(
        public string $characterId,
        public string $characterTitle,
        public array $choices,
    ) {
    }
}
