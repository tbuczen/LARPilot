<?php

namespace App\Domain\Map\Entity;

use App\Domain\Core\Entity\Trait\CreatorAwareInterface;
use App\Domain\Core\Entity\Trait\CreatorAwareTrait;
use App\Domain\Core\Entity\Trait\UuidTraitEntity;
use App\Domain\Map\Entity\Enum\LocationType;
use App\Domain\Map\Repository\MapLocationRepository;
use App\Domain\StoryObject\Entity\Place;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: MapLocationRepository::class)]
class MapLocation implements Timestampable, CreatorAwareInterface, \Stringable
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: GameMap::class, inversedBy: 'locations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GameMap $map = null;

    #[ORM\ManyToOne(targetEntity: Place::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Place $place = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(type: Types::JSON)]
    private array $gridCoordinates = [];

    #[ORM\Column(length: 7, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $capacity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50, nullable: true, enumType: LocationType::class)]
    private ?LocationType $type = null;

    public function __construct()
    {
        $this->id = Uuid::v4();
    }

    public function getMap(): ?GameMap
    {
        return $this->map;
    }

    public function setMap(?GameMap $map): self
    {
        $this->map = $map;
        return $this;
    }

    public function getPlace(): ?Place
    {
        return $this->place;
    }

    public function setPlace(?Place $place): self
    {
        $this->place = $place;
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

    public function getGridCoordinates(): array
    {
        return $this->gridCoordinates;
    }

    public function setGridCoordinates(array $gridCoordinates): self
    {
        $this->gridCoordinates = $gridCoordinates;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(?int $capacity): self
    {
        $this->capacity = $capacity;
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

    public function getType(): ?LocationType
    {
        return $this->type;
    }

    public function setType(?LocationType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getGridCoordinatesString(): string
    {
        return implode(', ', $this->gridCoordinates);
    }
}
