<?php

namespace App\Domain\Mailing\Dto;

use App\Domain\Mailing\Entity\Enum\MailTemplateType;

/**
 * @codeCoverageIgnore Simple data holder.
 */
readonly class MailTemplateDefinition
{
    /**
     * @param list<string> $placeholders
     */
    public function __construct(
        public MailTemplateType $type,
        public string $name,
        public string $description,
        public string $defaultSubject,
        public string $defaultBody,
        public array $placeholders = [],
        public bool $mandatory = false,
    ) {
    }
}
