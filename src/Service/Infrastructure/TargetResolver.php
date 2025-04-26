<?php

namespace App\Service\Infrastructure;

use App\Entity\Enum\TargetType;
use App\Entity\TargetableInterface;
use App\Repository\StoryObject\LarpCharacterRepository;
use App\Repository\StoryObject\LarpFactionRepository;
use Symfony\Component\Uid\Uuid;

final readonly class TargetResolver
{

    public function __construct(
        private LarpCharacterRepository $characterRepository,
        private LarpFactionRepository   $factionRepository,
    ) {}

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