<?php

declare(strict_types=1);

namespace App\Domain\Map\Entity;

use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Map\Repository\StaffPositionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Tracks staff member positions on game maps during events.
 * Only non-player participants (organizers, staff, etc.) can update positions.
 */
#[ORM\Entity(repositoryClass: StaffPositionRepository::class)]
#[ORM\Table(name: 'staff_position')]
#[ORM\UniqueConstraint(name: 'unique_participant_map', columns: ['participant_id', 'map_id'])]
#[UniqueEntity(fields: ['participant', 'map'], message: 'staff_position.already_exists')]
class StaffPosition
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: LarpParticipant::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private LarpParticipant $participant;

    #[ORM\ManyToOne(targetEntity: GameMap::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GameMap $map;

    /**
     * Grid cell reference in format "A1", "B2", etc.
     */
    #[ORM\Column(length: 10)]
    private string $gridCell;

    /**
     * Optional status note (e.g., "On break", "Handling incident")
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $statusNote = null;

    /**
     * Timestamp of when the position was last updated (separate from entity updatedAt)
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $positionUpdatedAt;

    public function __construct()
    {
        $this->positionUpdatedAt = new \DateTime();
    }

    public function getParticipant(): LarpParticipant
    {
        return $this->participant;
    }

    public function setParticipant(LarpParticipant $participant): self
    {
        $this->participant = $participant;
        return $this;
    }

    public function getMap(): GameMap
    {
        return $this->map;
    }

    public function setMap(GameMap $map): self
    {
        $this->map = $map;
        return $this;
    }

    public function getGridCell(): string
    {
        return $this->gridCell;
    }

    public function setGridCell(string $gridCell): self
    {
        $this->gridCell = strtoupper($gridCell);
        $this->positionUpdatedAt = new \DateTime();
        return $this;
    }

    public function getStatusNote(): ?string
    {
        return $this->statusNote;
    }

    public function setStatusNote(?string $statusNote): self
    {
        $this->statusNote = $statusNote;
        return $this;
    }

    public function getPositionUpdatedAt(): \DateTimeInterface
    {
        return $this->positionUpdatedAt;
    }

    public function setPositionUpdatedAt(\DateTimeInterface $positionUpdatedAt): self
    {
        $this->positionUpdatedAt = $positionUpdatedAt;
        return $this;
    }

    /**
     * Gets the column letter from the grid cell (e.g., "A" from "A1")
     */
    public function getGridColumn(): string
    {
        preg_match('/^([A-Z]+)/', $this->gridCell, $matches);
        return $matches[1] ?? 'A';
    }

    /**
     * Gets the row number from the grid cell (e.g., 1 from "A1")
     */
    public function getGridRow(): int
    {
        preg_match('/(\d+)$/', $this->gridCell, $matches);
        return (int) ($matches[1] ?? 1);
    }

    /**
     * Calculate the center position (as percentage) for displaying the marker on the map.
     */
    public function getCenterPosition(): array
    {
        $map = $this->map;
        $col = ord($this->getGridColumn()) - ord('A');
        $row = $this->getGridRow() - 1;

        $cellWidthPercent = 100 / $map->getGridColumns();
        $cellHeightPercent = 100 / $map->getGridRows();

        return [
            'x' => ($col + 0.5) * $cellWidthPercent,
            'y' => ($row + 0.5) * $cellHeightPercent,
        ];
    }
}
