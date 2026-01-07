<?php

declare(strict_types=1);

namespace Tests\Support\Factory\Map;

use App\Domain\Map\Entity\StaffPosition;
use Tests\Support\Factory\Core\LarpParticipantFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<StaffPosition>
 */
final class StaffPositionFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return StaffPosition::class;
    }

    protected function defaults(): array
    {
        return [
            'participant' => LarpParticipantFactory::new()->organizer(),
            'map' => GameMapFactory::new(),
            'gridCell' => $this->randomGridCell(),
            'statusNote' => null,
            'positionUpdatedAt' => new \DateTime(),
        ];
    }

    protected function initialize(): static
    {
        return $this;
    }

    // ========================================================================
    // Factory States
    // ========================================================================

    /**
     * Position for a specific participant
     */
    public function forParticipant(mixed $participant): self
    {
        return $this->with([
            'participant' => $participant,
        ]);
    }

    /**
     * Position on a specific map
     */
    public function forMap(mixed $map): self
    {
        return $this->with([
            'map' => $map,
        ]);
    }

    /**
     * Position at a specific grid cell
     */
    public function atCell(string $gridCell): self
    {
        return $this->with([
            'gridCell' => $gridCell,
        ]);
    }

    /**
     * Position with a status note
     */
    public function withStatusNote(string $note): self
    {
        return $this->with([
            'statusNote' => $note,
        ]);
    }

    /**
     * Position updated at a specific time
     */
    public function updatedAt(\DateTimeInterface $dateTime): self
    {
        return $this->with([
            'positionUpdatedAt' => $dateTime,
        ]);
    }

    /**
     * Position for an organizer participant
     */
    public function forOrganizer(): self
    {
        return $this->with([
            'participant' => LarpParticipantFactory::new()->organizer(),
        ]);
    }

    /**
     * Position for a staff participant
     */
    public function forStaff(): self
    {
        return $this->with([
            'participant' => LarpParticipantFactory::new()->staff(),
        ]);
    }

    /**
     * Position for a game master
     */
    public function forGameMaster(): self
    {
        return $this->with([
            'participant' => LarpParticipantFactory::new()->gameMaster(),
        ]);
    }

    /**
     * Position for a trust person
     */
    public function forTrustPerson(): self
    {
        return $this->with([
            'participant' => LarpParticipantFactory::new()->trustPerson(),
        ]);
    }

    /**
     * Position for a photographer
     */
    public function forPhotographer(): self
    {
        return $this->with([
            'participant' => LarpParticipantFactory::new()->photographer(),
        ]);
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    /**
     * Generate a random grid cell reference (A1-J10)
     */
    private function randomGridCell(): string
    {
        $col = chr(65 + rand(0, 9)); // A-J
        $row = rand(1, 10);
        return $col . $row;
    }
}
