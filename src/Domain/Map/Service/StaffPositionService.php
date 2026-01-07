<?php

declare(strict_types=1);

namespace App\Domain\Map\Service;

use App\Domain\Core\Entity\Enum\ParticipantRole;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Map\Entity\GameMap;
use App\Domain\Map\Entity\StaffPosition;
use App\Domain\Map\Repository\StaffPositionRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service for managing staff positions on game maps.
 * Handles visibility rules and grid cell validation.
 */
class StaffPositionService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private StaffPositionRepository $repository,
    ) {
    }

    /**
     * Get roles that players can see on the map.
     * Players see only these specific roles, while organizers/staff see all.
     *
     * @return ParticipantRole[]
     */
    public static function getPlayerVisibleRoles(): array
    {
        return [
            ParticipantRole::ORGANIZER,
            ParticipantRole::TRUST_PERSON,
            ParticipantRole::PHOTOGRAPHER,
        ];
    }

    /**
     * Check if a participant can update their position.
     * Only non-player participants (organizers) can update positions.
     */
    public function canUpdatePosition(LarpParticipant $participant): bool
    {
        return $participant->isOrganizer();
    }

    /**
     * Check if a participant can view all staff positions.
     * Organizers and staff can see all positions.
     */
    public function canViewAllPositions(LarpParticipant $participant): bool
    {
        return $participant->isOrganizer();
    }

    /**
     * Check if a position should be visible to a viewer.
     * If viewer is organizer, they can see all positions.
     * If viewer is player, they can only see specific roles (ORGANIZER, TRUST_PERSON, PHOTOGRAPHER).
     */
    public function isPositionVisibleTo(StaffPosition $position, LarpParticipant $viewer): bool
    {
        // Organizers can see all positions
        if ($viewer->isOrganizer()) {
            return true;
        }

        // Players can only see specific roles
        $staffRoles = $position->getParticipant()->getRoles();
        $visibleRoles = self::getPlayerVisibleRoles();

        foreach ($staffRoles as $role) {
            if (in_array($role, $visibleRoles, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all positions visible to a participant on a map.
     *
     * @return StaffPosition[]
     */
    public function getVisiblePositions(GameMap $map, LarpParticipant $viewer): array
    {
        $positions = $this->repository->findByMap($map);

        // Organizers see all positions
        if ($viewer->isOrganizer()) {
            return $positions;
        }

        // Filter positions for players based on visibility rules
        return array_filter(
            $positions,
            fn (StaffPosition $position) => $this->isPositionVisibleTo($position, $viewer)
        );
    }

    /**
     * Update a participant's position on a map.
     */
    public function updatePosition(
        LarpParticipant $participant,
        GameMap $map,
        string $gridCell,
        ?string $statusNote = null
    ): StaffPosition {
        $position = $this->repository->findOrCreate($participant, $map);

        if (!$this->validateGridCell($map, $gridCell)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Invalid grid cell "%s" for map with %d columns and %d rows',
                    $gridCell,
                    $map->getGridColumns(),
                    $map->getGridRows()
                )
            );
        }

        $position->setGridCell($gridCell);
        $position->setStatusNote($statusNote);

        $this->entityManager->persist($position);
        $this->entityManager->flush();

        return $position;
    }

    /**
     * Validate that a grid cell is valid for the given map.
     */
    public function validateGridCell(GameMap $map, string $gridCell): bool
    {
        $gridCell = strtoupper(trim($gridCell));

        // Match pattern like "A1", "B12", "AA1"
        if (!preg_match('/^([A-Z]+)(\d+)$/', $gridCell, $matches)) {
            return false;
        }

        $col = $this->columnLetterToNumber($matches[1]);
        $row = (int) $matches[2];

        // Validate within bounds (1-indexed)
        return $col >= 1 && $col <= $map->getGridColumns()
            && $row >= 1 && $row <= $map->getGridRows();
    }

    /**
     * Convert column letter(s) to number (A=1, B=2, ..., Z=26, AA=27)
     */
    private function columnLetterToNumber(string $letters): int
    {
        $result = 0;
        $length = strlen($letters);

        for ($i = 0; $i < $length; $i++) {
            $result = $result * 26 + (ord($letters[$i]) - ord('A') + 1);
        }

        return $result;
    }

    /**
     * Convert column number to letter(s) (1=A, 2=B, ..., 26=Z, 27=AA)
     */
    public function columnNumberToLetter(int $number): string
    {
        $result = '';

        while ($number > 0) {
            $number--;
            $result = chr(ord('A') + ($number % 26)) . $result;
            $number = (int) ($number / 26);
        }

        return $result;
    }

    /**
     * Remove a participant's position from a map.
     */
    public function removePosition(LarpParticipant $participant, GameMap $map): void
    {
        $position = $this->repository->findByParticipantAndMap($participant, $map);

        if ($position) {
            $this->entityManager->remove($position);
            $this->entityManager->flush();
        }
    }

    /**
     * Get a participant's position on a specific map.
     */
    public function getPosition(LarpParticipant $participant, GameMap $map): ?StaffPosition
    {
        return $this->repository->findByParticipantAndMap($participant, $map);
    }

    /**
     * Convert staff position to array for JSON response.
     */
    public function positionToArray(StaffPosition $position): array
    {
        $centerPosition = $position->getCenterPosition();
        $participant = $position->getParticipant();
        $user = $participant->getUser();
        $roles = $participant->getRoles();

        return [
            'id' => $position->getId()->toString(),
            'participantId' => $participant->getId()->toString(),
            'participantName' => $user?->getUsername() ?? 'Unknown',
            'roles' => array_map(fn ($r) => $r->value, $roles),
            'gridCell' => $position->getGridCell(),
            'centerX' => $centerPosition['x'],
            'centerY' => $centerPosition['y'],
            'statusNote' => $position->getStatusNote(),
            'updatedAt' => $position->getPositionUpdatedAt()->format('Y-m-d H:i:s'),
        ];
    }
}
