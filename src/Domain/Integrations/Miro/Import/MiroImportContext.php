<?php

declare(strict_types=1);

namespace App\Domain\Integrations\Miro\Import;

use App\Domain\Core\Entity\Larp;
use App\Domain\Integrations\Miro\Snapshot\MiroBoardSnapshot;
use App\Domain\StoryObject\Entity\Enum\RelationType;
use App\Domain\StoryObject\Entity\Relation;
use App\Domain\StoryObject\Entity\StoryObject;

final class MiroImportContext
{
    /**
     * @var array<string, MiroImportTarget>
     */
    private array $targetsByExternalId = [];

    /**
     * @var array<string, true>
     */
    private array $relationKeys = [];

    /**
     * @param array<string, list<StoryObject>> $linkedObjectsByExternalId
     * @param iterable<Relation> $existingRelations
     */
    public function __construct(
        public readonly Larp $larp,
        public readonly MiroBoardSnapshot $snapshot,
        public readonly MiroStoryObjectIndex $index,
        private readonly array $linkedObjectsByExternalId,
        iterable $existingRelations,
    ) {
        foreach ($existingRelations as $relation) {
            $from = $relation->getFrom();
            $to = $relation->getTo();
            if (!$from instanceof StoryObject || !$to instanceof StoryObject) {
                continue;
            }
            $this->relationKeys[self::relationKey($from, $to, $relation->getRelationType())] = true;
            $this->relationKeys[self::relationKey($to, $from, $relation->getRelationType())] = true;
        }
    }

    /**
     * @return list<StoryObject>
     */
    public function linkedObjects(string $externalId): array
    {
        return $this->linkedObjectsByExternalId[$externalId] ?? [];
    }

    public function recordTarget(string $externalId, MiroImportTarget $target): void
    {
        $this->targetsByExternalId[$externalId] = $target;
    }

    public function target(?string $externalId): ?MiroImportTarget
    {
        if ($externalId === null) {
            return null;
        }

        return $this->targetsByExternalId[$externalId] ?? null;
    }

    public function relationExists(StoryObject $from, StoryObject $to, RelationType $relationType): bool
    {
        return isset($this->relationKeys[self::relationKey($from, $to, $relationType)]);
    }

    private static function relationKey(StoryObject $from, StoryObject $to, RelationType $relationType): string
    {
        return $from->getId()->toRfc4122() . '|' . $to->getId()->toRfc4122() . '|' . $relationType->value;
    }
}
