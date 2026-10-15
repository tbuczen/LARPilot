<?php

namespace App\Domain\Integrations\Discord\Picker;

use App\Domain\Integrations\Entity\Enum\ExternalSourceVisibility;
use App\Domain\StoryObject\Entity\Enum\TargetType;

final readonly class DiscordSourceConfig
{
    /**
     * @param array<string, DiscordForumTagMapping> $forumTagMappings
     */
    public function __construct(
        public ExternalSourceVisibility $visibility = ExternalSourceVisibility::OrganizerOnly,
        public ?TargetType $defaultTargetType = null,
        public array $forumTagMappings = [],
        public bool $includePrivateThreads = false,
    ) {
    }

    /**
     * @param array<mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $visibility = $config['visibility'] ?? null;
        $defaultTargetType = $config['defaultTargetType'] ?? null;
        $forumTagMappings = [];
        foreach ($config['forumTagMappings'] ?? [] as $forumTagId => $mapping) {
            if (!is_array($mapping)) {
                continue;
            }

            $forumTagMappings[(string) $forumTagId] = DiscordForumTagMapping::fromArray($mapping);
        }

        return new self(
            is_string($visibility) ? ExternalSourceVisibility::tryFrom($visibility) ?? ExternalSourceVisibility::OrganizerOnly : ExternalSourceVisibility::OrganizerOnly,
            is_string($defaultTargetType) ? TargetType::tryFrom($defaultTargetType) : null,
            $forumTagMappings,
            ($config['includePrivateThreads'] ?? false) === true,
        );
    }

    public function forumTagMapping(string $forumTagId): DiscordForumTagMapping
    {
        return $this->forumTagMappings[$forumTagId] ?? new DiscordForumTagMapping();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $forumTagMappings = [];
        foreach ($this->forumTagMappings as $forumTagId => $mapping) {
            if ($mapping->isEmpty()) {
                continue;
            }

            $forumTagMappings[$forumTagId] = $mapping->toArray();
        }

        return [
            'visibility' => $this->visibility->value,
            'defaultTargetType' => $this->defaultTargetType?->value,
            'forumTagMappings' => $forumTagMappings,
            'includePrivateThreads' => $this->includePrivateThreads,
        ];
    }
}
