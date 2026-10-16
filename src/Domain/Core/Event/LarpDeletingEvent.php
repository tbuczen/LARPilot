<?php

namespace App\Domain\Core\Event;

use App\Domain\Core\Entity\Larp;

class LarpDeletingEvent
{
    /** @var list<string> */
    private array $pathsToRemove = [];

    public function __construct(
        private readonly Larp $larp,
    ) {
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function removeAfterDeletion(string $path): void
    {
        $this->pathsToRemove[] = $path;
    }

    /**
     * @return list<string>
     */
    public function getPathsToRemove(): array
    {
        return $this->pathsToRemove;
    }
}
