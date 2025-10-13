<?php

namespace App\Domain\EventPlanning\Entity;

use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\EventPlanning\Entity\Enum\BookingStatus;
use App\Domain\EventPlanning\Repository\ResourceBookingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ResourceBookingRepository::class)]
class ResourceBooking implements Timestampable
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: ScheduledEvent::class, inversedBy: 'resourceBookings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ScheduledEvent $scheduledEvent = null;

    #[ORM\ManyToOne(targetEntity: PlanningResource::class, inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PlanningResource $resource = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1])]
    private int $quantityNeeded = 1;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $required = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 50, enumType: BookingStatus::class, options: ['default' => BookingStatus::PENDING])]
    private BookingStatus $status = BookingStatus::PENDING;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getScheduledEvent(): ?ScheduledEvent
    {
        return $this->scheduledEvent;
    }

    public function setScheduledEvent(?ScheduledEvent $scheduledEvent): self
    {
        $this->scheduledEvent = $scheduledEvent;
        return $this;
    }

    public function getResource(): ?PlanningResource
    {
        return $this->resource;
    }

    public function setResource(?PlanningResource $resource): self
    {
        $this->resource = $resource;
        return $this;
    }

    public function getQuantityNeeded(): int
    {
        return $this->quantityNeeded;
    }

    public function setQuantityNeeded(int $quantityNeeded): self
    {
        $this->quantityNeeded = $quantityNeeded;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }

    public function getStatus(): BookingStatus
    {
        return $this->status;
    }

    public function setStatus(BookingStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Check if this booking conflicts with resource availability
     */
    public function hasAvailabilityConflict(): bool
    {
        if (!$this->resource || !$this->scheduledEvent) {
            return false;
        }

        return !$this->resource->isAvailableDuring(
            $this->scheduledEvent->getEffectiveStartTime(),
            $this->scheduledEvent->getEffectiveEndTime()
        );
    }
}
