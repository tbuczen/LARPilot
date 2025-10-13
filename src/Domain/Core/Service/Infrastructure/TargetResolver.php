<?php

namespace App\Domain\Core\Service\Infrastructure;

use App\Domain\Core\Entity\TargetableInterface;
use App\Domain\StoryObject\Entity\Enum\TargetType;
use App\Domain\StoryObject\Repository\CharacterRepository;
use App\Domain\StoryObject\Repository\FactionRepository;
use Symfony\Component\Uid\Uuid;

final readonly class TargetResolver
{
    public function __construct(
        private CharacterRepository $characterRepository,
        private FactionRepository   $factionRepository,
    ) {
    }

    public function resolve(TargetType $type, Uuid $id): ?TargetableInterface
    {
        $object = match ($type) {
            TargetType::Character => $this->characterRepository->find($id),
            TargetType::Faction => $this->factionRepository->find($id),
            default => null,
        };

        if ($object instanceof TargetableInterface) {
            return $object;
        }

        return null;
    }
}
