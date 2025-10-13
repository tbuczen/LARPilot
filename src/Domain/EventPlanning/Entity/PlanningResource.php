<?php

namespace App\Domain\EventPlanning\Entity;

use App\Domain\Core\Entity\Larp;
use App\Domain\Core\Entity\LarpParticipant;
use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\EventPlanning\Entity\Enum\PlanningResourceType;
use App\Domain\EventPlanning\Repository\PlanningResourceRepository;
use App\Domain\StoryObject\Entity\Character;
use App\Domain\StoryObject\Entity\Item;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: PlanningResourceRepository::class)]
class PlanningResource implements Timestampable, CreatorAwareInterface, \Stringable
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 50, enumType: PlanningResourceType::class)]
    private PlanningResourceType $type;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 1])]
    private int $quantity = 1;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $shareable = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $availableFrom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $availableUntil = null;

    #[ORM\ManyToOne(targetEntity: Character::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Character $character = null;

    #[ORM\ManyToOne(targetEntity: Item::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Item $item = null;

    #[ORM\ManyToOne(targetEntity: LarpParticipant::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?LarpParticipant $participant = null;

    /** @var Collection<ResourceBooking> */
    #[ORM\OneToMany(targetEntity: ResourceBooking::class, mappedBy: 'resource', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bookings;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->bookings = new ArrayCollection();
    }

    public function getLarp(): Larp
    {
        return $this->larp;
    }

    public function setLarp(Larp $larp): self
    {
        $this->larp = $larp;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): PlanningResourceType
    {
        return $this->type;
    }

    public function setType(PlanningResourceType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function isShareable(): bool
    {
        return $this->shareable;
    }

    public function setShareable(bool $shareable): self
    {
        $this->shareable = $shareable;
        return $this;
    }

    public function getAvailableFrom(): ?\DateTimeInterface
    {
        return $this->availableFrom;
    }

    public function setAvailableFrom(?\DateTimeInterface $availableFrom): self
    {
        $this->availableFrom = $availableFrom;
        return $this;
    }

    public function getAvailableUntil(): ?\DateTimeInterface
    {
        return $this->availableUntil;
    }

    public function setAvailableUntil(?\DateTimeInterface $availableUntil): self
    {
        $this->availableUntil = $availableUntil;
        return $this;
    }

    public function getCharacter(): ?Character
    {
        return $this->character;
    }

    public function setCharacter(?Character $character): self
    {
        $this->character = $character;
        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): self
    {
        $this->item = $item;
        return $this;
    }

    public function getParticipant(): ?LarpParticipant
    {
        return $this->participant;
    }

    public function setParticipant(?LarpParticipant $participant): self
    {
        $this->participant = $participant;
        return $this;
    }

    /**
     * @return Collection<ResourceBooking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(ResourceBooking $booking): self
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setResource($this);
        }
        return $this;
    }

    public function removeBooking(ResourceBooking $booking): self
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getResource() === $this) {
                $booking->setResource(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * Check if resource is available for a given time period
     */
    public function isAvailableDuring(\DateTimeInterface $startTime, \DateTimeInterface $endTime): bool
    {
        if ($this->availableFrom && $startTime < $this->availableFrom) {
            return false;
        }

        if ($this->availableUntil && $endTime > $this->availableUntil) {
            return false;
        }

        return true;
    }

    /**
     * Get linked entity title (character, item, or participant)
     */
    public function getLinkedEntityTitle(): ?string
    {
        if ($this->character) {
            return $this->character->getTitle();
        }
        if ($this->item) {
            return $this->item->getTitle();
        }
        if ($this->participant) {
            return $this->participant->getUser()->getFullName();
        }
        return null;
    }
}
