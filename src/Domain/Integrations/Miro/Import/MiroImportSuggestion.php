<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use App\Domain\Integrations\Miro\Snapshot\MiroBoardEntry;
use App\Domain\StoryObject\Entity\Enum\RelationType;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Entity\StoryObject;

final readonly class MiroImportSuggestion
{
    public const REASON_UNTAGGED = 'untagged';
    public const REASON_NO_TITLE = 'no_title';
    public const REASON_AMBIGUOUS_TAGS = 'ambiguous_tags';
    public const REASON_AMBIGUOUS_MATCH = 'ambiguous_match';
    public const REASON_NO_FRAME_MATCH = 'no_frame_match';
    public const REASON_ENDPOINT_UNMAPPED = 'endpoint_unmapped';

    public function __construct(
        public MiroBoardEntry $entry,
        public MiroImportAction $action,
        public MiroImportConfidence $confidence = MiroImportConfidence::High,
        public ?MiroImportTarget $target = null,
        public ?RelationType $relationType = null,
        public ?MiroImportTarget $from = null,
        public ?MiroImportTarget $to = null,
        public ?string $reason = null,
    ) {
    }

    public static function link(MiroBoardEntry $entry, StoryObject $storyObject, MiroImportConfidence $confidence = MiroImportConfidence::High): self
    {
        return new self($entry, MiroImportAction::Link, $confidence, MiroImportTarget::existing($storyObject));
    }

    public static function create(MiroBoardEntry $entry, TargetType $type): self
    {
        return new self($entry, MiroImportAction::Create, target: MiroImportTarget::planned($type, MiroTitleNormalizer::exact($entry->title)));
    }

    public static function unmapped(MiroBoardEntry $entry, string $reason): self
    {
        return new self($entry, MiroImportAction::Unmapped, MiroImportConfidence::Low, reason: $reason);
    }

    public static function relation(MiroBoardEntry $entry, MiroImportAction $action, MiroImportTarget $from, MiroImportTarget $to, ?RelationType $relationType = null): self
    {
        return new self($entry, $action, relationType: $relationType, from: $from, to: $to);
    }

    public function isApplicable(): bool
    {
        return $this->action->isApplicable();
    }

    public function token(): string
    {
        return hash('xxh128', implode('|', [
            $this->entry->externalId,
            $this->action->value,
            $this->target?->key() ?? '',
            $this->relationType->value ?? '',
            $this->from?->key() ?? '',
            $this->to?->key() ?? '',
        ]));
    }
}
