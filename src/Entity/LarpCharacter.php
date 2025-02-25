<?php

namespace App\Entity;

use App\Entity\Trait\UuidTraitEntity;
use App\Repository\LarpCharacterRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[Gedmo\Loggable]
#[ORM\Entity(repositoryClass: LarpCharacterRepository::class)]
class LarpCharacter
{
    use UuidTraitEntity;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Gedmo\Versioned]
    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\ManyToOne(inversedBy: 'characters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Larp $larp = null;

    // The owning side of the one-to-one self-referencing relation:
    #[ORM\OneToOne(targetEntity: self::class, inversedBy: 'continuation')]
    #[ORM\JoinColumn(name: "previous_character_id", referencedColumnName: "id", nullable: true)]
    private ?LarpCharacter $previousCharacter = null;

    // Inverse side, no join column here:
    #[ORM\OneToOne(targetEntity: self::class, mappedBy: 'previousCharacter')]
    private ?LarpCharacter $continuation = null;

    // New field for post-LARP fate, with versioning enabled:
    #[Gedmo\Versioned]
    #[ORM\Column(type: "text", nullable: true)]
    private ?string $postLarpFate = null;

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
}
