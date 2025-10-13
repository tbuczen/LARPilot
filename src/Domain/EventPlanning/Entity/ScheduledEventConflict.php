<?php

namespace App\Domain\EventPlanning\Entity;

use App\Domain\Account\Entity\User;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\EventPlanning\Entity\Enum\ConflictSeverity;
use App\Domain\EventPlanning\Entity\Enum\ConflictType;
use App\Domain\EventPlanning\Repository\ScheduledEventConflictRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ScheduledEventConflictRepository::class)]
class ScheduledEventConflict
{
    use UuidTraitEntity;
    use TimestampableEntity;

    #[ORM\ManyToOne(targetEntity: ScheduledEvent::class, inversedBy: 'conflicts')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ScheduledEvent $event1 = null;

    #[ORM\ManyToOne(targetEntity: ScheduledEvent::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ScheduledEvent $event2 = null;

    #[ORM\Column(length: 50, enumType: ConflictType::class)]
    private ConflictType $type;

    #[ORM\Column(length: 50, enumType: ConflictSeverity::class)]
    private ConflictSeverity $severity;

    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resolution = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $resolved = false;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $resolvedBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $resolvedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $detectedAt;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->detectedAt = new \DateTime();
    }

    public function getEvent1(): ?ScheduledEvent
    {
        return $this->event1;
    }

    public function setEvent1(?ScheduledEvent $event1): self
    {
        $this->event1 = $event1;
        return $this;
    }

    public function getEvent2(): ?ScheduledEvent
    {
        return $this->event2;
    }

    public function setEvent2(?ScheduledEvent $event2): self
    {
        $this->event2 = $event2;
        return $this;
    }

    public function getType(): ConflictType
    {
        return $this->type;
    }

    public function setType(ConflictType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getSeverity(): ConflictSeverity
    {
        return $this->severity;
    }

    public function setSeverity(ConflictSeverity $severity): self
    {
        $this->severity = $severity;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): self
    {
        $this->resolution = $resolution;
        return $this;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function setResolved(bool $resolved): self
    {
        $this->resolved = $resolved;
        return $this;
    }

    public function getResolvedBy(): ?User
    {
        return $this->resolvedBy;
    }

    public function setResolvedBy(?User $resolvedBy): self
    {
        $this->resolvedBy = $resolvedBy;
        return $this;
    }

    public function getResolvedAt(): ?\DateTimeInterface
    {
        return $this->resolvedAt;
    }

    public function setResolvedAt(?\DateTimeInterface $resolvedAt): self
    {
        $this->resolvedAt = $resolvedAt;
        return $this;
    }

    public function getDetectedAt(): \DateTimeInterface
    {
        return $this->detectedAt;
    }

    public function setDetectedAt(\DateTimeInterface $detectedAt): self
    {
        $this->detectedAt = $detectedAt;
        return $this;
    }

    /**
     * Mark conflict as resolved
     */
    public function resolve(User $user, ?string $resolution = null): self
    {
        $this->resolved = true;
        $this->resolvedBy = $user;
        $this->resolvedAt = new \DateTime();
        if ($resolution !== null) {
            $this->resolution = $resolution;
        }
        return $this;
    }
}
