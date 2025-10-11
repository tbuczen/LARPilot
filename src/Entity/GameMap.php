<?php

namespace App\Entity;

use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\GameMapRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GameMapRepository::class)]
class GameMap implements Timestampable, CreatorAwareInterface, \Stringable
{
    use UuidTraitEntity;
    use TimestampableEntity;
    use CreatorAwareTrait;

    #[ORM\ManyToOne(targetEntity: Larp::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Larp $larp;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFile = null;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 10])]
    private int $gridRows = 10;

    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 10])]
    private int $gridColumns = 10;

    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, options: ['default' => '0.50'])]
    private float $gridOpacity = 0.5;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $gridVisible = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /** @var Collection<MapLocation> */
    #[ORM\OneToMany(targetEntity: MapLocation::class, mappedBy: 'map', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $locations;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->locations = new ArrayCollection();
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

    public function getImageFile(): ?string
    {
        return $this->imageFile;
    }

    public function setImageFile(?string $imageFile): self
    {
        $this->imageFile = $imageFile;
        return $this;
    }

    public function getGridRows(): int
    {
        return $this->gridRows;
    }

    public function setGridRows(int $gridRows): self
    {
        $this->gridRows = $gridRows;
        return $this;
    }

    public function getGridColumns(): int
    {
        return $this->gridColumns;
    }

    public function setGridColumns(int $gridColumns): self
    {
        $this->gridColumns = $gridColumns;
        return $this;
    }

    public function getGridOpacity(): float
    {
        return $this->gridOpacity;
    }

    public function setGridOpacity(float $gridOpacity): self
    {
        $this->gridOpacity = $gridOpacity;
        return $this;
    }

    public function isGridVisible(): bool
    {
        return $this->gridVisible;
    }

    public function setGridVisible(bool $gridVisible): self
    {
        $this->gridVisible = $gridVisible;
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

    /**
     * @return Collection<MapLocation>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(MapLocation $location): self
    {
        if (!$this->locations->contains($location)) {
            $this->locations->add($location);
            $location->setMap($this);
        }
        return $this;
    }

    public function removeLocation(MapLocation $location): self
    {
        if ($this->locations->removeElement($location)) {
            if ($location->getMap() === $this) {
                $location->setMap(null);
            }
        }
        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getImagePath(): ?string
    {
        if (!$this->imageFile) {
            return null;
        }
        return '/uploads/maps/' . $this->imageFile;
    }
}
