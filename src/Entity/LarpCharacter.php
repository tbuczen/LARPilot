<?php

namespace App\Entity;

use App\Entity\Enum\TargetType;
use App\Entity\Trait\CreatorAwareInterface;
use App\Entity\Trait\CreatorAwareTrait;
use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LarpCharacterRepository;
use App\Validator\UniqueCharacterName;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Timestampable;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Uid\Uuid;

#[Gedmo\Loggable]
#[ORM\UniqueConstraint(fields: ['larp', 'name'])]
#[ORM\Entity(repositoryClass: LarpCharacterRepository::class)]
class LarpCharacter implements CreatorAwareInterface, Timestampable, StoryObject
{
    use UuidTraitEntity;
    use CreatorAwareTrait;
    use TimestampableEntity;

    #[ORM\Column(length: 255)]
    #[UniqueCharacterName]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $inGameName = null;

    #[Gedmo\Versioned]
    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'characters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    #[ORM\OneToOne(targetEntity: self::class, inversedBy: 'continuation')]
    #[ORM\JoinColumn(name: "previous_character_id", referencedColumnName: "id", nullable: true)]
    private ?LarpCharacter $previousCharacter = null;

    #[ORM\OneToOne(targetEntity: self::class, mappedBy: 'previousCharacter')]
    private ?LarpCharacter $continuation = null;

    #[Gedmo\Versioned]
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $postLarpFate = null;

    #[ORM\ManyToMany(targetEntity: LarpFaction::class, inversedBy: 'members')]
    private Collection $factions;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->factions = new ArrayCollection();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getLarp(): ?Larp
    {
        return $this->larp;
    }

    public function setLarp(?Larp $larp): static
    {
        $this->larp = $larp;
        return $this;
    }

    public function getPreviousCharacter(): ?self
    {
        return $this->previousCharacter;
    }

    public function setPreviousCharacter(?self $previousCharacter): static
    {
        $this->previousCharacter = $previousCharacter;
        return $this;
    }

    public function getContinuation(): ?self
    {
        return $this->continuation;
    }

    public function setContinuation(?self $continuation): static
    {
        $this->continuation = $continuation;
        return $this;
    }

    public function getPostLarpFate(): ?string
    {
        return $this->postLarpFate;
    }

    public function setPostLarpFate(?string $postLarpFate): static
    {
        $this->postLarpFate = $postLarpFate;
        return $this;
    }

    /**
     * @return Collection<LarpFaction>
     */
    public function getFactions(): Collection
    {
        return $this->factions;
    }

    public function addFaction(LarpFaction $larpFaction): self
    {
        if (!$this->factions->contains($larpFaction)) {
            $this->factions[] = $larpFaction;
        }
        return $this;
    }

    public function removeFaction(LarpFaction $larpFaction): self
    {
        $this->factions->removeElement($larpFaction);
        return $this;
    }

    public function getInGameName(): ?string
    {
        return $this->inGameName;
    }

    public function setInGameName(?string $inGameName): void
    {
        $this->inGameName = $inGameName;
    }

    public static function getTargetType(): TargetType
    {
        return TargetType::Character;
    }

}
